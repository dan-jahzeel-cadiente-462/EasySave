<?php

namespace App\Controller\Admin;

use App\Entity\ActivityLog;
use App\Entity\Category;
use App\Entity\Product;
use App\Entity\User;
use App\Repository\ActivityLogRepository;
use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use Symfony\Component\HttpFoundation\RequestStack;
use App\Repository\UserRepository;
use App\Repository\OrderRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DashboardController extends AbstractController
{
    private ProductRepository $productRepository;
    private CategoryRepository $categoryRepository;
    private UserRepository $userRepository;
    private OrderRepository $orderRepository;
    private ActivityLogRepository $activityLogRepository;
    private RequestStack $requestStack;

    public function __construct(
        ProductRepository $productRepository,
        CategoryRepository $categoryRepository,
        UserRepository $userRepository,
        RequestStack $requestStack,
        OrderRepository $orderRepository,
        ActivityLogRepository $activityLogRepository
    )
    {
        $this->productRepository = $productRepository;
        $this->categoryRepository = $categoryRepository;
        $this->userRepository = $userRepository;
        $this->orderRepository = $orderRepository;
        $this->activityLogRepository  = $activityLogRepository;
        $this->requestStack = $requestStack;
    }

    #[Route('/admin/dashboard', name: 'app_admin_dashboard')]
    public function index(): Response
    {
        // metrics
        if ($this->isGranted('ROLE_ADMIN')) {
            // Admins see all products
            $productCount = $this->productRepository->count([]);
        } else {
            // Staff see only their own products
            $productCount = $this->productRepository->count(['createdBy' => $this->getUser()]);
        }

        $categoryCount = $this->categoryRepository->count([]);
        $userCount = $this->userRepository->count([]);
        $qb = $this->productRepository->createQueryBuilder('p')
            ->select('SUM(p.price) as total');
        $totalValue = $qb->getQuery()->getSingleScalarResult() ?? 0;

        $limit = 10;

        $recentOrders = $this->orderRepository->findRecentOrders(5);
        $orderLast7DaysData = $this->orderRepository->findOrdersCountAndValueLast7Days();
        $ordersLast7DaysCount = $orderLast7DaysData['count'] ?? 0;
        $ordersLast7DaysValue = $orderLast7DaysData['value'] ?? 0;

        $totalOrdersCount = $this->orderRepository->countAll();
        $totalRevenue = $this->orderRepository->getTotalRevenue();

        $ordersLast7DaysPercent = ($totalOrdersCount > 0) ? ($ordersLast7DaysCount / $totalOrdersCount) * 100 : 0;
        $revenueLast7DaysPercent = ($totalRevenue > 0) ? ($ordersLast7DaysValue / $totalRevenue) * 100 : 0;



        // server info
        $phpVersion = phpversion();
        $memoryLimit = ini_get('memory_limit');
        
        // Removed disk_free_space check as it can be very slow on cloud/container filesystems
        $freeDisk = null;

        $recentActivityLogs = $this->activityLogRepository->findBy([], ['createdAt' => 'DESC'], 5);
        
        return $this->render('admin/dashboard/index.html.twig', [
            'productCount' => $productCount,
            'categoryCount' => $categoryCount,
            'userCount' => $userCount,
            'totalValue' => $totalValue,
            'phpVersion' => $phpVersion,
            'memoryLimit' => $memoryLimit,
            'freeDisk' => $freeDisk,
            'recentOrders' => $recentOrders,
            'ordersLast7DaysCount' => $ordersLast7DaysCount,
            'ordersLast7DaysValue' => $ordersLast7DaysValue,
            'ordersLast7DaysPercent' => $ordersLast7DaysPercent,
            'revenueLast7DaysPercent' => $revenueLast7DaysPercent,
            'orderLast7DaysData' => $orderLast7DaysData,
            'recentActivityLogs' => $recentActivityLogs,
        ]);
    }
}
