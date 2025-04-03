<?php

namespace App\Controller\Admin\Crud;

use App\Entity\Breakdown;
use App\Security\BreakdownVoter;
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
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Validator\Constraints\LessThanOrEqual;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;

class BreakdownCrudController extends AbstractCrudController
{
    private $adminUrlGenerator;
    private $requestStack;
    public function __construct(private Security $security, AdminUrlGenerator $adminUrlGenerator, RequestStack $requestStack)
    {
        $this->security = $security;
        $this->adminUrlGenerator = $adminUrlGenerator;
        $this->requestStack = $requestStack;
    }


    public function configureActions(Actions $actions): Actions
    {
        $addMaintenance = Action::new('addMaintenance', 'Ajouter Maintenance')
            ->linkToCrudAction('redirectToAddMaintenance')
            ->displayIf(static function (Breakdown $breakdown) {
                return !is_null($breakdown->getId());
            });

        return $actions
            ->add(Crud::PAGE_EDIT, $addMaintenance)
            ->setPermission(Action::NEW , BreakdownVoter::CREATE)
            ->setPermission(Action::EDIT, BreakdownVoter::EDIT)
            ->setPermission(Action::DELETE, BreakdownVoter::DELETE)
            ->setPermission(Action::DETAIL, BreakdownVoter::VIEW)
            ->setPermission(Action::INDEX, BreakdownVoter::ACCESS)
        ;
    }

    public function redirectToAddMaintenance(AdminContext $context): RedirectResponse
    {
        $breakdownId = $context->getEntity()->getInstance()->getId();
        $request = $this->requestStack->getCurrentRequest();
        $request->getSession()->set('breakdownId', $breakdownId);

        $url = $this->adminUrlGenerator
            ->setController(MaintenanceCrudController::class)
            ->setAction('new')
            ->unset('entityId')
            ->generateUrl();

        return $this->redirect($url);
    }

    public static function getEntityFqcn(): string
    {
        return Breakdown::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Panne')
            ->setEntityLabelInPlural('Pannes')
            ->setPageTitle('index', ' Liste des %entity_label_plural%')
            ->setPageTitle('new', 'Création d\'une %entity_label_singular%')
            ->setPageTitle('detail', 'Détail d\'une %entity_label_singular%')
            ->setPageTitle('edit', 'Modification d\'une %entity_label_singular%')

            ->renderSidebarMinimized()
            ->showEntityActionsInlined()
            ->setDefaultSort(['id' => 'ASC'])
            ->setPaginatorPageSize(20)
            ->setPaginatorRangeSize(3);
    }
    public function configureFields(string $pageName): iterable
    {
        $fields = [
            DateField::new('creation_date', label: 'Date de création')
                ->hideWhenCreating()
                ->setDisabled(),
            AssociationField::new('creator', label: 'Createur')
                ->hideWhenCreating()
                ->setDisabled(),
            DateField::new('date', label: 'Date de la panne')
                ->setFormTypeOptions([
                    'constraints' => [new LessThanOrEqual('today')]
                ]),
            TextField::new('description', label: 'Description'),
            TextareaField::new('comment', label: 'Commentaire'),
        ];

        if ($pageName === Crud::PAGE_EDIT) {
            $fields[] = AssociationField::new('maintenance', 'Maintenance')
                ->setDisabled()
                ->setQueryBuilder(function ($queryBuilder) {
                    $breakdownId = $this->getContext()->getEntity()->getPrimaryKeyValue();
                    return $queryBuilder
                        ->join('entity.breakdowns', 'b')
                        ->andWhere('b.id = :breakdownId')
                        ->setParameter('breakdownId', $breakdownId);
                });
            $fields[] = AssociationField::new('machine', label: 'Machine')
                            ->setDisabled();
        } else {
            $fields[] = AssociationField::new('maintenance', 'Maintenance')
                ->hideWhenCreating()
                ->setDisabled();
                if ($this->security->isGranted(USER_ROLES::SUPER_ADMIN->value)) {

                    $fields[] = $fields[] = AssociationField::new('machine', label: 'Machine')
                        ->setQueryBuilder(function ($queryBuilder) {
                            return $queryBuilder
                                ->andWhere("entity.status = 'en marche'");
                        });
                } else if ($this->security->isGranted(USER_ROLES::USER->value)) {
                    $fields[] = $fields[] = AssociationField::new('machine', label: 'Machine')
                        ->setQueryBuilder(function ($queryBuilder) {
                            return $queryBuilder
                                ->andWhere("entity.status = 'en marche'")
                                ->andWhere('entity.cabinet = :cabinetId')
                                ->setParameter('cabinetId', $this->security->getUser()->getCabinet());
        
                        });
                }
        }
        


        if ($this->security->isGranted(USER_ROLES::ADMIN->value)) {
            $fields[] = ChoiceField::new('status')
                ->setChoices([
                    'Urgent' => 'urgent',
                    'Non urgent' => 'non urgent',
                ]);
        }

        return $fields;
    }

    public function createEntity(string $entityFqcn): Breakdown
    {
        $breakdown = new Breakdown();
        $breakdown->setCreator($this->security->getUser());

        return $breakdown;
    }

    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): \Doctrine\ORM\QueryBuilder
    {
        $queryBuilder = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);
        $user = $this->security->getUser();
        if (!$this->security->isGranted(USER_ROLES::SUPER_ADMIN->value)) {
            $queryBuilder
                ->join('entity.machine', 'm')
                ->andWhere('m.cabinet=' . $user->getCabinet()->getId())
                ->addOrderBy('entity.date', 'DESC');
        }
        return $queryBuilder;
    }
}
