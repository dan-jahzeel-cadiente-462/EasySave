<?php

namespace App\DataFixtures;

use App\Entity\Order;
use App\Entity\User;
use App\Entity\Address;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class OrderFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        // Get a user from UserFixtures
        $user = $manager->getRepository(User::class)->findOneBy(['username' => 'admin1']);
        if (!$user) {
            return; // Skip if no user exists
        }

        // Get or create an address for the user
        $address = $manager->getRepository(Address::class)->findOneBy(['user' => $user]);
        if (!$address) {
            $address = new Address();
            $address->setUser($user);
            $address->setHouseNo('123');
            $address->setStreet('Main St');
            $address->setBarangay('Sample Barangay');
            $address->setCityMunicipality('Springfield');
            $address->setProvince('IL');
            $address->setPostalCode('62701');
            $manager->persist($address);
        }

        // Create sample orders
        $order1 = new Order();
        $order1->setCustomer($user);
        $order1->setShippingAddress($address);
        $order1->setTotal(99.99);
        $order1->setStatus('Pending');
        $order1->setCreatedAt(new \DateTimeImmutable());
        $manager->persist($order1);

        $order2 = new Order();
        $order2->setCustomer($user);
        $order2->setShippingAddress($address);
        $order2->setTotal(149.99);
        $order2->setStatus('Shipped');
        $order2->setCreatedAt(new \DateTimeImmutable('2025-01-01'));
        $manager->persist($order2);

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [UserFixtures::class];
    }
}
