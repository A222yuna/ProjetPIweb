<?php

namespace App\Controller\Admin;

use App\Entity\ActiviteProgramme;
use App\Entity\Avis;
use App\Entity\ProgrammeBienEtre;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/wellbeing')]
#[IsGranted('ROLE_ADMIN')]
final class WellBeingAdminController extends AbstractController
{
    #[Route('/programmes', name: 'app_admin_wellbeing_programmes', methods: ['GET'])]
    public function programmes(Request $request, EntityManagerInterface $em, PaginatorInterface $paginator): Response
    {
        $q = trim($request->query->getString('q'));
        $statut = trim($request->query->getString('statut'));
        $niveau = trim($request->query->getString('niveau'));

        $qb = $em->getRepository(ProgrammeBienEtre::class)->createQueryBuilder('p')
            ->leftJoin('p.psychologue', 'psy')->addSelect('psy')
            ->leftJoin('p.activites', 'act')->addSelect('act')
            ->leftJoin('p.avis', 'av')->addSelect('av')
            ->orderBy('p.id', 'DESC')
            ->groupBy('p.id', 'psy.id');

        if ($q !== '') {
            $qb->andWhere('LOWER(p.nom) LIKE :q OR LOWER(p.objectif) LIKE :q OR LOWER(psy.nom) LIKE :q OR LOWER(psy.prenom) LIKE :q')
                ->setParameter('q', '%'.mb_strtolower($q).'%');
        }
        if ($statut !== '') {
            $qb->andWhere('p.statut = :statut')->setParameter('statut', $statut);
        }
        if ($niveau !== '') {
            $qb->andWhere('p.niveauDifficulte = :niveau')->setParameter('niveau', $niveau);
        }

        $programmes = $paginator->paginate(
            $qb,
            max(1, $request->query->getInt('page', 1)),
            6
        );

        $stats = [
            'programmes_total'  => (int) $em->createQuery('SELECT COUNT(p.id) FROM App\Entity\ProgrammeBienEtre p')->getSingleScalarResult(),
            'programmes_actifs' => (int) $em->createQuery('SELECT COUNT(p.id) FROM App\Entity\ProgrammeBienEtre p WHERE p.statut = :statut')->setParameter('statut', 'actif')->getSingleScalarResult(),
            'activites_total'   => (int) $em->createQuery('SELECT COUNT(a.id) FROM App\Entity\ActiviteProgramme a')->getSingleScalarResult(),
            'avis_total'        => (int) $em->createQuery('SELECT COUNT(a.id) FROM App\Entity\Avis a')->getSingleScalarResult(),
        ];

        $distinctStatuts = $em->createQuery('SELECT DISTINCT p.statut FROM App\Entity\ProgrammeBienEtre p WHERE p.statut IS NOT NULL ORDER BY p.statut ASC')->getScalarResult();
        $distinctNiveaux = $em->createQuery('SELECT DISTINCT p.niveauDifficulte FROM App\Entity\ProgrammeBienEtre p WHERE p.niveauDifficulte IS NOT NULL ORDER BY p.niveauDifficulte ASC')->getScalarResult();

        if ($request->isXmlHttpRequest()) {
            return $this->render('admin/wellbeing/_programmes_table.html.twig', ['programmes' => $programmes]);
        }

        return $this->render('admin/wellbeing/programmes.html.twig', [
            'programmes' => $programmes,
            'stats'      => $stats,
            'q'          => $q,
            'statut'     => $statut,
            'niveau'     => $niveau,
            'statuts'    => array_map(static fn (array $row): string => (string) $row['statut'], $distinctStatuts),
            'niveaux'    => array_map(static fn (array $row): string => (string) $row['niveauDifficulte'], $distinctNiveaux),
        ]);
    }

    #[Route('/activites', name: 'app_admin_wellbeing_activites', methods: ['GET'])]
    public function activites(Request $request, EntityManagerInterface $em, PaginatorInterface $paginator): Response
    {
        $q          = trim($request->query->getString('q'));
        $type       = trim($request->query->getString('type'));
        $programmeId = $request->query->getInt('programme_id', 0);
        $jour       = trim($request->query->getString('jour'));

        $qb = $em->getRepository(ActiviteProgramme::class)->createQueryBuilder('a')
            ->leftJoin('a.programme', 'p')->addSelect('p')
            ->leftJoin('p.psychologue', 'psy')->addSelect('psy')
            ->orderBy('a.jour', 'ASC')
            ->addOrderBy('a.heureDebut', 'ASC')
            ->addOrderBy('a.id', 'DESC');

        if ($q !== '') {
            $qb->andWhere('LOWER(a.titre) LIKE :q OR LOWER(a.description) LIKE :q OR LOWER(p.nom) LIKE :q')
                ->setParameter('q', '%'.mb_strtolower($q).'%');
        }
        if ($type !== '') {
            $qb->andWhere('a.typeActivite = :type')->setParameter('type', $type);
        }
        if ($programmeId > 0) {
            $qb->andWhere('p.id = :pid')->setParameter('pid', $programmeId);
        }
        if ($jour !== '' && ctype_digit($jour)) {
            $qb->andWhere('a.jour = :jour')->setParameter('jour', (int) $jour);
        }

        $activites = $paginator->paginate($qb, max(1, $request->query->getInt('page', 1)), 6);

        $stats = [
            'activites_total' => (int) $em->createQuery('SELECT COUNT(a.id) FROM App\Entity\ActiviteProgramme a')->getSingleScalarResult(),
            'types_total'     => (int) $em->createQuery('SELECT COUNT(DISTINCT a.typeActivite) FROM App\Entity\ActiviteProgramme a WHERE a.typeActivite IS NOT NULL')->getSingleScalarResult(),
            'duree_moyenne'   => (int) round((float) $em->createQuery('SELECT COALESCE(AVG(a.dureeMinutes), 0) FROM App\Entity\ActiviteProgramme a')->getSingleScalarResult()),
            'jours_max'       => (int) $em->createQuery('SELECT COALESCE(MAX(a.jour), 0) FROM App\Entity\ActiviteProgramme a')->getSingleScalarResult(),
        ];

        $distinctTypes = $em->createQuery('SELECT DISTINCT a.typeActivite FROM App\Entity\ActiviteProgramme a WHERE a.typeActivite IS NOT NULL ORDER BY a.typeActivite ASC')->getScalarResult();
        $programmes    = $em->getRepository(ProgrammeBienEtre::class)->createQueryBuilder('p')->orderBy('p.nom', 'ASC')->getQuery()->getResult();

        if ($request->isXmlHttpRequest()) {
            return $this->render('admin/wellbeing/_activites_table.html.twig', ['activites' => $activites]);
        }

        return $this->render('admin/wellbeing/activites.html.twig', [
            'activites'    => $activites,
            'stats'        => $stats,
            'q'            => $q,
            'type'         => $type,
            'programme_id' => $programmeId,
            'jour'         => $jour,
            'types'        => array_map(static fn (array $row): string => (string) $row['typeActivite'], $distinctTypes),
            'programmes'   => $programmes,
        ]);
    }

    #[Route('/avis', name: 'app_admin_wellbeing_avis', methods: ['GET'])]
    public function avis(Request $request, EntityManagerInterface $em, PaginatorInterface $paginator): Response
    {
        $q           = trim($request->query->getString('q'));
        $note        = trim($request->query->getString('note'));
        $programmeId = $request->query->getInt('programme_id', 0);

        $qb = $em->getRepository(Avis::class)->createQueryBuilder('a')
            ->leftJoin('a.programme', 'p')->addSelect('p')
            ->leftJoin('a.patient', 'patient')->addSelect('patient')
            ->orderBy('a.id', 'DESC');

        if ($q !== '') {
            $qb->andWhere('LOWER(a.commentaire) LIKE :q OR LOWER(p.nom) LIKE :q OR LOWER(patient.nom) LIKE :q OR LOWER(patient.prenom) LIKE :q')
                ->setParameter('q', '%'.mb_strtolower($q).'%');
        }
        if ($note !== '' && ctype_digit($note)) {
            $qb->andWhere('a.note = :note')->setParameter('note', (int) $note);
        }
        if ($programmeId > 0) {
            $qb->andWhere('p.id = :pid')->setParameter('pid', $programmeId);
        }

        $avis = $paginator->paginate($qb, max(1, $request->query->getInt('page', 1)), 6);

        $stats = [
            'avis_total'    => (int) $em->createQuery('SELECT COUNT(a.id) FROM App\Entity\Avis a')->getSingleScalarResult(),
            'note_moyenne'  => (float) $em->createQuery('SELECT COALESCE(AVG(a.note), 0) FROM App\Entity\Avis a')->getSingleScalarResult(),
            'avis_positifs' => (int) $em->createQuery('SELECT COUNT(a.id) FROM App\Entity\Avis a WHERE a.note >= 4')->getSingleScalarResult(),
            'avis_negatifs' => (int) $em->createQuery('SELECT COUNT(a.id) FROM App\Entity\Avis a WHERE a.note <= 2')->getSingleScalarResult(),
        ];

        $programmes = $em->getRepository(ProgrammeBienEtre::class)->createQueryBuilder('p')->orderBy('p.nom', 'ASC')->getQuery()->getResult();

        if ($request->isXmlHttpRequest()) {
            return $this->render('admin/wellbeing/_avis_table.html.twig', ['avis' => $avis]);
        }

        return $this->render('admin/wellbeing/avis.html.twig', [
            'avis'         => $avis,
            'stats'        => $stats,
            'q'            => $q,
            'note'         => $note,
            'programme_id' => $programmeId,
            'programmes'   => $programmes,
        ]);
    }

    #[Route('/programmes/{id}/delete', name: 'app_admin_wellbeing_programme_delete', methods: ['POST'])]
    public function deleteProgramme(ProgrammeBienEtre $programme, Request $request, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('admin_delete_programme_'.$programme->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('app_admin_wellbeing_programmes');
        }
        $em->remove($programme);
        $em->flush();
        $this->addFlash('success', 'Programme supprimé avec succès.');
        return $this->redirectToRoute('app_admin_wellbeing_programmes');
    }

    #[Route('/activites/{id}/delete', name: 'app_admin_wellbeing_activite_delete', methods: ['POST'])]
    public function deleteActivite(ActiviteProgramme $activite, Request $request, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('admin_delete_activite_'.$activite->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('app_admin_wellbeing_activites');
        }
        $em->remove($activite);
        $em->flush();
        $this->addFlash('success', 'Activité supprimée avec succès.');
        return $this->redirectToRoute('app_admin_wellbeing_activites');
    }

    #[Route('/avis/{id}/delete', name: 'app_admin_wellbeing_avis_delete', methods: ['POST'])]
    public function deleteAvis(Avis $avis, Request $request, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('admin_delete_avis_'.$avis->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('app_admin_wellbeing_avis');
        }
        $em->remove($avis);
        $em->flush();
        $this->addFlash('success', 'Avis supprimé avec succès.');
        return $this->redirectToRoute('app_admin_wellbeing_avis');
    }
}
