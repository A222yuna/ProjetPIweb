<?php

namespace App\Controller\Patient;

use App\Entity\Appointment;
use App\Entity\Creneau;
use App\Entity\User;
use App\Form\CreneauType;
use App\Repository\AppointmentRepository;
use App\Repository\CreneauRepository;
use App\Repository\DisponibiliteRepository;
use App\Repository\PsychologuePlanRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/patient/creneaux')]
#[IsGranted('ROLE_PATIENT')]
final class CreneauController extends AbstractController
{
    #[Route('/', name: 'app_patient_creneaux_index', methods: ['GET'])]
    public function index(Request $request, CreneauRepository $creneaux): Response
    {
        $user = $this->getUser();
        \assert($user instanceof User);

        // --- RECHERCHE & TRI & FILTRE ---
        $search     = $request->query->getString('search');
        $filterStatut = $request->query->getString('statut'); // RESERVE | ANNULE | ''
        $sortBy     = $request->query->getString('sort', 'dateCreneau');
        $sortDir    = strtoupper($request->query->getString('dir', 'DESC')) === 'ASC' ? 'ASC' : 'DESC';
        $page       = max(1, $request->query->getInt('page', 1));
        $perPage    = 8;

        $allowedSorts = ['dateCreneau', 'heure', 'statut'];
        if (!\in_array($sortBy, $allowedSorts, true)) {
            $sortBy = 'dateCreneau';
        }

        $result = $creneaux->findForPatientPaginatedFiltered(
            $user, $search, $filterStatut, $sortBy, $sortDir, $page, $perPage
        );

        return $this->render('patient/creneaux/index.html.twig', [
            'creneaux'      => $result['items'],
            'total'         => $result['total'],
            'page'          => $page,
            'per_page'      => $perPage,
            'total_pages'   => max(1, (int) ceil($result['total'] / $perPage)),
            'search'        => $search,
            'filter_statut' => $filterStatut,
            'sort'          => $sortBy,
            'dir'           => $sortDir,
            'next_dir'      => $sortDir === 'ASC' ? 'DESC' : 'ASC',
        ]);
    }

    #[Route('/book', name: 'app_patient_creneaux_book', methods: ['GET', 'POST'])]
    public function book(
        Request $request,
        DisponibiliteRepository $disponibilites,
        CreneauRepository $creneauxRepo,
        PsychologuePlanRepository $plans,
        AppointmentRepository $appointments,
        EntityManagerInterface $em
    ): Response {
        $user = $this->getUser();
        \assert($user instanceof User);

        $creneau = new Creneau();
        $form = $this->createForm(CreneauType::class, $creneau);
        $form->handleRequest($request);

        $available = $disponibilites->findWithCreneauxByCabinet();
        $availableGrouped = $this->buildAvailableGrouped($available);

        if ($form->isSubmitted() && $form->isValid()) {
            $dispo = $creneau->getDisponibilite();
            $date  = $creneau->getDateCreneau();
            $heure = $creneau->getHeure();

            if (!$dispo || !$date || !$heure) {
                $this->addFlash('error', 'Formulaire incomplet.');
                return $this->redirectToRoute('app_patient_creneaux_book');
            }

            // RULE 5 — No past booking
            $today = new \DateTimeImmutable('today');
            if ($date < $today) {
                $this->addFlash('error', 'Vous ne pouvez pas réserver un créneau dans le passé.');
                return $this->redirectToRoute('app_patient_creneaux_book');
            }

            // RULE — Heure dans la fenêtre de disponibilité
            $heureMin  = $dispo->getHeureDebut();
            $heureMax  = $dispo->getHeureFin();
            if ($heureMin && $heureMax && ($heure < $heureMin || $heure >= $heureMax)) {
                $this->addFlash('error', sprintf(
                    "L'heure doit être entre %s et %s.",
                    $heureMin->format('H:i'),
                    $heureMax->format('H:i')
                ));
                return $this->redirectToRoute('app_patient_creneaux_book');
            }

            // RULE 4 — No double booking
            if ($creneauxRepo->isSlotAlreadyBooked($dispo, $date, $heure)) {
                $this->addFlash('error', 'Ce créneau est déjà réservé, veuillez en choisir un autre.');
                return $this->redirectToRoute('app_patient_creneaux_book');
            }

            // Trouver psychologue via cabinet
            $psy = null;
            $cabinet = $dispo->getCabinet();
            if ($cabinet && $cabinet->getPsyCabinets()->count() > 0) {
                $psy = $cabinet->getPsyCabinets()->first()?->getPsychologue();
            }
            if (!$psy instanceof User) {
                $this->addFlash('error', 'Psychologue introuvable pour ce cabinet.');
                return $this->redirectToRoute('app_patient_creneaux_book');
            }

            $dayMap = [
                'MONDAY'    => 'MONDAY',    'TUESDAY'  => 'TUESDAY',
                'WEDNESDAY' => 'WEDNESDAY', 'THURSDAY' => 'THURSDAY',
                'FRIDAY'    => 'FRIDAY',    'SATURDAY' => 'SATURDAY',
                'SUNDAY'    => 'SUNDAY',
            ];
            $dayOfWeek = $dayMap[strtoupper($date->format('l'))] ?? 'MONDAY';
            $period    = ((int)$heure->format('H') < 18) ? 'DAY' : 'NIGHT';

            $plan = $plans->findOneForPsychologueDayPeriod($psy, $dayOfWeek, $period);
            if (!$plan) {
                $this->addFlash('error', 'Aucun planning trouvé pour ce psychologue sur ce jour/période.');
                return $this->redirectToRoute('app_patient_creneaux_book');
            }

            // RULE 6 — Respect max_appointments
            if ($appointments->countScheduledForPlan($plan) >= $plan->getMaxAppointments()) {
                $this->addFlash('error', 'Ce psychologue a atteint son nombre maximum de rendez-vous.');
                return $this->redirectToRoute('app_patient_creneaux_book');
            }

            $creneau->setPatient($user);
            $creneau->setStatut(Creneau::STATUT_RESERVE);

            $appointment = new Appointment();
            $appointment->setPatient($user);
            $appointment->setPlan($plan);
            $appointment->setStatus(Appointment::STATUS_SCHEDULED);

            $em->persist($creneau);
            $em->persist($appointment);
            $em->flush();

            $this->addFlash('success', 'Votre créneau a été réservé avec succès ✓');
            return $this->redirectToRoute('app_patient_creneaux_index');
        }

        return $this->render('patient/creneaux/book.html.twig', [
            'form'             => $form,
            'disponibilites'   => $available,
            'available_grouped' => $availableGrouped,
        ]);
    }

    /**
     * @param array<int, \App\Entity\Disponibilite> $disponibilites
     * @return array<string, array<int, string>>
     */
    private function buildAvailableGrouped(array $disponibilites): array
    {
        $groups = [];
        $labels = [1 => 'Lundi', 2 => 'Mardi', 3 => 'Mercredi', 4 => 'Jeudi', 5 => 'Vendredi', 6 => 'Samedi', 7 => 'Dimanche'];
        $today = new \DateTimeImmutable('today');
        $cutoff = $today->modify('+7 days');

        foreach ($disponibilites as $disponibilite) {
            $cabinet = $disponibilite->getCabinet();
            $label = sprintf(
                '%s %s - %s (%d min)',
                $cabinet ? ($cabinet->getVille() . ' / ' . $cabinet->getAdresse()) : 'Cabinet',
                $labels[$disponibilite->getJour()] ?? 'Jour',
                $disponibilite->getHeureDebut()?->format('H:i') . '–' . $disponibilite->getHeureFin()?->format('H:i'),
                $disponibilite->getDureeConsultation()
            );

            $dates = [];
            for ($date = $today; $date <= $cutoff; $date = $date->modify('+1 day')) {
                if ((int) $date->format('N') === $disponibilite->getJour()) {
                    $dates[] = $date->format('d/m');
                }
            }

            if ($dates === []) {
                $dates[] = 'Aucune date dans les 7 prochains jours';
            }

            $groups[$label] = $dates;
        }

        return $groups;
    }

    #[Route('/{id}/cancel', name: 'app_patient_creneaux_cancel', methods: ['POST'])]
    public function cancel(
        int $id,
        Request $request,
        CreneauRepository $creneaux,
        AppointmentRepository $appointments,
        PsychologuePlanRepository $plans,
        EntityManagerInterface $em
    ): Response {
        $user = $this->getUser();
        \assert($user instanceof User);

        $creneau = $creneaux->find($id);
        if (!$creneau) {
            throw $this->createNotFoundException();
        }
        // RULE 7 — Only owner can cancel
        if ($creneau->getPatient()?->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException();
        }
        if (!$this->isCsrfTokenValid('cancel_creneau_'.$creneau->getId(), (string)$request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('app_patient_creneaux_index');
        }

        // Trouver l'appointment lié
        $dispo   = $creneau->getDisponibilite();
        $date    = $creneau->getDateCreneau();
        $heure   = $creneau->getHeure();
        $cabinet = $dispo?->getCabinet();
        $psy     = $cabinet && $cabinet->getPsyCabinets()->count() > 0
                   ? $cabinet->getPsyCabinets()->first()?->getPsychologue()
                   : null;

        $plan        = null;
        $appointment = null;

        if ($psy instanceof User && $date && $heure) {
            $dayMap    = ['MONDAY'=>'MONDAY','TUESDAY'=>'TUESDAY','WEDNESDAY'=>'WEDNESDAY',
                          'THURSDAY'=>'THURSDAY','FRIDAY'=>'FRIDAY','SATURDAY'=>'SATURDAY','SUNDAY'=>'SUNDAY'];
            $dayOfWeek = $dayMap[strtoupper($date->format('l'))] ?? 'MONDAY';
            $period    = ((int)$heure->format('H') < 18) ? 'DAY' : 'NIGHT';
            $plan      = $plans->findOneForPsychologueDayPeriod($psy, $dayOfWeek, $period);
            $appointment = $plan ? $appointments->findLatestNonCancelledForPatientAndPlan($user, $plan) : null;
        }

        // RULE 8 — Cannot cancel completed appointment
        if ($appointment && $appointment->getStatus() === Appointment::STATUS_COMPLETED) {
            $this->addFlash('error', "Impossible d'annuler un rendez-vous déjà terminé.");
            return $this->redirectToRoute('app_patient_creneaux_index');
        }

        $creneau->setStatut(Creneau::STATUT_ANNULE);

        // RULE 9 — Auto-cancel appointment
        if ($appointment) {
            $appointment->setStatus(Appointment::STATUS_CANCELLED);
        }

        $em->flush();
        $this->addFlash('success', 'Votre rendez-vous a été annulé.');
        return $this->redirectToRoute('app_patient_creneaux_index');
    }
}