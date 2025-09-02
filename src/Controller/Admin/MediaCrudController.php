<?php

namespace App\Controller\Admin;

use App\Entity\Media;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AvatarField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Survos\CoreBundle\Controller\BaseCrudController;
use Survos\FlickrBundle\Services\FlickrService;

class MediaCrudController extends BaseCrudController
{
    public function __construct(
        private FlickrService $flickrService,
    )
    {
    }

    public static function getEntityFqcn(): string
    {
        return Media::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id'),
            TextField::new('flickrId')
                ->formatValue(function ($value, $entity) {
                    if (!$value) {
                        return null;
                    }
                    $url = $this->flickrService->flickrPageUrl($value);
                    return sprintf('<a href="%s" target="_blank">%s</a>', $url, htmlspecialchars($value . '/' . $entity->title));
                })
                ->renderAsHtml(),
            AvatarField::new('thumbnailUrl'),
            TextField::new('description'),
            TextField::new('originalUrl'),
            TextField::new('type'),
            ArrayField::new('resized')->onlyOnDetail(),
        ];
    }
}
