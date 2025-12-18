<?php

namespace App\Controller\Admin;

use App\Entity\Order;
use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/order')]
final class OrderController extends AbstractController
{
    #[Route('/', name: 'app_admin_order_index', methods: ['GET'])]
    public function index(Request $request, OrderRepository $orderRepository): Response
    {
        // 1. Pagination & Limit Configuration
        $limit = 10; // Items per page
        $page = $request->query->getInt('page', 1);
        $page = max(1, $page); // Ensure page is at least 1
        $offset = ($page - 1) * $limit;

        // 2. Extract filter parameters from GET request
        $startDateStr = $request->query->get('start_date');
        $endDateStr = $request->query->get('end_date');
        $filterStatus = $request->query->get('status', '');

        $startDate = null;
        $endDate = null;

        if ($startDateStr) {
            try {
                $startDate = new \DateTimeImmutable($startDateStr . ' 00:00:00');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Invalid start date format.');
            }
        }

        if ($endDateStr) {
            try {
                $endDate = new \DateTimeImmutable($endDateStr . ' 23:59:59');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Invalid end date format.');
            }
        }
        
        // 3. Fetch filtered and paginated orders
        $totalOrders = $orderRepository->countFilteredOrders($startDate, $endDate, $filterStatus);
        $totalPages = ceil($totalOrders / $limit);
        $orders = $orderRepository->findFilteredPaginatedOrders($startDate, $endDate, $filterStatus, $limit, $offset);

        // 4. Render the Twig template
        return $this->render('admin/order/index.html.twig', [
            'orders' => $orders,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'limit' => $limit,
            'totalOrders' => $totalOrders,
            'startDate' => $startDateStr,
            'endDate' => $endDateStr,
            'filterStatus' => $filterStatus,
        ]);
    }

    #[Route('/{order}', name: 'app_admin_order_show', methods: ['GET'])]
    public function show(Order $order): Response
    {
        return $this->render('admin/order/show.html.twig', [
            'order' => $order,
        ]);
    }

    #[Route('/{order}/update-status', name: 'app_admin_order_update_status', methods: ['POST'])]
    public function updateStatus(Request $request, Order $order, EntityManagerInterface $entityManager): Response
    {
        $status = $request->request->get('status');
        $token = $request->request->get('_token');

        if ($this->isCsrfTokenValid('update-status'.$order->getId(), $token) && in_array($status, ['Pending', 'Shipped', 'Delivered', 'Cancelled'])) {
            $order->setStatus($status);
            $order->setUpdatedAt(new \DateTimeImmutable());
            $entityManager->flush();

            $this->addFlash('success', 'Order status updated successfully.');
        } else {
            $this->addFlash('danger', 'Invalid request or status.');
        }

        return $this->redirectToRoute('app_admin_order_show', ['order' => $order->getId()]);
    }

    #[Route('/{order}/delete', name: 'app_admin_order_delete', methods: ['POST'])]
    public function delete(Request $request, Order $order, EntityManagerInterface $entityManager): Response
    {
        $token = $request->request->get('_token');

        if ($this->isCsrfTokenValid('delete'.$order->getId(), $token)) {
            $entityManager->remove($order);
            $entityManager->flush();

            $this->addFlash('success', 'Order #' . $order->getId() . ' has been deleted.');
            return $this->redirectToRoute('app_admin_order_index');
        }

        $this->addFlash('danger', 'Invalid CSRF token.');
        return $this->redirectToRoute('app_admin_order_show', ['order' => $order->getId()]);
    }
}
