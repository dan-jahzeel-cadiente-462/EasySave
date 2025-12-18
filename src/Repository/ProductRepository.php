<?php

namespace App\Repository;

use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Product>
 */
class ProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    /**
     * Creates a QueryBuilder instance with search and category filters applied.
     * This method is intended to be used when you need the QueryBuilder object itself
     * (e.g., for pagination or further query manipulation).
     *
     * @param string|null $q Search query
     * @param int|null $categoryId Category ID
     * @return QueryBuilder
     */
    public function getFilterQueryBuilder(?string $q, ?int $categoryId): QueryBuilder
    {
        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.category', 'c')
            ->addSelect('c');

        if ($q) {
            // Apply search filter (name, description, brand)
            $qb->andWhere('p.name LIKE :q OR p.description LIKE :q OR p.brand LIKE :q')
               ->setParameter('q', '%'.trim($q).'%');
        }

        if ($categoryId) {
            // Apply category filter
            $qb->andWhere('c.id = :cat')->setParameter('cat', $categoryId);
        }

        // Sorting is intentionally left out here to keep the base query builder generic.

        return $qb;
    }

    /**
     * Find products with optional search, category and sort options.
     *
     * @param string|null $q
     * @param int|null $categoryId
     * @param string|null $sort ('price_asc'|'price_desc'|'name'|'popular'|'newest')
     * @return Product[]
     */
    public function findWithFilters(?string $q, ?int $categoryId, ?string $sort): array
    {
        // Use the new query builder method for filtering
        $qb = $this->getFilterQueryBuilder($q, $categoryId);

        switch ($sort) {
            case 'price_asc':
                $qb->orderBy('p.price', 'ASC');
                break;
            case 'price_desc':
                $qb->orderBy('p.price', 'DESC');
                break;
            case 'name':
                $qb->orderBy('p.name', 'ASC');
                break;
            case 'popular':
                $qb->orderBy('p.stock', 'DESC');
                break;
            case 'newest':
                $qb->orderBy('p.id', 'DESC');
                break;
            default:
                $qb->orderBy('p.id', 'DESC');
        }

        return $qb->getQuery()->getResult();
    }
}
