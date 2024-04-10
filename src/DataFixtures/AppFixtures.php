<?php

namespace App\DataFixtures;

use App\Entity\Cards;
use App\Entity\GameSettings;
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

            $this->addReference($themeName, $theme);
        }

        //Création des niveaux de difficulté
        $difficulties = ['facile', 'normal', 'difficile'];

        foreach ($difficulties as $difficulty) {
            $gameSetting = new GameSettings();
            $gameSetting->setDifficulty($difficulty);
            $manager->persist($gameSetting);
        }

        //Création de cartes
        foreach ($themes as $themeName) {
            $theme = $this->getReference($themeName);

            for ($i = 1; $i <= 10; $i++) {
                $card = new Cards();
                $card->setImageUrl("https://example.com/cards/{$themeName}/card{$i}.jpg");
                $card->setThemeId($theme);
                $manager->persist($card);
            }
        }

        $manager->flush();
    }
}