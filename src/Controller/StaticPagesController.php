<?php

namespace App\Controller;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class StaticPagesController extends Controller
{

    private $pages = array();

    public function __construct()
    {
        // Raw pages data

        $company = array(
            'name'        => 'Ingeneria',
            'address'     => '4 rue Gerin Ricard 13003 Marseille - France (métropolitaine)',
            'capital'     => '1 500€',
            'social_form' => 'SAS (Société par actions simplifiée)',
            'url'         => 'Ateliers-ingeneria.fr',
            'vta_number'  => '',
            'siren' => array(
                'number' => '752 183 988',
                'address' => 'Marseille B'
            )
        );

        $owner = array(
            'firstname' => 'Bertrand',
            'lastname'  => 'Heurfin',
            'phone'     => '+33 6 19 50 36 22',
            'email'     => 'contact@ingeneria.fr'
        );

        $developer = array(
            'firstname' => 'Aurélien',
            'lastname'  => 'Litti',
            'phone'     => '+33 6 95 06 40 91',
            'email'     => 'litti.aurelien@gmail.com'
        );

        $web_host = array(
            'name'        => 'OVH',
            'address'     => '2 rue Kellerman – BP 80157 – 59100 Roubaix – France',
            'social_form' => 'SAS (Société par actions simplifiée)',
            'phone'       => '+33 820 698 765'
        );
        


        // Pages list

        $this->pages['mentions-legales'] = array(
            'template' => 'static-pages/legal-terms.html.twig',
            'data' => array(
                'meta' => array('title' => 'Mentions Légales', 'robots' => 'noindex,nofollow'),
                'company'   => $company,
                'owner'     => $owner,
                'developer' => $developer,
                'web_host'  => $web_host
            )
        );


        $this->pages['a-propos'] = array(
            'template' => 'static-pages/about.html.twig',
            'data' => array(
                'meta' => array(
                    'title' => 'A propos',
                    'robots' => 'noindex,nofollow'
                ),
            )
        );


        $this->pages['donnees-personnelles'] = array(
            'template' => 'static-pages/personal-data.html.twig',
            'data' => array(
                'meta'          => array(
                    'title' => 'Données personnelles',
                    'robots' => 'noindex,nofollow'
                ),
                'data_manager'  => array(
                    'name' => 'Ingeneria',
                    'email' => 'contact@ingeneria.fr'
                ),
                'company'       => $company,
                'website'       => array(
                    'name' => 'Ateliers-Ingeneria.fr',
                    'host' => 'OVH',
                    'host_location' => 'France'
                ),
            )
        );
    }

    /**
     * @Route("/{page_slug}.html", name="static_pages")
     */
    public function index($page_slug)
    {
        if(isset($this->pages[$page_slug])) {
            $page = $this->pages[$page_slug];
            return $this->render($page['template'], $page['data']);
        } else {
            return $this->redirectToRoute('dashboard');
        }
    }
}
