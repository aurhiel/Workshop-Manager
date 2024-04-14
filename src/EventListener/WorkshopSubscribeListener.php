<?php

namespace App\EventListener;

use Doctrine\ORM\Event\LifecycleEventArgs;
// use Doctrine\ORM\Event\OnFlushEventArgs;

// use App\Entity\User;
use App\Entity\WorkshopSubscribe;

class WorkshopSubscribeListener
{

    protected $twig;
    protected $mailer;

    public function __construct(\Twig_Environment $twig, \Swift_Mailer $mailer)
    {
        $this->twig   = $twig;
        $this->mailer = $mailer;
    }

    public function preRemove(WorkshopSubscribe $subscribe, LifecycleEventArgs $event)
    {
        $workshop     = $subscribe->getWorkshop();
        $em           = $event->getEntityManager();
        $uow          = $em->getUnitOfWork();
        // Only change users status from "waiting-line" to "pre-subscribe"
        // if the workshop is still open AND it isn't scheduled for delete
        // AND is there more than 0 or 0 seat (0 because a seat is currently deleting)
        if ($uow->isScheduledForDelete($workshop) == false && $workshop->isOpen() == true && $workshop->getNbSeatsLeft() > 0) {
            // NOTE ~
            // Because of "OrderBy" for $subscribes relation in "App\Entity\Workshop"
            // ->first() subscriber with "STATUS_WAITING_SEATS" is always the first
            // user in the workshop's waiting line.
            //
            // >>> So after a subscribe remove (postRemove()) we get the first user waiting and
            //     subscribe him to the workshop by changing his status.
            $subscribes_waiting = $workshop->getSubscribesByStatus(WorkshopSubscribe::STATUS_WAITING_SEATS, 1);

            if($subscribes_waiting->first() != false && $subscribes_waiting->first() != $subscribe) {
                $waiter_to_sub = $subscribes_waiting->first();
                $waiter_to_sub->setStatus(WorkshopSubscribe::STATUS_PRE_SUBSCRIBE);

                // Save statuts change
                $em->persist($waiter_to_sub);

                // Flush
                $em->flush();
            }
        }
    }

    public function postUpdate(WorkshopSubscribe $subscribe, LifecycleEventArgs $event)
    {
        $user_subbed  = $subscribe->getUser();
        $workshop     = $subscribe->getWorkshop();
        $em           = $event->getEntityManager();
        $uow          = $em->getUnitOfWork();
        $changes      = $uow->getEntityChangeSet($subscribe);

        if($workshop->isOpen() && !is_null($user_subbed)) {
            foreach ($changes as $name => $values) {
                // When subscribe status change >
                if($name == 'status') {
                    // NOTE ~
                    // Send email to subber in STATUS_WAITING_SEATS if is
                    // new status is "STATUS_PRE_SUBSCRIBE".
                    //
                    // >>> Happen when :
                    //      - user unsubscribe form a workshop
                    //      - "auto-unsub script" unsubscribe non-confirmed users 24h
                    //        before a workshop ("remonté de file")
                    //      - an admin unsubscribe a user
                    if($values[0] == WorkshopSubscribe::STATUS_WAITING_SEATS &&
                        $values[1] == WorkshopSubscribe::STATUS_PRE_SUBSCRIBE) {
                        // Create message for mailer
                        $message = (new \Swift_Message('Inscription automatique à l\'atelier "'. $workshop->getTheme()->getName() .'"'))
                            ->setFrom(array('ne-pas-repondre@ateliers-ingeneria.fr' => 'Les Ateliers Ingeneria'))
                            ->setTo($user_subbed->getEmail())
                            ->setBody(
                                $this->twig->render(
                                    'emails/dashboard/workshop-auto-subscribe.html.twig',
                                    array(
                                      'user'      => $user_subbed,
                                      'workshop'  => $workshop
                                    )
                                ),
                                'text/html'
                            )
                        ;

                        $this->mailer->send($message);
                    }

                    // NOTE ~
                    // Send email to subber who was downgrade from
                    // "STATUS_SUBSCRIBED|STATUS_PRE_SUBSCRIBE|STATUS_WAITING_VALIDATION"
                    // to "STATUS_WAITING_SEATS".
                    //
                    // >>> Happen when :
                    //      - an admin decrease the amount of seats of a workshop
                    if(in_array($values[0], array(
                        WorkshopSubscribe::STATUS_SUBSCRIBED,
                        WorkshopSubscribe::STATUS_PRE_SUBSCRIBE,
                        WorkshopSubscribe::STATUS_WAITING_VALIDATION)) &&
                          $values[1] == WorkshopSubscribe::STATUS_WAITING_SEATS) {
                        // Create message for mailer
                        $message = (new \Swift_Message('Restriction de places pour l\'atelier "'. $workshop->getTheme()->getName() .'"'))
                            ->setFrom(array('ne-pas-repondre@ateliers-ingeneria.fr' => 'Les Ateliers Ingeneria'))
                            ->setTo($user_subbed->getEmail())
                            ->setBody(
                                $this->twig->render(
                                    'emails/dashboard/workshop-less-seats.html.twig',
                                    array(
                                      'user'      => $user_subbed,
                                      'workshop'  => $workshop
                                    )
                                ),
                                'text/html'
                            )
                        ;

                        $this->mailer->send($message);
                    }
                }
            }
        }
    }
}
