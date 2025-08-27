<?php

namespace App\Controller\Admin;

use App\Entity\Location;
use App\Enum\LocationType;
use App\Security\Voter\LocationVoter;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;

class LocationCrudController extends AbstractCrudController
{
    public function __construct(protected AdminUrlGenerator $adminUrlGenerator)
    {
    }

    public static function getEntityFqcn(): string
    {
        return Location::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('code'),
            NumberField::new('lat'),
            TextField::new('name')
                ->formatValue(function ($value, $entity) {
                    return '<a href="' . $this->adminUrlGenerator
                        ->setController(self::class)
                        ->setAction('detail')
                        ->setEntityId($entity->id)
                        ->generateUrl() . '">' . $value . '</a>';
                })->onlyOnIndex(),
            TextField::new('name')->hideOnIndex(),
            ChoiceField::new('type')
                ->setChoices(LocationType::choices())
                ->renderExpanded()
                ->renderAsBadges()
                ->setRequired(true),
            IntegerField::new('obraCount', '#obj')
                ->formatValue(fn ($value, $entity) => $entity->obraCount)
                ->onlyOnIndex(),
            CollectionField::new('obras')
                ->setTemplatePath('admin/field/obras.html.twig')
                ->allowAdd(false)
                ->allowDelete(false)
                ->onlyOnDetail(),
        ];
    }

    public function configureActions(Actions $actions): Actions
    {
        $rowPrintAction = Action::new('print', false, 'fa:print')
            ->linkToUrl(function ($entity) {
                return $this->generateUrl('location_print', ['locationId' => $entity->id]);
            });

        return $actions
            ->add(Crud::PAGE_INDEX, $rowPrintAction)
            // you can set permissions for built-in actions in the same way
            ->setPermission(Action::EDIT, LocationVoter::EDIT)
            ->setPermission(Action::DELETE, LocationVoter::DELETE)
            ->update(Crud::PAGE_INDEX, Action::EDIT, function (Action $action) {
                return $action->setLabel(false)->setIcon('fa:edit');
            })
        ;
    }
}
