<?php

namespace App\Command;

// Entities
use App\Entity\User;

// Components
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Symfony\Component\DependencyInjection\ContainerInterface;


class DisableUsersOutOfDateCommand extends ContainerAwareCommand
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
        $this->templating = $this->container->get('twig');
    }

    protected function configure()
    {
        $this
            // the name of the command (the part after "bin/console")
            ->setName('users:disable-out-of-date')

            // the short description shown while running "php bin/console list"
            ->setDescription('Disable users with out of date.')

            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp('This command disable users with a "register end date" no more valid (out of date).')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('<fg=black;bg=green> [App:Users] Désactivation des utilisateurs avec une date de fin d\'inscription antérieur à aujourd\'hui</>');

        $entityManager = $this->doctrine->getManager();
        $repoUsers = $entityManager->getRepository(User::class);

        // Find all users out of date
        $usersOutOfDate = $repoUsers->findOutOfDate();

        // Display number of users to disable
        $output->writeln('');
        if(count($usersOutOfDate) > 1)
            $output->writeln('<fg=cyan>  '.count($usersOutOfDate).' utilisateurs vont être désactivés</>');
        else if(count($usersOutOfDate) > 0)
            $output->writeln('<fg=cyan>  '.count($usersOutOfDate).' utilisateur va être désactivé</>');
        else
            $output->writeln('<fg=yellow>  Aucun utilisateur à désactiver</>');

        if(count($usersOutOfDate) > 0) {
            $nb_users_disabled = $repoUsers->disableUsersOutOfDate();

            if($nb_users_disabled > 0) {
                if($nb_users_disabled > 1)
                    $output->writeln('<fg=green>  - Tous les utilisateurs ont été désactivés avec succès</>');
                else
                    $output->writeln('<fg=green>  - L\'utilisateur a été désactivé avec succès</>');

                /*
                // Send email with the list of disabled users to admin
                $message = (new \Swift_Message('[CRON] Désactivation automatique de '.$nb_users_disabled.' utilisateur'.($nb_users_disabled>1?'s':'')))
                ->setFrom(array('ne-pas-repondre@ateliers-ingeneria.fr' => 'Les Ateliers Ingeneria'))
                ->setBody(
                  $this->templating->render(
                    'emails/crons/users-disabled.html.twig',
                    array(
                      'nb_users_disabled' => $nb_users_disabled,
                      'users_disabled'    => $usersOutOfDate
                    )
                  ),
                  'text/html'
                );

                // Set All users emails
                $message->setBcc(array(
                  'dominique.eloise@ingeneria.fr' => 'Eloise Dominique',
                  'litti.aurelien@gmail.com'      => 'Litti Aurélien'
                ));

                // Send email
                $this->mailer->send($message);
                */

                // return ok
                return true;
            } else {
                $output->writeln('<fg=red>  - Un problème est survenu veuillez éxécuter la commande à nouveau ou plus tard si le problème persiste</>');

                // return nok
                return false;
            }
        } else {
            return null;
        }
    }
}
