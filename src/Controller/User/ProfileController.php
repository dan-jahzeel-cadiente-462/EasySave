<?php

namespace App\Controller\User;

use App\Entity\User;
use App\Form\UserType;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;

final class ProfileController extends AbstractController
{
    #[Route('/user/profile', name: 'app_user_profile')]
    public function index(): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        return $this->render('user/profile/index.html.twig', ['user' => $this->getUser()]);
    }

    #[Route('/user/profile/{id}/edit', name: 'user_profile_edit_show')] // <-- Updated route
    public function edit(Request $request, User $user, EntityManagerInterface $entityManager): Response // Removed SluggerInterface, as it's unused in this context
    {
        // Deny access if the authenticated user is not the one being edited,
        // or if they don't have the appropriate role (e.g., ROLE_ADMIN)
        if ($user !== $this->getUser() && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(UserType::class, $user); // <-- Corrected form class and object
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()) { // <-- Corrected function calls
            $entityManager->flush();

            $this->addFlash('success', 'Profile updated successfully!');

            // Redirect back to the profile index page
            return $this->redirectToRoute('app_user_profile', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('user/profile/edit.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }
}