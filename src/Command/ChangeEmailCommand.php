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

class ChangeEmailCommand extends ContainerAwareCommand
{
    private $doctrine;

    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'users:change:email';

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
            ->setName('users:change:email')

            // the short description shown while running "php bin/console list"
            ->setDescription('Allow to change user email with his ID')

            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp('This command is a f*cking mistake.')

            // set input arguments
            ->addArgument('user_id', InputArgument::REQUIRED, 'User ID')
            ->addArgument('user_new_email', InputArgument::REQUIRED, 'User new e-mail')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $entityManager  = $this->doctrine->getManager();
        $repoUsers      = $entityManager->getRepository(User::class);
        // Retrieve ID and user
        $user_id  = (int)$input->getArgument('user_id');
        $user     = $repoUsers->findOneById($user_id);

        // Add some space
        $output->writeln('');
        if(!empty($user)) {
            // Retrieve new email
            $new_email = $input->getArgument('user_new_email');

            // Output
            $output->writeln('<fg=black;bg=green> [App:Users] Changement d\'adresse email </>');
            $output->writeln('  <fg=red>' . $user->getEmail() . '</> devient: <fg=green>' . $new_email . '</>');

            // Change user's email
            $user->setEmail($new_email);

            // Flush changes
            $entityManager->flush();
        } else {
            // Output : No user with this ID
            $output->writeln('<fg=yellow> Aucun utilisateur n\'existe pour cet ID (#' . $user_id . ') </>');
        }
        // MOAR space !!
        $output->writeln('');
    }
}
