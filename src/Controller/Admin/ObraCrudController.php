<?php

namespace App\Controller\Admin;

use App\Entity\Obra;
use App\Repository\ObraRepository;
use App\Security\Voter\ObjVoter;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminAction;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\BatchActionDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AvatarField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use App\Repository\MediaRepository;
use Survos\CoreBundle\Controller\BaseCrudController;

class ObraCrudController extends BaseCrudController
{
    public function __construct(
        protected AdminUrlGenerator $adminUrlGenerator,
        protected MediaRepository $imageRepository,
        private ObraRepository $obraRepository,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return Obra::class;
    }

    public function configureFields(string $pageName): iterable
    {
//        yield EasyMediaField::new('audio');
        yield AvatarField::new('thumbnailUrl', 'Media')
//            ->formatValue(function ($value, Obra $entity) {
//                $imageCodes = $entity->getImageCodes();
//                if (empty($imageCodes) || !is_array($imageCodes)) {
//                    return null; // AvatarField will show a default avatar
//                }
//
//                try {
//                    // Get the first image entity (primary image for obra)
//                    if ($primaryImageCode = $imageCodes[0]) {
//                        $image = $this->imageRepository->findByCode($primaryImageCode);
//                    }
//
//                    if (!$image) {
//                        return null; // AvatarField will show default avatar
//                    }
//
//                    $thumbnailUrl = $image->getThumbnail(); // Gets the 'small' size URL
//                    return $thumbnailUrl; // Return URL or null for default avatar
//
//                } catch (\Exception $e) {
//                    return null; // AvatarField will show default avatar
//                }
//            })
            ->setHeight(40) // Set avatar size
            ->onlyOnIndex();
        foreach (['width', 'height', 'depth'] as $fieldName) {
            yield IntegerField::new($fieldName)->setColumns(3)->onlyOnForms();
        }

        foreach ([
            //            IdField::new('id'),
            TextField::new('code')
                ->onlyOnForms()
                ->setPermission('ROLE_ADMIN'),
            TextField::new('title')
                ->formatValue(function ($value, $entity) {
                    return '<a href="' . $this->adminUrlGenerator
                        ->setController(self::class)
                        ->setAction('detail')
                        ->setEntityId($entity->id)
                        ->generateUrl() . '">' . $value . '</a>';
                })->onlyOnIndex(),
            TextField::new('title')->hideOnIndex(),
            ArrayField::new('imageCodes'),
            TextField::new('description')
                     ->hideOnIndex(),
        ] as $field) {
            yield $field;
        }
        // Avatar field - show obra thumbnails with nice styling
        yield TextField::new('size', 'Dimensions')
            ->setHelp('Size from CSV (e.g. "147 X 175 cm")')
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

        $rowPrintAction = Action::new('print', false, 'fa:print')
            ->linkToUrl(function ($entity) {
                return $this->generateUrl('obj_show', ['obraId' => $entity->id]);
            });

        $batchPrintAction = Action::new('batchPrint', 'Print')
            ->linkToCrudAction('batchPrint')
            ->addCssClass('btn btn-secondary');

        return $actions
            ->add(Crud::PAGE_INDEX, $rowPrintAction)
            ->addBatchAction($batchPrintAction)
            ->setPermission(Action::EDIT, ObjVoter::EDIT)
            ->setPermission(Action::DELETE, ObjVoter::DELETE)
            ->update(Crud::PAGE_INDEX, Action::EDIT, function (Action $action) {
                return $action->setLabel(false)->setIcon('fa:edit');
            })
        ;
    }

    #[AdminAction(routePath: '/batch-print', routeName: 'batch_print', methods: ['POST'])]
    public function batchPrint(BatchActionDto $batchActionDto, AdminContext $context)
    {
        $ids = $batchActionDto->getEntityIds();
        $obras = $this->obraRepository->findBy(['id' => $ids]);
        dd($ids);
        return $this->render('@EasyCorp/easyadmin-batch-print.html.twig', []);


        return $this->redirectToRoute('admin_obra_index');
    }
}
