<?php

namespace App\Controller;

use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Security\AdminLoginAuthenticator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    /**
     * Admin login page (form-based only, no OAuth)
     */
    #[Route(path: '/admin/login', name: 'app_admin_login')]
    public function adminLogin(Request $request, AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
            $user = $this->getUser();
            $roles = $user->getRoles();
            if (in_array('ROLE_ADMIN', $roles, true) || in_array('ROLE_STAFF', $roles, true)) {
                return $this->redirectToRoute('app_admin_dashboard');
            }
            return $this->redirectToRoute('app_user_dashboard');
        }

        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();
        $lastLoginType = $request->getSession()->get(
            AdminLoginAuthenticator::SESSION_LAST_LOGIN_TYPE,
            'email'
        );

        return $this->render('admin/login.html.twig', [
            'last_username' => $lastUsername,
            'last_login_type' => $lastLoginType,
            'error' => $error,
        ]);
    }

    /**
     * Staff/User login page (form-based AND OAuth)
     */
    #[Route(path: '/login', name: 'app_user_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
            $user = $this->getUser();
            $roles = $user->getRoles();
            
            if (in_array('ROLE_ADMIN', $roles, true) || in_array('ROLE_STAFF', $roles, true)) {
                return $this->redirectToRoute('app_admin_dashboard');
            }
            
            return $this->redirectToRoute('app_user_dashboard');
        }

        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    /**
     * Link to this controller to start the Google OAuth process
     */
    #[Route('/connect/google', name: 'connect_google_start')]
    public function connectGoogleAction(ClientRegistry $clientRegistry): RedirectResponse
    {
        return $clientRegistry
            ->getClient('google')
            ->redirect(
                ['profile', 'email'],
                []
            );
    }

    /**
     * Google OAuth callback
     * Handled by GoogleAuthenticator in the security firewall
     */
    #[Route('/connect/google/check', name: 'connect_google_check')]
    public function connectGoogleCheckAction(): void
    {
        // Handled by GoogleAuthenticator
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    #[Route('/deactivated', name: 'app_deactivated')]
    public function deactivated(): Response
    {
        return $this->render('security/deactivated.html.twig');
    }
}
