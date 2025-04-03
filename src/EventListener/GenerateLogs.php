<?php

namespace App\EventListener;

use App\Entity\Logs;
use App\Utils\UtilsLogs;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Event\AfterEntityPersistedEvent;
use EasyCorp\Bundle\EasyAdminBundle\Event\AfterEntityUpdatedEvent;
use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeEntityDeletedEvent;
use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeEntityUpdatedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class GenerateLogs implements EventSubscriberInterface
{
    private EntityManagerInterface $entityManager;
    private ?object $entitybeforeUpdate;
    private $tokenStorage;


    public function __construct(EntityManagerInterface $entityManager, TokenStorageInterface $tokenStorage)
    {
        $this->entityManager = $entityManager;
        $this->entitybeforeUpdate = null;
        $this->tokenStorage = $tokenStorage;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            AfterEntityPersistedEvent::class => ['onAfterEntityPersisted'],
            AfterEntityUpdatedEvent::class => ['onAfterEntityUpdate'],
            BeforeEntityUpdatedEvent::class => ['onBeforeEntityUpdate'],
            BeforeEntityDeletedEvent::class => ['onBeforeEntityDelete'],
        ];
    }

    private function generateMessage(String $nameClass, ?object $event): String {
        switch ($nameClass) {
            case 'Breakdown':
                $information = "[ID: ". $event->getId() . "]";
                $information .= "\n\t\t| date breakdown: ". $event->getDate()->format('Y-m-d H:i:s');
                $information .= "\n\t\t| description: ". $event->getDescription();
                $information .= "\n\t\t| comment: ". $event->getComment();
                $information .= "\n\t\t| status: ". $event->getStatus();
                $information .= "\n\t\t| creation date: ". $event->getCreationDate()->format('Y-m-d H:i:s');
                $information .= "\n\t\t[created by ". $event->getCreator() . "]";
                break;
            case 'Cabinet':
                $information = "[ID: ". $event->getId() . "]";
                $information .= "\n\t\t| name: ". $event->getName();
                $information .= "\n\t\t| address: ". $event->getAddress();
                $information .= "\n\t\t| creation date: ". $event->getCreationDate()->format('Y-m-d H:i:s');
                break;
            case 'Machine':
                $information = "[ID: ". $event->getId() . "]";
                $information .= "\n\t\t| serial number: ". $event->getSerialNumber();
                $information .= "\n\t\t| reference: ". $event->getReference();
                $information .= "\n\t\t| status: ". $event->getStatus();
                $information .= "\n\t\t| production date: ". $event->getProductionDate()->format('Y-m-d H:i:s');
                $information .= "\n\t\t| creation date: ". $event->getCreationDate()->format('Y-m-d H:i:s');
                break;
            case 'Maintenance':
                $information = "[ID: ". $event->getId() . "]";
                $information .= "\n\t\t| date maintenance: ". $event->getDate()->format('Y-m-d H:i:s');
                $information .= "\n\t\t| comment: ". $event->getComment();
                $information .= "\n\t\t| status: ". $event->getStatus();
                $information .= "\n\t\t| creation date: ". $event->getCreationDate()->format('Y-m-d H:i:s');
                $information .= "\n\t\t[created by ". $event->getCreator() . "]";
                break;
            case 'User':
                $information = "[ID: ". $event->getId() . "]";
                $information .= "\n\t\t| username: ". $event->getUsername();
                $information .= "\n\t\t| name: ". $event->getName();
                $information .= "\n\t\t| firstname: ". $event->getFirstName();
                $information .= "\n\t\t| email: ". $event->getEmail();
                $stringRole="";
                foreach($event->getRoles() as $role){
                    $stringRole.=$role." / ";
                }
                $information .= "\n\t\t| roles: ".$stringRole;
                $information .= "\n\t\t| creation date: ". $event->getCreationDate()->format('Y-m-d H:i:s');
                $information .= "\n\t\t| cabinet: ". $event->getCabinet();
                break;
            default:
                $information = "[[unknown entity]]";
                break;
        }
        return $information;
    }


    public function onAfterEntityPersisted(AfterEntityPersistedEvent $event): void
    {
//        $createur = $this->entityManager->getRepository(User::class)->findAll()[0]; // sera remplacé par le num de l'user actuel
        $createur = $this->tokenStorage->getToken()->getUser();

        UtilsLogs::addLog($this->entityManager,explode('\\', get_class($event->getEntityInstance()))[2], \App\Utils\Enum\LOGS_TYPE::ADD, "Create new entity[". $event->getEntityInstance()->getId()."]" , $createur);

    }
    public function onBeforeEntityUpdate(BeforeEntityUpdatedEvent $event): void
    {
        $this->entitybeforeUpdate = $event->getEntityInstance();
    }
    public function onAfterEntityUpdate(AfterEntityUpdatedEvent $event): void
    {
//        $createur = $this->entityManager->getRepository(User::class)->findAll()[0]; // sera remplacé par le num de l'user actuel
        $createur = $this->tokenStorage->getToken()->getUser();
        $nameClass = explode('\\', get_class($event->getEntityInstance()))[2];

        $message = "Update entity, \n\t\t- Older Informations " . $this->generateMessage($nameClass, $this->entitybeforeUpdate)
                    ."\n\n\t\t- New Informations ".$this->generateMessage($nameClass, $event->getEntityInstance());

        UtilsLogs::addLog($this->entityManager,$nameClass, \App\Utils\Enum\LOGS_TYPE::UPDATE, $message , $createur);

        $this->entitybeforeUpdate = null;
    }
    public function onBeforeEntityDelete(BeforeEntityDeletedEvent $event): void
    {
//        $createur = $this->entityManager->getRepository(User::class)->findAll()[0]; // sera remplacé par le num de l'user actuel
        $createur = $this->tokenStorage->getToken()->getUser();
        $nameClass = explode('\\', get_class($event->getEntityInstance()))[2];


        $message = "Delete entity" . $this->generateMessage($nameClass, $event->getEntityInstance());

        UtilsLogs::addLog($this->entityManager,$nameClass , \App\Utils\Enum\LOGS_TYPE::DELETE, $message , $createur);

    }
}