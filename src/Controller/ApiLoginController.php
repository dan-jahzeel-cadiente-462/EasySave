<?php

namespace App\Controller;

use App\Repository\ProductRepository;
use App\Service\EmailVerificationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User;

class ApiLoginController extends AbstractController
{
    public function __construct(
        private UserProviderInterface $userProvider,
        private UserPasswordHasherInterface $passwordHasher,
        private JWTTokenManagerInterface $jwtManager,
        private EmailVerificationService $emailVerificationService,
        private EntityManagerInterface $entityManager
    ) {}

    #[Route('/api/login', name: 'api_login', methods: ['POST'])]
    public function login(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);


        if (!isset($data['username']) || !isset($data['password'])) {
            return $this->json(['message' => 'missing credentials'], 401);
        }

        try {
            /** @var User $user */
            // Try finding by email first (as per provider default), then by username
            $user = $this->userProvider->loadUserByIdentifier($data['username']);
        } catch (AuthenticationException) {
            // Fallback: try finding by username manually if loadUserByIdentifier (email) failed
            $user = $this->entityManager->getRepository(User::class)->findOneBy(['username' => $data['username']]);
            
            if (!$user) {
                return $this->json(['message' => 'invalid credentials'], 401);
            }
        }


        if (!$this->passwordHasher->isPasswordValid($user, $data['password'])) {
            return $this->json(['message' => 'invalid credentials'], 401);
        }

        if (!$user->isVerified()
            && !$this->emailVerificationService->isExemptFromEmailVerification($user)
            && $user->getProvider() !== 'google'
        ) {
            return $this->json([
                'message' => 'Please verify your email before logging in. Check your inbox for the verification link.',
                'code' => 'EMAIL_NOT_VERIFIED'
            ], 401);
        }

        $token = $this->jwtManager->create($user);

        return $this->json([
            'token' => $token,
            'user' => $user->getUserIdentifier(),
            'first_name' => $user->getFirstName(),
            'last_name' => $user->getLastName(),
            'roles' => $user->getRoles(),
        ]);
    }
}