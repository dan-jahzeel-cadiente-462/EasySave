<?php

namespace App\Security;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Security\Authenticator\OAuth2Authenticator;
use League\OAuth2\Client\Provider\GoogleUser;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class GoogleAuthenticator extends OAuth2Authenticator
{
    public function __construct(
        private ClientRegistry $clientRegistry,
        private EntityManagerInterface $entityManager,
        private RouterInterface $router
    ) {
    }

    public function supports(Request $request): ?bool
    {
        // Matches the route defined in your routing configuration
        return $request->attributes->get('_route') === 'connect_google_check';
    }

    public function authenticate(Request $request): Passport
    {
        $client = $this->clientRegistry->getClient('google');
        try {
            $accessToken = $this->fetchAccessToken($client);
        } catch (\Exception $e) {
            error_log('Failed to fetch Google access token: ' . $e->getMessage());
            throw new CustomUserMessageAuthenticationException('Google authentication failed. Please try again.');
        }

        /** @var GoogleUser $googleUser */
        $googleUser = $client->fetchUserFromToken($accessToken);
        $email = $googleUser->getEmail();

        // 1) Find user by Google ID or Email
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['googleId' => $googleUser->getId()]);

        if (!$user) {
            $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);

            if (!$user) {
                $user = new User();
                $username = explode('@', $email, 2)[0] ?: $email;
                $username = preg_replace('/[^a-zA-Z0-9_\-]/', '', (string) $username);
                $user->setUsername($username);
                $user->setEmail($email);
                $user->setFirstName($googleUser->getFirstName() ?? '');
                $user->setLastName($googleUser->getLastName() ?? '');
                $user->setPassword(bin2hex(random_bytes(16)));
                $user->setIsVerified(true);
            }

            $user->setProvider('google');
            $user->setGoogleId($googleUser->getId());

            $this->entityManager->persist($user);
            $this->entityManager->flush();
        }

        if (in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            throw new CustomUserMessageAuthenticationException('Administrator accounts are not permitted to use Google authentication.');
        }

        if (method_exists($user, 'isActive') && !$user->isActive()) {
            throw new CustomUserMessageAuthenticationException('Your account has been deactivated.');
        }

        return new SelfValidatingPassport(
            new UserBadge($user->getUsername(), fn() => $user)
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        /** @var User $user */
        $user = $token->getUser();

        error_log('Google login successful for user: ' . $user->getEmail());

        // 4) Role-based redirect logic (Consistent with LoginFormAuthenticator)
        if ($this->hasRole($user, 'ROLE_ADMIN') || $this->hasRole($user, 'ROLE_STAFF')) {
            return new RedirectResponse($this->router->generate('app_admin_dashboard'));
        }

        // Redirect moderators and regular users to the user dashboard
        return new RedirectResponse($this->router->generate('app_user_dashboard'));
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        // Redirect deactivated users to the specific info page
        if ($exception instanceof CustomUserMessageAuthenticationException) {
            return new RedirectResponse($this->router->generate('app_deactivated'));
        }

        $message = strtr($exception->getMessageKey(), $exception->getMessageData());
        return new Response($message, Response::HTTP_FORBIDDEN);
    }

    private function hasRole(User $user, string $role): bool
    {
        $userRoles = array_map('strtoupper', $user->getRoles());
        return in_array(strtoupper($role), $userRoles, true);
    }
}