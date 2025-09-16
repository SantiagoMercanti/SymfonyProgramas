<?php

namespace App\Repository;

use App\Entity\Programa;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;

/**
 * @extends ServiceEntityRepository<Programa>
 */
class ProgramaRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Programa::class);
    }

    /**
     * Devuelve un QB para listar Programas con filtros básicos.
     *
     * @param string|null $search  Búsqueda por nombre de programa (LIKE, case-insensitive)
     * @param bool|null   $activo  Filtra por activo si no es null
     * @param bool|null   $vigente Filtra por vigente si no es null
     * @param string      $sort    Campo permitido para orden
     * @param string      $dir     asc|desc
     */
    public function buildListadoQb(
        ?string $search,
        ?bool $activo,
        ?bool $vigente,
        string $sort,
        string $dir
    ): QueryBuilder {
        $qb = $this->createQueryBuilder('p');

        if ($search !== null && $search !== '') {
            $qb->andWhere('LOWER(p.programa) LIKE :q')
                ->setParameter('q', '%' . mb_strtolower($search) . '%');
        }

        if ($activo !== null) {
            $qb->andWhere('p.activo = :activo')
                ->setParameter('activo', $activo);
        }

        if ($vigente !== null) {
            $qb->andWhere('p.vigente = :vigente')
                ->setParameter('vigente', $vigente);
        }

        // Whitelist de sort
        $map = [
            'id_programa' => 'p.id',
            'programa'    => 'p.programa',
            'vigente'     => 'p.vigente',
            'activo'      => 'p.activo',
        ];
        $sortExpr = $map[$sort] ?? 'p.programa';
        $dir      = strtolower($dir) === 'desc' ? 'DESC' : 'ASC';

        return $qb->orderBy($sortExpr, $dir);
    }
}
