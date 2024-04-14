<?php
namespace App\Controller;

// Form
use App\Form\UserType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

// Entities
use App\Entity\User;

// Repositories
use App\Repository\UserRepository;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;

class ProfileController extends Controller
{
    /**
     * @Route("/profil", name="profile_settings")
     */
    public function profileSettings(AuthorizationCheckerInterface $authChecker, Request $request, Security $security, UserPasswordEncoderInterface $passwordEncoder)
    {
        // 2) handle the submit (will only happen on POST)
        $user = $security->getUser();

        $form = $this->createForm(UserType::class, $user, array('type_form' => 'edit'));

        // Remove pole emploi ID for publisher's and more
        if (true === $authChecker->isGranted('ROLE_PUBLISHER'))
          $form->remove('idPoleEmploi');

        // Only if editing a classic user
        if(in_array('ROLE_USER', $user->getRoles())) {
            // Push referent consultant
            $form->add('referentConsultant', EntityType::class, array(
                'class'         => User::class,
                'label'         => 'form_user.referent_consultant.label',
                'required'      => true,
                'placeholder'   => 'form_user.referent_consultant.placeholder',
                'query_builder' => function (UserRepository $repo) {
                    return $repo->findConsultant(false, true);
                },
                'choice_label'  => function ($user) {
                    return $user->getLastname() . ' ' . $user->getFirstname();
                }
            ));
        } else {
            // dump('admin ?');
        }

        // 2) handle the submit (will only happen on POST)
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // 3) change password only if is not default password // TODO find a better way
            if($user->getPlainPassword() != '0ld-pa$$wo|2d') {
              $password = $passwordEncoder->encodePassword($user, $user->getPlainPassword());
              $user->setPassword($password);
            }

            $entityManager = $this->getDoctrine()->getManager();

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

        return $this->render(
            'dashboard/profile/settings.html.twig',
            array(
              'meta'        => array('title' => 'Profil'),
              'stylesheets' => array('dashboard.css'),
              'scripts'     => array('dashboard.js'),
              'breadcrumb_links' => array(
                [
                  'label' => 'Dashboard',
                  'href' => $this->generateUrl('dashboard')
                ]
              ),
              'form'        => $form->createView()
            )
        );
    }
}
