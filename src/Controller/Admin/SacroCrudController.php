<?php

namespace App\Controller\Admin;

use App\Entity\Sacro;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Survos\TranslatableFieldBundle\EasyAdmin\Field\TranslationsField;

class SacroCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Sacro::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id');
        yield TextField::new('label')->hideOnForm();
        yield TextField::new('description')->hideOnForm();
        yield TextField::new('notes')->hideOnIndex()->hideOnForm();
        yield TextField::new('flickrId')->hideOnForm()
            ->formatValue(static function ($value, Sacro $entity) {
                return $entity->getFlickrId();
            })
        ;

        yield TranslationsField::new('translations')
            ->setFormTypeOption('locales', ['es', 'en'])
            ->addTranslatableField(TextareaField::new('label'))
            ->addTranslatableField(TextareaField::new('description'))
            ->addTranslatableField(TextareaField::new('notes'))
        ;

    }
}
