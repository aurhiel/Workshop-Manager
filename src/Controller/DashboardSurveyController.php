<?php

namespace App\Controller;

// Types
use App\Form\SurveyType;

// Entities
use App\Entity\Survey;

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


class DashboardSurveyController extends Controller
{
    /**
     * @Route("/dashboard/utilisateurs/VSI/questionnaires", name="admin_dashboard_users_VSI_survey_list")
     */
    public function list()
    {
        $em = $this->getDoctrine()->getManager();

        $repoSurvey = $em->getRepository(Survey::class);

        // 1) Build the form
        $entity = new Survey();
        $form_entity = $this->createForm(SurveyType::class, $entity, array(
          // Change action for Stealth Raven Ajax Plugin
          'action' => $this->generateUrl('admin_dashboard_users_VSI_survey_manage')
        ));

        // 2) Retrieve default survey
        $default_survey = $repoSurvey->findOneByIsDefault(true);

        return $this->render(
            'dashboard/survey/index.html.twig',
            array(
              'meta'        => array(
                'title' => 'Questionnaires de satisfaction'
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
              ),
              'form_survey'     => $form_entity->createView(),
              'survey_default'  => $default_survey,
              'surveys'         => $repoSurvey->findAll()
            )
        );
    }

    /**
     * @Route("/dashboard/utilisateurs/VSI/questionnaires/gerer", name="admin_dashboard_users_VSI_survey_manage")
     */
    public function manage(Request $request)
    {
        $id_entity      = (int) $request->request->get('id');
        $entityManager  = $this->getDoctrine()->getManager();

        if (!empty($id_entity) && $id_entity > 0) { // Edit entity ?
            // Get Survey to edit
            $is_new     = false;
            $repoEntity = $entityManager->getRepository(Survey::class);
            $entity     = $repoEntity->find($id_entity);
            $message_status_ok  = 'Le questionnaire a bien été modifié';
            $message_status_nok = 'Un problème est survenu lors de la modification du questionnaire';
        } else {
            // New Survey
            $is_new = true;
            $entity = new Survey();
            $message_status_ok  = 'Le questionnaire a bien été ajouté';
            $message_status_nok = 'Un problème est survenu lors de l\'ajout du questionnaire';
        }

        // 1) Build the form
        $form_entity = $this->createForm(SurveyType::class, $entity);

        // 2) Handle request
        $form_entity->handleRequest($request);
        $data = array();

        if ($form_entity->isSubmitted() && $form_entity->isValid()) {
            // 3) Save !
            $entityManager->persist($entity);

            // 4) Try to save (flush) or clear
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
                $form_entity = $this->createForm(SurveyType::class, $entity);
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
            // Return JSON data
            return $this->json($data);
        } else {
            // Redirect to entities homepage
            return $this->redirectToRoute('admin_dashboard_users_VSI_survey_list');
        }
    }

    /**
     * @Route("/dashboard/utilisateurs/VSI/questionnaires/change-default/{id}", name="admin_dashboard_users_VSI_survey_change_default")
     */
    public function change_default($id, Request $request)
    {
        $em       = $this->getDoctrine()->getManager();
        $r_survey = $em->getRepository(Survey::class);
        $survey   = $r_survey->findOneById($id);

        if (is_null($survey))
            return $this->redirectToRoute('admin_dashboard_users_VSI_survey_list');

        // Set query status
        $data = array( 'query_status' => (!empty($survey) ? 1 : 0) );
        $data['message_status'] = 'Un problème est survenu lors de la récupération du questionnaire';

        if ($data['query_status'] == 1) {
            // 1) Disable all default survey (is_default = 1)
            $r_survey->disableAllDefault();

            // 2) Set new default survey
            $survey->setIsDefault(1);

            // 3) Try to save (flush) or clear
            try {
                // Flush OK !
                $em->flush();

                $data['id_entity'] = $survey->getId();
                $data['form_data'] = $this->formatDataForStealthRaven($survey);

                // No problem = no message
                unset($data['message_status']);
            } catch (\Exception $e) {
                // Something goes wrong
                $em->clear();

                $data = array(
                    'query_status'    => 0,
                    'exception'       => $e->getMessage(),
                    'message_status'  => 'Un problème est survenu, veuillez réessayer ultérieurement.'
                );
            }
        }

        if ($request->isXmlHttpRequest()) {
            return $this->json($data);
        } else {
            // Redirect to entities homepage (no direct access)
            return $this->redirectToRoute('admin_dashboard_users_VSI_survey_list');
        }
    }

    /**
     * @Route("/dashboard/utilisateurs/VSI/questionnaires/{id}", name="admin_dashboard_users_VSI_survey_get")
     */
    public function show($id, Request $request)
    {
        $em       = $this->getDoctrine()->getManager();
        $r_survey = $em->getRepository(Survey::class);
        // Get survey
        $survey   = $r_survey->findOneById($id);

        if (is_null($survey))
            return $this->redirectToRoute('admin_dashboard_users_VSI_survey_list');

        // Set query status
        $data = array( 'query_status' => (!empty($survey) ? 1 : 0) );
        $data['message_status'] = 'Un problème est survenu lors de la récupération du questionnaire';

        if ($data['query_status'] == 1) {
            $data['id_entity'] = $survey->getId();
            $data['form_data'] = $this->formatDataForStealthRaven($survey);

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


    private function formatDataForStealthRaven(Survey $survey) {
        return array(
          'survey_label'                  => $survey->getLabel(),
          'survey_slug'                   => $survey->getSlug(),
          'survey_enable_workshops_grade' => $survey->getEnableWorkshopsGrade()
        );
    }
}
