<?php

namespace App\Command;

// Entities
use App\Entity\User;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ForceReferentConsultantCommand extends ContainerAwareCommand
{
      public function __construct($name = null, ContainerInterface $container)
      {
          parent::__construct($name);

          $this->container  = $container;
          $this->doctrine   = $this->container->get('doctrine');
      }

      protected function configure()
      {
          $this
              // the name of the command (the part after "bin/console")
              ->setName('users:force:referent')

              // the short description shown while running "php bin/console list"
              ->setDescription('Force a referent consultant to users without one.')

              // the full command description shown when running the command with
              // the "--help" option
              ->setHelp('This command is an another f*cking mistake.')

              // set input arguments
              ->addArgument('referent_id', InputArgument::REQUIRED, 'Referent ID')
          ;
      }

      protected function execute(InputInterface $input, OutputInterface $output)
      {
          $entityManager  = $this->doctrine->getManager();
          $repoUsers      = $entityManager->getRepository(User::class);
          // Retrieve ID referent to force
          $referent_id    = (int)$input->getArgument('referent_id');
          $referent       = $repoUsers->findOneById($referent_id);

          // TOP Message
          $output->writeln('<fg=black;bg=green> [App:Users] Forcing d\'un-e référent-e pour les utilisateurs sans consultant-e référent-e </>');
          // Add some space
          $output->writeln('');

          // Test if referent consultant exist
          if(!empty($referent)) {
              // User's without a referent consultant
              $usersWithoutReferent = $repoUsers->findWithoutReferentConsultant();

              // Print referent to force
              $output->writeln('  Consultant-e à forcer : <fg=green>' . $referent->getLastname() . ' ' . $referent->getFirstname() . '</>');
              $output->writeln('');

              if(!empty($usersWithoutReferent)) {
                  // Print user's list to force referent
                  $output->writeln('  Liste des utilisateurs auquel le-la consultant-e sera rataché-e :');
                  foreach ($usersWithoutReferent as $key => $user) {
                      $output->writeln('  - ' . $user->getLastname() . ' ' . $user->getFirstname());

                      // Set ID referent for current looped user
                      $user->setReferentConsultant($referent);
                  }
              } else {
                  $output->writeln('  <fg=yellow>Aucun utilisateur auquel forcer un-e référent-e (tou-te-s les utilisateur-rice-s ont un-e consultant-e référent-e)</>');
              }

              // Save changes
              $entityManager->flush();
          } else {
              $output->writeln('  <fg=red>Aucun consultant n\'existe pour l\'id : ' . $referent_id . '</>');
          }

          // MOAR space !!
          $output->writeln('');
      }
}
