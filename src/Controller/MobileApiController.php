<?php

namespace App\Controller;

use App\Entity\Product;
use App\Entity\Category;
use App\Repository\ProductRepository;
use App\Repository\CategoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

#[Route('/api/mobile')]
class MobileApiController extends AbstractController
{
    public function __construct(
        private ProductRepository $productRepository,
        private CategoryRepository $categoryRepository,
        private EntityManagerInterface $entityManager
    ) {
    }

    /**
     * Get paginated list of products
     * 
     * Query Parameters:
     * - page: int (default: 1)
     * - limit: int (default: 20, max: 100)
     * - category: int (optional category ID)
     * - search: string (optional search term)
     * - sort: string (default: 'latest', options: 'price_asc', 'price_desc', 'name_asc', 'latest')
     */
    #[Route('/products', name: 'mobile_products_list', methods: ['GET'])]
    public function listProducts(Request $request): JsonResponse
    {
        try {
            $page = max(1, (int) $request->query->get('page', 1));
            $limit = min(100, max(1, (int) $request->query->get('limit', 20)));
            $categoryId = $request->query->get('category');
            $search = $request->query->get('search');
            $sort = $request->query->get('sort', 'latest');

            $offset = ($page - 1) * $limit;

            // Build query
            $queryBuilder = $this->productRepository->createQueryBuilder('p')
                ->andWhere('p.isActive = true')
                ->setFirstResult($offset)
                ->setMaxResults($limit);

            // Filter by category
            if ($categoryId) {
                $queryBuilder
                    ->andWhere('p.category = :categoryId')
                    ->setParameter('categoryId', (int) $categoryId);
            }

            // Search by name or brand
            if ($search) {
                $searchTerm = '%' . $search . '%';
                $queryBuilder
                    ->andWhere('(p.name LIKE :search OR p.brand LIKE :search OR p.description LIKE :search)')
                    ->setParameter('search', $searchTerm);
            }

            // Sorting
            match ($sort) {
                'price_asc' => $queryBuilder->orderBy('p.price', 'ASC'),
                'price_desc' => $queryBuilder->orderBy('p.price', 'DESC'),
                'name_asc' => $queryBuilder->orderBy('p.name', 'ASC'),
                default => $queryBuilder->orderBy('p.createdAt', 'DESC'),
            };

            $products = $queryBuilder->getQuery()->getResult();
            $total = $this->productRepository->count(
                array_filter([
                    'isActive' => true,
                    'category' => $categoryId ? $this->entityManager->getReference(Category::class, $categoryId) : null,
                ])
            );

            return $this->json([
                'success' => true,
                'data' => [
                    'products' => array_map(fn(Product $p) => $this->productToArray($p), $products),
                    'pagination' => [
                        'current_page' => $page,
                        'page_size' => $limit,
                        'total_items' => $total,
                        'total_pages' => ceil($total / $limit),
                        'has_next' => $page < ceil($total / $limit),
                        'has_previous' => $page > 1,
                    ]
                ],
                'message' => 'Products retrieved successfully'
            ], 200);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => 'Failed to retrieve products',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get single product details
     */
    #[Route('/products/{id}', name: 'mobile_product_detail', methods: ['GET'])]
    public function getProductDetail(int $id): JsonResponse
    {
        try {
            $product = $this->productRepository->find($id);

            if (!$product || !$product->isIsActive()) {
                return $this->json([
                    'success' => false,
                    'error' => 'Product not found',
                    'message' => 'The requested product does not exist or is not available'
                ], 404);
            }

            return $this->json([
                'success' => true,
                'data' => $this->productDetailToArray($product),
                'message' => 'Product retrieved successfully'
            ], 200);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => 'Failed to retrieve product',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all product categories
     * 
     * Query Parameters:
     * - include_count: boolean (include product count per category)
     */
    #[Route('/categories', name: 'mobile_categories_list', methods: ['GET'])]
    public function listCategories(Request $request): JsonResponse
    {
        try {
            $includeCount = $request->query->getBoolean('include_count', false);
            $categories = $this->categoryRepository->findAll();

            $categoryData = array_map(function (Category $category) use ($includeCount) {
                $data = [
                    'id' => $category->getId(),
                    'name' => $category->getName(),
                    'description' => $category->getDescription(),
                ];

                if ($includeCount) {
                    $data['product_count'] = count($category->getProducts());
                }

                return $data;
            }, $categories);

            return $this->json([
                'success' => true,
                'data' => [
                    'categories' => $categoryData,
                    'total' => count($categoryData)
                ],
                'message' => 'Categories retrieved successfully'
            ], 200);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => 'Failed to retrieve categories',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get authenticated user profile
     * Requires: Bearer JWT Token
     */
    #[Route('/user/profile', name: 'mobile_user_profile', methods: ['GET'])]
    public function getUserProfile(#[CurrentUser] ?User $user): JsonResponse
    {
        try {
            if (!$user) {
                return $this->json([
                    'success' => false,
                    'error' => 'Unauthorized',
                    'message' => 'User authentication is required'
                ], 401);
            }

            return $this->json([
                'success' => true,
                'data' => [
                    'id' => $user->getId(),
                    'username' => $user->getUsername(),
                    'email' => $user->getEmail(),
                    'first_name' => $user->getFirstName(),
                    'last_name' => $user->getLastName(),
                    'is_verified' => $user->isIsVerified(),
                    'is_active' => $user->isIsActive(),
                    'roles' => $user->getRoles(),
                    'created_at' => $user->getCreatedAt()?->format('Y-m-d H:i:s'),
                    'profile_picture' => $user->getProfilePicture(),
                ],
                'message' => 'User profile retrieved successfully'
            ], 200);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => 'Failed to retrieve user profile',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user's orders (requires authentication)
     */
    #[Route('/user/orders', name: 'mobile_user_orders', methods: ['GET'])]
    public function getUserOrders(#[CurrentUser] ?User $user, Request $request): JsonResponse
    {
        try {
            if (!$user) {
                return $this->json([
                    'success' => false,
                    'error' => 'Unauthorized',
                    'message' => 'User authentication is required'
                ], 401);
            }

            $page = max(1, (int) $request->query->get('page', 1));
            $limit = min(50, max(1, (int) $request->query->get('limit', 10)));
            $offset = ($page - 1) * $limit;

            $orders = $this->entityManager
                ->getRepository('App\Entity\Order')
                ->createQueryBuilder('o')
                ->where('o.customer = :user')
                ->setParameter('user', $user)
                ->orderBy('o.createdAt', 'DESC')
                ->setFirstResult($offset)
                ->setMaxResults($limit)
                ->getQuery()
                ->getResult();

            $totalOrders = count($user->getOrders());

            $ordersData = array_map(fn($order) => [
                'id' => $order->getId(),
                'order_number' => $order->getOrderNumber() ?? 'N/A',
                'status' => $order->getStatus(),
                'total_amount' => $order->getTotalAmount(),
                'created_at' => $order->getCreatedAt()?->format('Y-m-d H:i:s'),
                'updated_at' => $order->getUpdatedAt()?->format('Y-m-d H:i:s'),
            ], $orders);

            return $this->json([
                'success' => true,
                'data' => [
                    'orders' => $ordersData,
                    'pagination' => [
                        'current_page' => $page,
                        'page_size' => $limit,
                        'total_items' => $totalOrders,
                        'total_pages' => ceil($totalOrders / $limit),
                    ]
                ],
                'message' => 'User orders retrieved successfully'
            ], 200);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => 'Failed to retrieve orders',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user's favorites (requires authentication)
     */
    #[Route('/user/favorites', name: 'mobile_user_favorites', methods: ['GET'])]
    public function getUserFavorites(#[CurrentUser] ?User $user): JsonResponse
    {
        try {
            if (!$user) {
                return $this->json([
                    'success' => false,
                    'error' => 'Unauthorized',
                    'message' => 'User authentication is required'
                ], 401);
            }

            $favorites = $this->entityManager
                ->getRepository('App\Entity\Favorite')
                ->findBy(['user' => $user]);

            $favoritesData = array_map(function ($favorite) {
                $product = $favorite->getProduct();
                return [
                    'id' => $favorite->getId(),
                    'product' => $this->productToArray($product),
                    'added_at' => $favorite->getCreatedAt()?->format('Y-m-d H:i:s'),
                ];
            }, $favorites);

            return $this->json([
                'success' => true,
                'data' => [
                    'favorites' => $favoritesData,
                    'total' => count($favoritesData)
                ],
                'message' => 'User favorites retrieved successfully'
            ], 200);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => 'Failed to retrieve favorites',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Convert Product to standardized array format (summary)
     */
    private function productToArray(Product $p): array
    {
        return [
            'id' => $p->getId(),
            'name' => $p->getName(),
            'brand' => $p->getBrand(),
            'price' => (float) $p->getPrice(),
            'stock' => $p->getStock(),
            'image' => $p->getImagePath(),
            'category' => [
                'id' => $p->getCategory()->getId(),
                'name' => $p->getCategory()->getName(),
            ],
            'is_active' => $p->isIsActive(),
        ];
    }

    /**
     * Convert Product to detailed array format
     */
    private function productDetailToArray(Product $p): array
    {
        return [
            'id' => $p->getId(),
            'name' => $p->getName(),
            'brand' => $p->getBrand(),
            'description' => $p->getDescription(),
            'price' => (float) $p->getPrice(),
            'stock' => $p->getStock(),
            'image' => $p->getImagePath(),
            'category' => [
                'id' => $p->getCategory()->getId(),
                'name' => $p->getCategory()->getName(),
                'description' => $p->getCategory()->getDescription(),
            ],
            'created_at' => $p->getCreatedAt()?->format('Y-m-d H:i:s'),
            'updated_at' => $p->getUpdatedAt()?->format('Y-m-d H:i:s'),
            'is_active' => $p->isIsActive(),
            'created_by' => $p->getCreatedBy() ? [
                'id' => $p->getCreatedBy()->getId(),
                'username' => $p->getCreatedBy()->getUsername(),
            ] : null,
        ];
    }
}
