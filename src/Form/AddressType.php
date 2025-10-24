<?php

namespace App\Form;

use App\Entity\Address;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AddressType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('houseNo', TextType::class, [
                'required' => false,
                'label' => 'House/Unit/Building No.',
            ])
            ->add('street', TextType::class)
            ->add('subdivision', TextType::class, [
                'required' => false,
            ])
            ->add('barangay', TextType::class)
            ->add('cityMunicipality', TextType::class, [
                'label' => 'City/Municipality',
            ])
            ->add('province', TextType::class, [
                'required' => false,
            ])
            ->add('postalCode', TextType::class)
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Address::class,
        ]);
    }
}