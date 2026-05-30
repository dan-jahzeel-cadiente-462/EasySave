<?php

namespace App\Controller;

use App\Repository\ProductRepository;
use App\Repository\CategoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ProductController extends AbstractController
{
    #[Route('/catalog', name: 'app_catalog')]
    public function catalog(
        Request $request,
        ProductRepository $productRepository,
        CategoryRepository $categoryRepository
    ): Response
    {
        $page = max(1, $request->query->getInt('page', 1));
        $search = $request->query->getString('q', '');
        $categoryId = $request->query->getInt('category', 0);
        $sort = $request->query->getString('sort', 'newest');
        $limit = 12;

        // Get all categories for sidebar
        $categories = $categoryRepository->findBy(['parent' => null]);

        // Build query
        $query = $productRepository->createQueryBuilder('p')
            ->where('p.isActive = :active')
            ->setParameter('active', true)
            ->andWhere('p.stock > :zero')
            ->setParameter('zero', 0);

        // Apply search filter
        if (!empty($search)) {
            $query->andWhere('p.name LIKE :search OR p.description LIKE :search OR p.brand LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        // Apply category filter
        if ($categoryId > 0) {
            $query->andWhere('p.category = :categoryId')
                ->setParameter('categoryId', $categoryId);
        }

        // Apply sorting
        match($sort) {
            'price_low' => $query->orderBy('p.price', 'ASC'),
            'price_high' => $query->orderBy('p.price', 'DESC'),
            'popular' => $query->leftJoin('p.favorites', 'f')
                ->groupBy('p.id')
                ->orderBy('COUNT(f.id)', 'DESC'),
            default => $query->orderBy('p.createdAt', 'DESC'),
        };

        // Efficiently count total results
        $countQuery = clone $query;
        $total = (int) $countQuery->select('COUNT(DISTINCT p.id)')
            ->resetDQLPart('groupBy')
            ->getQuery()
            ->getSingleScalarResult();

        $pages = ceil($total / $limit);
        $offset = ($page - 1) * $limit;

        $products = $query
            ->select('p') // Ensure we only select the main entity for the final result
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();

        return $this->render('product/catalog.html.twig', [
            'products' => $products,
            'categories' => $categories,
            'page' => $page,
            'pages' => $pages,
            'total' => $total,
            'search' => $search,
            'categoryId' => $categoryId,
            'sort' => $sort,
        ]);
    }

    #[Route('/product/{id}', name: 'app_product_show', requirements: ['id' => '\d+'])]
    public function show(
        int $id,
        ProductRepository $productRepository
    ): Response
    {
        $product = $productRepository->find($id);

        if (!$product || !$product->getIsActive()) {
            throw $this->createNotFoundException('Product not found');
        }

        return $this->render('product/show.html.twig', [
            'product' => $product,
        ]);
    }
}
