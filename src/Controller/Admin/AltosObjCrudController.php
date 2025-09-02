<?php

namespace App\Controller\Admin;

use App\Entity\AltosObj;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Survos\CoreBundle\Controller\BaseCrudController;

class AltosObjCrudController extends BaseCrudController
{
    public static function getEntityFqcn(): string
    {
        return AltosObj::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id');
        yield TextField::new('loc');
        yield TextField::new('title_es');
        yield TextField::new('title_lt');
        yield TextField::new('description');
        yield TextField::new('name');
        yield TextField::new('ubi');
    }
}
