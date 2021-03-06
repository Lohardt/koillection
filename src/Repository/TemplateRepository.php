<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Template;
use App\Entity\User;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NonUniqueResultException;

class TemplateRepository extends EntityRepository
{
    /**
     * @return array
     */
    public function findAll() : array
    {
        return $this
            ->createQueryBuilder('t')
            ->orderBy('t.name', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }


    /**
     * @return array
     */
    public function findAllWithCounters() : array
    {
        return $this
            ->getEntityManager()
            ->createQueryBuilder()
            ->select('DISTINCT t as template, COUNT(DISTINCT f) as fieldsCounter')
            ->from(Template::class, 't')
            ->leftJoin('t.fields', 'f')
            ->groupBy('t.id')
            ->orderBy('t.name', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @param string $id
     * @return Template|null
     * @throws NonUniqueResultException
     */
    public function findWithItems(string $id) : ?Template
    {
        return $this
            ->createQueryBuilder('t')
            ->leftJoin('t.fields', 'f')
            ->addSelect('f')
            ->where('t.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    /**
     * @param string $id
     * @return Template|null
     * @throws NonUniqueResultException
     */
    public function findById(string $id) : ?Template
    {
        return $this
            ->createQueryBuilder('t')
            ->leftJoin('t.fields', 'f')
            ->addSelect('f')
            ->where('t.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
}
