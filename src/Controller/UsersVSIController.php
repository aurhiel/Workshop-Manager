<?php

namespace App\Controller;

// Types
use App\Form\UserVSIType;

// Entities
use App\Entity\User;
use App\Entity\UserVSI;
use App\Entity\Survey;
use App\Entity\SurveyGrade;
use App\Entity\SurveyToken;

// Repositories
use App\Repository\UserRepository;
use App\Repository\UserVSIRepository;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
// TranslatorInterface $translator
use Symfony\Component\Translation\TranslatorInterface;

use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

class UsersVSIController extends Controller
{

    const NB_USERS_BY_PAGE = 12;

    /**
     * @Route("/dashboard/utilisateurs/VSI/gerer", name="admin_dashboard_users_VSI_manage")
     */
    public function manage(AuthorizationCheckerInterface $authChecker, Request $request)
    {
        $id_entity      = (int) $request->request->get('id'); // Edit entity ?
        $entityManager  = $this->getDoctrine()->getManager();
        $repoEntity     = $entityManager->getRepository(UserVSI::class);

        if(!empty($id_entity) && $id_entity > 0) {
            // Get Entity to edit
            $is_new             = false;
            $entity             = $repoEntity->find($id_entity);
            $message_status_ok  = 'L\'utilisateur VSI a bien été modifiée';
            $message_status_nok = 'Un problème est survenu lors de la modification de l\'utilisateur VSI';
            $old_email          = $entity->getEmail();
        } else {
            // New Entity
            $is_new             = true;
            $entity             = new UserVSI();
            $message_status_ok  = 'L\'utilisateur VSI a bien été ajoutée';
            $message_status_nok = 'Un problème est survenu lors de l\'ajout de l\'utilisateur VSI';
        }

        // 1) Build the form
        $form_entity = $this->createForm(UserVSIType::class, $entity);

        // 2) Handle request
        $form_entity->handleRequest($request);
        $data = array();

        $email_exist = (!empty($repoEntity->findByEmail($entity->getEmail())));
        // 3.1) When editing an user check if the email already exist
        //        ONLY if email has changed
        if (false === $is_new && $old_email == $entity->getEmail())
            $email_exist = false;

        // 3.2) Check if user already exist and return error message if so
        if ($email_exist) {
            $entityManager->clear();
            $data = array(
                'query_status'    => 0,
                'message_status'  => 'L\'adresse email "' . $entity->getEmail() . '" est déjà utlisée, merci d\'en utiliser une autre.'
            );
        } elseif ($form_entity->isSubmitted() && $form_entity->isValid()) {
            // 4) Save !
            $entityManager->persist($entity);

            // 5) Try to save (flush) or clear
            try {
                // Flush OK !
                $entityManager->flush();

                $data = array(
                    'query_status'    => 1,
                    'message_status'  => $message_status_ok,
                    'id_entity'       => $entity->getId(),
                    'is_new_entity'   => $is_new,
                    'form_data'       => $this->formatDataForStealthRaven($entity)
                );

                // Clear/reset form
                $form_entity = $this->createForm(UserVSIType::class, $entity);
            } catch (\Exception $e) {
                // Something goes wrong
                $entityManager->clear();
                $data = array(
                    'query_status'    => 0,
                    'exception'       => $e->getMessage(),
                    'message_status'  => $message_status_nok
                );
            }
        }

       if ($request->isXmlHttpRequest()) {
          // 6.1) Return JSON data
          return $this->json($data);
       } else {
          // 6.2) Redirect to home entities if direct access to the page
          return $this->redirectToRoute('admin_dashboard_users_VSI');
       }
    }

    /**
     * @Route("/dashboard/utilisateurs/VSI/resultats-questionnaire/{id_consultant}/{id_cohort}/{survey_slug}/{date_start}/{date_end}", name="admin_dashboard_users_VSI_survey_results", defaults={"id_consultant"=null,"id_cohort"=null,"survey_slug"=null,"date_start"=null,"date_end"=null})
     */
    public function surveyResults($id_consultant, $id_cohort, $survey_slug, $date_start, $date_end, Request $request, Security $security, AuthorizationCheckerInterface $authChecker, TranslatorInterface $translator)
    {
        // Default values
        $user           = $security->getUser();
        $entityManager  = $this->getDoctrine()->getManager();

        // Filters + Pagination
        $r = $request->request;
        // Users VSI filter's default values
        $user_vsi_filters = array(
            'id_consultant' => (int)(!is_null($id_consultant) ? $id_consultant : ($user->getIsConsultant() ? $user->getId() : 0)),
            'id_cohort'     => (!is_null($id_cohort) ? $id_cohort : 'all'),
            'survey_slug'   => (!is_null($survey_slug) ? $survey_slug : Survey::DEFAULT_SURVEY_SLUG),
            'date_start'    => (!is_null($date_start) ? $date_start : date('Y-01-01')),
            'date_end'      => (!is_null($date_end) ? $date_end : date('Y-12-31'))
        );

        // Get submitted filters & redirect with filters in the current URI
        if ($r->get('user_vsi_filter_submit') === 'send') {
            $user_vsi_filters['id_consultant'] = (int) $r->get('user_vsi_filter_consultant');

            if (!empty($r->get('user_vsi_filter_date_start')))
                $user_vsi_filters['date_start'] = $r->get('user_vsi_filter_date_start');

            if (!empty($r->get('user_vsi_filter_date_end')))
                $user_vsi_filters['date_end'] = $r->get('user_vsi_filter_date_end');

            if (!empty($r->get('user_vsi_filter_id_cohort')))
                $user_vsi_filters['id_cohort'] = $r->get('user_vsi_filter_id_cohort');

            if (!empty($r->get('user_vsi_filter_survey_slug')))
                $user_vsi_filters['survey_slug'] = $r->get('user_vsi_filter_survey_slug');

            // Date end is inferior to date start so we need to reverse them
            if (strtotime($user_vsi_filters['date_end']) < strtotime($user_vsi_filters['date_start'])) {
                $date_end = $user_vsi_filters['date_start'];
                $user_vsi_filters['date_start'] = $user_vsi_filters['date_end'];
                $user_vsi_filters['date_end']   = $date_end;
            }

            // Redirect to keep filters in URI
            return $this->redirectToRoute('admin_dashboard_users_VSI_survey_results', $user_vsi_filters);
        }

        // Get repositories
        $repoUserVSI  = $entityManager->getRepository(UserVSI::class);
        $repoUser     = $entityManager->getRepository(User::class);
        $repoSurvey   = $entityManager->getRepository(Survey::class);

        // Get consultants & current consultant in the filter
        $consultants = $repoUser->findConsultant(true);
        // // current
        $current_consultant = null;
        foreach ($consultants as $consul) {
            if ($user_vsi_filters['id_consultant'] == $consul->getId())
                $current_consultant = $consul;
        }

        // Get survey data
        $survey               = $repoSurvey->findOneBySlug($user_vsi_filters['survey_slug']);
        $survey_nb_questions  = $survey->getSurveyQuestionsCount();
        $survey_grades        = $survey->getSurveyGrades();
        $grades_answers_count = array();
        $survey_steps         = array();

        // Get VSI users filtered
        $usersVSI = $repoUserVSI->findByConsultantAndSurveySlugAndDateBetween(
            $user_vsi_filters['id_consultant'],
            $user_vsi_filters['survey_slug'],
            $user_vsi_filters['date_start'],
            $user_vsi_filters['date_end'],
            $user_vsi_filters['id_cohort'],
            null,
            null
        );
        // TODO add classic users ? (new incoming feature)
        $participants = $usersVSI;

        $total_nb_participants = 0;
        $total_answers = 0;
        $total_average = 0;
        $total_std_deviation = 0;

        $survey_steps_labels = array();
        $survey_steps_averages = array();
        $survey_steps_std_deviations = array();

        // Get data for survey results (% & co)
        foreach ($participants as $user) {
            $answers = $user->getSurveyAnswers();
            // Only get data from users who complete the survey
            if (count($answers) >= $survey_nb_questions) {
                foreach ($answers as $answer) {
                    $grade    = $answer->getSurveyGrade();
                    $question = $answer->getSurveyQuestion();
                    $step     = $question->getSurveyStep();
                    if (!is_null($step)) {
                        $step_id    = $step->getId();
                        $step_label = $step->getLabel();
                    } else {
                        $step_id    = 'ws';
                        $step_label = 'Avis sur les ateliers'; // TODO add translation
                    }

                    // Create default grades answers counter array
                    if (!isset($grades_answers_count[$grade->getId()]))
                        $grades_answers_count[$grade->getId()] = 0;

                    // Create default current step data
                    if (!isset($survey_steps[$step_id])) {
                        $survey_steps[$step_id] = array(
                          'step_label'    => $step_label,
                          'average'       => 0,
                          'std_deviation' => 0,
                          'answers'       => array(),
                          'questions'     => array()
                        );
                    }

                    // Add step answer (grade) value
                    $survey_steps[$step_id]['answers'][] = (int)$grade->getValue();

                    // Create default survey steps questions (to count answers by questions & grades)
                    if (!isset($survey_steps[$step_id]['questions'][$question->getId()])) {
                        $survey_steps[$step_id]['questions'][$question->getId()] = array(
                          'question_label'          => $question->getLabel(),
                          'grades_labels'           => array(),
                          'grades_answers_count'    => array(),
                          'grades_answers_percent'  => array()
                        );
                        foreach ($survey_grades as $s_grade) {
                            $survey_steps[$step_id]['questions'][$question->getId()]['grades_labels'][$s_grade->getId()] = $s_grade->getLabel();
                            $survey_steps[$step_id]['questions'][$question->getId()]['grades_answers_count'][$s_grade->getId()] = 0;
                        }
                    }

                    // Update counter for answers by questions & grades
                    $survey_steps[$step_id]['questions'][$question->getId()]['grades_answers_count'][$grade->getId()]++;

                    // Update total average & standard deviation
                    $total_average += (int)$grade->getValue();

                    // Update answers amount by grades
                    $grades_answers_count[$grade->getId()]++;

                    // Update nb answers
                    $total_answers++;
                }

                // Update nb participants
                $total_nb_participants++;
            }
        }

        if ($total_answers > 0) {
            $total_average = $total_average / $total_answers;

            // Get useful data by steps & set Chart JS data
            foreach ($survey_steps as &$step) {
                $average = array_sum($step['answers']) / count($step['answers']);
                $std_deviation = 0;
                foreach ($step['answers'] as $answer_value) {
                    $std_deviation += pow((int)$answer_value - $average, 2);
                    $total_std_deviation += pow((int)$answer_value - $average, 2);
                }
                $std_deviation = sqrt($std_deviation / count($step['answers']));

                // Assign average & standard deviation
                $step['average']       = round($average, 2);
                $step['std_deviation'] = round($std_deviation, 2);

                // Get answers percent by grades for each questions
                foreach ($step['questions'] as $id_question => &$question) {
                    $q_total_answers = array_sum($question['grades_answers_count']);
                    foreach ($question['grades_answers_count'] as $id_grade => $answers_count) {
                        $question['grades_answers_percent'][$id_grade] = round(($answers_count * 100 / $q_total_answers), 2);
                    }
                }

                // Set data for ChartJS
                $survey_steps_labels[]   = $step['step_label'];
                $survey_steps_averages[] = $step['average'];
                $survey_steps_std_deviations[] = $step['std_deviation'];
            }
            // Update total standard deviation
            $total_std_deviation = sqrt($total_std_deviation / $total_answers);
        }

        // Some key statistics
        $key_stats = array(
            'total_nb_participants' => array(
              'label' => 'Nombre de participants',
              'value' =>  $total_nb_participants . ' / ' . count($participants)
            ),
            'contribution_percent' => array(
              'label' => 'Taux de participation',
              'value' => ((count($participants) > 0) ? round($total_nb_participants * 100 / count($participants), 2) : 0) . '%'
            ),
            'total_average' => array(
              'label' => 'Moyenne totale',
              'value' => round($total_average, 1)
            ),
            'total_std_deviation' => array(
              'label' => 'Écart-type total',
              'value' => round($total_std_deviation, 1)
            ),
        );

        // Display page with twig & assign values
        return $this->render('dashboard/users/vsi/survey-results.html.twig', array(
            'meta'        => array('title' => 'Résultats du questionnaire'),
            'stylesheets' => array('admin-dashboard.css', 'survey-results.css'),
            'scripts'     => array('admin-dashboard.js', 'survey-results.js'),
            'breadcrumb_links' => array(
              [
                'label' => 'Dashboard',
                'href'  => $this->generateUrl('dashboard')
              ],
              [
                'label' => 'Utilisateurs VSI',
                'href'  => $this->generateUrl('admin_dashboard_users_VSI', array(
                  'id_consultant' => $id_consultant,
                  'id_cohort'     => $id_cohort,
                  'date_start'    => $date_start,
                  'date_end'      => $date_end
                ))
              ],
            ),
            'user_vsi_filters'    => $user_vsi_filters,
            'filters_form_action' => $this->generateUrl('admin_dashboard_users_VSI_survey_results'),
            'consultants'         => $consultants,
            'current_consultant'  => $current_consultant,
            // Survey data
            'surveys'             => $repoSurvey->findAll(),
            'survey_grades'       => $survey_grades,
            // Statistics !
            'participants'          => $participants,
            'grades_answers_count'  => $grades_answers_count,
            'total_answers'         => $total_answers,
            'steps_stats'           => $survey_steps,
            'key_stats'             => $key_stats,
            'steps_answers_labels'          => $survey_steps_labels,
            'steps_answers_averages'        => $survey_steps_averages,
            'steps_answers_std_deviations'  => $survey_steps_std_deviations
        ));
    }


    /**
     * @Route("/dashboard/utilisateurs/VSI/supprimer/{id}", name="admin_dashboard_users_VSI_del")
     */
    public function delete(UserVSI $entity, AuthorizationCheckerInterface $authChecker, Request $request)
    {
        if(!empty($entity)) {
          $entityManager      = $this->getDoctrine()->getManager();
          $id_entity_deleted  = $entity->getId();

          // Delete entity
          $entityManager->remove($entity);

          // Try to save (flush) or clear
          try {
            // Flush OK !
            $entityManager->flush();
            $data = array(
              'query_status'  => 99, // = force reload in front (see Stealth Raven JS module)
              'id_entity'     => $id_entity_deleted,
            );
          } catch (\Exception $e) {
            // Something goes wrong
            $entityManager->clear();
            $data = array(
              'query_status'    => 0,
              'exception'       => $e->getMessage(),
              'message_status'  => 'Un problème est survenu lors de la suppression de l\'utilisateur VSI'
            );
          }
        } else {
          $data = array(
            'query_status'    => 0,
            'message_status'  => 'Aucun utilisateur VSI n\'existe pour cet ID'
          );
        }

        if ($request->isXmlHttpRequest()) {
          return $this->json($data);
        } else {
          // Not accessible by URL
          return $this->redirectToRoute('admin_dashboard_users_VSI');
        }
    }


    /**
     * @Route("/dashboard/utilisateurs/VSI/get/{id}", name="admin_dashboard_users_VSI_get")
     */
    public function show(UserVSI $entity, AuthorizationCheckerInterface $authChecker, Request $request)
    {
        // Set query status
        $data = array( 'query_status' => (!empty($entity) ? 1 : 0) );

         if ($request->isXmlHttpRequest()) {
            $data['message_status'] = 'Un problème est survenu lors de la récupération de l\'utilisateur VSI';

            if ($data['query_status'] == 1) {
              $data['id_entity'] = $entity->getId();
              $data['form_data'] = $this->formatDataForStealthRaven($entity);

              // No problem = no message
              unset($data['message_status']);
            }

            return $this->json($data);
         } else {
            // No direct access
            return $this->redirectToRoute('admin_dashboard_addresses');
         }
    }


    /**
     * @Route("/dashboard/utilisateurs/VSI/resultats/{id}", name="admin_dashboard_users_VSI_user_survey_results")
     */
    public function userSurveyResults(UserVSI $userVSI, AuthorizationCheckerInterface $authChecker, Request $request)
    {
        // Default values
        $entityManager  = $this->getDoctrine()->getManager();
        $answers        = $userVSI->getSurveyAnswers();
        $nb_answers     = count($answers);

        if ($nb_answers < 1)
            return $this->redirectToRoute('admin_dashboard_users_VSI');

        // Get repositories
        $repoUserVSI  = $entityManager->getRepository(UserVSI::class);
        $repoUser     = $entityManager->getRepository(User::class);
        $repoSurvey   = $entityManager->getRepository(Survey::class);

        // Retrieve survey id base on first user answer (TODO find a better way ? Issue with multiple survey ?)
        $survey_id = $answers[0]->getSurveyQuestion()->getSurveyStep()->getSurvey()->getId();

        // Get survey data
        $survey               = $repoSurvey->findOneById($survey_id);
        $survey_nb_questions  = $survey->getSurveyQuestionsCount();
        $survey_grades        = $this->formatById($survey->getSurveyGrades());
        $grades_answers_count = array();
        $survey_steps         = array();

        $total_answers          = 0;
        $total_average          = 0;
        $total_std_deviation    = 0;

        $survey_steps_labels          = array();
        $survey_steps_averages        = array();
        $survey_steps_std_deviations  = array();

        // NOTE: Determines $url_back_to_usersVSI according to referer value
        // If $referer is null or not $referer isn't users VSI list's homepage
        //   redirect to generic users VSI list's homepage (without filters)
        $referer = $request->headers->get('referer');
        if (is_null($referer) || (preg_match('/utilisateurs\/VSI\//', $referer) < 0)) {
            $url_back_to_usersVSI = $this->generateUrl('admin_dashboard_users_VSI');
        } else {
            $url_back_to_usersVSI = $referer;
        }

        if ($nb_answers >= $survey_nb_questions) {
            // Loop on every user's answers to retrieve useful datasets
            foreach ($answers as $answer) {
                $grade    = $answer->getSurveyGrade();
                $question = $answer->getSurveyQuestion();
                $step     = $question->getSurveyStep();
                if (!is_null($step)) {
                    $step_id    = $step->getId();
                    $step_label = $step->getLabel();
                } else {
                    $step_id    = 'ws';
                    $step_label = 'Avis sur les ateliers'; // TODO add translation
                }

                // Create default grades answers counter array
                if (!isset($grades_answers_count[$grade->getId()]))
                    $grades_answers_count[$grade->getId()] = 0;

                // Create default current step data
                if (!isset($survey_steps[$step_id]))
                    $survey_steps[$step_id] = array(
                      'step_label'    => $step_label,
                      'average'       => 0,
                      'std_deviation' => 0,
                      'answers'       => array(),
                      'questions'     => array()
                    );

                // Add step answer (grade) value
                $survey_steps[$step_id]['answers'][] = (int)$grade->getValue();

                // Create default survey steps questions (to count answers by questions & grades)
                if (!isset($survey_steps[$step_id]['questions'][$question->getId()])) {
                    $survey_steps[$step_id]['questions'][$question->getId()] = array(
                      'question_label'          => $question->getLabel(),
                      'grades_labels'           => array(),
                      'grades_answers_count'    => array()
                    );
                    foreach ($survey_grades as $s_grade) {
                        $survey_steps[$step_id]['questions'][$question->getId()]['grades_labels'][$s_grade->getId()] = $s_grade->getLabel();
                        $survey_steps[$step_id]['questions'][$question->getId()]['grades_answers_count'][$s_grade->getId()] = 0;
                    }
                }

                // Update counter for answers by questions & grades
                $survey_steps[$step_id]['questions'][$question->getId()]['grades_answers_count'][$grade->getId()]++;

                // Update total average & standard deviation
                $total_average += (int)$grade->getValue();

                // Update answers amount by grades
                $grades_answers_count[$grade->getId()]++;

                // Update nb answers
                $total_answers++;
            }

            // Get MOAR data for each steps
            if ($total_answers > 0) {
                $total_average = $total_average / $total_answers;

                // Get useful data by steps & set Chart JS data
                foreach ($survey_steps as &$step) {
                    $average = array_sum($step['answers']) / count($step['answers']);
                    $std_deviation = 0;
                    foreach ($step['answers'] as $answer_value) {
                        $std_deviation += pow((int)$answer_value - $average, 2);
                        $total_std_deviation += pow((int)$answer_value - $average, 2);
                    }
                    $std_deviation = sqrt($std_deviation / count($step['answers']));

                    // Assign average & standard deviation
                    $step['average']       = round($average, 2);
                    $step['std_deviation'] = round($std_deviation, 2);

                    // Set data for ChartJS
                    $survey_steps_labels[]   = $step['step_label'];
                    $survey_steps_averages[] = $step['average'];
                    $survey_steps_std_deviations[] = $step['std_deviation'];
                }
                // Update total standard deviation
                $total_std_deviation = sqrt($total_std_deviation / $total_answers);
            }

            // Some key statistics
            $key_stats = array(
                'consultant' => array(
                    'label' => 'Consultant&middot;e référent&middot;e',
                    'value' => $userVSI->getReferentConsultant()->getLastname() . ' ' . $userVSI->getReferentConsultant()->getFirstname()
                ),
                'id-vsi' => array(
                    'label' => 'Identifiant VSI',
                    'value' => (!empty($userVSI->getIdVSI()) ? $userVSI->getIdVSI() : '-')
                ),
                'id-cohort' => array(
                    'label' => 'Numéro de Cohorte',
                    'value' => $userVSI->getIdCohort()
                ),
                'workshop-end-date' => array(
                    'label' => 'Date fin des ateliers',
                    'value' => $userVSI->getWorkshopEndDate()->format('d/m/Y')
                ),
                'total_average' => array(
                    'label' => 'Moyenne totale',
                    'value' => round($total_average, 1)
                ),
                'total_std_deviation' => array(
                    'label' => 'Écart-type total',
                    'value' => round($total_std_deviation, 1)
                ),
            );

            // Display page with twig & assign values
            return $this->render('dashboard/users/vsi/user-survey-results.html.twig', array(
                'meta'        => array('title' => $userVSI->getLastname() . ' ' . $userVSI->getFirstname() . ' - Résultats du questionnaire'),
                'stylesheets' => array('admin-dashboard.css', 'survey-results.css'),
                'scripts'     => array('admin-dashboard.js', 'survey-results.js'),
                'breadcrumb_links' => array(
                  [
                    'label' => 'Dashboard',
                    'href'  => $this->generateUrl('dashboard')
                  ],
                  [
                    'label' => 'Utilisateurs VSI',
                    'href'  => $url_back_to_usersVSI
                  ],
                ),
                'user_vsi'      => $userVSI,
                'survey_grades' => $survey_grades,
                // Statistics !
                'grades_answers_count'  => $grades_answers_count,
                'total_answers'         => $total_answers,
                'steps_stats'           => $survey_steps,
                'key_stats'             => $key_stats,
                'steps_answers_labels'          => $survey_steps_labels,
                'steps_answers_averages'        => $survey_steps_averages,
                'steps_answers_std_deviations'  => $survey_steps_std_deviations
            ));
        } else {
            // Users hasn't completed his survey yet > redirect to users VSI homepage
            return $this->redirectToRoute('admin_dashboard_users_VSI');
        }
    }


    /**
     * @Route ("/dashboard/utilisateurs/VSI/{id_consultant}/{id_cohort}/{date_start}/{date_end}/{page}", name="admin_dashboard_users_VSI", defaults={"id_consultant"=null,"id_cohort"=null,"date_start"=null,"date_end"=null,"page"=1})
     */
    public function index($id_consultant, $id_cohort, $date_start, $date_end, $page, Request $request, Security $security, AuthorizationCheckerInterface $authChecker, TranslatorInterface $translator)
    {
        // Default values
        $roles          = array(
            'ROLE_USER'       => $translator->trans('label.plural.role_user'),
            'ROLE_PUBLISHER'  => $translator->trans('label.plural.role_publisher'),
            'ROLE_ADMIN'      => $translator->trans('label.plural.role_admin')
        );
        // No admin = Only classic users edit
        if (false === $authChecker->isGranted('ROLE_ADMIN'))
            $roles = array('ROLE_USER' => $translator->trans('label.plural.role_user'));

        $user           = $security->getUser();
        $entityManager  = $this->getDoctrine()->getManager();

        // Filters + Pagination
        $r = $request->request;
        // Users VSI filter's default values
        $user_vsi_filters = array(
            'id_consultant' => (int)(!is_null($id_consultant) ? $id_consultant : ($user->getIsConsultant() ? $user->getId() : 0)),
            'id_cohort'     => (!is_null($id_cohort) ? $id_cohort : 'all'),
            'date_start'    => (!is_null($date_start) ? $date_start : date('Y-01-01')),
            'date_end'      => (!is_null($date_end) ? $date_end : date('Y-12-31')),
            'page'          => (int) $page
        );

        // Get submitted filters & redirect with filters in the current URI
        if ($r->get('user_vsi_filter_submit') === 'send') {
            $user_vsi_filters['id_consultant'] = (int) $r->get('user_vsi_filter_consultant');

            if (!empty($r->get('user_vsi_filter_date_start')))
                $user_vsi_filters['date_start'] = $r->get('user_vsi_filter_date_start');

            if (!empty($r->get('user_vsi_filter_date_end')))
                $user_vsi_filters['date_end'] = $r->get('user_vsi_filter_date_end');

            if (!empty($r->get('user_vsi_filter_id_cohort')))
                $user_vsi_filters['id_cohort'] = $r->get('user_vsi_filter_id_cohort');

            // Date end is inferior to date start so we need to reverse them
            if (strtotime($user_vsi_filters['date_end']) < strtotime($user_vsi_filters['date_start'])) {
                $date_end = $user_vsi_filters['date_start'];
                $user_vsi_filters['date_start'] = $user_vsi_filters['date_end'];
                $user_vsi_filters['date_end']   = $date_end;
            }

            // Redirect to keep filters in URI
            return $this->redirectToRoute('admin_dashboard_users_VSI', $user_vsi_filters);
        }

        // Build the form for new VSI users
        $entity = new UserVSI();
        $form_entity = $this->createForm(UserVSIType::class, $entity, array(
          // Change action for Stealth Raven Ajax Plugin
          'action' => $this->generateUrl('admin_dashboard_users_VSI_manage')
        ));

        // Get repositories
        $repoUserVSI  = $entityManager->getRepository(UserVSI::class);
        $repoUser     = $entityManager->getRepository(User::class);
        $repoSurvey   = $entityManager->getRepository(Survey::class);

        // Get survey data
        $tmp_surveys = $repoSurvey->findAll();
        $surveys = array();
        $default_survey = null;
        foreach ($tmp_surveys as $survey) {
            $surveys[$survey->getSlug()] = $survey;
            if ($survey->getIsDefault() == true)
                $default_survey = $survey;
        }
        // $survey = $repoSurvey->findOneByIsDefault(true);
        $user_vsi_filters['survey_slug'] = $default_survey->getSlug();

        // Get consultants & current consultant in the filter
        $consultants = $repoUser->findConsultant(true);
        // // current
        $current_consultant = null;
        foreach ($consultants as $consul) {
            if ($user_vsi_filters['id_consultant'] == $consul->getId())
                $current_consultant = $consul;
        }

        // Get consultant last id_cohort inserted
        $id_cohort_default = 1;
        if (!empty($current_consultant)) {
            $last_id_cohort = $repoUserVSI->findLastIdCohortByIdConsultantAndSurveySlugAndDateBetween(
                $current_consultant->getId(),
                $user_vsi_filters['survey_slug'],
                $user_vsi_filters['date_start'],
                $user_vsi_filters['date_end'],
                $user_vsi_filters['id_cohort']
            );
            if (!empty($last_id_cohort)) {
                $id_cohort_default = str_replace($last_id_cohort['numCohort'], (int)$last_id_cohort['numCohort'] + 1, $last_id_cohort['idCohort']);
            }
        }

        // Get nb VSI users
        $nb_users_vsi = (int) $repoUserVSI->countByConsultantAndSurveySlugAndDateBetween(
            $user_vsi_filters['id_consultant'],
            null,
            $user_vsi_filters['date_start'],
            $user_vsi_filters['date_end'],
            $user_vsi_filters['id_cohort']
        );
        // $nb_users_vsi = (int) $nb_users_vsi['nb_users_vsi'];

        // Get nb pages of VSI users
        $nb_pages_raw = ($nb_users_vsi / self::NB_USERS_BY_PAGE);
        $nb_pages = floor($nb_pages_raw);

        // If there is decimal numbers,
        //  there is less than 12 people (=self::NB_USERS_BY_PAGE) to display
        //  > So we need to add 1 more page
        if (($nb_pages_raw - $nb_pages) > 0)
            $nb_pages++;

        // Get VSI users filtered
        $usersVSI = $repoUserVSI->findByConsultantAndSurveySlugAndDateBetween(
            $user_vsi_filters['id_consultant'],
            null,
            $user_vsi_filters['date_start'],
            $user_vsi_filters['date_end'],
            $user_vsi_filters['id_cohort'],
            $user_vsi_filters['page'],
            self::NB_USERS_BY_PAGE
        );

        // Check if $page is correct, if not redirect with a correct page number
        if ($nb_pages > 0 && $user_vsi_filters['page'] > $nb_pages) {
            $user_vsi_filters['page'] = max(1, $user_vsi_filters['page'] - 1);

            // Redirect with correct $page and filters in URI
            return $this->redirectToRoute('admin_dashboard_users_VSI', $user_vsi_filters);
        }

        // Display page with twig & assign values
        return $this->render('dashboard/users/index-vsi.html.twig', array(
            'meta'        => array('title' => 'Utilisateurs VSI'),
            'stylesheets' => array('admin-dashboard.css'),
            'scripts'     => array('admin-dashboard.js'),
            'breadcrumb_links' => array(
              [
                'label' => 'Dashboard',
                'href'  => $this->generateUrl('dashboard')
              ],
            ),
            'current_role'  => 'none',
            'roles'         => $roles,
            'form_user_vsi'     => $form_entity->createView(),
            'id_cohort_default' => $id_cohort_default,
            'users_vsi'         => $usersVSI,
            'nb_users_vsi'      => $nb_users_vsi,
            'current_page'      => $page,
            'nb_pages'          => $nb_pages,
            'consultants'         => $consultants,
            'current_consultant'  => $current_consultant,
            'filters_form_action' => $this->generateUrl('admin_dashboard_users_VSI'),
            'user_vsi_filters'    => $user_vsi_filters,
        ));
    }


    /**
     * @Route("/dashboard/questionnaire/reset-token/{id}", name="admin_dashboard_reset_survey_token")
     */
    public function resetSurveyToken(SurveyToken $surveyToken, Security $security, Request $request)
    {
        if(!empty($surveyToken)) {
            $em               = $this->getDoctrine()->getManager();
            $id_survey_token  = $surveyToken->getId();

            // Reset expires at instead of deleting users survey answers
            $surveyToken->resetExpiresAt(new \DateInterval('P15D'));

            // Try to save (flush) or clear
            try {
                // Flush OK !
                $em->flush();

                // Set data to return
                $answersCount = count($surveyToken->getUserVSI()->getSurveyAnswers());
                $expiresAtDate = new \DateTime();
                $expiresAtDate->setTimestamp($surveyToken->getExpiresAt());
                $data = array(
                    'query_status'    => 1,
                    'id_survey_token' => $id_survey_token,
                    'survey_token'    => $surveyToken->getToken(),
                    'percent_completed' => round($answersCount * 100 / $surveyToken->getSurvey()->getSurveyQuestionsCount()),
                    'expires_at_formatted' => $expiresAtDate->format('d M. Y')
                );
            } catch (\Exception $e) {
                // Something goes wrong
                $em->clear();
                $data = array(
                    'query_status'    => 0,
                    'exception'       => $e->getMessage(),
                    'message_status'  => 'Un problème est survenu lors de la ré-initialisation du token'
                );
            }
        } else {
            $data = array(
                'query_status'    => 0,
                'message_status'  => 'Aucun token n\'existe pour cet ID'
            );
        }

        if ($request->isXmlHttpRequest()) {
            return $this->json($data);
        } else {
            // Not accessible by URL
            return $this->redirectToRoute('admin_dashboard_users_VSI');
        }
    }


    private function formatDataForStealthRaven(UserVSI $entity)
    {
        return array(
            'user_vsi_id'                   => $entity->getId(),
            'user_vsi_idVSI'                => $entity->getIdVSI(),
            'user_vsi_idCohort'             => $entity->getIdCohort(),
            'user_vsi_firstname'            => $entity->getFirstname(),
            'user_vsi_lastname'             => $entity->getLastname(),
            'user_vsi_email'                => $entity->getEmail(),
            'user_vsi_workshop_end_date'    => $entity->getWorkshopEndDate()->format('Y-m-d'),
            'user_vsi_referent_consultant'  => $entity->getReferentConsultant()->getId()
        );
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
