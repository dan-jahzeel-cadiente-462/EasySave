<?php

namespace App\Service;

use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\SecurityBundle\Security;

class DashboardStatsService
{
    public function __construct(
        private CategoryRepository $categoryRepository,
        private ProductRepository $productRepository,
        private UserRepository $userRepository,
        private Security $security
    ) {}

    public function getStats(): array
    {
        $user = $this->security->getUser();
        $isAdmin = $this->security->isGranted('ROLE_ADMIN');

        $productCount = $isAdmin 
            ? $this->productRepository->count([])
            : ($user ? $this->productRepository->count(['createdBy' => $user]) : 0);

        // Optimize: Use a SUM query instead of loading all products into memory
        $totalValue = $this->productRepository->createQueryBuilder('p')
            ->select('SUM(p.price)')
            ->getQuery()
            ->getSingleScalarResult() ?? 0;

        return [
            'categoryCount' => $this->categoryRepository->count([]),
            'userCount' => $this->userRepository->count([]),
            'productCount' => $productCount,
            'totalValue' => (float) $totalValue,
        ];
    }
}
