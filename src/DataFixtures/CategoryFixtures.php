<?php

namespace App\DataFixtures;

use App\Entity\Category;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class CategoryFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $cat1 = new Category();
        $cat1->setName('Electronics');
        $cat1->setDescription('Electronic gadgets and devices.');
        $manager->persist($cat1);

        $cat2 = new Category();
        $cat2->setName('Home & Garden');
        $cat2->setDescription('Home improvement and gardening supplies.');
        $manager->persist($cat2);

        $manager->flush();
    }
}
