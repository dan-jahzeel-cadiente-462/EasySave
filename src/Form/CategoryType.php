<?php

namespace App\Form;

use App\Entity\Category;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CategoryType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class)
            ->add('description', TextareaType::class, [
                'required' => false,
            ])
            ->add('parent', EntityType::class, [
                'class' => Category::class,
                'choice_label' => 'name',
                'placeholder' => 'Choose a parent category (optional)',
                'required' => false,
                // This prevents a category from being its own parent and creating circular references
                'query_builder' => function ($er) use ($options) {
                    $qb = $er->createQueryBuilder('c');
                    if ($options['data'] && $options['data']->getId()) {
                        // Get the repository to exclude descendants
                        /** @var CategoryRepository $repo */
                        $repo = $er->getEntityManager()->getRepository(Category::class);
                        $descendantIds = $repo->getDescendantIds($options['data']);
                        $qb->andWhere('c.id NOT IN (:descendants)')
                           ->setParameter('descendants', $descendantIds);
                    }
                    return $qb->orderBy('c.name', 'ASC');
                },
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Category::class,
        ]);
    }
}