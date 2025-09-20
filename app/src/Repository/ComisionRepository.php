<?php

namespace App\Repository;

use App\Entity\Comision;
use App\Entity\Actividad;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Query;

/**
 * @extends ServiceEntityRepository<Comision>
 *
 * @method Comision|null find($id, $lockMode = null, $lockVersion = null)
 * @method Comision|null findOneBy(array $criteria, array $orderBy = null)
 * @method Comision[]    findAll()
 * @method Comision[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ComisionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Comision::class);
    }

    /**
     * Construye una Query DQL para el listado con filtros.
     *
     * Filtros soportados (keys en $f):
     *  - search: string|null (busca en nombre de comisión y actividad)
     *  - activo: 1|0|"all"|null  (default lo decide el Manager; aquí solo aplicamos lo que venga)
     *  - actividadId: int|null
     *  - sort: id|comision|actividad|activo  (default: comision)
     *  - dir: asc|desc (default: asc)
     */
    public function buildListadoQuery(array $f): Query
    {
        $search      = $f['search']      ?? null;
        $activoParam = $f['activo']      ?? null;
        $actividadId = $f['actividadId'] ?? null;
        $sort        = $f['sort']        ?? 'comision';
        $dir         = strtolower((string)($f['dir'] ?? 'asc')) === 'desc' ? 'DESC' : 'ASC';

        $dql = <<<DQL
            SELECT c, a
            FROM App\Entity\Comision c
            JOIN c.actividad a
            WHERE 1=1
        DQL;

        $params = [];

        // Filtro activo: aplica solo si NO es "all" ni null
        if (!($activoParam === null || $activoParam === 'all')) {
            $dql .= ' AND c.activo = :activo';
            $params['activo'] = (int)$activoParam ? true : false;
        }

        if ($actividadId !== null) {
            $dql .= ' AND a.id = :actividadId';
            $params['actividadId'] = (int)$actividadId;
        }

        if ($search !== null && $search !== '') {
            $dql .= ' AND (LOWER(c.comision) LIKE :q OR LOWER(a.actividad) LIKE :q)';
            $params['q'] = '%'.mb_strtolower($search).'%';
        }

        // Orden seguro (whitelist)
        $orderMap = [
            'id'        => 'c.id',
            'comision'  => 'c.comision',
            'actividad' => 'a.actividad',
            'activo'    => 'c.activo',
        ];
        $orderBy = $orderMap[$sort] ?? 'c.comision';
        $dql    .= " ORDER BY {$orderBy} {$dir}, c.id ASC";

        $q = $this->getEntityManager()->createQuery($dql);
        foreach ($params as $k => $v) {
            $q->setParameter($k, $v);
        }
        return $q;
    }

    /**
     * Devuelve la Comisión solo si está activa. Si no existe o está inactiva, retorna null.
     */
    public function findActiveById(int $id): ?Comision
    {
        $dql = <<<DQL
            SELECT c, a
            FROM App\Entity\Comision c
            JOIN c.actividad a
            WHERE c.id = :id
              AND c.activo = true
        DQL;

        return $this->getEntityManager()
            ->createQuery($dql)
            ->setParameter('id', $id)
            ->getOneOrNullResult();
    }

    /**
     * Chequea si existe un duplicado por (actividad, comision) case-insensitive.
     * Excluye el propio ID cuando se actualiza.
     */
    public function existsByActividadNombre(Actividad $actividad, string $nombre, ?int $exceptId = null): bool
    {
        $dql = <<<DQL
            SELECT c.id
            FROM App\Entity\Comision c
            WHERE c.actividad = :a
              AND LOWER(c.comision) = :n
        DQL;

        $params = [
            'a' => $actividad,
            'n' => mb_strtolower(trim($nombre)),
        ];

        if ($exceptId !== null) {
            $dql .= ' AND c.id <> :id';
            $params['id'] = $exceptId;
        }

        $res = $this->getEntityManager()
            ->createQuery($dql)
            ->setParameters($params)
            ->setMaxResults(1)
            ->getOneOrNullResult();

        return (bool)$res;
    }
}
