<?php

declare(strict_types=1);

namespace App\Entity\Interfaces;

interface ItemCountableInterface
{
    /**
     * @return string|null
     */
    public function getId() : ?string;

    /**
     * @return ChildCountableInterface|null
     */
    public function getParent() : ?ChildCountableInterface;
}
