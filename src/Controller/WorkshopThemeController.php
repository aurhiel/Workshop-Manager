<?php

namespace App\Controller;

use App\Entity\WorkshopTheme;

use App\Form\WorkshopThemeType;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class WorkshopThemeController extends Controller
{

    const NB_ITEMS_BY_PAGE = 10;

    /**
     * @Route("/dashboard/thematiques/{page}", name="admin_dashboard_workthemes", requirements={"page"="\d+"})
     */
    public function index($page = 1, AuthorizationCheckerInterface $authChecker, Request $request)
    {
        // 1) Build the form
        $entity = new WorkshopTheme();
        $form_entity = $this->createForm(WorkshopThemeType::class, $entity, array(
          // Change action for Stealth Raven Ajax Plugin
          'action' => $this->generateUrl('admin_dashboard_workthemes_manage')
        ));

        $em       = $this->getDoctrine()->getManager();
        $r_theme  = $em->getRepository(WorkshopTheme::class);

        // Retrieve pagined entities list
        $entities = $r_theme->getPaging($page, self::NB_ITEMS_BY_PAGE);

        // Get total nb items
        $nb_items = $r_theme->counter();

        $nb_pages_raw = ($nb_items / self::NB_ITEMS_BY_PAGE);
        $nb_pages = floor($nb_pages_raw);

        // If there is decimal numbers,
        //  there is less than 10 entities (=self::NB_ITEMS_BY_PAGE) to display
        //  > So we need to add 1 more page
        if (($nb_pages_raw - $nb_pages) > 0)
            $nb_pages++;

        // If current page value is greater than the amount of pages > redirect
        if($page > $nb_pages)
            return $this->redirectToRoute('admin_dashboard_workthemes');

        return $this->render('dashboard/workshop_themes/index.html.twig', array(
          'meta'                => array('title' => 'Thématiques'),
          'stylesheets'         => array('admin-dashboard.css'),
          'scripts'             => array('admin-dashboard.js'),
          'breadcrumb_links' => array(
            [
              'label' => 'Dashboard',
              'href' => $this->generateUrl('dashboard')
            ],
          ),
          'form_workshop_theme' => $form_entity->createView(),
          'current_page'        => $page,
          'nb_pages'            => $nb_pages,
          'workshop_themes'     => $entities
        ));
    }


    /**
     * @Route("/dashboard/thematiques/gerer", name="admin_dashboard_workthemes_manage")
     */
    public function manage(AuthorizationCheckerInterface $authChecker, Request $request)
    {
        $id_entity      = (int) $request->request->get('id');
        $entityManager  = $this->getDoctrine()->getManager();

        if(!empty($id_entity) && $id_entity > 0) { // Edit entity ?
          // Get Workshop Theme to edit
          $is_new     = false;
          $repoEntity = $entityManager->getRepository(WorkshopTheme::class);
          $entity     = $repoEntity->find($id_entity);
          $message_status_ok  = 'La thématique a bien été modifiée';
          $message_status_nok = 'Un problème est survenu lors de la modification de la thématique';
        } else {
          // New Workshop Theme
          $is_new = true;
          $entity = new WorkshopTheme();
          $message_status_ok  = 'La thématique a bien été ajoutée';
          $message_status_nok = 'Un problème est survenu lors de l\'ajout de la thématique';
        }

        // 1) Build the form
        $form_entity = $this->createForm(WorkshopThemeType::class, $entity);

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
                $form_entity = $this->createForm(WorkshopThemeType::class, $entity);
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
          return $this->redirectToRoute('admin_dashboard_workthemes');

          // JUST FOR FUN (submit for non-ajax supporting)
          // Set flash message for classic submit
          if(isset($data['message_status']) && isset($data['query_status'])) {
              $request->getSession()->getFlashBag()->add(
                  (($data['query_status'] == 1) ? 'success' : 'error'),
                  $data['message_status']
              );
          }

          // Return HTML add form
          return $this->render('dashboard/workshop_themes/add.html.twig',
            array_merge(
                $data,
                array(
                    'meta'          => array('title' => 'Ajouter une thématique'),
                    'stylesheets'   => array('admin-dashboard.css'),
                    'scripts'       => array('admin-dashboard.js'),
                    'form_workshop_theme' => $form_entity->createView()
                )
            )
          );
       }
    }


    /**
     * @Route("/dashboard/thematiques/supprimer/{id}", name="admin_dashboard_workthemes_del")
     */
    public function delete(WorkshopTheme $entity, AuthorizationCheckerInterface $authChecker, Request $request)
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
              'query_status'  => 1,
              'id_entity'     => $id_entity_deleted,
            );
          } catch (\Exception $e) {
            // Something goes wrong
            $entityManager->clear();
            $data = array(
              'query_status'    => 0,
              'exception'       => $e->getMessage(),
              'message_status'  => 'Un problème est survenu lors de la suppression de la thématique'
            );
          }
        } else {
          $data = array(
            'query_status'    => 0,
            'message_status'  => 'Aucune thématique n\'existe pour cet ID'
          );
        }

        if ($request->isXmlHttpRequest()) {
          return $this->json($data);
        } else {
          // Not accessible by URL
          return $this->redirectToRoute('admin_dashboard_workthemes');
        }
    }


    /**
     * @Route("/dashboard/thematique/{id}", name="admin_dashboard_workthemes_get")
     */
    public function show($id, AuthorizationCheckerInterface $authChecker, Request $request)
    {
        $em      = $this->getDoctrine()->getManager();
        $r_theme = $em->getRepository(WorkshopTheme::class);
        // Get theme
        $theme   = $r_theme->findOneById($id);

        if(is_null($theme))
          return $this->redirectToRoute('admin_dashboard_workthemes');

        // Set query status
        $data = array( 'query_status' => (!empty($theme) ? 1 : 0) );
        $data['message_status'] = 'Un problème est survenu lors de la récupération de la thématique';

        if ($data['query_status'] == 1) {
            $data['id_entity'] = $theme->getId();
            $data['form_data'] = $this->formatDataForStealthRaven($theme);

            // No problem = no message
            unset($data['message_status']);
        }

        if ($request->isXmlHttpRequest()) {
            return $this->json($data);
        } else {
            $form_entity = $this->createForm(WorkshopThemeType::class, $theme, array(
              // Change action for Stealth Raven Ajax Plugin
              'action' => $this->generateUrl('admin_dashboard_workthemes_manage')
            ));

            // Referer = dashboard/themathiques/{num_page}
            $referer = $request->headers->get('referer');
            $url_to_themes = (($request->getUri() != $referer) ? $referer : $this->generateUrl('admin_dashboard_workthemes'));

            // Get workshops
            $theme_workshops = $theme->getWorkshops();

            // $theme_workshops = $theme->getWorkshops();
            // $theme_workshops_by_date = array();
            // foreach ($theme_workshops as $workshop) {
            //     $key_date = $workshop->getDateStart()->format('Y-m-d');
            //     $theme_workshops_by_date[$key_date][] = $workshop;
            // }

            return $this->render('dashboard/workshop_themes/theme.html.twig',
              array_merge(
                  $data,
                  array(
                      'meta'              => array('title' => $theme->getName()),
                      'stylesheets'       => array('admin-dashboard.css'),
                      'scripts'           => array('admin-dashboard.js'),
                      'breadcrumb_links'  => array(
                        [
                          'label' => 'Dashboard',
                          'href' => $this->generateUrl('dashboard')
                        ],
                        [
                          'label' => 'Thématiques',
                          'href' => $url_to_themes
                        ],
                      ),
                      'workshop_theme'          => $theme,
                      'theme_workshops'         => $theme_workshops,
                      // 'theme_workshops_by_date' => $theme_workshops_by_date,
                      'form_workshop_theme'     => $form_entity->createView()
                  )
              )
            );
        }
    }


    private function formatDataForStealthRaven(WorkshopTheme $theme) {
        return array(
          'workshop_theme_name'         => $theme->getName(),
          'workshop_theme_description'  => $theme->getDescription()
        );
    }
}
