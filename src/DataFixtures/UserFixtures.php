<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use App\Entity\User;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        // $product = new Product();
        // $manager->persist($product);

        $admin = new User();
        $admin->setUsername('admin1');
        $admin->setEmail('admin@easysave.local');
        $hashedPassword = $this->passwordHasher->hashPassword($admin, 'adminpass1');
        $admin->setPassword($hashedPassword);
        $admin->setRoles(['ROLE_ADMIN']);
        $manager->persist($admin);

        $staff = new User();
        $staff->setUsername('staff1');
        $staff->setEmail('staff@easysave.local');
        $hashedPassword = $this->passwordHasher->hashPassword($staff, 'staffpass1');
        $staff->setPassword($hashedPassword);
        $staff->setRoles(['ROLE_STAFF']);
        $manager->persist($staff);

        $manager->flush();
    }
}
