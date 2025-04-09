<?php

namespace App\Controller\Admin;

use Adeliom\EasyMediaBundle\Admin\Field\EasyMediaField;
use App\Entity\Obra;
use App\Security\Voter\ObjVoter;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminAction;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\BatchActionDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;

class ObraCrudController extends AbstractCrudController
{
    public function __construct(protected AdminUrlGenerator $adminUrlGenerator)
    {
    }

    public static function getEntityFqcn(): string
    {
        return Obra::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield EasyMediaField::new('audio');
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
                        ->setEntityId($entity->getId())
                        ->generateUrl() . '">' . $value . '</a>';
                })->onlyOnIndex(),
            TextField::new('title')->hideOnIndex(),
            TextField::new('description')
                     ->hideOnIndex(),
        ] as $field) {
            yield $field;
        }
        foreach (['width', 'height', 'depth'] as $fieldName) {
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

        $rowPrintAction = Action::new('print', false, 'fa:print')
            ->linkToUrl(function ($entity) {
                return $this->generateUrl('obj_show', ['obraId' => $entity->getId()]);
            });

        $batchPrintAction = Action::new('batchPrint', 'Print')
            ->linkToCrudAction('batchPrint')
            ->addCssClass('btn btn-secondary');

        return $actions
            ->add(Crud::PAGE_INDEX, $rowPrintAction)
            ->addBatchAction($batchPrintAction)
            ->setPermission(Action::EDIT, ObjVoter::EDIT)
            ->setPermission(Action::DELETE, ObjVoter::DELETE)
            ->remove(Crud::PAGE_INDEX, Action::DETAIL)
            ->update(Crud::PAGE_INDEX, Action::EDIT, function (Action $action) {
                return $action->setLabel(false)->setIcon('fa:edit');
            })
        ;
    }

    #[AdminAction(routePath: '{/batch-print', routeName: 'batch_print', methods: ['POST'])]
    public function batchPrint(BatchActionDto $batchActionDto, AdminContext $context)
    {
        $ids = $batchActionDto->getEntityIds();

        dd($ids);

        return $this->redirectToRoute('admin_obra_index');
    }
}
