<?php

namespace App\Controller\Admin;

use App\Entity\Category;
use App\Form\CategoryType;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/category')]
final class CategoryController extends AbstractController
{
    #[Route('/', name: 'app_category_index', methods: ['GET'])]
    public function index(Request $request, CategoryRepository $categoryRepository): Response
    {
        // 1. Get query parameters
        $searchQuery = trim($request->query->get('q', ''));
        $page = max(1, $request->query->getInt('page', 1));
        $limit = 10; // Items per page

        // Handle sorting
        $sortCombined = $request->query->get('sort_by_direction');

        if ($sortCombined && str_contains($sortCombined, '-')) {
            [$sortBy, $sortDirection] = explode('-', $sortCombined, 2);
        } else {
            // Default sorting
            $sortBy = 'id';
            $sortDirection = 'desc';
        }

        // Validate sort direction
        $sortDirection = in_array(strtolower($sortDirection), ['asc', 'desc']) ? strtolower($sortDirection) : 'desc';

        // Validate sort field
        $allowedSortFields = ['id', 'name', 'description', 'products', 'parent', 'createdAt'];
        $sortBy = in_array($sortBy, $allowedSortFields) ? $sortBy : 'id';

        // 2. Get total filtered items for pagination count
        $totalItems = $categoryRepository->countFiltered($searchQuery);
        $totalPages = $totalItems > 0 ? (int) ceil($totalItems / $limit) : 1;

        // Ensure page is within valid range
        $page = min($page, $totalPages);

        $offset = ($page - 1) * $limit;

        // 3. Fetch the categories for the current page
        $categories = $categoryRepository->findFilteredAndPaginated(
            $searchQuery,
            $sortBy,
            $sortDirection,
            $limit,
            $offset
        );

        return $this->render('admin/category/index.html.twig', [
            'categories' => $categories,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalItems' => $totalItems,
            'searchQuery' => $searchQuery,
            'sortBy' => $sortBy,
            'sortDirection' => $sortDirection,
        ]);
    }

    #[Route('/new', name: 'app_category_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $category = new Category();
        $form = $this->createForm(CategoryType::class, $category, [
            'current_category' => null, // No current category for new
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($this->getUser()) {
                $category->setCreatedBy($this->getUser());
            }
            $entityManager->persist($category);
            $entityManager->flush();
            $this->addFlash('success', 'Category created successfully!');
            return $this->redirectToRoute('app_category_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/category/new.html.twig', [
            'category' => $category,
            'form' => $form,
        ]);
    }

    #[Route('/{category}', name: 'app_category_show', methods: ['GET'])]
    public function show(Category $category): Response
    {
        return $this->render('admin/category/show.html.twig', [
            'category' => $category,
        ]);
    }

    #[Route('/{category}/edit', name: 'app_category_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Category $category, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(CategoryType::class, $category, [
            'current_category' => $category, // Pass the current category
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Category updated successfully.');
            return $this->redirectToRoute('app_category_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/category/edit.html.twig', [
            'category' => $category,
            'form' => $form,
        ]);
    }

    #[Route('/{category}', name: 'app_category_delete', methods: ['POST'])]
    public function delete(Request $request, Category $category, EntityManagerInterface $entityManager, CategoryRepository $categoryRepository): Response
    {
        if ($this->isCsrfTokenValid('delete' . $category->getId(), $request->request->get('_token'))) {
            // Check if category has children - reassign to parent or prevent deletion
            $children = $categoryRepository->findBy(['parent' => $category]);

            if (!empty($children)) {
                // If category has children, reassign them to the parent before deletion
                foreach ($children as $child) {
                    $child->setParent($category->getParent());
                }
            }

            $entityManager->remove($category);
            $entityManager->flush();

            $this->addFlash('success', 'Category deleted successfully.');
        } else {
            $this->addFlash('danger', 'Invalid security token.');
        }

        return $this->redirectToRoute('app_category_index');
    }
}