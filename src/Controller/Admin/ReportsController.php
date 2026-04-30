<?php

namespace App\Controller\Admin;

use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use App\Repository\ActivityLogRepository;
use App\Service\ExportService;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
final class ReportsController extends AbstractController
{
    #[Route('/admin/reports', name: 'app_reports')]
    public function index(OrderRepository $orderRepository, ProductRepository $productRepository, ActivityLogRepository $activityLogRepository): Response
    {
        // Get monthly savings data for the last 12 months
        $monthlySavingsData = $this->getMonthlyOrdersData($orderRepository);
        
        // Get top 5 products by value
        $topProducts = $this->getTopProductsByValue($productRepository, $orderRepository);
        
        // Get action distribution for pie chart
        $endDate = new \DateTime();
        $startDate = (new \DateTime())->modify('-30 days');
        $actionDistribution = $activityLogRepository->getActionDistribution($startDate, $endDate);
        
        // Get daily activity data
        $dailyActivity = $activityLogRepository->getDailyActivityData($startDate, $endDate);
        
        return $this->render('admin/reports/index.html.twig', [
            'monthlySavingsData' => json_encode($monthlySavingsData),
            'topProductsData' => json_encode($topProducts),
            'actionDistribution' => json_encode($actionDistribution),
            'dailyActivityData' => json_encode($dailyActivity),
        ]);
    }
    
    /**
     * Generate monthly orders/revenue data for last 12 months
     */
    private function getMonthlyOrdersData(OrderRepository $orderRepository): array
    {
        $data = [];
        $now = new \DateTime();
        
        for ($i = 11; $i >= 0; $i--) {
            $startDate = new \DateTime('first day of this month');
            $startDate->modify("-" . $i . " months");
            
            $endDate = new \DateTime('last day of this month');
            $endDate->modify("-" . $i . " months");
            $endDate->setTime(23, 59, 59);
            
            // Convert to DateTimeImmutable for repository method
            $start = \DateTimeImmutable::createFromMutable($startDate);
            $end = \DateTimeImmutable::createFromMutable($endDate);
            
            $result = $orderRepository->findOrdersCountAndValueLast7Days();
            $value = $result['value'] ?? 0;
            
            $data[] = [
                'month' => $startDate->format('M'),
                'value' => (float) $value
            ];
        }
        
        return $data;
    }
    
    /**
     * Get top 5 products by total value sold
     */
    private function getTopProductsByValue(ProductRepository $productRepository, OrderRepository $orderRepository): array
    {
        $products = $productRepository->findAll();
        $productValues = [];
        
        foreach ($products as $product) {
            $productValues[] = [
                'name' => $product->getName(),
                'value' => (float) $product->getPrice(),
                'category' => $product->getCategory() ? $product->getCategory()->getName() : 'Uncategorized'
            ];
        }
        
        // Sort by value descending and get top 5
        usort($productValues, function($a, $b) {
            return $b['value'] <=> $a['value'];
        });
        
        return array_slice($productValues, 0, 5);
    }

    #[Route('/export/csv', name: 'app_admin_reports_export_csv', methods: ['GET'])]
    public function exportCSV(OrderRepository $orderRepository, ExportService $exportService): Response
    {
        // Get all orders for export
        $orders = $orderRepository->findAll();
        return $exportService->exportOrdersToCSV($orders);
    }

    #[Route('/export/json', name: 'app_admin_reports_export_json', methods: ['GET'])]
    public function exportJSON(OrderRepository $orderRepository, ExportService $exportService): Response
    {
        // Get all orders for export
        $orders = $orderRepository->findAll();
        return $exportService->exportOrdersToJSON($orders);
    }

    #[Route('/export/excel', name: 'app_admin_reports_export_excel', methods: ['GET'])]
    public function exportExcel(OrderRepository $orderRepository): Response
    {
        $orders = $orderRepository->findAll();
        
        // Create CSV content for Excel
        $csvContent = "Order ID,Customer,Email,Total,Status,Date\n";
        foreach ($orders as $order) {
            $customer = $order->getCustomer();
            $email = $customer ? $customer->getEmail() : '';
            $csvContent .= implode(',', [
                $order->getId(),
                $customer ? $customer->getUsername() : 'Unknown',
                '"' . $email . '"',
                $order->getTotal(),
                $order->getStatus(),
                $order->getCreatedAt()->format('Y-m-d H:i:s')
            ]) . "\n";
        }
        
        return new Response(
            $csvContent,
            Response::HTTP_OK,
            [
                'Content-Type' => 'application/vnd.ms-excel; charset=utf-8',
                'Content-Disposition' => 'attachment; filename="reports_' . date('Y-m-d_His') . '.xlsx"',
            ]
        );
    }
}

