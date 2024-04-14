<?php

namespace App\Command;

// Entities
use App\Entity\SurveyStep;
use App\Entity\SurveyQuestion;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\DependencyInjection\ContainerInterface;

class SurveyAddQuestionCommand extends ContainerAwareCommand
{
    private $doctrine;

    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'survey:add:question';

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
            ->setName('survey:add:question')

            // the short description shown while running "php bin/console list"
            ->setDescription('Allow to add some questions to a survey')

            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp('')

            // set input arguments
            ->addArgument('id_survey_step', InputArgument::REQUIRED, 'Survey step ID (eg. 99)')
            ->addArgument('label',          InputArgument::REQUIRED, 'Survey question label (eg. "Availability and expectations")')
            ->addArgument('position',       InputArgument::REQUIRED, 'Survey question position (eg. 0)')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $entityManager  = $this->doctrine->getManager();
        $repoSurveyStep = $entityManager->getRepository(SurveyStep::class);

        // Get arguments & variables
        $label      = $input->getArgument('label');
        $position   = $input->getArgument('position');
        $id_survey_step = $input->getArgument('id_survey_step');
        $survey_step    = $repoSurveyStep->findOneById($id_survey_step);

        // Output
        $output->writeln('<fg=black;bg=green> [Survey:Add:Question] Ajout d\'une question à un questionnaire </>');
        $output->writeln('  id_survey_step: <fg=green>' . $id_survey_step . '</>, label: <fg=green>' . $label . '</>, position: <fg=green>' . $position . '</>');

        if (!empty($survey_step)) {
            // Create new survey question
            $survey_question = new SurveyQuestion();

            // Set survey question fields
            $survey_question->setLabel($label);
            $survey_question->setPosition($position);
            $survey_question->setSurveyStep($survey_step);

            // Persist
            $entityManager->persist($survey_question);

            // Try to save (flush) or clear
            try {
                // Flush OK !
                $entityManager->flush();

                // Output : success message
                $output->writeln(' <fg=green>Sauvegarde de la question effectuée avec succès ! (ID: ' . $survey_question->getId() . ') </>');
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
            $output->writeln('<fg=yellow> Aucune étape de questionnaire n\'existe pour cet ID (#' . $id_survey_step . ') </>');
        }

        // MOAR space !!
        $output->writeln('');
    }
}
