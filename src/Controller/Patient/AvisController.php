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

#[Route('/patient')]
final class AvisController extends AbstractController
{
    #[Route('/programmes', name: 'app_patient_programmes_index', methods: ['GET'])]
    public function browsePrograms(Request $request, ProgrammeBienEtreRepository $repo): Response
    {
        $q = trim($request->query->getString('q'));
        $jour = $request->query->getInt('jour', 0);
        $programmes = $repo->createQueryBuilder('p')
            ->leftJoin('p.psychologue', 'psy')->addSelect('psy')
            ->leftJoin('p.activites', 'act')->addSelect('act')
            ->leftJoin('p.avis', 'av')->addSelect('av')
            ->andWhere('LOWER(COALESCE(p.statut, :defaultStatut)) = :actif')
            ->setParameter('defaultStatut', 'actif')
            ->setParameter('actif', 'actif')
            ->orderBy('p.id', 'DESC')
            ->getQuery()
            ->getResult();

        if ($q !== '') {
            $needle = function_exists('mb_strtolower') ? mb_strtolower($q) : strtolower($q);
            $programmes = array_values(array_filter($programmes, static function (ProgrammeBienEtre $p) use ($needle): bool {
                $nom = $p->getNom() ?? '';
                $objectif = $p->getObjectif() ?? '';
                $psyNom = $p->getPsychologue()?->getNom() ?? '';
                $psyPrenom = $p->getPsychologue()?->getPrenom() ?? '';
                $haystack = trim($nom.' '.$objectif.' '.$psyNom.' '.$psyPrenom);
                $haystack = function_exists('mb_strtolower') ? mb_strtolower($haystack) : strtolower($haystack);

                return str_contains($haystack, $needle);
            }));
        }

        if ($jour > 0) {
            $programmes = array_values(array_filter($programmes, static function (ProgrammeBienEtre $p) use ($jour): bool {
                foreach ($p->getActivites() as $activite) {
                    if ($activite->getJour() === $jour) {
                        return true;
                    }
                }

                return false;
            }));
        }

        return $this->render('patient/programmes/index.html.twig', [
            'programmes' => $programmes,
            'q' => $q,
            'jour' => $jour > 0 ? $jour : '',
        ]);
    }

    #[Route('/programmes/{id}', name: 'app_patient_programme_show', methods: ['GET'])]
    public function showProgramme(ProgrammeBienEtre $programme): Response
    {
        return $this->render('patient/programmes/show.html.twig', [
            'programme' => $programme,
        ]);
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
            $avis->setPsychologue($programme->getPsychologue());
            $avis->setDateAvis(new \DateTime());
            $em->persist($avis);
            $em->flush();

            $this->addFlash('success', 'Merci pour votre avis !');

            return $this->redirectToRoute('app_patient_programme_show', ['id' => $programme->getId()]);
        }

        return $this->render('patient/avis/new.html.twig', [
            'programme' => $programme,
            'avis' => $avis,
            'form' => $form,
        ]);
    }

    #[Route('/mes-avis', name: 'app_patient_avis_index', methods: ['GET'])]
    public function myAvis(EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        $avisRepo = $em->getRepository(Avis::class);
        $avis = $avisRepo->findBy(['patient' => $user], ['dateAvis' => 'DESC']);

        return $this->render('patient/avis/index.html.twig', [
            'avis_list' => $avis,
        ]);
    }

    #[Route('/avis/{id}/edit', name: 'app_patient_avis_edit', methods: ['GET', 'POST'])]
    public function editAvis(Avis $avis, Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(AvisType::class, $avis);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            $this->addFlash('success', 'Avis modifié avec succès !');

            return $this->redirectToRoute('app_patient_avis_index');
        }

        return $this->render('patient/avis/edit.html.twig', [
            'avis' => $avis,
            'form' => $form,
        ]);
    }

    #[Route('/avis/{id}/delete', name: 'app_patient_avis_delete', methods: ['POST'])]
    public function deleteAvis(Avis $avis, Request $request, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete'.$avis->getId(), $request->getPayload()->getString('_token'))) {
            $em->remove($avis);
            $em->flush();
            $this->addFlash('success', 'Avis supprimé.');
        }

        return $this->redirectToRoute('app_patient_avis_index');
    }
}
