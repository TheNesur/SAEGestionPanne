<?php

namespace App\Controller\Admin\Crud;

use App\Entity\Logs;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class LogsCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Logs::class;
    }

    public function configureCrud(Crud $crud): Crud {
        return $crud
            ->setEntityLabelInSingular('Log')
            ->setEntityLabelInPlural('Logs')
            ->setPageTitle('index', ' Liste des %entity_label_plural%')
            ->setPageTitle('new', 'Création d\'une %entity_label_singular%')
            ->setPageTitle('detail','Détail d\'une %entity_label_singular%')
            ->setPageTitle('edit','Modification d\'une %entity_label_singular%')

            ->renderSidebarMinimized()
            ->showEntityActionsInlined()
            ->setDefaultSort(['serial_number'=>'ASC'])
            ->setPaginatorPageSize(20)
            ->setPaginatorRangeSize(3);
    }
    /*
    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id'),
            TextField::new('title'),
            TextEditorField::new('description'),
        ];
    }
    */
}
