<?php

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ReportsController extends AbstractController
{
    #[Route('/admin/reports', name: 'app_reports')]
    public function index(): Response
    {
        return $this->render('admin/reports/index.html.twig', [
            'controller_name' => 'ReportsController',
        ]);
    }
}
