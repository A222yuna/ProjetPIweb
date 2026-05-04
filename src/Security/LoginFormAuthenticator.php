<?php

namespace App\Security;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\SecurityRequestAttributes;

final class LoginFormAuthenticator extends AbstractLoginFormAuthenticator
{
    public const LOGIN_ROUTE = 'app_login';
    public const MAX_FAILED_ATTEMPTS = 3;
    public const CAPTCHA_ATTEMPTS = 1;

    /** @var UrlGeneratorInterface */
    private $urlGenerator;
    /** @var UserRepository */
    private $users;
    /** @var EntityManagerInterface */
    private $em;

    public function __construct(
        UrlGeneratorInterface $urlGenerator,
        UserRepository $users,
        EntityManagerInterface $em
    )
    {
        $this->urlGenerator = $urlGenerator;
        $this->users = $users;
        $this->em = $em;
    }

    public function authenticate(Request $request): Passport
    {
        $email = (string) $request->request->get('email', '');
        $request->getSession()->set(SecurityRequestAttributes::LAST_USERNAME, $email);
        $user = $this->users->findOneBy(['email' => $email]);

        // Pending psychologue accounts are blocked before CAPTCHA checks.
        if ($user instanceof User && !$user->isEstActif() && $user->getRole() === 'Psychologue' && $user->getStatutValidation() === 'en_attente') {
            throw new CustomUserMessageAuthenticationException('Votre compte psychologue est en attente de validation par un administrateur.');
        }

        // Store failed attempts in session for CAPTCHA display
        $session = $request->getSession();
        $sessionAttempts = (int) $session->get('login_failed_attempts', 0);

        // Validate CAPTCHA if displayed (after 1+ failed attempts)
        if ($sessionAttempts >= self::CAPTCHA_ATTEMPTS) {
            $captchaA = (int) $request->request->get('captcha_a', 0);
            $captchaB = (int) $request->request->get('captcha_b', 0);
            $userCaptcha = (int) $request->request->get('captcha', -1);

            if ($captchaA + $captchaB !== $userCaptcha) {
                throw new CustomUserMessageAuthenticationException('Vérification CAPTCHA incorrecte.');
            }
        }

        return new Passport(
            new UserBadge($email, function (string $userIdentifier): User {
                $user = $this->users->findOneBy(['email' => $userIdentifier]);
                if (!$user instanceof User) {
                    throw new CustomUserMessageAuthenticationException('Identifiants invalides.');
                }

                // Check if account is locked by admin or after failed attempts
                if (!$user->isEstActif()) {
                    if ($user->getRole() === 'Psychologue' && $user->getStatutValidation() === 'en_attente') {
                        throw new CustomUserMessageAuthenticationException('Votre compte psychologue est en attente de validation par un administrateur.');
                    }
                    throw new CustomUserMessageAuthenticationException('Votre compte est désactivé. Contactez un administrateur.');
                }

                // Check if locked due to failed attempts
                if ($user->isLocked()) {
                    throw new CustomUserMessageAuthenticationException('Votre compte est temporairement verrouillé après plusieurs tentatives échouées. Veuillez contacter un administrateur.');
                }

                if ($user->getStatutValidation() !== 'approuve') {
                    throw new CustomUserMessageAuthenticationException('Votre compte n\'est pas encore validé.');
                }

                return $user;
            }),
            new PasswordCredentials((string) $request->request->get('password', '')),
            [
                new CsrfTokenBadge('authenticate', (string) $request->request->get('_csrf_token', '')),
                new RememberMeBadge(),
            ]
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?RedirectResponse
    {
        $user = $token->getUser();
        if ($user instanceof User) {
            // Reset failed attempts on successful login
            $user->resetFailedAttempts();
            $user->setDerniereConnexion(new \DateTime());
            $this->em->flush();

            // Clear session failed attempts
            $request->getSession()->remove('login_failed_attempts');
        }

        $roles = $token->getRoleNames();
        if (\in_array('ROLE_ADMIN', $roles, true)) {
            return new RedirectResponse($this->urlGenerator->generate('app_admin_dashboard'));
        }
        if (\in_array('ROLE_PSYCHOLOGUE', $roles, true)) {
            return new RedirectResponse($this->urlGenerator->generate('app_psychologue_dashboard'));
        }

        return new RedirectResponse($this->urlGenerator->generate('app_patient_dashboard'));
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        $email = (string) $request->request->get('email', '');
        $user = $this->users->findOneBy(['email' => $email]);
        $sessionAttempts = (int) $request->getSession()->get('login_failed_attempts', 0);

        if ($user instanceof User) {
            // No CAPTCHA/tentative logic for pending psychologue accounts.
            if (!$user->isEstActif() && $user->getRole() === 'Psychologue' && $user->getStatutValidation() === 'en_attente') {
                $request->getSession()->remove('login_failed_attempts');
                $this->addFlash($request, 'warning', 'Votre compte psychologue est en attente de validation par un administrateur.');

                return $this->redirectToRoute(self::LOGIN_ROUTE);
            }

            // For any inactive account, do not increase attempt counters.
            if (!$user->isEstActif()) {
                $request->getSession()->remove('login_failed_attempts');
                $this->addFlash($request, 'error', 'Votre compte est désactivé. Contactez un administrateur.');

                return $this->redirectToRoute(self::LOGIN_ROUTE);
            }

            // Increment failed attempts
            $user->incrementFailedAttempts();
            $failedCount = $user->getFailedAttempts();

            // Lock account after MAX_FAILED_ATTEMPTS
            if ($failedCount >= self::MAX_FAILED_ATTEMPTS) {
                $user->setLockedAt(new \DateTime());
                $user->setEstActif(false);
                $this->em->flush();

                $request->getSession()->remove('login_failed_attempts');
                $this->addFlash($request, 'error', 'Votre compte a été verrouillé après 3 tentatives échouées. Veuillez contacter un administrateur.');

                return $this->redirectToRoute(self::LOGIN_ROUTE);
            }

            $this->em->flush();

            // Store attempts in session for CAPTCHA
            $request->getSession()->set('login_failed_attempts', $failedCount);

            $remaining = self::MAX_FAILED_ATTEMPTS - $failedCount;
            $this->addFlash($request, 'error', sprintf('Identifiants invalides. Il vous reste %d tentative(s) avant verrouillage.', $remaining));
        } else {
            // Unknown account: still enable CAPTCHA after first failed try.
            $sessionAttempts++;
            $request->getSession()->set('login_failed_attempts', $sessionAttempts);
        }

        return $this->redirectToRoute(self::LOGIN_ROUTE);
    }

    private function redirectToRoute(string $route, array $parameters = []): Response
    {
        return new RedirectResponse($this->urlGenerator->generate($route, $parameters));
    }

    private function addFlash(Request $request, string $type, string $message): void
    {
        $session = $request->getSession();
        if ($session instanceof FlashBagAwareSessionInterface) {
            $session->getFlashBag()->add($type, $message);
        }
    }

    public function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }
}
