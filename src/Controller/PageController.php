<?php
namespace App\Controller;

use App\Utils\Enum\USER_ROLES;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class PageController extends AbstractController
{
    public function __construct(private Security $security){

    }

    #[Route('/menu',name:'menu')]
    public function menuUtilisateur():Response{
        if($this->security->isGranted(USER_ROLES::TECH->value)){
            return $this->redirectToRoute("menuAdmin");
        }else if($this->security->isGranted(USER_ROLES::USER->value)){
            return $this->render('menu/menuUtilisateur.html.twig');
        }else {
            return $this->redirectToRoute("app_login");
        }
    }
} 
