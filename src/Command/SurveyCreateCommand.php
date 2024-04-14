<?php

namespace App\Command;

// Entities
use App\Entity\Survey;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\DependencyInjection\ContainerInterface;

class SurveyCreateCommand extends ContainerAwareCommand
{
    private $doctrine;

    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'survey:create';

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
            ->setName('survey:create')

            // the short description shown while running "php bin/console list"
            ->setDescription('Creates a new survey')

            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp('')

            // set input arguments
            ->addArgument('label',  InputArgument::REQUIRED, 'Survey label (eg. VSI #1)')
            ->addArgument('slug',   InputArgument::REQUIRED, 'Survey slug (eg. vsi-1)')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Get arguments
        $label  = $input->getArgument('label');
        $slug   = $input->getArgument('slug');

        // Output
        $output->writeln('<fg=black;bg=green> [Survey:Create] Création d\'un nouveau questionnaire </>');
        $output->writeln('  label: <fg=green>' . $label . '</>, slug: <fg=green>' . $slug . '</>');

        // Create new survey
        $survey = new Survey();

        // Set survey fields
        $survey->setLabel($label);
        $survey->setSlug($slug);

        // Persist
        $entityManager = $this->doctrine->getManager();
        $entityManager->persist($survey);

        // Try to save (flush) or clear
        try {
            // Flush OK !
            $entityManager->flush();
            // Display success message
            $output->writeln(' <fg=green>Sauvegarde du questionnaire effectuée avec succès ! (ID: ' . $survey->getId() . ') </>');
        } catch (\Exception $e) {
            // Something goes wrong > clear
            $entityManager->clear();
            // Display error/exception message
            $output->writeln(' '); // line break
            $output->writeln('<fg=white;bg=red> Problème lors de la sauvegarde: </>');
            $output->writeln('  <fg=red>' . $e->getMessage() . '</>');
        }
    }
}
