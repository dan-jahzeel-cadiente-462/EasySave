<?php

namespace App\Repository;

use App\Entity\Category;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Category>
 *
 * @method Category|null find($id, $lockMode = null, $lockVersion = null)
 * @method Category|null findOneBy(array $criteria, array $orderBy = null)
 * @method Category[]    findAll()
 * @method Category[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Category::class);
    }

    /**
     * Finds categories based on search query, applying sorting and pagination.
     *
     * @param string $searchQuery The search term (q)
     * @param string $sortBy The field to sort by (e.g., 'id', 'name', 'products', 'parent')
     * @param string $sortDirection The direction ('asc' or 'desc')
     * @param int $limit The maximum number of results to return (items per page)
     * @param int $offset The starting point for the results (for pagination)
     * @return Category[]
     */
    public function findFilteredAndPaginated(
        string $searchQuery, 
        string $sortBy, 
        string $sortDirection, 
        int $limit, 
        int $offset
    ): array
    {
        // Start building the query
        $qb = $this->createQueryBuilder('c')
            // Join with parent to allow sorting/filtering by parent name
            ->leftJoin('c.parent', 'p')
            // Left join products for counting
            ->leftJoin('c.products', 'prod')
            // Add select with grouping
            ->addSelect('p', 'COUNT(prod.id) as HIDDEN product_count')
            ->groupBy('c.id, p.id');

        // 1. Apply Filtering (Search) - matches on name, description, and parent name
        $this->applySearchFilter($qb, $searchQuery);

        // 2. Apply Sorting
        $this->applySortingToQueryBuilder($qb, $sortBy, $sortDirection);
        
        // 3. Apply Pagination
        $qb->setMaxResults($limit)
           ->setFirstResult($offset);

        // Execute the query
        return $qb->getQuery()->getResult();
    }

    /**
     * Counts total results for pagination without limit/offset.
     */
    public function countFiltered(string $searchQuery): int
    {
        $qb = $this->createQueryBuilder('c')
            ->select('COUNT(DISTINCT c.id)')
            ->leftJoin('c.parent', 'p');

        $this->applySearchFilter($qb, $searchQuery);

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Apply search filter to query builder
     */
    private function applySearchFilter(QueryBuilder $qb, string $searchQuery): void
    {
        if (!empty($searchQuery)) {
            // Split search query into individual keywords
            $keywords = explode(' ', trim($searchQuery));
            $keywords = array_filter($keywords); // Remove empty strings
            
            if (empty($keywords)) {
                return;
            }

            $conditions = [];
            
            foreach ($keywords as $index => $keyword) {
                $paramName = 'query' . $index;
                $keyword = strtolower($keyword);
                
                // Search in category name and description
                $conditions[] = $qb->expr()->orX(
                    $qb->expr()->like('LOWER(c.name)', ':' . $paramName . '_name'),
                    $qb->expr()->like('LOWER(c.description)', ':' . $paramName . '_desc'),
                    $qb->expr()->like('LOWER(p.name)', ':' . $paramName . '_parent')
                );
                
                $qb->setParameter($paramName . '_name', '%' . $keyword . '%')
                   ->setParameter($paramName . '_desc', '%' . $keyword . '%')
                   ->setParameter($paramName . '_parent', '%' . $keyword . '%');
            }
            
            // Combine all keyword conditions with AND
            $qb->andWhere($qb->expr()->andX(...$conditions));
        }
    }

    /**
     * Internal helper to handle sorting logic.
     */
    private function applySortingToQueryBuilder(QueryBuilder $qb, string $sortBy, string $sortDirection): void
    {
        $direction = strtoupper($sortDirection) === 'DESC' ? 'DESC' : 'ASC';

        switch ($sortBy) {
            case 'name':
                $qb->orderBy('c.name', $direction);
                break;
            case 'description':
                $qb->orderBy('c.description', $direction);
                break;
            case 'products':
                // Sort by the count of associated products
                $qb->orderBy('COUNT(prod.id)', $direction);
                break;
            case 'parent':
                // Sort by parent name, with NULLs (no parent) last
                $qb->addSelect('CASE WHEN p.name IS NULL THEN 1 ELSE 0 END as HIDDEN parent_null_sort')
                   ->orderBy('parent_null_sort', 'ASC')
                   ->addOrderBy('p.name', $direction)
                   ->addOrderBy('c.name', 'ASC');
                break;
            case 'createdAt':
                $qb->orderBy('c.createdAt', $direction);
                break;
            case 'id':
            default:
                $qb->orderBy('c.id', $direction);
                break;
        }
    }

    /**
     * Get all descendant category IDs for a given category (recursive).
     * Used to prevent circular references in parent selection.
     */
    public function getDescendantIds(Category $category): array
    {
        $descendants = [];
        $this->collectDescendants($category, $descendants);
        return $descendants;
    }

    /**
     * Recursive helper to collect all descendant IDs.
     */
    private function collectDescendants(Category $category, array &$descendants): void
    {
        foreach ($category->getChildren() as $child) {
            $descendants[] = $child->getId();
            $this->collectDescendants($child, $descendants);
        }
    }

    /**
     * Find categories for dropdown select (optimized)
     */
    public function findForSelect(): array
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.parent', 'p')
            ->orderBy('p.name', 'ASC')
            ->addOrderBy('c.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}