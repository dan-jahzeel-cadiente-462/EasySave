<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\ProductRepository;
use App\Repository\CategoryRepository;

final class ServicesController extends AbstractController
{
    #[Route('/services', name: 'app_services')]
    public function index(Request $request, ProductRepository $productRepository, CategoryRepository $categoryRepository): Response
    {
        $q = $request->query->get('q');
        $category = $request->query->get('category');
        $sort = $request->query->get('sort');

        // Basic query building (repository could be extended with custom methods)
        $criteria = [];
        if ($category) {
            $criteria['category'] = $category;
        }

        $products = $productRepository->findBy([], [], 100);

        // Filter by search query
        if ($q) {
            $products = array_filter($products, fn($p) => stripos($p->getName(), $q) !== false || stripos((string)$p->getDescription(), $q) !== false);
        }

        // Filter by category id
        if ($category) {
            $products = array_filter($products, fn($p) => $p->getCategory() && $p->getCategory()->getId() == $category);
        }

        // Sorting
        if ($sort === 'price_asc') {
            usort($products, fn($a, $b) => $a->getPrice() <=> $b->getPrice());
        } elseif ($sort === 'price_desc') {
            usort($products, fn($a, $b) => $b->getPrice() <=> $a->getPrice());
        } elseif ($sort === 'name') {
            usort($products, fn($a, $b) => strcmp($a->getName(), $b->getName()));
        }

        $categories = $categoryRepository->findAll();

        $detail = $request->query->get('detail');

        return $this->render('services/index.html.twig', [
            'products' => $products,
            'categories' => $categories,
            'q' => $q,
            'selectedCategory' => $category,
            'sort' => $sort,
            'detail' => $detail,
        ]);
    }

    #[Route('/services/product/{id}', name: 'app_services_product_show', methods: ['GET'])]
    public function showProduct(int $id, ProductRepository $productRepository): Response
    {
        $product = $productRepository->find($id);
        if (!$product) {
            throw $this->createNotFoundException('Product not found');
        }
        return $this->render('services/product_show.html.twig', [
            'product' => $product,
        ]);
    }
}
