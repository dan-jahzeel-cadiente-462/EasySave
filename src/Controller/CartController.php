<?php

namespace App\Controller;

use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;

final class CartController extends AbstractController
{
    #[Route('/cart', name: 'app_cart')]
    public function index(SessionInterface $session, ProductRepository $productRepository): Response
    {
        $cart = $session->get('cart', []);
        $products = $productRepository->findBy(['id' => array_keys($cart)]);

        return $this->render('cart/index.html.twig', [
            'items' => $products,
            'cart' => $cart,
        ]);
    }

    #[Route('/cart/add/{id}', name: 'app_cart_add')]
    public function add($id, SessionInterface $session, ProductRepository $productRepository): Response
    {
        // Allow adding to cart without authentication; checkout will require login
        $product = $productRepository->find($id);
        if (!$product) {
            $this->addFlash('warning', 'Product not found');
            return $this->redirectToRoute('app_services');
        }

        $cart = $session->get('cart', []);
        $cart[$id] = ($cart[$id] ?? 0) + 1;
        $session->set('cart', $cart);

        $this->addFlash('success', 'Added to cart');
        return $this->redirectToRoute('app_services');
    }

    #[Route('/cart/remove/{id}', name: 'app_cart_remove')]
    public function remove($id, SessionInterface $session): Response
    {
        $cart = $session->get('cart', []);
        if (isset($cart[$id])) {
            unset($cart[$id]);
            $session->set('cart', $cart);
        }

        return $this->redirectToRoute('app_cart');
    }
}
