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

#[Route('/category')]
final class CategoryController extends AbstractController
{
    /**
     * Finds, filters, sorts, and paginates all categories efficiently using the repository.
     */
    #[Route(name: 'app_category_index', methods: ['GET'])]
    public function index(Request $request, CategoryRepository $categoryRepository): Response
    {
        // 1. Get query parameters
        $searchQuery = $request->query->get('q', '');
        $page = $request->query->getInt('page', 1);
        $limit = 10; // Items per page

        // --- NEW SORTING LOGIC: Handle the combined 'sort_by_direction' dropdown parameter ---
        $sortCombined = $request->query->get('sort_by_direction');

        if ($sortCombined && str_contains($sortCombined, '-')) {
            // Split the combined value (e.g., 'name-asc')
            [$sortBy, $sortDirection] = explode('-', $sortCombined, 2);
        } else {
            // Fallback: use individual sort/direction params (from search form or pagination links)
            // Default sort by ID ascending
            $sortBy = $request->query->get('sort', 'id');
            $sortDirection = $request->query->get('direction', 'asc');
        }
        // --- END NEW SORTING LOGIC ---

        // 2. Get total filtered items for pagination count
        // This relies on the new 'countFiltered' method in the Repository
        $totalItems = $categoryRepository->countFiltered($searchQuery);
        $totalPages = (int)ceil($totalItems / $limit);
        
        // Ensure page is within valid range
        $page = max(1, min($page, $totalPages > 0 ? $totalPages : 1));
        
        $offset = max(0, ($page - 1) * $limit);

        // 3. Fetch the categories for the current page
        // This relies on the new 'findFilteredAndPaginated' method in the Repository
        $categories = $categoryRepository->findFilteredAndPaginated(
            $searchQuery,
            $sortBy,
            $sortDirection,
            $limit,
            $offset
        );
        // --- End Efficient Database Logic ---

        return $this->render('admin/category/index.html.twig', [
            'categories' => $categories,
            // Pagination and state variables for the template
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalItems'=> $totalItems,
            'searchQuery' => $searchQuery,
            'sortBy' => $sortBy,
            'sortDirection' => $sortDirection,
        ]);
    }

    /**
     * REMOVED: applySearchFilter, applySort, and getCategoryProperty methods.
     * They are no longer needed as the logic is now handled in CategoryRepository 
     * using Doctrine's QueryBuilder for database-level efficiency.
     */

    #[Route('/new', name: 'app_category_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $category = new Category();
        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
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

    #[Route('/{id}', name: 'app_category_show', methods: ['GET'])]
    public function show(Category $category): Response
    {
        return $this->render('admin/category/show.html.twig', [
            'category' => $category,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_category_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Category $category, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(CategoryType::class, $category);
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

    #[Route('/{id}', name: 'app_category_delete', methods: ['POST'])]
    public function delete(Request $request, Category $category, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$category->getId(), $request->request->get('_token'))) {
            $entityManager->remove($category);
            $entityManager->flush();

            $this->addFlash('success', 'Category deleted successfully.');
        } else {
            $this->addFlash('danger', 'Invalid security token.');
        }

        return $this->redirectToRoute('app_category_index');
    }
}