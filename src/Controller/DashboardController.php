<?php

namespace App\Controller;

use App\Entity\Breakdown;
use App\Entity\Cabinet;
use App\Entity\Machine;
use App\Entity\Maintenance;
use App\Entity\User;
use App\Security\BreakdownVoter;
use App\Security\CabinetVoter;
use App\Security\MachineVoter;
use App\Security\MaintenanceVoter;
use App\Security\UserVoter;
use App\Utils\Enum\USER_ROLES;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractDashboardController
{
    public function __construct(private Security $security){

    }

    #[Route('/admin', name: 'admin;index')]
    public function index(): Response
    {
        if($this->security->isGranted(USER_ROLES::TECH->value)){
            return $this->redirectToRoute("menuAdmin");
        }else if($this->security->isGranted(USER_ROLES::USER->value)){
            return $this->redirectToRoute("menu");
        }else {
            return $this->redirectToRoute("unidentify");
        }
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Gestions des pannes')
            ->renderContentMaximized();
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToRoute('Mon compte', 'fa-solid fa-users-gear', 'app_profile');
        yield MenuItem::linkToCrud('Cabinets', 'fas fa-solid fa-hospital', Cabinet::class)
            ->setPermission(CabinetVoter::ACCESS);
        yield MenuItem::linkToCrud('Utilisateurs', 'fa fa-user', User::class)
            ->setPermission(UserVoter::ACCESS);
        yield MenuItem::linkToCrud('Machines', 'fa-solid fa-laptop', Machine::class)
            ->setPermission(MachineVoter::ACCESS);
        yield MenuItem::linkToCrud('Pannes', 'fas fa-solid fa-wrench', Breakdown::class)
            ->setPermission(BreakdownVoter::ACCESS);
        yield MenuItem::linkToCrud('Maintenances', 'fa-solid fa-plug-circle-xmark', Maintenance::class)
            ->setPermission(MaintenanceVoter::ACCESS);
    }
}
