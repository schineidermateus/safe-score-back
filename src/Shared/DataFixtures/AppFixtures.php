<?php

namespace App\Shared\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public const string EMAIL_FIXTURE = 'teste@teste123.com';
    public const string PASSWORD_FIXTURE = 'Lumi@321';


    public function load(ObjectManager $manager): void
    {
        // $product = new Product();
        // $manager->persist($product);

        $manager->flush();
    }
}
