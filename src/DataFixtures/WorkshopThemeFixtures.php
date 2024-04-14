<?php
namespace App\DataFixtures;

use App\Entity\WorkshopTheme;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;

class WorkshopThemeFixtures extends Fixture implements OrderedFixtureInterface
{

    public function load(ObjectManager $manager)
    {
        // List themes
        $themes = [
          [
            'name' => "Comment fonctionne une couveuse d'entreprises à l'essai",
          ],
          [
            'name' => "Construire son prévisionnel",
          ],
          [
            'name' => "Elaborer votre business plan financier",
          ],
          [
            'name' => "Fonctionnement de ma micro-entreprise",
          ],
          [
            'name' => "Identifier mes compétences",
          ],
          [
            'name' => "Plan de financement et prévisionnel de la micro-entreprise",
          ],
          [
            'name' => "Reconnaitre et accepter sa légitimité - Se libérer du syndrome de l'imposteur",
          ]
        ];


        foreach ($themes as $theme_data)
        {
            $theme = new WorkshopTheme();

            // Set Theme Name
            $theme->setName($theme_data['name']);

            // Save it
            $manager->persist($theme);
        }

        // Flush
        $manager->flush();
    }

    public function getOrder()
    {
        return 15;
    }
}
