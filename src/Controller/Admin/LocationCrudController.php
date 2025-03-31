<?php

namespace App\Controller\Admin;

use App\Entity\Location;
use App\Enum\LocationType;
use App\Security\Voter\LocationVoter;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class LocationCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Location::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('code'),
            TextField::new('name'),
            ChoiceField::new('type')
                ->setChoices(LocationType::choices())
                ->renderExpanded()
                ->renderAsBadges()
                ->setRequired(true),
            IntegerField::new('obraCount', 'Obras Count')
                ->formatValue(fn ($value, $entity) => $entity->getObras()->count())
                ->onlyOnIndex(),
            CollectionField::new('obras')
                ->setTemplatePath('admin/field/obras.html.twig')
                ->allowAdd(false)
                ->allowDelete(false)
                ->onlyOnDetail(),
            CollectionField::new('obras')
                ->onlyOnIndex(),
        ];
    }

    public function configureActions(Actions $actions): Actions
    {
//        $viewInvoice = Action::new('invoice', 'View invoice', 'fa fa-file-invoice')
//            ->linkToCrudAction('renderInvoice');

        return $actions
            // ...
//            ->add(Crud::PAGE_EDIT, $viewInvoice)
            // use the 'setPermission()' method to set the permission of actions
            // (the same permission is granted to the action on all pages)
//            ->setPermission('invoice', LocationVoter::EDIT)

            // you can set permissions for built-in actions in the same way
            ->setPermission(Action::EDIT, LocationVoter::EDIT)
            ->setPermission(Action::DELETE, LocationVoter::DELETE)
            ;
    }
}
