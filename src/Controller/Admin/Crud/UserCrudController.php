<?php

namespace App\Controller\Admin\Crud;

use App\Entity\User;
use App\Security\UserVoter;
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
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Orm\EntityRepository;
use Symfony\Bundle\SecurityBundle\Security;

class UserCrudController extends AbstractCrudController
{
    private $user;
    public function __construct(private Security $security, EntityRepository $entityRepository)
    {
        $this->entityRepository = $entityRepository;
        $this->user=$this->security->getUser();
    }
    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->setPermission(Action::NEW , UserVoter::CREATE)
            ->setPermission(Action::EDIT, UserVoter::EDIT)
            ->setPermission(Action::DELETE, UserVoter::DELETE)
            ->setPermission(Action::DETAIL, UserVoter::VIEW)
            ->setPermission(Action::INDEX, UserVoter::ACCESS)
        ;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return parent::configureCrud($crud)
            //Barre latérale minimaliste
            ->renderSidebarMinimized()
            ->showEntityActionsInlined()
            //Définition des titres des pages du CRUD
            ->setEntityLabelInSingular('Utilisateur')
            ->setEntityLabelInPlural('Utilisateurs')
            ->setPageTitle('index', ' Liste des %entity_label_plural%')
            ->setPageTitle('new', 'Création %entity_label_singular%')
            ->setPageTitle('detail', 'Détail %entity_label_singular%')
            ->setPageTitle('edit', 'Modification %entity_label_singular%')
            //->setSearchFields(['nom'])
            //Option d'affichage de la liste
            ->setDefaultSort(['name' => 'ASC'])
            //Nombre de page et d'onglet proposé en fin de page
            ->setPaginatorPageSize(20)
            ->setPaginatorRangeSize(3)
        ;
    }

    //Modification des champs du CRUD
    public function configureFields(string $pageName): iterable
    {
        $fields = [
            TextField::new('name', 'Nom'),
            TextField::new('firstName', 'Prénom'),
            EmailField::new('email', 'Email'),
            TextField::new('username', 'Nom utilisateur'),
            TextField::new('password', 'Mot de passe')->onlyOnForms()
            ->onlyWhenCreating(),
        ];


        if ($this->security->isGranted(USER_ROLES::SUPER_ADMIN->value)) {
            $fields[] = $fields[] = AssociationField::new('cabinet', 'Cabinet');
            $fields[] = ChoiceField::new('roles', 'Role')
                ->allowMultipleChoices()
                ->onlyOnForms()
                ->setChoices([
                    'administrateur' => USER_ROLES::ADMIN->value,
                    'technicien' => USER_ROLES::TECH->value,
                    "utilisateur" => USER_ROLES::USER->value,
                ]);
            $fields[] = ChoiceField::new('roles', 'Role')
                ->allowMultipleChoices()
                ->onlyOnIndex()
                ->setChoices([
                    'super administrateur' => USER_ROLES::SUPER_ADMIN->value,
                    'administrateur' => USER_ROLES::ADMIN->value,
                    'technicien' => USER_ROLES::TECH->value,
                    "utilisateur" => USER_ROLES::USER->value,
                ]);
        } else if ($this->security->isGranted(USER_ROLES::ADMIN->value)) {
            $fields[] = $fields[] = AssociationField::new('cabinet', 'Cabinet')
                ->setQueryBuilder(function ($queryBuilder) {
                    return $queryBuilder
                        ->andWhere('entity.id = :cabinetId')
                        ->setParameter('cabinetId', $this->user->getCabinet()); // Changer le 2 par la valeur correct
                });
            $fields[] = ChoiceField::new('roles', 'Role')
                ->allowMultipleChoices()
                ->setChoices([
                    'technicien' => "ROLE_TECH",
                    "utilisateur" => "ROLE_USER",
                ]);
        }

        return $fields;
    }

    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): \Doctrine\ORM\QueryBuilder
    {
        $queryBuilder = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);
        $user = $this->security->getUser();
        // if user defined sort is not set
        if (!$this->security->isGranted(USER_ROLES::SUPER_ADMIN->value)) {
            $queryBuilder
                ->andWhere('entity.cabinet=' . $user->getCabinet()->getId() . "OR entity.cabinet IS NULL")
                ->addOrderBy('entity.name', 'ASC');
        }

        return $queryBuilder;
    }
}
