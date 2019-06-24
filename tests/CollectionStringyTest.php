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
        $collection = S::collection(['fòôbàř', 'lall', 'öäü']);

        static::assertSame('fòôbàř+lall+öäü', $collection->implode('+'));
        static::assertSame('noop-fòôbàř-lall-öäü', $collection->prepend(new Stringy('noop'))->implode('-'));
        static::assertSame('noop-fòôbàř-lall-öäü-lall', $collection->append(S::collection('lall'))->implode('-'));

        $collectionTmp = S::collection();
        foreach ($collection->getGenerator() as $stringy) {
            $collectionTmp[] = $stringy->append('.');
        }

        static::assertSame('noop.+fòôbàř.+lall.+öäü.+lall.', $collectionTmp->implode('+'));
    }
}
