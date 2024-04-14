<?php

namespace App\Controller;

// Types
use App\Form\WorkshopType;

// Entities
use App\Entity\Workshop;
use App\Entity\WorkshopSubscribe;

// Components
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class DashboardController extends Controller
{
    // private $GOOGLE_MAPS_KEY = null;

    public function __construct()
    {
        // $this->GOOGLE_MAPS_KEY = getenv('GOOGLE_MAPS_KEY');
    }


    /**
     * @Route("/dashboard", name="dashboard")
     */
    public function index(Request $request, Security $security, AuthorizationCheckerInterface $authChecker)
    {
        $entityManager  = $this->getDoctrine()->getManager();
        $repoWorkshop   = $entityManager->getRepository(Workshop::class);
        $user           = $security->getUser();

        // Page data
        $data = array(
          'meta'        => array('title' => 'Dashboard'),
          'show_help_modal'     => ($user->getHideHelpModal() == false) ? 1 : 0
        );

        $is_publisher = false;

        // Add stylesheets/scripts and data for Admins
        if(true === $authChecker->isGranted('ROLE_PUBLISHER')) {
            // = all dates available on calendar
            $calendarStartDate  = null;
            $calendarEndDate    = null;

            // Push admin's scripts & stylesheets
            $data['scripts'] = array('admin-dashboard.js');
            $data['stylesheets'] = array('admin-dashboard.css');

            // Get events
            $workshops = $repoWorkshop->findAll();

            // Build add workshop form
            $workshop = new Workshop();
            $form_workshop = $this->createForm(WorkshopType::class, $workshop, array(
              // Change action
              'action' => $this->generateUrl('admin_dashboard_workshop_manage')
            ));

            $data['form_workshop'] = $form_workshop->createView();

            $is_publisher = true;
        } else {

            // Push classic user's scripts & stylesheets
            $data['scripts'] = array('dashboard.js');
            $data['stylesheets'] = array('dashboard.css');

            // = get workshops until the end of user's register (+7 days / +1 week)
            $queryStartDate     = $user->getRegisterDate()->format('Y-m-d');
            $queryEndDate       = $user->getRegisterEndDate()->format('Y-m-d');
            // Avoid odd month display on fullCalendar
            $calendarStartDate  = date('Y-m-01', strtotime($queryStartDate));
            $calendarEndDate    = date('Y-m-t', strtotime($queryEndDate));

            // Then get restricted events (for classic users)
            $workshops = $repoWorkshop->findWorkshopsByDate($queryStartDate, $queryEndDate);
        }


        // Formatting events for FullCalendar
        $fullcalendar_events = array();
        foreach($workshops as $workshop) {
            $subscribes = $workshop->getSubscribes();
            $user_subscribe = null;
            foreach ($subscribes as $subscribe) {
                if($subscribe->getUser() == $user) {
                    $user_subscribe = $subscribe;
                    break;
                }
            }

            // Set event CSS class
            $event_classes = [];
            // User status
            if($user_subscribe != null)
              $event_classes[] = 'user-status-'.$user_subscribe->getStatusSlug();
            // Workshop Status
            $event_classes[] = 'workshop-status-'.$workshop->getStatusSlug();

						if($workshop->getIsVSIType() == true)
							$event_classes[] = 'workshop-is-vsi';

            $event_title = $workshop->getTheme()->getName();

            $temp = array(
                'id'        => $workshop->getId(),
                'className' => $event_classes,
                'url'   => $this->generateUrl('dashboard_workshop_get', ['id' => $workshop->getId()]),
                'title' => $event_title,
                'start' => $workshop->getDateStart()->format('c'),
                'end'   => $workshop->getDateEnd()->format('c')
            );

            if(true === $is_publisher)
                $temp['subbers_amount'] = count($workshop->getSubscribes());
						else
								$temp['nb_seats_left'] = $workshop->getNbSeatsLeft();

            $fullcalendar_events[] = $temp;
        }

        // Push formatted events into twig data
        $data['calendar'] = array(
            'events'  => json_encode($fullcalendar_events),
            'start'   => $calendarStartDate,
            'end'     => $calendarEndDate
        );

        return $this->render('dashboard/index.html.twig', $data);
    }


    /**
     * @Route("/dashboard/mes-ateliers", name="dashboard_user_workshops_subbed")
     */
    public function user_workshops_subbed(Request $request, Security $security, AuthorizationCheckerInterface $authChecker)
    {
        // Only access to classic users, PUBLISHER is the lowest level for admins
        if(true === $authChecker->isGranted('ROLE_PUBLISHER'))
            return $this->redirectToRoute('dashboard');

        $user           = $security->getUser();
        $entityManager  = $this->getDoctrine()->getManager();
        $repoSubs       = $entityManager->getRepository(WorkshopSubscribe::class);

        // Get user's subs
        $subscribes     = $repoSubs->findByUser($user);

        // Sort subs by date
        $workshops_by_date = array();
        foreach ($subscribes as $sub) {
            $key_date = $sub->getWorkshop()->getDateStart()->format('Y-m-d');
            $workshops_by_date[$key_date][] = $sub;
        }

        return $this->render('dashboard/my-workshops.html.twig', array(
            'meta'        => array('title' => 'Mes ateliers'),
            'stylesheets' => array('dashboard.css'),
            'scripts'     => array('dashboard.js'),
            'breadcrumb_links' => array(
              [
                'label' => 'Dashboard',
                'href' => $this->generateUrl('dashboard')
              ]
            ),
            'workshops_by_date'   => $workshops_by_date
        ));
    }



    /**
    * @Route("/dashboard/disable-help-modal", name="dashboard_disable_help_modal")
    */
    public function disable_help_modal(Request $request, Security $security)
    {
        if ($request->isXmlHttpRequest()) {
            $em   = $this->getDoctrine()->getManager();
            $user = $security->getUser();

            // Disable auto-show help modal
            $user->setHideHelpModal(true);

            // Persist
            $em->persist($user);

            // Save
            $em->flush();

            return $this->json([ 'query_status' => 1 ]);
        } else {
            // No direct access
            return $this->redirectToRoute('dashboard');
        }
    }
}
