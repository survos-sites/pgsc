<?php

declare(strict_types=1);

use App\Entity\Catalog;
use App\Entity\Spreadsheet;
use App\Workflow\ImageWorkflow;
use App\Workflow\MetWorkflow;
use App\Workflow\MusdigObjectWorkflow;
use App\Workflow\OwnerWorkflow;
use Survos\WorkflowBundle\Service\ConfigureFromAttributesService;
use Symfony\Config\FrameworkConfig;

// modeled after knp_dictionary.php
//return static function (ContainerConfigurator $containerConfigurator): void {
//    $containerConfigurator->extension('knp_dictionary', [

return static function (FrameworkConfig $framework) {
//return static function (ContainerConfigurator $containerConfigurator): void {

    foreach ([
        // doctrine
        \App\Workflow\LocationWorkflow::class
             ] as $workflowClass) {
        ConfigureFromAttributesService::configureFramework($workflowClass, $framework, [$workflowClass]);
    }

};
