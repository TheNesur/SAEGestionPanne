<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ProfileController extends AbstractController
{
    #[Route('/profile', name: 'app_profile')]
    public function index(): Response
    {
        $user = $this->getUser();
        if($user!=null){
            $profile = $user->getProfile();
        }else{
            $profile = "Vous n'êtes pas connecté.";
        }
        return $this->render('profile/index.html.twig', [
            'profil' => $profile,
        ]);
    }
}
