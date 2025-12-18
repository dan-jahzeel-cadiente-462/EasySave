<?php

namespace App\DataFixtures;

use App\Entity\Product;
use App\Entity\Category;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class ProductFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        // Get a category from CategoryFixtures
        $category = $manager->getRepository(Category::class)->findOneBy([]);
        if (!$category) {
            return; // Skip if no category exists
        }

        // Create sample products
        $product1 = new Product();
        $product1->setName('Sample Product 1');
        $product1->setBrand('Test Brand');
        $product1->setDescription('This is a sample product for testing.');
        $product1->setPrice(29.99);
        $product1->setCategory($category);
        $product1->setImagePath('');
        $product1->setStock(10);
        $product1->setCreatedAt(new \DateTimeImmutable());
        $manager->persist($product1);

        $product2 = new Product();
        $product2->setName('Sample Product 2');
        $product2->setBrand('Test Brand');
        $product2->setDescription('Another sample product for testing.');
        $product2->setPrice(49.99);
        $product2->setCategory($category);
        $product2->setImagePath('');
        $product2->setStock(5);
        $product2->setCreatedAt(new \DateTimeImmutable());
        $manager->persist($product2);

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [CategoryFixtures::class];
    }
}
