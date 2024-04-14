<?php
namespace App\DataFixtures;

use App\Entity\Address;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;

class AddressFixtures extends Fixture implements OrderedFixtureInterface
{

    public function load(ObjectManager $manager)
    {
        // List addresses
        $addresses = [
          [
            'name'      => 'Aix - Le Ligourès',
            'latitude'  => 43.5233622,
            'longitude' => 5.4316114
          ],
          [
            'name'      => 'Aix - Mansard',
            'latitude'  => 43.5223031,
            'longitude' => 5.4240048
          ],
        ];

        foreach ($addresses as $addr_data)
        {
            $address = new Address();

            // Set Address Name
            $address->setName($addr_data['name']);

            // Set Address positions
            $address->setLatPosition($addr_data['latitude']);
            $address->setLngPosition($addr_data['longitude']);

            // Save it
            $manager->persist($address);
        }

        // Flush
        $manager->flush();
    }

    public function getOrder()
    {
        return 10;
    }
}
