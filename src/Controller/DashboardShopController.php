<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DashboardShopController extends AbstractController
{
    #[Route('/dashboard/shop', name: 'app_dashboard_shop')]
    public function index(): Response
    {
        return $this->render('dashboard_shop/index.html.twig', [
            'controller_name' => 'DashboardShopController',
        ]);
    }
}
