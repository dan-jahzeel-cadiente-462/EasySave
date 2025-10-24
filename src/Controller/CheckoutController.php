<?php

namespace App\Controller;

use App\Entity\Address;
use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\User;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;

class CheckoutController extends AbstractController
{
    #[Route('/checkout', name: 'app_checkout', methods: ['GET'])]
    public function index(SessionInterface $session, ProductRepository $productRepository): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        /** @var User $user */
        $user = $this->getUser();
        $cart = $session->get('cart', []);
        $cartItems = [];
        $total = 0;

        foreach ($cart as $id => $quantity) {
            $product = $productRepository->find($id);
            if ($product) {
                $cartItems[] = [
                    'product' => $product,
                    'quantity' => $quantity,
                ];
                $total += $product->getPrice() * $quantity;
            }
        }
        $primaryAddress = $user->getAddresses()->first() ?: null;

        if (!$primaryAddress && !empty($cartItems)) {
            $this->addFlash('warning', 'Please add a shipping address to your profile before placing an order.');
        }

        return $this->render('checkout/index.html.twig', [
            'items' => $cartItems,
            'total' => $total,
            'primary_address' => $primaryAddress,
            // FIX: Pass all addresses for the template's address selection loop.
            'addresses' => $user->getAddresses(), 
        ]);
    }

    #[Route('/checkout/process', name: 'app_checkout_process', methods: ['POST'])]
    public function process(
        SessionInterface $session,
        ProductRepository $productRepository,
        EntityManagerInterface $entityManager,
        Request $request
    ): Response {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        /** @var User $user */
        $user = $this->getUser();
        $cartData = $session->get('cart', []);
        $cart = [];
        $total = 0;

        foreach ($cartData as $id => $quantity) {
            $product = $productRepository->find($id);
            if ($product) {
                $cart[] = ['product' => $product, 'quantity' => $quantity];
                $total += $product->getPrice() * $quantity;
            }
        }

        if (empty($cart)) {
            $this->addFlash('warning', 'Your cart is empty. You cannot place an order.');
            return $this->redirectToRoute('app_cart');
        }

        // --- Fetch the shipping address ---
        $shippingAddressId = $request->request->get('shipping_address_id');
        /** @var Address|null $shippingAddress */
        $shippingAddress = null;

        if ($shippingAddressId) {
            $shippingAddress = $entityManager->getRepository(Address::class)->find($shippingAddressId);
        } else {
            $shippingAddress = $user->getAddresses()->first() ?: null;
        }

        if (!$shippingAddress) {
             $this->addFlash('danger', 'A valid shipping address is required to place an order.');
             // FIX: Changed the redirect from the non-existent 'app_user_checkout' route to the defined 'app_checkout' route.
             return $this->redirectToRoute('app_checkout');
        }
        // -----------------------------

        $order = new Order();
        $order->setCustomer($user);
        $order->setShippingAddress($shippingAddress);
        $order->setCreatedAt(new \DateTimeImmutable());
        $order->setStatus('Pending');

        foreach ($cart as $cartItem) {
            $product = $cartItem['product'];
            if ($product) {
                $orderItem = new OrderItem();
                $orderItem->setProduct($product);
                $orderItem->setQuantity($cartItem['quantity']);
                $orderItem->setPrice($product->getPrice());
                $order->addOrderItem($orderItem);

                // Decrease stock
                $product->setStock($product->getStock() - $cartItem['quantity']);
            }
        }

        $order->setTotal($total);
        $entityManager->persist($order);
        $entityManager->flush();

        $session->remove('cart'); 

        $this->addFlash('success', 'Your order has been placed successfully!');
        // This redirect is correct and preserved.
        return $this->redirectToRoute('app_order_success', ['orderId' => $order->getId()]);
    }
}