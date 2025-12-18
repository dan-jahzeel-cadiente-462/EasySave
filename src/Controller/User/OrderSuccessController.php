<?php

namespace App\Controller\User;

use App\Repository\OrderRepository;
use App\Entity\Order;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class OrderSuccessController extends AbstractController
{
    #[Route('/order/success/{orderId}', name: 'app_order_success')]
    public function index(int $orderId, OrderRepository $orderRepository): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $order = $orderRepository->find($orderId);

        if (!$order || $order->getCustomer() !== $this->getUser()) {
            $this->addFlash('warning', 'Order not found.');
            return $this->redirectToRoute('app_user_dashboard');
        }

        return $this->render('user/checkout/order_success/index.html.twig', [
            'order' => $order,
        ]);
    }
}