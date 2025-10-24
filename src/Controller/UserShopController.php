<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\ProductRepository;

final class UserShopController extends AbstractController
{
    #[Route('/user/shop', name: 'user_shop')]
    public function index(ProductRepository $productRepository): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $products = $productRepository->findAll();

        return $this->render('user/shop/index.html.twig', [
            'products' => $products,
        ]);
    }
}
