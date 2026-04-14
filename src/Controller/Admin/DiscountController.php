<?php

namespace App\Controller\Admin;

use App\Entity\Discount;
use App\Form\DiscountType;
use App\Repository\DiscountRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/discount')]
final class DiscountController extends AbstractController
{
    #[Route(name: 'app_discount_index', methods: ['GET'])]
    public function index(DiscountRepository $discountRepository): Response
    {
        return $this->render('admin/discount/index.html.twig', [
            'discounts' => $discountRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_discount_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $discount = new Discount();
        $form = $this->createForm(DiscountType::class, $discount);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $entityManager->persist($discount);
                $entityManager->flush();
                $this->addFlash('success', 'Discount created successfully!');
                return $this->redirectToRoute('app_discount_index', [], Response::HTTP_SEE_OTHER);
            } catch (\Exception $e) {
                $this->addFlash('error', 'Failed to create discount: ' . $e->getMessage());
            }
        }

        return $this->render('admin/discount/new.html.twig', [
            'discount' => $discount,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_discount_show', methods: ['GET'])]
    public function show(Discount $discount): Response
    {
        return $this->render('admin/discount/show.html.twig', [
            'discount' => $discount,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_discount_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Discount $discount, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(DiscountType::class, $discount);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $entityManager->flush();
                $this->addFlash('success', 'Discount updated successfully!');
                return $this->redirectToRoute('app_discount_index', [], Response::HTTP_SEE_OTHER);
            } catch (\Exception $e) {
                $this->addFlash('error', 'Failed to update discount: ' . $e->getMessage());
            }
        }

        return $this->render('admin/discount/edit.html.twig', [
            'discount' => $discount,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_discount_delete', methods: ['POST'])]
    public function delete(Request $request, Discount $discount, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$discount->getId(), $request->getPayload()->getString('_token'))) {
            try {
                $entityManager->remove($discount);
                $entityManager->flush();
                $this->addFlash('success', 'Discount deleted successfully!');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Failed to delete discount: ' . $e->getMessage());
            }
        } else {
            $this->addFlash('danger', 'Invalid CSRF token.');
        }

        return $this->redirectToRoute('app_discount_index', [], Response::HTTP_SEE_OTHER);
    }
}
