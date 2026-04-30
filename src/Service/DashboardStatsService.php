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

        // Calculate total inventory value (sum of all product prices)
        $totalValue = 0;
        $products = $this->productRepository->findAll();
        foreach ($products as $product) {
            $totalValue += (float) $product->getPrice();
        }

        return [
            'categoryCount' => $this->categoryRepository->count([]),
            'userCount' => $this->userRepository->count([]),
            'productCount' => $productCount,
            'totalValue' => $totalValue,
        ];
    }
}
