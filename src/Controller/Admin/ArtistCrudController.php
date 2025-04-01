<?php

namespace App\Controller\Admin;

use AlexandreFernandez\JsonTranslationBundle\Form\JsonTranslationType;
use App\Entity\Artist;
use App\Entity\Obra;
use App\Security\Voter\ArtistVoter;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
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
            /** this needs to be JsonTranslationType */
//            Field::new('bio', 'bio')
//                ->hideOnIndex(),
            TextareaField::new('socialMedia')
                ->setHelp("URLs, one per line")
                ->hideOnIndex(),
            IntegerField::new('obraCount', 'obraCount')
//                ->setFormTypeOption('disabled','disabled')
                ->onlyOnIndex(),
            IntegerField::new('birthYear', 'birthYear'),

            ChoiceField::new('studioVisitable', 'studioVisitable')->setChoices(
                array_combine(Artist::STUDIO_VISITABLE, Artist::STUDIO_VISITABLE),
                )->renderExpanded(),

            CollectionField::new('obras')
                ->setTemplatePath('admin/field/obras.html.twig')
                ->useEntryCrudForm()
                ->hideOnIndex(),

        ];
    }

    public function configureActions(Actions $actions): Actions
    {
//        $viewInvoice = Action::new('invoice', 'View invoice', 'fa fa-file-invoice')
//            ->linkToCrudAction('renderInvoice');

        return $actions
            ->setPermission(Action::EDIT, ArtistVoter::EDIT)
            ->setPermission(Action::DELETE, ArtistVoter::DELETE)
            ;
    }

}
