<?php

declare(strict_types=1);

namespace Stringy;

class CollectionStringy extends \Arrayy\Collection\AbstractCollection
{
    public function getType(): string
    {
        return Stringy::class;
    }

    /**
     * @return Stringy[]
     */
    public function getAll(): array
    {
        return parent::getAll();
    }

    /**
     * @return \Generator|Stringy[]
     */
    public function getGenerator(): \Generator
    {
        return parent::getGenerator();
    }
}
