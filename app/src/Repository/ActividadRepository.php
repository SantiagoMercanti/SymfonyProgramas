<?php

namespace App\Repository;

use App\Entity\Actividad;
use App\Entity\Programa;
use App\Entity\TipoActividad;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Query;

/**
 * @extends ServiceEntityRepository<Actividad>
 *
 * @method Actividad|null find($id, $lockMode = null, $lockVersion = null)
 * @method Actividad|null findOneBy(array $criteria, array $orderBy = null)
 * @method Actividad[]    findAll()
 * @method Actividad[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ActividadRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Actividad::class);
    }

    /**
     * Construye una Query DQL para listado con filtros.
     *
     * Filtros soportados (keys en $f):
     *  - search: string|null (busca en actividad/descripcion)
     *  - activo: 1|0|"all"|null  (default lo decide el Manager; aquí solo aplicamos lo que venga)
     *  - programaId: int|null
     *  - tipoActividadId: int|null
     *  - sort: actividad|id|programa|tipoActividad|activo (default: actividad)
     *  - dir: asc|desc (default: asc)
     */
    public function buildListadoQuery(array $f): Query
    {
        $search          = $f['search']          ?? null;
        $activoParam     = $f['activo']          ?? null;
        $programaId      = $f['programaId']      ?? null;
        $tipoActividadId = $f['tipoActividadId'] ?? null;
        $sort            = $f['sort']            ?? 'actividad';
        $dir             = strtolower((string)($f['dir'] ?? 'asc')) === 'desc' ? 'DESC' : 'ASC';

        $dql = <<<DQL
            SELECT a, p, t
            FROM App\Entity\Actividad a
            JOIN a.programa p
            JOIN a.tipoActividad t
            WHERE 1=1
        DQL;

        $params = [];

        // Filtro activo: aplica solo si NO es "all" ni null
        if (!($activoParam === null || $activoParam === 'all')) {
            $dql .= ' AND a.activo = :activo';
            $params['activo'] = (int)$activoParam ? true : false;
        }

        if ($programaId !== null) {
            $dql .= ' AND p.id = :programaId';
            $params['programaId'] = (int)$programaId;
        }

        if ($tipoActividadId !== null) {
            $dql .= ' AND t.id = :tipoId';
            $params['tipoId'] = (int)$tipoActividadId;
        }

        if ($search !== null && $search !== '') {
            $dql .= ' AND (LOWER(a.actividad) LIKE :q OR LOWER(a.descripcion) LIKE :q)';
            $params['q'] = '%'.mb_strtolower($search).'%';
        }

        // Orden seguro, para evitar SQL Injection
        $orderMap = [
            'id'            => 'a.id',
            'actividad'     => 'a.actividad',
            'programa'      => 'p.id', // p.programa si se quiere ordenar por nombre del programa
            'tipoActividad' => 't.id', // t.tipoActividad si se quiere ordenar por nombre del tipo
            'activo'        => 'a.activo',
        ];
        $orderBy = $orderMap[$sort] ?? 'a.actividad';
        $dql .= " ORDER BY {$orderBy} {$dir}";

        $q = $this->getEntityManager()->createQuery($dql);
        foreach ($params as $k => $v) {
            $q->setParameter($k, $v);
        }
        return $q;
    }

    /**
     * Devuelve la Actividad solo si está activa. Si no existe o está inactiva, retorna null.
     */
    public function findActiveById(int $id): ?Actividad
    {
        $dql = <<<DQL
            SELECT a, p, t
            FROM App\Entity\Actividad a
            JOIN a.programa p
            JOIN a.tipoActividad t
            WHERE a.id = :id
              AND a.activo = true
        DQL;

        return $this->getEntityManager()
            ->createQuery($dql)
            ->setParameter('id', $id)
            ->getOneOrNullResult();
    }

    /**
     * Chequea si existe un duplicado por (programa, tipoActividad, actividad) case-insensitive.
     * Excluye el propio ID cuando se actualiza.
     */
    public function existsByProgramaTipoNombre(Programa $p, TipoActividad $t, string $nombre, ?int $exceptId = null): bool
    {
        $dql = <<<DQL
            SELECT a.id
            FROM App\Entity\Actividad a
            WHERE a.programa = :p
              AND a.tipoActividad = :t
              AND LOWER(a.actividad) = :n
        DQL;

        $params = [
            'p' => $p,
            't' => $t,
            'n' => mb_strtolower($nombre),
        ];

        // Excluir un ID (si es update sería correcto que ya exista la misma fila)
        if ($exceptId !== null) {
            $dql .= ' AND a.id <> :id';
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
