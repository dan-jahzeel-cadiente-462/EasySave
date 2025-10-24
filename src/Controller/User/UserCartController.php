<?php

namespace App\Controller\User;

use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

final class UserCartController extends AbstractController
{
    #[Route('/user/cart', name: 'app_user_cart')]
    public function index(SessionInterface $session, ProductRepository $productRepository): Response
    {
        $cart = $session->get('cart', []);
        $products = $productRepository->findBy(['id' => array_keys($cart)]);

        return $this->render('user/cart/index.html.twig', [
            'items' => $products,
            'cart' => $cart,
        ]);
    }

    #[Route('/user/cart/add/{id}', name: 'app_user_cart_add')]
    public function add($id, SessionInterface $session, ProductRepository $productRepository): Response
    {
        $product = $productRepository->find($id);
        if (!$product) {
            $this->addFlash('warning', 'Product not found');
            return $this->redirectToRoute('app_user_shop');
        }

        $cart = $session->get('cart', []);
        $cart[$id] = ($cart[$id] ?? 0) + 1;
        $session->set('cart', $cart);

        $this->addFlash('success', 'Added to cart');
        return $this->redirectToRoute('app_user_shop');
    }

    #[Route('/user/cart/remove/{id}', name: 'app_user_cart_remove')]
    public function remove($id, SessionInterface $session): Response
    {
        $cart = $session->get('cart', []);
        if (isset($cart[$id])) {
            unset($cart[$id]);
            $session->set('cart', $cart);
        }

        return $this->redirectToRoute('app_user_cart');
    }
}

