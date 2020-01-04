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
     *
     * @noinspection SenselessProxyMethodInspection - other phpdocs ;)
     */
    public function getAll(): array
    {
        return parent::getAll();
    }

    /**
     * @return \Generator|Stringy[]
     *
     * @psalm-return \Generator<mixed,Stringy>|\Generator<TKey,Stringy>
     * @psalm-mutation-free
     *
     * @noinspection PhpInconsistentReturnPointsInspection
     * @noinspection SenselessProxyMethodInspection - other phpdocs ;)
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
     *
     * @noinspection PhpDocSignatureInspection
     */
    public function addString(string ...$string): self
    {
        foreach ($string as $stringTmp) {
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
