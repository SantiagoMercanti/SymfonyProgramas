<?php

namespace App\Repository;

use App\Entity\ModalidadEncuentro;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ModalidadEncuentro>
 */
class ModalidadEncuentroRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ModalidadEncuentro::class);
    }

    /** @return ModalidadEncuentro[] */
    public function findActivos(): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.activo = :a')
            ->setParameter('a', true)
            ->orderBy('m.modalidadEncuentro', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findOneActivoById(int $id): ?ModalidadEncuentro
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.id = :id')
            ->andWhere('m.activo = :a')
            ->setParameter('id', $id)
            ->setParameter('a', true)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
