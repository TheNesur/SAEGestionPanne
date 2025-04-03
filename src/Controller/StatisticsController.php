<?php

namespace App\Controller;

use App\Repository\BreakdownRepository;
use App\Repository\MachineRepository;
use App\Utils\Enum\USER_ROLES;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class StatisticsController extends AbstractController
{
    #[Route('/statistics', name: 'app_statistics')]
    #[IsGranted(new Expression('is_granted("'.USER_ROLES::SUPER_ADMIN->value.'")'))]
    public function index(BreakdownRepository $breakdownRepository, MachineRepository $machineRepository): Response
    {
        $totalBreakdowns = $breakdownRepository->getTotalBreakdowns();
        $urgentBreakdowns = $breakdownRepository->getUrgentBreakdowns();
        $nonUrgentBreakdowns = $breakdownRepository->getNonUrgentBreakdowns();
        $todayBreakdowns = $breakdownRepository->getTodayBreakdowns();
        $brokenMachineByCabinet = $machineRepository->getPercentageOfBrokenMachinesByCabinet();
        $unscheduledUrgentBreakdownsCount = $breakdownRepository->getUnscheduledUrgentBreakdownsCount();
        $unscheduledBreakdownsCount = $breakdownRepository->getUnscheduledBreakdownsCount();
        $urgentBreakdownsByCabinet = $breakdownRepository->getUrgentBreakdownsByCabinet();
        $nonUrgentBreakdownsByCabinet = $breakdownRepository->getNonUrgentBreakdownsByCabinet();
            return $this->render('statistics/index.html.twig', [
                'totalBreakdowns' => $totalBreakdowns,
                'urgentBreakdowns' => $urgentBreakdowns,
                'nonUrgentBreakdowns' => $nonUrgentBreakdowns,
                'todayBreakdowns' => $todayBreakdowns,
                'brokenMachineByCabinet' => $brokenMachineByCabinet,
                'unscheduledUrgentBreakdownsCount' => $unscheduledUrgentBreakdownsCount,
                'unscheduledBreakdownsCount' => $unscheduledBreakdownsCount,
                'urgentBreakdownsByCabinet' => $urgentBreakdownsByCabinet,
                'nonUrgentBreakdownsByCabinet' => $nonUrgentBreakdownsByCabinet,

        ]);
    }
}
