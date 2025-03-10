<?php

namespace App\Controller\Admin;

use App\Entity\Obra;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
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
        };
        yield AssociationField::new('artist')
            ->autocomplete()
            ->setHelp('nombre del artista');;
        yield AssociationField::new('location')
            ->setFormTypeOption('choice_label', 'name')
        ;
        yield ImageField::new('image')
            ->setBasePath('uploads/')
            ->setUploadDir('public/uploads');

    }
}
