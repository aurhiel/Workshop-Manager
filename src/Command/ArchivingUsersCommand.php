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


class ArchivingUsersCommand extends ContainerAwareCommand
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
            ->setName('users:archiving')

            // the short description shown while running "php bin/console list"
            ->setDescription('Archiving users disabled for more than 3 months.')

            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp('This command archiving (hide them in users list) users disabled for more than 3 months.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('<fg=black;bg=green> [App:Users] Archivage des utilisateurs désactivés depuis plus de 3 mois</>');

        $entityManager = $this->doctrine->getManager();
        $repoUsers = $entityManager->getRepository(User::class);

        // Find all users out of date
        $usersToArchive = $repoUsers->findToArchive();

        // Display number of users to disable
        $output->writeln('');
        if(count($usersToArchive) > 1)
            $output->writeln('<fg=cyan>  '.count($usersToArchive).' utilisateurs vont être archivés :</>');
        else if(count($usersToArchive) > 0)
            $output->writeln('<fg=cyan>  '.count($usersToArchive).' utilisateur va être archivé :</>');
        else
            $output->writeln('<fg=yellow>  Aucun utilisateur à archiver</>');

        if(count($usersToArchive) > 0) {
            // Display archiving users
            foreach ($usersToArchive as $user) {
              $output->writeln('<fg=cyan>   - ' . $user->getFirstname() . ' ' . $user->getLastname() . '</>');
            }
            $nbUsersArchived = $repoUsers->archiveUsersDisabled();
            $output->writeln('');

            if($nbUsersArchived > 0) {
                if($nbUsersArchived > 1)
                    $output->writeln('<fg=green>  - Tous les utilisateurs ont été archivés avec succès</>');
                else
                    $output->writeln('<fg=green>  - L\'utilisateur a été archivé avec succès</>');

                /*
                // Send email with the list of archived users to admin
                $message = (new \Swift_Message('[CRON] Archivage automatique de '.$nbUsersArchived.' utilisateur'.($nbUsersArchived>1?'s':'')))
                ->setFrom(array('ne-pas-repondre@ateliers-ingeneria.fr' => 'Les Ateliers Ingeneria'))
                ->setBody(
                  $this->templating->render(
                    'emails/crons/users-archived.html.twig', // TODO
                    array(
                      'nb_users_disabled' => $nbUsersArchived,
                      'users_disabled'    => $usersToArchive
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
                $output->writeln('<fg=red>  - Un problème est survenu veuillez éxécuter la commande à nouveau, si le problème persiste contactez un administrateur</>');

                // return nok
                return false;
            }
        } else {
            return null;
        }
    }
}
