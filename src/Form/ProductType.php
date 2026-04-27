<?php

namespace App\Form;

use App\Entity\Category;
use App\Entity\Discount;
use App\Entity\Product;
use App\Repository\CategoryRepository;
use App\Repository\DiscountRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProductType extends AbstractType
{
    private CategoryRepository $categoryRepository;
    private DiscountRepository $discountRepository;

    public function __construct(CategoryRepository $categoryRepository, DiscountRepository $discountRepository)
    {
        $this->categoryRepository = $categoryRepository;
        $this->discountRepository = $discountRepository;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'required' => true,
            ])
            ->add('brand', TextType::class, [
                'required' => false,
                'empty_data' => 'Unknown',
            ])
            ->add('imageFile', FileType::class, [
                'label' => 'Upload image (required for new products)',
                'mapped' => false,
                'required' => !$options['edit_mode'],
                'help' => $options['edit_mode'] ? 'Leave empty to keep the current image' : 'Select an image file',
            ])
            ->add('description', TextareaType::class, [
                'required' => false,
            ])
            ->add('price', NumberType::class, [
                'required' => true,
            ])
            ->add('stock', IntegerType::class, [
                'required' => true,
            ])
            ->add('category', EntityType::class, [
                'class' => Category::class,
                'choice_label' => function(Category $category) {
                    $path = $category->getHierarchyPath();
                    return $path ? $path . ' (' . $category->getName() . ')' : $category->getName();
                },
                'placeholder' => 'Choose a category...',
                'required' => true,
                'attr' => [
                    'class' => 'form-control-select',
                    'data-filter' => 'true',
                    'aria-label' => 'Product Category',
                ],
            ])
            ->add('discounts', EntityType::class, [
                'class' => Discount::class,
                'choice_label' => function(Discount $discount) {
                    $label = $discount->getLabel();
                    $type = $discount->getType() === 'percentage' ? $discount->getValue() . '%' : 'PHP ' . $discount->getValue();
                    return $label . ' (' . $type . ')';
                },
                'query_builder' => function(DiscountRepository $repository) {
                    return $repository->createQueryBuilder('d')
                        ->where('d.is_active = :active')
                        ->setParameter('active', true)
                        ->orderBy('d.label', 'ASC');
                },
                'multiple' => true,
                'expanded' => false,
                'required' => false,
                'attr' => [
                    'class' => 'form-control-select',
                    'aria-label' => 'Product Discounts',
                ],
                'help' => 'Select active discounts to apply to this product. Customers will see these discounts on the catalog and product pages.',
            ])
            ->add('isActive', CheckboxType::class, [
                'required' => false,
                'attr' => [
                    'class' => 'form-check-input',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Product::class,
            'edit_mode' => false,
        ]);
    }
}


