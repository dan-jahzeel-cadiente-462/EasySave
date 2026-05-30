<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\EmailVerificationService;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/auth')]
class GoogleOAuthController extends AbstractController
{
    public function __construct(
        private ClientRegistry $clientRegistry,
        private EntityManagerInterface $entityManager,
        private EmailVerificationService $emailVerificationService,
        private Security $security
    ) {
    }

    /**
     * Redirect to Google OAuth login
     * Used for Staff/User login with Google OAuth
     * Admins are NOT allowed to use OAuth (remain offline)
     */
    #[Route('/connect/google', name: 'oauth_connect_google')]
    public function connectToGoogle(): Response
    {
        $user = $this->getUser();

        // Prevent admins from using OAuth - they must use traditional login
        if ($user && in_array('ROLE_ADMIN', $user->getRoles())) {
            $this->addFlash('error', 'Admin accounts cannot use OAuth login. Please use your regular credentials.');
            return $this->redirectToRoute('app_login');
        }

        // IMPORTANT: OAuth callback must match the Google console redirect_uri.
        // This project uses the KnpU OAuth2 bundle callback: /connect/google/check
        // so we start OAuth using that flow.
        return $this->redirectToRoute('connect_google_start');
    }

    // NOTE: Callback is handled by KnpU OAuth2 bundle + App\Security\GoogleAuthenticator
    // and uses route: connect_google_check (/connect/google/check)

    // Keeping this controller around, but the dedicated callback endpoints below should NOT be used for OAuth.
    

    /**
     * Google OAuth callback for Staff/User login
     * Admins cannot authenticate via OAuth and remain offline
     */
    // Deprecated/unused: kept for legacy routes, but DO NOT configure Google console redirect_uri to this.
    // #[Route('/google/callback', name: 'app_oauth_google_callback')]
    // public function handleGoogleCallback(): Response
    // {
    //     throw new \LogicException('Unused OAuth callback. Use /connect/google/check instead.');
    // }
    

    /**
     * Start Google OAuth flow for Staff/User authentication
     * Admins cannot initiate OAuth and remain offline
     */
    #[Route('/google/staff-login', name: 'app_google_staff_login')]
    public function googleStaffLogin(): Response
    {
        // Check if user is already logged in
        $currentUser = $this->getUser();
        if ($currentUser && in_array('ROLE_ADMIN', $currentUser->getRoles())) {
            $this->addFlash('error', 'Admin accounts cannot use OAuth login. Please use your regular credentials.');
            return $this->redirectToRoute('app_login');
        }

        // Redirect to OAuth flow
        $client = $this->clientRegistry->getClient('google');
        return $this->redirect(
            $client->getOAuthClient()->getAuthorizationUrl([
                'scope' => ['openid', 'email', 'profile'],
                'access_type' => 'online'
            ])
        );
    }

    /**
     * Manage connected Google account
     */
    #[Route('/google/disconnect', name: 'app_oauth_google_disconnect')]
    public function disconnectFromGoogle(#[CurrentUser] ?User $user): Response
    {
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // Clear OAuth data if stored in future implementation
        $this->addFlash('success', 'Google account disconnected successfully.');
        return $this->redirectToRoute('app_dashboard');
    }
}
