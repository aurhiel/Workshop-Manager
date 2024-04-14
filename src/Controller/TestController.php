<?php

namespace App\Controller;

use App\Entity\Workshop;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Security;

class TestController extends Controller
{
    /**
     * @Route("/test", name="test")
     */
    public function index()
    {
        return $this->render('test/index.html.twig', [
            'controller_name' => 'TestController',
        ]);
    }


    /**
     * @Route("/test/workshop-confirm-open", name="test_workshop_confirm_open")
     */
    public function test_workshop_confirm_open()
    {
        $entityManager = $this->getDoctrine()->getManager();
        $repoWorkshops = $entityManager->getRepository(Workshop::class);

        // Get workshop
        $workshops_to_confirm = $repoWorkshops->findWorkshopsToConfirm();

        return $this->render(
            'test/workshops-to-confirm.html.twig',
            array( 'workshops' => $workshops_to_confirm )
        );
    }

    /**
     * @Route("/test/email/simple", name="test_email_simple")
     */
    public function test_simple_mail(\Swift_Mailer $mailer)
    {
        // Sender/Receiver
        $from     = array('ne-pas-repondre@ateliers-ingeneria.fr' => 'Les Ateliers Ingeneria');
        $to       = 'raven.roth999@gmail.com';
        // Uniq ID
        $uniq_id  = uniqid();
        // Message
        $message = (new \Swift_Message('Email de test'))
            ->setFrom($from)
            ->setTo($to)
            ->setBody(
                $this->renderView(
                    'emails/test/simple-mail.html.twig',
                    array(
                      'user'    => $user,
                      'uniq_id' => $uniq_id
                    )
                ),
                'text/html'
            )
        ;

        $mail_return = $mailer->send($message);
        // $mail_return = 0;

        if(1 == $mail_return) {
            $response = "<span class='alert alert-success'>Email envoyé avec succès :)</span>";
        } else {
            $response = "<span class='alert alert-danger'>/!\ Un problème est survenu lors de l'envoi du message !</span>";
        }

        return new Response(
            '<html>
              <head>
                <style>
                  body {
                    background-color: #ecf0f1;
                    color: #2c3e50;
                    font-family: monospace;
                    font-size: 12px;
                    line-height: 1.8;
                  }

                  p {
                    border-left: 4px solid #bac3cc;
                    padding: 4px 0 4px 8px;
                    margin: 10px 8px;
                  }

                  .alert {
                    background-color: #2c3e50;
                    color: #ecf0f1;
                    border-radius: 4px;
                    display: inline-block;
                    padding: 14px 18px;
                  }

                  .alert-success {
                    background-color: #27ae60;
                  }

                  .alert-danger {
                    background-color: #c0392b;
                  }
                </style>
              </head>
              <body>
                '.$response.'
                <p>
                  Expéditeur: <b>'. $from[key($from)] .' &lt;'. key($from) .'&gt;</b><br>
                  Destinataire: <b>'. $to .'</b><br>
                  Token: <b>'. $uniq_id .'</b>
                </p>
              </body>
            </html>'
        );
    }



    /**
     * @Route("/test/email/unsub-workshop/{id}", name="test_email_unsub_workshop")
     */
    public function test_email_unsub_workshop(Workshop $workshop, Request $request, Security $security, \Swift_Mailer $mailer)
    {
        $user = $security->getUser();
        if($workshop->isOpen()) {
            $message = (new \Swift_Message('Vous avez été inscrit(e) automatiquement à l\'atelier "'. $workshop->getTheme()->getName() .'"'))
                ->setFrom(array('ne-pas-repondre@ateliers-ingeneria.fr' => 'Les Ateliers Ingeneria'))
                ->setTo($user->getEmail())
                ->setBody(
                    $this->renderView(
                        'emails/dashboard/workshop-auto-subscribe.html.twig',
                        array(
                          'user'      => $user,
                          'workshop'  => $workshop
                        )
                    ),
                    'text/html'
                )
            ;

            $mailer->send($message);

            return $this->render(
                'emails/dashboard/workshop-auto-subscribe.html.twig',
                array(
                    'user'      => $user,
                    'workshop'  => $workshop
                )
            );
        } else {
            exit('Workshop is closed !');
        }
    }



    /**
     * @Route("/test/email/welcome", name="test_email_welcome")
     */
    public function test_email_welcome(Security $security, \Swift_Mailer $mailer)
    {
        $user = $security->getUser();
        if($user) {
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

            return $this->render(
                'emails/registration-confirm.html.twig',
                array( 'user' => $user )
            );
        } else {
            exit('You must be connected !');
        }
    }
}
