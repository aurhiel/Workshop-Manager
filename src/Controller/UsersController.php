<?php

namespace App\Controller;

// Types
use App\Form\UserType;
use App\Form\WorkshopType;

// Entities
use App\Entity\User;
use App\Entity\UserVSI;
use App\Entity\Workshop;
use App\Entity\WorkshopSubscribe;

// Repositories
use App\Repository\UserRepository;
use App\Repository\UserVSIRepository;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
// TranslatorInterface $translator
use Symfony\Component\Translation\TranslatorInterface;

use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

class UsersController extends Controller
{

    const NB_USERS_BY_PAGE = 12;

    /**
     * @Route("/dashboard/utilisateurs/{id}/profil", name="admin_dashboard_user_settings")
     */
    public function userEditSettings(User $user, AuthorizationCheckerInterface $authChecker, Request $request, UserPasswordEncoderInterface $passwordEncoder)
    {
        $entityManager = $this->getDoctrine()->getManager();

        if (!$user) {
            throw $this->createNotFoundException('This user doesn\'t exist');
            // the above is just a shortcut for:
            // throw new NotFoundHttpException('The product does not exist');
        }

        // Users with Admin or Publisher role
        $is_editorial_user = (count(array_intersect(array('ROLE_SUPERADMIN', 'ROLE_ADMIN', 'ROLE_PUBLISHER'), $user->getRoles())) > 0);

        // IF user to edit is an admin or publisher AND authentified user has NOT role admin > redirect
        if($is_editorial_user && $authChecker->isGranted('ROLE_ADMIN') == false)
            return $this->redirectToRoute('admin_dashboard_users');

        // 1) handle the submit (will only happen on POST)
        $form = $this->createForm(UserType::class, $user, array('type_form' => 'edit'));

        // Only if editing a classic user
        if(in_array('ROLE_USER', $user->getRoles())) {
            // Push service type choice
            $form->add('serviceType', ChoiceType::Class, array(
              'label' => 'form_user.service_type.label',
              'label_attr' => ['class' => 'radio-inline radio-custom'],
              'expanded' => true,
              'multiple' => false,
              'choices' => array(
                'label.' . User::SERVICE_TYPE_ACTIV_CREA    => User::SERVICE_TYPE_ACTIV_CREA,
                'label.' . User::SERVICE_TYPE_ACTIV_PROJET  => User::SERVICE_TYPE_ACTIV_PROJET
              )
            ));

            // Push register end date input
            $form->add('registerEndDate', DateType::class, array(
              'label'   => 'form_user.register_end_date.label',
              'widget'  => 'single_text'
            ));

            // Push referent consultant
            $form->add('referentConsultant', EntityType::class, array(
                'class'         => User::class,
                'label'         => 'form_user.referent_consultant.label',
                'required'      => true,
                'placeholder'   => 'form_user.referent_consultant.placeholder',
                'query_builder' => function (UserRepository $repo) {
                    return $repo->findConsultant();
                },
                'choice_label'  => function ($user) {
                    return $user->getLastname() . ' ' . $user->getFirstname();
                }
            ));
        }

        // Push roles edit /!\ ONLY FOR ADMINS ROLE LEVEL
        if($authChecker->isGranted('ROLE_ADMIN')) {
            $form->add('roles', ChoiceType::class, array(
                'label'   => 'form_user.roles.label',
                'choices' =>
                    array (
                        'label.role_user'       => 'ROLE_USER',
                        'label.role_publisher'  => 'ROLE_PUBLISHER',
                        'label.role_admin'      => 'ROLE_ADMIN'
                    ),
                'multiple' => true,
                'required' => true,
            ));

            $form->add('is_consultant', CheckboxType::class, array(
                'label'       => 'form_user.is_consultant.label',
                'required'    => false,
                'label_attr'  => ['class' => 'checkbox-custom']
            ));
        }

        // Push archiving edit
        $form->add('isArchived', CheckboxType::class, array(
            'label'       => 'form_user.is_archived.label',
            'required'    => false,
            'label_attr'  => ['class' => 'checkbox-custom']
        ));

        // Push active/inactive edit
        $form->add('isActive', CheckboxType::class, array(
            'label'       => 'form_user.is_active.label',
            'required'    => false,
            'label_attr'  => ['class' => 'checkbox-custom']
        ));

        // 2) handle the submit (will only happen on POST)
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // 3) change password only if is not default password // TODO find a better way
            if($user->getPlainPassword() != '0ld-pa$$wo|2d') {
              $password = $passwordEncoder->encodePassword($user, $user->getPlainPassword());
              $user->setPassword($password);
            }

            // 4) Try or clear
            try {
                $request->getSession()->getFlashBag()->add('success', 'Modification(s) effectuée(s) avec succès.');
                $entityManager->flush();
                // Flush OK !
            } catch (\Exception $e) {
                // Something goes wrong
                $request->getSession()->getFlashBag()->add('error', 'Une erreur inconnue est survenue, veuillez essayer de nouveau.');
                $entityManager->clear();
            }
        }

        // Display a message when an user will be disabled
        if(in_array('ROLE_USER', $user->getRoles()) && $user->isEnabled() && $user->isOutOfDate())
          $request->getSession()->getFlashBag()->add('warning', 'Cet utilisateur arrive à la fin de sa période de prestation, il sera donc désactivé automatiquement en fin de journée.');

        $referer  = $request->headers->get('referer');
        // NOTE: Determines $url_back according to referer value
        // If $referer is null or redirecting to current user's profile or workshops then
        // redirect to generic users list's page with ROLE
        if (is_null($referer) || (preg_match('/\/ateliers|\/profil/', $referer) > 0)) {
            $roles = $user->getRoles();
            $main_user_role = reset($roles);
            $url_back = $this->generateUrl('admin_dashboard_users', array('role' => $main_user_role));
        } else {
            $url_back = $referer;
        }

        return $this->render(
            'dashboard/users/user-settings.html.twig',
            array(
              'meta'        => array(
                'title' => 'Profil de '.$user->getLastname().' '.$user->getFirstname()
              ),
              'stylesheets' => array('admin-dashboard.css'),
              'scripts'     => array('admin-dashboard.js'),
              'breadcrumb_links' => array(
                [
                  'label' => 'Dashboard',
                  'href' => $this->generateUrl('dashboard')
                ],
                [
                  'label' => 'Utilisateurs',
                  'href' => $url_back
                ],
              ),
              'user'        => $user,
              'form'        => $form->createView()
            )
        );
    }


    /**
     * @Route("/dashboard/utilisateurs/{id}/ateliers", name="admin_dashboard_user_workshops_subbed")
     */
    public function userWorkshopsSubbed(User $user, AuthorizationCheckerInterface $authChecker, Request $request)
    {
        $entityManager  = $this->getDoctrine()->getManager();

        // Get workshops or subscribers depending on user's roles
        if (in_array('ROLE_PUBLISHER', $user->getRoles()) ||
              in_array('ROLE_ADMIN', $user->getRoles()) ||
                in_array('ROLE_SUPERADMIN', $user->getRoles())) {
            // Get lecturer's workshops
            $r_workshop = $entityManager->getRepository(Workshop::class);
            $workshops  = $r_workshop->findByLecturer($user, array('date_start' => 'ASC'));

            // Sort workshops by date
            $workshops_by_date = array();
            foreach ($workshops as $workshop) {
                $key_date = $workshop->getDateStart()->format('Y-m-d');
                $workshops_by_date[$key_date][] = $workshop;
            }
        } else {
            // Get user's subs
            $repoSubs   = $entityManager->getRepository(WorkshopSubscribe::class);
            $subscribes = $repoSubs->findByUser($user);

            // Sort workshops by date
            $workshops_by_date = array();
            foreach ($subscribes as $sub) {
                $key_date = $sub->getWorkshop()->getDateStart()->format('Y-m-d');
                $workshops_by_date[$key_date][] = $sub;
            }
        }

        // Build add workshop form
        $workshop = new Workshop();
        $form_workshop = $this->createForm(WorkshopType::class, $workshop, array(
          // Change action
          'action' => $this->generateUrl('admin_dashboard_workshop_manage')
        ));

        // Back url = to referer only if it's not himself or go to user profile
        // Back url is the url to user's list (depending on {role}, {letter} and {page})
        $referer  = $request->headers->get('referer');
        $url_back = (preg_match('/\/ateliers|\/profil/', $referer) > 0) ? $this->generateUrl('admin_dashboard_users') : $referer;

        return $this->render(
            'dashboard/users/user-workshops-subbed.html.twig',
            array(
              'meta' => array(
                'title' => 'Liste des ateliers de '.$user->getLastname().' '.$user->getFirstname()
              ),
              'stylesheets'         => array('admin-dashboard.css'),
              'scripts'             => array('admin-dashboard.js'),
              'breadcrumb_links'    => array(
                [
                  'label' => 'Dashboard',
                  'href' => $this->generateUrl('dashboard')
                ],
                [
                  'label' => 'Utilisateurs',
                  'href' => $url_back
                ],
                [
                  'label' => $user->getLastname().' '.$user->getFirstname(),
                  'href' => $this->generateUrl('admin_dashboard_user_settings', [ 'id' => $user->getId() ])
                ]
              ),
              'user'              => $user,
              'workshops_by_date' => $workshops_by_date,
              'form_workshop'     => $form_workshop->createView()
            )
        );
    }


    /**
     * @Route("/dashboard/utilisateurs/{id}/supprimer", name="admin_dashboard_delete_user")
     */
    public function deleteUser(User $user, AuthorizationCheckerInterface $authChecker, Request $request)
    {
        $flashbag = $request->getSession()->getFlashBag();

        if(!$user) {
            // Set "not found" message
            $flashbag->add('error', "L'utilisateur que vous souhaitez supprimer n'existe pas.");
        } else {
            $em = $this->getDoctrine()->getEntityManager();

            // Retrieve user first and lastname before deleting it
            $user_identity = $user->getLastname().' '.$user->getFirstname();

            // Delete User & Flush
            $em->remove($user);
            $em->flush();

            // Set success message
            $flashbag->add('success', "L'utilisateur $user_identity a bien été supprimé.");
        }

        // Redirect to users list
        return $this->redirectToRoute('admin_dashboard_users');
    }


    /**
     * @Route("/dashboard/utilisateurs/rechercher", name="dashboard_admin_search_users")
     */
    public function search(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        // Retrieve user's inputs
        $lastname   = $request->request->get('lastname');
        $firstname  = $request->request->get('firstname');

        $users = array();
        $temps = array();

        // Retrieve classics users
        $r_user = $em->getRepository(User::class);
        $temps = $r_user->findByRoleAndStartLastnameOrFirstname('ROLE_USER', $lastname, $firstname);

        // Retrieve VSI users
        $r_user_vsi = $em->getRepository(UserVSI::class);
        $temps = array_merge($temps, $r_user_vsi->findByStartLastnameOrFirstname($lastname, $firstname));

        // Prepare users
        foreach ($temps as $key => $user) {
            $users[] = array(
                'id'        => $user->getId(),
                'lastname'  => $user->getLastname(),
                'firstname' => $user->getFirstname(),
                'email'     => $user->getEmail(),
                'is_vsi'    => preg_match('/(VSI)/', get_class($user))
            );
        }

        $data = array(
            'query_status'  => 1,
            'users'         => $users
        );

        //
        // Handle request (Ajax|Html)
        if ($request->isXmlHttpRequest()) {
            return $this->json($data);
        } else {
            // No direct access > subscribe and redirect to dashboard homepage
            return $this->redirectToRoute('dashboard');
        }
    }


    /**
     * Matches /dashboard/utilisateurs/[ROLE_USER|ROLE_PUBLISHER|ROLE_ADMIN]/{*}/{0-9}+
     *
     * @Route("/dashboard/utilisateurs/{role}/{first_letter}/{page}", name="admin_dashboard_users", defaults={"role"="ROLE_USER","first_letter"="A","page"=1}, requirements={"role":"ROLE_USER|ROLE_PUBLISHER|ROLE_ADMIN","page":"\d+"})
     */
    public function index($role, $first_letter, $page, AuthorizationCheckerInterface $authChecker, TranslatorInterface $translator)
    {
        // Roles list
        $roles = array(
            'ROLE_USER'       => $translator->trans('label.plural.role_user'),
            'ROLE_PUBLISHER'  => $translator->trans('label.plural.role_publisher'),
            'ROLE_ADMIN'      => $translator->trans('label.plural.role_admin')
        );

        // No admin = Only classic users edit
        if (false === $authChecker->isGranted('ROLE_ADMIN'))
            $roles = array('ROLE_USER' => $translator->trans('label.plural.role_user'));

        // Role doesn't exist ? > Redirect to users' home
        if (false === isset($roles[$role]))
            return $this->redirectToRoute('admin_dashboard_users');

        // Database vars
        $entityManager  = $this->getDoctrine()->getManager();
        $repoUser       = $entityManager->getRepository(User::class);

        // Prepare letters array
        $alphabet = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K',
        'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z');
        $letters = array();
        foreach ($alphabet as $alpha_letter) {
            $letters[$alpha_letter] = array(
                'letter'    => $alpha_letter,
                'nb_users'  => 0
            );
        }

        // Retrieve users alphas list
        $users_alphas = $repoUser->getNbUsersByRoleOnLastnameFirstLetter($role);
        $nb_pages = 0;
        // Set BDD alphas into letters
        foreach ($users_alphas as $users_alpha) {
            $letters[$users_alpha['users_alpha']]['nb_users'] = $users_alpha['users_count'];

            // Retrieve nb pages for the current $first_letter (ex: A) displayed
            if ($users_alpha['users_alpha'] == $first_letter) {
                $nb_pages_raw = ($users_alpha['users_count'] / self::NB_USERS_BY_PAGE);
                $nb_pages = floor($nb_pages_raw);

                // If there is decimal numbers,
                //  there is less than 12 people (=self::NB_USERS_BY_PAGE) to display
                //  > So we need to add 1 more page
                if (($nb_pages_raw - $nb_pages) > 0)
                    $nb_pages++;
            }
        }

        // Retrieve filtered users list
        $users = $repoUser->getUsersByRoleAndLastnameLetter($role, $first_letter, $page, self::NB_USERS_BY_PAGE);

        // If no users for this filters, redirect to first alpha letter with users
        if(count($users) < 1 && count($users_alphas) > 0) {
            return $this->redirectToRoute('admin_dashboard_users', array(
              'role'          => $role,
              'first_letter'  => $users_alphas[0]['users_alpha']
            ));
        }

        return $this->render('dashboard/users/index.html.twig', array(
            'meta'        => array('title' => 'Utilisateurs'),
            'stylesheets' => array('admin-dashboard.css'),
            'scripts'     => array('admin-dashboard.js'),
            'breadcrumb_links' => array(
              [
                'label' => 'Dashboard',
                'href' => $this->generateUrl('dashboard')
              ],
            ),
            'first_letter'  => $first_letter,
            'current_page'  => $page,
            'current_role'  => $role,
            'nb_pages'      => $nb_pages,
            'roles'       => $roles,
            'letters'     => $letters,
            'users'       => $users
        ));
    }
}
