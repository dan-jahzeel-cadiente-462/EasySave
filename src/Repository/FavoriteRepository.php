<?php

namespace App\Repository;

use App\Entity\Favorite;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Favorite>
 */
class FavoriteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Favorite::class);
    }

    /**
     * @return int[] Returns an array of Product IDs favorited by the user.
     */
    public function findFavoriteProductIdsByUser(int $userId): array
    {
        return $this->createQueryBuilder('f')
            // Selects only the ID of the related Product entity
            ->select('IDENTITY(f.product)') 
            ->andWhere('f.user = :userId')
            ->setParameter('userId', $userId)
            ->getQuery()
            // Returns an array of scalars (integers), which is fast and efficient
            ->getSingleColumnResult(); 
    }

    /**
     * @return ?Favorite Returns the Favorite entity or null if not found.
     */
    public function findOneByProductAndUser(int $productId, int $userId): ?Favorite
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.product = :productId')
            ->andWhere('f.user = :userId')
            ->setParameter('productId', $productId)
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getOneOrNullResult();
    }
}