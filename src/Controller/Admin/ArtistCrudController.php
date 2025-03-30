<?php

namespace App\Controller\Admin;

use App\Entity\Artist;
use App\Entity\Obra;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class ArtistCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Artist::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('name', 'name'),
            TextField::new('code', 'code'),
            TextEditorField::new('bio', 'bio')
                ->hideOnIndex(),
            TextareaField::new('socialMedia')
                ->setHelp("URLs, one per line")
                ->hideOnIndex(),
            IntegerField::new('obraCount', 'obraCount')
//                ->setFormTypeOption('disabled','disabled')
                ->onlyOnIndex(),
            IntegerField::new('birthYear', 'birthYear'),

            ChoiceField::new('studioVisitable', 'studioVisitable')->setChoices([
                'studio.open' => 'open',
                'studio.appointment' => 'appointment',
                'studio.closed' => 'closed',
            ])->renderExpanded(),

            CollectionField::new('obras')
                ->setTemplatePath('admin/field/obras.html.twig')
                ->useEntryCrudForm()
                ->hideOnIndex(),

        ];
    }
}
