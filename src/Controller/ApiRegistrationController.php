<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\EmailVerificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api')]
class ApiRegistrationController extends AbstractController
{
    public function __construct(

        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
        private EmailVerificationService $emailVerificationService,
        private ValidatorInterface $validator
    ) {
    }

    #[Route('/register', name: 'api_register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);


        // Validate required fields
        if (!isset($data['email']) || !isset($data['password'])) {
            return $this->json([
                'success' => false,
                'message' => 'Email and password are required'
            ], 400);
        }



        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return $this->json([
                'success' => false,
                'message' => 'Invalid email address'
            ], 400);
        }



        if (strlen($data['password']) < 6) {
            return $this->json([
                'success' => false,
                'message' => 'Password must be at least 6 characters long'
            ], 400);
        }

        // Check if email already exists (username = email)
        $existingUser = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy(['username' => $data['email']]);


        if ($existingUser) {
            return $this->json([
                'success' => false,
                'message' => 'Email already registered'
            ], 409);
        }



        // Create new user

        $user = new User();
        // Username should be provided in the form data
        $username = $data['username'] ?? null;
        if (!$username) {
            return $this->json([
                'success' => false,
                'message' => 'Username is required'
            ], 400);
        }
        $user->setUsername($username);
        $user->setEmail($data['email']);



        // Hash password
        $hashedPassword = $this->passwordHasher->hashPassword($user, $data['password']);
        $user->setPassword($hashedPassword);

        // Set default role
        $user->setRoles(['ROLE_USER']);

        // Auto-verify user (email verification disabled)
        $user->setIsVerified(true);
        $user->setVerificationToken(null);

        // Validate entity
        $errors = $this->validator->validate($user);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {

                $errorMessages[] = $error->getMessage();
            }
            return $this->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $errorMessages
            ], 400);
        }

        // Save user
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $this->json([
            'success' => true,
            'message' => 'Registration successful. You can now login.',
            'user' => [
                'id' => $user->getId(),
                'username' => $user->getUsername(),
                'email' => $user->getEmail(),
                'isVerified' => $user->isVerified(),
                'roles' => $user->getRoles()
            ]
        ], 201);
    }
}