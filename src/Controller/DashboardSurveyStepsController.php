<?php

namespace App\Controller;

// Types
use App\Form\SurveyType;
use App\Form\SurveyStepType;
use App\Form\SurveyQuestionType;

// Entities
use App\Entity\Survey;
use App\Entity\SurveyStep;
use App\Entity\SurveyQuestion;

// Repositories
// use App\Repository\UserRepository;
// use App\Repository\UserVSIRepository;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;


class DashboardSurveyStepsController extends Controller
{
    /**
     * @Route("/dashboard/utilisateurs/VSI/questionnaires/{id}/etapes", name="admin_dashboard_users_VSI_survey_steps")
     */
    public function list($id)
    {
        $em       = $this->getDoctrine()->getManager();
        $r_survey = $em->getRepository(Survey::class);
        // Get survey
        $survey   = $r_survey->findOneById($id);

        // 1) Build survey step form
        $survey_step = new SurveyStep();
        $survey_step_form = $this->createForm(SurveyStepType::class, $survey_step, array(
          // Change action for Stealth Raven Ajax Plugin
          'action' => $this->generateUrl('admin_dashboard_users_VSI_survey_step_manage')
        ));

        // 2) Build survey question form
        $survey_question = new SurveyQuestion();
        $survey_question_form = $this->createForm(SurveyQuestionType::class, $survey_question, array(
          // Change action for Stealth Raven Ajax Plugin
          'action' => $this->generateUrl('admin_dashboard_users_VSI_survey_question_manage')
        ));

        if (!empty($survey)) {
            return $this->render(
                'dashboard/survey/steps.html.twig',
                array(
                  'meta'        => array(
                    'title' => 'Étapes du questionnaire de satisfaction ' . $survey->getLabel()
                  ),
                  'stylesheets' => array('admin-dashboard.css'),
                  'scripts'     => array('admin-dashboard.js'),
                  'breadcrumb_links' => array(
                    [
                      'label' => 'Dashboard',
                      'href' => $this->generateUrl('dashboard')
                    ],
                    [
                      'label' => 'Utilisateurs VSI',
                      'href'  => $this->generateUrl('admin_dashboard_users_VSI')
                    ],
                    [
                      'label' => 'Questionnaires',
                      'href'  => $this->generateUrl('admin_dashboard_users_VSI_survey_list')
                    ],
                  ),
                  'form_survey_step'      => $survey_step_form->createView(),
                  'form_survey_question'  => $survey_question_form->createView(),
                  'survey'                => $survey
                )
            );
        } else {
            // Survey not found > Redirect to surveys homepage
            return $this->redirectToRoute('admin_dashboard_users_VSI_survey_list');
        }
    }

    /**
     * @Route("/dashboard/utilisateurs/VSI/questionnaires/etapes/gerer", name="admin_dashboard_users_VSI_survey_step_manage")
     */
    public function manage(Request $request)
    {
        $id_entity  = (int) $request->request->get('id');
        $survey_id  = (int) $request->request->get('survey-id');
        $em         = $this->getDoctrine()->getManager();

        if (!empty($id_entity) && $id_entity > 0) { // Edit entity ?
            // Get entity to edit
            $is_new     = false;
            $repoEntity = $em->getRepository(SurveyStep::class);
            $entity     = $repoEntity->find($id_entity);
            $message_status_ok  = 'L\'étape a bien été modifiée';
            $message_status_nok = 'Un problème est survenu lors de la modification de l\'étape';
        } else {
            // New entity
            $is_new = true;
            $entity = new SurveyStep();
            $message_status_ok  = 'L\'étape a bien été ajoutée';
            $message_status_nok = 'Un problème est survenu lors de l\'ajout de l\'étape';
        }

        // 1) Build the form
        $form_entity = $this->createForm(SurveyStepType::class, $entity);

        // 2) Handle request
        $form_entity->handleRequest($request);
        $data = array();

        if ($form_entity->isSubmitted() && $form_entity->isValid()) {
            $r_survey = $em->getRepository(Survey::class);
            $survey = $r_survey->findOneById($survey_id);

            // Set step survey
            $entity->setSurvey($survey);

            // 3) Save !
            $em->persist($entity);

            // 4) Try to save (flush) or clear
            try {
                // Flush OK !
                $em->flush();

                $data = array(
                    'query_status'    => 1,
                    'message_status'  => $message_status_ok,
                    'id_entity'       => $entity->getId(),
                    'is_new_entity'   => $is_new,
                    'form_data'       => $this->formatStepDataForStealthRaven($entity)
                );

                // Clear/reset form
                $form_entity = $this->createForm(SurveyStepType::class, $entity);
            } catch (\Exception $e) {
                // Something goes wrong
                $em->clear();
                $data = array(
                    'query_status'    => 0,
                    'exception'       => $e->getMessage(),
                    'message_status'  => $message_status_nok
                );
            }
        }

        if ($request->isXmlHttpRequest()) {
            // Return JSON data
            return $this->json($data);
        } else {
            // Redirect to entities homepage
            return $this->redirectToRoute('admin_dashboard_users_VSI_survey_steps', array( 'id' => $survey_id ));
        }
    }

    /**
     * @Route("/dashboard/utilisateurs/VSI/questionnaires/etapes/supprimer/{id}", name="admin_dashboard_survey_step_del")
     */
    public function delete(SurveyStep $entity, AuthorizationCheckerInterface $authChecker, Request $request)
    {
        $survey_id = 0;
        if(!empty($entity)) {
            $em                 = $this->getDoctrine()->getManager();
            $id_entity_deleted  = $entity->getId();
            $survey_id          = $entity->getSurvey()->getId();

            // Delete entity
            $em->remove($entity);

            // Try to save (flush) or clear
            try {
                // Flush OK !
                $em->flush();
                $data = array(
                    'query_status'  => 99, // = force reload in front (see Stealth Raven JS module)
                    'id_entity'     => $id_entity_deleted,
                );
            } catch (\Exception $e) {
                // Something goes wrong
                $em->clear();
                $data = array(
                    'query_status'    => 0,
                    'exception'       => $e->getMessage(),
                    'message_status'  => 'Un problème est survenu lors de la suppression de l\'étape'
                );
            }
        } else {
            $data = array(
                'query_status'    => 0,
                'message_status'  => 'Aucune étape n\'existe pour cet ID'
            );
        }

        if ($request->isXmlHttpRequest()) {
            return $this->json($data);
        } else {
            // Not accessible by URL
            if (!empty($survey_id)) return $this->redirectToRoute('admin_dashboard_users_VSI_survey_steps', array( 'id' => $survey_id ));
            else return $this->redirectToRoute('admin_dashboard_users_VSI_survey_list');
        }
    }

    /**
     * @Route("/dashboard/utilisateurs/VSI/questionnaires/etapes/{id}", name="admin_dashboard_users_VSI_survey_step_get")
     */
    public function show($id, Request $request)
    {
        $em             = $this->getDoctrine()->getManager();
        $r_survey_step  = $em->getRepository(SurveyStep::class);
        // Get survey
        $survey_step    = $r_survey_step->findOneById($id);

        if (is_null($survey_step))
          return $this->redirectToRoute('admin_dashboard_users_VSI_survey_list');

        // Set query status
        $data = array( 'query_status' => (!empty($survey_step) ? 1 : 0) );
        $data['message_status'] = 'Un problème est survenu lors de l\'étape';

        if ($data['query_status'] == 1) {
            $data['id_entity'] = $survey_step->getId();
            $data['form_data'] = $this->formatStepDataForStealthRaven($survey_step);

            // No problem = no message
            unset($data['message_status']);
        }

        if ($request->isXmlHttpRequest()) {
            return $this->json($data);
        } else {
            // Redirect to entities homepage (no direct access)
            return $this->redirectToRoute('admin_dashboard_users_VSI_survey_list');
        }
    }

    /**
     * @Route("/dashboard/utilisateurs/VSI/questionnaires/questions/gerer", name="admin_dashboard_users_VSI_survey_question_manage")
     */
    public function manageQuestion(Request $request)
    {
        $id_entity  = (int) $request->request->get('id');
        $step_id    = (int) $request->request->get('survey-step-id');
        $em         = $this->getDoctrine()->getManager();

        if (!empty($id_entity) && $id_entity > 0) { // Edit entity ?
            // Get entity to edit
            $is_new     = false;
            $repoEntity = $em->getRepository(SurveyQuestion::class);
            $entity     = $repoEntity->find($id_entity);
            $message_status_ok  = 'La question a bien été modifiée';
            $message_status_nok = 'Un problème est survenu lors de la modification de la question';
        } else {
            // New entity
            $is_new = true;
            $entity = new SurveyQuestion();
            $message_status_ok  = 'La question a bien été ajoutée';
            $message_status_nok = 'Un problème est survenu lors de l\'ajout de la question';
        }

        // 1) Build the form
        $form_entity = $this->createForm(SurveyQuestionType::class, $entity);

        // 2) Handle request
        $form_entity->handleRequest($request);
        $data = array();

        if ($form_entity->isSubmitted() && $form_entity->isValid()) {
            $r_step = $em->getRepository(SurveyStep::class);
            $step = $r_step->findOneById($step_id);

            // Add survey step to question
            $entity->setSurveyStep($step);

            // 3) Save !
            $em->persist($entity);

            // 4) Try to save (flush) or clear
            try {
                // Flush OK !
                $em->flush();

                $data = array(
                    'query_status'    => 1,
                    'message_status'  => $message_status_ok,
                    'id_entity'       => $entity->getId(),
                    'is_new_entity'   => $is_new,
                    'form_data'       => $this->formatQuestionDataForStealthRaven($entity)
                );

                // Clear/reset form
                $form_entity = $this->createForm(SurveyQuestionType::class, $entity);
            } catch (\Exception $e) {
                // Something goes wrong
                $em->clear();
                $data = array(
                    'query_status'    => 0,
                    'exception'       => $e->getMessage(),
                    'message_status'  => $message_status_nok
                );
            }
        }

        if ($request->isXmlHttpRequest()) {
            // Return JSON data
            return $this->json($data);
        } else {
            // Redirect to entities homepage
            return $this->redirectToRoute('admin_dashboard_users_VSI_survey_steps', array( 'id' => $step->getSurvey()->getId() ));
        }
    }

    /**
     * @Route("/dashboard/utilisateurs/VSI/questionnaires/questions/supprimer/{id}", name="admin_dashboard_survey_question_del")
     */
    public function deleteQuestion(SurveyQuestion $entity, AuthorizationCheckerInterface $authChecker, Request $request)
    {
        if(!empty($entity)) {
            $em                 = $this->getDoctrine()->getManager();
            $id_entity_deleted  = $entity->getId();

            // Delete entity
            $em->remove($entity);

            // Try to save (flush) or clear
            try {
                // Flush OK !
                $em->flush();
                $data = array(
                    'query_status'  => 99, // = force reload in front (see Stealth Raven JS module)
                    'id_entity'     => $id_entity_deleted,
                );
            } catch (\Exception $e) {
                // Something goes wrong
                $em->clear();
                $data = array(
                    'query_status'    => 0,
                    'exception'       => $e->getMessage(),
                    'message_status'  => 'Un problème est survenu lors de la suppression de la question'
                );
            }
        } else {
            $data = array(
                'query_status'    => 0,
                'message_status'  => 'Aucune question n\'existe pour cet ID'
            );
        }

        if ($request->isXmlHttpRequest()) {
            return $this->json($data);
        } else {
            // Not accessible by URL
            return $this->redirectToRoute('admin_dashboard_users_VSI_survey_list');
        }
    }

    /**
     * @Route("/dashboard/utilisateurs/VSI/questionnaires/questions/{id}", name="admin_dashboard_users_VSI_survey_question_get")
     */
    public function showQuestion($id, Request $request)
    {
        $em                 = $this->getDoctrine()->getManager();
        $r_survey_question  = $em->getRepository(SurveyQuestion::class);
        // Get survey
        $survey_question    = $r_survey_question->findOneById($id);

        if (is_null($survey_question))
          return $this->redirectToRoute('admin_dashboard_users_VSI_survey_list');

        // Set query status
        $data = array( 'query_status' => (!empty($survey_question) ? 1 : 0) );
        $data['message_status'] = 'Un problème est survenu lors de la question';

        if ($data['query_status'] == 1) {
            $data['id_entity'] = $survey_question->getId();
            $data['form_data'] = $this->formatQuestionDataForStealthRaven($survey_question);

            // No problem = no message
            unset($data['message_status']);
        }

        if ($request->isXmlHttpRequest()) {
            return $this->json($data);
        } else {
            // Redirect to entities homepage (no direct access)
            return $this->redirectToRoute('admin_dashboard_users_VSI_survey_list');
        }
    }


    private function formatStepDataForStealthRaven(SurveyStep $step) {
        return array(
          'survey_step_label'    => $step->getLabel(),
          'survey_step_position' => $step->getPosition()
        );
    }

    private function formatQuestionDataForStealthRaven(SurveyQuestion $question) {
        return array(
          'survey_question_label'    => $question->getLabel(),
          'survey_question_position' => $question->getPosition()
        );
    }
}
