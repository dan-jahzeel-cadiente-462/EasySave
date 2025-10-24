<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class UserManagementController extends AbstractController
{
    /*
    #[Route('/user/management', name: 'app_user_management')]
    public function index(): Response
    {
        return $this->render('user_management/index.html.twig', [
            'controller_name' => 'UserManagementController',
        ]);
    }]
    */

    #[Route('admin/user/management', name: 'app_admin_user_management')]
    public function index(UserRepository $userRepository): Response
    {
        return $this->render('admin/users/index.html.twig', [
            'users' => $userRepository->findAll(),
        ]);
    }

    #[Route('admin/user/management/{id}', name: 'app_admin_user_show', methods: ['GET'])]
    public function show(User $user): Response
    {
        return $this->render('admin/users/show.html.twig', [
            'user' => $user,
        ]);
    }
}
