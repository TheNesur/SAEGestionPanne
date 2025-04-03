<?php

namespace App\Controller\Admin\Crud;

use App\Entity\Machine;
use App\Security\MachineVoter;
use App\Utils\Enum\USER_ROLES;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Orm\EntityRepository;
use Symfony\Component\Validator\Constraints\LessThanOrEqual;
use \Symfony\Bundle\SecurityBundle\Security;


class MachineCrudController extends AbstractCrudController
{
    private $security;
    private $user;
    public function __construct(Security $security, EntityRepository $entityRepository)
    {
        $this->security = $security;
        $this->user=$this->security->getUser();
        $this->entityRepository = $entityRepository;
        $this->user = $this->security->getUser();
    }

    public static function getEntityFqcn(): string
    {
        return Machine::class;
    }

    public function configureActions(Actions $actions):Actions{
        return $actions
        ->setPermission(Action::NEW,MachineVoter::CREATE)
        ->setPermission(Action::EDIT,MachineVoter::EDIT)
        ->setPermission(Action::DELETE,MachineVoter::DELETE)
        ->setPermission(Action::DETAIL,MachineVoter::VIEW)
        ->setPermission(Action::INDEX,MachineVoter::ACCESS)
        ;
    }

    public function configureCrud(Crud $crud): Crud {
        return $crud
            ->setEntityLabelInSingular('Machine')
            ->setEntityLabelInPlural('Machines')
            ->setPageTitle('index', ' Liste des %entity_label_plural%')
            ->setPageTitle('new', 'Création d\'une %entity_label_singular%')
            ->setPageTitle('detail','Détail d\'une %entity_label_singular%')
            ->setPageTitle('edit','Modification d\'une %entity_label_singular%')

            ->renderSidebarMinimized()
            ->showEntityActionsInlined()
            ->setPaginatorPageSize(20)
            ->setPaginatorRangeSize(3);
    }

    public function configureFields(string $pageName): iterable
    {
        $fields = [
            TextField::new('serial_number'),
            TextField::new('reference'),
            DateField::new('production_date')->setFormTypeOptions([
                'constraints'=>[new LessThanOrEqual('today')]
            ]),

        ];
        if ($this->security->isGranted(USER_ROLES::SUPER_ADMIN->value)) {
            $fields[]=AssociationField::new('cabinet', 'Cabinet')
            ->setFormTypeOption('choice_label', function($cabinet){
            return $cabinet->getName();
            });
        } else if ($this->security->isGranted(USER_ROLES::ADMIN->value)) {
            $fields[]=AssociationField::new('cabinet', 'Cabinet')
                ->setFormTypeOption('choice_label', function($cabinet){
                return $cabinet->getName();
                })
                ->setQueryBuilder(function ($queryBuilder) {
                    return $queryBuilder
                        ->andWhere('entity.id = :cabinetId')
                        ->setParameter('cabinetId', $this->user->getCabinet()); // Changer le 2 par la valeur correct
                });
        }

        if ($pageName === Crud::PAGE_NEW) {
            $fields[] = Field::new('status')->setLabel('Statut')->hideOnForm()->setFormTypeOption('disabled', true)->setFormattedValue('en marche');
        } else {
            $fields[] = Field::new('status')->setLabel('Statut')->setFormTypeOption('disabled', true);
        }


        return $fields;
    }

  public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): \Doctrine\ORM\QueryBuilder
    {
        $queryBuilder = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);
        $user =$this->security->getUser();
        // if user defined sort is not set
        if (!$this->security->isGranted(USER_ROLES::SUPER_ADMIN->value)) {
            $queryBuilder
            ->andWhere('entity.cabinet='.$user->getCabinet()->getId())
            ->addOrderBy('entity.serial_number','ASC');
        }
        return $queryBuilder;
    }
}
