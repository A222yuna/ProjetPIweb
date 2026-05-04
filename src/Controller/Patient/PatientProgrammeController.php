<?php

namespace App\Controller\Patient;

use App\Entity\Avis;
use App\Entity\ProgrammeBienEtre;
use App\Form\AvisType;
use App\Repository\ProgrammeBienEtreRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/patient/programmes')]
#[IsGranted('ROLE_PATIENT')]
class PatientProgrammeController extends AbstractController
{
    #[Route('/', name: 'app_patient_programmes_index', methods: ['GET'])]
    public function index(Request $request, ProgrammeBienEtreRepository $repo): Response
    {
        $search = $request->query->get('q');
        $difficulty = $request->query->get('difficulty');

        $queryBuilder = $repo->createQueryBuilder('p')
            ->where('p.statut = :statut')
            ->setParameter('statut', 'Publié');

        if ($search) {
            $queryBuilder->andWhere('p.nom LIKE :search OR p.objectif LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        if ($difficulty) {
            $queryBuilder->andWhere('p.niveauDifficulte = :difficulty')
                ->setParameter('difficulty', $difficulty);
        }

        return $this->render('patient/programme/index.html.twig', [
            'programmes' => $queryBuilder->getQuery()->getResult(),
            'search' => $search,
            'current_difficulty' => $difficulty,
        ]);
    }

    #[Route('/{id}', name: 'app_patient_programmes_show', methods: ['GET', 'POST'])]
    public function show(Request $request, ProgrammeBienEtre $programme, EntityManagerInterface $em): Response
    {
        $avis = new Avis();
        $avis->setProgramme($programme);
        $avis->setPsychologue($programme->getPsychologue());
        $avis->setDateAvis(new \DateTimeImmutable());

        $form = $this->createForm(AvisType::class, $avis);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($avis);
            $em->flush();
            $this->addFlash('success', 'Votre avis a été publié.');
            return $this->redirectToRoute('app_patient_programmes_show', ['id' => $programme->getId()]);
        }

        return $this->render('patient/programme/show.html.twig', [
            'programme' => $programme,
            'form' => $form,
        ]);
    }
}
