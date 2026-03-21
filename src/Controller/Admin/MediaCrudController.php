<?php

namespace App\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Field\AvatarField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Survos\EzBundle\Controller\BaseCrudController;
use Survos\FlickrBundle\Services\FlickrService;
use Survos\MediaBundle\Entity\Photo;

class MediaCrudController extends BaseCrudController
{
    public function __construct(
        private FlickrService $flickrService,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return Photo::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id'),
            TextField::new('rawData[flickr_id]', 'Flickr ID')
                ->formatValue(function ($value, $entity) {
                    $flickrId = $entity->rawData['flickr_id'] ?? null;
                    if (!$flickrId) {
                        return null;
                    }
                    $url = $this->flickrService->flickrPageUrl($flickrId);
                    return sprintf('<a href="%s" target="_blank">%s</a>', $url, htmlspecialchars($flickrId));
                })
                ->renderAsHtml(),
            AvatarField::new('smallUrl'),
            TextField::new('description'),
            TextField::new('externalUrl'),
            TextField::new('status'),
        ];
    }
}
