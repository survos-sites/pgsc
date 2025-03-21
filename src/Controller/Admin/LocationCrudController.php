<?php

namespace App\Controller\Admin;

use App\Entity\Location;
use App\Enum\LocationType;
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
}
