<?php

namespace App\Controller\Admin\Crud;

use App\Entity\Cabinet;
use App\Security\CabinetVoter;
use App\Utils\Enum\USER_ROLES;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Orm\EntityRepository;
use Symfony\Bundle\SecurityBundle\Security;

class CabinetCrudController extends AbstractCrudController
{
    private $security;
    public function __construct(Security $security, EntityRepository $entityRepository)
    {
        $this->security = $security;
        $this->entityRepository = $entityRepository;
    }

    public static function getEntityFqcn(): string
    {
        return Cabinet::class;
    }

    public function configureActions(Actions $actions):Actions{
        return $actions
        ->setPermission(Action::NEW,CabinetVoter::CREATE)
        ->setPermission(Action::EDIT,CabinetVoter::EDIT)
        ->setPermission(Action::DELETE,CabinetVoter::DELETE)
        ->setPermission(Action::DETAIL,CabinetVoter::VIEW)
        ->setPermission(Action::INDEX,CabinetVoter::ACCESS)
        ;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Cabinet')
            ->setEntityLabelInPlural('Cabinets')

            ->setPageTitle('index', ' Liste des %entity_label_plural%')
            ->setPageTitle('new', 'Création d\'un %entity_label_singular%')
            ->setPageTitle('detail','Détail d\'un %entity_label_singular%')
            ->setPageTitle('edit','Modification d\'un %entity_label_singular%')

            ->setSearchFields(['id', 'title', 'description'])
            ->setDefaultSort(['id' => 'ASC'])

            ->renderSidebarMinimized()
            ->showEntityActionsInlined()
            ->setPaginatorPageSize(20)
            ->setPaginatorRangeSize(3);
    }


    public function configureFields(string $pageName): iterable
    {
        $date=new \DateTime();
        return [
            IdField::new('id')
                ->hideOnForm(),
            TextField::new('name'),
            TextField::new('address'),
            #DateField::new('creation_date')
            #    ->setValue($date),
        ];
    }

    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): \Doctrine\ORM\QueryBuilder
    {
        $queryBuilder = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);
        $user =$this->security->getUser();
        // if user defined sort is not set
        if (!$this->security->isGranted(USER_ROLES::SUPER_ADMIN->value)) {
            $queryBuilder
            ->andWhere('entity.id='.$user->getCabinet()->getId())
            ->addOrderBy('entity.name','ASC');
        }
        return $queryBuilder;
    }
}
