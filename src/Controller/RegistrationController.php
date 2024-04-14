<?php

namespace App\Controller;

// Entities
use App\Entity\User;
use App\Form\UserType;

// Repositories
use App\Repository\UserRepository;

// Components
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

// Form
use Symfony\Bridge\Doctrine\Form\Type\EntityType;


class RegistrationController extends Controller
{
    /**
     * @Route("/inscription", name="user_registration")
     */
    public function registerAction(Request $request, UserPasswordEncoderInterface $passwordEncoder, \Swift_Mailer $mailer, AuthorizationCheckerInterface $authChecker)
    {
        if (true === $authChecker->isGranted('IS_AUTHENTICATED_FULLY'))
            return $this->redirectToRoute('dashboard');

        // 1) build the form
        $user = new User();
        $form = $this->createForm(UserType::class, $user);

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

        // 2) handle the submit (will only happen on POST)
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // 3) Encode the password (you could also do this via Doctrine listener)
            $password = $passwordEncoder->encodePassword($user, $user->getPlainPassword());
            $user->setPassword($password);

            // 4) Set the date end of user's access to the service according to the type of service choosen
            $registerEndDate = new \DateTime(($user->getServiceType() == User::SERVICE_TYPE_ACTIV_CREA)?'+3 month':'+2 month');
            $registerEndDate->setTime(8,0,0); // 8h00
            $user->setRegisterEndDate($registerEndDate);

            // Set default role
            $user->setRoles(array('ROLE_USER'));

            // 5) save the User!
            $entityManager = $this->getDoctrine()->getManager();
            $repoUser = $entityManager->getRepository('App:User');
            $entityManager->persist($user);

            // 6) User already exist ?
            if(!empty($repoUser->loadUserByUsername($user->getUsername()))) {
                $request->getSession()->getFlashBag()->add('error', 'Cet utilisateur existe déjà, veuillez utiliser une adresse email différente.');
            } else {
                // 7) Try or clear
                try {
                    $entityManager->flush();
                    // Flush OK ! > Send email to user and redirect to dashboard

                    // Send email to user
                    $message = (new \Swift_Message('Confirmation d\'inscription'))
                        ->setFrom(array('ne-pas-repondre@ateliers-ingeneria.fr' => 'Les Ateliers Ingeneria'))
                        ->setTo($user->getEmail())
                        ->setBody(
                            $this->renderView(
                                'emails/registration-confirm.html.twig',
                                array( 'user' => $user )
                            ),
                            'text/html'
                        )
                    ;
                    $mailer->send($message);

                    // Send email to referent consultant
                    $consultant         = $user->getReferentConsultant();
                    $message_consultant = (new \Swift_Message('Veuillez valider l\'inscription de ' . $user->getFirstname() . ' ' . $user->getLastname()))
                        ->setFrom(array('ne-pas-repondre@ateliers-ingeneria.fr' => 'Les Ateliers Ingeneria'))
                        ->setTo($consultant->getEmail())
                        ->setBody(
                            $this->renderView(
                                'emails/new-registration.html.twig',
                                array(
                                    'user'        => $user,
                                    'consultant'  => $consultant
                                )
                            ),
                            'text/html'
                        )
                    ;
                    $mailer->send($message_consultant);

                    // Add success message
                    $request->getSession()->getFlashBag()->add('success', 'Inscription effectuée avec succès !');

                    // Add waiting activation message
                    $request->getSession()->getFlashBag()->add('warning', 'Votre compte est inactif, une fois activé par votre consultant référent, vous pourrez vous connecter au site.');

                    // Redirect to dashboard
                    return $this->redirectToRoute('dashboard');
                } catch (\Exception $e) {
                    // Something goes wrong
                    $request->getSession()->getFlashBag()->add('error', 'Une erreur inconnue est survenue, veuillez essayer de nouveau.');
                    $entityManager->clear();
                }
            }
        }

        return $this->render(
            'registration/register.html.twig',
            array(
              'meta' => array('title' => 'Inscription'),
              'form' => $form->createView(),
            )
        );
    }
}
