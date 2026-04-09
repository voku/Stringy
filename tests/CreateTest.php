<?php

use Stringy\Stringy;

require_once __DIR__ . '/../src/Create.php';

/**
 * Class CreateTest
 *
 * @internal
 */
final class CreateTest extends \PHPUnit\Framework\TestCase
{
    public function testCreate()
    {
        $stringyObject = new Stringy();
        $stringy = $stringyObject::create('foo bar', 'UTF-8');
        static::assertInstanceOf('Stringy\Stringy', $stringy);
        static::assertSame('foo bar', (string) $stringy);
        static::assertSame('UTF-8', $stringy->getEncoding());
    }

    public function testCollectionFunctionWithArrayOfStrings()
    {
        $collection = \Stringy\collection(['foo', 'bar', 'baz']);

        static::assertInstanceOf(\Stringy\CollectionStringy::class, $collection);
        static::assertSame(3, $collection->count());
        static::assertSame('foo+bar+baz', $collection->implode('+'));
    }

    public function testCollectionFunctionWithSingleString()
    {
        $collection = \Stringy\collection('hello');

        static::assertInstanceOf(\Stringy\CollectionStringy::class, $collection);
        static::assertSame(1, $collection->count());
        static::assertSame('hello', $collection->implode(','));
    }

    public function testCollectionFunctionWithSingleStringy()
    {
        $collection = \Stringy\collection(Stringy::create('world'));

        static::assertInstanceOf(\Stringy\CollectionStringy::class, $collection);
        static::assertSame(1, $collection->count());
        static::assertSame('world', $collection->implode(','));
    }

    public function testCollectionFunctionWithNull()
    {
        $collection = \Stringy\collection(null);

        static::assertInstanceOf(\Stringy\CollectionStringy::class, $collection);
        static::assertSame(0, $collection->count());
    }

    public function testCollectionFunctionWithMixedArray()
    {
        $collection = \Stringy\collection(['foo', Stringy::create('bar')]);

        static::assertSame(2, $collection->count());
        static::assertSame('foo+bar', $collection->implode('+'));
    }
}
