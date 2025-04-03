<?php

namespace App\Controller\Admin\Field;

use App\Controller\Admin\Type\BreakdownTypeController;
use EasyCorp\Bundle\EasyAdminBundle\Config\Asset;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use EasyCorp\Bundle\EasyAdminBundle\Field\FieldTrait;

class BreakdownFieldCustom implements FieldInterface
{
    use FieldTrait;
    public static function new(string $propertyName, ?string $label = null): self
    {
       return (new self())
           ->setProperty($propertyName)
           ->setLabel($label)
           ->setTemplateName('crud/field/collection')

//           ->setTemplatePath("admin/fields/breakdown/custom.html.twig")
           ->setFormType(BreakdownTypeController::class)
           ->addCssClass('field-collection')
           ->addJsFiles(Asset::fromEasyAdminAssetPackage('field-collection.js')->onlyOnForms())
           ->setDefaultColumns('col-md-8 col-xxl-7');

    }
}