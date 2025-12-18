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
     * This method is the efficient replacement for the in-controller logic.
     * * @param string $searchQuery The search term (q).
     * @param string $sortBy The field to sort by (e.g., 'id', 'name', 'products').
     * @param string $sortDirection The direction ('asc' or 'desc').
     * @param int $limit The maximum number of results to return (items per page).
     * @param int $offset The starting point for the results (for pagination).
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
            // Select related products collection for counting (essential for 'products' sort)
            ->leftJoin('c.products', 'prod')
            // Group by category to allow aggregation (e.g., counting products)
            ->groupBy('c.id'); 

        // 1. Apply Filtering (Search)
        if (!empty($searchQuery)) {
            $qb->andWhere('LOWER(c.name) LIKE :query OR LOWER(c.description) LIKE :query')
               ->setParameter('query', '%' . strtolower($searchQuery) . '%');
        }

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
     * Used to calculate totalPages in the controller.
     */
    public function countFiltered(string $searchQuery): int
    {
        $qb = $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            // Join is necessary if filtering is done on related entities (like parent)
            ->leftJoin('c.parent', 'p'); 

        if (!empty($searchQuery)) {
            $qb->andWhere('LOWER(c.name) LIKE :query OR LOWER(c.description) LIKE :query')
               ->setParameter('query', '%' . strtolower($searchQuery) . '%');
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
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
                // Sort by the count of associated products. Note: We use c.id to avoid issues
                // if there are no products, but the HAVING/GROUP BY is the key.
                // We use the aggregation function COUNT(prod.id) here.
                $qb->orderBy('COUNT(prod.id)', $direction);
                break;
            case 'parent':
                // Sort by the parent's name. Use p.name (the joined entity's name field).
                $qb->orderBy('p.name', $direction)
                   // Ensure categories without parents ('None') are handled consistently
                   ->addOrderBy('c.name', 'ASC');
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
}