<?php
namespace App\Controller;

use App\Utils\Enum\USER_ROLES;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class MenuAdminController extends AbstractController
{
    public function __construct(private Security $security){

    }

    #[Route('/menuAdmin',name:'menuAdmin')]
    public function menuUtilisateur():Response{
        if($this->security->isGranted(USER_ROLES::TECH->value)){
            return $this->render('menuAdmin/menuAdmin.html.twig');
        }else if($this->security->isGranted(USER_ROLES::USER->value)){
            return $this->redirectToRoute("menu");
        }else {
            return $this->redirectToRoute("app_login");
        }
    }
} 
