<?php

namespace App\Command;

// Entities
use App\Entity\UserVSI;
use App\Entity\Survey;
use App\Entity\SurveyToken;

// Components
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Symfony\Component\DependencyInjection\ContainerInterface;


class UsersVSISendSurveyCommand extends ContainerAwareCommand
{
    private $container;

    private $doctrine;

    private $mailer;

    private $templating;


    public function __construct($name = null, ContainerInterface $container) {
        parent::__construct($name);

        $this->container  = $container;
        $this->doctrine   = $this->container->get('doctrine');
        $this->mailer     = $this->container->get('mailer');
        $this->twig       = $this->container->get('twig');
    }

    protected function configure()
    {
        $this
            // the name of the command (the part after "bin/console")
            ->setName('users-vsi:send:survey')

            // the short description shown while running "php bin/console list"
            ->setDescription('Send an email with a link to answer a survey');

            // the full command description shown when running the command with
            // the "--help" option
            // ->setHelp('This command disable users with a "register end date" no more valid (out of date).')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('<fg=black;bg=green> [UsersVSI:Send Survey] Génération et envoi du token du questionnaire de satisfaction</>');

        $em = $this->doctrine->getManager();
        $repoUsersVSI = $em->getRepository(UserVSI::class);

        // Find all users to notify
        $usersToSurvey = $repoUsersVSI->findToNotifySurvey();

        if(count($usersToSurvey) > 0) {
            $repoSurvey = $em->getRepository(Survey::class);
            $survey     = $repoSurvey->findOneByIsDefault(true);

            foreach($usersToSurvey as $userVSI) {
                $tokens = $userVSI->getSurveyTokens();
                $nb_notified = 0;

                if (count($tokens) < 1) {
                    $output->writeln('  - ' . $userVSI->getFirstname() . ' ' . $userVSI->getLastname() . ' (<fg=cyan>' . $userVSI->getEmail() . '</>)');

                    // Create survey token
                    $survey_token = new SurveyToken();
                    $survey_token->setSurvey($survey);
                    $survey_token->setUserVSI($userVSI);

                    // Generate token (survey is available for 15 days)
                    $token = $survey_token->generateToken(new \DateInterval('P15D'));

                    // Persist & flush user's survey token
                    $em->persist($survey_token);
                    $em->flush();

                    // Create message for mailer
                    $message = (new \Swift_Message('Questionnaire de satisfaction'))
                        ->setFrom(array('ne-pas-repondre@ateliers-ingeneria.fr' => 'Les Ateliers Ingeneria'))
                        ->setBody(
                            $this->twig->render(
                                'emails/survey/new-token.html.twig',
                                array(
                                  'survey'  => $survey,
                                  'user'    => $userVSI,
                                  'token'   => $survey_token
                                )
                            ),
                            'text/html'
                        )
                    ;

                    $message->setBcc($userVSI->getEmail());

                    $this->mailer->send($message);
                    $nb_notified++;
                } else {
                    // TODO re-generate ?
                }
            }
        }

        // Display amount of users notified
        if ($nb_notified > 0) {

        } else {
            $output->writeln('<fg=yellow>  Aucun utilisateur notifié</>');
        }
    }
}
