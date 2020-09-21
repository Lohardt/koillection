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

class ChildrenCounterListener
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
            if ($entity instanceof ChildCountableInterface) {
                $this->increaseCountersBy($entity->getParent(), 1);
            }
        }

        foreach ($this->uow->getScheduledEntityDeletions() as $keyEntity => $entity) {
            if ($entity instanceof ChildCountableInterface) {
                $this->decreaseCountersBy($entity->getParent(), 1);
            }
        }

        foreach ($this->uow->getScheduledEntityUpdates() as $keyEntity => $entity) {
            if ($entity instanceof ChildCountableInterface) {
                $changeset = $this->uow->getEntityChangeSet($entity);

                if (isset($changeset['parent'])) {
                    $this->decreaseCountersBy($changeset['parent'][0], $entity->getChildrenCounter() + 1, $entity->getItemsCounter());
                    $this->increaseCountersBy($changeset['parent'][1], $entity->getChildrenCounter() + 1, $entity->getItemsCounter());
                }
            }
        }
    }

    private function increaseCountersBy($parent, $childrenNumber, $itemsNumber = 0)
    {
        while ($parent instanceof ChildCountableInterface) {
            $parent->increaseChildrenCounterBy($childrenNumber);
            $parent->increaseItemsCounterBy($itemsNumber);
            $this->uow->computeChangeSet($this->em->getClassMetadata(get_class($parent)), $parent);


            $parent = $parent->getParent();
        }
    }

    private function decreaseCountersBy($parent, $childrenNumber, $itemsNumber = 0)
    {
        while ($parent instanceof ChildCountableInterface) {
            $parent->decreaseChildrenCounterBy($childrenNumber);
            $parent->decreaseItemsCounterBy($itemsNumber);
            $this->uow->computeChangeSet($this->em->getClassMetadata(get_class($parent)), $parent);
            $parent = $parent->getParent();
        }
    }
}
