<?php

namespace App\Controller\Psychologue;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_PSYCHOLOGUE')]
final class PsychologueProfileController extends AbstractController
{
    #[Route('/psychologue/profile/photo', name: 'app_psychologue_profile_photo', methods: ['GET', 'POST'])]
    public function photo(Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        \assert($user instanceof User);

        if ($request->isMethod('POST')) {
            $file = $request->files->get('photo');
            if (!$file instanceof UploadedFile) {
                $this->addFlash('error', 'Veuillez selectionner une image.');

                return $this->redirectToRoute('app_psychologue_profile_photo');
            }

            $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/webp'];
            if (!\in_array((string) $file->getMimeType(), $allowedMimeTypes, true)) {
                $this->addFlash('error', 'Format invalide. Utilisez JPG, PNG ou WEBP.');

                return $this->redirectToRoute('app_psychologue_profile_photo');
            }

            if ($file->getSize() > 5 * 1024 * 1024) {
                $this->addFlash('error', 'Taille maximale depassee (5MB).');

                return $this->redirectToRoute('app_psychologue_profile_photo');
            }

            $projectDir = (string) $this->getParameter('kernel.project_dir');
            $uploadDir = $projectDir . '/public/uploads/photos';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $oldPhoto = $user->getPhotoProfil();
            if ($oldPhoto) {
                $oldPath = $projectDir . '/public/' . ltrim($oldPhoto, '/');
                if (is_file($oldPath)) {
                    @unlink($oldPath);
                }
            }

            $extension = $file->guessExtension() ?: 'jpg';
            $filename = sprintf('psy_%d_%s.%s', $user->getId(), uniqid('', true), $extension);
            $file->move($uploadDir, $filename);

            $user->setPhotoProfil('/uploads/photos/' . $filename);
            $em->flush();

            $this->addFlash('success', 'Photo de profil mise a jour.');

            return $this->redirectToRoute('app_psychologue_profile_photo');
        }

        return $this->render('psychologue/profile/photo.html.twig', [
            'photo_url' => $user->getPhotoProfil(),
        ]);
    }

    #[Route('/psychologue/profile/photo/delete', name: 'app_psychologue_profile_photo_delete', methods: ['POST'])]
    public function deletePhoto(Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        \assert($user instanceof User);

        if (!$this->isCsrfTokenValid('delete_profile_photo', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton CSRF invalide.');

            return $this->redirectToRoute('app_psychologue_profile_photo');
        }

        $photo = $user->getPhotoProfil();
        if ($photo) {
            $path = (string) $this->getParameter('kernel.project_dir') . '/public/' . ltrim($photo, '/');
            if (is_file($path)) {
                @unlink($path);
            }
            $user->setPhotoProfil(null);
            $em->flush();
            $this->addFlash('success', 'Photo supprimee.');
        }

        return $this->redirectToRoute('app_psychologue_profile_photo');
    }
}
