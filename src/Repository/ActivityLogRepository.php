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

    public function countActionsLast7Days(): int
    {
        $sevenDaysAgo = new \DateTime('-7 days');

        $qb = $this->createQueryBuilder('al')
            ->select('COUNT(al.id)')
            ->where('al.createdAt >= :sevenDaysAgo')
            ->setParameter('sevenDaysAgo', $sevenDaysAgo);

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Get activity data grouped by time period
     */
    public function getActivityByDateRange(\DateTime $startDate, \DateTime $endDate, string $groupBy = 'day'): array
    {
        try {
            $qb = $this->createQueryBuilder('al')
                ->select('COUNT(al.id) as count, al.action')
                ->where('al.createdAt >= :startDate')
                ->andWhere('al.createdAt <= :endDate')
                ->setParameter('startDate', $startDate)
                ->setParameter('endDate', $endDate)
                ->groupBy('al.action')
                ->orderBy('count', 'DESC');

            $results = $qb->getQuery()->getResult();
            
            return array_map(function($row) {
                return [
                    'action' => $row['action'],
                    'count' => (int)$row['count']
                ];
            }, $results);
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get daily activity statistics for a given date range
     */
    public function getDailyActivityData(\DateTime $startDate, \DateTime $endDate): array
    {
        try {
            $qb = $this->createQueryBuilder('al')
                ->select('al.createdAt, COUNT(al.id) as count')
                ->where('al.createdAt >= :startDate')
                ->andWhere('al.createdAt <= :endDate')
                ->setParameter('startDate', $startDate)
                ->setParameter('endDate', $endDate)
                ->groupBy('DATE(al.createdAt)')
                ->orderBy('DATE(al.createdAt)', 'ASC');

            $results = $qb->getQuery()->getResult();
            
            $data = [];
            foreach ($results as $row) {
                $date = $row['createdAt'] instanceof \DateTimeImmutable ? 
                    $row['createdAt']->format('Y-m-d') : 
                    (new \DateTime($row['createdAt']))->format('Y-m-d');
                
                $data[] = [
                    'date' => $date,
                    'count' => (int)$row['count']
                ];
            }
            
            return $data;
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get action distribution for pie chart
     */
    public function getActionDistribution(\DateTime $startDate, \DateTime $endDate): array
    {
        try {
            $qb = $this->createQueryBuilder('al')
                ->select('al.action, COUNT(al.id) as count')
                ->where('al.createdAt >= :startDate')
                ->andWhere('al.createdAt <= :endDate')
                ->setParameter('startDate', $startDate)
                ->setParameter('endDate', $endDate)
                ->groupBy('al.action')
                ->orderBy('count', 'DESC');

            $results = $qb->getQuery()->getResult();
            
            return array_map(function($row) {
                return [
                    'action' => $row['action'],
                    'count' => (int)$row['count']
                ];
            }, $results);
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get activity by user for a date range
     */
    public function getUserActivityData(\DateTime $startDate, \DateTime $endDate): array
    {
        try {
            $qb = $this->createQueryBuilder('al')
                ->select('COALESCE(u.username, \'System\') as username, COUNT(al.id) as count')
                ->leftJoin('al.user', 'u')
                ->where('al.createdAt >= :startDate')
                ->andWhere('al.createdAt <= :endDate')
                ->setParameter('startDate', $startDate)
                ->setParameter('endDate', $endDate)
                ->groupBy('u.id')
                ->orderBy('count', 'DESC')
                ->setMaxResults(10);

            $results = $qb->getQuery()->getResult();
            
            return array_map(function($row) {
                return [
                    'username' => $row['username'] ?? 'System',
                    'count' => (int)$row['count']
                ];
            }, $results);
        } catch (\Exception $e) {
            return [];
        }
    }
}
