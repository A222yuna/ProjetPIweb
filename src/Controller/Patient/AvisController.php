<?php

namespace App\Controller\Patient;

use App\Entity\Avis;
use App\Entity\ProgrammeBienEtre;
use App\Entity\ActiviteProgramme;
use App\Form\AvisType;
use App\Repository\ProgrammeBienEtreRepository;
use App\Service\GeminiService;
use Doctrine\ORM\EntityManagerInterface;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/patient')]
final class AvisController extends AbstractController
{
    #[Route('/programmes', name: 'app_patient_programmes_index', methods: ['GET'])]
    public function browsePrograms(Request $request, ProgrammeBienEtreRepository $repo): Response
    {
        $q = trim($request->query->getString('q'));
        $programmes = $repo->createQueryBuilder('p')
            ->leftJoin('p.psychologue', 'psy')->addSelect('psy')
            ->leftJoin('p.activites', 'act')->addSelect('act')
            ->leftJoin('p.avis', 'av')->addSelect('av')
            ->orderBy('p.id', 'DESC')
            ->getQuery()->getResult();

        if ($q !== '') {
            $needle = mb_strtolower($q);
            $programmes = array_values(array_filter($programmes, static function (ProgrammeBienEtre $p) use ($needle): bool {
                $haystack = mb_strtolower(($p->getNom() ?? '') . ' ' . ($p->getObjectif() ?? '') . ' ' . ($p->getPsychologue()?->getNom() ?? '') . ' ' . ($p->getPsychologue()?->getPrenom() ?? ''));
                return str_contains($haystack, $needle);
            }));
        }

        return $this->render('patient/programmes/index.html.twig', ['programmes' => $programmes, 'q' => $q]);
    }

    #[Route('/programmes/{id}', name: 'app_patient_programme_show', methods: ['GET'])]
    public function showProgramme(ProgrammeBienEtre $programme): Response
    {
        return $this->render('patient/programmes/show.html.twig', ['programme' => $programme]);
    }

    #[Route('/programmes/{id}/pdf', name: 'app_patient_programme_pdf', methods: ['GET'])]
    public function downloadProgrammePdf(ProgrammeBienEtre $programme): Response
    {
        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($this->renderView('patient/programmes/pdf.html.twig', ['programme' => $programme]));
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '_', (string) ($programme->getNom() ?? 'programme'));
        return new Response($dompdf->output(), Response::HTTP_OK, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('attachment; filename="programme_%s.pdf"', trim($safeName, '_')),
        ]);
    }

    #[Route('/activite/{id}/ai-advice', name: 'app_patient_activite_ai_advice', methods: ['GET'])]
    public function getActivityAdvice(ActiviteProgramme $activite, GeminiService $gemini): JsonResponse
    {
        $advice = $gemini->getActivityAdvice($activite->getTitre() ?? 'Activite', $activite->getDescription() ?? '');
        return $this->json(['advice' => $advice]);
    }

    #[Route('/programmes/{id}/avis/new', name: 'app_patient_avis_new', methods: ['GET', 'POST'])]
    public function newAvis(ProgrammeBienEtre $programme, Request $request, EntityManagerInterface $em): Response
    {
        $avis = new Avis();
        $form = $this->createForm(AvisType::class, $avis);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $avis->setProgramme($programme);
            $avis->setPatient($this->getUser());
            $avis->setDateAvis(new \DateTime());
            $em->persist($avis);
            $em->flush();
            $this->addFlash('success', 'Merci pour votre avis !');
            return $this->redirectToRoute('app_patient_programme_show', ['id' => $programme->getId()]);
        }

        return $this->render('patient/avis/new.html.twig', ['programme' => $programme, 'avis' => $avis, 'form' => $form]);
    }

    #[Route('/mes-avis', name: 'app_patient_avis_index', methods: ['GET'])]
    public function myAvis(EntityManagerInterface $em): Response
    {
        // Avis has no patient field - show all avis ordered by date
        $avis = $em->getRepository(Avis::class)->findBy([], ['dateAvis' => 'DESC']);
        return $this->render('patient/avis/index.html.twig', ['avis_list' => $avis]);
    }

    #[Route('/avis/{id}/edit', name: 'app_patient_avis_edit', methods: ['GET', 'POST'])]
    public function editAvis(Avis $avis, Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(AvisType::class, $avis);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Avis modifie avec succes !');
            return $this->redirectToRoute('app_patient_avis_index');
        }

        return $this->render('patient/avis/edit.html.twig', ['programme' => $avis->getProgramme(), 'avis' => $avis, 'form' => $form]);
    }

    #[Route('/avis/{id}/delete', name: 'app_patient_avis_delete', methods: ['POST'])]
    public function deleteAvis(Avis $avis, Request $request, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete' . $avis->getId(), $request->getPayload()->getString('_token'))) {
            $em->remove($avis);
            $em->flush();
            $this->addFlash('success', 'Avis supprime.');
        }
        return $this->redirectToRoute('app_patient_avis_index');
    }
}
