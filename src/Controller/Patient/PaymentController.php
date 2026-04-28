<?php

namespace App\Controller\Patient;

use App\Entity\Appointment;
use App\Entity\User;
use App\Repository\AppointmentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\SvgWriter;
use Stripe\Checkout\Session;
use Stripe\Stripe;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

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

        if ($appointment->getStatus() !== Appointment::STATUS_CONFIRMED) {
            $this->addFlash('warning', 'Ce rendez-vous ne peut pas être payé (Statut: ' . $appointment->getStatus() . ').');
            return $this->redirectToRoute('app_patient_rendezvous_index');
        }

        return $this->render('patient/payment/checkout.html.twig', [
            'appointment' => $appointment,
            'stripe_public_key' => $this->getParameter('stripe_public_key'),
            'price' => $this->getParameter('stripe_price') / 100
        ]);
    }

    #[Route('/{id}/pay/stripe', name: 'app_patient_appointment_stripe_session', methods: ['POST'])]
    public function createStripeSession(int $id, AppointmentRepository $appointmentRepository): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->redirectToRoute('app_login');
        }

        $appointment = $appointmentRepository->find($id);

        if (!$appointment || $appointment->getPatient() !== $user || $appointment->getStatus() !== Appointment::STATUS_CONFIRMED) {
            throw $this->createAccessDeniedException();
        }

        Stripe::setApiKey($this->getParameter('stripe_secret_key'));

        $session = Session::create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => 'eur',
                    'product_data' => [
                        'name' => 'Consultation Psychologique',
                        'description' => 'Rendez-vous du ' . $appointment->getPlan()->getDayOfWeek(),
                    ],
                    'unit_amount' => $this->getParameter('stripe_price'),
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => $this->generateUrl('app_patient_appointment_pay_success', ['id' => $id], UrlGeneratorInterface::ABSOLUTE_URL),
            'cancel_url' => $this->generateUrl('app_patient_appointment_pay_cancel', ['id' => $id], UrlGeneratorInterface::ABSOLUTE_URL),
        ]);

        return $this->redirect($session->url, 303);
    }

    #[Route('/{id}/pay/success', name: 'app_patient_appointment_pay_success', methods: ['GET'])]
    public function success(int $id, AppointmentRepository $appointmentRepository, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        $appointment = $appointmentRepository->find($id);

        if (!$appointment || $appointment->getPatient() !== $user) {
            throw $this->createAccessDeniedException();
        }

        if ($appointment->getStatus() === Appointment::STATUS_CONFIRMED) {
            $appointment->setStatus(Appointment::STATUS_PAID);
            $em->flush();
            $this->addFlash('success', 'Paiement effectué ✓');
        }

        // Generate QR Code
        $qrContent = sprintf(
            "RDV Payé\nPatient: %s %s\nDate: %s\nID: %d",
            $user->getPrenom(),
            $user->getNom(),
            $appointment->getPlan()->getDayOfWeek(),
            $appointment->getId()
        );

        $qrCode = new QrCode($qrContent);
        $writer = new SvgWriter();
        $result = $writer->write($qrCode);
        $qrCodeDataUri = $result->getDataUri();

        return $this->render('patient/payment/success.html.twig', [
            'appointment' => $appointment,
            'qrCode' => $qrCodeDataUri
        ]);
    }

    #[Route('/{id}/pay/cancel', name: 'app_patient_appointment_pay_cancel', methods: ['GET'])]
    public function cancel(int $id, AppointmentRepository $appointmentRepository): Response
    {
        $this->addFlash('warning', 'Paiement annulé');
        return $this->redirectToRoute('app_patient_rendezvous_index');
    }
}
