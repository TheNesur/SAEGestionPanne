<?php

namespace App\Controller\Admin\Crud;

use App\Entity\Breakdown;
use App\Entity\Maintenance;
use App\Entity\User;
use App\Security\MaintenanceVoter;
use App\Utils\Enum\USER_ROLES;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Orm\EntityRepository;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class MaintenanceCrudController extends AbstractCrudController
{
    private $logger;
    private $eventDispatcher;
    private $security;
    private EntityManagerInterface $entityManager;
    private $user;
    private $requestStack;


    public function __construct(LoggerInterface $logger, EntityManagerInterface $entityManager, EventDispatcherInterface $eventDispatcher, Security $security, EntityRepository $entityRepository, RequestStack $requestStack)
    {
        $this->logger = $logger;
        $this->eventDispatcher = $eventDispatcher;
        $this->entityManager = $entityManager;
        $this->security = $security;
        $this->entityRepository = $entityRepository;
        $this->user = $this->security->getUser();
        $this->requestStack = $requestStack;
    }


    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->setPermission(Action::NEW , MaintenanceVoter::CREATE)
            ->setPermission(Action::EDIT, MaintenanceVoter::EDIT)
            ->setPermission(Action::DELETE, MaintenanceVoter::DELETE)
            ->setPermission(Action::DETAIL, MaintenanceVoter::VIEW)
            ->setPermission(Action::INDEX, MaintenanceVoter::ACCESS)
        ;
    }

    public static function getEntityFqcn(): string
    {
        return Maintenance::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Maintenance')
            ->setEntityLabelInPlural('Maintenances')
            ->setPageTitle('index', ' Liste des %entity_label_plural%')
            ->setPageTitle('new', 'Création d\'une %entity_label_singular%')
            ->setPageTitle('detail', 'Détail d\'une %entity_label_singular%')
            ->setPageTitle('edit', 'Modification d\'une %entity_label_singular%')

            ->renderSidebarMinimized()
            ->showEntityActionsInlined()
            //            ->setDefaultSort(['date' => 'ABS(DATEDIFF(date, CURRENT_DATE()))']) // A refaire
            ->setPaginatorPageSize(20)
            ->setPaginatorRangeSize(3);
    }


    public function configureFields(string $pageName): iterable
    {
        $breakdownId = $this->requestStack->getCurrentRequest()->query->get('breakdownId');
        // Récupréer tout les user qui ont le rôle Tech et admin
        $listUser = $this->entityManager->getRepository(User::class)->findAll();
        $listTechnician = array();
        foreach ($listUser as $user) {
            if (in_array(USER_ROLES::TECH->value, $user->getRoles())) {
                $listTechnician[$user->getName() . " " . $user->getFirstName()] = $user;
            }
        }
        //        $listTechnician = $this->entityManager->getRepository(User::class)->findBy(['roles' => 'ROLE_TECHNICIAN']);

        //        $maintenanceRespositoy = $this->entityManager->getRepository(Maintenance::class);
//        $userid = $this->security->getToken()->getUser();
//        $userEntity = $this->entityManager->getRepository(User::class)->findBy(['id' => $userid]);

        $fields = [
            //            IdField::new('id')
//                ->hideOnForm(),
            DateField::new('date', label: 'Date'), // changer date europe
            ChoiceField::new('status', label: 'Statut')
                //                ->hideOnForm()
                ->setChoices([
                    'En attente' => 'en attente',
                    'Réparé' => 'repare',
                ])
                ->onlyWhenUpdating()
            ,
            TextField::new('status', label: 'Statut')
                ->hideOnForm()
            ,
        ];
        if ($breakdownId) {
            $breakdown = $this->entityManager->getRepository(Breakdown::class)->find($breakdownId);
            if ($breakdown) {
                if ($this->security->isGranted(USER_ROLES::SUPER_ADMIN->value)) {
                    $fields[] = AssociationField::new('breakdowns', 'Breakdown')
                        ->setQueryBuilder(function ($queryBuilder) {
                            return $queryBuilder
                                ->andWhere('entity.maintenance IS null OR entity.maintenance = :maintenance')
                                ->setParameter('maintenance', $this->getContext()->getEntity()->getInstance()->getId());
                    })
                    ->setFormTypeOption('data', new ArrayCollection([$breakdown]))
                    ->hideOnIndex();
                }elseif ($this->security->isGranted(USER_ROLES::USER->value)) {
                    $fields[] = AssociationField::new('breakdowns', 'Panne')
                        ->setQueryBuilder(function ($queryBuilder) {
                            return $queryBuilder
                                ->join('entity.machine', 'm')
                                ->join('m.cabinet', 'c')
                                ->andWhere('entity.maintenance IS null OR entity.maintenance = :maintenance')
                                ->andWhere('c.id = :cabinetId')
                                ->setParameter('cabinetId', $this->security->getUser()->getCabinet())
                                ->setParameter('maintenance', $this->getContext()->getEntity()->getInstance()->getId());
                        })
                        ->setFormTypeOption('data', new ArrayCollection([$breakdown]))
                        ->hideOnIndex();
                }
            }
        } else {
            if ($this->security->isGranted(USER_ROLES::SUPER_ADMIN->value)) {
                $fields[] = AssociationField::new('breakdowns', 'Panne')
                    ->setQueryBuilder(function ($queryBuilder) {
                        return $queryBuilder
                            ->andWhere('entity.maintenance IS null OR entity.maintenance = :maintenance')
                            ->setParameter('maintenance', $this->getContext()->getEntity()->getInstance()->getId());
                    });
            } elseif ($this->security->isGranted(USER_ROLES::USER->value)) {
                $fields[] = AssociationField::new('breakdowns', 'Panne')
                    ->setQueryBuilder(function ($queryBuilder) {
                        return $queryBuilder
                            ->join('entity.machine', 'm')
                            ->join('m.cabinet', 'c')
                            ->andWhere('entity.maintenance IS null OR entity.maintenance = :maintenance')
                            ->andWhere('c.id = :cabinetId')
                            ->setParameter('cabinetId', $this->security->getUser()->getCabinet())
                            ->setParameter('maintenance', $this->getContext()->getEntity()->getInstance()->getId());
                    });
            }
        }

        if ($this->security->isGranted(USER_ROLES::ADMIN->value)) {
            $fields[] = TextField::new('creator')
                ->setLabel("Créer par ")
                ->setDisabled(true)
                ->hideOnIndex()
                ->hideWhenCreating();


            $fields[] = ChoiceField::new('technician', 'Technicien') // Cache si c'est un UTILISATEUR
                ->setLabel('Technicien')
                ->setChoices($listTechnician)
                ->hideOnIndex();

        } else if ($this->security->isGranted(USER_ROLES::TECH->value)) {
            $tech =$this->security->getUser();
            $techEnCours[$tech->getName() . " " . $tech->getFirstName()] = $tech;
            $fields[] = ChoiceField::new('technician', 'Technicien') // Cache si c'est un UTILISATEUR
                ->setLabel('Technicien')
                ->setChoices($techEnCours)
                ->hideOnIndex();
        }

        if($this->security->isGranted(USER_ROLES::TECH->value)){
            $fields[]=TextareaField::new('comment','Commentaire')
                ->onlyWhenUpdating();
        }

        return $fields;
    }
    
    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): \Doctrine\ORM\QueryBuilder
    {
        $queryBuilder = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);
        $user =$this->security->getUser();
        if (!$this->security->isGranted(USER_ROLES::SUPER_ADMIN->value)) {
            $queryBuilder
            ->innerJoin('entity.breakdowns','b')
            ->innerjoin('b.machine','m')
            ->andWhere('m.cabinet = '.$user->getCabinet()->getId());
        }
        return $queryBuilder;
    }

    //    Modifier pour l'adapter a eays admin
//    https://symfony.com/bundles/EasyAdminBundle/current/events.html

}
