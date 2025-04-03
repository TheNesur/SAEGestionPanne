<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ErrorController extends AbstractController{


    #[Route('/error', name: 'app_error')]
    public function show(): Response 
    {
        return $this->render('error/error.html.twig');
    }

    #[Route('/unidentify', name: 'unidentify')]
    public function enAttente(): Response 
    {
        return $this->render('error/attente.html.twig');
    }
}