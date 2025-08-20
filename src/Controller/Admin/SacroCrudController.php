<?php

namespace App\Controller\Admin;

use App\Entity\Sacro;
use App\Field\FlickrField;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Survos\TranslatableFieldBundle\EasyAdmin\Field\TranslationsField;
use function Symfony\Component\Translation\t;

class SacroCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Sacro::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id');
        // @todo: changing this field should re-fetch flickrInfo
//        yield TextField::new('flickrId');
        yield FlickrField::new('flickrInfo')->hideOnForm()
            ->setTemplatePath('ez/field/flickr.html.twig')
        ;
//        yield TextField::new('flickrUrl')->hideOnForm();
//            ->formatValue(static function ($value, Sacro $entity) {
//                return $entity->getFlickrId();
//            })

        // Replace TranslationsField with regular fields for now
        yield TextField::new('label');
        yield TextareaField::new('description');
        yield TextareaField::new('notes');

    }
}
