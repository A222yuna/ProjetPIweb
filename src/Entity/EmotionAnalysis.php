<?php

namespace App\Entity;

use App\Repository\EmotionAnalysisRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Stores the AI emotion analysis result for a cabinet.
 * Recomputed on demand and cached here.
 */
#[ORM\Entity(repositoryClass: EmotionAnalysisRepository::class)]
#[ORM\Table(name: 'emotion_analysis')]
class EmotionAnalysis
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'cabinet_id', referencedColumnName: 'id_cabinet', nullable: false, onDelete: 'CASCADE')]
    private ?Cabinet $cabinet = null;

    /** Number of reviews analysed */
    #[ORM\Column]
    private int $totalReviews = 0;

    /** Sentiment breakdown (%) */
    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2)]
    private float $positifPct = 0;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2)]
    private float $neutrePct = 0;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2)]
    private float $negatifPct = 0;

    /** Emotion scores 0–100 */
    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2)]
    private float $confianceScore = 0;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2)]
    private float $satisfactionScore = 0;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2)]
    private float $anxieteScore = 0;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2)]
    private float $deceptionScore = 0;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2)]
    private float $stressScore = 0;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2)]
    private float $gratitudeScore = 0;

    /** Alert flag: true when negatifPct > 40 */
    #[ORM\Column]
    private bool $alerteActive = false;

    /** Top frequent words JSON array */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $topMots = null;

    /** Raw per-review results JSON */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $detailsAnalyse = null;

    #[ORM\Column(name: 'analysed_at')]
    private ?\DateTimeImmutable $analysedAt = null;

    public function __construct()
    {
        $this->analysedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getCabinet(): ?Cabinet { return $this->cabinet; }
    public function setCabinet(?Cabinet $v): static { $this->cabinet = $v; return $this; }

    public function getTotalReviews(): int { return $this->totalReviews; }
    public function setTotalReviews(int $v): static { $this->totalReviews = $v; return $this; }

    public function getPositifPct(): float { return (float) $this->positifPct; }
    public function setPositifPct(float $v): static { $this->positifPct = $v; return $this; }

    public function getNeutrePct(): float { return (float) $this->neutrePct; }
    public function setNeutrePct(float $v): static { $this->neutrePct = $v; return $this; }

    public function getNegatifPct(): float { return (float) $this->negatifPct; }
    public function setNegatifPct(float $v): static { $this->negatifPct = $v; return $this; }

    public function getConfianceScore(): float { return (float) $this->confianceScore; }
    public function setConfianceScore(float $v): static { $this->confianceScore = $v; return $this; }

    public function getSatisfactionScore(): float { return (float) $this->satisfactionScore; }
    public function setSatisfactionScore(float $v): static { $this->satisfactionScore = $v; return $this; }

    public function getAnxieteScore(): float { return (float) $this->anxieteScore; }
    public function setAnxieteScore(float $v): static { $this->anxieteScore = $v; return $this; }

    public function getDeceptionScore(): float { return (float) $this->deceptionScore; }
    public function setDeceptionScore(float $v): static { $this->deceptionScore = $v; return $this; }

    public function getStressScore(): float { return (float) $this->stressScore; }
    public function setStressScore(float $v): static { $this->stressScore = $v; return $this; }

    public function getGratitudeScore(): float { return (float) $this->gratitudeScore; }
    public function setGratitudeScore(float $v): static { $this->gratitudeScore = $v; return $this; }

    public function isAlerteActive(): bool { return $this->alerteActive; }
    public function setAlerteActive(bool $v): static { $this->alerteActive = $v; return $this; }

    public function getTopMots(): ?array { return $this->topMots; }
    public function setTopMots(?array $v): static { $this->topMots = $v; return $this; }

    public function getDetailsAnalyse(): ?array { return $this->detailsAnalyse; }
    public function setDetailsAnalyse(?array $v): static { $this->detailsAnalyse = $v; return $this; }

    public function getAnalysedAt(): ?\DateTimeImmutable { return $this->analysedAt; }
    public function setAnalysedAt(\DateTimeImmutable $v): static { $this->analysedAt = $v; return $this; }
}
