<?php

namespace App\Controller\Psychologue;

use App\Entity\Disponibilite;
use App\Entity\User;
use App\Form\DisponibiliteType;
use App\Repository\DisponibiliteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

use App\Service\DisponibiliteExportService;
use App\Service\PdfService;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Route('/psychologue/disponibilites')]
#[IsGranted('ROLE_PSYCHOLOGUE')]
final class DisponibiliteController extends AbstractController
{
    #[Route('/export/excel', name: 'app_psychologue_disponibilites_export_excel', methods: ['GET'])]
    public function exportExcel(DisponibiliteExportService $exportService): StreamedResponse
    {
        $spreadsheet = $exportService->exportAll(); // Or exportByCabinet if needed
        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');

        return new StreamedResponse(function () use ($writer) {
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="disponibilites_' . date('Y-m-d') . '.xlsx"',
        ]);
    }

    #[Route('/export/pdf', name: 'app_psychologue_disponibilites_export_pdf', methods: ['GET'])]
    public function exportPdf(DisponibiliteRepository $repo, PdfService $pdfService): Response
    {
        $dispos = $repo->findForPsychologue($this->getUser());
        $html = $this->renderView('pdf/psychologue_disponibilites.html.twig', [
            'disponibilites' => $dispos,
            'psychologue' => $this->getUser(),
            'generatedAt' => new \DateTime(),
        ]);

        $options = new \Dompdf\Options();
        $options->set('defaultFont', 'Arial');
        $dompdf = new \Dompdf\Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        return new Response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="disponibilites.pdf"',
        ]);
    }

    #[Route('/', name: 'app_psychologue_disponibilites_index', methods: ['GET'])]
    public function index(Request $request, DisponibiliteRepository $disponibilites): Response
    {
        $user = $this->getUser();
        \assert($user instanceof User);

        // --- RECHERCHE & TRI ---
        $search  = $request->query->getString('search');
        $sortBy  = $request->query->getString('sort', 'jour');
        $sortDir = strtoupper($request->query->getString('dir', 'ASC')) === 'DESC' ? 'DESC' : 'ASC';
        $page    = max(1, $request->query->getInt('page', 1));
        $perPage = 10;

        $allowedSorts = ['jour', 'heureDebut', 'heureFin', 'dureeConsultation'];
        if (!\in_array($sortBy, $allowedSorts, true)) {
            $sortBy = 'jour';
        }

        $result = $disponibilites->findForPsychologuePaginatedFiltered(
            $user, $search, $sortBy, $sortDir, $page, $perPage
        );

        return $this->render('psychologue/disponibilites/index.html.twig', [
            'disponibilites' => $result['items'],
            'total'          => $result['total'],
            'page'           => $page,
            'per_page'       => $perPage,
            'total_pages'    => max(1, (int) ceil($result['total'] / $perPage)),
            'search'         => $search,
            'sort'           => $sortBy,
            'dir'            => $sortDir,
            'next_dir'       => $sortDir === 'ASC' ? 'DESC' : 'ASC',
        ]);
    }

    #[Route('/new', name: 'app_psychologue_disponibilites_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em, DisponibiliteRepository $disponibilites): Response
    {
        $user = $this->getUser();
        \assert($user instanceof User);

        $disponibilite = new Disponibilite();
        $form = $this->createForm(DisponibiliteType::class, $disponibilite, [
            'psychologue_user' => $user,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // RULE — Vérifier ownership cabinet
            $cabinetId = $disponibilite->getCabinet()?->getId();
            if (!$cabinetId || !$disponibilites->canManageCabinetId($user, $cabinetId)) {
                $this->addFlash('error', 'Ce cabinet ne vous appartient pas.');
                return $this->redirectToRoute('app_psychologue_disponibilites_new');
            }
            // RULE 2 — heure_fin after heure_debut
            if ($disponibilite->getHeureFin() && $disponibilite->getHeureDebut()
                && $disponibilite->getHeureFin() <= $disponibilite->getHeureDebut()) {
                $this->addFlash('error', "L'heure de fin doit être strictement après l'heure de début.");
                return $this->redirectToRoute('app_psychologue_disponibilites_new');
            }
            // RULE 3 — Duration fits window (warning only)
            $this->checkDurationWarning($disponibilite);

            $em->persist($disponibilite);
            $em->flush();
            $this->addFlash('success', 'Disponibilité créée avec succès ✓');
            return $this->redirectToRoute('app_psychologue_disponibilites_index');
        }

        return $this->render('psychologue/disponibilites/form.html.twig', [
            'form'    => $form,
            'is_edit' => false,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_psychologue_disponibilites_edit', methods: ['GET', 'POST'])]
    public function edit(int $id, Request $request, DisponibiliteRepository $disponibilites, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        \assert($user instanceof User);

        $disponibilite = $disponibilites->find($id);
        if (!$disponibilite) {
            throw $this->createNotFoundException('Disponibilité introuvable.');
        }
        if (!$disponibilites->isOwnedByPsychologue($disponibilite, $user)) {
            throw $this->createAccessDeniedException('Cette disponibilité ne vous appartient pas.');
        }

        $form = $this->createForm(DisponibiliteType::class, $disponibilite, [
            'psychologue_user' => $user,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $cabinetId = $disponibilite->getCabinet()?->getId();
            if (!$cabinetId || !$disponibilites->canManageCabinetId($user, $cabinetId)) {
                $this->addFlash('error', 'Ce cabinet ne vous appartient pas.');
                return $this->redirectToRoute('app_psychologue_disponibilites_edit', ['id' => $disponibilite->getId()]);
            }
            if ($disponibilite->getHeureFin() && $disponibilite->getHeureDebut()
                && $disponibilite->getHeureFin() <= $disponibilite->getHeureDebut()) {
                $this->addFlash('error', "L'heure de fin doit être strictement après l'heure de début.");
                return $this->redirectToRoute('app_psychologue_disponibilites_edit', ['id' => $disponibilite->getId()]);
            }
            $this->checkDurationWarning($disponibilite);

            $em->flush();
            $this->addFlash('success', 'Disponibilité mise à jour ✓');
            return $this->redirectToRoute('app_psychologue_disponibilites_index');
        }

        return $this->render('psychologue/disponibilites/form.html.twig', [
            'form'          => $form,
            'is_edit'       => true,
            'disponibilite' => $disponibilite,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_psychologue_disponibilites_delete', methods: ['POST'])]
    public function delete(int $id, Request $request, DisponibiliteRepository $disponibilites, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        \assert($user instanceof User);

        $disponibilite = $disponibilites->find($id);
        if (!$disponibilite) {
            throw $this->createNotFoundException();
        }
        if (!$disponibilites->isOwnedByPsychologue($disponibilite, $user)) {
            throw $this->createAccessDeniedException();
        }
        if (!$this->isCsrfTokenValid('delete_disponibilite_'.$disponibilite->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('app_psychologue_disponibilites_index');
        }

        $em->remove($disponibilite);
        $em->flush();
        $this->addFlash('success', 'Disponibilité supprimée.');
        return $this->redirectToRoute('app_psychologue_disponibilites_index');
    }

    private function checkDurationWarning(Disponibilite $d): void
    {
        $start = $d->getHeureDebut();
        $end   = $d->getHeureFin();
        if (!$start || !$end || $d->getDureeConsultation() <= 0) {
            return;
        }
        $totalMin = ((int)$end->format('H')) * 60 + (int)$end->format('i')
                  - (((int)$start->format('H')) * 60 + (int)$start->format('i'));
        if ($totalMin > 0 && $totalMin % $d->getDureeConsultation() !== 0) {
            $this->addFlash('warning', 'La durée de consultation ne divise pas exactement le créneau horaire.');
        }
    }
}