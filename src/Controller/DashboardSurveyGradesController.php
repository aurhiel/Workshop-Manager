<?php

namespace App\Controller;

// Types
use App\Form\SurveyGradeType;

// Entities
use App\Entity\Survey;
use App\Entity\SurveyGrade;

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


class DashboardSurveyGradesController extends Controller
{
    /**
     * @Route("/dashboard/utilisateurs/VSI/questionnaires/{id}/notations", name="admin_dashboard_users_VSI_survey_grades")
     */
    public function list($id)
    {
        $em       = $this->getDoctrine()->getManager();
        $r_survey = $em->getRepository(Survey::class);
        // Get survey
        $survey = $r_survey->findOneById($id);

        // 1) Build survey grade form
        $survey_grade = new SurveyGrade();
        $survey_grade_form = $this->createForm(SurveyGradeType::class, $survey_grade, array(
          // Change action for Stealth Raven Ajax Plugin
          'action' => $this->generateUrl('admin_dashboard_users_VSI_survey_grade_manage')
        ));

        if (!empty($survey)) {
            return $this->render(
                'dashboard/survey/grades.html.twig',
                array(
                  'meta'        => array(
                    'title' => 'Notations du questionnaire de satisfaction ' . $survey->getLabel()
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
                  'form_survey_grade' => $survey_grade_form->createView(),
                  'survey'            => $survey
                )
            );
        } else {
            // Survey not found > Redirect to surveys homepage
            return $this->redirectToRoute('admin_dashboard_users_VSI_survey_list');
        }
    }

    /**
     * @Route("/dashboard/utilisateurs/VSI/questionnaires/notations/gerer", name="admin_dashboard_users_VSI_survey_grade_manage")
     */
    public function manage(Request $request)
    {
        $id_entity  = (int) $request->request->get('id');
        $survey_id  = (int) $request->request->get('survey-id');
        $em         = $this->getDoctrine()->getManager();

        if (!empty($id_entity) && $id_entity > 0) { // Edit entity ?
            // Get entity to edit
            $is_new     = false;
            $repoEntity = $em->getRepository(SurveyGrade::class);
            $entity     = $repoEntity->find($id_entity);
            $message_status_ok  = 'La notation a bien été modifiée';
            $message_status_nok = 'Un problème est survenu lors de la modification de la notation';
        } else {
            // New entity
            $is_new = true;
            $entity = new SurveyGrade();
            $message_status_ok  = 'La notation a bien été ajoutée';
            $message_status_nok = 'Un problème est survenu lors de l\'ajout de la notation';
        }

        // 1) Build the form
        $form_entity = $this->createForm(SurveyGradeType::class, $entity);

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
                    'form_data'       => $this->formatDataForStealthRaven($entity)
                );

                // Clear/reset form
                $form_entity = $this->createForm(SurveyGradeType::class, $entity);
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
            return $this->redirectToRoute('admin_dashboard_users_VSI_survey_grades', array( 'id' => $survey_id ));
        }
    }

    /**
     * @Route("/dashboard/utilisateurs/VSI/questionnaires/notations/{id}", name="admin_dashboard_users_VSI_survey_grade_get")
     */
    public function show($id, Request $request)
    {
        $em             = $this->getDoctrine()->getManager();
        $r_survey_grade = $em->getRepository(SurveyGrade::class);
        // Get survey
        $survey_grade   = $r_survey_grade->findOneById($id);

        if (is_null($survey_grade))
          return $this->redirectToRoute('admin_dashboard_users_VSI_survey_list');

        // Set query status
        $data = array( 'query_status' => (!empty($survey_grade) ? 1 : 0) );
        $data['message_status'] = 'Un problème est survenu lors de la notation';

        if ($data['query_status'] == 1) {
            $data['id_entity'] = $survey_grade->getId();
            $data['form_data'] = $this->formatDataForStealthRaven($survey_grade);

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


    private function formatDataForStealthRaven(SurveyGrade $grade) {
        return array(
          'survey_grade_label'    => $grade->getLabel(),
          'survey_grade_value'    => $grade->getValue(),
          'survey_grade_position' => $grade->getPosition()
        );
    }
}
