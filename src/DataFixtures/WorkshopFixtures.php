<?php
namespace App\DataFixtures;

// Entities
use App\Entity\User;
use App\Entity\Workshop;
use App\Entity\Address;
use App\Entity\WorkshopTheme;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;

class WorkshopFixtures extends Fixture implements OrderedFixtureInterface
{

    public function load(ObjectManager $manager)
    {
        // Retrieve publishers available
        $repoUser = $manager->getRepository(User::class);
        $publishers = $repoUser->findByRole('ROLE_PUBLISHER');

        $repoTheme = $manager->getRepository(WorkshopTheme::class);
        $themes    = $repoTheme->findAll();
        // dump($themes);exit;

        $repoAddr   = $manager->getRepository(Address::class);
        $addresses  = $repoAddr->findAll();
        // dump($addresses);exit;

        $the_future = new \DateTime();
        $the_future_end = clone $the_future;

        for($i = 0; $i < 20; $i++) {
            // Add 1 day to $the_future every 3 occurences
            if($i%3 == 0 && $i > 0) {
                $the_future->add(new \DateInterval('P1D'));
                $the_future_end->add(new \DateInterval('P1D'));
            }

            // Create the future entity workshop
            $workshop = new Workshop();

            // Determine start hour according to current $i
            // $hour_start = 8 + (($i%3)*2);
            $hour_start = 8 + rand(0, 4);
            $hour_start = ($hour_start == 12) ? 14 : $hour_start;
            // Set the new start hour
            $the_future->setTime($hour_start, 0, 0, 0);
            // Assign start hour to the new entity
            $workshop->setDateStart($the_future);


            // Add 2h to start hour and assign end hour to the new entity
            $the_future_end->setTime(($hour_start + rand(2, 4)), 0, 0, 0);
            $workshop->setDateEnd($the_future_end);


            // Set nb seats
            $workshop->setNbSeats(rand(1, 4));


            // Set lecturer
            $num_lecturer = rand(0, (count($publishers) - 1));
            $workshop->setLecturer($publishers[$num_lecturer]);
            // dump($publishers[$num_lecturer]);


            // Set theme
            $num_theme = rand(0, (count($themes) - 1));
            $workshop->setTheme($themes[$num_theme]);
            // dump($num_theme);


            // Set address
            $num_addr = rand(0, (count($addresses) - 1));
            $workshop->setAddress($addresses[$num_addr]);


            // Save new workshop entity
            $manager->persist($workshop);

            // Flush
            $manager->flush();
        }
    }

    public function getOrder()
    {
        return 20;
    }
}
