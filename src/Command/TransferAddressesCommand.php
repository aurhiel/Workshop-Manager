<?php

namespace App\Command;

// Entities
use App\Entity\Workshop;

// Components
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Symfony\Component\DependencyInjection\ContainerInterface;


class TransferAddressesCommand extends ContainerAwareCommand
{
    private $container;

    private $doctrine;


    public function __construct($name = null, ContainerInterface $container) {
        parent::__construct($name);

        $this->container  = $container;
        $this->doctrine   = $this->container->get('doctrine');
    }

    protected function configure()
    {
        $this
            // the name of the command (the part after "bin/console")
            ->setName('workshops:transfer:addresses')

            // the short description shown while running "php bin/console list"
            ->setDescription('Set workshops\' addresses from themes addresses.')

            // the full command description shown when running the command with
            // the "--help" option
            // ->setHelp('--')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('<fg=black;bg=green> [App:Workshops] Transfert des adresses des thématiques vers les ateliers</>');

        $em = $this->doctrine->getManager();
        $r_workshops = $em->getRepository(Workshop::class);

        // Get only workshops without address
        $workshops = $r_workshops->findBy(array('address' => null));
        if (count($workshops) > 0) {
          foreach ($workshops as $workshop) {
              $w_theme = $workshop->getTheme();

              $output->writeln('  <fg=blue>[#' . $workshop->getId() . ']</> > ' . $w_theme->getAddress()->getName() . '');

              // Set address to workshop
              $workshop->setAddress($w_theme->getAddress());
          }
        } else {
            $output->writeln('  <fg=yellow>Aucun atelier avec une adresse NULL à remplir par l\'adresse de la thématique</>');
        }

        // Save new address in workshops
        $em->flush();
    }
}
