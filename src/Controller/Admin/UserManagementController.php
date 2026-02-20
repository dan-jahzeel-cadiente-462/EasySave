<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Entity\ActivityLog;
use App\Repository\ActivityLogRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class UserManagementController extends AbstractController
{
    #[Route('/user/management', name: 'app_user_management')]
    public function index(UserRepository $userRepository, ActivityLogRepository $activityLogRepository, Request $request): Response
    {
        $search = $request->query->get('search', '');
        $role = $request->query->get('role', '');
        $status = $request->query->get('status', '');
        $logs = $activityLogRepository->findAll();

        // Build the query dynamically based on filters
        $qb = $userRepository->createQueryBuilder('u');

        if ($search) {
            $qb->andWhere('u.username LIKE :search OR u.email LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        if ($role) {
            $qb->andWhere('u.roles LIKE :role')
                ->setParameter('role', '%' . $role . '%');
        }

        if ($status === 'active') {
            $qb->andWhere('u.isActive = true');
        } elseif ($status === 'inactive') {
            $qb->andWhere('u.isActive = false');
        }

        $users = $qb->orderBy('u.id', 'DESC')
                    ->getQuery()
                    ->getResult();

        return $this->render('admin/user_management/index.html.twig', [
            'users' => $users,
        ]);
    }

    #[Route('/management/create', name: 'app_admin_user_create', methods: ['GET', 'POST'])]
    public function create(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $hasher): Response
    {
        if ($request->isMethod('POST')) {
            $username = $request->request->get('username');
            $email = $request->request->get('email');
            $password = $request->request->get('password');
            $confirmPassword = $request->request->get('confirm_password');
            $role = $request->request->get('role', 'ROLE_USER');

            // Validation
            if (!$username || !$email || !$password || $password !== $confirmPassword) {
                $this->addFlash('error', 'Invalid input or passwords do not match');
                return $this->redirectToRoute('app_admin_user_create');
            }

            $user = new User();
            $user->setUsername($username);
            $user->setEmail($email);
            $user->setPassword($hasher->hashPassword($user, $password));
            $user->setRoles([$role]);
            $user->setCreatedAt(new \DateTime());
            $user->setIsActive(true);

            $entityManager->persist($user);
            $entityManager->flush();

            // Activity logging removed to avoid authentication side-effects

            $this->addFlash('success', "User '$username' created successfully");
            return $this->redirectToRoute('app_user_management');
        }

        return $this->render('admin/user_management/create.html.twig');
    }

    #[Route('/management/{id}', name: 'app_admin_user_show', methods: ['GET'])]
    public function show(User $user, ActivityLog $logs): Response
    {
        return $this->render('admin/user_management/show.html.twig', [
            'user' => $user,
            'logs' => $logs
        ]);
    }

    
    #[Route('/management/{id}/edit', name: 'app_admin_user_edit', methods: ['GET', 'POST'])]
    public function edit(User $user, Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $hasher): Response
    {
        if ($request->isMethod('POST')) {
            $username = $request->request->get('username');
            $email = $request->request->get('email');
            $role = $request->request->get('role');
            $newPassword = $request->request->get('new_password');
            $confirmNewPassword = $request->request->get('confirm_new_password');

            // Validation
            if (!$username || !$email) {
                $this->addFlash('error', 'Username and email are required');
                return $this->redirectToRoute('app_admin_user_edit', ['id' => $user->getId()]);
            }

            if ($newPassword && $newPassword !== $confirmNewPassword) {
                $this->addFlash('error', 'Passwords do not match');
                return $this->redirectToRoute('app_admin_user_edit', ['id' => $user->getId()]);
            }

            $user->setUsername($username);
            $user->setEmail($email);
            $user->setRoles([$role]);

            if ($newPassword) {
                $user->setPassword($hasher->hashPassword($user, $newPassword));
            }

            $entityManager->flush();

            $this->addFlash('success', "User '$username' updated successfully");
            return $this->redirectToRoute('app_admin_user_show', ['id' => $user->getId()]);
        }

        return $this->render('admin/user_management/edit.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/management/{id}/delete', name: 'app_admin_user_delete', methods: ['POST'])]
    public function delete(User $user, Request $request, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isCsrfTokenValid('delete_user' . $user->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token');
        }

        $username = $user->getUsername();

        // Activity logging removed to avoid authentication side-effects

        $entityManager->remove($user);
        $entityManager->flush();

        $this->addFlash('success', "User '$username' deleted successfully");
        return $this->redirectToRoute('app_user_management');
    }

     #[Route('/management/{id}/toggle-status', name: 'app_admin_user_toggle_status', methods: ['POST'])]
    public function toggleStatus(User $user, Request $request, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isCsrfTokenValid('toggle_status' . $user->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token');
        }

        $currentStatus = $user->isActive();
        $newStatus = !$currentStatus;
        $user->setIsActive($newStatus);
        $entityManager->flush();

        // Activity logging removed to avoid authentication side-effects

        $status = $user->isActive() ? 'activated' : 'deactivated';
        $this->addFlash('success', "User account $status successfully");
        return $this->redirectToRoute('app_admin_user_show', ['id' => $user->getId()]);
    }

    #[Route('/management/{id}/toggle-staff', name: 'app_admin_user_toggle_staff', methods: ['POST'])]
    public function toggleStaff(User $user, EntityManagerInterface $entityManager, Request $request): Response
    {
        if (!$this->isCsrfTokenValid('toggle_staff' . $user->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token');
        }

        $roles = $user->getRoles();
        $isRemovingStaff = false;
        
        if (in_array('ROLE_STAFF', $roles)) {
            $roles = array_filter($roles, function($role) {
                return $role !== 'ROLE_STAFF';
            });
            $message = 'Staff role removed successfully.';
            $isRemovingStaff = true;
        } else {
            $roles[] = 'ROLE_STAFF';
            $message = 'Staff role assigned successfully.';
        }

        $user->setRoles($roles);
        $entityManager->flush();

        // Activity logging removed to avoid authentication side-effects

        $this->addFlash('success', $message);
        return $this->redirectToRoute('app_admin_user_show', ['id' => $user->getId()]);
    }

}
