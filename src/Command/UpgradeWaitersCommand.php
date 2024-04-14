<?php

namespace App\Command;

// Entities
use App\Entity\Workshop;
use App\Entity\WorkshopSubscribe;

// Components
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Symfony\Component\DependencyInjection\ContainerInterface;


class UpgradeWaitersCommand extends ContainerAwareCommand
{
    private $container;

    private $doctrine;

    private $mailer;

    private $twig;


    public function __construct($name = null, ContainerInterface $container) {
        parent::__construct($name);

        $this->container  = $container;
        $this->doctrine   = $this->container->get('doctrine');
        $this->mailer     = $this->container->get('mailer');
        $this->twig       = $this->container->get('twig');
    }

    protected function configure()
    {
        $this
            // the name of the command (the part after "bin/console")
            ->setName('workshops:waiters:upgrade')

            // the short description shown while running "php bin/console list"
            ->setDescription('Replace subber in waiting validation by users in waiting line.')

            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp('This command delete subscribers who has not confirm their presence.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('<fg=black;bg=green> [App:Workshops] Suppression des inscriptions d\'utilisateurs n\'ayant pas confirmé leur inscripion</>');

        // Script disabled
        $output->writeln('<fg=black;bg=yellow> Script désactivé</>');
        return null;

        $entityManager = $this->doctrine->getManager();
        $repoWorkshops = $entityManager->getRepository(Workshop::class);

        // Get workshops to confirm > TODO : Find a better way, Query ? GROUP BY ?
        $workshops_to_confirm = $repoWorkshops->findWorkshopsToConfirm();

        if(count($workshops_to_confirm) > 0) {
            // Change locale for localizeddate
            $this->container->get('translator')->setLocale('fr');

            foreach ($workshops_to_confirm as $workshop) {
                $output->writeln(' [<fg=cyan>' . $workshop->getTheme()->getName() . '</>]');
                $waiters = $workshop->getSubscribesByStatus(WorkshopSubscribe::STATUS_WAITING_SEATS);

                // If there is users in waiting seats for workshop to confirm
                if(count($waiters) > 0) {
                    $subbers_wait_confirm = $workshop->getSubscribesByStatus([
                      WorkshopSubscribe::STATUS_PRE_SUBSCRIBE,
                      WorkshopSubscribe::STATUS_WAITING_VALIDATION
                    ], count($waiters));

                    // Then check if there is users who has not confirm their seats
                    if (count($subbers_wait_confirm) > 0) {
                        $subber_to_mail = array();

                        $output->writeln('   <fg=red;bg=white>Désinscription automatique de : </>');
                        foreach ($subbers_wait_confirm as $sub) {
                            $user = $sub->getUser();

                            // Remove user's sub
                            $entityManager->remove($sub);

                            // Output
                            $output->writeln('   <fg=red;bg=white> - '.$user->getLastname(). ' ' .$user->getFirstname().' </>');

                            // Store user's identity & email to notify by email later
                            $subber_to_mail[$user->getEmail()] = $user->getLastname(). ' ' .$user->getFirstname();
                        }

                        // Flush removes
                        $entityManager->flush();

                        // Create message for mailer
                        $message = (new \Swift_Message('Suppression automatique de votre inscription à l\'atelier "'. $workshop->getTheme()->getName() .'"'))
                            ->setFrom(array('ne-pas-repondre@ateliers-ingeneria.fr' => 'Les Ateliers Ingeneria'))
                            ->setBody(
                                $this->twig->render(
                                    'emails/dashboard/subs-replace-by-waiter.html.twig',
                                    array(
                                      'workshop'  => $workshop
                                    )
                                ),
                                'text/html'
                            )
                        ;

                        $message->setBcc($subber_to_mail);

                        $this->mailer->send($message);
                    }

                    // If there is still seats left auto-subscribe waiters
                    $nb_seats_left = $workshop->getNbSeatsLeft();
                    if ($nb_seats_left > 0) {
                        // re-get waiters
                        $waiters = $workshop->getSubscribesByStatus(WorkshopSubscribe::STATUS_WAITING_SEATS);
                        if(count($waiters) > 0) {
                            $output->writeln('   <fg=green;bg=white>Il reste des places (?!) et des personnes en attente : </>');
                            $output->writeln('   <fg=green;bg=white>Insription automatique de : </>');

                            foreach ($waiters as $subber_waiting) {
                                // Break loop if no more seats for waiters
                                if($nb_seats_left < 1) break;

                                $user = $subber_waiting->getUser();

                                // Output
                                $output->writeln('   <fg=green;bg=white> - '.$user->getLastname(). ' ' .$user->getFirstname().' </>');

                                // Change status
                                $subber_waiting->setStatus(WorkshopSubscribe::STATUS_PRE_SUBSCRIBE);

                                // Decrease nb seats manually
                                $nb_seats_left--;
                            }

                            // Flush changes
                            $entityManager->flush();
                        }
                    }

                } else {
                    $output->writeln('   <fg=yellow>Aucune personne en file d\'attente</>');
                }

                $output->writeln('');
            }
        } else {
            return null;
        }
    }
}
