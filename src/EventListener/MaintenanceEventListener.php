<?php

namespace App\EventListener;

use App\Entity\Breakdown;
use App\Entity\Logs;
use App\Entity\Maintenance;
use App\Entity\User;
use App\Utils\Enum\USER_ROLES;
use App\Utils\UtilsLogs;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeEntityPersistedEvent;
use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeEntityUpdatedEvent;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use function PHPUnit\Framework\throwException;

class MaintenanceEventListener implements EventSubscriberInterface
{
    private EntityManagerInterface $entityManager;
    private MailerInterface $mailer;

    public function __construct(EntityManagerInterface $entityManager, MailerInterface $mailer, private Security $security)
    {
        $this->entityManager = $entityManager;
        $this->mailer = $mailer;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            BeforeEntityPersistedEvent::class => ['onBeforeEntityPersisted'],
            BeforeEntityUpdatedEvent::class => ['onBeforeEntityUpdated'],
        ];
    }

    public function onBeforeEntityPersisted(BeforeEntityPersistedEvent $event): void
    {
        $entity = $event->getEntityInstance();
        if ($entity instanceof Maintenance) {
            $tabBreakdowns = $entity->getBreakdowns();
            foreach($tabBreakdowns as $breakdown){
                $breakdown->setMaintenance($entity);
            }

            $createur = $this->entityManager->getRepository(User::class)->findAll()[0]; // sera remplacé par le num de l'user actuel
            // récupérer les utilisateur avec comme role TECH

            $entity->setCreator($createur);            
        if ($this->security->isGranted(USER_ROLES::ADMIN->value) && $entity->getTechnician() == null) {
            $entity->setTechnician($this->security->getUser());
        }
        }

    }
    public function onBeforeEntityUpdated(BeforeEntityUpdatedEvent $event): void
    {
        $entity = $event->getEntityInstance();
        if ($entity instanceof Maintenance) {
            try {
                $this->sendMail($entity);

            } catch (\Exception $e){
                UtilsLogs::addLog(
                    $this->entityManager,
                    "Register send email",
                    \App\Utils\Enum\LOGS_TYPE::ERROR,
                    $e->getMessage(),
                    $this->security->getUser()
                );
            }
        
            $tabBreakdownInit = $this->entityManager->getRepository(Breakdown::class)->getBreakdownsByMaintenance($entity->getId());
            $tabBreakdowns = $entity->getBreakdowns();
            //Permet de prendre en compte si des pannes ont été retirés ou ajouté à la maintenance
            $tabBreakdownCompare=[];
            foreach($tabBreakdowns as $breakdown){
                if(!in_array($breakdown,$tabBreakdownInit)){
                    $breakdown->setMaintenance($entity);
                }else{
                    $tabBreakdownCompare[]=$breakdown->getId();
                }
            }
            foreach($tabBreakdownInit as $idBreakdownInit){
                if(!in_array($idBreakdownInit,$tabBreakdownCompare)){
                    $breakdown=$this->entityManager->getRepository(Breakdown::class)->find($idBreakdownInit);
                    $breakdown->setMaintenance(null);
                }
            }
            foreach($tabBreakdowns as $breakdown){
                $breakdown->setMaintenance($entity);
            }
            //Permet de repasser le statut de la machine en "en marche" si maintenance réparé
            if($entity->getStatus()=="repare"){
                foreach($tabBreakdowns as $breakdown){
                    $breakdown->setStatus("repare");
                    $breakdown->getMachine()->setStatus("en marche");
                }
            }
        }
    }


    private function sendMail(Maintenance $entity): void
    {

        $email = (new Email())
            ->from('depanne-radiologie@gmail.com')
            ->to($entity->getCreator()->getEmail())
            //->cc('cc@example.com')
            //->bcc('bcc@example.com')
            //->replyTo('fabien@example.com')
            //->priority(Email::PRIORITY_HIGH)
            ->subject('Ticket '.$entity->getStatus().' #'.$entity->getId().' -- --') // changer par l'id de la panne
            // Entre les -- -- mettre le problème de la panne
            ->text(
                '=-=-=-= Pour répondre a ce courriel, contacter le technicien en charge =-=-=-=
'
            )
            ->html(
                '<p>Date de résolution : '.$entity->getDate()->format("'d-m-Y'").'</p>'
            );

        try {
            $this->mailer->send($email);
        } catch (TransportExceptionInterface $e) {
            throwException($e);
        }
    }

}
