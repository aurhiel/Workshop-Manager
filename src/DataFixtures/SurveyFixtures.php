<?php
namespace App\DataFixtures;

// Entities
use App\Entity\Survey;
use App\Entity\SurveyStep;
use App\Entity\SurveyGrade;
use App\Entity\SurveyQuestion;
// use App\Entity\UserVSI;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;

class SurveyFixtures extends Fixture implements OrderedFixtureInterface
{

    public function load(ObjectManager $em)
    {
        // Create survey
        $survey = new Survey();
        // Survey set fields
        $survey->setLabel('VSI #1');
        $survey->setSlug('vsi-1');
        $survey->setIsDefault(true);
        // Flush survey
        $em->persist($survey);

        // Create survey steps
        $survey_steps = array(
            array( 'label' => 'Contenu global de la prestation'),
            array( 'label' => 'Animation'),
            array( 'label' => 'Conditions Matérielles'),
            array( 'label' => 'Appréciation globales'),
        );
        foreach ($survey_steps as $key => &$s) {
            $survey_step  = new SurveyStep();
            $position     = $key + 1;
            // Survey step fields
            $survey_step->setLabel($s['label']);
            $survey_step->setPosition($position);
            $survey_step->setSurvey($survey);
            // Persist survey step
            $em->persist($survey_step);
            // Store entity & things for later use
            $s['position'] = $position;
            $s['entity']   = $survey_step;
        }

        // Create survey grades
        $survey_grades = array(
            array( 'label' => 'Totalement insatisfaisant',  'value' => '1'),
            array( 'label' => 'Insatisfaisant',             'value' => '2'),
            array( 'label' => 'Peu satisfaisant',           'value' => '3'),
            array( 'label' => 'Satisfaisant',               'value' => '4'),
            array( 'label' => 'Très satisfaisant',          'value' => '5'),
        );
        foreach ($survey_grades as $key => $g) {
            $survey_grade = new SurveyGrade();
            $position     = $key + 1;
            // Survey grade fields
            $survey_grade->setLabel($g['label']);
            $survey_grade->setValue($g['value']);
            $survey_grade->setPosition($position);
            $survey_grade->setSurvey($survey);
            // Persist
            $em->persist($survey_grade);
        }

        // Create survey questions
        $survey_questions = array(
            array( 'step_position' => 1, 'label' => "Durée totale"),
            array( 'step_position' => 1, 'label' => "Programme"),
            array( 'step_position' => 1, 'label' => "Apport de connaissances théoriques"),
            array( 'step_position' => 1, 'label' => "Apport de connaissances pratiques"),
            array( 'step_position' => 1, 'label' => "Adéquation avec l'objectif initial de la Prestation"),
            array( 'step_position' => 2, 'label' => "Pédagogie – clarté de l'exposé"),
            array( 'step_position' => 2, 'label' => "Disponibilité et réponses aux attentes"),
            array( 'step_position' => 2, 'label' => "Supports utilisés"),
            array( 'step_position' => 2, 'label' => "Interactions dans le groupe, vie de groupe"),
            array( 'step_position' => 3, 'label' => "Locaux"),
            array( 'step_position' => 3, 'label' => "Taille du groupe"),
            array( 'step_position' => 3, 'label' => "Matériel mis à disposition"),
            array( 'step_position' => 3, 'label' => "Organisation générale"),
            array( 'step_position' => 3, 'label' => "Disponibilité, diffusion informations"),
            array( 'step_position' => 4, 'label' => "Avis global Modules du parcours socle"),
            array( 'step_position' => 4, 'label' => "Avis Module carte 1 : Dim individuelle"),
            array( 'step_position' => 4, 'label' => "Avis Module carte 2 : Dim collective"),
            array( 'step_position' => 4, 'label' => "Avis Module carte 3 : Dim Entreprise"),
            array( 'step_position' => 4, 'label' => "Avis Module carte 4 : Valoriser son image"),
            array( 'step_position' => 4, 'label' => "Avis Module carte 5 : code entreprises"),
            array( 'step_position' => 4, 'label' => "Avis global sur le questionnaire Assessfirts"),
            array( 'step_position' => 4, 'label' => "Avis global sur la prestation"),
            array( 'step_position' => 4, 'label' => "Cette prestation vous a-t-elle permis de repérer vos qualités professionnelles ?"),
            array( 'step_position' => 4, 'label' => "Cette prestation vous a-t-elle permis d'engager un travail sur vous-même ?"),
            array( 'step_position' => 4, 'label' => "Pensez-vous avoir progressé ?"),
            array( 'step_position' => 4, 'label' => "Pensez-vous avoir besoin d’approfondir les notions abordées ?"),
        );
        $previous_step_position = false;
        foreach ($survey_questions as $q) {
            // Reset key every time step position is changing
            if ($previous_step_position != $q['step_position'])
                $key = 0;
            $position = ($key == 0) ? 1 : $key * 5;

            // Get survey step & save question if step found
            $survey_step = $this->getStepFromPosition($survey_steps, $q['step_position']);
            if ($survey_step !== null && $survey_step !== false) {
                $survey_question = new SurveyQuestion();
                // Survey question fields
                $survey_question->setLabel($q['label']);
                $survey_question->setPosition($position);
                $survey_question->setSurveyStep($survey_step);
                // Persist
                $em->persist($survey_question);
            }

            $previous_step_position = $q['step_position'];
            $key++;
        }

        // And one flush to save them all :3
        $em->flush();
    }

    public function getOrder()
    {
        return 3;
    }

    private function getStepFromPosition($steps, $position)
    {
        if (is_array($steps) && (int)$position > 0) {
            foreach ($steps as $step) {
                if ($position == $step['position']) {
                    return $step['entity'];
                }
            }
            return null;
        }
        return false;
    }
}
