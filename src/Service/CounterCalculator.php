<?php

declare(strict_types=1);

namespace App\Service;

use App\Doctrine\QueryNameGenerator;
use App\Entity\Album;
use App\Entity\Collection;
use App\Entity\Item;
use App\Entity\Photo;
use App\Entity\Wish;
use App\Entity\Wishlist;
use App\Enum\VisibilityEnum;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\ResultSetMapping;

class CounterCalculator
{
    /**
     * @var EntityManagerInterface
     */
    private EntityManagerInterface $em;

    /**
     * @var QueryNameGenerator
     */
    private QueryNameGenerator $qng;

    /**
     * @var ContextHandler
     */
    private ContextHandler $contextHandler;

    /**
     * CounterCalculator constructor.
     * @param EntityManagerInterface $em
     * @param QueryNameGenerator $qng
     * @param ContextHandler $contextHandler
     */
    public function __construct(EntityManagerInterface $em, QueryNameGenerator $qng, ContextHandler $contextHandler)
    {
        $this->em = $em;
        $this->qng = $qng;
        $this->contextHandler = $contextHandler;
    }

    /**
     * @return array
     */
    public function computeCounters() : array
    {
        //Collections and items
        $tableName = $this->em->getClassMetadata(Collection::class)->getTableName();
        $itemTableName = $this->em->getClassMetadata(Item::class)->getTableName();
        $parentProperty = 'collection_id';
        $globalCacheIndexKey = 'collections';
        $collections = $this->executeItemQuery($tableName, $itemTableName, $parentProperty);
        $collections = array_merge($collections, $this->getGlobalCounters($tableName, $itemTableName, $globalCacheIndexKey));

        //Wishlists and wishes
        $tableName = $this->em->getClassMetadata(Wishlist::class)->getTableName();
        $itemTableName = $this->em->getClassMetadata(Wish::class)->getTableName();
        $parentProperty = 'wishlist_id';
        $globalCacheIndexKey = 'wishlists';
        $wishlists = $this->executeItemQuery($tableName, $itemTableName, $parentProperty);
        $wishlists = array_merge($wishlists, $this->getGlobalCounters($tableName, $itemTableName, $globalCacheIndexKey));

        //Albums and photos
        $tableName = $this->em->getClassMetadata(Album::class)->getTableName();
        $itemTableName = $this->em->getClassMetadata(Photo::class)->getTableName();
        $parentProperty = 'album_id';
        $globalCacheIndexKey = 'albums';
        $albums = $this->executeItemQuery($tableName, $itemTableName, $parentProperty);
        $albums = array_merge($albums, $this->getGlobalCounters($tableName, $itemTableName, $globalCacheIndexKey));

        return \array_merge($collections, $wishlists, $albums);
    }

    public function getGlobalCounters($table, $itemTable, $cacheIndexName) : array
    {
        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('children', 'children');
        $alias = $this->qng->generateJoinAlias('c');
        $ownerId = $this->contextHandler->getContextUser()->getId();

        $sql = "
            SELECT COUNT(DISTINCT id) as children
            FROM $table $alias
            WHERE $alias.owner_id = '$ownerId'
        ";

        if ($this->em->getFilters()->isEnabled('visibility')) {
            $sql .= sprintf("AND %s.visibility = '%s'", $alias, VisibilityEnum::VISIBILITY_PUBLIC);
        }
        $children = $this->em->createNativeQuery($sql, $rsm)->getResult()[0]['children'];

        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('items', 'items');
        $alias = $this->qng->generateJoinAlias('i');

        $sql = "
            SELECT COUNT(DISTINCT id) as items
            FROM $itemTable $alias
            WHERE $alias.owner_id = '$ownerId'
        ";

        if ($this->em->getFilters()->isEnabled('visibility')) {
            $sql .= sprintf("AND %s.visibility = '%s'", $alias, VisibilityEnum::VISIBILITY_PUBLIC);
        }
        $items = $this->em->createNativeQuery($sql, $rsm)->getResult()[0]['items'];

        $results[$cacheIndexName] = [
            'children' => $children,
            'items' => $items,
        ];

        return $results;
    }

    /**
     * @param $table
     * @param $itemTable
     * @param $parentProperty
     * @return array
     */
    public function executeItemQuery($table, $itemTable, $parentProperty) : array
    {
        $rsm = new ResultSetMapping();
        $rsm->addIndexByScalar('id');
        $rsm->addScalarResult('counters', 'counters');
        $alias = $this->qng->generateJoinAlias('c');
        $ownerId = $this->contextHandler->getContextUser()->getId();

        //Counters per objects
        $sqlCounters = $this->getSQLForCounters($alias, $table, $itemTable, $parentProperty);
        $sql = "
            SELECT $alias.id as id, ($sqlCounters) as counters
            FROM $table $alias
            WHERE $alias.owner_id = '$ownerId'
        ";

        if ($this->em->getFilters()->isEnabled('visibility')) {
            $sql .= sprintf("AND %s.visibility = '%s'", $alias, VisibilityEnum::VISIBILITY_PUBLIC);
        };

        $results = [];
        foreach ($this->em->createNativeQuery($sql, $rsm)->getResult() as $id => $result) {
            $explodedCounters = explode('-', $result['counters']);
            $results[$id] = [
                'children' => $explodedCounters[0],
                'items' => $explodedCounters[1],
            ];
        }

        return $results;
    }

    /**
     * @param string $alias
     * @param $table
     * @param $itemTable
     * @return string
     */
    private function getSQLForCounters(string $alias, $table, $itemTable, $parentProperty) : string
    {
        $c1 = $this->qng->generateJoinAlias('c');
        $c2 = $this->qng->generateJoinAlias('c');

        $ch1 = $this->qng->generateJoinAlias('ch');
        $ch2 = $this->qng->generateJoinAlias('ch');

        $i1 = $this->qng->generateJoinAlias('i');
        $i2 = $this->qng->generateJoinAlias('i');

        $sql = "
            WITH RECURSIVE counters AS (
                SELECT $c1.id, $c1.parent_id, $c1.visibility, $i1.id AS item_id
                FROM $table $c1
                LEFT JOIN $itemTable $i1 ON $i1.$parentProperty = $c1.id
                WHERE $c1.id = $alias.id
                UNION
                SELECT $c2.id, $c2.parent_id, $c2.visibility, $i2.id AS item_id 
                FROM $table $c2
                LEFT JOIN $itemTable $i2 ON $i2.$parentProperty = $c2.id
                INNER JOIN counters $ch1 ON $ch1.id = $c2.parent_id
            ) SELECT CONCAT(COUNT(DISTINCT id) - 1, '-' , COUNT(DISTINCT item_id)) FROM counters $ch2
        ";

        if ($this->em->getFilters()->isEnabled('visibility')) {
            $sql .= sprintf("WHERE %s.visibility = '%s'", $ch2, VisibilityEnum::VISIBILITY_PUBLIC);
        };

        return $sql;
    }
}
