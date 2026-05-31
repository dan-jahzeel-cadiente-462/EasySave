<?php

namespace App\Security;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\SecurityRequestAttributes;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class LoginAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    public const LOGIN_ROUTE = 'app_user_login';

    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
        private EntityManagerInterface $entityManager
    ) {
    }

    public function supports(Request $request): bool
    {
        return $request->attributes->get('_route') === self::LOGIN_ROUTE
            && $request->isMethod('POST');
    }

    public function authenticate(Request $request): Passport
    {
        $identifier = trim((string) $request->request->get('_username', ''));
        $password = (string) $request->request->get('_password', '');
        $csrfToken = $request->request->get('_csrf_token');

        $request->getSession()->set(SecurityRequestAttributes::LAST_USERNAME, $identifier);

        // Find user by email OR username to ensure we can get the canonical email for the badge
        // This is necessary because the user provider is configured to use the email property.
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $identifier]);
        if (!$user) {
            $user = $this->entityManager->getRepository(User::class)->findOneBy(['username' => $identifier]);
        }

        if (!$user instanceof User) {
            throw new UserNotFoundException();
        }

        return new Passport(
            new UserBadge($user->getUsername()),
            new PasswordCredentials($password),
            [
                new CsrfTokenBadge('authenticate', $csrfToken),
            ]
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        try {
            $session = $request->getSession();
            
            // Check for stored target path (from protected page redirect)
            if ($targetPath = $this->getTargetPath($session, $firewallName)) {
                $this->removeTargetPath($session, $firewallName);
                return new RedirectResponse($targetPath);
            }

            // Get authenticated user
            $user = $token->getUser();
            
            if (!$user instanceof User) {
                return new RedirectResponse($this->urlGenerator->generate(self::LOGIN_ROUTE));
            }
            
            // Check roles and redirect to appropriate dashboard
            // Admins and Staff go to Admin Dashboard
            if ($this->isAdministrativeUser($user)) {
                return new RedirectResponse($this->urlGenerator->generate('app_admin_dashboard'));
            }

            // Default redirect for regular users
            return new RedirectResponse($this->urlGenerator->generate('app_user_dashboard'));
            
        } catch (\Exception $e) {
            error_log('Authentication success redirect error: ' . $e->getMessage());
            return new RedirectResponse($this->urlGenerator->generate('app_user_dashboard'));
        }
    }

    private function isAdministrativeUser(User $user): bool
    {
        $roles = $user->getRoles();
        return in_array('ROLE_ADMIN', $roles, true) || in_array('ROLE_STAFF', $roles, true);
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }
}
