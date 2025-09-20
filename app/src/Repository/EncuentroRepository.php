<?php

namespace App\Repository;

use App\Entity\Encuentro;
use App\Entity\Comision;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Query;

/**
 * @extends ServiceEntityRepository<Encuentro>
 *
 * @method Encuentro|null find($id, $lockMode = null, $lockVersion = null)
 * @method Encuentro|null findOneBy(array $criteria, array $orderBy = null)
 * @method Encuentro[]    findAll()
 * @method Encuentro[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EncuentroRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Encuentro::class);
    }

    /**
     * Construye una Query DQL para el listado con filtros.
     *
     * Filtros soportados (keys en $f):
     *  - search: string|null (busca en e.encuentro y c.comision)
     *  - activo: 1|0|"all"|null  (default lo decide el Manager; aquí sólo aplicamos lo que venga)
     *  - comisionId: int|null
     *  - modalidadEncuentroId: int|null
     *  - desde: string ISO-8601|null  (fechaHoraInicio >= desde)
     *  - hasta: string ISO-8601|null  (fechaHoraFin    <= hasta)
     *  - sort: id|encuentro|inicio|fin|comision|modalidad|activo  (default: inicio)
     *  - dir: asc|desc (default: asc)
     */
    public function buildListadoQuery(array $f): Query
    {
        $search      = $f['search']               ?? null;
        $activoParam = $f['activo']               ?? null;
        $comisionId  = $f['comisionId']           ?? null;
        $modalidadId = $f['modalidadEncuentroId'] ?? null;
        $desdeStr    = $f['desde']                ?? null;
        $hastaStr    = $f['hasta']                ?? null;
        $sort        = $f['sort']                 ?? 'inicio';
        $dir         = strtolower((string)($f['dir'] ?? 'asc')) === 'desc' ? 'DESC' : 'ASC';

        $dql = <<<DQL
            SELECT e, c, m
            FROM App\Entity\Encuentro e
            JOIN e.comision c
            JOIN e.modalidadEncuentro m
            WHERE 1=1
        DQL;

        $params = [];

        // Filtro activo: aplica solo si NO es "all" ni null
        if (!($activoParam === null || $activoParam === 'all')) {
            $dql .= ' AND e.activo = :activo';
            $params['activo'] = (int)$activoParam ? true : false;
        }

        if ($comisionId !== null) {
            $dql .= ' AND c.id = :cid';
            $params['cid'] = (int)$comisionId;
        }

        if ($modalidadId !== null) {
            $dql .= ' AND m.id = :mid';
            $params['mid'] = (int)$modalidadId;
        }

        if ($search !== null && $search !== '') {
            $dql .= ' AND (LOWER(e.encuentro) LIKE :q OR LOWER(c.comision) LIKE :q)';
            $params['q'] = '%'.mb_strtolower($search).'%';
        }

        // Filtros de fecha (parseo defensivo)
        if ($desdeStr) {
            try {
                $desde = new \DateTimeImmutable($desdeStr);
                $dql .= ' AND e.fechaHoraInicio >= :desde';
                $params['desde'] = $desde;
            } catch (\Throwable) {
                // ignora filtro inválido
            }
        }
        if ($hastaStr) {
            try {
                $hasta = new \DateTimeImmutable($hastaStr);
                $dql .= ' AND e.fechaHoraFin <= :hasta';
                $params['hasta'] = $hasta;
            } catch (\Throwable) {
                // ignora filtro inválido
            }
        }

        // Orden seguro (whitelist)
        $orderMap = [
            'id'        => 'e.id',
            'encuentro' => 'e.encuentro',
            'inicio'    => 'e.fechaHoraInicio',
            'fin'       => 'e.fechaHoraFin',
            'comision'  => 'c.comision',
            'modalidad' => 'm.modalidadEncuentro',
            'activo'    => 'e.activo',
        ];
        $orderBy = $orderMap[$sort] ?? 'e.fechaHoraInicio';
        $dql    .= " ORDER BY {$orderBy} {$dir}, e.id ASC";

        $q = $this->getEntityManager()->createQuery($dql);
        foreach ($params as $k => $v) {
            $q->setParameter($k, $v);
        }
        return $q;
    }

    /**
     * Devuelve el Encuentro sólo si está activo; si no existe o está inactivo, retorna null.
     */
    public function findActiveById(int $id): ?Encuentro
    {
        $dql = <<<DQL
            SELECT e, c, m
            FROM App\Entity\Encuentro e
            JOIN e.comision c
            JOIN e.modalidadEncuentro m
            WHERE e.id = :id
              AND e.activo = true
        DQL;

        return $this->getEntityManager()
            ->createQuery($dql)
            ->setParameter('id', $id)
            ->getOneOrNullResult();
    }

    /**
     * (Opcional) Verifica si existe solapamiento de horarios dentro de la misma comisión.
     * Considera solapado si: (inicio < finExistente) y (fin > inicioExistente)
     */
    public function existsOverlapEnComision(
        Comision $comision,
        \DateTimeInterface $inicio,
        \DateTimeInterface $fin,
        ?int $exceptId = null
    ): bool {
        $dql = <<<DQL
            SELECT e.id
            FROM App\Entity\Encuentro e
            WHERE e.comision = :c
              AND e.fechaHoraInicio < :fin
              AND e.fechaHoraFin > :ini
        DQL;

        $params = [
            'c'   => $comision,
            'ini' => $inicio,
            'fin' => $fin,
        ];

        if ($exceptId !== null) {
            $dql .= ' AND e.id <> :id';
            $params['id'] = $exceptId;
        }

        // Limitar a activos únicamente
        // $dql .= ' AND e.activo = true';

        $res = $this->getEntityManager()
            ->createQuery($dql)
            ->setParameters($params)
            ->setMaxResults(1)
            ->getOneOrNullResult();

        return (bool)$res;
    }
}
