<?php

namespace App\Controller\Psychologue;

use App\Entity\User;
use App\Repository\AppointmentRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_PSYCHOLOGUE')]
final class FaceVerificationController extends AbstractController
{
    #[Route('/psychologue/face-verification/{appointmentId}', name: 'app_psychologue_face_verify', methods: ['GET'])]
    public function verify(
        int $appointmentId,
        AppointmentRepository $appointments,
        CsrfTokenManagerInterface $csrfTokenManager
    ): Response {
        $user = $this->getUser();
        \assert($user instanceof User);

        $appointment = $appointments->find($appointmentId);
        if (!$appointment) {
            throw $this->createNotFoundException();
        }

        if ($appointment->getPlan()?->getPsychologue()?->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException();
        }

        $photoUrl = $user->getPhotoProfil();

        return $this->render('psychologue/face/verify.html.twig', [
            'appointment' => $appointment,
            'has_photo'   => (bool) $photoUrl,
            'photo_url'   => $photoUrl,
            'csrf_token'  => $csrfTokenManager->getToken('confirm_appointment_' . $appointment->getId())->getValue(),
        ]);
    }
}
