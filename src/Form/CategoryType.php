<?php

namespace App\Form;

use App\Entity\Category;
use App\Repository\CategoryRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;

class CategoryType extends AbstractType
{
    private CategoryRepository $categoryRepository;

    public function __construct(CategoryRepository $categoryRepository)
    {
        $this->categoryRepository = $categoryRepository;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Category Name',
                'attr' => [
                    'placeholder' => 'Enter category name',
                    'class' => 'form-control',
                    'maxlength' => 255
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Category name cannot be blank']),
                    new Length(max: 255, maxMessage: 'Category name cannot exceed {{ limit }} characters')
                ]
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Enter category description (optional)',
                    'class' => 'form-control',
                    'rows' => 5
                ]
            ])
            ->add('parent', EntityType::class, [
                'class' => Category::class,
                'choice_label' => 'name',
                'choice_value' => 'id',
                'placeholder' => '-- No parent (top-level category) --',
                'required' => false,
                'label' => 'Parent Category',
                'attr' => [
                    'class' => 'form-control',
                    'data-live-search' => 'true'
                ],
                // Use a closure for query builder to access the options
                'query_builder' => function (CategoryRepository $er) use ($options) {
                    $qb = $er->createQueryBuilder('c')
                        ->orderBy('c.name', 'ASC');

                    // If editing an existing category, exclude itself and its descendants
                    if (isset($options['current_category']) && $options['current_category'] instanceof Category) {
                        $currentCategory = $options['current_category'];

                        // Only exclude if it has an ID (persisted entity)
                        if ($currentCategory->getId()) {
                            $descendantIds = $this->categoryRepository->getDescendantIds($currentCategory);

                            // Add current category ID to excluded list
                            $excludedIds = array_merge([$currentCategory->getId()], $descendantIds);

                            $qb->andWhere('c.id NOT IN (:excludedIds)')
                                ->setParameter('excludedIds', $excludedIds);
                        }
                    }

                    return $qb;
                },
                // Group categories by their hierarchy for better UX
                'group_by' => function ($category, $key, $value) {
                    if ($category->getParent()) {
                        $parent = $category->getParent();
                        return $parent->getHierarchyPath(' > ');
                    }
                    return 'Top Level Categories';
                },
                // Custom choice label to show hierarchy
                'choice_attr' => function ($category, $key, $value) {
                    // Add data attributes for the path if needed
                    return [
                        'data-path' => $category->getHierarchyPath(' > ')
                    ];
                },
                // Help text for the field
                'help' => 'Select a parent category. A category cannot be its own parent or descendant.',
            ]);
    }

        public function configureOptions(OptionsResolver $resolver): void

        {

            $resolver->setDefaults([

                'data_class' => Category::class,

                'current_category' => null,

                'attr' => [

                    'novalidate' => 'novalidate', // Disable browser validation

                    'class' => 'category-form'

                ]

            ]);

    

            // Add validation for current_category option

            $resolver->setAllowedTypes('current_category', ['null', 'object']);

        }

    

    }

    