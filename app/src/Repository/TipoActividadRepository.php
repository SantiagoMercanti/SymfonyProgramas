<?php

namespace App\Repository;

use App\Entity\TipoActividad;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TipoActividad>
 */
class TipoActividadRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TipoActividad::class);
    }

    /** @return TipoActividad[] */
    public function findActivos(): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.activo = :a')
            ->setParameter('a', true)
            ->orderBy('t.tipoActividad', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findOneActivoById(int $id): ?TipoActividad
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.id = :id')
            ->andWhere('t.activo = :a')
            ->setParameter('id', $id)
            ->setParameter('a', true)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
