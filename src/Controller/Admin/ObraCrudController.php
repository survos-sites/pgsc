<?php

namespace App\Controller\Admin;

use App\Entity\Obra;
use App\Form\ObraImageFileType;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class ObraCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Obra::class;
    }

    public function configureFields(string $pageName): iterable
    {
        foreach ([
//            IdField::new('id'),
            TextField::new('code'),
            TextField::new('title'),
            TextEditorField::new('description'),

        ] as $field) {
            yield $field;
        }
        foreach (['width','height','depth'] as $fieldName) {
            yield IntegerField::new($fieldName);
        }
        yield TextField::new('materials');
        yield AssociationField::new('artist')
            ->autocomplete()
            ->setHelp('nombre del artista');
        yield AssociationField::new('location')
            ->setFormTypeOption('choice_label', 'name');
        yield CollectionField::new('obraImages')
            ->setEntryType(ObraImageFileType::class)
            ->onlyOnForms();
        yield CollectionField::new('obraImages')
            ->onlyOnDetail()
            ->setTemplatePath('admin/field/obra_images.html.twig');
        yield CollectionField::new('obraImages')
            ->onlyOnIndex()
            ->setTemplatePath('admin/field/obra_images_small.html.twig');
    }
}
