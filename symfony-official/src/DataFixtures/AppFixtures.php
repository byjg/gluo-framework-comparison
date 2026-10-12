<?php

namespace App\DataFixtures;

use App\Entity\Product;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(private readonly UserPasswordHasherInterface $passwordHasher)
    {
    }

    public function load(ObjectManager $manager): void
    {
        $user = (new User())->setEmail('admin@example.com');
        $user->setPassword($this->passwordHasher->hashPassword($user, '!P4ssw0rdstr!'));
        $manager->persist($user);

        foreach (['Keyboard' => '49.90', 'Mouse' => '19.90', 'Monitor' => '899.00', 'Headset' => '129.50', 'Webcam' => '75.00'] as $name => $price) {
            $manager->persist((new Product())->setName($name)->setPrice($price));
        }

        $manager->flush();
    }
}
