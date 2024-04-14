<?php

namespace App\Command;

// Entities
use App\Entity\Survey;
use App\Entity\SurveyStep;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\DependencyInjection\ContainerInterface;

class SurveyAddStepCommand extends ContainerAwareCommand
{
    private $doctrine;

    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'survey:add:step';

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
            ->setName('survey:add:step')

            // the short description shown while running "php bin/console list"
            ->setDescription('Allow to add some steps to a survey')

            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp('')

            // set input arguments
            ->addArgument('id_survey',  InputArgument::REQUIRED, 'Survey ID (eg. 99)')
            ->addArgument('label',      InputArgument::REQUIRED, 'Survey Step label (eg. Animation)')
            ->addArgument('position',   InputArgument::REQUIRED, 'Survey Step position (eg. 1)')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $entityManager  = $this->doctrine->getManager();
        $repoSurvey     = $entityManager->getRepository(Survey::class);

        // Get arguments & variables
        $label      = $input->getArgument('label');
        $position   = $input->getArgument('position');
        $id_survey  = $input->getArgument('id_survey');
        $survey     = $repoSurvey->findOneById($id_survey);

        // Output
        $output->writeln('<fg=black;bg=green> [Survey:Add:Step] Ajout d\'une étape à un questionnaire </>');
        $output->writeln('  id_survey: <fg=green>' . $id_survey . '</>, label: <fg=green>' . $label . '</>, position: <fg=green>' . $position . '</>');

        if (!empty($survey)) {
            // Create new survey
            $survey_step = new SurveyStep();

            // Set survey step fields
            $survey_step->setLabel($label);
            $survey_step->setPosition($position);
            $survey_step->setSurvey($survey);

            // Persist
            $entityManager->persist($survey_step);

            // Try to save (flush) or clear
            try {
                // Flush OK !
                $entityManager->flush();

                // Output : success message
                $output->writeln(' <fg=green>Sauvegarde de l\'étape du questionnaire effectuée avec succès ! (ID: ' . $survey_step->getId() . ') </>');
            } catch (\Exception $e) {
                // Something goes wrong > clear
                $entityManager->clear();

                // Output : error/exception message
                $output->writeln(' '); // line break
                $output->writeln('<fg=white;bg=red> Problème lors de la sauvegarde: </>');
                $output->writeln('  <fg=red>' . $e->getMessage() . '</>');
            }
        } else {
            // Output : No survey with this ID
            $output->writeln('');
            $output->writeln('<fg=yellow> Aucun questionnaire n\'existe pour cet ID (#' . $id_survey . ') </>');
        }

        // MOAR space !!
        $output->writeln('');
    }
}
