<?php

namespace App\Repository;

use App\Entity\Media;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Media>
 */
class MediaRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Media::class);
    }

    /**
     * Find an image by its SAIS code
     */
    public function findByCode(?string $code): ?Media
    {
        return $code ? $this->find($code) : null;
    }

    /**
     * Find images by Google Drive file ID
     */
    public function findByDriveId(string $driveId): ?Media
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.originalUrl LIKE :driveId')
            ->setParameter('driveId', '%' . $driveId . '%')
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Find all processed images (have resized versions)
     */
    public function findProcessed(): array
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.resized IS NOT NULL')
            ->andWhere('i.statusCode = :statusCode')
            ->setParameter('statusCode', 200)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find all unprocessed images
     */
    public function findUnprocessed(): array
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.resized IS NULL OR i.statusCode != :statusCode')
            ->setParameter('statusCode', 200)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find images by mime type
     */
    public function findByMimeType(string $mimeType): array
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.mimeType = :mimeType')
            ->setParameter('mimeType', $mimeType)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find images created within a date range
     */
    public function findByDateRange(\DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.createdAt >= :startDate')
            ->andWhere('i.createdAt <= :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->orderBy('i.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get statistics about images
     */
    public function getStatistics(): array
    {
        $qb = $this->createQueryBuilder('i');

        $total = $qb->select('COUNT(i.code)')
            ->getQuery()
            ->getSingleScalarResult();

        $processed = $qb->select('COUNT(i.code)')
            ->andWhere('i.resized IS NOT NULL')
            ->andWhere('i.statusCode = :statusCode')
            ->setParameter('statusCode', 200)
            ->getQuery()
            ->getSingleScalarResult();

        return [
            'total' => $total,
            'processed' => $processed,
            'unprocessed' => $total - $processed,
            'processing_rate' => $total > 0 ? round(($processed / $total) * 100, 2) : 0
        ];
    }

    public function save(Media $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Media $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
