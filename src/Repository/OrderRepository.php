<?php

namespace App\Repository;

use App\Entity\Order;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Order>
 */
class OrderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Order::class);
    }
    
    /**
     * Finds a paginated subset of the most recent Order objects.
     * * @return Order[]
     */
    public function findPaginatedOrders(int $limit, int $offset): array
    {
        // Use setMaxResults (LIMIT) and setFirstResult (OFFSET) to paginate
        return $this->createQueryBuilder('o')
            ->orderBy('o.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * Counts the total number of Order entities.
     */
    public function countAll(): int
    {
        // Return the count of all orders for calculating total pages
        return $this->createQueryBuilder('o')
            ->select('COUNT(o.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }
    
    // The original findRecentOrders method can be kept, but is not used for this pagination logic
    /**
     * @return Order[] Returns an array of the most recent Order objects
     */
    public function findRecentOrders(int $limit = 10): array
    {
        return $this->createQueryBuilder('o')
            ->orderBy('o.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * Finds Orders within a specified date range.
     *
     * @param \DateTimeInterface|null $startDate The start date (inclusive)
     * @param \DateTimeInterface|null $endDate The end date (inclusive)
     * @return Order[] Returns an array of Order objects
     */
    public function findByDateRange(?\DateTimeInterface $startDate, ?\DateTimeInterface $endDate): array
    {
        $qb = $this->createQueryBuilder('o');
        
        // Apply start date filter
        if ($startDate) {
            $qb->andWhere('o.orderDate >= :start')
               ->setParameter('start', $startDate);
        }

        // Apply end date filter
        if ($endDate) {
            $qb->andWhere('o.orderDate <= :end')
               ->setParameter('end', $endDate);
        }
        
        // Order by date descending by default
        $qb->orderBy('o.orderDate', 'DESC');

        return $qb->getQuery()->getResult();
    }

    public function countFilteredOrders(?\DateTimeInterface $startDate, ?\DateTimeInterface $endDate, string $status = ''): int
{
    $qb = $this->createQueryBuilder('o')
        ->select('COUNT(o.id)');
        
    if ($startDate) {
        $qb->andWhere('o.createdAt >= :start')->setParameter('start', $startDate);
    }

    if ($endDate) {
        $qb->andWhere('o.createdAt <= :end')->setParameter('end', $endDate);
    }

    if ($status) {
        $qb->andWhere('o.status = :status')->setParameter('status', $status);
    }

    return $qb->getQuery()->getSingleScalarResult();
}

    public function findFilteredPaginatedOrders(?\DateTimeInterface $startDate, ?\DateTimeInterface $endDate, string $status = '', int $limit = 10, int $offset = 0): array
    {
        $qb = $this->createQueryBuilder('o')
            ->orderBy('o.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset);

        if ($startDate) {
            $qb->andWhere('o.createdAt >= :start')->setParameter('start', $startDate);
        }

        if ($endDate) {
            $qb->andWhere('o.createdAt <= :end')->setParameter('end', $endDate);
        }

        if ($status) {
            $qb->andWhere('o.status = :status')->setParameter('status', $status);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Finds the count and total value of orders placed in the last 7 days.
     *
     * @return array An associative array with 'count' and 'value' keys.
     */    
    public function findOrdersCountAndValueLast7Days(): array
    {
        $qb = $this->createQueryBuilder('o')
            ->select('COUNT(o.id) as count, SUM(o.total) as value')
            ->where('o.createdAt >= :sevenDaysAgo')
            ->setParameter('sevenDaysAgo', new \DateTime('-7 days'));

        return $qb->getQuery()->getSingleResult();
    }

    /**
     * Calculates the total revenue from all orders.
     */
    public function getTotalRevenue(): float
    {
        return (float) $this->createQueryBuilder('o')
            ->select('SUM(o.total)')
            ->getQuery()
            ->getSingleScalarResult();
    }
}
