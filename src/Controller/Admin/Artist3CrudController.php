<?php

namespace App\Controller\Admin;

use App\Entity\Artist;
use App\Entity\Obra;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class Artist3CrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Artist::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('name'),
            TextEditorField::new('bio')
                ->hideOnIndex()
            ,
            IntegerField::new('obraCount')
                ->onlyOnIndex(),
            IntegerField::new('birthYear'),
            CollectionField::new('obras')
                ->setTemplatePath('admin/field/obras.html.twig')
                ->useEntryCrudForm()
                ->hideOnIndex(),
            CollectionField::new('obras')
                ->onlyOnIndex(),

        ];
    }
}
