<?php

namespace App\Controller\Admin;

use App\Entity\Artist;
use App\Security\Voter\ArtistVoter;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Survos\TranslatableFieldBundle\EasyAdmin\Field\TranslationsField;

class ArtistCrudController extends AbstractCrudController
{
    public function __construct(protected AdminUrlGenerator $adminUrlGenerator)
    {
    }

    public static function getEntityFqcn(): string
    {
        return Artist::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('name')
            ->formatValue(function ($value, $entity) {
                return '<a href="' . $this->adminUrlGenerator
                        ->setController(self::class)
                        ->setAction('detail')
                        ->setEntityId($entity->getId())
                        ->generateUrl() . '">' . $value . '</a>';
            })->onlyOnIndex();
        yield ChoiceField::new('gender', 'gender')->setChoices(
            array_combine(Artist::GENDERS, Artist::GENDERS)
        )->setFormTypeOption('expanded', true);
        ;

        yield TextareaField::new('social')->hideOnIndex()
            ->setNumOfRows(2)
            ->setHelp("e.g. facebook, instagram, twitter.  One URL per line");
        yield TextField::new('name')->hideOnIndex();
        yield TextField::new('bio')->hideOnForm();
        yield IntegerField::new('birthYear');
        yield TranslationsField::new('translations')
            ->addTranslatableField(
                TextareaField::new('bio') /// ->setRequired(true)->setColumns(6)
            );
        yield TextField::new('code', 'code');

        /* this needs to be JsonTranslationType */
            //            Field::new('bio', 'bio')
            //                ->hideOnIndex(),
        return [
            TextareaField::new('socialMedia')
                ->setHelp('URLs, one per line')
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
            ->remove(Crud::PAGE_INDEX, Action::DETAIL)
            ->update(Crud::PAGE_INDEX, Action::EDIT, function (Action $action) {
                return $action->setLabel(false)->setIcon('fa:edit');
            })
        ;
    }
}
