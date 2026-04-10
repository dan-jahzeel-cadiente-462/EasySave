<?php

namespace App\Form;

use App\Entity\Category;
use App\Entity\Discount;
use App\Entity\Product;
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
                'choice_label' => 'name',
                'group_by' => 'parent.name',
                'placeholder' => 'Choose a category',
                'required' => true,
            ])
            ->add('discounts', EntityType::class, [
                'class' => Discount::class,
                'choice_label' => 'label',
                'multiple' => true,
                'expanded' => false,
                'required' => false,
                'attr' => [
                    'class' => 'select2',
                    'help_text' => 'Optional: Select active discounts to apply to this product',
                ],
            ])
            ->add('isActive', CheckboxType::class, [
                'required' => false,
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
