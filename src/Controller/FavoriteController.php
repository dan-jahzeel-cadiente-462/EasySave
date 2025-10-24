<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Product; 
use App\Entity\Favorite; 
use App\Entity\User; 
use App\Repository\FavoriteRepository; // Import the repository

// Note: Using a clearer route prefix 'app_user_favorite_'
#[Route('/user/favorite', name: 'app_user_favorite_')] 
final class FavoriteController extends AbstractController
{
    #[Route('/toggle/{id}', name: 'toggle', methods: ['POST'])]
    public function toggle(
        Product $product, // Symfony finds the Product by {id} automatically
        Request $request,
        EntityManagerInterface $entityManager,
        FavoriteRepository $favoriteRepository // Inject the repository
    ): Response {
        // 1. Ensure the user is logged in
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY'); 

        /** @var User $user */
        $user = $this->getUser();
        
        // 2. Check if the favorite relationship already exists
        // Using the custom repository method for clarity and efficiency
        $favorite = $favoriteRepository->findOneByProductAndUser($product->getId(), $user->getId());

        if ($favorite) {
            // 3. If found, remove it (UNFAVORITE)
            $entityManager->remove($favorite);
            $message = sprintf('Product "%s" removed from favorites.', $product->getName());
        } else {
            // 4. If not found, create and persist a new Favorite (FAVORITE)
            $newFavorite = new Favorite();
            $newFavorite->setUser($user);
            $newFavorite->setProduct($product);
            $entityManager->persist($newFavorite);
            $message = sprintf('Product "%s" added to favorites.', $product->getName());
        }

        $entityManager->flush();
        $this->addFlash('success', $message);

        // Redirect back to the page the user came from
        return $this->redirect($request->headers->get('referer', $this->generateUrl('app_user_shop')));
    }
}