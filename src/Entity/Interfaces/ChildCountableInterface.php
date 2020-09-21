<?php

declare(strict_types=1);

namespace App\Entity\Interfaces;

interface ChildCountableInterface
{
    /**
     * @return string|null
     */
    public function getId() : ?string;

    /**
     * @return mixed
     */
    public function getParent() : ?ChildCountableInterface;

    /**
     * @return mixed
     */
    public function increaseItemsCounterBy($number);

    /**
     * @return mixed
     */
    public function decreaseItemsCounterBy($number);

    /**
     * @return mixed
     */
    public function increaseChildrenCounterBy($number);

    /**
     * @return mixed
     */
    public function decreaseChildrenCounterBy($number);
}
