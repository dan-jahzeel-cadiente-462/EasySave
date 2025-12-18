<?php

namespace App\Controller\Admin;

use App\Entity\Product;
use App\Entity\ProductImage;
use App\Form\ProductType;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/admin/product')]
final class ProductController extends AbstractController
{
    #[Route('/', name: 'app_product_index', methods: ['GET'])]
    public function index(ProductRepository $productRepository): Response
    {
        $request = Request::createFromGlobals();
        $q = $request->query->get('q');
        $category = $request->query->get('category');
        $sort = $request->query->get('sort');
        $view = $request->query->get('view', 'list'); // Default to 'list' if not set

        $products = $productRepository->findWithFilters($q, $category ? (int)$category : null, $sort);

        return $this->render('admin/product/index.html.twig', [
            'products' => $products,
            'q' => $q,
            'selectedCategory' => $category,
            'sort' => $sort,
            'view' => $view,
        ]);
    }

    #[Route('/new', name: 'app_product_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $product = new Product();
        $form = $this->createForm(ProductType::class, $product, ['edit_mode' => false]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $imageFile */
            $imageFile = $form->get('imageFile')->getData();
            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();

                $uploadsDir = $this->getParameter('kernel.project_dir').'/public/uploads/products';
                if (!is_dir($uploadsDir)) {
                    mkdir($uploadsDir, 0755, true);
                }

                $imageFile->move($uploadsDir, $newFilename);
                $product->setImagePath('uploads/products/'.$newFilename);
            }

            $entityManager->persist($product);
            $entityManager->flush();

            return $this->redirectToRoute('app_product_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/product/new.html.twig', [
            'product' => $product,
            'form' => $form,
        ]);
    }

    #[Route('/{product}', name: 'app_product_show', methods: ['GET'])]
    public function show(Product $product): Response
    {
        return $this->render('admin/product/show.html.twig', [
            'product' => $product,
        ]);
    }

    #[Route('/{product}/edit', name: 'app_product_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Product $product, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $form = $this->createForm(ProductType::class, $product, ['edit_mode' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->addFlash('info', 'Form submitted and valid.');

            /** @var UploadedFile|null $imageFile */
            $imageFile = $form->get('imageFile')->getData();

            // Handle main image upload only if a new file was selected
            if ($imageFile instanceof UploadedFile) {
                try {
                    // 1. Delete old main image file if present
                    $oldPath = $product->getImagePath();
                    if ($oldPath) {
                        $oldFull = $this->getParameter('kernel.project_dir').'/public/'.ltrim($oldPath, '/');
                        if (file_exists($oldFull)) {
                            @unlink($oldFull);
                        }
                    }

                    // 2. Upload the new main image file
                    $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                    $safeFilename = $slugger->slug($originalFilename);
                    $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();

                    $uploadsDir = $this->getParameter('kernel.project_dir').'/public/uploads/products';
                    if (!is_dir($uploadsDir)) {
                        mkdir($uploadsDir, 0755, true);
                    }

                    $imageFile->move($uploadsDir, $newFilename);
                    $product->setImagePath('uploads/products/'.$newFilename);
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Failed to upload main image: ' . $e->getMessage());
                }
            }

            // Flush the main product entity changes
            try {
                $entityManager->flush();
                $this->addFlash('success', 'Product updated successfully.');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Failed to save product: ' . $e->getMessage());
                return $this->render('admin/product/edit.html.twig', [
                    'product' => $product,
                    'form' => $form,
                ]);
            }

            // Redirect back to edit page to see changes and/or continue
            return $this->redirectToRoute('app_product_edit', ['product' => $product->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/product/edit.html.twig', [
            'product' => $product,
            'form' => $form,
        ]);
    }
    
    /**
     * Handles the upload of additional product images.
     */
    #[Route('/{product}/add-image', name: 'app_product_image_add', methods: ['POST'])]
    public function addProductImage(Request $request, Product $product, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        // CSRF Token validation for the image addition form
        if (!$this->isCsrfTokenValid('add_image'.$product->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid CSRF token for image upload.');
            return $this->redirectToRoute('app_product_edit', ['product' => $product->getId()]);
        }

        // The input name in edit.html.twig is 'images[]'
        $newImageFiles = $request->files->get('images');
        $imagesAdded = 0;
        $errors = [];

        if (is_array($newImageFiles)) {
            $uploadsDir = $this->getParameter('kernel.project_dir').'/public/uploads/products';
            if (!is_dir($uploadsDir)) {
                mkdir($uploadsDir, 0755, true);
            }

            foreach ($newImageFiles as $newImageFile) {
                if ($newImageFile instanceof UploadedFile) {
                    try {
                        $originalFilename = pathinfo($newImageFile->getClientOriginalName(), PATHINFO_FILENAME);
                        $safeFilename = $slugger->slug($originalFilename);
                        $newFilename = $safeFilename.'-'.uniqid().'.'.$newImageFile->guessExtension();

                        $newImageFile->move($uploadsDir, $newFilename);

                        $productImage = new ProductImage();
                        $productImage->setImagePath('uploads/products/'.$newFilename);
                        $productImage->setProduct($product); // Important: set the relationship!
                        $entityManager->persist($productImage);
                        $imagesAdded++;
                    } catch (\Exception $e) {
                        $errors[] = 'Failed to upload ' . $newImageFile->getClientOriginalName() . ': ' . $e->getMessage();
                    }
                }
            }

            if ($imagesAdded > 0) {
                try {
                    $entityManager->flush();
                    $this->addFlash('success', sprintf('%d new image(s) uploaded successfully.', $imagesAdded));
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Failed to save images: ' . $e->getMessage());
                }
            } else {
                $this->addFlash('warning', 'No valid image files were uploaded.');
            }

            if (!empty($errors)) {
                foreach ($errors as $error) {
                    $this->addFlash('error', $error);
                }
            }
        } else {
             $this->addFlash('warning', 'No files were selected for upload.');
        }

        return $this->redirectToRoute('app_product_edit', ['product' => $product->getId()]);
    }

    /**
     * Handles the deletion of a specific product image.
     */
    #[Route('/{product}/image/{imageId}/delete', name: 'app_product_image_delete', methods: ['POST'])]
    public function deleteImage(Request $request, Product $product, int $imageId, EntityManagerInterface $entityManager): Response
    {
        // Token is passed as POST data, use request->request->get
        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('delete'.$imageId, $token)) {
            $this->addFlash('danger', 'Invalid CSRF token.');
            return $this->redirectToRoute('app_product_edit', ['product' => $product->getId()]);
        }

        // Find the ProductImage entity by ID
        $image = $entityManager->getRepository(ProductImage::class)->find($imageId);
        
        // Ensure the image exists and belongs to the correct product
        if ($image && $image->getProduct() === $product) {
            // Delete the file from the server
            $filePath = $this->getParameter('kernel.project_dir').'/public/'.$image->getImagePath();
            if (file_exists($filePath)) {
                @unlink($filePath);
            }
            
            // Remove the entity from the database
            $entityManager->remove($image);
            $entityManager->flush();
            $this->addFlash('success', 'Image deleted successfully.');
        } else {
            $this->addFlash('danger', 'Image not found or access denied.');
        }

        return $this->redirectToRoute('app_product_edit', ['product' => $product->getId()]);
    }

    #[Route('/{product}', name: 'app_product_delete', methods: ['POST'])]
    public function delete(Request $request, Product $product, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$product->getId(), $request->getPayload()->getString('_token'))) {
            // Delete associated ProductImage entities and files 
            foreach ($product->getProductImages() as $image) {
                $filePath = $this->getParameter('kernel.project_dir').'/public/'.$image->getImagePath();
                if (file_exists($filePath)) {
                    @unlink($filePath);
                }
                $entityManager->remove($image);
            }

            // Delete main image file
            $mainImagePath = $product->getImagePath();
            if ($mainImagePath) {
                $mainFilePath = $this->getParameter('kernel.project_dir').'/public/'.$mainImagePath;
                if (file_exists($mainFilePath)) {
                    @unlink($mainFilePath);
                }
            }

            $entityManager->remove($product);
            $entityManager->flush();
            $this->addFlash('success', 'Product deleted successfully.');
        } else {
            $this->addFlash('error', 'Invalid CSRF token for deletion.');
        }

        return $this->redirectToRoute('app_product_index', [], Response::HTTP_SEE_OTHER);
    }

    /**
     * Increases product stock by 1.
     */
    #[Route('/{product}/increase-stock', name: 'app_product_increase_stock', methods: ['POST'])]
    public function increaseStock(Request $request, Product $product, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('stock_increase'.$product->getId(), $request->getPayload()->getString('_token'))) {
            $product->increaseStock(1); // Increase by 1
            $entityManager->flush();
            $this->addFlash('success', 'Stock for "'.$product->getName().'" increased successfully to '.$product->getStock().'.');
        } else {
            $this->addFlash('error', 'Invalid CSRF token for stock increase.');
        }

        return $this->redirectToRoute('app_product_index', [], Response::HTTP_SEE_OTHER);
    }

    /**
     * Decreases product stock by 1, if stock is above 0.
     */
    #[Route('/{product}/decrease-stock', name: 'app_product_decrease_stock', methods: ['POST'])]
    public function decreaseStock(Request $request, Product $product, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('stock_decrease'.$product->getId(), $request->getPayload()->getString('_token'))) {
            if ($product->getStock() > 0) {
                $product->decreaseStock(1); // Decrease by 1
                $entityManager->flush();
                $this->addFlash('success', 'Stock for "'.$product->getName().'" decreased successfully to '.$product->getStock().'.');
            } else {
                $this->addFlash('error', 'Cannot decrease stock for "'.$product->getName().'". Stock is already 0.');
            }
        } else {
            $this->addFlash('error', 'Invalid CSRF token for stock decrease.');
        }

        return $this->redirectToRoute('app_product_index', [], Response::HTTP_SEE_OTHER);
    }

    /**
     * Adjust product stock by amounts provided from the modal.
     */
    #[Route('/{product}/adjust-stock', name: 'app_product_adjust_stock', methods: ['POST'])]
    public function adjustStock(Request $request, Product $product, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isCsrfTokenValid('adjust_stock'.$product->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid CSRF token for stock adjustment.');
            return $this->redirectToRoute('app_product_index');
        }

        $add = (int) $request->request->get('add_quantity', 0);
        $remove = (int) $request->request->get('remove_quantity', 0);

        if ($add < 0 || $remove < 0) {
            $this->addFlash('error', 'Quantities must be non-negative.');
            return $this->redirectToRoute('app_product_index');
        }

        // compute net change
        $net = $add - $remove;

        if ($net > 0) {
            $product->increaseStock($net);
            $message = sprintf('Increased stock for "%s" by %d to %d.', $product->getName(), $net, $product->getStock());
        } elseif ($net < 0) {
            $dec = abs($net);
            if ($product->getStock() < $dec) {
                $this->addFlash('error', 'Cannot remove more items than available.');
                return $this->redirectToRoute('app_product_index');
            }
            $product->decreaseStock($dec);
            $message = sprintf('Decreased stock for "%s" by %d to %d.', $product->getName(), $dec, $product->getStock());
        } else {
            $this->addFlash('info', 'No stock change applied.');
            return $this->redirectToRoute('app_product_index');
        }

        $entityManager->flush();
        $this->addFlash('success', $message);
        return $this->redirectToRoute('app_product_index');
    }
}
