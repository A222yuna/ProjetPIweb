<?php

namespace App\Service;

use App\Entity\Cabinet;
use App\Entity\EmotionAnalysis;
use App\Repository\RatingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * PsyMoodAnalysisService
 *
 * Analyses patient review texts using HuggingFace sentiment API.
 * Falls back to a local lexicon-based analysis when the API is unavailable
 * or when no HuggingFace token is configured.
 */
class PsyMoodAnalysisService
{
    /** HuggingFace model for French sentiment */
    private const HF_MODEL = 'cardiffnlp/twitter-xlm-roberta-base-sentiment';
    private const HF_API    = 'https://api-inference.huggingface.co/models/';

    /** Negative alert threshold */
    private const ALERT_THRESHOLD = 40.0;

    /** French stop words to exclude from word cloud */
    private const STOP_WORDS = [
        'le','la','les','de','du','des','un','une','et','en','au','aux',
        'je','il','elle','nous','vous','ils','elles','ce','se','sa','son',
        'mon','ma','mes','ton','ta','tes','que','qui','ne','pas','plus',
        'très','bien','est','sont','été','avoir','être','avec','pour',
        'sur','dans','par','mais','ou','donc','or','ni','car','si','tout',
        'cette','cet','ces','leur','leurs','y','on','me','te','lui',
    ];

    public function __construct(
        private HttpClientInterface  $httpClient,
        private EntityManagerInterface $em,
        private RatingRepository     $ratingRepo,
        private ?string              $hfToken = ''
    ) {
        $this->hfToken = $hfToken ?? '';
    }

    // =========================================================================
    // PUBLIC API
    // =========================================================================

    /**
     * Run full analysis for a cabinet and persist the result.
     */
    public function analyseAndPersist(Cabinet $cabinet): EmotionAnalysis
    {
        $ratings = $this->ratingRepo->findAllByCabinet($cabinet);

        // Collect non-empty comments
        $texts = array_filter(
            array_map(fn($r) => trim((string) $r->getCommentaireRating()), $ratings),
            fn($t) => strlen($t) >= 5
        );

        $result = $this->buildAnalysis(array_values($texts));

        // Always create a fresh analysis record (delete old one first)
        $existing = $this->em->getRepository(EmotionAnalysis::class)
            ->findLatestByCabinet($cabinet);
        if ($existing) {
            $this->em->remove($existing);
            $this->em->flush();
        }

        $analysis = new EmotionAnalysis();
        $analysis->setCabinet($cabinet);
        $analysis->setTotalReviews(count($ratings));
        $analysis->setPositifPct($result['positif_pct']);
        $analysis->setNeutrePct($result['neutre_pct']);
        $analysis->setNegatifPct($result['negatif_pct']);
        $analysis->setConfianceScore($result['emotions']['confiance']);
        $analysis->setSatisfactionScore($result['emotions']['satisfaction']);
        $analysis->setAnxieteScore($result['emotions']['anxiete']);
        $analysis->setDeceptionScore($result['emotions']['deception']);
        $analysis->setStressScore($result['emotions']['stress']);
        $analysis->setGratitudeScore($result['emotions']['gratitude']);
        $analysis->setAlerteActive($result['negatif_pct'] > self::ALERT_THRESHOLD);
        $analysis->setTopMots($result['top_mots']);
        $analysis->setDetailsAnalyse($result['details']);
        $analysis->setAnalysedAt(new \DateTimeImmutable());

        $this->em->persist($analysis);
        $this->em->flush();

        return $analysis;
    }

    // =========================================================================
    // CORE ANALYSIS
    // =========================================================================

    private function buildAnalysis(array $texts): array
    {
        if (empty($texts)) {
            return $this->emptyResult();
        }

        $details   = [];
        $sentiments = ['positif' => 0, 'neutre' => 0, 'negatif' => 0];

        foreach ($texts as $text) {
            $sentiment = $this->classifySentiment($text);
            $emotions  = $this->detectEmotions($text, $sentiment);
            $details[] = [
                'text'      => mb_substr($text, 0, 120),
                'sentiment' => $sentiment,
                'emotions'  => $emotions,
            ];
            $sentiments[$sentiment]++;
        }

        $total = count($texts);
        $positifPct  = round($sentiments['positif'] / $total * 100, 1);
        $neutrePct   = round($sentiments['neutre']  / $total * 100, 1);
        $negatifPct  = round($sentiments['negatif'] / $total * 100, 1);

        // Aggregate emotion scores
        $emotions = $this->aggregateEmotions($details);

        return [
            'positif_pct' => $positifPct,
            'neutre_pct'  => $neutrePct,
            'negatif_pct' => $negatifPct,
            'emotions'    => $emotions,
            'top_mots'    => $this->extractTopWords($texts),
            'details'     => $details,
        ];
    }

    // =========================================================================
    // SENTIMENT CLASSIFICATION
    // Tries HuggingFace API first, falls back to lexicon
    // =========================================================================

    private function classifySentiment(string $text): string
    {
        if ($this->hfToken !== '') {
            try {
                $response = $this->httpClient->request('POST', self::HF_API . self::HF_MODEL, [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $this->hfToken,
                        'Content-Type'  => 'application/json',
                    ],
                    'json'    => ['inputs' => $text],
                    'timeout' => 5,
                ]);

                $data = $response->toArray(false);

                if (isset($data[0]) && is_array($data[0])) {
                    $best = array_reduce($data[0], function ($carry, $item) {
                        return ($carry === null || $item['score'] > $carry['score']) ? $item : $carry;
                    });

                    $label = strtolower($best['label'] ?? '');
                    if (str_contains($label, 'pos')) return 'positif';
                    if (str_contains($label, 'neg')) return 'negatif';
                    return 'neutre';
                }
            } catch (\Throwable) {
                // Fall through to lexicon
            }
        }

        return $this->lexiconSentiment($text);
    }

    /**
     * Lexicon-based French sentiment analysis with negation handling.
     */
    private function lexiconSentiment(string $text): string
    {
        $text = mb_strtolower(trim($text));

        // --- Negation detection ---
        $hasNegation = (bool) preg_match(
            "/\\b(n['\"]est pas|ne\\s+\\w+\\s+pas|pas\\s+du\\s+tout|pas\\s+très|pas\\s+assez|jamais|sans|aucune?|manque\\s+de)\\b/",
            $text
        );

        // --- Strong negative phrases (always negative) ---
        $strongNegativePhrases = [
            "n'est pas ponctuel","n'est pas ponctuell","n'est pas ponctulle",
            "pas ponctuel","pas à l'heure","toujours en retard",
            "pas professionnel","pas compétent","pas à l'écoute",
            "pas disponible","pas sérieux","pas rassurant","pas efficace",
            "pas agréable","pas sympathique","pas accueillant",
            "manque de ponctualité","manque de professionnalisme","manque d'écoute",
            "très décevant","vraiment décevant","très mauvais","vraiment mauvais",
            "à éviter","je déconseille","pas recommandé","pas du tout",
        ];
        foreach ($strongNegativePhrases as $phrase) {
            if (str_contains($text, $phrase)) return 'negatif';
        }

        // --- "très + anything" = positif (unless negation) ---
        if (preg_match('/\btrès\s+\w+/u', $text) && !$hasNegation) {
            return 'positif';
        }

        // --- "vraiment + anything positive" ---
        if (preg_match('/\bvraiment\s+\w+/u', $text) && !$hasNegation) {
            return 'positif';
        }

        // --- "j'aime / j'adore / trop bien" patterns ---
        // Check negated forms first
        if (preg_match("/j['\"]aime pas|j['\"]adore pas|j['\"]aime plus|n['\"]aime pas/u", $text)) {
            return 'negatif';
        }
        if (preg_match("/\\b(j['\"]aime|j['\"]adore|trop bien|trop cool|trop top|j['\"]apprécie)\\b/u", $text) && !$hasNegation) {
            return 'positif';
        }

        // --- "trop + [positive adjective]" ---
        if (preg_match('/\btrop\s+(bien|bon|bonne|super|top|cool|génial|sympa|agréable|professionnel|compétent|rassurant|efficace|ponctuel|satisfait|content)/u', $text) && !$hasNegation) {
            return 'positif';
        }

        // --- Word-level scoring ---
        $positiveWords = [
            'excellent','excellente','parfait','super','génial','géniale','merci','bravo',
            'professionnel','professionnelle','professionele','professionelle',
            'profesionnel','profesionnelle','profitonnele','profitonnelle',
            'profitionelle','proffessionnel','proffessionnelle',
            'compétent','compétente','attentif','attentive','bienveillant',
            'rassurant','efficace','recommande','satisfait','content',
            'heureux','heureuse','agréable','chaleureux','disponible',
            'confiance','sérieux','ponctuel','accueil','sympathique',
            'formidable','remarquable','impressionnant','top','bien',
            'bon','bonne','qualité','rapide','souriant','gentil',
            'magnifique','fantastique','incroyable','extraordinaire',
            // Expressions familières positives
            'aime','adore','jaime','jadore','trop','cool','sympa',
            'nickel','impeccable','irréprochable','optimal','idéal',
        ];

        $negativeWords = [
            'mauvais','mauvaise','nul','nulle','décevant','décevante',
            'déçu','déçue','incompétent','incompétente','impoli','impolie',
            'désagréable','retard','problème','erreur','froid','froide',
            'distant','distante','stressant','horrible','catastrophe',
            'inutile','inefficace','absent','absente','oublié','négligent',
            'brutal','lent','lente','compliqué','difficile','insatisfait',
            'déplorable','irrespectueux','arrogant','désorganisé',
        ];

        $pos = 0; $neg = 0;
        $words = preg_split('/[\s\.,!?;:()\[\]"]+/u', $text, -1, PREG_SPLIT_NO_EMPTY);
        foreach ($words as $w) {
            $w = preg_replace('/[^a-zàâäéèêëîïôùûüç]/u', '', $w);
            if (in_array($w, $positiveWords, true)) $pos++;
            if (in_array($w, $negativeWords, true)) $neg++;
        }

        // Negation flips positive score to negative
        if ($hasNegation) { $neg += $pos; $pos = 0; }

        if ($pos > $neg) return 'positif';
        if ($neg > $pos) return 'negatif';

        // Short text fallback: scan for any known word
        if (mb_strlen($text) < 40) {
            foreach ($positiveWords as $w) { if (str_contains($text, $w)) return 'positif'; }
            foreach ($negativeWords as $w) { if (str_contains($text, $w)) return 'negatif'; }
            // Check for "aime" / "adore" with apostrophe
            if (preg_match("/j['\"]aime|j['\"]adore/u", $text)) return 'positif';
            if (preg_match("/j['\"]aime pas|j['\"]adore pas/u", $text)) return 'negatif';
        }

        return 'neutre';
    }

    // =========================================================================
    // EMOTION DETECTION (lexicon-based, always local)
    // =========================================================================

    private function detectEmotions(string $text, string $sentiment): array
    {
        $textLower = mb_strtolower($text);

        // Detect negation in text
        $hasNegation = (bool) preg_match(
            "/\\b(n['\"]est pas|ne\\s+\\w+\\s+pas|pas\\s+du\\s+tout|pas\\s+très|jamais|sans|aucun)\\b/",
            $textLower
        );

        $lexicons = [
            'confiance'    => ['confiance','fiable','sérieux','professionnel','compétent','sûr','rassurant','crédible','honnête'],
            'satisfaction' => ['satisfait','content','heureux','bien','parfait','excellent','super','recommande','merci','top'],
            'anxiete'      => ['anxieux','inquiet','peur','stress','angoisse','nerveux','appréhension','incertain','mal à l\'aise'],
            'deception'    => ['déçu','décevant','mauvais','nul','inutile','inefficace','absent','oublié','déplorable','honteux'],
            'stress'       => ['stress','pressé','urgent','retard','attente','long','lent','compliqué','difficile','chaotique'],
            'gratitude'    => ['merci','reconnaissant','gratitude','remercie','apprécié','formidable','bravo','félicitations'],
        ];

        // Negative emotion triggers (phrases that directly signal these emotions)
        $negativeEmotionPhrases = [
            'anxiete'   => ['pas rassurant','pas à l\'aise','mal à l\'aise','inquiétant','stressant'],
            'deception' => ['pas ponctuel','pas professionnel','pas compétent','pas à l\'écoute','décevant','déçu'],
            'stress'    => ['toujours en retard','très en retard','longue attente','fait attendre'],
        ];

        $scores = [];
        foreach ($lexicons as $emotion => $words) {
            $count = 0;
            foreach ($words as $w) {
                if (str_contains($textLower, $w)) $count++;
            }
            $scores[$emotion] = min(round($count / max(count($words) * 0.25, 1) * 100), 100);
        }

        // Apply negative phrase boosts
        foreach ($negativeEmotionPhrases as $emotion => $phrases) {
            foreach ($phrases as $phrase) {
                if (str_contains($textLower, $phrase)) {
                    $scores[$emotion] = min($scores[$emotion] + 40, 100);
                }
            }
        }

        // If negation detected, boost negative emotions and reduce positive ones
        if ($hasNegation) {
            $scores['anxiete']      = min($scores['anxiete']      + 25, 100);
            $scores['deception']    = min($scores['deception']    + 30, 100);
            $scores['confiance']    = max($scores['confiance']    - 20, 0);
            $scores['satisfaction'] = max($scores['satisfaction'] - 20, 0);
        }

        // Sentiment-based global adjustment
        if ($sentiment === 'positif') {
            $scores['confiance']    = min($scores['confiance']    + 20, 100);
            $scores['satisfaction'] = min($scores['satisfaction'] + 25, 100);
            $scores['gratitude']    = min($scores['gratitude']    + 15, 100);
            $scores['anxiete']      = max($scores['anxiete']      - 10, 0);
            $scores['deception']    = max($scores['deception']    - 10, 0);
        } elseif ($sentiment === 'negatif') {
            $scores['anxiete']      = min($scores['anxiete']      + 20, 100);
            $scores['deception']    = min($scores['deception']    + 25, 100);
            $scores['stress']       = min($scores['stress']       + 15, 100);
            $scores['confiance']    = max($scores['confiance']    - 15, 0);
            $scores['satisfaction'] = max($scores['satisfaction'] - 15, 0);
        }

        return $scores;
    }

    private function aggregateEmotions(array $details): array
    {
        if (empty($details)) {
            return array_fill_keys(['confiance','satisfaction','anxiete','deception','stress','gratitude'], 0);
        }

        $sums = array_fill_keys(['confiance','satisfaction','anxiete','deception','stress','gratitude'], 0);
        foreach ($details as $d) {
            foreach ($sums as $k => $_) {
                $sums[$k] += $d['emotions'][$k] ?? 0;
            }
        }

        $count = count($details);
        return array_map(fn($v) => round($v / $count, 1), $sums);
    }

    // =========================================================================
    // WORD CLOUD
    // =========================================================================

    private function extractTopWords(array $texts, int $limit = 30): array
    {
        $freq = [];
        foreach ($texts as $text) {
            $words = preg_split('/[\s\.,!?;:()\[\]"\']+/u', mb_strtolower($text), -1, PREG_SPLIT_NO_EMPTY);
            foreach ($words as $word) {
                if (mb_strlen($word) < 3) continue;
                if (in_array($word, self::STOP_WORDS, true)) continue;
                $freq[$word] = ($freq[$word] ?? 0) + 1;
            }
        }

        arsort($freq);
        $top = array_slice($freq, 0, $limit, true);

        $result = [];
        foreach ($top as $word => $count) {
            $result[] = ['text' => $word, 'count' => $count];
        }
        return $result;
    }

    // =========================================================================
    // HELPERS
    // =========================================================================

    private function emptyResult(): array
    {
        return [
            'positif_pct' => 0,
            'neutre_pct'  => 0,
            'negatif_pct' => 0,
            'emotions'    => array_fill_keys(['confiance','satisfaction','anxiete','deception','stress','gratitude'], 0),
            'top_mots'    => [],
            'details'     => [],
        ];
    }
}
