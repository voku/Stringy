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
}
