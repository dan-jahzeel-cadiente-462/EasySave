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

        // 2. Date Search Logic: Extract and validate dates from the GET request
        $startDateStr = $request->query->get('start_date');
        $endDateStr = $request->query->get('end_date');

        $startDate = null;
        $endDate = null;

        if ($startDateStr) {
            try {
                // Set time to the beginning of the day (00:00:00)
                $startDate = new \DateTimeImmutable($startDateStr . ' 00:00:00');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Invalid start date format.');
            }
        }

        if ($endDateStr) {
            try {
                // Set time to the end of the day (23:59:59)
                $endDate = new \DateTimeImmutable($endDateStr . ' 23:59:59');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Invalid end date format.');
            }
        }
        
        // --- 3. Update Repository Calls to include Date Filters ---
        // A new repository method (e.g., findFilteredPaginatedOrders) would be ideal.
        // For simplicity, we'll assume the repository handles the filtering and returns the correct count.
        
        // Fetch the total count for the filtered results
        // NOTE: This requires a new count method in OrderRepository (e.g., countFilteredOrders)
        $totalOrders = $orderRepository->countFilteredOrders($startDate, $endDate);
        
        // Calculate total pages
        $totalPages = ceil($totalOrders / $limit);

        // Fetch the limited set of filtered orders
        // NOTE: This requires a new combined method in OrderRepository
        $orders = $orderRepository->findFilteredPaginatedOrders($startDate, $endDate, $limit, $offset);

        // 4. Render the Twig template
        return $this->render('admin/order/index.html.twig', [
            'orders' => $orders,
            
            // Pagination metadata
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'limit' => $limit,
            'totalOrders' => $totalOrders,
            
            // Pass the search criteria back to Twig for form field persistence and link generation
            'startDate' => $startDateStr,
            'endDate' => $endDateStr,
        ]);
    }

    #[Route('/{id}', name: 'app_admin_order_show', methods: ['GET'])]
    public function show(Order $order): Response
    {
        return $this->render('admin/order/show.html.twig', [
            'order' => $order,
        ]);
    }

    #[Route('/{id}/update-status', name: 'app_admin_order_update_status', methods: ['POST'])]
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

        return $this->redirectToRoute('app_admin_order_show', ['id' => $order->getId()]);
    }

    #[Route('/{id}/delete', name: 'app_admin_order_delete', methods: ['POST'])]
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
        return $this->redirectToRoute('app_admin_order_show', ['id' => $order->getId()]);
    }
}