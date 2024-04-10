<?php

namespace App\DataFixtures;

use App\Entity\Themes;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        //Création de thèmes
        $themes = ['Mario', 'Zelda', 'Final Fantasy', 'Halo', 'Call of Duty', 'The Witcher', 'Cyberpunk 2077', 'Minecraft', 'Fortnite', 'Among Us'];

        foreach ($themes as $themeName) {
            $theme = new Themes();
            $theme->setName($themeName);
            $manager->persist($theme);
        }

        $manager->flush();
    }
}