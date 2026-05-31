<?php

namespace App\Security;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\SecurityRequestAttributes;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class AdminLoginAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    public const ADMIN_LOGIN_ROUTE = 'app_admin_login';
    public const SESSION_LAST_LOGIN_TYPE = 'admin_last_login_type';

    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
        private UserRepository $userRepository,
    ) {
    }

    public function supports(Request $request): bool
    {
        return $request->attributes->get('_route') === self::ADMIN_LOGIN_ROUTE
            && $request->isMethod('POST');
    }

    public function authenticate(Request $request): Passport
    {
        $loginType = (string) $request->request->get('login_type', 'email');
        $identifier = trim((string) $request->request->get('_username', ''));
        $password = (string) $request->request->get('_password', '');
        $csrfToken = $request->request->get('_csrf_token');

        error_log("Admin Auth Attempt: type=$loginType, identifier=$identifier");

        $request->getSession()->set(SecurityRequestAttributes::LAST_USERNAME, $identifier);
        $request->getSession()->set(self::SESSION_LAST_LOGIN_TYPE, $loginType);

        return new Passport(
            new UserBadge($identifier, function (string $userIdentifier) use ($loginType): UserInterface {
                // Choose search field based on UI selection
                $searchField = ($loginType === 'username') ? 'username' : 'email';
                
                $user = $this->userRepository->findOneBy([$searchField => $userIdentifier]);

                if (!$user instanceof User) {
                    error_log("Admin Auth Failed: User not found for $searchField=$userIdentifier");
                    throw new UserNotFoundException();
                }

                return $user;
            }),
            new PasswordCredentials($password),
            [
                new CsrfTokenBadge('authenticate_admin', $csrfToken),
            ]
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $user = $token->getUser();
        if ($user instanceof User) {
            $roles = $user->getRoles();
            if (!in_array('ROLE_ADMIN', $roles, true) && !in_array('ROLE_STAFF', $roles, true)) {
                error_log('Admin login success but roles invalid: ' . implode(',', $roles));
                $request->getSession()->getFlashBag()->add('error', 'Access denied.');
                return new RedirectResponse($this->urlGenerator->generate(self::ADMIN_LOGIN_ROUTE));
            }
        }

        return new RedirectResponse($this->urlGenerator->generate('app_admin_dashboard'));
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate(self::ADMIN_LOGIN_ROUTE);
    }
}
