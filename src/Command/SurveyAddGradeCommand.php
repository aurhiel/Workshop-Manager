<?php

namespace App\Command;

// Entities
use App\Entity\Survey;
use App\Entity\SurveyGrade;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\DependencyInjection\ContainerInterface;

class SurveyAddGradeCommand extends ContainerAwareCommand
{
    private $doctrine;

    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'survey:add:grade';

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
            ->setName('survey:add:grade')

            // the short description shown while running "php bin/console list"
            ->setDescription('Allow to add some grades to a survey')

            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp('')

            // set input arguments
            ->addArgument('id_survey',  InputArgument::REQUIRED, 'Survey ID (eg. 99)')
            ->addArgument('label',      InputArgument::REQUIRED, 'Survey grade label (eg. Very satisfy)')
            ->addArgument('value',      InputArgument::REQUIRED, 'Survey grade value (eg. 5)')
            ->addArgument('position',   InputArgument::REQUIRED, 'Survey grade position (eg. 0)')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $entityManager  = $this->doctrine->getManager();
        $repoSurvey     = $entityManager->getRepository(Survey::class);

        // Get arguments & variables
        $label      = $input->getArgument('label');
        $value      = $input->getArgument('value');
        $position   = $input->getArgument('position');
        $id_survey  = $input->getArgument('id_survey');
        $survey     = $repoSurvey->findOneById($id_survey);

        // Output
        $output->writeln('<fg=black;bg=green> [Survey:Add:Grade] Ajout d\'une note à un questionnaire </>');
        $output->writeln('  id_survey: <fg=green>' . $id_survey . '</>, label: <fg=green>' . $label . '</>, position: <fg=green>' . $position . '</>');

        if (!empty($survey)) {
            // Create new survey grade
            $survey_grade = new SurveyGrade();

            // Set survey grade fields
            $survey_grade->setLabel($label);
            $survey_grade->setValue($value);
            $survey_grade->setPosition($position);
            $survey_grade->setSurvey($survey);

            // Persist
            $entityManager->persist($survey_grade);

            // Try to save (flush) or clear
            try {
                // Flush OK !
                $entityManager->flush();

                // Output : success message
                $output->writeln(' <fg=green>Sauvegarde de la note de questionnaire effectuée avec succès ! (ID: ' . $survey_grade->getId() . ') </>');
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
