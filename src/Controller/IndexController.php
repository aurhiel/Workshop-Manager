<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class IndexController extends Controller
{
    /**
     * @Route("/", name="home")
     */
    public function index()
    {
        // return $this->render('index.html.twig', array());
        // No homepage right now, so redirect to login page
        return $this->redirectToRoute('dashboard');
    }
}
