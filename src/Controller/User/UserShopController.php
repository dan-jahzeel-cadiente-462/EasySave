<?php

namespace App\Controller\User;

use App\Repository\ProductRepository;
use App\Repository\CategoryRepository; // ADDED
use App\Repository\FavoriteRepository; // ADDED
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\Product; // ADDED

final class UserShopController extends AbstractController
{
    #[Route('/user/shop', name: 'app_user_shop')]
    public function index(
        Request $request,
        ProductRepository $productRepository,
        CategoryRepository $categoryRepository, // ADDED
        FavoriteRepository $favoriteRepository // ADDED
    ): Response
    {
        $q = $request->query->get('q');
        $category = $request->query->get('category');
        $sort = $request->query->get('sort');
        $view = $request->query->get('view', 'grid'); // Default to grid view

        // Fetch products using the repository's findWithFilters method
        $products = $productRepository->findWithFilters($q, $category ? (int)$category : null, $sort);

        // Fetch categories for filter dropdown
        $categories = $categoryRepository->findAll();

        // Check for user favorites
        $favoriteProductIds = [];
        if ($this->getUser()) {
            // Assumes a method like this exists on FavoriteRepository
            $favoriteProductIds = $favoriteRepository->findFavoriteProductIdsByUser($this->getUser()->getId());
        }

        return $this->render('user/shop/index.html.twig', [
            'products' => $products,
            'categories' => $categories,
            'q' => $q,
            'sort' => $sort,
            'selectedCategory' => $category, // Pass this to pre-select the dropdown
            'favoriteProductIds' => $favoriteProductIds, // Pass favorite IDs to the template
            'view' => $view, // Pass view mode to template
        ]);
    }

    #[Route('/user/shop/product/{id}', name: 'app_user_shop_show')]
    public function show(
        Product $product, // Use Symfony's ParamConverter to fetch Product automatically
        FavoriteRepository $favoriteRepository // Inject the repository here
    ): Response
    {
        // $product = $productRepository->find($id); // This line is no longer needed when using Product $product
        
        // Use the injected repository to check if the current user has favorited this product
        $isFavorite = false;
        if ($this->getUser()) {
            $isFavorite = $favoriteRepository->findOneByProductAndUser(
                $product->getId(), 
                $this->getUser()->getId()
            ) !== null;
        }

        if (!$product) {
            throw $this->createNotFoundException('Product not found');
        }

        return $this->render('user/shop/show.html.twig', [
            'product' => $product,
            'isFavorite' => $isFavorite, // Pass the boolean flag to the template
        ]);
    }
}