<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\ProductRepository;

final class UserCartController extends AbstractController
{
    #[Route('/user/cart', name: 'user_cart')]
    public function index(Request $request, ProductRepository $productRepository): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $cart = $request->getSession()->get('cart', []);
        $products = $productRepository->findBy(['id' => array_keys($cart)]);

        return $this->render('user/cart/index.html.twig', [
            'items' => $products,
            'cart' => $cart,
        ]);
    }
}
