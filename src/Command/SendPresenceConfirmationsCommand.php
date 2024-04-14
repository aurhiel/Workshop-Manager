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


class SendPresenceConfirmationsCommand extends ContainerAwareCommand
{
    private $container;

    private $doctrine;

    private $mailer;

    private $templating;


    public function __construct($name = null, ContainerInterface $container) {
        parent::__construct($name);

        $this->container  = $container;
        $this->doctrine   = $this->container->get('doctrine');
        $this->mailer     = $this->container->get('mailer');
        $this->templating = $this->container->get('twig');
    }

    protected function configure()
    {
        $this
            // the name of the command (the part after "bin/console")
            ->setName('workshops:send:emails-confirmations')

            // the short description shown while running "php bin/console list"
            ->setDescription('Send confirmations to users')

            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp('This command allows you to send email confirmations to users who has subscribed to workshops.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('<fg=black;bg=green> [App:Workshops] Envoi des emails de rappel de confirmation d\'inscription aux ateliers </>');

        $entityManager = $this->doctrine->getManager();
        $repoWorkshops = $entityManager->getRepository(Workshop::class);

        $this->container->get('translator')->setLocale('fr');

        // Get workshops to confirm > TODO : Find a better way, Query ? GROUP BY ?
        $workshops_to_confirm = $repoWorkshops->findWorkshopsToConfirm();
        $users_groups         = array();

        foreach ($workshops_to_confirm as $key => $workshop) {
            $subscribers = $workshop->getSubscribesByStatus([WorkshopSubscribe::STATUS_PRE_SUBSCRIBE, WorkshopSubscribe::STATUS_WAITING_VALIDATION]);

            foreach ($subscribers as $subscribe) {
                $subber = $subscribe->getUser();

                if (!is_null($subber)) {
                    $subber_slug = $subber->getUsername();

                    if($subber->isEnabled()) {
                        // Define user in array()
                        if(!isset($users_groups[$subber_slug])) {
                            $users_groups[$subber_slug] = array('mail_ws_ids' => ''.$key, 'user' => $subber);
                        } else {
                            $users_groups[$subber_slug]['mail_ws_ids'] .= ','.$key;
                        }
                    }
                }
            }
        }

        // Regroup user's into one last array > TODO : see above
        $emails = array();
        foreach ($users_groups as $user_slug => $data_user_group) {
            $email_key = $data_user_group['mail_ws_ids'];

            if(!isset($emails[$email_key])) {
                $emails[$email_key] = array(
                  'bcc_users' => array(),
                  'workshops' => array()
                );

                // Set workshops list ONCE
                $ws_ids = explode(',', $email_key);
                foreach ($ws_ids as $workshop_id) {
                  $emails[$email_key]['workshops'][] = $workshops_to_confirm[$workshop_id];
                }
            }

            // Push user top notify
            $emails[$email_key]['bcc_users'][$data_user_group['user']->getEmail()] = $data_user_group['user']->getFirstname().' '.$data_user_group['user']->getLastname();
        }


        if(count($emails) > 0) {
            // Send emails
            $output->writeln('');
            $output->writeln('<fg=cyan>  '.count($emails).' email(s)</> vont être expédiés à destination de <fg=blue>'.count($users_groups).' personne(s) inscrite(s)</>');
            $output->writeln('');

            $num_email = 1;
            foreach ($emails as $data_email) {
                $output->writeln('  [<fg=cyan>#'.$num_email.'</>] - Envoi à : <fg=blue>'.implode(', ', $data_email['bcc_users']).'</>');

                $message = (new \Swift_Message("Ouverture des confirmations d'inscription"))
                ->setFrom(array('ne-pas-repondre@ateliers-ingeneria.fr' => 'Les Ateliers Ingeneria'))
                ->setBody(
                  $this->templating->render(
                    'emails/dashboard/subs-validation-open.html.twig',
                    array(
                      'workshops_date' => $data_email['workshops'][0]->getDateStart(),
                      'workshops' => $data_email['workshops']
                    )
                  ),
                  'text/html'
                );

                // Set All users emails
                $message->setBcc($data_email['bcc_users']);

                // Send email
                $this->mailer->send($message);

                // Increment number for display
                $num_email++;
            }
        } else {
            $num_email = 0;

            $output->writeln('');
            $output->writeln('<fg=default>  Aucun email à expédier.</>');
            $output->writeln('');
        }

        return ($num_email == count($emails));
    }
}
