<?php

namespace App\Controller\Admin;

use App\Repository\ActivityLogRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/activity-logs')]
#[IsGranted('ROLE_ADMIN')]
final class ActivityLogController extends AbstractController
{
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

    #[Route('/{id}', name: 'app_admin_activity_logs_show')]
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

    #[Route('', name: 'app_admin_activity_log_dashboard_index', methods: ['GET'])]
    public function dashboardIndex(ActivityLogRepository $activityLogRepository): Response
    {
        $recentLogs = $activityLogRepository->getRecentLogs(5);
        $actionsCount = $activityLogRepository->countActionsLast7Days();

        return $this->render('admin/dashboard/index.html.twig', [
            'recentActivityLogs' => $recentLogs,
            'actionsCount' => $actionsCount,
        ]);
    }
}
