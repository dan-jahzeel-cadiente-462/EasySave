<?php

namespace App\Repository;

use App\Entity\ActivityLog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ActivityLog>
 */
class ActivityLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ActivityLog::class);
    }

    public function findByFilters(?string $user = null, ?string $action = null, ?\DateTime $dateFrom = null, ?\DateTime $dateTo = null, int $limit = 50, int $offset = 0)
    {
        $qb = $this->createQueryBuilder('al')
            ->orderBy('al.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset);

        if ($user) {
            $qb->leftJoin('al.user', 'u')
                ->andWhere('u.username LIKE :user')
                ->setParameter('user', '%' . $user . '%');
        }

        if ($action) {
            $qb->andWhere('al.action = :action')
                ->setParameter('action', $action);
        }

        if ($dateFrom) {
            $qb->andWhere('al.createdAt >= :dateFrom')
                ->setParameter('dateFrom', $dateFrom);
        }

        if ($dateTo) {
            $dateToModified = clone $dateTo;
            $dateToModified->modify('+1 day');
            $qb->andWhere('al.createdAt < :dateTo')
                ->setParameter('dateTo', $dateToModified);
        }

        return $qb->getQuery()->getResult();
    }

    public function countByFilters(?string $user = null, ?string $action = null, ?\DateTime $dateFrom = null, ?\DateTime $dateTo = null): int
    {
        $qb = $this->createQueryBuilder('al')
            ->select('COUNT(al.id)');

        if ($user) {
            $qb->leftJoin('al.user', 'u')
                ->andWhere('u.username LIKE :user')
                ->setParameter('user', '%' . $user . '%');
        }

        if ($action) {
            $qb->andWhere('al.action = :action')
                ->setParameter('action', $action);
        }

        if ($dateFrom) {
            $qb->andWhere('al.createdAt >= :dateFrom')
                ->setParameter('dateFrom', $dateFrom);
        }

        if ($dateTo) {
            $dateToModified = clone $dateTo;
            $dateToModified->modify('+1 day');
            $qb->andWhere('al.createdAt < :dateTo')
                ->setParameter('dateTo', $dateToModified);
        }

        return (int)$qb->getQuery()->getSingleScalarResult();
    }

    public function getRecentLogs(int $limit = 20)
    {
        return $this->createQueryBuilder('al')
            ->orderBy('al.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findByUser(int $userId)
    {
        return $this->createQueryBuilder('al')
            ->andWhere('al.user = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('al.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
