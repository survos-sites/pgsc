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
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AvatarField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Survos\TranslatableFieldBundle\EasyAdmin\Field\TranslationsField;
use App\Repository\MediaRepository;

class ArtistCrudController extends AbstractCrudController
{
    public function __construct(
        protected AdminUrlGenerator $adminUrlGenerator,
        protected MediaRepository $imageRepository
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return Artist::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            // Name field with link on index
            TextField::new('name')
                ->formatValue(function ($value, $entity) {
                    return '<a href="' . $this->adminUrlGenerator
                            ->setController(self::class)
                            ->setAction('detail')
                            ->setEntityId($entity->getId())
                            ->generateUrl() . '">' . $value . '</a>';
                })->onlyOnIndex(),

            // Name field for forms
            TextField::new('name')->hideOnIndex(),

            // Code field
            TextField::new('code', 'code'),

            // Birth year
            IntegerField::new('birthYear', 'birthYear'),

            // Gender choice
            ChoiceField::new('gender', 'gender')->setChoices(
                array_combine(Artist::GENDERS, Artist::GENDERS)
            )->setFormTypeOption('expanded', true),

            // Bio (read-only on form, comes from translations)
            TextField::new('bio')->hideOnForm(),

            // Translations field for bio
            TranslationsField::new('translations')
                ->addTranslatableField(
                    TextareaField::new('bio')
                ),

            // Avatar field - show artist thumbnails with nice styling
            AvatarField::new('thumbnailUrl', 'Media')
                ->formatValue(function ($value, Artist $entity) {
                    $imageCodes = $entity->getImageCodes();
                    if (empty($imageCodes) || !is_array($imageCodes)) {
                        return null; // AvatarField will show a default avatar
                    }

                    try {
                        // Get the first image entity
                        $primaryImageCode = $imageCodes[0];
                        $image = $this->imageRepository->findByCode($primaryImageCode);

                        if (!$image) {
                            return null; // AvatarField will show default avatar
                        }

                        $thumbnailUrl = $image->getThumbnailUrl(); // Gets the 'small' size URL
                        return $thumbnailUrl; // Return URL or null for default avatar

                    } catch (\Exception $e) {
                        return null; // AvatarField will show default avatar
                    }
                })
                ->setHeight(40) // Set avatar size
                ->onlyOnIndex(),

            // Media codes field - show comma-delimited image codes
            ArrayField::new('imageCodes', 'Media Codes')
                ->formatValue(function ($value, Artist $entity) {
                    if (empty($value) || !is_array($value)) {
                        return '-';
                    }
                    return implode(', ', $value);
                })
                ->onlyOnIndex()
                ->setHelp('SAIS image codes'),

            // Drive URL field
            TextField::new('driveUrl', 'Drive URL')
                ->hideOnIndex()
                ->setHelp('Google Drive URL for images'),

            // Social media
            TextareaField::new('socialMedia')
                ->setHelp('URLs, one per line')
                ->hideOnIndex(),

            TextareaField::new('social')->hideOnIndex()
                ->setNumOfRows(2)
                ->setHelp("e.g. facebook, instagram, twitter.  One URL per line"),

            // Obra count (read-only)
            IntegerField::new('obraCount', 'Obra Count')
                ->onlyOnIndex(),

            // Studio visitable
            ChoiceField::new('studioVisitable', 'Studio Visitable')->setChoices(
                array_combine(Artist::STUDIO_VISITABLE, Artist::STUDIO_VISITABLE),
            )->renderExpanded(),

            // Obras collection
            CollectionField::new('obras')
                ->setTemplatePath('admin/field/obras.html.twig')
                ->useEntryCrudForm()
                ->hideOnIndex(),
        ];
    }

    public function configureActions(Actions $actions): Actions
    {
        $rowPrintAction = Action::new('print', false, 'fa:print')
            ->linkToUrl(function ($entity) {
                return $this->generateUrl('artist_print', ['artistId' => $entity->getId()]);
            });

        return $actions
            ->add(Crud::PAGE_INDEX, $rowPrintAction)
            ->setPermission(Action::EDIT, ArtistVoter::EDIT)
            ->setPermission(Action::DELETE, ArtistVoter::DELETE)
            ->remove(Crud::PAGE_INDEX, Action::DETAIL)
            ->update(Crud::PAGE_INDEX, Action::EDIT, function (Action $action) {
                return $action->setLabel(false)->setIcon('fa:edit');
            })
        ;
    }
}
