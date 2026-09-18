<?php

declare(strict_types=1);

namespace App\Tests\Load;

use App\Entity\Sacro;
use App\Command\AppCmasCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class LoadCommandTest extends KernelTestCase
{
    public function testCmasImportHonorsLimitAndIsIdempotent(): void
    {
        self::bootKernel();
        $em = self::getContainer()->get('doctrine.orm.entity_manager');
        self::assertTrue($em->getConnection()->getParams()['memory'] ?? false);
        (new SchemaTool($em))->createSchema($em->getMetadataFactory()->getAllMetadata());
        $file = tempnam(sys_get_temp_dir(), 'pgsc-cmas-');
        try {
            $stream = fopen($file, 'w');
            fputcsv($stream, ['code', 'vinculo', 'label.es', 'description.es', 'notes.es'], escape: '');
            for ($i = 1; $i <= 4; ++$i) {
                fputcsv($stream, ['fixture-'.$i, 'https://example.test/image-'.$i, 'Objeto '.$i, 'Descripción', 'Notas'], escape: '');
            }
            fclose($stream);
            for ($i = 0; $i < 2; ++$i) {
                $tester = new CommandTester(new Command('app:cmas', self::getContainer()->get(AppCmasCommand::class)));
                $tester->execute(['path' => $file, '--limit' => 3]);
                $tester->assertCommandIsSuccessful();
                self::assertStringContainsString('success: 3', $tester->getDisplay());
                self::assertSame(3, $em->getRepository(Sacro::class)->count([]));
            }
            self::assertNull($em->find(Sacro::class, 'fixture-4'));
        } finally {
            unlink($file);
        }
    }
}
