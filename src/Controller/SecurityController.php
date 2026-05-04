<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

final class SecurityController extends AbstractController
{
    #[Route('/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils, Request $request): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }

        $error = $authenticationUtils->getLastAuthenticationError();

        // Generate simple CAPTCHA numbers
        $captchaA = random_int(1, 9);
        $captchaB = random_int(1, 9);

        return $this->render('security/login.html.twig', [
            'last_username' => null !== $error ? $authenticationUtils->getLastUsername() : '',
            'error' => $error,
            'captcha_a' => $captchaA,
            'captcha_b' => $captchaB,
        ]);
    }

    #[Route('/register', name: 'app_register')]
    public function register(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setMotDePasse(
                $passwordHasher->hashPassword($user, (string) $form->get('plainPassword')->getData())
            );
            $user->setDateInscription(new \DateTime());

            // Par défaut : les psychologues sont inactifs jusqu'à activation par l'admin
            // Les patients sont actifs immédiatement
            $role = $form->get('role')->getData();
            if ($role === 'Psychologue') {
                $user->setEstActif(false);
                $user->setStatutValidation('en_attente');
            } else {
                $user->setEstActif(true);
                $user->setStatutValidation('approuve');
            }

            $em->persist($user);
            $em->flush();

            if ($role === 'Psychologue') {
                $this->addFlash('info', 'Votre compte a été créé. Il sera activé après validation par un administrateur.');
            } else {
                $this->addFlash('success', 'Compte créé avec succès, connectez-vous.');
            }

            return $this->redirectToRoute('app_login');
        }

        return $this->render('security/register.html.twig', [
            'registrationForm' => $form,
        ]);
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('Handled by Symfony firewall logout.');
    }
}
