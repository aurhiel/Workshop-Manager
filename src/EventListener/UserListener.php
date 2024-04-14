<?php

namespace App\EventListener;

use Doctrine\ORM\Event\LifecycleEventArgs;

// Entities
use App\Entity\User;

class UserListener
{

    protected $twig;
    protected $mailer;

    public function __construct(\Twig_Environment $twig, \Swift_Mailer $mailer)
    {
        $this->twig   = $twig;
        $this->mailer = $mailer;
    }

    public function postUpdate(User $user, LifecycleEventArgs $event)
    {
        $em       = $event->getEntityManager();
        $uow      = $em->getUnitOfWork();
        $changes  = $uow->getEntityChangeSet($user);

        if (!empty($changes) && isset($changes['isActive'])) {
            if($changes['isActive'] == true) {
                // If a user is re-activated > then send an email to him
                $active_changes = $changes['isActive'];
                if($active_changes[1] === true) {
                    // Send email to re-activated user
                    $message = (new \Swift_Message("Activation de votre compte des Ateliers Ingeneria"))
                        ->setFrom(array('ne-pas-repondre@ateliers-ingeneria.fr' => 'Les Ateliers Ingeneria'))
                        ->setTo($user->getEmail())
                        ->setBody(
                            $this->twig->render(
                                'emails/security/account/reactivated.html.twig',
                                array( 'user' => $user )
                            ),
                            'text/html'
                        );
                    $this->mailer->send($message);
                }
            }
        }
    }
}
