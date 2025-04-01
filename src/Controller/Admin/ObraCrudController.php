<?php

namespace App\Controller\Admin;

use App\Entity\Obra;
use App\Form\ObraImageFileType;
use App\Security\Voter\ObjVoter;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;

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
            TextField::new('code')
                     ->setPermission('ROLE_ADMIN'),
            TextField::new('title'),
            TextEditorField::new('description'),

        ] as $field) {
            yield $field;
        }
        foreach (['width','height','depth'] as $fieldName) {
            yield IntegerField::new($fieldName)->setColumns(3)->onlyOnForms();
        }
        yield TextField::new('dimensions', 'Dimensions')
            ->setHelp('width x height x depth')
            ->setLabel('Dimensions (in cm)')
            ->hideOnForm();

        yield TextField::new('materials');
        yield IntegerField::new('price', 'price');

        yield AssociationField::new('artist', 'artist')
            ->setHelp('nombre del artista')
            ->setColumns(5);
        // allow admins to edit
        yield AssociationField::new('location')
//            ->onlyOnForms()
            ->setPermission('ROLE_ADMIN')
            ->setFormTypeOption('choice_label', 'name')
            ->setColumns(5);
        // not editable except by admin
        yield AssociationField::new('location')
            ->setDisabled(true)
            ->setFormTypeOption('choice_label', 'name')
            ;
//        yield CollectionField::new('obraImages')
//            ->setEntryType(ObraImageFileType::class)
//            ->onlyOnForms();
//        yield CollectionField::new('obraImages')
//            ->onlyOnDetail()
//            ->setTemplatePath('admin/field/obra_images.html.twig');
//        yield CollectionField::new('obraImages')
//            ->onlyOnIndex()
//            ->setTemplatePath('admin/field/obra_images_small.html.twig');
    }


    public function configureFilters(Filters $filters): Filters
    {
        return parent::configureFilters($filters)
            ->add('location')
            ->add('artist');
    }


    public function configureActions(Actions $actions): Actions
    {
//        $viewInvoice = Action::new('invoice', 'View invoice', 'fa fa-file-invoice')
//            ->linkToCrudAction('renderInvoice');

        $printAction = Action::new('print')
            ->linkToUrl($this->generateUrl('obj_show', ['obraId' => 1]))
            ->setTemplatePath('admin/print.html.twig')
            ;

        // @todo: add print action
        return $actions
//            ->add('print', $printAction)
            ->setPermission(Action::EDIT, ObjVoter::EDIT)
            ->setPermission(Action::DELETE, ObjVoter::DELETE)
            ;
    }
}
