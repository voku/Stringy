<?php

declare(strict_types=1);

namespace Stringy;

/**
 * @template TKey of array-key
 * @template T of Stringy
 * @extends \Arrayy\Collection\Collection<TKey,T>
 */
class CollectionStringy extends \Arrayy\Collection\Collection
{
    /**
     * Creates an CollectionInterface object.
     *
     * @param mixed  $data
     * @param string $iteratorClass
     * @param bool   $checkPropertiesInConstructor
     *
     * @return static
     *                <p>(Immutable) Returns an new instance of the CollectionInterface object.</p>
     *
     * @template TKeyCreate as array-key
     * @template TCreate of Stringy
     *
     * @phpstan-param  array<TKeyCreate,TCreate> $data
     * @phpstan-param  class-string<\Arrayy\ArrayyIterator<array-key, mixed>> $iteratorClass
     * @phpstan-return static<TKeyCreate,TCreate>
     *
     * @psalm-mutation-free
     */
    public static function create(
        $data = [],
        string $iteratorClass = \Arrayy\ArrayyIterator::class,
        bool $checkPropertiesInConstructor = true
    ) {
        return new static(
            $data,
            $iteratorClass,
            $checkPropertiesInConstructor
        );
    }

    public function getType(): string
    {
        return Stringy::class;
    }

    /**
     * @return Stringy[]
     *
     * @phpstan-return array<array-key,Stringy>
     */
    public function getAll(): array
    {
        return parent::getAll();
    }

    /**
     * @return \Generator|Stringy[]
     *
     * @phpstan-return \Generator<mixed,Stringy>|\Generator<TKey,T>
     * @psalm-mutation-free
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
     * @param string ...$string
     *
     * @return $this
     */
    public function addString(string ...$string): self
    {
        foreach ($string as $stringTmp) {
            /** @phpstan-ignore-next-line | FP? */
            $this->add(Stringy::create($stringTmp));
        }

        return $this;
    }

    /**
     * @param Stringy ...$stringy
     *
     * @return $this
     */
    public function addStringy(Stringy ...$stringy): self
    {
        foreach ($stringy as $stringyTmp) {
            /** @phpstan-ignore-next-line | FP? */
            $this->add($stringyTmp);
        }

        return $this;
    }

    /**
     * @param string[] $strings
     *
     * @return static
     */
    public static function createFromStrings($strings = []): self
    {
        /** @noinspection AlterInForeachInspection */
        foreach ($strings as &$string) {
            $string = Stringy::create($string);
        }

        /** @noinspection PhpSillyAssignmentInspection */
        /** @var Stringy[] $strings */
        $strings = $strings;

        return new static($strings);
    }
}
