<?php

namespace App\Controller;

use App\Form\AddressType;
use App\Entity\Address;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class AddressController extends Controller
{
    /**
     * @Route("/dashboard/adresses", name="admin_dashboard_addresses")
     */
    public function index(AuthorizationCheckerInterface $authChecker, Request $request)
    {
        // 1) Build the form
        $entity = new Address();
        $form_entity = $this->createForm(AddressType::class, $entity, array(
          // Change action for Stealth Raven Ajax Plugin
          'action' => $this->generateUrl('admin_dashboard_addresses_manage')
        ));

        // Get all entities
        $entityManager  = $this->getDoctrine()->getManager();
        $repoAddress    = $entityManager->getRepository(Address::class);

        $entities       = $repoAddress->findAll();

        // Push data for Twig
        $data = array(
          'meta'          => array('title' => 'Adresses'),
          'stylesheets'   => array('admin-dashboard.css'),
          'scripts'       => array('admin-dashboard.js'),
          'breadcrumb_links' => array(
            [
              'label' => 'Dashboard',
              'href' => $this->generateUrl('dashboard')
            ]
          ),
          'form_address'  => $form_entity->createView(),
          'addresses'     => $entities
        );

        return $this->render('dashboard/addresses/index.html.twig', $data);
    }


    /**
     * @Route("/dashboard/adresses/gerer", name="admin_dashboard_addresses_manage")
     */
    public function manage(AuthorizationCheckerInterface $authChecker, Request $request)
    {
        $id_entity      = (int) $request->request->get('id'); // Edit entity ?
        $entityManager  = $this->getDoctrine()->getManager();

        if(!empty($id_entity) && $id_entity > 0) {
            // Get Entity to edit
            $is_new             = false;
            $repoEntity         = $entityManager->getRepository(Address::class);
            $entity             = $repoEntity->find($id_entity);
            $message_status_ok  = 'L\'adresse a bien été modifiée';
            $message_status_nok = 'Un problème est survenu lors de la modification de l\'adresse';
        } else {
            // New Entity
            $is_new             = true;
            $entity             = new Address();
            $message_status_ok  = 'L\'adresse a bien été ajoutée';
            $message_status_nok = 'Un problème est survenu lors de l\'ajout de l\'adresse';
        }

        // 1) Build the form
        $form_entity = $this->createForm(AddressType::class, $entity);

        // 2) Handle request
        $form_entity->handleRequest($request);
        $data = array();

        if ($form_entity->isSubmitted() && $form_entity->isValid()) {
            // 3) Save !
            $entityManager->persist($entity);

            // 4) Try to save (flush) or clear
            try {
                // Flush OK !
                $entityManager->flush();

                $data = array(
                    'query_status'    => 1,
                    'message_status'  => $message_status_ok,
                    'id_entity'       => $entity->getId(),
                    'is_new_entity'   => $is_new,
                    'form_data'       => $this->formatDataForStealthRaven($entity)
                );

                // Clear/reset form
                $form_entity = $this->createForm(AddressType::class, $entity);
            } catch (\Exception $e) {
                // Something goes wrong
                $entityManager->clear();
                $data = array(
                    'query_status'    => 0,
                    'exception'       => $e->getMessage(),
                    'message_status'  => $message_status_nok
                );
            }
        }

       if ($request->isXmlHttpRequest()) {
          // Return JSON data
          return $this->json($data);
       } else {
          // Redirect to home entities
          return $this->redirectToRoute('admin_dashboard_addresses');
       }
    }


    /**
     * @Route("/dashboard/adresses/supprimer/{id}", name="admin_dashboard_addresses_del")
     */
    public function delete(Address $entity, AuthorizationCheckerInterface $authChecker, Request $request)
    {
        if(!empty($entity)) {
          $entityManager      = $this->getDoctrine()->getManager();
          $id_entity_deleted  = $entity->getId();
          $entityManager->remove($entity);

          // Try to save (flush) or clear
          try {
            // Flush OK !
            $entityManager->flush();
            $data = array(
              'query_status' => 1,
              'id_entity'     => $id_entity_deleted,
            );
          } catch (\Exception $e) {
            // Something goes wrong
            $entityManager->clear();
            $data = array(
              'query_status'    => 0,
              'exception'       => $e->getMessage(),
              'message_status'  => 'Un problème est survenu lors de la suppression de l\'adresse'
            );
          }
        } else {
          $data = array(
            'query_status'    => 0,
            'message_status'  => 'Aucune adresse n\'existe pour cet ID'
          );
        }

        if ($request->isXmlHttpRequest()) {
          return $this->json($data);
        } else {
          // Not accessible by URL
          return $this->redirectToRoute('admin_dashboard_addresses');
        }
    }


    /**
     * @Route("/dashboard/adresses/{id}", name="admin_dashboard_addresses_get")
     */
    public function show(Address $entity, AuthorizationCheckerInterface $authChecker, Request $request)
    {
        // Set query status
        $data = array( 'query_status' => (!empty($entity) ? 1 : 0) );

         if ($request->isXmlHttpRequest()) {
            $data['message_status'] = 'Un problème est survenu lors de la récupération de l\'adresse';

            if ($data['query_status'] == 1) {
              $data['id_entity'] = $entity->getId();
              $data['form_data'] = $this->formatDataForStealthRaven($entity);

              // No problem = no message
              unset($data['message_status']);
            }

            return $this->json($data);
         } else {
            // No direct access
            return $this->redirectToRoute('admin_dashboard_addresses');
         }
    }


    private function formatDataForStealthRaven(Address $entity) {
        return array(
          'address_name'          => $entity->getName(),
          'address_lat_position'  => $entity->getLatPosition(),
          'address_lng_position'  => $entity->getLngPosition(),
        );
    }
}
