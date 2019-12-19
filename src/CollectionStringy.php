<?php

declare(strict_types=1);

namespace Stringy;

/**
 * @template TKey of array-key
 * @template T
 * @extends \Arrayy\Collection\Collection<TKey,T|Stringy>
 */
class CollectionStringy extends \Arrayy\Collection\Collection
{
    public function getType(): string
    {
        return Stringy::class;
    }

    /**
     * @return Stringy[]
     *
     * @psalm-return array<array-key,Stringy>
     */
    public function getAll(): array
    {
        return parent::getAll();
    }

    /**
     * @return \Generator|Stringy[]
     *
     * @psalm-return \Generator<Stringy>
     */
    public function getGenerator(): \Generator
    {
        return parent::getGenerator();
    }

    /**
     * @return string[]
     */
    public function toStrings(): array
    {
        // init
        $result = [];

        foreach ($this->getArray() as $key => $value) {
            \assert($value instanceof Stringy);
            $result[$key] = $value->toString();
        }

        return $result;
    }

    /**
     * @param string $string
     *
     * @return $this
     */
    public function addString(string $string): self
    {
        $this->add(Stringy::create($string));

        return $this;
    }

    /**
     * @param Stringy $stringy
     *
     * @return $this
     */
    public function addStringy(Stringy $stringy): self
    {
        $this->add($stringy);

        return $this;
    }

    /**
     * @param string[] $strings
     *
     * @psalm-pure
     *
     * @return static
     */
    public static function createFromStrings($strings = []): self
    {
        foreach ($strings as &$string) {
            $string = Stringy::create($string);
        }

        /**
         * @psalm-suppress ImpureMethodCall -> add more psalm stuff to the collection class
         */
        return new static($strings);
    }
}
