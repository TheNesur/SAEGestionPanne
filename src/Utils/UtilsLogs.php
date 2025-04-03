<?php

namespace App\Utils;

use App\Entity\Logs;
use App\Entity\User;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;

class UtilsLogs
{
    public static function addLog(EntityManagerInterface $entityManager, String $entity, \App\Utils\Enum\LOGS_TYPE $typeLogs, String $message, User $author = null): void
    {
//        if (!($typeLogs instanceof \LOGS_TYPE)) {
//            throw new \InvalidArgumentException("Invalid type");
//        }

        $logs = new Logs();
        $logs->setType($typeLogs->value);
        date_default_timezone_set('Europe/Paris');
        $logs->setDate(new DateTime('now'));
        $logs->setEntity($entity);
        $logs->setOperation($message);
        $logs->setAuthor($author);
        $entityManager->persist($logs);
        $entityManager->flush();
    }
}