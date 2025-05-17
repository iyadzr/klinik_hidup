<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DefaultController extends AbstractController
{
    #[Route('/{route}', name: 'app_default', requirements: ['route' => '^(?!api|_wdt|_profiler).+'], defaults: ['route' => null])]
    #[Route('/', name: 'app_default_index')]
    #[Route('/login', name: 'app_login_page')]
    #[Route('/register', name: 'app_register_page')]
    #[Route('/dashboard', name: 'app_dashboard_page')]
    #[Route('/patients', name: 'app_patients_page')]
    #[Route('/doctors', name: 'app_doctors_page')]
    #[Route('/appointments', name: 'app_appointments_page')]
    #[Route('/consultations/new', name: 'app_consultation_page')]
    public function index(): Response
    {
        return $this->render('base.html.twig');
    }
}
