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
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final class SecurityController extends AbstractController
{
    #[Route('/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }

        return $this->render('security/login.html.twig', [
            'last_username' => $authenticationUtils->getLastUsername(),
            'error' => $authenticationUtils->getLastAuthenticationError(),
        ]);
    }

    #[Route('/register', name: 'app_register')]
    public function register(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher,
        MailerInterface $mailer,
        #[Autowire('%env(MAILER_FROM)%')] string $mailerFrom
    ): Response {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setMotDePasse(
                $passwordHasher->hashPassword($user, (string) $form->get('plainPassword')->getData())
            );
            $user->setDateInscription(new \DateTime());
            $user->setStatutValidation('approuve');
            $em->persist($user);
            $em->flush();

            try {
                $email = (new TemplatedEmail())
                    ->from($mailerFrom)
                    ->to($user->getEmail())
                    ->subject('Bienvenue chez MindCare !')
                    ->htmlTemplate('emails/welcome.html.twig')
                    ->context([
                        'user' => $user,
                        'subject' => 'Bienvenue chez MindCare',
                        'message' => 'Nous sommes ravis de vous compter parmi nous ! Votre compte a été créé avec succès.',
                    ]);
                $mailer->send($email);
            } catch (\Exception $e) {
                // Fail silently or log error
            }

            $this->addFlash('success', 'Compte cree avec succes, connectez-vous.');

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
