<?php

namespace App\Controller\Admin;

use App\Entity\Category;
use App\Entity\Product;
use App\Entity\User;
use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use Symfony\Component\HttpFoundation\RequestStack;
use App\Repository\UserRepository;
use App\Repository\OrderRepository;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;

class DashboardController extends AbstractDashboardController
{
    private ProductRepository $productRepository;
    private CategoryRepository $categoryRepository;
    private UserRepository $userRepository;
    private OrderRepository $orderRepository;
    private RequestStack $requestStack;

    public function __construct(
        ProductRepository $productRepository,
        CategoryRepository $categoryRepository,
        UserRepository $userRepository,
        RequestStack $requestStack,
        OrderRepository $orderRepository
    )
    {
        $this->productRepository = $productRepository;
        $this->categoryRepository = $categoryRepository;
        $this->userRepository = $userRepository;
        $this->orderRepository = $orderRepository;
        $this->requestStack = $requestStack;
    }
    #[Route('/admin', name: 'admin')]
    public function index(): Response
    {
        // metrics
        $productCount = $this->productRepository->count([]);
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
        $request = $this->requestStack->getCurrentRequest();
        $uploadsPath = $request ? $request->server->get('DOCUMENT_ROOT').'/uploads' : null;
        $freeDisk = $uploadsPath && file_exists($uploadsPath) ? round(disk_free_space($uploadsPath) / (1024*1024), 2) : null;

        return $this->render('admin/index.html.twig', [
            'productCount' => $productCount,
            'categoryCount' => $categoryCount,
            'userCount' => $userCount,
            'totalValue' => $totalValue,
            'phpVersion' => $phpVersion,
            'memoryLimit' => $memoryLimit,
            'uploadsPath' => $uploadsPath,
            'freeDisk' => $freeDisk,
            'recentOrders' => $recentOrders,
            'ordersLast7DaysCount' => $ordersLast7DaysCount,
            'ordersLast7DaysValue' => $ordersLast7DaysValue,
            'ordersLast7DaysPercent' => $ordersLast7DaysPercent,
            'revenueLast7DaysPercent' => $revenueLast7DaysPercent,
            'orderLast7DaysData' => $orderLast7DaysData,
        ]);
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Easysave');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');
        yield MenuItem::section('Shop Management');
        yield MenuItem::linkToRoute('Shop Dashboard', 'fa fa-shopping-cart', 'shop_admin');
        // yield MenuItem::linkToCrud('The Label', 'fas fa-list', EntityClass::class);
        yield MenuItem::linkToCrud('Categories', 'fa fa-tags', Category::class);
        yield MenuItem::linkToCrud('Products', 'fa fa-shopping-basket', Product::class);
        yield MenuItem::linkToCrud('Users', 'fa fa-user', User::class)
            ->setPermission('ROLE_SUPER_ADMIN'); // Restrict user management
    }
}
