<?php

namespace App\EventListener;

// use Doctrine\Common\Persistence\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\OnFlushEventArgs;
// use Doctrine\ORM\Event\LifecycleEventArgs;

use App\Entity\Workshop;
use App\Entity\WorkshopSubscribe;

class WaitingLineMaintainer
{
    public function onFlush(OnFlushEventArgs $eventArgs)
    {
        $em   = $eventArgs->getEntityManager();
        $uow  = $em->getUnitOfWork();
        $updatedEntities = $uow->getScheduledEntityUpdates();

        foreach ($updatedEntities as $entity) {
            // Only act on "Workshop" entities
            if ($entity instanceof Workshop) {
                // Only for opened Workshop
                // if ($entity->isOpen()) {
                    $changes = $uow->getEntityChangeSet($entity);

                    // Loop on entity's field changes
                    foreach ($changes as $name => $values) {
                        // Changes on 'nb_seats' : update subscribes
                        if ($name == 'nb_seats') {
                            $diff_seats = $values[1] - $values[0];

                            // More seats : subscriber some waiters
                            if ($diff_seats > 0) {
                                // NOTE ~
                                // "OrderBy" for $subscribes relation in "App\Entity\Workshop"
                                // ->first() subscriber with "STATUS_WAITING_SEATS" is always the first
                                // user in the workshop's waiting line.

                                if($entity->getNbSeatsTaken() < $values[1]) {
                                    $subbers_in_wait = $entity->getSubscribesByStatus(WorkshopSubscribe::STATUS_WAITING_SEATS, $diff_seats);

                                    foreach ($subbers_in_wait as $subber) {
                                      $subber->setStatus(WorkshopSubscribe::STATUS_PRE_SUBSCRIBE);
                                      // Update changes on subscribe status : equivalent to .flush()
                                      $metaData = $em->getClassMetadata('App\Entity\WorkshopSubscribe');
                                      $uow->computeChangeSet($metaData, $subber);
                                    }
                                }
                            }

                            // Less seats : unsub some people
                            if ($diff_seats < 0) {
                                $subscribes = $entity->getSubscribesByStatus(array(
                                    WorkshopSubscribe::STATUS_SUBSCRIBED,
                                    WorkshopSubscribe::STATUS_PRE_SUBSCRIBE,
                                    WorkshopSubscribe::STATUS_WAITING_VALIDATION
                                ));

                                if (count($subscribes) > $values[1]) {
                                    $subs_to_delete = array_slice(array_reverse($subscribes->toArray()), 0, ($diff_seats * -1));
                                    foreach ($subs_to_delete as $subscribe) {
                                        $subscribe->setStatus(WorkshopSubscribe::STATUS_WAITING_SEATS);
                                        // Update changes
                                        $metaData = $em->getClassMetadata('App\Entity\WorkshopSubscribe');
                                        $uow->computeChangeSet($metaData, $subscribe);
                                    }
                                }
                            }
                        }
                    }
                // }
            }
        }
    }
}
