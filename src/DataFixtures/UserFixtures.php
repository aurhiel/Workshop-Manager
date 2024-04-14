<?php
namespace App\DataFixtures;

// User entity
use App\Entity\User;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;

use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class UserFixtures extends Fixture implements OrderedFixtureInterface
{
    private $encoder;

    public function __construct(UserPasswordEncoderInterface $encoder)
    {
        $this->encoder = $encoder;
    }

    public function load(ObjectManager $manager)
    {
        // List users
        $users = [
            [
                'firstname' => 'Dominique',
                'lastname'  => 'Eloise',
                'email'     => 'dominique.eloise@ingeneria.fr',
                'phone'     => '06 25 62 07 95',
                'roles'     => array('ROLE_ADMIN')
            ],
            [
                'firstname' => 'Veronique',
                'lastname'  => 'Ceccaldi',
                'email'     => 'veronique.ceccaldi@ingeneria.fr',
                'roles'     => array('ROLE_PUBLISHER')
            ],
            [
                'firstname' => 'Aurélien',
                'lastname'  => 'Litti',
                'email'     => 'litti.aurelien@gmail.com',
                'phone'     => '06 95 06 40 91',
                'roles'     => array('ROLE_SUPERADMIN')
            ]
        ];


        $users = array_merge([
            [ 'firstname' => 'John',      'lastname'  => 'Doe' ],
            [ 'firstname' => 'Michel',    'lastname'  => 'Mannhart' ],
            [ 'firstname' => 'Olivier',   'lastname'  => 'Dubois' ],
            [ 'firstname' => 'Cléo',      'lastname'  => 'Patreus' ],
            [ 'firstname' => 'Bob',       'lastname'  => 'Donnel' ],
            [ 'firstname' => 'Norbert',   'lastname'  => 'Dragonneau' ],
            [ 'firstname' => 'Laura',     'lastname'  => 'Cullen' ],
            [ 'firstname' => 'Roger',     'lastname'  => 'Davis' ],
            [ 'firstname' => 'Fanny',     'lastname'  => 'Rovelo' ],
            [ 'firstname' => 'Antonin',   'lastname'  => 'Dolohov' ],
            [ 'firstname' => 'Cédric',    'lastname'  => 'Diggory' ],
            [ 'firstname' => 'Pénélope',  'lastname'  => 'Deauclaire' ],
            [ 'firstname' => 'Nicolas',   'lastname'  => 'Duchamps' ],
            [ 'firstname' => 'Marine',    'lastname'  => 'Pichu' ],
            [ 'firstname' => 'Armando',   'lastname'  => 'Dippet' ],
            [ 'firstname' => 'Ludovic',   'lastname'  => 'Verpey' ],
            [ 'firstname' => 'Fleur',     'lastname'  => 'Delacour' ],
            [ 'firstname' => 'Gabriel',   'lastname'  => 'Delacour' ],
            [ 'firstname' => 'Peregrin',  'lastname'  => 'Derrick' ],
        ], $users);


        $letters = ['A', 'B', 'C', 'D', 'E', 'F'];
        foreach ($users as $user_data)
        {
            $user = new User();

            // Email
            if(isset($user_data['email'])) {
                $user->setEmail($user_data['email']);
            } else {
                // Default email
                $user->setEmail(strtolower($user_data['firstname']).'@'.strtolower($user_data['lastname']).'.fr');
            }

            // Prénom / Nom
            $user->setFirstname($user_data['firstname']);
            $user->setLastname($user_data['lastname']);

            // Téléphone
            if(isset($user_data['phone'])) {
              $user->setPhone($user_data['phone']);
            } else {
              $fake_phone = '06';
              for ($i=0; $i < 4; $i++) {
                $fake_phone .= ' '.rand(10, 99);
              }
              $user->setPhone($fake_phone);
            }

            // ID Pôle emploi
            $user->setIdPoleEmploi(rand(1000000, 9999999).$letters[rand(0, count($letters)-1)]);

            // Type de prestation
            $serviceType = (rand()%2 == 0)?User::SERVICE_TYPE_ACTIV_CREA:User::SERVICE_TYPE_ACTIV_PROJET;
            $user->setServiceType($serviceType);

            // Date de fin de la prestation
            $registerEndDate = new \DateTime(($serviceType == User::SERVICE_TYPE_ACTIV_CREA)?'+3 month':'+2 month');
            $registerEndDate->setTime(8,0,0); // 8h00
            $user->setRegisterEndDate($registerEndDate);

            // Mot de passe
            $password = isset($user_data['password']) ? $user_data['password'] : 'pass';
            $user->setPassword($this->encoder->encodePassword($user, $password));

            // Droits
            if(isset($user_data['roles']) && !empty($user_data['roles'])) {
                $user->setRoles($user_data['roles']);
                $user->setIsConsultant($user_data['email'] != 'litti.aurelien@gmail.com');
            } else {
                // Default roles
                $user->setRoles(array('ROLE_USER'));
            }

            // Force activation (by default users are disabled)
            $user->setIsActive(true);

            $manager->persist($user);
        }

        // Flush
        $manager->flush();
    }

    public function getOrder()
    {
        return 1;
    }
}
