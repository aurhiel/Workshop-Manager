<?php
namespace App\DataFixtures;

// User entity
use App\Entity\User;
use App\Entity\UserVSI;
use App\Entity\Survey;
use App\Entity\SurveyToken;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;

use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class UserVSIFixtures extends Fixture implements OrderedFixtureInterface
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
            [ 'firstname' => 'Aayla',     'lastname'  => 'Secura' ],
            [ 'firstname' => 'Poe',       'lastname'  => 'Dameron' ],
            [ 'firstname' => 'Lando',     'lastname'  => 'Carlrissian' ],
            [ 'firstname' => 'Gial',      'lastname'  => 'Ackbar' ],
            [ 'firstname' => 'Ysanne',    'lastname'  => 'Isard' ],
            [ 'firstname' => 'Rune',      'lastname'  => 'Haako' ],
            [ 'firstname' => 'Ponda',     'lastname'  => 'Baba' ],
            [ 'firstname' => 'Bib',       'lastname'  => 'Fortuna' ],
            [ 'firstname' => 'Kanan',     'lastname'  => 'Jarrus' ],
            [ 'firstname' => 'Mara',      'lastname'  => 'Jade' ],
            [ 'firstname' => 'Jabba',     'lastname'  => 'Le-Hutt' ],
            [ 'firstname' => 'Zett',      'lastname'  => 'Jukassa' ],
            [ 'firstname' => 'Harter',    'lastname'  => 'Kalonia' ],
            [ 'firstname' => 'Tsavong',   'lastname'  => 'Lah' ],
            [ 'firstname' => 'Ray',       'lastname'  => 'Park' ],
            [ 'firstname' => 'Kirster',   'lastname'  => 'Banai' ],
            [ 'firstname' => 'Walex',     'lastname'  => 'Blissex' ],
            [ 'firstname' => 'Zorii',     'lastname'  => 'Bliss' ],
            [ 'firstname' => 'Vober',     'lastname'  => 'Dand' ],
            [ 'firstname' => 'Boba',      'lastname'  => 'Fett' ],
            [ 'firstname' => 'Corran',    'lastname'  => 'Horn' ],
            [ 'firstname' => 'Armitage',  'lastname'  => 'Hux' ],
            [ 'firstname' => 'Maz',       'lastname'  => 'Kanata' ],
            [ 'firstname' => 'Shu',       'lastname'  => 'Mai' ],
            [ 'firstname' => 'Lyn',       'lastname'  => 'Me' ],
            [ 'firstname' => 'Ric',       'lastname'  => 'Olie' ],
            [ 'firstname' => 'Garazeb',   'lastname'  => 'Orrelios' ],
            [ 'firstname' => 'Leia',      'lastname'  => 'Organa' ],
        ];

        $letters = ['A', 'B', 'C', 'D', 'E', 'F'];

        // Retrieve consultant
        $repoUser   = $manager->getRepository(User::class);
        $consultant = $repoUser->findByEmail('dominique.eloise@ingeneria.fr')[0];

        // Get default survey
        $repoSurvey = $manager->getRepository(Survey::class);
        $survey     = $repoSurvey->findOneByIsDefault(true);

        if ($survey !== null) {
            $idCohort = 1;
            foreach ($users as $key => $user_data) {
                if ($key%5 == 1) $idCohort++;
                // Create user VSI
                $user = new UserVSI();
                $user->setEmail(strtolower($user_data['firstname']).'@'.strtolower($user_data['lastname']).'.fr');
                $user->setFirstname($user_data['firstname']);
                $user->setLastname($user_data['lastname']);
                $user->setIdVSI(rand(1000000, 9999999).$letters[rand(0, count($letters)-1)]);
                $user->setIdCohort($idCohort);
                $workshopEndDate = new \DateTime();
                $user->setWorkshopEndDate($workshopEndDate);
                if (!empty($consultant))
                    $user->setReferentConsultant($consultant);
                // Persist user
                $manager->persist($user);

                // Create survey token
                $survey_token = new SurveyToken();
                $survey_token->setSurvey($survey);
                $survey_token->setUserVSI($user);
                $survey_token->generateToken(new \DateInterval('P3D')); // Survey available for 3 days
                // Persist user's survey token
                $manager->persist($survey_token);
            }
        } else {
            echo '[UserVSIFixtures::Error] Must create a survey before users VSI ! ';
            var_dump('eol'); // Add a linebreak ! x3
            die();
        }

        // Flush
        $manager->flush();
    }

    public function getOrder()
    {
        return 5;
    }
}
