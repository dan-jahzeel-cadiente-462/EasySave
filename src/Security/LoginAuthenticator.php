<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Entity\User;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
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

    public const LOGIN_ROUTE = 'app_login';

    public function __construct(private UrlGeneratorInterface $urlGenerator)
    {
    }

    public function authenticate(Request $request): Passport
    {
        // read form fields from the request (login form uses `_username` and `_password`)
        $username = (string) $request->request->get('_username', '');
        $password = (string) $request->request->get('_password', '');
        $csrfToken = $request->request->get('_csrf_token');

        $request->getSession()->set(SecurityRequestAttributes::LAST_USERNAME, $username);

        return new Passport(
            new UserBadge($username),
            new PasswordCredentials($password),
            [
                new CsrfTokenBadge('authenticate', $csrfToken),
            ]
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        if ($targetPath = $this->getTargetPath($request->getSession(), $firewallName)) {
            return new RedirectResponse($targetPath);
        }

        // For example:
        // return new RedirectResponse($this->urlGenerator->generate('admin_dashboard'));
        // throw new \Exception('TODO: provide a valid redirect inside '.__FILE__);
        try {
            $session = $request->getSession();
            
            // Check for stored target path
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
            $roles = $user->getRoles();

            if (in_array('ROLE_ADMIN', $roles, true) || in_array('ROLE_STAFF', $roles, true)) {
                return new RedirectResponse($this->urlGenerator->generate('app_admin_dashboard'));
            }

            // Default redirect for regular users
            return new RedirectResponse($this->urlGenerator->generate('app_user_dashboard'));
            
        } catch (\Exception $e) {
            error_log('Authentication success redirect error: ' . $e->getMessage());
            // Default fallback redirect
            return new RedirectResponse($this->urlGenerator->generate('app_user_dashboard'));
        }
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }
}
