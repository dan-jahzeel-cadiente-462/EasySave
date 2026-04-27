<?php

namespace App\Controller\Admin;

use App\Repository\CategoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/admin')]
class AdminCategoryController extends AbstractController
{
    public function __construct(
        private CategoryRepository $categoryRepository,
        private SerializerInterface $serializer
    ) {}

    #[Route('/categories/search', name: 'admin_categories_search', methods: ['GET'])]
    public function search(Request $request): JsonResponse
    {
        $searchQuery = $request->query->get('q', '');
        $sortBy = $request->query->get('sort', 'name');
        $sortDirection = $request->query->get('dir', 'asc');
        $limit = (int) $request->query->get('limit', 200);
        $offset = (int) $request->query->get('offset', 0);

        $categories = $this->categoryRepository->findFilteredAndPaginated(
            $searchQuery,
            $sortBy,
            $sortDirection,
            $limit,
            $offset
        );

        $data = array_map(function ($cat) {
            return [
                'id' => $cat->getId(),
                'name' => $cat->getName(),
                'path' => $cat->getHierarchyPath(),
                'description' => $cat->getDescription(),
                'level' => $this->getCategoryLevel($cat),
                'productCount' => $cat->getProducts()->count(),
                'childrenCount' => $cat->getChildren()->count(),
                'label' => $cat->getName() . ' (' . $cat->getHierarchyPath() . ')'
            ];
        }, $categories);

        return new JsonResponse([
            'categories' => $data,
            'total' => count($data)
        ]);
    }

    private function getCategoryLevel($category): int
    {
        $level = 0;
        $parent = $category->getParent();
        while ($parent) {
            $level++;
            $parent = $parent->getParent();
        }
        return $level;
    }
}

