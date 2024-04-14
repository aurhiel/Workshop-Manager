<?php

namespace App\Controller;

// Entities
use App\Entity\UserVSI;
use App\Entity\Survey;
use App\Entity\SurveyToken;
use App\Entity\SurveyStep;
use App\Entity\SurveyQuestion;
use App\Entity\SurveyAnswer;
use App\Entity\WorkshopSubscribe;

// Misc. stuff
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;

class SurveyController extends Controller
{
    /**
     * @Route("/questionnaire/{token}/{step_position}", name="survey", defaults={"token"=null,"step_position"=1}, requirements={"step_position"="\d+"})
     */
    public function index($token, $step_position, Request $request)
    {
        $em             = $this->getDoctrine()->getManager();
        $step_position  = (int) $step_position;
        $step_todo      = $step_position;

        // Check if token is valid
        $rst = $em->getRepository(SurveyToken::class);
        $survey_token = $rst->findOneByToken($token);

        if (!is_null($survey_token) && $survey_token->isTokenValid($token)) {
            $date_expires_at = new \DateTime();
            $date_expires_at->setTimestamp($survey_token->getExpiresAt());
            $nb_days_left   = $date_expires_at->diff(new \DateTime())->days;
            $nb_hours_left  = ($nb_days_left < 1) ? $date_expires_at->diff(new \DateTime())->h : null;

            $survey   = $survey_token->getSurvey();
            $survey_grade_workshops = $survey->getEnableWorkshopsGrade();
            $user_vsi = $survey_token->getUserVSI();

            $rsq = $em->getRepository(SurveyQuestion::class);
            $rsa = $em->getRepository(SurveyAnswer::class);

            $nb_question_by_steps = $rsq->countByStep($survey->getId());
            $nb_answer_by_steps   = $rsa->countByStep($user_vsi);
            $total_nb_steps       = count($nb_question_by_steps);
            $ws_step_position     = null;

            // Increase total nb steps if survey require workshops grading
            if ($survey_grade_workshops === true) {
                $rws = $em->getRepository(WorkshopSubscribe::class);
                $user_vsi_subs = $rws->findByUserVSI($user_vsi);
                $nb_subs = count($user_vsi_subs);

                // Add workshops grading steps ONLY IF the VSI user has been subscribe to a workshop
                if ($nb_subs > 0) {
                    $ws_step_position = $total_nb_steps;
                    $total_nb_steps++;
                    $ws_step_position_in_db = $total_nb_steps;
                }
            }

            $redirect_to_final = ($step_position > $total_nb_steps);
            // Check if last step is completed then redirect to final page
            if (count($nb_answer_by_steps) >= $total_nb_steps) {
                $last_answers   = end($nb_answer_by_steps);
                $last_questions = end($nb_question_by_steps);

                $redirect_to_final = ((int)$last_answers['answer_count'] >= (int)$last_questions['question_count']);

                // Reset arrays
                reset($nb_answer_by_steps);
                reset($nb_question_by_steps);
            }

            if ($redirect_to_final == false) {
                //--
                $this->formatByStepId($nb_question_by_steps);
                $this->formatByStepId($nb_answer_by_steps);

                // Retrieve step data, questions, grades and user's answers
                // $steps = $survey->getSurveySteps();
                $grades = $this->formatById($survey->getSurveyGrades());

                // If survey require workshop grading we must force a fake step
                //  to save answers based on workshops
                if (!is_null($ws_step_position) && $step_position == $ws_step_position) {
                    $step_id = 'ws';
                    // Create the fake step
                    $step = [
                      'id'        => $step_id,
                      'position'  => $ws_step_position,
                      'label'     => 'Avis sur les ateliers' // TODO add translation
                    ];

                    // Retrieve user's workshop
                    $user_workshops = array();
                    foreach ($user_vsi_subs as $sub) {
                        $user_workshop = $sub->getWorkshop();
                        $user_workshops[$user_workshop->getId()] = $user_workshop;
                    }

                    // Create workshops questions or retrieve them
                    $questions          = array();
                    $questions_existing = $rsq->findByWorkshop(array_keys($user_workshops));
                    $question_pos       = 1;
                    foreach ($user_workshops as $u_workshop) {
                        $question_already_exist = false;

                        foreach ($questions_existing as $question_e) {
                            if ($question_e->getWorkshop()->getId() == $u_workshop->getId()) {
                                // Store existing question and break loop (2) to avoid creating a new question
                                $questions[] = $question_e;
                                $question_pos++;
                                $question_already_exist = true;
                                break(1);
                            }
                        }

                        if ($question_already_exist == false) {
                            // Create new question
                            $question = new SurveyQuestion();
                            $question->setLabel('Atelier "' . $u_workshop->getTheme()->getName() . '" du ' .
                              $u_workshop->getDateStart()->format('d/m/Y')); // TODO add translation
                            $question->setPosition($question_pos);
                            $question->setWorkshop($u_workshop);
                            // Workshops questions aren't linked to any step ('cause it's a fake one)
                            // $question->setSurveyStep($survey_step);

                            // Persist
                            $em->persist($question);

                            $questions[] = $question;
                            $question_pos++;
                        }
                    }

                    // Flush new questions
                    $em->flush();

                    // Format questions by ID
                    $questions = $this->formatById($questions);
                    $answers = $rsa->findByUserVSIAndByWorkshops($user_vsi, array_keys($user_workshops));
                } else {
                    $step       = $survey->getStepFromPosition(((!is_null($ws_step_position) && $step_position > $ws_step_position) ? ($step_position - 1) : $step_position));
                    $step_id    = $step->getId();
                    $questions  = $this->formatById($step->getSurveyQuestions());
                    // Retrieve user's answers according to step position
                    $answers    = $rsa->findByUserVSIAndStepPosition($user_vsi, $step_position);
                }

                // Format answers by ID
                $this->formatAnswersByQuestionId($answers);

                // Answers submit handle (insert & update)
                $action = (null !== $request->request->get('action')) ? $request->request->get('action') : $request->query->get('action');
                if (null !== $action && 'next' == $action) {
                    // Loop on request variables and matche only questions to save them and set user grade
                    foreach ($request->request as $key => $grade_id) {
                        if (preg_match('/question-([0-9]+)/', $key, $matches)) {
                            $question_id  = (int)$matches[1];
                            $answer       = isset($answers[$question_id]) ? $answers[$question_id] : new SurveyAnswer();

                            // Set answer data
                            $answer->setUserVSI($user_vsi);
                            $answer->setSurveyQuestion($questions[$question_id]);
                            $answer->setSurveyGrade($grades[$grade_id]);

                            // Persist answer into db
                            $em->persist($answer);

                            // Create answers array for later redirection if needed
                            if (!isset($nb_answer_by_steps[$step_id]))
                              $nb_answer_by_steps[$step_id] = array(
                                'survey_step_id'        => $step_id,
                                'survey_step_position'  => $step_position,
                                'answer_count'          => 0);

                            // Increment nb answers
                            $nb_answer_by_steps[$step_id]['answer_count']++;
                        }
                    }
                    // Flush
                    $em->flush();
                    $step_todo++;
                }

                // Check choosen step to auto-redirect to previous user history
                if ($action == null) {
                    $step_todo = 1;
                    foreach ($nb_question_by_steps as $survey_step_id => $fields) {
                        if (isset($nb_answer_by_steps[$survey_step_id])) {
                            $fields_answer = $nb_answer_by_steps[$survey_step_id];
                            if ((int) $fields_answer['answer_count'] < (int) $fields['question_count']) {
                                $step_todo = $fields['survey_step_position'];
                                break;
                            } else {
                                $step_todo++;
                            }
                        } else {
                            // No step left
                            break;
                        }
                    }
                }

                // Step is incorrect or passed > Redirect to the right one
                if ($step_todo != $step_position && ($action == null || $action == 'next')) {
                    return $this->redirectToRoute('survey', array(
                        'token'         => $token,
                        'step_position' => $step_todo,
                        'action'        => 'redirect' // Disable check step auto-redirection to user history (see above)
                    ));
                }

                if ($step !== null) {
                    // Display survey
                    return $this->render('survey/index.html.twig', array(
                        'stylesheets' => array('survey.css'),
                        'scripts'     => array('survey.js'),
                        'meta'        => array(
                            'title' => 'Étape ' . $step_position . '/' . $total_nb_steps . ' - ' .$survey->getLabel()
                        ),
                        'token'     => $token,
                        'user_vsi'  => $user_vsi,
                        'survey'    => $survey,
                        'step'      => $step,
                        'step_position' => $step_position,
                        'nb_steps'  => $total_nb_steps,
                        'questions' => $questions,
                        'answers'   => $answers,
                        'grades'    => $grades,
                        'date_expires_at' => $date_expires_at,
                        'nb_days_left'    => $nb_days_left,
                        'nb_hours_left'   => $nb_hours_left
                    ));
                } else {
                    // TODO error step invalid, display error ? redirect ?
                }
            } else {
                return $this->redirectToRoute('survey_final', array(
                    'token' => $token
                ));
            }
        } else {
            $fb = $request->getSession()->getFlashBag();

            $msg = 'Le lien du questionnaire est invalide, veuillez prendre contact avec votre référent si vous souhaitez plus d\'informations.';
            // Create error message, with more info if token is just expired
            if (!is_null($survey_token) && $survey_token->hasExpired()) {
                $user_vsi = $survey_token->getUserVSI();
                $referent = $user_vsi->getReferentConsultant();

                $msg = 'Votre accès au questionnaire a expiré.<br>
                  Veuillez contacter votre référent&middot;e <b>' . $referent->getLastname() . ' ' . $referent->getFirstname() . '</b>
                  si vous n\'avez pas pu le terminer.';
            }

            // Push error message in the flashbag
            $fb->add('warning', $msg);

            return $this->redirectToRoute('home');
        }
    }

    /**
     * @Route("/questionnaire/{token}/final", name="survey_final", defaults={"token"=null})
     */
    public function final($token, Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        // Check if token is valid
        $rst = $em->getRepository(SurveyToken::class);
        $survey_token = $rst->findOneByToken($token);

        if ($survey_token->isTokenValid($token)) {
            $survey   = $survey_token->getSurvey();
            $user_vsi = $survey_token->getUserVSI();

            return $this->render('survey/final.html.twig', array(
                'stylesheets' => array('survey.css'),
                'scripts'     => array('survey.js'),
                'meta'        => array(
                    'title' => $survey->getLabel()
                ),
                'token'     => $token,
                'user_vsi'  => $user_vsi,
                'survey'    => $survey
            ));
        } else {
            // Invalid token TODO display error message ?
            return $this->redirectToRoute('home');
        }
    }


    private function formatByStepId(&$array)
    {
        if (is_array($array)) {
            $temp = array();
            foreach($array as $fields) {
                $temp[$fields['survey_step_id']] = $fields;
            }
            $array = $temp;
        }
    }

    private function formatAnswersByQuestionId(&$answers)
    {
        if (is_array($answers)) {
            $temp = array();
            foreach($answers as $fields) {
                $temp[$fields->getSurveyQuestion()->getId()] = $fields;
            }
            $answers = $temp;
        }
    }

    private function formatById($collection)
    {
        if (null !== $collection) {
            $temp = array();
            foreach($collection as $fields) {
                $temp[$fields->getId()] = $fields;
            }
            return $temp;
        }
        return false;
    }
}
