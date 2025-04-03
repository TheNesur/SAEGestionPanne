<?php

namespace App\Controller;

use App\Entity\Logs;
use App\Utils\Enum\USER_ROLES;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class LogsController extends AbstractController
{

    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    #[Route(path: '/logs', name: 'app_logs')]
    #[IsGranted(new Expression('is_granted("'.USER_ROLES::SUPER_ADMIN->value.'")'))]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        $listLogs = $this->entityManager->getRepository(Logs::class)->findAll();

        return $this->render('logs/logs.html.twig', [
            'logs_message' => $listLogs,
        ]);
    }


    #[Route(path: '/logs/generate', name: 'app_generate_logs')]
    #[IsGranted(new Expression('is_granted("'.USER_ROLES::SUPER_ADMIN->value.'")'))]
    public function generateLogs(AuthenticationUtils $authenticationUtils): Response
    {
        $listLogs = $this->entityManager->getRepository(Logs::class)->findAll();
        date_default_timezone_set('Europe/Paris');
        $dir = "logsGenerate";
        if (!file_exists($dir) || !is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $file=fopen($dir.'/logs['.date('Y-m-d h-i-s', time()).'].txt','w') or die("Unable to open file!");

        /*
         * https://stackoverflow.com/questions/12094080/download-files-from-server-php
         * https://www.w3docs.com/snippets/php/automatic-download-file.html
         */

        foreach ($listLogs as $log) {
            fwrite($file,$log."\n");
        }
        fclose($file);


        return $this->render('logs/logs.html.twig', [
            'logs_message' => $listLogs,
        ]);
    }
}