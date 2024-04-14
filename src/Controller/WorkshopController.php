<?php

namespace App\Controller;

// Types
use App\Form\WorkshopType;

// Entities
use App\Entity\Workshop;
use App\Entity\WorkshopSubscribe;
use App\Entity\SurveyQuestion;

// Misc. stuff
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class WorkshopController extends Controller
{
    /**
     * @Route("/dashboard/atelier/gerer", name="admin_dashboard_workshop_manage")
     */
    public function manage(Security $security, Request $request)
    {
        $id_entity      = (int) $request->request->get('id'); // Edit entity ?
        $entityManager  = $this->getDoctrine()->getManager();
        $is_edit        = (!empty($id_entity) && $id_entity > 0);

        if($is_edit) {
            // Get Entity to edit
            $is_new             = false;
            $repoWorkshop       = $entityManager->getRepository(Workshop::class);
            $workshop           = $repoWorkshop->find($id_entity);
            $old_workshop       = clone $workshop;
            $message_status_ok  = 'L\'atelier a bien été modifié';
            $message_status_nok = 'Un problème est survenu lors de la modification de l\'atelier';
        } else {
            // New Entity
            $is_new             = true;
            $workshop           = new Workshop();
            $message_status_ok  = 'L\'atelier a bien été ajouté';
            $message_status_nok = 'Un problème est survenu lors de l\'ajout de l\'atelier';
        }

        // 1) Build the form
        $form = $this->createForm(WorkshopType::class, $workshop);

        // Handle request
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // 2) Save !
            $entityManager->persist($workshop);

            // 3) Try to save (flush) or clear
            try {
                // Flush OK !
                $entityManager->flush();

                $data = array_merge(array(
                    'query_status'    => 1,
                    // 'message_status'  => $message_status_ok,
                    'is_new_entity'   => $is_new
                ), $this->formatDataForStealthRaven($workshop, $security->getUser()));

                // On edit success >
                if($is_edit) {
                    // Set subscribers list
                    $data['subscribers'] = $this->formatSubscribers($workshop);
                }
            } catch (\Exception $e) {
                // Something goes wrong
                $entityManager->clear();

                $data = array(
                    'query_status'    => 0,
                    'exception'       => $e->getMessage(),
                    'message_status'  => $message_status_nok
                );
            }
        } else {
            $data = array(
                'query_status'    => 0,
                'message_status'  => 'Un problème est survenu, veuillez actualiser la page pour essayer de nouveau ou contacter un administrateur si le problème persiste.'
            );
        }

        if ($request->isXmlHttpRequest()) {
            return $this->json($data);
        } else {
            // No direct access
            return $this->redirectToRoute('dashboard');
        }
    }


    /**
     * @Route("/dashboard/atelier/supprimer/{id}", name="admin_dashboard_workshop_del")
     */
    public function delete(Workshop $workshop, Request $request)
    {
        if(!empty($workshop)) {
          $em                   = $this->getDoctrine()->getManager();
          $workshop_deleted     = $workshop;
          $id_workshop_deleted  = $workshop_deleted->getId();

          $r_sq = $em->getRepository(SurveyQuestion::class);
          if (is_null($r_sq->findOneByWorkshop($workshop)) === false) {
              $data = array(
                  'query_status'    => 0,
                  'message_status'  => 'Vous ne pouvez pas supprimer cet atelier car il est lié à un questionnaire.'
              );
          } else {
              // Remove entity
              $em->remove($workshop);

              // Try to save (flush) or clear entity remove
              try {
                  // Flush OK !
                  $em->flush();

                  $data = array(
                      'query_status'  => 1,
                      'id_entity'     => $id_workshop_deleted,
                  );
              } catch (\Exception $e) {
                  // Something goes wrong
                  $em->clear();
                  $data = array(
                      'query_status'    => 0,
                      'exception'       => $e->getMessage(),
                      'message_status'  => 'Un problème est survenu lors de la suppression de l\'atelier'
                  );
              }
          }
        } else {
            $data = array(
                'query_status'    => 0,
                'message_status'  => 'Aucun atelier n\'existe pour cet ID'
            );
        }

        if ($request->isXmlHttpRequest()) {
          return $this->json($data);
        } else {
          // Not accessible by URL
          // exit('no direct access');
          return $this->redirectToRoute('dashboard');
        }
    }


    /**
     * @Route("/dashboard/atelier/{id}", name="dashboard_workshop_get")
     */
    public function show(Workshop $workshop, Security $security, AuthorizationCheckerInterface $authChecker, Request $request)
    {
        // Set query status
        $data = array( 'query_status' => (!empty($workshop) ? 1 : 0) );
        $user = $security->getUser();

        if ($request->isXmlHttpRequest()) {
            $data['message_status'] = 'Un problème est survenu lors de la récupération de l\'atelier';

            if ($data['query_status'] == 1) {
                $data = array_merge($data, $this->formatDataForStealthRaven($workshop, $user));

                // Add url, only if workshop is waiting validations AND
                //    connected user can validate (his status must be equal to 0)
                $user_subscribe = $user->getSubscribeByWorkshop($workshop);

                if(($user_subscribe != null) && $user_subscribe->getStatus() == WorkshopSubscribe::STATUS_WAITING_VALIDATION)
                    $data['url_validate_subscribe'] = $this->generateUrl('dashboard_workshop_validate_subscribe', [ 'id' => $user_subscribe->getId() ]);

                // Get Delete URL and some informations for users with Publisher Role
                if(true === $authChecker->isGranted('ROLE_PUBLISHER')) {
                    // Set delete workshop url
                    $data['url_delete'] = $this->generateUrl('admin_dashboard_workshop_del', [ 'id' => $workshop->getId() ]);

                    // Set subscribers list
                    $subscribes = $workshop->getSubscribes();
                    $data['subscribers'] = $this->formatSubscribers($workshop);
                }

                // No problem = no message
                unset($data['message_status']);
            }

            return $this->json($data);
        } else {
            // No direct access
            return $this->redirectToRoute('dashboard');
        }
    }


    /**
    * Format Workshop data for StealthRaven
    */
    private function formatDataForStealthRaven(Workshop $workshop, $user)
    {
        $workshop_theme = $workshop->getTheme();
        $address        = $workshop->getAddress();
        $lecturer       = $workshop->getLecturer();
        $theme          = $workshop->getTheme();

        $data = array(
          'id_entity'       => $workshop->getId(),
          'url_entity'      => $this->generateUrl('dashboard_workshop_get', [ 'id' => $workshop->getId() ]),
          'workshop_status' => $workshop->getStatusSlug(),
          'form_data'   => array(
              'workshop_is_VSI_type' 		=> $workshop->getIsVSIType(),
              // Lecturer
              'workshop_lecturer'       => $lecturer->getId(),
              'workshop_lecturer_name'  => $lecturer->getLastname().' '.$lecturer->getFirstname(),
              'workshop_lecturer_email' => $lecturer->getEmail(),
              // Theme
              'workshop_theme'              => $theme->getId(),
              'workshop_theme_name'         => $theme->getName(),
              'workshop_theme_description'  => $theme->getDescription(),
              // Address
              'workshop_address'      => $address->getId(),
              'workshop_address_name' => $address->getName(),
              'workshop_latitude'     => $address->getLatPosition(),
              'workshop_longitude'    => $address->getLngPosition(),
              // Seats
              'workshop_nb_seats'       => $workshop->getNbSeats(),
              'workshop_nb_seats_left'  => $workshop->getNbSeatsLeft(),
              'workshop_nb_waiters'     => $workshop->getNbWaiters(),
              // Dates
              'workshop_date_start'     => $workshop->getDateStart(),
              'workshop_date_end'       => $workshop->getDateEnd(),
              // Description
              'workshop_description'    => $workshop->getDescription(),
          )
        );

        if($user->hasRole('ROLE_USER')) {
          // Get user workshop subscribe status
          $user_subscribe = $user->getSubscribeByWorkshop($workshop);

          $data = array_merge($data, array(
            'url_subscribe'   => $this->generateUrl('dashboard_workshop_subscribe',   [ 'id' => $workshop->getId() ]),
            'url_unsubscribe' => $this->generateUrl('dashboard_workshop_unsubscribe', [ 'id' => $workshop->getId() ]),
            'user_subscribe_status'       => ($user_subscribe != null) ? $user_subscribe->getStatusSlug() : null,
            'user_subscribe_status_text'  => ($user_subscribe != null) ? $user_subscribe->getStatusText() : 'Non-inscrit&middot;e',
            'user_subscribe_has_come'       => ($user_subscribe != null) ? $user_subscribe->getHasCome() : null,
            'user_subscribe_has_come_text'  => ($user_subscribe != null) ? $user_subscribe->getHasComeText() : null,
          ));
        }

        return $data;
    }


    public function formatSubscribers(Workshop $workshop)
    {
        $subscribes = $workshop->getSubscribes();
        $subscribers = [];
        foreach ($subscribes as $key => $sub) {
            $userIsVSI = ($sub->getUserVSI() != null);
            $user = (($userIsVSI == true) ? $sub->getUserVSI() : $sub->getUser());
            $subscribers[] = [
                'id'                => $user->getId(),
                'lastname'          => $user->getLastname(),
                'firstname'         => $user->getFirstname(),
                'email'             => $user->getEmail(),
                'user_url'          => $this->generateUrl('admin_dashboard_user_settings', [ 'id' => $user->getId() ]),
                'is_active'         => (($userIsVSI == true) ? true : $user->getIsActive()),
                'is_vsi'            => $userIsVSI,
                'subscribe_id'      => $sub->getId(),
                'subscribe_status'      => $sub->getStatusSlug(),
                'subscribe_status_text' => $sub->getStatusText(),
                'subscribe_delete_url'  => $this->generateUrl('dashboard_admin_subscribe_delete', [ 'id' => $sub->getId() ]),
                'subscriber_has_come'   => $sub->getHasCome(),
            ];
        }
        return $subscribers;
    }
}
