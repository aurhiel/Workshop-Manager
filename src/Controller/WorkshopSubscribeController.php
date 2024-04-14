<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\UserVSI;
use App\Entity\Workshop;
use App\Entity\WorkshopSubscribe;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class WorkshopSubscribeController extends Controller
{
    /**
     * @Route("/dashboard/atelier/inscription/{id}/valider", name="dashboard_workshop_validate_subscribe")
     */
    public function validate_subscription(WorkshopSubscribe $subscribe, Security $security, Request $request)
    {
        $translator = $this->get('translator');
        // 1) Check $subscribe and User's subscribe status = PRE_SUBSCRIBE
        // AND check if subscribe is owned by connected user
        if($subscribe && $subscribe->getUser() == $security->getUser() && $subscribe->getStatus() == WorkshopSubscribe::STATUS_WAITING_VALIDATION) {
            $entityManager = $this->getDoctrine()->getManager();
            $workshop = $subscribe->getWorkshop();

            // 2) Set Status to "Subscribed"
            $subscribe->setStatus(WorkshopSubscribe::STATUS_SUBSCRIBED);

            // 3) Try to save (flush) or clear
            try {
                // Flush OK !
                $entityManager->flush();
                $data = array(
                  'query_status'    => 1,
                  'message_status'  => $translator->trans(
                      'workshop.subs.validate_subscription.done',
                      array(
                          '%workshop_theme%'  => $workshop->getTheme()->getName()
                      )
                  ),
                  'user_subscribe_status'       => $subscribe->getStatusSlug(),
                  'user_subscribe_status_text'  => $subscribe->getStatusText(),
                  'workshop_id'             => $workshop->getId(),
                  'workshop_status'         => $workshop->getStatusSlug(),
                  // Theme name
                  'workshop_theme_name' => $workshop->getTheme()->getName(),
                  // Date
                  'workshop_date_start' => $workshop->getDateStart(),
                  // Seats
                  'workshop_nb_seats'       => $workshop->getNbSeats(),
                  'workshop_nb_seats_left'  => $workshop->getNbSeatsLeft(),
                  'workshop_nb_waiters'     => $workshop->getNbWaiters()
                );
            } catch (\Exception $e) {
                // Something goes wrong
                $entityManager->clear();
                $data = array(
                    'query_status'    => 0,
                    'exception'       => $e->getMessage(),
                    'message_status'  => 'Un problème est survenu, veuillez réessayer ultérieurement.'
                );
            }

        } else {
            $data = array(
              'query_status'    => 0,
              'message_status'  => 'Un problème est survenu, veuillez réessayer ultérieurement.'
            );
        }

        if ($request->isXmlHttpRequest()) {
            return $this->json($data);
        } else {
            // No direct access > subscribe and redirect to dashboard homepage
            return $this->redirectToRoute('dashboard');
        }
    }


    /**
     * @Route("/dashboard/atelier/inscription/{id}", name="dashboard_workshop_subscribe")
     */
    public function subscribe(Workshop $workshop, Security $security, Request $request)
    {
        if ($workshop->isAvailableForSubscribe() == false)
          return $this->json(['query_status' => 0, 'message_status' => 'Les inscriptions ne sont plus ouvertes pour cet atelier.']);

        if ($workshop->isWaitingSubscribesValidation() == true && $workshop->hasSeatsLeft() == false)
          return $this->json(['query_status' => 0, 'message_status' => 'Les inscriptions en file d\'attente ne sont plus ouvertes pour cet atelier.']);

        $user = $security->getUser();

        // Check if user already subscribe (status doesn't matter)
        if(null === $workshop->getSubscribeByUser($user)) {
            $entityManager      = $this->getDoctrine()->getManager();
            $workshop_subscribe = new WorkshopSubscribe();
            $nb_seats_left      = $workshop->getNbSeatsLeft();

            // If enough seats left > subscribe
            if($nb_seats_left > 0) {
                // echo "subscribe !";
                $status = WorkshopSubscribe::STATUS_PRE_SUBSCRIBE;
                $message_success = 'Vous avez été inscrit à l\'atelier : '.$workshop->getTheme()->getName();
            } else {
                // echo "no more places";
                $status = WorkshopSubscribe::STATUS_WAITING_SEATS;
                $message_success = 'Vous êtes en file d\'attente pour participer à l\'atelier : '.$workshop->getTheme()->getName();
            }

            // 1) Insert values
            $workshop_subscribe
              ->setUser($user)
              ->setWorkshop($workshop)
              ->setStatus($status);

            // 2) Save !
            $entityManager->persist($workshop_subscribe);

            // 3) Try to save (flush) or clear
            try {
                // Flush OK !
                $entityManager->flush();

                // After flush validated add subscribe to workshop
                $workshop->addSubscribe($workshop_subscribe);

                $data = array(
                  'query_status'    => 1,
                  // 'message_status'  => $message_success,
                  'user_subscribe_status'       => $workshop_subscribe->getStatusSlug(),
                  'user_subscribe_status_text'  => $workshop_subscribe->getStatusText(),
                  'workshop_id'             => $workshop->getId(),
                  'workshop_status'         => $workshop->getStatusSlug(),
                  // Seats
                  'workshop_nb_seats'       => $workshop->getNbSeats(),
                  'workshop_nb_seats_left'  => $workshop->getNbSeatsLeft(),
                  'workshop_nb_waiters'     => $workshop->getNbWaiters()
                );

                // Add validate subscribe url if needed
                if(($workshop_subscribe != null) && $workshop_subscribe->getStatus() == WorkshopSubscribe::STATUS_WAITING_VALIDATION)
                    $data['url_validate_subscribe'] = $this->generateUrl('dashboard_workshop_validate_subscribe', [ 'id' => $workshop_subscribe->getId() ]);

              } catch (\Exception $e) {
                // Something goes wrong
                $entityManager->clear();
                $data = array(
                  'query_status'    => 0,
                  'exception'       => $e->getMessage(),
                  'message_status'  => 'Un problème est survenu, veuillez réessayer ultérieurement.'
                );
            }
        } else {
            // echo "already subscribed (STATUS = 0) or waiting (STATUS = -1)";
            $data = array(
              'query_status'    => 1,
              'message_status'  => 'Vous êtes déjà inscrit à cet atelier'
            );
        }

        if ($request->isXmlHttpRequest()) {
            return $this->json($data);
        } else {
            // No direct access > subscribe and redirect to dashboard homepage
            return $this->redirectToRoute('dashboard');
        }
    }


    /**
     * @Route("/dashboard/atelier/desinscription/{id}", name="dashboard_workshop_unsubscribe")
     */
    public function unsubscribe(Workshop $workshop, Security $security, Request $request)
    {
        $user = $security->getUser();
        $workshop_subscribe = $workshop->getSubscribeByUser($user);

        if($workshop_subscribe !== null) {
            $old_status     = $workshop_subscribe->getStatus();
            $entityManager  = $this->getDoctrine()->getManager();

            // If user has pre-sub or waiting is validation > remove sub
            if($old_status === WorkshopSubscribe::STATUS_WAITING_VALIDATION ||
              $old_status === WorkshopSubscribe::STATUS_PRE_SUBSCRIBE ||
                $old_status === WorkshopSubscribe::STATUS_WAITING_SEATS) {
                // 2) Remove
                $entityManager->remove($workshop_subscribe);

                // 3) Try to apply remove (flush) or clear
                try {
                    // Flush OK !
                    $entityManager->flush();

                    $data = array(
                        'query_status'                => 1,
                        'user_subscribe_status'       => null,
                        'user_subscribe_status_text'  => 'Non-inscrit&middot;e',
                        'workshop_id'             => $workshop->getId(),
                        'workshop_status'         => $workshop->getStatusSlug(),
                        // Seats
                        'workshop_nb_seats'       => $workshop->getNbSeats(),
                        'workshop_nb_seats_left'  => $workshop->getNbSeatsLeft(),
                        'workshop_nb_waiters'     => $workshop->getNbWaiters()
                    );
                } catch (\Exception $e) {
                    // Something goes wrong
                    $entityManager->clear();
                    $data = array(
                      'query_status'    => 0,
                      'exception'       => $e->getMessage(),
                      'message_status'  => 'Un problème est survenu, veuillez réessayer ultérieurement.'
                    );
                }
            } else {
                $data = array(
                  'query_status'    => 0,
                  'message_status'  => 'Vous ne pouvez pas vous désinscrire d\'un atelier auquel vous avez confirmé votre inscription.'
                );
            }
        } else {
            $data = array(
              'query_status'    => 0,
              'message_status'  => 'Vous n\'êtes pas inscrit à cet atelier'
            );
        }

        if ($request->isXmlHttpRequest()) {
            return $this->json($data);
        } else {
            // No direct access > subscribe and redirect to dashboard homepage
            return $this->redirectToRoute('dashboard');
        }
    }


    /**
     * @Route("/dashboard/inscriptions/ajout-manuel", name="dashboard_admin_subscribe_user")
     */
    public function subscribe_by_admin(Security $security, Request $request)
    {
        $workshop_id  = (int) $request->request->get('workshop_id');
        $user_is_vsi  = $request->request->get('user_is_vsi') == 'false' ? false : (bool) $request->request->get('user_is_vsi');
        $user_id      = (int) $request->request->get('user_id');

        $em             = $this->getDoctrine()->getManager();
        $repo_user      = ($user_is_vsi === true) ? $em->getRepository(UserVSI::class) : $em->getRepository(User::class);
        $repo_workshop  = $em->getRepository(Workshop::class);

        $user = $repo_user->findOneById($user_id);
        $workshop = $repo_workshop->findOneById($workshop_id);


        if($workshop !== false && $user !== false) {
            $user_sub = ($user_is_vsi === true) ? $workshop->getSubscribeByUserVSI($user) : $workshop->getSubscribeByUser($user);
            if(null === $user_sub) {
                $workshop_subscribe = new WorkshopSubscribe();
                $nb_seats_left      = $workshop->getNbSeatsLeft();

                // If enough seats left > subscribe
                if($nb_seats_left > 0) {
                    // echo "subscribe !";
                    $status = WorkshopSubscribe::STATUS_PRE_SUBSCRIBE;
                    $message_success = 'Le participant a bien été inscrit à l\'atelier';
                } else {
                    // echo "no more places";
                    $status = WorkshopSubscribe::STATUS_WAITING_SEATS;
                    $message_success = 'Le participant a bien été inscrit en file d\'attente';
                }

                // 1) Insert values
                if ($user_is_vsi === true) $workshop_subscribe->setUserVSI($user);
                else  $workshop_subscribe->setUser($user);
                // ... other values
                $workshop_subscribe
                  ->setWorkshop($workshop)
                  ->setStatus($status);

                // 2) Save !
                $em->persist($workshop_subscribe);

                // 3) Try to save (flush) or clear
                try {
                    // Flush OK !
                    $em->flush();

                    // After flush validated add subscribe to workshop
                    $workshop->addSubscribe($workshop_subscribe);

                    $data = array(
                      'query_status'    => 1,
                      'message_status'  => $message_success,
                      'user_subscribe_status'       => $workshop_subscribe->getStatusSlug(),
                      'user_subscribe_status_text'  => $workshop_subscribe->getStatusText(),
                      // Subscribers
                      'subscribers'             => $this->formatSubscribers($workshop),
                      // Workshop
                      'workshop'      => [
                          'status'        => $workshop->getStatusSlug(),
                          // Seats
                          'nb_seats'      => $workshop->getNbSeats(),
                          'nb_seats_left' => $workshop->getNbSeatsLeft(),
                          'nb_waiters'    => $workshop->getNbWaiters(),
                      ]
                    );

                    // Add validate subscribe url if needed
                    if(($workshop_subscribe != null) && $workshop_subscribe->getStatus() == WorkshopSubscribe::STATUS_WAITING_VALIDATION)
                        $data['url_validate_subscribe'] = $this->generateUrl('dashboard_workshop_validate_subscribe', [ 'id' => $workshop_subscribe->getId() ]);

                  } catch (\Exception $e) {
                    // Something goes wrong
                    $em->clear();
                    $data = array(
                      'query_status'    => 0,
                      'exception'       => $e->getMessage(),
                      'message_status'  => 'Un problème est survenu, veuillez réessayer ultérieurement.'
                    );
                }

            } else {
                $data = array(
                    'query_status'    => 0,
                    'message_status'  => 'Cet utilisateur est déjà inscrit à l\'atelier.'
                );
            }
        } else {
            $data = array(
                'query_status'    => 0,
                'message_status'  => 'Un problème est survenu avec l\'utilisateur à inscrire ou l\'atelier, veuillez ré-essayer ultérieurement.'
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
     * @Route("/dashboard/inscriptions/changer-statuts", name="dashboard_admin_subscribe_change_status")
     */
    public function change_status(Security $security, Request $request)
    {
        $id_subscribe = (int) $request->request->get('id');
        $status_slug  = $request->request->get('status');

        // Get Status ID
        $status = false;
        switch ($status_slug) {
          case 'subscribed':
            $status = WorkshopSubscribe::STATUS_SUBSCRIBED;
            break;
          case 'pre-subscribe':
            $status = WorkshopSubscribe::STATUS_PRE_SUBSCRIBE;
            break;
          case 'waiting-seats':
            $status = WorkshopSubscribe::STATUS_WAITING_SEATS;
            break;
        }

        if($status !== false) {
            $em         = $this->getDoctrine()->getManager();
            $repoSubs   = $em->getRepository(WorkshopSubscribe::class);
            $subscribe  = $repoSubs->find($id_subscribe);

            if(!empty($subscribe)) {
                // Update status
                $subscribe->setStatus($status);

                // Try to save (flush) or clear entity remove
                try {
                    // Flush OK !
                    $em->flush();

                    // Get subscribers
                    $workshop     = $subscribe->getWorkshop();
                    $subscribers  = $this->formatSubscribers($workshop);

                    $data = array(
                        'query_status'  => 1,
                        'id_subscribe'  => $subscribe->getId(),
                        // Subscribers
                        'subscribers'   => $subscribers,
                        // Workshop
                        'workshop'      => [
                            'status'        => $workshop->getStatusSlug(),
                            // Seats
                            'nb_seats'      => $workshop->getNbSeats(),
                            'nb_seats_left' => $workshop->getNbSeatsLeft(),
                            'nb_waiters'    => $workshop->getNbWaiters(),
                        ],
                        // User subscribe
                        'subscribe_status'      => $subscribe->getStatusSlug(),
                        'subscribe_status_text' => $subscribe->getStatusText()
                    );
                } catch (\Exception $e) {
                    // Something goes wrong
                    $em->clear();
                    $data = array(
                        'query_status'    => 0,
                        'exception'       => $e->getMessage(),
                        'message_status'  => 'Un problème est survenu lors de la modification du statuts de l\'inscription'
                    );
                }
            } else {
                $data = array(
                    'query_status'    => 0,
                    'message_status'  => 'Aucune inscription n\'existe pour cet ID'
                );
            }
        } else {
            $data = array(
              'query_status'    => 0,
              'message_status'  => 'Statuts invalide'
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
     * @Route("/dashboard/inscriptions/update-presence", name="dashboard_admin_subscribe_update_presence")
     */
    public function update_presence(Request $request)
    {
        $id_subscribe = (int) $request->request->get('id');
        if(!empty($id_subscribe)) {
            $em         = $this->getDoctrine()->getManager();
            $repoSubs   = $em->getRepository(WorkshopSubscribe::class);
            $subscribe  = $repoSubs->find($id_subscribe);
            $presence   = $request->request->get('presence') == 'true';

            // Set if user has come to workshop
            $subscribe->setHasCome($presence);

            // Try to save (flush) or clear entity
            try {
                // Flush OK !
                $em->flush();

                $data = array(
                    'query_status'  => 1,
                    'id_subscribe'  => $id_subscribe,
                    'has_come'      => $presence
                );
            } catch (\Exception $e) {
                // Something goes wrong
                $em->clear();
                $data = array(
                    'query_status'    => 0,
                    'exception'       => $e->getMessage(),
                    'message_status'  => 'Un problème est survenu lors de la modification de la présence'
                );
            }
        } else {
            $data = array(
                'query_status'    => 0,
                'message_status'  => 'Aucune inscription n\'existe pour cet ID'
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
     * @Route("/dashboard/inscriptions/supprimer/{id}", name="dashboard_admin_subscribe_delete")
     */
    public function delete(WorkshopSubscribe $subscribe, Security $security, Request $request)
    {
        if(!empty($subscribe)) {
          $entityManager      = $this->getDoctrine()->getManager();
          $subscribe_deleted  = $subscribe;
          $workshop           = $subscribe->getWorkshop();
          $id_subs_deleted    = $subscribe->getId();

          // Remove entity
          $entityManager->remove($subscribe);

          // Try to save (flush) or clear entity remove
          try {
              // Flush OK !
              $entityManager->flush();

              // Get subscribers
              $subscribers = $this->formatSubscribers($workshop);

              $data = array(
                  'query_status'  => 1,
                  'id_subscribe'  => $id_subs_deleted,
                  // Subscribers
                  'subscribers'   => $subscribers,
                  // Workshop
                  'id_workshop'   => $workshop->getId(),
                  'workshop'      => [
                      // Seats
                      'nb_seats'       => $workshop->getNbSeats(),
                      'nb_seats_left'  => $workshop->getNbSeatsLeft(),
                      'nb_waiters'     => $workshop->getNbWaiters(),
                  ]
              );
          } catch (\Exception $e) {
              // Something goes wrong
              $entityManager->clear();
              $data = array(
                  'query_status'    => 0,
                  'exception'       => $e->getMessage(),
                  'message_status'  => 'Un problème est survenu lors de la suppression de l\'inscription'
              );
          }
        } else {
            $data = array(
                'query_status'    => 0,
                'message_status'  => 'Aucune inscription n\'existe pour cet ID'
            );
        }

        if ($request->isXmlHttpRequest()) {
            return $this->json($data);
        } else {
            // No direct access > subscribe and redirect to dashboard homepage
            return $this->redirectToRoute('dashboard');
        }
    }

    /**
    * NOTE ~ Duplicate from WorkshopController, if you edit this function, edit other ones !
    */
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
                'is_active'         => (($sub->getUserVSI() != null) ? true : $user->getIsActive()),
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
