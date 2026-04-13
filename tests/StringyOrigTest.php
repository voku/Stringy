<?php

use Stringy\Stringy as S;

/**
 * @internal
 */
final class StringyOrigTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Asserts that a variable is of a Stringy instance.
     *
     * @param mixed $actual
     */
    public function assertStringy($actual)
    {
        static::assertInstanceOf('Stringy\Stringy', $actual);
    }

    public function testConstruct()
    {
        $stringy = new S('foo bar', 'UTF-8');
        $this->assertStringy($stringy);
        static::assertEquals('foo bar', (string) $stringy);
        static::assertEquals('UTF-8', $stringy->getEncoding());
    }

    public function testEmptyConstruct()
    {
        $stringy = new S();
        $this->assertStringy($stringy);
        static::assertEquals('', (string) $stringy);
    }

    public function testConstructWithArray()
    {
        $this->expectException(\InvalidArgumentException::class);

        /** @noinspection PhpExpressionResultUnusedInspection */
        (string) new S([]);
        static::fail('Expecting exception when the constructor is passed an array');
    }

    public function testMissingToString()
    {
        $this->expectException(\InvalidArgumentException::class);

        /** @noinspection PhpExpressionResultUnusedInspection */
        (string) new S(new stdClass());
        static::fail(
            'Expecting exception when the constructor is passed an ' .
            'object without a __toString method'
        );
    }

    /**
     * @dataProvider toStringProvider()
     *
     * @param mixed $expected
     * @param mixed $str
     */
    public function testToString($expected, $str)
    {
        static::assertEquals($expected, (string) new S($str));
    }

    public function toStringProvider()
    {
        return [
            ['', null],
            ['', false],
            ['1', true],
            ['-9', -9],
            ['1.18', 1.18],
            [' string  ', ' string  '],
        ];
    }

    public function testCreate()
    {
        $stringy = S::create('foo bar', 'UTF-8');
        $this->assertStringy($stringy);
        static::assertEquals('foo bar', (string) $stringy);
        static::assertEquals('UTF-8', $stringy->getEncoding());
    }

    public function testChaining()
    {
        $stringy = S::create('Fòô     Bàř', 'UTF-8');
        $this->assertStringy($stringy);
        $result = $stringy->collapseWhitespace()->swapCase()->upperCaseFirst();
        static::assertEquals('FÒÔ bÀŘ', $result);
    }

    public function testCount()
    {
        $stringy = S::create('Fòô', 'UTF-8');
        static::assertEquals(3, $stringy->count());
        static::assertCount(3, $stringy);
    }

    public function testGetIterator()
    {
        $stringy = S::create('Fòô Bàř', 'UTF-8');

        $valResult = [];
        foreach ($stringy as $char) {
            $valResult[] = $char;
        }

        $keyValResult = [];
        foreach ($stringy as $pos => $char) {
            $keyValResult[$pos] = $char;
        }

        static::assertEquals(['F', 'ò', 'ô', ' ', 'B', 'à', 'ř'], $valResult);
        static::assertEquals(['F', 'ò', 'ô', ' ', 'B', 'à', 'ř'], $keyValResult);
    }

    /**
     * @dataProvider offsetExistsProvider()
     *
     * @param mixed $expected
     * @param mixed $offset
     */
    public function testOffsetExists($expected, $offset)
    {
        $stringy = S::create('fòô', 'UTF-8');
        static::assertEquals($expected, $stringy->offsetExists($offset));
        static::assertEquals($expected, isset($stringy[$offset]));
    }

    public function offsetExistsProvider()
    {
        return [
            [true, 0],
            [true, 2],
            [false, 3],
            [true, -1],
            [true, -3],
            [false, -4],
        ];
    }

    public function testOffsetGet()
    {
        $stringy = S::create('fòô', 'UTF-8');

        static::assertEquals('f', $stringy->offsetGet(0));
        static::assertEquals('ô', $stringy->offsetGet(2));

        static::assertEquals('ô', $stringy[2]);
    }

    public function testOffsetGetOutOfBounds()
    {
        $this->expectException(\OutOfBoundsException::class);

        $stringy = S::create('fòô', 'UTF-8');
        /** @noinspection PhpUnusedLocalVariableInspection */
        $test = $stringy[3];
    }

    public function testOffsetSet()
    {
        $this->expectException(\Exception::class);

        $stringy = S::create('fòô', 'UTF-8');
        $stringy[1] = 'invalid';
    }

    public function testOffsetUnset()
    {
        $this->expectException(\Exception::class);

        $stringy = S::create('fòô', 'UTF-8');
        unset($stringy[1]);
    }

    /**
     * @dataProvider indexOfProvider()
     *
     * @param mixed      $expected
     * @param mixed      $str
     * @param mixed      $subStr
     * @param mixed      $offset
     * @param mixed|null $encoding
     */
    public function testIndexOf($expected, $str, $subStr, $offset = 0, $encoding = null)
    {
        $result = S::create($str, $encoding)->indexOf($subStr, $offset);
        static::assertEquals($expected, $result);
    }

    public function indexOfProvider()
    {
        return [
            [3, 'string', 'ing'],
            [6, 'foo & bar', 'bar'],
            [6, 'foo & bar', 'bar', 0],
            [false, 'foo & bar', 'baz'],
            [false, 'foo & bar', 'baz', 0],
            [0, 'foo & bar & foo', 'foo', 0],
            [12, 'foo & bar & foo', 'foo', 5],
            [6, 'fòô & bàř', 'bàř', 0, 'UTF-8'],
            [false, 'fòô & bàř', 'baz', 0, 'UTF-8'],
            [0, 'fòô & bàř & fòô', 'fòô', 0, 'UTF-8'],
            [12, 'fòô & bàř & fòô', 'fòô', 5, 'UTF-8'],
        ];
    }

    /**
     * @dataProvider indexOfLastProvider()
     *
     * @param mixed      $expected
     * @param mixed      $str
     * @param mixed      $subStr
     * @param mixed      $offset
     * @param mixed|null $encoding
     */
    public function testIndexOfLast($expected, $str, $subStr, $offset = 0, $encoding = null)
    {
        $result = S::create($str, $encoding)->indexOfLast($subStr, $offset);
        static::assertEquals($expected, $result);
    }

    public function indexOfLastProvider()
    {
        return [
            [false, 'foo & Bar', 'bar'],
            [6, 'foo & bar', 'bar'],
            [6, 'foo & bar', 'bar', 0],
            [false, 'foo & bar', 'baz'],
            [false, 'foo & bar', 'baz', 0],
            [12, 'foo & bar & foo', 'foo', 0],
            [0, 'foo & bar & foo', 'foo', -5],
            [6, 'fòô & bàř', 'bàř', 0, 'UTF-8'],
            [false, 'fòô & bàř', 'baz', 0, 'UTF-8'],
            [12, 'fòô & bàř & fòô', 'fòô', 0, 'UTF-8'],
            [0, 'fòô & bàř & fòô', 'fòô', -5, 'UTF-8'],
        ];
    }

    /**
     * @dataProvider appendProvider()
     *
     * @param mixed      $expected
     * @param mixed      $str
     * @param mixed      $string
     * @param mixed|null $encoding
     */
    public function testAppend($expected, $str, $string, $encoding = null)
    {
        $result = S::create($str, $encoding)->append($string);
        $this->assertStringy($result);
        static::assertEquals($expected, $result);
    }

    public function appendProvider()
    {
        return [
            ['foobar', 'foo', 'bar'],
            ['fòôbàř', 'fòô', 'bàř', 'UTF-8'],
        ];
    }

    /**
     * @dataProvider prependProvider()
     *
     * @param mixed      $expected
     * @param mixed      $str
     * @param mixed      $string
     * @param mixed|null $encoding
     */
    public function testPrepend($expected, $str, $string, $encoding = null)
    {
        $result = S::create($str, $encoding)->prepend($string);
        $this->assertStringy($result);
        static::assertEquals($expected, $result);
    }

    public function prependProvider()
    {
        return [
            ['foobar', 'bar', 'foo'],
            ['fòôbàř', 'bàř', 'fòô', 'UTF-8'],
        ];
    }

    /**
     * @dataProvider charsProvider()
     *
     * @param mixed      $expected
     * @param mixed      $str
     * @param mixed|null $encoding
     */
    public function testChars($expected, $str, $encoding = null)
    {
        $result = S::create($str, $encoding)->chars();
        static::assertTrue(\is_array($result));
        foreach ($result as $char) {
            static::assertTrue(\is_string($char));
        }
        static::assertEquals($expected, $result);
    }

    public function charsProvider()
    {
        return [
            [[], ''],
            [['T', 'e', 's', 't'], 'Test'],
            [['F', 'ò', 'ô', ' ', 'B', 'à', 'ř'], 'Fòô Bàř', 'UTF-8'],
        ];
    }

    /**
     * @dataProvider linesProvider()
     *
     * @param mixed      $expected
     * @param mixed      $str
     * @param mixed|null $encoding
     */
    public function testLines($expected, $str, $encoding = null)
    {
        $result = S::create($str, $encoding)->linesCollection();

        static::assertInstanceOf(\Stringy\CollectionStringy::class, $result);
        foreach ($result as $line) {
            $this->assertStringy($line);
        }

        for ($i = 0, $iMax = \count($expected); $i < $iMax; ++$i) {
            static::assertEquals($expected[$i], $result[$i]);
        }
    }

    public function linesProvider()
    {
        return [
            [[], ''],
            [[''], "\r\n"],
            [['foo', 'bar'], "foo\nbar"],
            [['foo', 'bar'], "foo\rbar"],
            [['foo', 'bar'], "foo\r\nbar"],
            [['foo', '', 'bar'], "foo\r\n\r\nbar"],
            [['foo', 'bar', ''], "foo\r\nbar\r\n"],
            [['', 'foo', 'bar'], "\r\nfoo\r\nbar"],
            [['fòô', 'bàř'], "fòô\nbàř", 'UTF-8'],
            [['fòô', 'bàř'], "fòô\rbàř", 'UTF-8'],
            [['fòô', 'bàř'], "fòô\n\rbàř", 'UTF-8'],
            [['fòô', 'bàř'], "fòô\r\nbàř", 'UTF-8'],
            [['fòô', '', 'bàř'], "fòô\r\n\r\nbàř", 'UTF-8'],
            [['fòô', 'bàř', ''], "fòô\r\nbàř\r\n", 'UTF-8'],
            [['', 'fòô', 'bàř'], "\r\nfòô\r\nbàř", 'UTF-8'],
            [['1111111111111111111'], '1111111111111111111', 'UTF-8'],
            [['1111111111111111111111'], '1111111111111111111111', 'UTF-8'],
        ];
    }

    /**
     * @dataProvider upperCaseFirstProvider()
     *
     * @param mixed      $expected
     * @param mixed      $str
     * @param mixed|null $encoding
     */
    public function testUpperCaseFirst($expected, $str, $encoding = null)
    {
        $result = S::create($str, $encoding)->upperCaseFirst();
        $this->assertStringy($result);
        static::assertEquals($expected, $result);
    }

    public function upperCaseFirstProvider()
    {
        return [
            ['Test', 'Test'],
            ['Test', 'test'],
            ['1a', '1a'],
            ['Σ test', 'σ test', 'UTF-8'],
            [' σ test', ' σ test', 'UTF-8'],
        ];
    }

    /**
     * @dataProvider lowerCaseFirstProvider()
     *
     * @param mixed      $expected
     * @param mixed      $str
     * @param mixed|null $encoding
     */
    public function testLowerCaseFirst($expected, $str, $encoding = null)
    {
        $stringy = S::create($str, $encoding);
        $result = $stringy->lowerCaseFirst();
        $this->assertStringy($result);
        static::assertEquals($expected, $result);
        static::assertEquals($str, $stringy);
    }

    public function lowerCaseFirstProvider()
    {
        return [
            ['test', 'Test'],
            ['test', 'test'],
            ['1a', '1a'],
            ['σ test', 'Σ test', 'UTF-8'],
            [' Σ test', ' Σ test', 'UTF-8'],
        ];
    }

    /**
     * @dataProvider camelizeProvider()
     *
     * @param mixed      $expected
     * @param mixed      $str
     * @param mixed|null $encoding
     */
    public function testCamelize($expected, $str, $encoding = null)
    {
        $stringy = S::create($str, $encoding);
        $result = $stringy->camelize();
        $this->assertStringy($result);
        static::assertEquals($expected, $result);
        static::assertEquals($str, $stringy);
    }

    public function camelizeProvider()
    {
        return [
            ['camelCase', 'CamelCase'],
            ['camelCase', 'Camel-Case'],
            ['camelCase', 'camel case'],
            ['camelCase', 'camel -case'],
            ['camelCase', 'camel - case'],
            ['camelCase', 'camel_case'],
            ['camelCTest', 'camel c test'],
            ['stringWith1Number', 'string_with1number'],
            ['stringWith22Numbers', 'string-with-2-2 numbers'],
            ['dataRate', 'data_rate'],
            ['backgroundColor', 'background-color'],
            ['yesWeCan', 'yes_we_can'],
            ['mozSomething', '-moz-something'],
            ['carSpeed', '_car_speed_'],
            ['serveHTTP', 'ServeHTTP'],
            ['1Camel2Case', '1camel2case'],
            ['camelΣase', 'camel σase', 'UTF-8'],
            ['στανιλCase', 'Στανιλ case', 'UTF-8'],
            ['σamelCase', 'σamel  Case', 'UTF-8'],
            ['fooBar', 'FOO BAR'],
            ['fooBar', 'FOO_BAR'],
            ['fooBar', 'FOO-BAR'],
            ['fòôBàř', 'FÒÔ BÀŘ', 'UTF-8'],
            ['api2Url', 'API2URL'],
        ];
    }

    /**
     * @dataProvider upperCamelizeProvider()
     *
     * @param mixed      $expected
     * @param mixed      $str
     * @param mixed|null $encoding
     */
    public function testUpperCamelize($expected, $str, $encoding = null)
    {
        $stringy = S::create($str, $encoding);
        $result = $stringy->upperCamelize();
        $this->assertStringy($result);
        static::assertEquals($expected, $result);
        static::assertEquals($str, $stringy);
    }

    public function upperCamelizeProvider()
    {
        return [
            ['CamelCase', 'camelCase'],
            ['CamelCase', 'Camel-Case'],
            ['CamelCase', 'camel case'],
            ['CamelCase', 'camel -case'],
            ['CamelCase', 'camel - case'],
            ['CamelCase', 'camel_case'],
            ['CamelCTest', 'camel c test'],
            ['StringWith1Number', 'string_with1number'],
            ['StringWith22Numbers', 'string-with-2-2 numbers'],
            ['1Camel2Case', '1camel2case'],
            ['CamelΣase', 'camel σase', 'UTF-8'],
            ['ΣτανιλCase', 'στανιλ case', 'UTF-8'],
            ['ΣamelCase', 'Σamel  Case', 'UTF-8'],
            ['FooBar', 'FOO BAR'],
            ['FooBar', 'FOO_BAR'],
            ['FooBar', 'FOO-BAR'],
            ['FòôBàř', 'FÒÔ BÀŘ', 'UTF-8'],
            ['Api2Url', 'API2URL'],
        ];
    }

    /**
     * @dataProvider dasherizeProvider()
     *
     * @param mixed      $expected
     * @param mixed      $str
     * @param mixed|null $encoding
     */
    public function testDasherize($expected, $str, $encoding = null)
    {
        $stringy = S::create($str, $encoding);
        $result = $stringy->dasherize();
        $this->assertStringy($result);
        static::assertEquals($expected, $result);
        static::assertEquals($str, $stringy);
    }

    public function dasherizeProvider()
    {
        return [
            ['test-case', 'testCase'],
            ['test-case', 'Test-Case'],
            ['test-case', 'test case'],
            ['-test-case', '-test -case'],
            ['test-case', 'test - case'],
            ['test-case', 'test_case'],
            ['test-c-test', 'test c test'],
            ['test-d-case', 'TestDCase'],
            ['test-c-c-test', 'TestCCTest'],
            ['string-with1number', 'string_with1number'],
            ['string-with-2-2-numbers', 'String-with_2_2 numbers'],
            ['1test2case', '1test2case'],
            ['data-rate', 'dataRate'],
            ['car-speed', 'CarSpeed'],
            ['yes-we-can', 'yesWeCan'],
            ['background-color', 'backgroundColor'],
            ['dash-σase', 'dash Σase', 'UTF-8'],
            ['στανιλ-case', 'Στανιλ case', 'UTF-8'],
            ['σash-case', 'Σash  Case', 'UTF-8'],
        ];
    }

    /**
     * @dataProvider underscoredProvider()
     *
     * @param mixed      $expected
     * @param mixed      $str
     * @param mixed|null $encoding
     */
    public function testUnderscored($expected, $str, $encoding = null)
    {
        $stringy = S::create($str, $encoding);
        $result = $stringy->underscored();
        $this->assertStringy($result);
        static::assertEquals($expected, $result);
        static::assertEquals($str, $stringy);
    }

    public function underscoredProvider()
    {
        return [
            ['test_case', 'testCase'],
            ['test_case', 'Test-Case'],
            ['test_case', 'test case'],
            ['test_case', 'test -case'],
            ['_test_case', '-test - case'],
            ['test_case', 'test_case'],
            ['test_c_test', '  test c test'],
            ['test_u_case', 'TestUCase'],
            ['test_c_c_test', 'TestCCTest'],
            ['string_with1number', 'string_with1number'],
            ['string_with_2_2_numbers', 'String-with_2_2 numbers'],
            ['1test2case', '1test2case'],
            ['yes_we_can', 'yesWeCan'],
            ['test_σase', 'test Σase', 'UTF-8'],
            ['στανιλ_case', 'Στανιλ case', 'UTF-8'],
            ['σash_case', 'Σash  Case', 'UTF-8'],
        ];
    }

    /**
     * @dataProvider delimitProvider()
     *
     * @param mixed      $expected
     * @param mixed      $str
     * @param mixed      $delimiter
     * @param mixed|null $encoding
     */
    public function testDelimit($expected, $str, $delimiter, $encoding = null)
    {
        $stringy = S::create($str, $encoding);
        $result = $stringy->delimit($delimiter);
        $this->assertStringy($result);
        static::assertEquals($expected, $result);
        static::assertEquals($str, $stringy);
    }

    public function delimitProvider()
    {
        return [
            ['test*case', 'testCase', '*'],
            ['test&case', 'Test-Case', '&'],
            ['test#case', 'test case', '#'],
            ['test**case', 'test -case', '**'],
            ['~!~test~!~case', '-test - case', '~!~'],
            ['test*case', 'test_case', '*'],
            ['test%c%test', '  test c test', '%'],
            ['test+u+case', 'TestUCase', '+'],
            ['test=c=c=test', 'TestCCTest', '='],
            ['string#>with1number', 'string_with1number', '#>'],
            ['1test2case', '1test2case', '*'],
            ['test ύα σase', 'test Σase', ' ύα ', 'UTF-8'],
            ['στανιλαcase', 'Στανιλ case', 'α', 'UTF-8'],
            ['σashΘcase', 'Σash  Case', 'Θ', 'UTF-8'],
        ];
    }

    /**
     * @dataProvider swapCaseProvider()
     *
     * @param mixed      $expected
     * @param mixed      $str
     * @param mixed|null $encoding
     */
    public function testSwapCase($expected, $str, $encoding = null)
    {
        $stringy = S::create($str, $encoding);
        $result = $stringy->swapCase();
        $this->assertStringy($result);
        static::assertEquals($expected, $result);
        static::assertEquals($str, $stringy);
    }

    public function swapCaseProvider()
    {
        return [
            ['TESTcASE', 'testCase'],
            ['tEST-cASE', 'Test-Case'],
            [' - σASH  cASE', ' - Σash  Case', 'UTF-8'],
            ['νΤΑΝΙΛ', 'Ντανιλ', 'UTF-8'],
        ];
    }

    /**
     * @dataProvider titleizeProvider()
     *
     * @param mixed      $expected
     * @param mixed      $str
     * @param mixed|null $ignore
     * @param mixed|null $encoding
     */
    public function testTitleize(
        $expected,
        $str,
        $ignore = null,
        $encoding = null
    ) {
        $stringy = S::create($str, $encoding);
        $result = $stringy->titleize($ignore);
        $this->assertStringy($result);
        static::assertEquals($expected, $result);
        static::assertEquals($str, $stringy);
    }

    public function titleizeProvider()
    {
        $ignore = ['at', 'by', 'for', 'in', 'of', 'on', 'out', 'to', 'the'];

        return [
            ['Title Case', 'TITLE CASE'],
            ['Testing The Method', 'testing the method'],
            ['Testing the Method', 'testing the method', $ignore],
            [
                'I Like to Watch Dvds at Home',
                'i like to watch DVDs at home',
                $ignore,
            ],
            ['Θα Ήθελα Να Φύγει', '  Θα ήθελα να φύγει  ', null, 'UTF-8'],
        ];
    }

    /**
     * @dataProvider humanizeProvider()
     *
     * @param mixed      $expected
     * @param mixed      $str
     * @param mixed|null $encoding
     */
    public function testHumanize($expected, $str, $encoding = null)
    {
        $stringy = S::create($str, $encoding);
        $result = $stringy->humanize();
        $this->assertStringy($result);
        static::assertEquals($expected, $result);
        static::assertEquals($str, $stringy);
    }

    public function humanizeProvider()
    {
        return [
            ['Author', 'author_id'],
            ['Test user', ' _test_user_'],
            ['Συγγραφέας', ' συγγραφέας_id ', 'UTF-8'],
        ];
    }

    /**
     * @dataProvider tidyProvider()
     *
     * @param mixed $expected
     * @param mixed $str
     */
    public function testTidy($expected, $str)
    {
        $stringy = S::create($str);
        $result = $stringy->tidy();
        $this->assertStringy($result);
        static::assertEquals($expected, $result);
        static::assertEquals($str, $stringy);
    }

    public function tidyProvider()
    {
        return [
            ['"I see..."', '“I see…”'],
            ["'This too'", '‘This too’'],
            ['test-dash', 'test—dash'],
            ['Ο συγγραφέας είπε...', 'Ο συγγραφέας είπε…'],
        ];
    }

    /**
     * @dataProvider collapseWhitespaceProvider()
     *
     * @param mixed      $expected
     * @param mixed      $str
     * @param mixed|null $encoding
     */
    public function testCollapseWhitespace($expected, $str, $encoding = null)
    {
        $stringy = S::create($str, $encoding);
        $result = $stringy->collapseWhitespace();
        $this->assertStringy($result);
        static::assertEquals($expected, $result);
        static::assertEquals($str, $stringy);
    }

    public function collapseWhitespaceProvider()
    {
        return [
            ['foo bar', '  foo   bar  '],
            ['test string', 'test string'],
            ['Ο συγγραφέας', '   Ο     συγγραφέας  '],
            ['123', ' 123 '],
            ['', ' ', 'UTF-8'], // no-break space (U+00A0)
            ['', '           ', 'UTF-8'], // spaces U+2000 to U+200A
            ['', ' ', 'UTF-8'], // narrow no-break space (U+202F)
            ['', ' ', 'UTF-8'], // medium mathematical space (U+205F)
            ['', '　', 'UTF-8'], // ideographic space (U+3000)
            ['1 2 3', '  1  2  3　　', 'UTF-8'],
            ['', ' '],
            ['', ''],
        ];
    }

    /**
     * @dataProvider toAsciiProvider()
     *
     * @param mixed $expected
     * @param mixed $str
     * @param mixed $language
     * @param mixed $removeUnsupported
     */
    public function testToAscii(
        $expected,
        $str,
        $language = 'en',
        $removeUnsupported = true
    ) {
        $stringy = S::create($str);
        $result = $stringy->toAscii($language, $removeUnsupported);
        $this->assertStringy($result);
        static::assertEquals($expected, $result);
        static::assertEquals($str, $stringy);
    }

    public function toAsciiProvider()
    {
        return [
            ['foo bar', 'fòô bàř'],
            [' TEST ', ' ŤÉŚŢ '],
            ['f = z = 3', 'φ = ź = 3'],
            ['perevirka', 'перевірка'],
            ['lysaia gora', 'лысая гора'],
            ['user@host', 'user@host'],
            ['shhuka', 'щука'],
            ['', '漢字'],
            ['xin chao the gioi', 'xin chào thế giới'],
            ['XIN CHAO THE GIOI', 'XIN CHÀO THẾ GIỚI'],
            ['dam phat chet luon', 'đấm phát chết luôn'],
            [' ', ' '], // no-break space (U+00A0)
            ['           ', '           '], // spaces U+2000 to U+200A
            [' ', ' '], // narrow no-break space (U+202F)
            [' ', ' '], // medium mathematical space (U+205F)
            [' ', '　'], // ideographic space (U+3000)
            ['', '𐍉'], // some uncommon, unsupported character (U+10349)
            ['𐍉', '𐍉', 'en', false],
            ['aouAOU', 'äöüÄÖÜ'],
            ['aeoeueAeOeUe', 'äöüÄÖÜ', 'de'],
            ['aeoeueAeOeUe', 'äöüÄÖÜ', 'de_DE'],
            ['h H sht Sht a A  ', 'х Х щ Щ ъ Ъ ь Ь', 'bg'],
            ['h H sht Sht a A  ', 'х Х щ Щ ъ Ъ ь Ь', 'bg_BG'],
        ];
    }

    /**
     * @dataProvider padProvider()
     *
     * @param mixed      $expected
     * @param mixed      $str
     * @param mixed      $length
     * @param mixed      $padStr
     * @param mixed      $padType
     * @param mixed|null $encoding
     */
    public function testPad(
        $expected,
        $str,
        $length,
        $padStr = ' ',
        $padType = 'right',
        $encoding = null
    ) {
        $stringy = S::create($str, $encoding);
        $result = $stringy->pad($length, $padStr, $padType);
        $this->assertStringy($result);
        static::assertEquals($expected, $result);
        static::assertEquals($str, $stringy);
    }

    public function padProvider()
    {
        return [
            // length <= str
            ['foo bar', 'foo bar', -1],
            ['foo bar', 'foo bar', 7],
            ['fòô bàř', 'fòô bàř', 7, ' ', 'right', 'UTF-8'],

            // right
            ['foo bar  ', 'foo bar', 9],
            ['foo bar_*', 'foo bar', 9, '_*', 'right'],
            ['fòô bàř¬ø¬', 'fòô bàř', 10, '¬ø', 'right', 'UTF-8'],

            // left
            ['  foo bar', 'foo bar', 9, ' ', 'left'],
            ['_*foo bar', 'foo bar', 9, '_*', 'left'],
            ['¬ø¬fòô bàř', 'fòô bàř', 10, '¬ø', 'left', 'UTF-8'],

            // both
            ['foo bar ', 'foo bar', 8, ' ', 'both'],
            ['¬fòô bàř¬ø', 'fòô bàř', 10, '¬ø', 'both', 'UTF-8'],
            ['¬øfòô bàř¬øÿ', 'fòô bàř', 12, '¬øÿ', 'both', 'UTF-8'],
        ];
    }

    public function testPadException()
    {
        $this->expectException(\InvalidArgumentException::class);

        $stringy = S::create('foo');
        $stringy->pad(5, 'foo', 'bar');
    }

    /**
     * @dataProvider padLeftProvider()
     *
     * @param mixed      $expected
     * @param mixed      $str
     * @param mixed      $length
     * @param mixed      $padStr
     * @param mixed|null $encoding
     */
    public function testPadLeft(
        $expected,
        $str,
        $length,
        $padStr = ' ',
        $encoding = null
    ) {
        $stringy = S::create($str, $encoding);
        $result = $stringy->padLeft($length, $padStr);
        $this->assertStringy($result);
        static::assertEquals($expected, $result);
        static::assertEquals($str, $stringy);
    }

    public function padLeftProvider()
    {
        return [
            ['  foo bar', 'foo bar', 9],
            ['_*foo bar', 'foo bar', 9, '_*'],
            ['_*_foo bar', 'foo bar', 10, '_*'],
            ['  fòô bàř', 'fòô bàř', 9, ' ', 'UTF-8'],
            ['¬øfòô bàř', 'fòô bàř', 9, '¬ø', 'UTF-8'],
            ['¬ø¬fòô bàř', 'fòô bàř', 10, '¬ø', 'UTF-8'],
            ['¬ø¬øfòô bàř', 'fòô bàř', 11, '¬ø', 'UTF-8'],
        ];
    }

    /**
     * @dataProvider padRightProvider()
     *
     * @param mixed      $expected
     * @param mixed      $str
     * @param mixed      $length
     * @param mixed      $padStr
     * @param mixed|null $encoding
     */
    public function testPadRight(
        $expected,
        $str,
        $length,
        $padStr = ' ',
        $encoding = null
    ) {
        $stringy = S::create($str, $encoding);
        $result = $stringy->padRight($length, $padStr);
        $this->assertStringy($result);
        static::assertEquals($expected, $result);
        static::assertEquals($str, $stringy);
    }

    public function padRightProvider()
    {
        return [
            ['foo bar  ', 'foo bar', 9],
            ['foo bar_*', 'foo bar', 9, '_*'],
            ['foo bar_*_', 'foo bar', 10, '_*'],
            ['fòô bàř  ', 'fòô bàř', 9, ' ', 'UTF-8'],
            ['fòô bàř¬ø', 'fòô bàř', 9, '¬ø', 'UTF-8'],
            ['fòô bàř¬ø¬', 'fòô bàř', 10, '¬ø', 'UTF-8'],
            ['fòô bàř¬ø¬ø', 'fòô bàř', 11, '¬ø', 'UTF-8'],
        ];
    }

    /**
     * @dataProvider padBothProvider()
     *
     * @param mixed      $expected
     * @param mixed      $str
     * @param mixed      $length
     * @param mixed      $padStr
     * @param mixed|null $encoding
     */
    public function testPadBoth(
        $expected,
        $str,
        $length,
        $padStr = ' ',
        $encoding = null
    ) {
        $stringy = S::create($str, $encoding);
        $result = $stringy->padBoth($length, $padStr);
        $this->assertStringy($result);
        static::assertEquals($expected, $result);
        static::assertEquals($str, $stringy);
    }

    public function padBothProvider()
    {
        return [
            ['foo bar ', 'foo bar', 8],
            [' foo bar ', 'foo bar', 9, ' '],
            ['fòô bàř ', 'fòô bàř', 8, ' ', 'UTF-8'],
            [' fòô bàř ', 'fòô bàř', 9, ' ', 'UTF-8'],
            ['fòô bàř¬', 'fòô bàř', 8, '¬ø', 'UTF-8'],
            ['¬fòô bàř¬', 'fòô bàř', 9, '¬ø', 'UTF-8'],
            ['¬fòô bàř¬ø', 'fòô bàř', 10, '¬ø', 'UTF-8'],
            ['¬øfòô bàř¬ø', 'fòô bàř', 11, '¬ø', 'UTF-8'],
            ['¬fòô bàř¬ø', 'fòô bàř', 10, '¬øÿ', 'UTF-8'],
            ['¬øfòô bàř¬ø', 'fòô bàř', 11, '¬øÿ', 'UTF-8'],
            ['¬øfòô bàř¬øÿ', 'fòô bàř', 12, '¬øÿ', 'UTF-8'],
        ];
    }

    /**
     * @dataProvider startsWithProvider()
     *
     * @param mixed      $expected
     * @param mixed      $str
     * @param mixed      $substring
     * @param mixed      $caseSensitive
     * @param mixed|null $encoding
     */
    public function testStartsWith(
        $expected,
        $str,
        $substring,
        $caseSensitive = true,
        $encoding = null
    ) {
        $stringy = S::create($str, $encoding);
        $result = $stringy->startsWith($substring, $caseSensitive);
        static::assertTrue(\is_bool($result));
        static::assertEquals($expected, $result);
        static::assertEquals($str, $stringy);
    }

    public function startsWithProvider()
    {
        return [
            [true, 'foo bars', 'foo bar'],
            [true, 'FOO bars', 'foo bar', false],
            [true, 'FOO bars', 'foo BAR', false],
            [true, 'FÒÔ bàřs', 'fòô bàř', false, 'UTF-8'],
            [true, 'fòô bàřs', 'fòô BÀŘ', false, 'UTF-8'],
            [false, 'foo bar', 'bar'],
            [false, 'foo bar', 'foo bars'],
            [false, 'FOO bar', 'foo bars'],
            [false, 'FOO bars', 'foo BAR'],
            [false, 'FÒÔ bàřs', 'fòô bàř', true, 'UTF-8'],
            [false, 'fòô bàřs', 'fòô BÀŘ', true, 'UTF-8'],
        ];
    }

    /**
     * @dataProvider startsWithProviderAny()
     *
     * @param mixed      $expected
     * @param mixed      $str
     * @param mixed      $substrings
     * @param mixed      $caseSensitive
     * @param mixed|null $encoding
     */
    public function testStartsWithAny(
        $expected,
        $str,
        $substrings,
        $caseSensitive = true,
        $encoding = null
    ) {
        $stringy = S::create($str, $encoding);
        $result = $stringy->startsWithAny($substrings, $caseSensitive);
        static::assertTrue(\is_bool($result));
        static::assertEquals($expected, $result);
        static::assertEquals($str, $stringy);
    }

    public function startsWithProviderAny()
    {
        return [
            [true, 'foo bars', ['foo bar']],
            [true, 'FOO bars', ['foo bar'], false],
            [true, 'FOO bars', ['foo bar', 'foo BAR'], false],
            [true, 'FÒÔ bàřs', ['foo bar', 'fòô bàř'], false, 'UTF-8'],
            [true, 'fòô bàřs', ['foo bar', 'fòô BÀŘ'], false, 'UTF-8'],
            [false, 'foo bar', ['bar']],
            [false, 'foo bar', ['foo bars']],
            [false, 'FOO bar', ['foo bars']],
            [false, 'FOO bars', ['foo BAR']],
            [false, 'FÒÔ bàřs', ['fòô bàř'], true, 'UTF-8'],
            [false, 'fòô bàřs', ['fòô BÀŘ'], true, 'UTF-8'],
        ];
    }

    /**
     * @dataProvider endsWithProvider()
     *
     * @param mixed      $expected
     * @param mixed      $str
     * @param mixed      $substring
     * @param mixed      $caseSensitive
     * @param mixed|null $encoding
     */
    public function testEndsWith(
        $expected,
        $str,
        $substring,
        $caseSensitive = true,
        $encoding = null
    ) {
        $stringy = S::create($str, $encoding);
        $result = $stringy->endsWith($substring, $caseSensitive);
        static::assertTrue(\is_bool($result));
        static::assertEquals($expected, $result);
        static::assertEquals($str, $stringy);
    }

    public function endsWithProvider()
    {
        return [
            [true, 'foo bars', 'o bars'],
            [true, 'FOO bars', 'o bars', false],
            [true, 'FOO bars', 'o BARs', false],
            [true, 'FÒÔ bàřs', 'ô bàřs', false, 'UTF-8'],
            [true, 'fòô bàřs', 'ô BÀŘs', false, 'UTF-8'],
            [false, 'foo bar', 'foo'],
            [false, 'foo bar', 'foo bars'],
            [false, 'FOO bar', 'foo bars'],
            [false, 'FOO bars', 'foo BARS'],
            [false, 'FÒÔ bàřs', 'fòô bàřs', true, 'UTF-8'],
            [false, 'fòô bàřs', 'fòô BÀŘS', true, 'UTF-8'],
        ];
    }

    /**
     * @dataProvider endsWithAnyProvider()
     *
     * @param mixed      $expected
     * @param mixed      $str
     * @param mixed      $substrings
     * @param mixed      $caseSensitive
     * @param mixed|null $encoding
     */
    public function testEndsWithAny(
        $expected,
        $str,
        $substrings,
        $caseSensitive = true,
        $encoding = null
    ) {
        $stringy = S::create($str, $encoding);
        $result = $stringy->endsWithAny($substrings, $caseSensitive);
        static::assertTrue(\is_bool($result));
        static::assertEquals($expected, $result);
        static::assertEquals($str, $stringy);
    }

    public function endsWithAnyProvider()
    {
        return [
            [true, 'foo bars', ['foo', 'o bars']],
            [true, 'FOO bars', ['foo', 'o bars'], false],
            [true, 'FOO bars', ['foo', 'o BARs'], false],
            [true, 'FÒÔ bàřs', ['foo', 'ô bàřs'], false, 'UTF-8'],
            [true, 'fòô bàřs', ['foo', 'ô BÀŘs'], false, 'UTF-8'],
            [false, 'foo bar', ['foo']],
            [false, 'foo bar', ['foo', 'foo bars']],
            [false, 'FOO bar', ['foo', 'foo bars']],
            [false, 'FOO bars', ['foo', 'foo BARS']],
            [false, 'FÒÔ bàřs', ['fòô', 'fòô bàřs'], true, 'UTF-8'],
            [false, 'fòô bàřs', ['fòô', 'fòô BÀŘS'], true, 'UTF-8'],
        ];
    }

    /**
     * @dataProvider toBooleanProvider()
     *
     * @param mixed      $expected
     * @param mixed      $str
     * @param mixed|null $encoding
     */
    public function testToBoolean($expected, $str, $encoding = null)
    {
        $stringy = S::create($str, $encoding);
        $result = $stringy->toBoolean();
        static::assertTrue(\is_bool($result));
        static::assertEquals($expected, $result);
        static::assertEquals($str, $stringy);
    }

    public function toBooleanProvider()
    {
        return [
            [true, 'true'],
            [true, '1'],
            [true, 'on'],
            [true, 'ON'],
            [true, 'yes'],
            [true, '999'],
            [false, 'false'],
            [false, '0'],
            [false, 'off'],
            [false, 'OFF'],
            [false, 'no'],
            [false, '-999'],
            [false, ''],
            [false, ' '],
            [false, '  ', 'UTF-8'], // narrow no-break space (U+202F)
        ];
    }

    /**
     * @dataProvider toSpacesProvider()
     *
     * @param mixed $expected
     * @param mixed $str
     * @param mixed $tabLength
     */
    public function testToSpaces($expected, $str, $tabLength = 4)
    {
        $stringy = S::create($str);
        $result = $stringy->toSpaces($tabLength);
        $this->assertStringy($result);
        static::assertEquals($expected, $result);
        static::assertEquals($str, $stringy);
    }

    public function toSpacesProvider()
    {
        return [
            ['    foo    bar    ', '	foo	bar	'],
            ['     foo     bar     ', '	foo	bar	', 5],
            ['    foo  bar  ', '		foo	bar	', 2],
            ['foobar', '	foo	bar	', 0],
            ["    foo\n    bar", "	foo\n	bar"],
            ["    fòô\n    bàř", "	fòô\n	bàř"],
        ];
    }

    /**
     * @dataProvider toTabsProvider()
     *
     * @param mixed $expected
     * @param mixed $str
     * @param mixed $tabLength
     */
    public function testToTabs($expected, $str, $tabLength = 4)
    {
        $stringy = S::create($str);
        $result = $stringy->toTabs($tabLength);
        $this->assertStringy($result);
        static::assertEquals($expected, $result);
        static::assertEquals($str, $stringy);
    }

    public function toTabsProvider()
    {
        return [
            ['	foo	bar	', '    foo    bar    '],
            ['	foo	bar	', '     foo     bar     ', 5],
            ['		foo	bar	', '    foo  bar  ', 2],
            ["	foo\n	bar", "    foo\n    bar"],
            ["	fòô\n	bàř", "    fòô\n    bàř"],
        ];
    }

    /**
     * @dataProvider toLowerCaseProvider()
     *
     * @param mixed      $expected
     * @param mixed      $str
     * @param mixed|null $encoding
     */
    public function testToLowerCase($expected, $str, $encoding = null)
    {
        $stringy = S::create($str, $encoding);
        $result = $stringy->toLowerCase();
        $this->assertStringy($result);
        static::assertEquals($expected, $result);
        static::assertEquals($str, $stringy);
    }

    public function toLowerCaseProvider()
    {
        return [
            ['foo bar', 'FOO BAR'],
            [' foo_bar ', ' FOO_bar '],
            ['fòô bàř', 'FÒÔ BÀŘ', 'UTF-8'],
            [' fòô_bàř ', ' FÒÔ_bàř ', 'UTF-8'],
            ['αυτοκίνητο', 'ΑΥΤΟΚΊΝΗΤΟ', 'UTF-8'],
        ];
    }

    /**
     * @dataProvider toTitleCaseProvider()
     *
     * @param mixed      $expected
     * @param mixed      $str
     * @param mixed|null $encoding
     */
    public function testToTitleCase($expected, $str, $encoding = null)
    {
        $stringy = S::create($str, $encoding);
        $result = $stringy->toTitleCase();
        $this->assertStringy($result);
        static::assertEquals($expected, $result);
        static::assertEquals($str, $stringy);
    }

    public function toTitleCaseProvider()
    {
        return [
            ['Foo Bar', 'foo bar'],
            [' Foo_Bar ', ' foo_bar '],
            ['Fòô Bàř', 'fòô bàř', 'UTF-8'],
            [' Fòô_Bàř ', ' fòô_bàř ', 'UTF-8'],
            ['Αυτοκίνητο Αυτοκίνητο', 'αυτοκίνητο αυτοκίνητο', 'UTF-8'],
        ];
    }

    /**
     * @dataProvider toUpperCaseProvider()
     *
     * @param mixed      $expected
     * @param mixed      $str
     * @param mixed|null $encoding
     */
    public function testToUpperCase($expected, $str, $encoding = null)
    {
        $stringy = S::create($str, $encoding);
        $result = $stringy->toUpperCase();
        $this->assertStringy($result);
        static::assertEquals($expected, $result);
        static::assertEquals($str, $stringy);
    }

    public function toUpperCaseProvider()
    {
        return [
            ['FOO BAR', 'foo bar'],
            [' FOO_BAR ', ' FOO_bar '],
            ['FÒÔ BÀŘ', 'fòô bàř', 'UTF-8'],
            [' FÒÔ_BÀŘ ', ' FÒÔ_bàř ', 'UTF-8'],
            ['ΑΥΤΟΚΊΝΗΤΟ', 'αυτοκίνητο', 'UTF-8'],
        ];
    }

    /**
     * @dataProvider slugifyProvider()
     *
     * @param mixed $expected
     * @param mixed $str
     * @param mixed $replacement
     */
    public function testSlugify($expected, $str, $replacement = '-')
    {
        $stringy = S::create($str);
        $result = $stringy->slugify($replacement);
        $this->assertStringy($result);
        static::assertEquals($expected, $result);
        static::assertEquals($str, $stringy);
    }

    public function testSlugifyWithReplacement()
    {
        $text = 'Texttile/Machine Learning';
        $expected = 'texttile-machine-learning';

        $actual = s::create($text)->slugify('-', 'en', ['/' => '-'])->toString();

        static::assertEquals($expected, $actual);
    }

    public function slugifyProvider()
    {
        return [
            ['foo-bar', ' foo  bar '],
            ['foo-bar', 'foo -.-"-...bar'],
            ['another-and-foo-bar', 'another..& foo -.-"-...bar'],
            ['foo-dbar', " Foo d'Bar "],
            ['a-string-with-dashes', 'A string-with-dashes'],
            ['user-at-host', 'user@host'],
            ['using-strings-like-foo-bar', 'Using strings like fòô bàř'],
            ['numbers-1234', 'numbers 1234'],
            ['perevirka-riadka', 'перевірка рядка'],
            ['bukvar-s-bukvoi-y', 'букварь с буквой ы'],
            ['podieexal-k-podieezdu-moego-doma', 'подъехал к подъезду моего дома'],
            ['foo:bar:baz', 'Foo bar baz', ':'],
            ['a_string_with_underscores', 'A_string with_underscores', '_'],
            ['a_string_with_dashes', 'A string-with-dashes', '_'],
            ['a\string\with\dashes', 'A string-with-dashes', '\\'],
            ['an_odd_string', '--   An odd__   string-_', '_'],
        ];
    }

    public function testCharsArray()
    {
        $charsArray = self::getMethod('charsArray');
        $obj = new S();
        $array = $charsArray->invoke($obj);

        static::assertSame(['य'], $array['Ya']);
    }

    /**
     * @dataProvider betweenProvider()
     *
     * @param mixed      $expected
     * @param mixed      $str
     * @param mixed      $start
     * @param mixed      $end
     * @param mixed|null $offset
     * @param mixed|null $encoding
     */
    public function testBetween(
        $expected,
        $str,
        $start,
        $end,
        $offset = null,
        $encoding = null
    ) {
        $stringy = S::create($str, $encoding);
        $result = $stringy->between($start, $end, $offset);
        $this->assertStringy($result);
        static::assertEquals($expected, $result);
        static::assertEquals($str, $stringy);
    }

    public function betweenProvider()
    {
        return [
            ['', 'foo', '{', '}'],
            ['', '{foo', '{', '}'],
            ['foo', '{foo}', '{', '}'],
            ['{foo', '{{foo}', '{', '}'],
            ['', '{}foo}', '{', '}'],
            ['foo', '}{foo}', '{', '}'],
            ['foo', 'A description of {foo} goes here', '{', '}'],
            ['bar', '{foo} and {bar}', '{', '}', 1],
            ['', 'fòô', '{', '}', 0, 'UTF-8'],
            ['', '{fòô', '{', '}', 0, 'UTF-8'],
            ['fòô', '{fòô}', '{', '}', 0, 'UTF-8'],
            ['{fòô', '{{fòô}', '{', '}', 0, 'UTF-8'],
            ['', '{}fòô}', '{', '}', 0, 'UTF-8'],
            ['fòô', '}{fòô}', '{', '}', 0, 'UTF-8'],
            ['fòô', 'A description of {fòô} goes here', '{', '}', 0, 'UTF-8'],
            ['bàř', '{fòô} and {bàř}', '{', '}', 1, 'UTF-8'],
        ];
    }

    /**
     * @dataProvider containsProvider()
     *
     * @param mixed      $expected
     * @param mixed      $haystack
     * @param mixed      $needle
     * @param mixed      $caseSensitive
     * @param mixed|null $encoding
     */
    public function testContains(
        $expected,
        $haystack,
        $needle,
        $caseSensitive = true,
        $encoding = null
    ) {
        $stringy = S::create($haystack, $encoding);
        $result = $stringy->contains($needle, $caseSensitive);
        static::assertTrue(\is_bool($result));
        static::assertEquals($expected, $result);
        static::assertEquals($haystack, $stringy);
    }

    public function containsProvider()
    {
        return [
            [true, 'Str contains foo bar', 'foo bar'],
            [true, '12398!@(*%!@# @!%#*&^%', ' @!%#*&^%'],
            [true, 'Ο συγγραφέας είπε', 'συγγραφέας', 'UTF-8'],
            [true, 'å´¥©¨ˆßå˚ ∆∂˙©å∑¥øœ¬', 'å´¥©', true, 'UTF-8'],
            [true, 'å´¥©¨ˆßå˚ ∆∂˙©å∑¥øœ¬', 'å˚ ∆', true, 'UTF-8'],
            [true, 'å´¥©¨ˆßå˚ ∆∂˙©å∑¥øœ¬', 'øœ¬', true, 'UTF-8'],
            [false, 'Str contains foo bar', 'Foo bar'],
            [false, 'Str contains foo bar', 'foobar'],
            [false, 'Str contains foo bar', 'foo bar '],
            [false, 'Ο συγγραφέας είπε', '  συγγραφέας ', true, 'UTF-8'],
            [false, 'å´¥©¨ˆßå˚ ∆∂˙©å∑¥øœ¬', ' ßå˚', true, 'UTF-8'],
            [true, 'Str contains foo bar', 'Foo bar', false],
            [true, '12398!@(*%!@# @!%#*&^%', ' @!%#*&^%', false],
            [true, 'Ο συγγραφέας είπε', 'ΣΥΓΓΡΑΦΈΑΣ', false, 'UTF-8'],
            [true, 'å´¥©¨ˆßå˚ ∆∂˙©å∑¥øœ¬', 'Å´¥©', false, 'UTF-8'],
            [true, 'å´¥©¨ˆßå˚ ∆∂˙©å∑¥øœ¬', 'Å˚ ∆', false, 'UTF-8'],
            [true, 'å´¥©¨ˆßå˚ ∆∂˙©å∑¥øœ¬', 'ØŒ¬', false, 'UTF-8'],
            [false, 'Str contains foo bar', 'foobar', false],
            [false, 'Str contains foo bar', 'foo bar ', false],
            [false, 'Ο συγγραφέας είπε', '  συγγραφέας ', false, 'UTF-8'],
            [false, 'å´¥©¨ˆßå˚ ∆∂˙©å∑¥øœ¬', ' ßÅ˚', false, 'UTF-8'],
        ];
    }

    /**
     * @dataProvider containsAnyProvider()
     *
     * @param mixed      $expected
     * @param mixed      $haystack
     * @param mixed      $needles
     * @param mixed      $caseSensitive
     * @param mixed|null $encoding
     */
    public function testTestcontainsAny(
        $expected,
        $haystack,
        $needles,
        $caseSensitive = true,
        $encoding = null
    ) {
        $stringy = S::create($haystack, $encoding);
        $result = $stringy->containsAny($needles, $caseSensitive);
        static::assertTrue(\is_bool($result));
        static::assertEquals($expected, $result);
        static::assertEquals($haystack, $stringy);
    }

    public function containsAnyProvider()
    {
        // One needle
        $singleNeedle = \array_map(
            static function ($array) {
                $array[2] = [$array[2]];

                return $array;
            },
            $this->containsProvider()
        );

        $provider = [
            // No needles
            [false, 'Str contains foo bar', []],
            // Multiple needles
            [true, 'Str contains foo bar', ['foo', 'bar']],
            [true, '12398!@(*%!@# @!%#*&^%', [' @!%#*', '&^%']],
            [true, 'Ο συγγραφέας είπε', ['συγγρ', 'αφέας'], 'UTF-8'],
            [true, 'å´¥©¨ˆßå˚ ∆∂˙©å∑¥øœ¬', ['å´¥', '©'], true, 'UTF-8'],
            [true, 'å´¥©¨ˆßå˚ ∆∂˙©å∑¥øœ¬', ['å˚ ', '∆'], true, 'UTF-8'],
            [true, 'å´¥©¨ˆßå˚ ∆∂˙©å∑¥øœ¬', ['øœ', '¬'], true, 'UTF-8'],
            [false, 'Str contains foo bar', ['Foo', 'Bar']],
            [false, 'Str contains foo bar', ['foobar', 'bar ']],
            [false, 'Str contains foo bar', ['foo bar ', '  foo']],
            [false, 'Ο συγγραφέας είπε', ['  συγγραφέας ', '  συγγραφ '], true, 'UTF-8'],
            [false, 'å´¥©¨ˆßå˚ ∆∂˙©å∑¥øœ¬', [' ßå˚', ' ß '], true, 'UTF-8'],
            [true, 'Str contains foo bar', ['Foo bar', 'bar'], false],
            [true, '12398!@(*%!@# @!%#*&^%', [' @!%#*&^%', '*&^%'], false],
            [true, 'Ο συγγραφέας είπε', ['ΣΥΓΓΡΑΦΈΑΣ', 'ΑΦΈΑ'], false, 'UTF-8'],
            [true, 'å´¥©¨ˆßå˚ ∆∂˙©å∑¥øœ¬', ['Å´¥©', '¥©'], false, 'UTF-8'],
            [true, 'å´¥©¨ˆßå˚ ∆∂˙©å∑¥øœ¬', ['Å˚ ∆', ' ∆'], false, 'UTF-8'],
            [true, 'å´¥©¨ˆßå˚ ∆∂˙©å∑¥øœ¬', ['ØŒ¬', 'Œ'], false, 'UTF-8'],
            [false, 'Str contains foo bar', ['foobar', 'none'], false],
            [false, 'Str contains foo bar', ['foo bar ', ' ba '], false],
            [false, 'Ο συγγραφέας είπε', ['  συγγραφέας ', ' ραφέ '], false, 'UTF-8'],
            [false, 'å´¥©¨ˆßå˚ ∆∂˙©å∑¥øœ¬', [' ßÅ˚', ' Å˚ '], false, 'UTF-8'],
        ];

        return \array_merge($singleNeedle, $provider);
    }

    /**
     * @dataProvider containsAllProvider()
     *
     * @param mixed      $expected
     * @param mixed      $haystack
     * @param mixed      $needles
     * @param mixed      $caseSensitive
     * @param mixed|null $encoding
     */
    public function testContainsAll(
        $expected,
        $haystack,
        $needles,
        $caseSensitive = true,
        $encoding = null
    ) {
        $stringy = S::create($haystack, $encoding);
        $result = $stringy->containsAll($needles, $caseSensitive);
        static::assertTrue(\is_bool($result));
        static::assertEquals($expected, $result);
        static::assertEquals($haystack, $stringy);
    }

    public function containsAllProvider()
    {
        // One needle
        $singleNeedle = \array_map(
            static function ($array) {
                $array[2] = [$array[2]];

                return $array;
            },
            $this->containsProvider()
        );

        $provider = [
            // One needle
            [false, 'Str contains foo bar', []],
            // Multiple needles
            [true, 'Str contains foo bar', ['foo', 'bar']],
            [true, '12398!@(*%!@# @!%#*&^%', [' @!%#*', '&^%']],
            [true, 'Ο συγγραφέας είπε', ['συγγρ', 'αφέας'], 'UTF-8'],
            [true, 'å´¥©¨ˆßå˚ ∆∂˙©å∑¥øœ¬', ['å´¥', '©'], true, 'UTF-8'],
            [true, 'å´¥©¨ˆßå˚ ∆∂˙©å∑¥øœ¬', ['å˚ ', '∆'], true, 'UTF-8'],
            [true, 'å´¥©¨ˆßå˚ ∆∂˙©å∑¥øœ¬', ['øœ', '¬'], true, 'UTF-8'],
            [false, 'Str contains foo bar', ['Foo', 'bar']],
            [false, 'Str contains foo bar', ['foobar', 'bar']],
            [false, 'Str contains foo bar', ['foo bar ', 'bar']],
            [false, 'Ο συγγραφέας είπε', ['  συγγραφέας ', '  συγγραφ '], true, 'UTF-8'],
            [false, 'å´¥©¨ˆßå˚ ∆∂˙©å∑¥øœ¬', [' ßå˚', ' ß '], true, 'UTF-8'],
            [true, 'Str contains foo bar', ['Foo bar', 'bar'], false],
            [true, '12398!@(*%!@# @!%#*&^%', [' @!%#*&^%', '*&^%'], false],
            [true, 'Ο συγγραφέας είπε', ['ΣΥΓΓΡΑΦΈΑΣ', 'ΑΦΈΑ'], false, 'UTF-8'],
            [true, 'å´¥©¨ˆßå˚ ∆∂˙©å∑¥øœ¬', ['Å´¥©', '¥©'], false, 'UTF-8'],
            [true, 'å´¥©¨ˆßå˚ ∆∂˙©å∑¥øœ¬', ['Å˚ ∆', ' ∆'], false, 'UTF-8'],
            [true, 'å´¥©¨ˆßå˚ ∆∂˙©å∑¥øœ¬', ['ØŒ¬', 'Œ'], false, 'UTF-8'],
            [false, 'Str contains foo bar', ['foobar', 'none'], false],
            [false, 'Str contains foo bar', ['foo bar ', ' ba'], false],
            [false, 'Ο συγγραφέας είπε', ['  συγγραφέας ', ' ραφέ '], false, 'UTF-8'],
            [false, 'å´¥©¨ˆßå˚ ∆∂˙©å∑¥øœ¬', [' ßÅ˚', ' Å˚ '], false, 'UTF-8'],
        ];

        return \array_merge($singleNeedle, $provider);
    }

    /**
     * @dataProvider surroundProvider()
     *
     * @param mixed $expected
     * @param mixed $str
     * @param mixed $substring
     */
    public function testSurround($expected, $str, $substring)
    {
        $stringy = S::create($str);
        $result = $stringy->surround($substring);
        $this->assertStringy($result);
        static::assertEquals($expected, $result);
        static::assertEquals($str, $stringy);
    }

    public function surroundProvider()
    {
        return [
            ['__foobar__', 'foobar', '__'],
            ['test', 'test', ''],
            ['**', '', '*'],
            ['¬fòô bàř¬', 'fòô bàř', '¬'],
            ['ßå∆˚ test ßå∆˚', ' test ', 'ßå∆˚'],
        ];
    }

    /**
     * @dataProvider insertProvider()
     *
     * @param mixed      $expected
     * @param mixed      $str
     * @param mixed      $substring
     * @param mixed      $index
     * @param mixed|null $encoding
     */
    public function testInsert(
        $expected,
        $str,
        $substring,
        $index,
        $encoding = null
    ) {
        $stringy = S::create($str, $encoding);
        $result = $stringy->insert($substring, $index);
        $this->assertStringy($result);
        static::assertEquals($expected, $result);
        static::assertEquals($str, $stringy);
    }

    public function insertProvider()
    {
        return [
            ['foo bar', 'oo bar', 'f', 0],
            ['foo bar', 'f bar', 'oo', 1],
            ['f bar', 'f bar', 'oo', 20],
            ['foo bar', 'foo ba', 'r', 6],
            ['fòôbàř', 'fòôbř', 'à', 4, 'UTF-8'],
            ['fòô bàř', 'òô bàř', 'f', 0, 'UTF-8'],
            ['fòô bàř', 'f bàř', 'òô', 1, 'UTF-8'],
            ['fòô bàř', 'fòô bà', 'ř', 6, 'UTF-8'],
        ];
    }

    /**
     * @dataProvider truncateProvider()
     *
     * @param mixed      $expected
     * @param mixed      $str
     * @param mixed      $length
     * @param mixed      $substring
     * @param mixed|null $encoding
     */
    public function testTruncate(
        $expected,
        $str,
        $length,
        $substring = '',
        $encoding = null
    ) {
        $stringy = S::create($str, $encoding);
        $result = $stringy->truncate($length, $substring);
        $this->assertStringy($result);
        static::assertEquals($expected, $result);
        static::assertEquals($str, $stringy);
    }

    public function truncateProvider()
    {
        return [
            ['Test foo bar', 'Test foo bar', 12],
            ['Test foo ba', 'Test foo bar', 11],
            ['Test foo', 'Test foo bar', 8],
            ['Test fo', 'Test foo bar', 7],
            ['Test', 'Test foo bar', 4],
            ['Test foo bar', 'Test foo bar', 12, '...'],
            ['Test foo...', 'Test foo bar', 11, '...'],
            ['Test ...', 'Test foo bar', 8, '...'],
            ['Test...', 'Test foo bar', 7, '...'],
            ['T...', 'Test foo bar', 4, '...'],
            ['Test fo....', 'Test foo bar', 11, '....'],
            ['Test fòô bàř', 'Test fòô bàř', 12, '', 'UTF-8'],
            ['Test fòô bà', 'Test fòô bàř', 11, '', 'UTF-8'],
            ['Test fòô', 'Test fòô bàř', 8, '', 'UTF-8'],
            ['Test fò', 'Test fòô bàř', 7, '', 'UTF-8'],
            ['Test', 'Test fòô bàř', 4, '', 'UTF-8'],
            ['Test fòô bàř', 'Test fòô bàř', 12, 'ϰϰ', 'UTF-8'],
            ['Test fòô ϰϰ', 'Test fòô bàř', 11, 'ϰϰ', 'UTF-8'],
            ['Test fϰϰ', 'Test fòô bàř', 8, 'ϰϰ', 'UTF-8'],
            ['Test ϰϰ', 'Test fòô bàř', 7, 'ϰϰ', 'UTF-8'],
            ['Teϰϰ', 'Test fòô bàř', 4, 'ϰϰ', 'UTF-8'],
            ['What are your pl...', 'What are your plans today?', 19, '...'],
        ];
    }

    /**
     * @dataProvider safeTruncateProvider()
     *
     * @param mixed      $expected
     * @param mixed      $str
     * @param mixed      $length
     * @param mixed      $substring
     * @param mixed|null $encoding
     */
    public function testSafeTruncate(
        $expected,
        $str,
        $length,
        $substring = '',
        $encoding = null
    ) {
        $stringy = S::create($str, $encoding);
        $result = $stringy->safeTruncate($length, $substring);
        $this->assertStringy($result);
        static::assertEquals($expected, $result);
        static::assertEquals($str, $stringy);
    }

    public function safeTruncateProvider()
    {
        return [
            ['Test foo bar', 'Test foo bar', 12],
            ['Test foo', 'Test foo bar', 11],
            ['Test foo', 'Test foo bar', 8],
            ['Test', 'Test foo bar', 7],
            ['Test', 'Test foo bar', 4],
            ['Test foo bar', 'Test foo bar', 12, '...'],
            ['Test foo...', 'Test foo bar', 11, '...'],
            ['Test...', 'Test foo bar', 8, '...'],
            ['Test...', 'Test foo bar', 7, '...'],
            ['T...', 'Test foo bar', 4, '...'],
            ['Test....', 'Test foo bar', 11, '....'],
            ['Tëst fòô bàř', 'Tëst fòô bàř', 12, '', 'UTF-8'],
            ['Tëst fòô', 'Tëst fòô bàř', 11, '', 'UTF-8'],
            ['Tëst fòô', 'Tëst fòô bàř', 8, '', 'UTF-8'],
            ['Tëst', 'Tëst fòô bàř', 7, '', 'UTF-8'],
            ['Tëst', 'Tëst fòô bàř', 4, '', 'UTF-8'],
            ['Tëst fòô bàř', 'Tëst fòô bàř', 12, 'ϰϰ', 'UTF-8'],
            ['Tëst fòôϰϰ', 'Tëst fòô bàř', 11, 'ϰϰ', 'UTF-8'],
            ['Tëstϰϰ', 'Tëst fòô bàř', 8, 'ϰϰ', 'UTF-8'],
            ['Tëstϰϰ', 'Tëst fòô bàř', 7, 'ϰϰ', 'UTF-8'],
            ['Tëϰϰ', 'Tëst fòô bàř', 4, 'ϰϰ', 'UTF-8'],
            ['What are your plans...', 'What are your plans today?', 22, '...'],
        ];
    }

    /**
     * @dataProvider reverseProvider()
     *
     * @param mixed      $expected
     * @param mixed      $str
     * @param mixed|null $encoding
     */
    public function testReverse($expected, $str, $encoding = null)
    {
        $stringy = S::create($str, $encoding);
        $result = $stringy->reverse();
        $this->assertStringy($result);
        static::assertEquals($expected, $result);
        static::assertEquals($str, $stringy);
    }

    public function reverseProvider()
    {
        return [
            ['', ''],
            ['raboof', 'foobar'],
            ['řàbôòf', 'fòôbàř', 'UTF-8'],
            ['řàb ôòf', 'fòô bàř', 'UTF-8'],
            ['∂∆ ˚åß', 'ßå˚ ∆∂', 'UTF-8'],
        ];
    }

    /**
     * @dataProvider repeatProvider()
     *
     * @param mixed      $expected
     * @param mixed      $str
     * @param mixed      $multiplier
     * @param mixed|null $encoding
     */
    public function testRepeat($expected, $str, $multiplier, $encoding = null)
    {
        $stringy = S::create($str, $encoding);
        $result = $stringy->repeat($multiplier);
        $this->assertStringy($result);
        static::assertEquals($expected, $result);
        static::assertEquals($str, $stringy);
    }

    public function repeatProvider()
    {
        return [
            ['', 'foo', 0],
            ['foo', 'foo', 1],
            ['foofoo', 'foo', 2],
            ['foofoofoo', 'foo', 3],
            ['fòô', 'fòô', 1, 'UTF-8'],
            ['fòôfòô', 'fòô', 2, 'UTF-8'],
            ['fòôfòôfòô', 'fòô', 3, 'UTF-8'],
        ];
    }

    /**
     * @dataProvider shuffleProvider()
     *
     * @param mixed      $str
     * @param mixed|null $encoding
     */
    public function testShuffle($str, $encoding = null)
    {
        $stringy = S::create($str, $encoding);
        $encoding = $encoding ?: \mb_internal_encoding();
        $result = $stringy->shuffle();

        $this->assertStringy($result);
        static::assertEquals($str, $stringy);
        static::assertEquals(
            \mb_strlen($str, $encoding),
            \mb_strlen($result, $encoding)
        );

        // We'll make sure that the chars are present after shuffle
        for ($i = 0, $iMax = \mb_strlen($str, $encoding); $i < $iMax; ++$i) {
            $char = \mb_substr($str, $i, 1, $encoding);
            $countBefore = \mb_substr_count($str, $char, $encoding);
            $countAfter = \mb_substr_count($result, $char, $encoding);
            static::assertEquals($countBefore, $countAfter);
        }
    }

    public function shuffleProvider()
    {
        return [
            ['foo bar'],
            ['∂∆ ˚åß', 'UTF-8'],
            ['å´¥©¨ˆßå˚ ∆∂˙©å∑¥øœ¬', 'UTF-8'],
        ];
    }

    /**
     * @dataProvider trimProvider()
     *
     * @param mixed      $expected
     * @param mixed      $str
     * @param mixed|null $chars
     * @param mixed|null $encoding
     */
    public function testTrim($expected, $str, $chars = null, $encoding = null)
    {
        $stringy = S::create($str, $encoding);
        $result = $stringy->trim($chars);
        $this->assertStringy($result);
        static::assertEquals($expected, $result);
        static::assertEquals($str, $stringy);
    }

    public function trimProvider()
    {
        return [
            ['foo   bar', '  foo   bar  '],
            ['foo bar', ' foo bar'],
            ['foo bar', 'foo bar '],
            ['foo bar', "\n\t foo bar \n\t"],
            ['fòô   bàř', '  fòô   bàř  '],
            ['fòô bàř', ' fòô bàř'],
            ['fòô bàř', 'fòô bàř '],
            [' foo bar ', "\n\t foo bar \n\t", "\n\t"],
            ['fòô bàř', "\n\t fòô bàř \n\t", null, 'UTF-8'],
            ['fòô', ' fòô ', null, 'UTF-8'], // narrow no-break space (U+202F)
            ['fòô', '  fòô  ', null, 'UTF-8'], // medium mathematical space (U+205F)
            ['fòô', '           fòô', null, 'UTF-8'], // spaces U+2000 to U+200A
        ];
    }

    /**
     * @dataProvider trimLeftProvider()
     *
     * @param mixed      $expected
     * @param mixed      $str
     * @param mixed|null $chars
     * @param mixed|null $encoding
     */
    public function testTrimLeft(
        $expected,
        $str,
        $chars = null,
        $encoding = null
    ) {
        $stringy = S::create($str, $encoding);
        $result = $stringy->trimLeft($chars);
        $this->assertStringy($result);
        static::assertEquals($expected, $result);
        static::assertEquals($str, $stringy);
    }

    public function trimLeftProvider()
    {
        return [
            ['foo   bar  ', '  foo   bar  '],
            ['foo bar', ' foo bar'],
            ['foo bar ', 'foo bar '],
            ["foo bar \n\t", "\n\t foo bar \n\t"],
            ['fòô   bàř  ', '  fòô   bàř  '],
            ['fòô bàř', ' fòô bàř'],
            ['fòô bàř ', 'fòô bàř '],
            ['foo bar', '--foo bar', '-'],
            ['fòô bàř', 'òòfòô bàř', 'ò', 'UTF-8'],
            ["fòô bàř \n\t", "\n\t fòô bàř \n\t", null, 'UTF-8'],
            ['fòô ', ' fòô ', null, 'UTF-8'], // narrow no-break space (U+202F)
            ['fòô  ', '  fòô  ', null, 'UTF-8'], // medium mathematical space (U+205F)
            ['fòô', '           fòô', null, 'UTF-8'], // spaces U+2000 to U+200A
        ];
    }

    /**
     * @dataProvider trimRightProvider()
     *
     * @param mixed      $expected
     * @param mixed      $str
     * @param mixed|null $chars
     * @param mixed|null $encoding
     */
    public function testTrimRight(
        $expected,
        $str,
        $chars = null,
        $encoding = null
    ) {
        $stringy = S::create($str, $encoding);
        $result = $stringy->trimRight($chars);
        $this->assertStringy($result);
        static::assertEquals($expected, $result);
        static::assertEquals($str, $stringy);
    }

    public function trimRightProvider()
    {
        return [
            ['  foo   bar', '  foo   bar  '],
            ['foo bar', 'foo bar '],
            [' foo bar', ' foo bar'],
            ["\n\t foo bar", "\n\t foo bar \n\t"],
            ['  fòô   bàř', '  fòô   bàř  '],
            ['fòô bàř', 'fòô bàř '],
            [' fòô bàř', ' fòô bàř'],
            ['foo bar', 'foo bar--', '-'],
            ['fòô bàř', 'fòô bàřòò', 'ò', 'UTF-8'],
            ["\n\t fòô bàř", "\n\t fòô bàř \n\t", null, 'UTF-8'],
            [' fòô', ' fòô ', null, 'UTF-8'], // narrow no-break space (U+202F)
            ['  fòô', '  fòô  ', null, 'UTF-8'], // medium mathematical space (U+205F)
            ['fòô', 'fòô           ', null, 'UTF-8'], // spaces U+2000 to U+200A
        ];
    }

    /**
     * @dataProvider longestCommonPrefixProvider()
     *
     * @param mixed      $expected
     * @param mixed      $str
     * @param mixed      $otherStr
     * @param mixed|null $encoding
     */
    public function testLongestCommonPrefix(
        $expected,
        $str,
        $otherStr,
        $encoding = null
    ) {
        $stringy = S::create($str, $encoding);
        $result = $stringy->longestCommonPrefix($otherStr);
        $this->assertStringy($result);
        static::assertEquals($expected, $result);
        static::assertEquals($str, $stringy);
    }

    public function longestCommonPrefixProvider()
    {
        return [
            ['foo', 'foobar', 'foo bar'],
            ['foo bar', 'foo bar', 'foo bar'],
            ['f', 'foo bar', 'far boo'],
            ['', 'toy car', 'foo bar'],
            ['', 'foo bar', ''],
            ['fòô', 'fòôbar', 'fòô bar', 'UTF-8'],
            ['fòô bar', 'fòô bar', 'fòô bar', 'UTF-8'],
            ['fò', 'fòô bar', 'fòr bar', 'UTF-8'],
            ['', 'toy car', 'fòô bar', 'UTF-8'],
            ['', 'fòô bar', '', 'UTF-8'],
        ];
    }

    /**
     * @dataProvider longestCommonSuffixProvider()
     *
     * @param mixed      $expected
     * @param mixed      $str
     * @param mixed      $otherStr
     * @param mixed|null $encoding
     */
    public function testLongestCommonSuffix(
        $expected,
        $str,
        $otherStr,
        $encoding = null
    ) {
        $stringy = S::create($str, $encoding);
        $result = $stringy->longestCommonSuffix($otherStr);
        $this->assertStringy($result);
        static::assertEquals($expected, $result);
        static::assertEquals($str, $stringy);
    }

    public function longestCommonSuffixProvider()
    {
        return [
            ['bar', 'foobar', 'foo bar'],
            ['foo bar', 'foo bar', 'foo bar'],
            ['ar', 'foo bar', 'boo far'],
            ['', 'foo bad', 'foo bar'],
            ['', 'foo bar', ''],
            ['bàř', 'fòôbàř', 'fòô bàř', 'UTF-8'],
            ['fòô bàř', 'fòô bàř', 'fòô bàř', 'UTF-8'],
            [' bàř', 'fòô bàř', 'fòr bàř', 'UTF-8'],
            ['', 'toy car', 'fòô bàř', 'UTF-8'],
            ['', 'fòô bàř', '', 'UTF-8'],
        ];
    }

    /**
     * @dataProvider longestCommonSubstringProvider()
     *
     * @param mixed      $expected
     * @param mixed      $str
     * @param mixed      $otherStr
     * @param mixed|null $encoding
     */
    public function testLongestCommonSubstring(
        $expected,
        $str,
        $otherStr,
        $encoding = null
    ) {
        $stringy = S::create($str, $encoding);
        $result = $stringy->longestCommonSubstring($otherStr);
        $this->assertStringy($result);
        static::assertEquals($expected, $result);
        static::assertEquals($str, $stringy);
    }

    public function longestCommonSubstringProvider()
    {
        return [
            ['foo', 'foobar', 'foo bar'],
            ['foo bar', 'foo bar', 'foo bar'],
            ['oo ', 'foo bar', 'boo far'],
            ['foo ba', 'foo bad', 'foo bar'],
            ['', 'foo bar', ''],
            ['fòô', 'fòôbàř', 'fòô bàř', 'UTF-8'],
            ['fòô bàř', 'fòô bàř', 'fòô bàř', 'UTF-8'],
            [' bàř', 'fòô bàř', 'fòr bàř', 'UTF-8'],
            [' ', 'toy car', 'fòô bàř', 'UTF-8'],
            ['', 'fòô bàř', '', 'UTF-8'],
        ];
    }

    /**
     * @dataProvider lengthProvider()
     *
     * @param mixed      $expected
     * @param mixed      $str
     * @param mixed|null $encoding
     */
    public function testLength($expected, $str, $encoding = null)
    {
        $stringy = S::create($str, $encoding);
        $result = $stringy->length();
        static::assertTrue(\is_int($result));
        static::assertEquals($expected, $result);
        static::assertEquals($str, $stringy);
    }

    public function lengthProvider()
    {
        return [
            [11, '  foo bar  '],
            [1, 'f'],
            [0, ''],
            [7, 'fòô bàř', 'UTF-8'],
        ];
    }

    /**
     * @dataProvider sliceProvider()
     *
     * @param mixed      $expected
     * @param mixed      $str
     * @param mixed      $start
     * @param mixed|null $end
     * @param mixed|null $encoding
     */
    public function testSlice(
        $expected,
        $str,
        $start,
        $end = null,
        $encoding = null
    ) {
        $stringy = S::create($str, $encoding);
        $result = $stringy->slice($start, $end);
        $this->assertStringy($result);
        static::assertEquals($expected, $result);
        static::assertEquals($str, $stringy);
    }

    public function sliceProvider()
    {
        return [
            ['foobar', 'foobar', 0],
            ['foobar', 'foobar', 0, null],
            ['foobar', 'foobar', 0, 6],
            ['fooba', 'foobar', 0, 5],
            ['', 'foobar', 3, 0],
            ['', 'foobar', 3, 2],
            ['ba', 'foobar', 3, 5],
            ['ba', 'foobar', 3, -1],
            ['fòôbàř', 'fòôbàř', 0, null, 'UTF-8'],
            ['fòôbàř', 'fòôbàř', 0, null],
            ['fòôbàř', 'fòôbàř', 0, 6, 'UTF-8'],
            ['fòôbà', 'fòôbàř', 0, 5, 'UTF-8'],
            ['', 'fòôbàř', 3, 0, 'UTF-8'],
            ['', 'fòôbàř', 3, 2, 'UTF-8'],
            ['bà', 'fòôbàř', 3, 5, 'UTF-8'],
            ['bà', 'fòôbàř', 3, -1, 'UTF-8'],
        ];
    }

    /**
     * @dataProvider splitProvider()
     *
     * @param mixed      $expected
     * @param mixed      $str
     * @param mixed      $pattern
     * @param mixed|null $limit
     * @param mixed|null $encoding
     */
    public function testSplit(
        $expected,
        $str,
        $pattern,
        $limit = null,
        $encoding = null
    ) {
        $stringy = S::create($str, $encoding);
        $result = $stringy->splitCollection($pattern, $limit);

        static::assertInstanceOf(\Stringy\CollectionStringy::class, $result);
        foreach ($result as $string) {
            $this->assertStringy($string);
        }

        for ($i = 0, $iMax = \count($expected); $i < $iMax; ++$i) {
            static::assertEquals($expected[$i], $result[$i]);
        }
    }

    public function splitProvider()
    {
        return [
            [['foo,bar,baz'], 'foo,bar,baz', ''],
            [['foo,bar,baz'], 'foo,bar,baz', '-'],
            [['foo', 'bar', 'baz'], 'foo,bar,baz', ','],
            [['foo', 'bar', 'baz'], 'foo,bar,baz', ',', null],
            [['foo', 'bar', 'baz'], 'foo,bar,baz', ',', -1],
            [[], 'foo,bar,baz', ',', 0],
            [['foo'], 'foo,bar,baz', ',', 1],
            [['foo', 'bar'], 'foo,bar,baz', ',', 2],
            [['foo', 'bar', 'baz'], 'foo,bar,baz', ',', 3],
            [['foo', 'bar', 'baz'], 'foo,bar,baz', ',', 10],
            [['fòô,bàř,baz'], 'fòô,bàř,baz', '-', null, 'UTF-8'],
            [['fòô', 'bàř', 'baz'], 'fòô,bàř,baz', ',', null, 'UTF-8'],
            [['fòô', 'bàř', 'baz'], 'fòô,bàř,baz', ',', null, 'UTF-8'],
            [['fòô', 'bàř', 'baz'], 'fòô,bàř,baz', ',', -1, 'UTF-8'],
            [[], 'fòô,bàř,baz', ',', 0, 'UTF-8'],
            [['fòô'], 'fòô,bàř,baz', ',', 1, 'UTF-8'],
            [['fòô', 'bàř'], 'fòô,bàř,baz', ',', 2, 'UTF-8'],
            [['fòô', 'bàř', 'baz'], 'fòô,bàř,baz', ',', 3, 'UTF-8'],
            [['fòô', 'bàř', 'baz'], 'fòô,bàř,baz', ',', 10, 'UTF-8'],
        ];
    }

    /**
     * @dataProvider stripWhitespaceProvider()
     *
     * @param mixed      $expected
     * @param mixed      $str
     * @param mixed|null $encoding
     */
    public function testStripWhitespace($expected, $str, $encoding = null)
    {
        $stringy = S::create($str, $encoding);
        $result = $stringy->stripWhitespace();
        $this->assertStringy($result);
        static::assertEquals($expected, $result);
        static::assertEquals($str, $stringy);
    }

    public function stripWhitespaceProvider()
    {
        return [
            ['foobar', '  foo   bar  '],
            ['teststring', 'test string'],
            ['Οσυγγραφέας', '   Ο     συγγραφέας  '],
            ['123', ' 123 '],
            ['', ' ', 'UTF-8'], // no-break space (U+00A0)
            ['', '           ', 'UTF-8'], // spaces U+2000 to U+200A
            ['', ' ', 'UTF-8'], // narrow no-break space (U+202F)
            ['', ' ', 'UTF-8'], // medium mathematical space (U+205F)
            ['', '　', 'UTF-8'], // ideographic space (U+3000)
            ['123', '  1  2  3　　', 'UTF-8'],
            ['', ' '],
            ['', ''],
        ];
    }

    /**
     * @dataProvider substrProvider()
     *
     * @param mixed      $expected
     * @param mixed      $str
     * @param mixed      $start
     * @param mixed|null $length
     * @param mixed|null $encoding
     */
    public function testSubstr(
        $expected,
        $str,
        $start,
        $length = null,
        $encoding = null
    ) {
        $stringy = S::create($str, $encoding);
        $result = $stringy->substr($start, $length);
        $this->assertStringy($result);
        static::assertEquals($expected, $result);
        static::assertEquals($str, $stringy);
    }

    public function substrProvider()
    {
        return [
            ['foo bar', 'foo bar', 0],
            ['bar', 'foo bar', 4],
            ['bar', 'foo bar', 4, null],
            ['o b', 'foo bar', 2, 3],
            ['', 'foo bar', 4, 0],
            ['fòô bàř', 'fòô bàř', 0, null, 'UTF-8'],
            ['bàř', 'fòô bàř', 4, null, 'UTF-8'],
            ['ô b', 'fòô bàř', 2, 3, 'UTF-8'],
            ['', 'fòô bàř', 4, 0, 'UTF-8'],
        ];
    }

    /**
     * @dataProvider atProvider()
     *
     * @param mixed      $expected
     * @param mixed      $str
     * @param mixed      $index
     * @param mixed|null $encoding
     */
    public function testAt($expected, $str, $index, $encoding = null)
    {
        $stringy = S::create($str, $encoding);
        $result = $stringy->at($index);
        $this->assertStringy($result);
        static::assertEquals($expected, $result);
        static::assertEquals($str, $stringy);
    }

    public function atProvider()
    {
        return [
            ['f', 'foo bar', 0],
            ['o', 'foo bar', 1],
            ['r', 'foo bar', 6],
            ['', 'foo bar', 7],
            ['f', 'fòô bàř', 0, 'UTF-8'],
            ['ò', 'fòô bàř', 1, 'UTF-8'],
            ['ř', 'fòô bàř', 6, 'UTF-8'],
            ['', 'fòô bàř', 7, 'UTF-8'],
        ];
    }

    /**
     * @dataProvider firstProvider()
     *
     * @param mixed      $expected
     * @param mixed      $str
     * @param mixed      $n
     * @param mixed|null $encoding
     */
    public function testFirst($expected, $str, $n, $encoding = null)
    {
        $stringy = S::create($str, $encoding);
        $result = $stringy->first($n);
        $this->assertStringy($result);
        static::assertEquals($expected, $result);
        static::assertEquals($str, $stringy);
    }

    public function firstProvider()
    {
        return [
            ['', 'foo bar', -5],
            ['', 'foo bar', 0],
            ['f', 'foo bar', 1],
            ['foo', 'foo bar', 3],
            ['foo bar', 'foo bar', 7],
            ['foo bar', 'foo bar', 8],
            ['', 'fòô bàř', -5, 'UTF-8'],
            ['', 'fòô bàř', 0, 'UTF-8'],
            ['f', 'fòô bàř', 1, 'UTF-8'],
            ['fòô', 'fòô bàř', 3, 'UTF-8'],
            ['fòô bàř', 'fòô bàř', 7, 'UTF-8'],
            ['fòô bàř', 'fòô bàř', 8, 'UTF-8'],
        ];
    }

    /**
     * @dataProvider lastProvider()
     *
     * @param mixed      $expected
     * @param mixed      $str
     * @param mixed      $n
     * @param mixed|null $encoding
     */
    public function testLast($expected, $str, $n, $encoding = null)
    {
        $stringy = S::create($str, $encoding);
        $result = $stringy->last($n);
        $this->assertStringy($result);
        static::assertEquals($expected, $result);
        static::assertEquals($str, $stringy);
    }

    public function lastProvider()
    {
        return [
            ['', 'foo bar', -5],
            ['', 'foo bar', 0],
            ['r', 'foo bar', 1],
            ['bar', 'foo bar', 3],
            ['foo bar', 'foo bar', 7],
            ['foo bar', 'foo bar', 8],
            ['', 'fòô bàř', -5, 'UTF-8'],
            ['', 'fòô bàř', 0, 'UTF-8'],
            ['ř', 'fòô bàř', 1, 'UTF-8'],
            ['bàř', 'fòô bàř', 3, 'UTF-8'],
            ['fòô bàř', 'fòô bàř', 7, 'UTF-8'],
            ['fòô bàř', 'fòô bàř', 8, 'UTF-8'],
        ];
    }

    /**
     * @dataProvider ensureLeftProvider()
     *
     * @param mixed      $expected
     * @param mixed      $str
     * @param mixed      $substring
     * @param mixed|null $encoding
     */
    public function testEnsureLeft($expected, $str, $substring, $encoding = null)
    {
        $stringy = S::create($str, $encoding);
        $result = $stringy->ensureLeft($substring);
        $this->assertStringy($result);
        static::assertEquals($expected, $result);
        static::assertEquals($str, $stringy);
    }

    public function ensureLeftProvider()
    {
        return [
            ['foobar', 'foobar', 'f'],
            ['foobar', 'foobar', 'foo'],
            ['foo/foobar', 'foobar', 'foo/'],
            ['http://foobar', 'foobar', 'http://'],
            ['http://foobar', 'http://foobar', 'http://'],
            ['fòôbàř', 'fòôbàř', 'f', 'UTF-8'],
            ['fòôbàř', 'fòôbàř', 'fòô', 'UTF-8'],
            ['fòô/fòôbàř', 'fòôbàř', 'fòô/', 'UTF-8'],
            ['http://fòôbàř', 'fòôbàř', 'http://', 'UTF-8'],
            ['http://fòôbàř', 'http://fòôbàř', 'http://', 'UTF-8'],
        ];
    }

    /**
     * @dataProvider ensureRightProvider()
     *
     * @param mixed      $expected
     * @param mixed      $str
     * @param mixed      $substring
     * @param mixed|null $encoding
     */
    public function testEnsureRight($expected, $str, $substring, $encoding = null)
    {
        $stringy = S::create($str, $encoding);
        $result = $stringy->ensureRight($substring);
        $this->assertStringy($result);
        static::assertEquals($expected, $result);
        static::assertEquals($str, $stringy);
    }

    public function ensureRightProvider()
    {
        return [
            ['foobar', 'foobar', 'r'],
            ['foobar', 'foobar', 'bar'],
            ['foobar/bar', 'foobar', '/bar'],
            ['foobar.com/', 'foobar', '.com/'],
            ['foobar.com/', 'foobar.com/', '.com/'],
            ['fòôbàř', 'fòôbàř', 'ř', 'UTF-8'],
            ['fòôbàř', 'fòôbàř', 'bàř', 'UTF-8'],
            ['fòôbàř/bàř', 'fòôbàř', '/bàř', 'UTF-8'],
            ['fòôbàř.com/', 'fòôbàř', '.com/', 'UTF-8'],
            ['fòôbàř.com/', 'fòôbàř.com/', '.com/', 'UTF-8'],
        ];
    }

    /**
     * @dataProvider removeLeftProvider()
     *
     * @param mixed      $expected
     * @param mixed      $str
     * @param mixed      $substring
     * @param mixed|null $encoding
     */
    public function testRemoveLeft($expected, $str, $substring, $encoding = null)
    {
        $stringy = S::create($str, $encoding);
        $result = $stringy->removeLeft($substring);
        $this->assertStringy($result);
        static::assertEquals($expected, $result);
        static::assertEquals($str, $stringy);
    }

    public function removeLeftProvider()
    {
        return [
            ['foo bar', 'foo bar', ''],
            ['oo bar', 'foo bar', 'f'],
            ['bar', 'foo bar', 'foo '],
            ['foo bar', 'foo bar', 'oo'],
            ['foo bar', 'foo bar', 'oo bar'],
            ['oo bar', 'foo bar', S::create('foo bar')->first(1), 'UTF-8'],
            ['oo bar', 'foo bar', S::create('foo bar')->at(0), 'UTF-8'],
            ['fòô bàř', 'fòô bàř', '', 'UTF-8'],
            ['òô bàř', 'fòô bàř', 'f', 'UTF-8'],
            ['bàř', 'fòô bàř', 'fòô ', 'UTF-8'],
            ['fòô bàř', 'fòô bàř', 'òô', 'UTF-8'],
            ['fòô bàř', 'fòô bàř', 'òô bàř', 'UTF-8'],
        ];
    }

    /**
     * @dataProvider removeRightProvider()
     *
     * @param mixed      $expected
     * @param mixed      $str
     * @param mixed      $substring
     * @param mixed|null $encoding
     */
    public function testRemoveRight($expected, $str, $substring, $encoding = null)
    {
        $stringy = S::create($str, $encoding);
        $result = $stringy->removeRight($substring);
        $this->assertStringy($result);
        static::assertEquals($expected, $result);
        static::assertEquals($str, $stringy);
    }

    public function removeRightProvider()
    {
        return [
            ['foo bar', 'foo bar', ''],
            ['foo ba', 'foo bar', 'r'],
            ['foo', 'foo bar', ' bar'],
            ['foo bar', 'foo bar', 'ba'],
            ['foo bar', 'foo bar', 'foo ba'],
            ['foo ba', 'foo bar', S::create('foo bar')->last(1), 'UTF-8'],
            ['foo ba', 'foo bar', S::create('foo bar')->at(6), 'UTF-8'],
            ['fòô bàř', 'fòô bàř', '', 'UTF-8'],
            ['fòô bà', 'fòô bàř', 'ř', 'UTF-8'],
            ['fòô', 'fòô bàř', ' bàř', 'UTF-8'],
            ['fòô bàř', 'fòô bàř', 'bà', 'UTF-8'],
            ['fòô bàř', 'fòô bàř', 'fòô bà', 'UTF-8'],
        ];
    }

    /**
     * @dataProvider isAlphaProvider()
     *
     * @param mixed      $expected
     * @param mixed      $str
     * @param mixed|null $encoding
     */
    public function testIsAlpha($expected, $str, $encoding = null)
    {
        $stringy = S::create($str, $encoding);
        $result = $stringy->isAlpha();
        static::assertTrue(\is_bool($result));
        static::assertEquals($expected, $result);
        static::assertEquals($str, $stringy);
    }

    public function isAlphaProvider()
    {
        return [
            [true, ''],
            [true, 'foobar'],
            [false, 'foo bar'],
            [false, 'foobar2'],
            [true, 'fòôbàř', 'UTF-8'],
            [false, 'fòô bàř', 'UTF-8'],
            [false, 'fòôbàř2', 'UTF-8'],
            [true, 'ҠѨњфгШ', 'UTF-8'],
            [false, 'ҠѨњ¨ˆфгШ', 'UTF-8'],
            [true, '丹尼爾', 'UTF-8'],
        ];
    }

    /**
     * @dataProvider isAlphanumericProvider()
     *
     * @param mixed      $expected
     * @param mixed      $str
     * @param mixed|null $encoding
     */
    public function testIsAlphanumeric($expected, $str, $encoding = null)
    {
        $stringy = S::create($str, $encoding);
        $result = $stringy->isAlphanumeric();
        static::assertTrue(\is_bool($result));
        static::assertEquals($expected, $result);
        static::assertEquals($str, $stringy);
    }

    public function isAlphanumericProvider()
    {
        return [
            [true, ''],
            [true, 'foobar1'],
            [false, 'foo bar'],
            [false, 'foobar2"'],
            [false, "\nfoobar\n"],
            [true, 'fòôbàř1', 'UTF-8'],
            [false, 'fòô bàř', 'UTF-8'],
            [false, 'fòôbàř2"', 'UTF-8'],
            [true, 'ҠѨњфгШ', 'UTF-8'],
            [false, 'ҠѨњ¨ˆфгШ', 'UTF-8'],
            [true, '丹尼爾111', 'UTF-8'],
            [true, 'دانيال1', 'UTF-8'],
            [false, 'دانيال1 ', 'UTF-8'],
        ];
    }

    /**
     * @dataProvider isBlankProvider()
     *
     * @param mixed      $expected
     * @param mixed      $str
     * @param mixed|null $encoding
     */
    public function testIsBlank($expected, $str, $encoding = null)
    {
        $stringy = S::create($str, $encoding);
        $result = $stringy->isBlank();
        static::assertTrue(\is_bool($result));
        static::assertEquals($expected, $result);
        static::assertEquals($str, $stringy);
    }

    public function isBlankProvider()
    {
        return [
            [true, ''],
            [true, ' '],
            [true, "\n\t "],
            [true, "\n\t  \v\f"],
            [false, "\n\t a \v\f"],
            [false, "\n\t ' \v\f"],
            [false, "\n\t 2 \v\f"],
            [true, '', 'UTF-8'],
            [true, ' ', 'UTF-8'], // no-break space (U+00A0)
            [true, '           ', 'UTF-8'], // spaces U+2000 to U+200A
            [true, ' ', 'UTF-8'], // narrow no-break space (U+202F)
            [true, ' ', 'UTF-8'], // medium mathematical space (U+205F)
            [true, '　', 'UTF-8'], // ideographic space (U+3000)
            [false, '　z', 'UTF-8'],
            [false, '　1', 'UTF-8'],
        ];
    }

    /**
     * @dataProvider isJsonProvider()
     *
     * @param mixed      $expected
     * @param mixed      $str
     * @param mixed|null $encoding
     */
    public function testIsJson($expected, $str, $encoding = null)
    {
        $stringy = S::create($str, $encoding);
        $result = $stringy->isJson();
        static::assertTrue(\is_bool($result));
        static::assertEquals($expected, $result);
        static::assertEquals($str, $stringy);
    }

    public function isJsonProvider()
    {
        return [
            [false, ''],
            [false, '  '],
            [true, 'null'],
            [true, 'true'],
            [true, 'false'],
            [true, '[]'],
            [true, '{}'],
            [true, '123'],
            [true, '{"foo": "bar"}'],
            [false, '{"foo":"bar",}'],
            [false, '{"foo"}'],
            [true, '["foo"]'],
            [false, '{"foo": "bar"]'],
            [true, '123', 'UTF-8'],
            [true, '{"fòô": "bàř"}', 'UTF-8'],
            [false, '{"fòô":"bàř",}', 'UTF-8'],
            [false, '{"fòô"}', 'UTF-8'],
            [false, '["fòô": "bàř"]', 'UTF-8'],
            [true, '["fòô"]', 'UTF-8'],
            [false, '{"fòô": "bàř"]', 'UTF-8'],
        ];
    }

    /**
     * @dataProvider isLowerCaseProvider()
     *
     * @param mixed      $expected
     * @param mixed      $str
     * @param mixed|null $encoding
     */
    public function testIsLowerCase($expected, $str, $encoding = null)
    {
        $stringy = S::create($str, $encoding);
        $result = $stringy->isLowerCase();
        static::assertTrue(\is_bool($result));
        static::assertEquals($expected, $result);
        static::assertEquals($str, $stringy);
    }

    public function isLowerCaseProvider()
    {
        return [
            [true, ''],
            [true, 'foobar'],
            [false, 'foo bar'],
            [false, 'Foobar'],
            [true, 'fòôbàř', 'UTF-8'],
            [false, 'fòôbàř2', 'UTF-8'],
            [false, 'fòô bàř', 'UTF-8'],
            [false, 'fòôbÀŘ', 'UTF-8'],
        ];
    }

    /**
     * @dataProvider hasLowerCaseProvider()
     *
     * @param mixed      $expected
     * @param mixed      $str
     * @param mixed|null $encoding
     */
    public function testHasLowerCase($expected, $str, $encoding = null)
    {
        $stringy = S::create($str, $encoding);
        $result = $stringy->hasLowerCase();
        static::assertTrue(\is_bool($result));
        static::assertEquals($expected, $result);
        static::assertEquals($str, $stringy);
    }

    public function hasLowerCaseProvider()
    {
        return [
            [false, ''],
            [true, 'foobar'],
            [false, 'FOO BAR'],
            [true, 'fOO BAR'],
            [true, 'foO BAR'],
            [true, 'FOO BAr'],
            [true, 'Foobar'],
            [false, 'FÒÔBÀŘ', 'UTF-8'],
            [true, 'fòôbàř', 'UTF-8'],
            [true, 'fòôbàř2', 'UTF-8'],
            [true, 'Fòô bàř', 'UTF-8'],
            [true, 'fòôbÀŘ', 'UTF-8'],
        ];
    }

    /**
     * @dataProvider isSerializedProvider()
     *
     * @param mixed      $expected
     * @param mixed      $str
     * @param mixed|null $encoding
     */
    public function testIsSerialized($expected, $str, $encoding = null)
    {
        $stringy = S::create($str, $encoding);
        $result = $stringy->isSerialized();
        static::assertTrue(\is_bool($result));
        static::assertEquals($expected, $result);
        static::assertEquals($str, $stringy);
    }

    public function isSerializedProvider()
    {
        return [
            [false, ''],
            [true, 'a:1:{s:3:"foo";s:3:"bar";}'],
            [false, 'a:1:{s:3:"foo";s:3:"bar"}'],
            [true, \serialize(['foo' => 'bar'])],
            [true, 'a:1:{s:5:"fòô";s:5:"bàř";}', 'UTF-8'],
            [false, 'a:1:{s:5:"fòô";s:5:"bàř"}', 'UTF-8'],
            [true, \serialize(['fòô' => 'bár']), 'UTF-8'],
        ];
    }

    /**
     * @dataProvider isBase64Provider()
     *
     * @param mixed $expected
     * @param mixed $str
     */
    public function testIsBase64($expected, $str)
    {
        $stringy = S::create($str);
        $result = $stringy->isBase64();
        static::assertTrue(\is_bool($result));
        static::assertEquals($expected, $result);
        static::assertEquals($str, $stringy);
    }

    public function isBase64Provider()
    {
        return [
            [false, ' '],
            [true, ''],
            [true, \base64_encode('FooBar')],
            [true, \base64_encode(' ')],
            [true, \base64_encode('FÒÔBÀŘ')],
            [true, \base64_encode('συγγραφέας')],
            [false, 'Foobar'],
        ];
    }

    /**
     * @dataProvider isUpperCaseProvider()
     *
     * @param mixed      $expected
     * @param mixed      $str
     * @param mixed|null $encoding
     */
    public function testIsUpperCase($expected, $str, $encoding = null)
    {
        $stringy = S::create($str, $encoding);
        $result = $stringy->isUpperCase();
        static::assertTrue(\is_bool($result));
        static::assertEquals($expected, $result);
        static::assertEquals($str, $stringy);
    }

    public function isUpperCaseProvider()
    {
        return [
            [true, ''],
            [true, 'FOOBAR'],
            [false, 'FOO BAR'],
            [false, 'fOOBAR'],
            [true, 'FÒÔBÀŘ', 'UTF-8'],
            [false, 'FÒÔBÀŘ2', 'UTF-8'],
            [false, 'FÒÔ BÀŘ', 'UTF-8'],
            [false, 'FÒÔBàř', 'UTF-8'],
        ];
    }

    /**
     * @dataProvider hasUpperCaseProvider()
     *
     * @param mixed      $expected
     * @param mixed      $str
     * @param mixed|null $encoding
     */
    public function testHasUpperCase($expected, $str, $encoding = null)
    {
        $stringy = S::create($str, $encoding);
        $result = $stringy->hasUpperCase();
        static::assertTrue(\is_bool($result));
        static::assertEquals($expected, $result);
        static::assertEquals($str, $stringy);
    }

    public function hasUpperCaseProvider()
    {
        return [
            [false, ''],
            [true, 'FOOBAR'],
            [false, 'foo bar'],
            [true, 'Foo bar'],
            [true, 'FOo bar'],
            [true, 'foo baR'],
            [true, 'fOOBAR'],
            [false, 'fòôbàř', 'UTF-8'],
            [true, 'FÒÔBÀŘ', 'UTF-8'],
            [true, 'FÒÔBÀŘ2', 'UTF-8'],
            [true, 'fÒÔ BÀŘ', 'UTF-8'],
            [true, 'FÒÔBàř', 'UTF-8'],
        ];
    }

    /**
     * @dataProvider isHexadecimalProvider()
     *
     * @param mixed      $expected
     * @param mixed      $str
     * @param mixed|null $encoding
     */
    public function testIsHexadecimal($expected, $str, $encoding = null)
    {
        $stringy = S::create($str, $encoding);
        $result = $stringy->isHexadecimal();
        static::assertTrue(\is_bool($result));
        static::assertEquals($expected, $result);
        static::assertEquals($str, $stringy);
    }

    public function isHexadecimalProvider()
    {
        return [
            [true, ''],
            [true, 'abcdef'],
            [true, 'ABCDEF'],
            [true, '0123456789'],
            [true, '0123456789AbCdEf'],
            [false, '0123456789x'],
            [false, 'ABCDEFx'],
            [true, 'abcdef', 'UTF-8'],
            [true, 'ABCDEF', 'UTF-8'],
            [true, '0123456789', 'UTF-8'],
            [true, '0123456789AbCdEf', 'UTF-8'],
            [false, '0123456789x', 'UTF-8'],
            [false, 'ABCDEFx', 'UTF-8'],
        ];
    }

    /**
     * @dataProvider countSubstrProvider()
     *
     * @param mixed      $expected
     * @param mixed      $str
     * @param mixed      $substring
     * @param mixed      $caseSensitive
     * @param mixed|null $encoding
     */
    public function testCountSubstr(
        $expected,
        $str,
        $substring,
        $caseSensitive = true,
        $encoding = null
    ) {
        $stringy = S::create($str, $encoding);
        $result = $stringy->countSubstr($substring, $caseSensitive);
        static::assertTrue(\is_int($result));
        static::assertEquals($expected, $result);
        static::assertEquals($str, $stringy);
    }

    public function countSubstrProvider()
    {
        return [
            [0, '', 'foo'],
            [0, 'foo', 'bar'],
            [1, 'foo bar', 'foo'],
            [2, 'foo bar', 'o'],
            [0, '', 'fòô', 'UTF-8'],
            [0, 'fòô', 'bàř', 'UTF-8'],
            [1, 'fòô bàř', 'fòô', 'UTF-8'],
            [2, 'fôòô bàř', 'ô', 'UTF-8'],
            [0, 'fÔÒÔ bàř', 'ô', 'UTF-8'],
            [0, 'foo', 'BAR', false],
            [1, 'foo bar', 'FOo', false],
            [2, 'foo bar', 'O', false],
            [1, 'fòô bàř', 'fÒÔ', false, 'UTF-8'],
            [2, 'fôòô bàř', 'Ô', false, 'UTF-8'],
            [2, 'συγγραφέας', 'Σ', false, 'UTF-8'],
        ];
    }

    /**
     * @dataProvider replaceProvider()
     *
     * @param mixed      $expected
     * @param mixed      $str
     * @param mixed      $search
     * @param mixed      $replacement
     * @param mixed|null $encoding
     */
    public function testReplace(
        $expected,
        $str,
        $search,
        $replacement,
        $encoding = null
    ) {
        $stringy = S::create($str, $encoding);
        $result = $stringy->replace($search, $replacement);
        $this->assertStringy($result);
        static::assertEquals($expected, $result);
        static::assertEquals($str, $stringy);
    }

    public function replaceProvider()
    {
        return [
            ['', '', '', ''],
            ['foo', '', '', 'foo'],
            ['foo', '\s', '\s', 'foo'],
            ['foo bar', 'foo bar', '', ''],
            ['foo bar', 'foo bar', 'f(o)o', '\1'],
            ['\1 bar', 'foo bar', 'foo', '\1'],
            ['bar', 'foo bar', 'foo ', ''],
            ['far bar', 'foo bar', 'foo', 'far'],
            ['bar bar', 'foo bar foo bar', 'foo ', ''],
            ['', '', '', '', 'UTF-8'],
            ['fòô', '', '', 'fòô', 'UTF-8'],
            ['fòô', '\s', '\s', 'fòô', 'UTF-8'],
            ['fòô bàř', 'fòô bàř', '', '', 'UTF-8'],
            ['bàř', 'fòô bàř', 'fòô ', '', 'UTF-8'],
            ['far bàř', 'fòô bàř', 'fòô', 'far', 'UTF-8'],
            ['bàř bàř', 'fòô bàř fòô bàř', 'fòô ', '', 'UTF-8'],
        ];
    }

    /**
     * @dataProvider regexReplaceProvider()
     *
     * @param mixed      $expected
     * @param mixed      $str
     * @param mixed      $pattern
     * @param mixed      $replacement
     * @param mixed      $options
     * @param mixed|null $encoding
     */
    public function testTestregexReplace(
        $expected,
        $str,
        $pattern,
        $replacement,
        $options = 'msr',
        $encoding = null
    ) {
        $stringy = S::create($str, $encoding);
        $result = $stringy->regexReplace($pattern, $replacement, $options);
        $this->assertStringy($result);
        static::assertEquals($expected, $result);
        static::assertEquals($str, $stringy);
    }

    public function regexReplaceProvider()
    {
        return [
            ['', '', '', ''],
            ['bar', 'foo', 'f[o]+', 'bar'],
            ['o bar', 'foo bar', 'f(o)o', '\1'],
            ['bar', 'foo bar', 'f[O]+\s', '', 'i'],
            ['foo', 'bar', '[[:alpha:]]{3}', 'foo'],
            ['', '', '', '', 'msr', 'UTF-8'],
            ['bàř', 'fòô ', 'f[òô]+\s', 'bàř', 'msr', 'UTF-8'],
            ['fòô', 'fò', '(ò)', '\\1ô', 'msr', 'UTF-8'],
            ['fòô', 'bàř', '[[:alpha:]]{3}', 'fòô', 'msr', 'UTF-8'],
        ];
    }

    /**
     * @dataProvider htmlEncodeProvider()
     *
     * @param mixed      $expected
     * @param mixed      $str
     * @param mixed      $flags
     * @param mixed|null $encoding
     */
    public function testHtmlEncode($expected, $str, $flags = \ENT_COMPAT, $encoding = null)
    {
        $stringy = S::create($str, $encoding);
        $result = $stringy->htmlEncode($flags);
        $this->assertStringy($result);
        static::assertEquals($expected, $result);
        static::assertEquals($str, $stringy);
    }

    public function htmlEncodeProvider()
    {
        return [
            ['&amp;', '&'],
            ['&quot;', '"'],
            ['&#039;', "'", \ENT_QUOTES],
            ['&lt;', '<'],
            ['&gt;', '>'],
        ];
    }

    /**
     * @dataProvider htmlDecodeProvider()
     *
     * @param mixed      $expected
     * @param mixed      $str
     * @param mixed      $flags
     * @param mixed|null $encoding
     */
    public function testHtmlDecode($expected, $str, $flags = \ENT_COMPAT, $encoding = null)
    {
        $stringy = S::create($str, $encoding);
        $result = $stringy->htmlDecode($flags);
        $this->assertStringy($result);
        static::assertEquals($expected, $result);
        static::assertEquals($str, $stringy);
    }

    public function htmlDecodeProvider()
    {
        return [
            ['&', '&amp;'],
            ['"', '&quot;'],
            ["'", '&#039;', \ENT_QUOTES],
            ['<', '&lt;'],
            ['>', '&gt;'],
        ];
    }

    /**
     * @param string $name
     *
     * @return ReflectionMethod
     */
    private static function getMethod(string $name): ReflectionMethod
    {
        $class = new ReflectionClass(S::class);
        $method = $class->getMethod($name);
        $method->setAccessible(true);

        return $method;
    }
}
