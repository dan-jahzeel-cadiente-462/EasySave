<?php

namespace App\Controller\Admin;

use App\Repository\ActivityLogRepository;
use App\Repository\OrderRepository;
use App\Service\DashboardStatsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/activity-logs')]
#[IsGranted('ROLE_ADMIN')]
final class ActivityLogController extends AbstractController
{
    #[Route('/dashboard', name: 'app_admin_activity_log_dashboard_index', methods: ['GET'])]
    public function dashboardIndex(ActivityLogRepository $activityLogRepository, OrderRepository $orderRepository, DashboardStatsService $statsService): Response
    {
        $recentLogs = $activityLogRepository->getRecentLogs(5);
        $actionsCount = $activityLogRepository->countActionsLast7Days();
        $recentOrders = $orderRepository->findBy([], ['createdAt' => 'DESC'], 5);
        $stats = $statsService->getStats();

        return $this->render('admin/dashboard/index.html.twig', [
            'recentActivityLogs' => $recentLogs,
            'recentOrders' => $recentOrders,
            'actionsCount' => $actionsCount,
            ...$stats,
        ]);
    }

    #[Route('', name: 'app_admin_activity_logs')]
    public function index(ActivityLogRepository $activityLogRepository, Request $request): Response
    {
        $page = $request->query->getInt('page', 1);
        $limit = 10;
        $action = $request->query->get('action');
        $user = $request->query->get('user');
        $dateFrom = $request->query->get('date_from');
        $dateTo = $request->query->get('date_to');
        $offset = ($page - 1) * $limit;

        // Get filtered logs with pagination
        $logs = $activityLogRepository->findByFilters(
            $user,
            $action,
            $dateFrom ? \DateTime::createFromFormat('Y-m-d', $dateFrom) : null,
            $dateTo ? \DateTime::createFromFormat('Y-m-d', $dateTo) : null,
            $limit,
            $offset
        );
        
        // Get total count for pagination
        $totalLogs = $activityLogRepository->countByFilters(
            $user,
            $action,
            $dateFrom ? \DateTime::createFromFormat('Y-m-d', $dateFrom) : null,
            $dateTo ? \DateTime::createFromFormat('Y-m-d', $dateTo) : null
        );
        
        $totalPages = ceil($totalLogs / $limit);

        return $this->render('admin/activity_logs/index.html.twig', [
            'logs' => $logs,
            'current_page' => $page,
            'total_pages' => $totalPages,
            'total_logs' => $totalLogs,
            'filter_action' => $action,
            'filter_user' => $user,
            'filter_date_from' => $dateFrom,
            'filter_date_to' => $dateTo,
        ]);
    }

    #[Route('/analytics', name: 'app_admin_activity_logs_analytics', methods: ['GET'])]
    public function analytics(Request $request, ActivityLogRepository $activityLogRepository): Response
    {
        // Get date range from request or use defaults
        $range = $request->query->get('range', 'week'); // day, week, month, all
        
        $endDate = new \DateTime();
        $startDate = new \DateTime();
        
        switch ($range) {
            case 'day':
                $startDate->modify('-1 day');
                break;
            case 'month':
                $startDate->modify('-1 month');
                break;
            case 'all':
                $startDate = (new \DateTime('1970-01-01'));
                break;
            case 'week':
            default:
                $startDate->modify('-7 days');
                break;
        }
        
        // Get various data for charts (already as arrays, not JSON)
        $actionDistribution = $activityLogRepository->getActionDistribution($startDate, $endDate);
        $dailyActivity = $activityLogRepository->getDailyActivityData($startDate, $endDate);
        $userActivity = $activityLogRepository->getUserActivityData($startDate, $endDate);
        
        // Get recent activities for the selected range
        $recentActivities = $activityLogRepository->findByFilters(
            null,
            null,
            $startDate,
            $endDate,
            50
        );
        
        return $this->render('admin/activity_logs/analytics.html.twig', [
            'range' => $range,
            'actionDistribution' => json_encode($actionDistribution),
            'dailyActivityData' => json_encode($dailyActivity),
            'userActivityData' => json_encode($userActivity),
            'actionDistributionArray' => $actionDistribution,
            'dailyActivityArray' => $dailyActivity,
            'userActivityArray' => $userActivity,
            'recentActivities' => $recentActivities,
            'startDate' => $startDate->format('Y-m-d'),
            'endDate' => $endDate->format('Y-m-d'),
        ]);
    }

    #[Route('/export/csv', name: 'app_admin_activity_logs_export_csv', methods: ['GET'])]
    public function exportCSV(Request $request, ActivityLogRepository $activityLogRepository, \App\Service\ExportService $exportService): Response
    {
        // Extract filter parameters
        $user = $request->query->get('user');
        $action = $request->query->get('action');
        $dateFrom = $request->query->get('date_from');
        $dateTo = $request->query->get('date_to');

        // Get all filtered logs (no pagination limit)
        $logs = $activityLogRepository->findByFilters(
            $user,
            $action,
            $dateFrom ? \DateTime::createFromFormat('Y-m-d', $dateFrom) : null,
            $dateTo ? \DateTime::createFromFormat('Y-m-d', $dateTo) : null,
            10000,
            0
        );

        return $exportService->exportActivityLogsToCSV($logs);
    }

    #[Route('/export/json', name: 'app_admin_activity_logs_export_json', methods: ['GET'])]
    public function exportJSON(Request $request, ActivityLogRepository $activityLogRepository, \App\Service\ExportService $exportService): Response
    {
        // Extract filter parameters
        $user = $request->query->get('user');
        $action = $request->query->get('action');
        $dateFrom = $request->query->get('date_from');
        $dateTo = $request->query->get('date_to');

        // Get all filtered logs (no pagination limit)
        $logs = $activityLogRepository->findByFilters(
            $user,
            $action,
            $dateFrom ? \DateTime::createFromFormat('Y-m-d', $dateFrom) : null,
            $dateTo ? \DateTime::createFromFormat('Y-m-d', $dateTo) : null,
            10000,
            0
        );

        return $exportService->exportActivityLogsToJSON($logs);
    }

    #[Route('/{id}', name: 'app_admin_activity_logs_show', requirements: ['id' => '\d+'])]
    public function show(int $id, ActivityLogRepository $activityLogRepository): Response
    {
        $log = $activityLogRepository->find($id);

        if (!$log) {
            throw $this->createNotFoundException('Activity log not found');
        }

        return $this->render('admin/activity_logs/show.html.twig', [
            'log' => $log,
        ]);
    }
}
