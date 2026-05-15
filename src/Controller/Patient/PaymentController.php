<?php

namespace App\Controller\Patient;

use App\Entity\Appointment;
use App\Entity\User;
use App\Repository\AppointmentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/patient/appointments')]
class PaymentController extends AbstractController
{
    #[Route('/{id}/pay', name: 'app_patient_appointment_pay', methods: ['GET'])]
    public function checkout(int $id, AppointmentRepository $appointmentRepository): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->redirectToRoute('app_login');
        }

        $appointment = $appointmentRepository->find($id);

        if (!$appointment || $appointment->getPatient() !== $user) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas payer ce rendez-vous.');
        }

        if ($appointment->getStatus() !== Appointment::STATUS_CONFIRMED && $appointment->getStatus() !== '') {
            $this->addFlash('warning', 'Ce rendez-vous ne peut pas être payé.');
            return $this->redirectToRoute('app_patient_rendezvous_index');
        }

        return $this->render('patient/payment/checkout.html.twig', [
            'appointment' => $appointment,
            'stripe_public_key' => '',
            'price' => 50,
        ]);
    }

    #[Route('/{id}/pay/stripe', name: 'app_patient_appointment_stripe_session', methods: ['POST'])]
    public function createStripeSession(int $id, AppointmentRepository $appointmentRepository, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->redirectToRoute('app_login');
        }

        $appointment = $appointmentRepository->find($id);
        if (!$appointment || $appointment->getPatient() !== $user) {
            throw $this->createAccessDeniedException();
        }

        // Stripe disabled — simulate payment success directly
        $appointment->setStatus(Appointment::STATUS_PAID);
        $em->flush();
        $this->addFlash('success', 'Paiement simulé ✓ (Stripe non configuré)');

        return $this->redirectToRoute('app_patient_appointment_pay_success', ['id' => $id]);
    }

    #[Route('/{id}/pay/success', name: 'app_patient_appointment_pay_success', methods: ['GET'])]
    public function success(int $id, AppointmentRepository $appointmentRepository, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        $appointment = $appointmentRepository->find($id);

        if (!$appointment || $appointment->getPatient() !== $user) {
            throw $this->createAccessDeniedException();
        }

        if ($appointment->getStatus() === Appointment::STATUS_CONFIRMED || $appointment->getStatus() === '') {
            $appointment->setStatus(Appointment::STATUS_PAID);
            $em->flush();
            $this->addFlash('success', 'Paiement effectué ✓');
        }

        return $this->render('patient/payment/success.html.twig', [
            'appointment' => $appointment,
            'qrCode' => null,
        ]);
    }

    #[Route('/{id}/pay/cancel', name: 'app_patient_appointment_pay_cancel', methods: ['GET'])]
    public function cancel(): Response
    {
        $this->addFlash('warning', 'Paiement annulé');
        return $this->redirectToRoute('app_patient_rendezvous_index');
    }
}
