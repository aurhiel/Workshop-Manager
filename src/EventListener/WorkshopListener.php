<?php

namespace App\EventListener;

use Doctrine\ORM\Event\LifecycleEventArgs;
// use Doctrine\ORM\Event\OnFlushEventArgs;

use App\Entity\Workshop;

class WorkshopListener
{

    protected $twig;
    protected $mailer;

    public function __construct(\Twig_Environment $twig, \Swift_Mailer $mailer)
    {
        $this->twig   = $twig;
        $this->mailer = $mailer;
    }

    public function postRemove(Workshop $workshop, LifecycleEventArgs $event)
    {
        // If workshop is still open, retrieve subbers and notify them
        if($workshop->isOpen()) {
            // Contact subbers
            $subbers_mailing_list = $workshop->getSubscribesMailingList();
            if (count($subbers_mailing_list) > 0) {
                $message = (new \Swift_Message("Annulation de l'atelier : ".$workshop->getTheme()->getName()))
                ->setFrom(array('ne-pas-repondre@ateliers-ingeneria.fr' => 'Les Ateliers Ingeneria'))
                ->setBody(
                    $this->twig->render(
                        'emails/dashboard/workshop-deleted.html.twig',
                        array( 'workshop' => $workshop )
                    ),
                    'text/html'
                );

                // Set subbers emails
                $message->setBcc($subbers_mailing_list);

                // Send email
                $this->mailer->send($message);
            }
        }
    }

    public function postUpdate(Workshop $workshop, LifecycleEventArgs $event)
    {
        if ($workshop->isOpen()) {
            $em           = $event->getEntityManager();
            $uow          = $em->getUnitOfWork();
            $changes      = $uow->getEntityChangeSet($workshop);

            if (!empty($changes)) {
                $changes_to_notify = [];
                // NOTE ~
                // Change on seats and workshop's theme are not notified to users
                foreach ($changes as $name => $values) {
                    if ($name != 'nb_seats'  && $name != 'theme') {
                        $changes_to_notify[$name] = $values;
                    }
                }

                // If workshop has changes (but not the amount of seats) : contact subbers
                if (!empty($changes_to_notify)) {
                    $subbers_mailing_list = $workshop->getSubscribesMailingList();
                    if (count($subbers_mailing_list) > 0) {
                        $message = (new \Swift_Message("Modification de l'atelier : ".$workshop->getTheme()->getName()))
                        ->setFrom(array('ne-pas-repondre@ateliers-ingeneria.fr' => 'Les Ateliers Ingeneria'))
                        ->setBody(
                            $this->twig->render(
                                'emails/dashboard/workshop-edited.html.twig',
                                array( 'workshop' => $workshop, 'changes' => $changes )
                            ),
                            'text/html'
                        );

                        // Set subbers emails
                        $message->setBcc($subbers_mailing_list);

                        // Send email
                        $this->mailer->send($message);
                    }
                }
            }
        }
    }
}
