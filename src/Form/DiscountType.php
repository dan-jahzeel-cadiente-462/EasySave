<?php

namespace App\Form;

use App\Entity\Discount;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DiscountType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('label', TextType::class, [
                'required' => true,
            ])
            ->add('type', ChoiceType::class, [
                'choices' => [
                    'Percentage (%)' => 'percentage',
                    'Fixed Amount (PHP)' => 'fixed_amount',
                ],
                'required' => true,
                'expanded' => false,
                'placeholder' => 'Select discount type',
            ])
            ->add('value', NumberType::class, [
                'required' => true,
                'scale' => 2,
                'attr' => [
                    'min' => 0,
                    'placeholder' => 'Enter amount or percentage',
                ],
            ])
            ->add('is_active', CheckboxType::class, [
                'required' => false,
                'label' => 'Active',
            ])
            ->add('starts_at', null, [
                'widget' => 'single_text',
                'required' => false,
                'label' => 'Start Date',
            ])
            ->add('ends_at', null, [
                'widget' => 'single_text',
                'required' => false,
                'label' => 'End Date',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Discount::class,
        ]);
    }
}
