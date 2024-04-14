<?php

namespace App\Controller;

// Entities
use App\Entity\ResetPassword;
use App\Entity\User;

// Repositories
use App\Repository\UserRepository;

// Forms
use App\Form\ResetPasswordType;

// Components
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;


class SecurityController extends Controller
{
    /**
     * @Route("/connexion", name="login")
     */
    public function login(Request $request, AuthenticationUtils $authenticationUtils, AuthorizationCheckerInterface $authChecker)
    {
        // NOTE ~ If ajax call for login = user is logout and try to do something
        // but redirected to login ... so 99 = reload for StealthRaven
        if ($request->isXmlHttpRequest())
            return $this->json([ 'query_status' => 99 ]);

        if (true === $authChecker->isGranted('IS_AUTHENTICATED_FULLY'))
            return $this->redirectToRoute('dashboard');

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();

        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        // errors = submit
        if (!is_null($error)) {
            $em = $this->getDoctrine()->getManager();
            $repoUser = $em->getRepository(User::class);

            // Get user
            $user = $repoUser->loadUserByUsername($lastUsername);
            $now  = new \DateTime();

            // Only for DISABLED users
            if (!empty($user) && $user->getIsActive() == false) {
                // Diff between now and user's register date
                $diff_register_date = $now->diff($user->getRegisterDate());
                $diff_register_end_date = $now->diff($user->getRegisterEndDate());

                // dump($diff_register_end_date->invert, $diff_register_end_date->format('%a'));

                // Under 2 days of register, user's account will be activated
                //    by his referent consultant
                if ($diff_register_date->format('%a') < 2) {
                    $error = [
                        'type'    => 'warning',
                        'message' => 'Le compte est désactivé, votre consultant référent l\'activera sous peu, merci de bien vouloir patienter.'
                    ];
                // If not yet end of register OR register end date havn't exceed 7 days
                //    then get link to ask reactivation
                } else if ($diff_register_end_date->invert == 0 || $diff_register_end_date->format('%a') < 8) {
                    // Retrieve url to ask a reactivation
                    $url_ask_reactivation = $this->generateUrl('ask_reactive_account', array('username' => $user->getUsername()));
                    $error = [
                        'type'    => 'warning',
                        'message' => 'Le compte est désactivé, <a href="'.$url_ask_reactivation.'">faire une demande de réactivation</a>, auprès de mon consultant référent.'
                    ];
                }
            }
        }

        return $this->render('security/login.html.twig', array(
            'meta'          => array('title' => 'Identification'),
            'last_username' => $lastUsername,
            'error'         => $error,
        ));
    }

    /**
    * @Route("/demande-reactivation/{username}", name="ask_reactive_account")
    */
    public function ask_reactive_account($username, Request $request, \Swift_Mailer $mailer)
    {
        $em = $this->getDoctrine()->getManager();
        $repoUser = $em->getRepository(User::class);

        // Get user & test if user exist
        //   > redirect to "/login" event if user doesn't exist, to not helping crawling bots
        $user = $repoUser->loadUserByUsername($username);
        if (!empty($user)) {
            // Get if user has already ask a reactivation
            $has_already_ask_react = ($user->getAskReactivationExpiresAt() != null && ($user->getAskReactivationExpiresAt() > time()));
            $fb = $request->getSession()->getFlashBag();

            // dump($user->getAskReactivationExpiresAt(), time());
            // exit;

            if (!$has_already_ask_react) {
                // 1) Set new expire reactivation date (now + 1 day)
                $user->setAskReactivationExpiresAt(new \DateInterval('P1D'));

                // 2) Persist
                $em->persist($user);

                // 3) Try to save (flush) or clear
                try {
                    // Flush OK !
                    $em->flush();

                    // 4) Get user's referent consultant
                    $consultant = $user->getReferentConsultant();

                    // 5) Send ask reactivation email to user's referent consultant
                    $message = (new \Swift_Message('Demande de ré-activation de la part de : ' . $user->getFirstname() . ' ' . $user->getLastname()))
                        ->setFrom(array('ne-pas-repondre@ateliers-ingeneria.fr' => 'Les Ateliers Ingeneria'))
                        ->setTo($consultant->getEmail())
                        ->setBody(
                            $this->renderView(
                                'emails/security/account/ask-reactivation.html.twig',
                                array(
                                  'user'        => $user,
                                  'consultant'  => $consultant
                                )
                            ),
                            'text/html'
                        );
                    $mailer->send($message);

                    // Set success message that email has been sent
                    $fb->add('success', 'Votre demande de ré-activation a bien été envoyé à votre consultant référent, vous serez prévenu par e-mail lors de la ré-activation de votre compte.');
                } catch (\Exception $e) {
                    // Something goes wrong
                    $em->clear();
                    $fb->add('error', 'Un problème est survenu lors de la demande de ré-activation de votre compte, veuillez ré-essayer ultérieurement.');
                }
            } else {
                // Set warning message, that user already ask a reactivation today
                $fb->add('warning', 'Vous avez déjà effectué une demande de ré-activation, veuillez ré-essayer utérieurement.');
            }
        }

        return $this->redirectToRoute('login');
    }

    /**
    * @Route("/mot-de-passe/oublie", name="forgotten_password")
    */
    public function forgotten_password(Request $request, AuthorizationCheckerInterface $authChecker, \Swift_Mailer $mailer)
    {
        // user connected = redirect
        if (true === $authChecker->isGranted('IS_AUTHENTICATED_FULLY'))
            return $this->redirectToRoute('dashboard');

        // Input:login
        $username = $request->request->get('username');

        if(!empty($username)) {
          $em     = $this->getDoctrine()->getManager();
          $r_user = $em->getRepository(User::class);

          $fb   = $request->getSession()->getFlashBag();
          $user = $r_user->loadUserByUsername($username);

          if(!empty($user)) {
              $r_reset_password = $em->getRepository(ResetPassword::class);
              $reset_pwd = $r_reset_password->findOneByUser($user);
              $has_send_token = false;

              // Get if user has already reset is password
              if($reset_pwd != null)
                  $has_send_token = ($reset_pwd->getResetTokenExpiresAt() != null && ($reset_pwd->getResetTokenExpiresAt() > time()));
              else
                  $reset_pwd = new ResetPassword();

              if($has_send_token == false) {
                  // 1) Insert/Update ResetPassword entity fields & generate new token
                  $generated_token = $reset_pwd
                    ->setUser($user)
                    ->generateResetToken(new \DateInterval('PT1H'));

                  // 2) Save !
                  $em->persist($reset_pwd);

                  // 3) Try to save (flush) or clear
                  try {
                      // Flush OK !
                      $em->flush();

                      // 4) Send reset link to user
                      $message = (new \Swift_Message('Demande de réinitialisation de mot de passe'))
                          ->setFrom(array('ne-pas-repondre@ateliers-ingeneria.fr' => 'Les Ateliers Ingeneria'))
                          ->setTo($user->getEmail())
                          ->setBody(
                              $this->renderView(
                                  'emails/security/password/forgotten.html.twig',
                                  array(
                                    'user' => $user,
                                    'token' => $generated_token
                                  )
                              ),
                              'text/html'
                          );

                      $mailer->send($message);

                      $fb->add('success', 'Le lien pour réinitialiser votre mot de passe a bien été généré et vous a été envoyé par email, veuillez vous rendre sur boîte afin de continuer la procédure.');
                  } catch (\Exception $e) {
                      // Something goes wrong
                      $em->clear();
                      $fb->add('error', 'Un problème est survenu lors de la génération de la clé, veuillez ré-essayer ultérieurement.');
                  }
              } else {
                  $fb->add('warning', 'Vous avez déjà effectué une demande de réinitialisation, veuillez ré-essayer utérieurement.');
              }
          } else {
              $fb->add('warning', 'Aucun compte trouvé pour cet identifiant');
          }
        }

        return $this->render('security/password/forgotten.html.twig', array(
            'meta'          => array( 'title' => 'Réinitialisation de mot de passe' ),
            'last_username' => $username,
        ));
    }

    /**
    * @Route("/mot-de-passe/reinitialisation/{token}", name="reset_password")
    */
    public function reset_password($token, Request $request, UserPasswordEncoderInterface $passwordEncoder, AuthorizationCheckerInterface $authChecker)
    {
        // user connected = redirect
        if (true === $authChecker->isGranted('IS_AUTHENTICATED_FULLY'))
            return $this->redirectToRoute('dashboard');

        $fb = $request->getSession()->getFlashBag();
        $em = $this->getDoctrine()->getManager();
        $r_reset_password = $em->getRepository(ResetPassword::class);
        $reset_pwd        = $r_reset_password->findOneByResetToken($token);

        if($reset_pwd != null) {
            $user = $reset_pwd->getUser();
            $form = $this->createForm(ResetPasswordType::class, $user);

            // 1) handle the submit (will only happen on POST)
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $password = $passwordEncoder->encodePassword($user, $user->getPlainPassword());
                $user->setPassword($password);

                // 2) Persist the entity (update password)
                $em->persist($user);

                // 3) Clear the reset token
                $reset_pwd->clearResetToken();

                // 4) Try to flush or clear
                try {
                    // Flush !
                    $em->flush();

                    // Add success message
                    $fb->add('success', 'Votre mot de passe a bien été modifié, vous pouvez maintenant l\'utiliser pour vous connecter.');

                    // Redirect to login page
                    return $this->redirectToRoute('login');
                } catch (\Exception $e) {
                    // Something goes wrong
                    $fb->add('error', 'Une erreur inconnue est survenue, veuillez essayer de nouveau.');
                    $em->clear();
                }
            }

            return $this->render('security/password/reset.html.twig', array(
                'meta' => array('title' => 'Nouveau mot de passe'),
                'form' => $form->createView(),
            ));
        } else {
          $fb->add('warning', 'Lien de réinitialisation de mot de passe invalide');
          return $this->redirectToRoute('login');
        }
    }
}
