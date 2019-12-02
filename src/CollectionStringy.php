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

    /**
     * @return string[]
     */
    public function toStrings(): array {
        // init
        $result = [];

        foreach ($this->getArray() as $key => $value) {
            /** @var $value Stringy */
            $result[$key] = $value->toString();
        }

        return $result;
    }

    public function addString(string $string): CollectionStringy
    {
        $this->add(Stringy::create($string));

        return $this;
    }

    /**
     * @param string[] $strings
     *
     * @return static
     */
    public static function createFromStrings($strings = []): self {
        foreach ($strings as &$string) {
            $string = Stringy::create($string);
        }

        return new static($strings);
    }
}
