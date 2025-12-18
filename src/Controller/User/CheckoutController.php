<?php

namespace App\Controller\User;

use App\Entity\User;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;

final class CheckoutController extends AbstractController
{
    #[Route('/user/checkout', name: 'app_user_checkout')]
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

        return $this->render('user/checkout/index.html.twig', [
            'items' => $cartItems,
            'total' => $total,
            'addresses' => $user->getAddresses(),
        ]);
    }

    
}
