<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Entity\Interfaces\ChildCountableInterface;
use App\Entity\Interfaces\ItemCountableInterface;
use App\Entity\Item;
use App\Entity\Photo;
use App\Entity\Wish;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\UnitOfWork;

class ItemsCounterListener
{
    /**
     * @var UnitOfWork
     */
    private $uow;

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @param OnFlushEventArgs $args
     */
    public function onFlush(OnFlushEventArgs $args)
    {
        $this->em = $args->getEntityManager();
        $this->uow = $this->em->getUnitOfWork();

        foreach ($this->uow->getScheduledEntityInsertions() as $keyEntity => $entity) {
            if ($entity instanceof ItemCountableInterface) {
                $this->increaseCounter($entity->getParent());
            }
        }

        foreach ($this->uow->getScheduledEntityDeletions() as $keyEntity => $entity) {
            if ($entity instanceof ItemCountableInterface) {
                $this->decreaseCounter($entity->getParent());
            }
        }

        foreach ($this->uow->getScheduledEntityUpdates() as $keyEntity => $entity) {
            if ($entity instanceof ItemCountableInterface) {
                $changeset = $this->uow->getEntityChangeSet($entity);

                switch (true) {
                    case $entity instanceof Item:
                        $key = 'collection';
                        break;
                    case $entity instanceof Wish:
                        $key = 'wishlist';
                        break;
                    case $entity instanceof Photo:
                        $key = 'album';
                        break;
                }
                if (isset($changeset[$key])) {
                    $this->decreaseCounter($changeset[$key][0]);
                    $this->increaseCounter($changeset[$key][1]);
                }
            }
        }
    }

    private function increaseCounter($parent)
    {
        while ($parent instanceof ChildCountableInterface) {
            $parent->increaseItemsCounterBy(1);
            $this->uow->computeChangeSet($this->em->getClassMetadata(get_class($parent)), $parent);
            $parent = $parent->getParent();
        }
    }

    private function decreaseCounter($parent)
    {
        while ($parent instanceof ChildCountableInterface) {
            $parent->decreaseItemsCounterBy(1);
            $this->uow->computeChangeSet($this->em->getClassMetadata(get_class($parent)), $parent);
            $parent = $parent->getParent();
        }
    }
}
