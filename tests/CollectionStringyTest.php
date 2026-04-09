<?php

use Stringy\StaticStringy as S;

require_once __DIR__ . '/../src/StaticStringy.php';

use Stringy\Stringy;

/**
 * @internal
 */
final class CollectionStringyTest extends \PHPUnit\Framework\TestCase
{
    public function testBasic()
    {
        $collection = \Stringy\collection(['fòôbàř', 'lall', 'öäü']);

        static::assertSame('fòôbàř+lall+öäü', $collection->implode('+'));
        static::assertSame('noop-fòôbàř-lall-öäü', $collection->prepend(new Stringy('noop'))->implode('-'));
        static::assertSame('noop-fòôbàř-lall-öäü-lall', $collection->append(S::collection('lall'))->implode('-'));

        $collectionTmp = S::collection();
        foreach ($collection->getGenerator() as $stringy) {
            $collectionTmp[] = $stringy->append('.');
        }

        static::assertSame('noop.+fòôbàř.+lall.+öäü.+lall.', $collectionTmp->implode('+'));

        $collection->addString('foo1', 'foo2');
        static::assertSame('noop+fòôbàř+lall+öäü+lall+foo1+foo2', $collection->implode('+'));

        $collection->addStringy(Stringy::create('foo_1'), Stringy::create('foo_2'));
        static::assertSame('noop+fòôbàř+lall+öäü+lall+foo1+foo2+foo_1+foo_2', $collection->implode('+'));
    }

    public function testFail()
    {
        $this->expectException(TypeError::class);
        $this->expectExceptionMessage('Invalid type: expected to be of type {Stringy\Stringy}, instead got value `1` with type {integer}.');

        S::collection(['fòôbàř', 'lall', 1]);
    }

    public function testCollectionFunctionWithSingleString()
    {
        // A single (non-array) string must be wrapped and produce a one-element collection.
        $collection = \Stringy\collection('hello');

        static::assertSame(1, $collection->count());
        static::assertSame('hello', $collection->implode(','));
    }

    public function testCollectionFunctionWithSingleStringy()
    {
        // A single Stringy object (non-array) must be accepted and produce a one-element collection.
        $collection = \Stringy\collection(Stringy::create('world'));

        static::assertSame(1, $collection->count());
        static::assertSame('world', $collection->implode(','));
    }

    public function testCollectionFunctionWithNull()
    {
        // null input must return an empty collection.
        $collection = \Stringy\collection(null);

        static::assertSame(0, $collection->count());
        static::assertSame('', $collection->implode(','));
    }

    public function testCreateFromStringsPreservesKeys()
    {
        // createFromStrings must preserve associative keys.
        $collection = \Stringy\CollectionStringy::createFromStrings(['a' => 'foo', 'b' => 'bar', 'c' => 'baz']);

        $all = $collection->getAll();
        static::assertArrayHasKey('a', $all);
        static::assertArrayHasKey('b', $all);
        static::assertArrayHasKey('c', $all);

        static::assertSame('foo', $all['a']->toString());
        static::assertSame('bar', $all['b']->toString());
        static::assertSame('baz', $all['c']->toString());
    }

    public function testCreateFromStringsReturnsStringyObjects()
    {
        $collection = \Stringy\CollectionStringy::createFromStrings(['hello', 'wörld']);

        foreach ($collection->getAll() as $item) {
            static::assertInstanceOf(Stringy::class, $item);
        }
    }

    public function testCreateFromStringsWithEmptyInput()
    {
        $collection = \Stringy\CollectionStringy::createFromStrings([]);

        static::assertSame(0, $collection->count());
    }
}
