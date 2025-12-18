<?php

namespace App\Controller\Admin;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class AccountController extends AbstractController
{
    #[Route('/profile', name: 'app_admin_account_profile')]
    public function profile(): Response
    {
        // Allow both admins and staff to view their profile
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_STAFF')) {
            throw $this->createAccessDeniedException('Access Denied. Admin or Staff role required.');
        }

        $user = $this->getUser();

        return $this->render('admin/account/index.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/change-password', name: 'app_admin_account_change_password', methods: ['GET', 'POST'])]
    public function changePassword(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $hasher): Response
    {
        // Allow both admins and staff to change their password
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_STAFF')) {
            throw $this->createAccessDeniedException('Access Denied. Admin or Staff role required.');
        }

        $user = $this->getUser();

        if ($request->isMethod('POST')) {
            $currentPassword = $request->request->get('current_password');
            $newPassword = $request->request->get('new_password');
            $confirmPassword = $request->request->get('confirm_password');

            // Verify current password
            if (!$hasher->isPasswordValid($user, $currentPassword)) {
                $this->addFlash('error', 'Current password is incorrect');
                return $this->redirectToRoute('app_admin_account_change_password');
            }

            // Verify new passwords match
            if ($newPassword !== $confirmPassword) {
                $this->addFlash('error', 'New passwords do not match');
                return $this->redirectToRoute('app_admin_account_change_password');
            }

            // Validate password strength
            if (strlen($newPassword) < 6) {
                $this->addFlash('error', 'Password must be at least 6 characters long');
                return $this->redirectToRoute('app_admin_account_change_password');
            }

            $user->setPassword($hasher->hashPassword($user, $newPassword));
            $entityManager->flush();

            $this->addFlash('success', 'Password changed successfully');
            return $this->redirectToRoute('app_admin_account_profile');
        }

        return $this->render('admin/account/change-password.html.twig');
    }

    #[Route('/update-profile', name: 'app_admin_account_update_profile', methods: ['POST'])]
    public function updateProfile(Request $request, EntityManagerInterface $entityManager): Response
    {
        // Allow both admins and staff to update their profile
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_STAFF')) {
            throw $this->createAccessDeniedException('Access Denied. Admin or Staff role required.');
        }

        $user = $this->getUser();

        $firstName = trim($request->request->get('first_name', ''));
        $lastName = trim($request->request->get('last_name', ''));

        // Basic validation
        if (empty($firstName) || empty($lastName)) {
            $this->addFlash('error', 'First name and last name are required');
            return $this->redirectToRoute('app_admin_account_profile');
        }

        if (strlen($firstName) > 50 || strlen($lastName) > 50) {
            $this->addFlash('error', 'First name and last name must be 50 characters or less');
            return $this->redirectToRoute('app_admin_account_profile');
        }

        $user->setFirstName($firstName);
        $user->setLastName($lastName);
        $entityManager->flush();

        $this->addFlash('success', 'Profile updated successfully');
        return $this->redirectToRoute('app_admin_account_profile');
    }
}
