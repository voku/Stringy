<?php

declare(strict_types=1);

namespace Stringy;

use Defuse\Crypto\Crypto;
use voku\helper\AntiXSS;
use voku\helper\ASCII;
use voku\helper\EmailCheck;
use voku\helper\URLify;
use voku\helper\UTF8;

/**
 * @template-implements \IteratorAggregate<string>
 * @template-implements \ArrayAccess<array-key,string>
 */
class Stringy implements \ArrayAccess, \Countable, \IteratorAggregate, \JsonSerializable
{
    /**
     * An instance's string.
     *
     * @var string
     */
    protected $str;

    /**
     * The string's encoding, which should be one of the mbstring module's
     * supported encodings.
     *
     * @var string
     */
    protected $encoding;

    /**
     * @var UTF8
     */
    private $utf8;

    /**
     * @var ASCII
     */
    private $ascii;

    /**
     * Initializes a Stringy object and assigns both str and encoding properties
     * the supplied values. $str is cast to a string prior to assignment, and if
     * $encoding is not specified, it defaults to mb_internal_encoding(). Throws
     * an InvalidArgumentException if the first argument is an array or object
     * without a __toString method.
     *
     * @param mixed  $str      [optional] <p>Value to modify, after being cast to string. Default: ''</p>
     * @param string $encoding [optional] <p>The character encoding. Fallback: 'UTF-8'</p>
     *
     * @throws \InvalidArgumentException
     *                                   <p>if an array or object without a
     *                                   __toString method is passed as the first argument</p>
     *
     * @psalm-mutation-free
     */
    public function __construct($str = '', string $encoding = null)
    {
        if (\is_array($str)) {
            throw new \InvalidArgumentException(
                'Passed value cannot be an array'
            );
        }

        if (
            \is_object($str)
            &&
            !\method_exists($str, '__toString')
        ) {
            throw new \InvalidArgumentException(
                'Passed object must have a __toString method'
            );
        }

        $this->str = (string) $str;

        static $ASCII = null;
        if ($ASCII === null) {
            $ASCII = new ASCII();
        }
        $this->ascii = $ASCII;

        static $UTF8 = null;
        if ($UTF8 === null) {
            $UTF8 = new UTF8();
        }
        $this->utf8 = $UTF8;

        if ($encoding !== 'UTF-8') {
            $this->encoding = $this->utf8::normalize_encoding($encoding, 'UTF-8');
        } else {
            $this->encoding = $encoding;
        }
    }

    /**
     * Returns the value in $str.
     *
     * EXAMPLE: <code>
     * </code>
     *
     * @psalm-mutation-free
     *
     * @return string
     *                <p>The current value of the $str property.</p>
     */
    public function __toString()
    {
        return (string) $this->str;
    }

    /**
     * Return part of the string occurring after a specific string.
     *
     * EXAMPLE: <code>
     * s('宮本 茂')->after('本'); // ' 茂'
     * </code>
     *
     * @param string $string <p>The delimiting string.</p>
     *
     * @psalm-mutation-free
     *
     * @return static
     */
    public function after(string $string): self
    {
        $strArray = UTF8::str_split_pattern(
            $this->str,
            $string
        );

        unset($strArray[0]);

        return new static(
            \implode(' ', $strArray),
            $this->encoding
        );
    }

    /**
     * Gets the substring after the first occurrence of a separator.
     * If no match is found returns new empty Stringy object.
     *
     * EXAMPLE: <code>
     * s('</b></b>')->afterFirst('b'); // '></b>'
     * </code>
     *
     * @param string $separator
     *
     * @psalm-mutation-free
     *
     * @return static
     */
    public function afterFirst(string $separator): self
    {
        return static::create(
            $this->utf8::str_substr_after_first_separator(
                $this->str,
                $separator,
                $this->encoding
            )
        );
    }

    /**
     * Gets the substring after the first occurrence of a separator.
     * If no match is found returns new empty Stringy object.
     *
     * EXAMPLE: <code>
     * s('</B></B>')->afterFirstIgnoreCase('b'); // '></B>'
     * </code>
     *
     * @param string $separator
     *
     * @psalm-mutation-free
     *
     * @return static
     */
    public function afterFirstIgnoreCase(string $separator): self
    {
        return static::create(
            $this->utf8::str_isubstr_after_first_separator(
                $this->str,
                $separator,
                $this->encoding
            )
        );
    }

    /**
     * Gets the substring after the last occurrence of a separator.
     * If no match is found returns new empty Stringy object.
     *
     * EXAMPLE: <code>
     * s('</b></b>')->afterLast('b'); // '>'
     * </code>
     *
     * @param string $separator
     *
     * @psalm-mutation-free
     *
     * @return static
     */
    public function afterLast(string $separator): self
    {
        return static::create(
            $this->utf8::str_substr_after_last_separator(
                $this->str,
                $separator,
                $this->encoding
            )
        );
    }

    /**
     * Gets the substring after the last occurrence of a separator.
     * If no match is found returns new empty Stringy object.
     *
     * EXAMPLE: <code>
     * s('</B></B>')->afterLastIgnoreCase('b'); // '>'
     * </code>
     *
     * @param string $separator
     *
     * @psalm-mutation-free
     *
     * @return static
     */
    public function afterLastIgnoreCase(string $separator): self
    {
        return static::create(
            $this->utf8::str_isubstr_after_last_separator(
                $this->str,
                $separator,
                $this->encoding
            )
        );
    }

    /**
     * Returns a new string with $suffix appended.
     *
     * EXAMPLE: <code>
     * s('fòô')->append('bàř'); // 'fòôbàř'
     * </code>
     *
     * @param string ...$suffix <p>The string to append.</p>
     *
     * @psalm-mutation-free
     *
     * @return static
     *                <p>Object with appended $suffix.</p>
     */
    public function append(string ...$suffix): self
    {
        if (\count($suffix) <= 1) {
            /** @noinspection CallableParameterUseCaseInTypeContextInspection */
            $suffix = $suffix[0];
        } else {
            /** @noinspection CallableParameterUseCaseInTypeContextInspection */
            $suffix = \implode('', $suffix);
        }

        return static::create($this->str . $suffix, $this->encoding);
    }

    /**
     * Append an password (limited to chars that are good readable).
     *
     * EXAMPLE: <code>
     * s('')->appendPassword(8); // e.g.: '89bcdfgh'
     * </code>
     *
     * @param int $length <p>Length of the random string.</p>
     *
     * @return static
     *                <p>Object with appended password.</p>
     */
    public function appendPassword(int $length): self
    {
        return $this->appendRandomString(
            $length,
            '2346789bcdfghjkmnpqrtvwxyzBCDFGHJKLMNPQRTVWXYZ!?_#'
        );
    }

    /**
     * Append an random string.
     *
     * EXAMPLE: <code>
     * s('')->appendUniqueIdentifier(5, 'ABCDEFGHI'); // e.g.: 'CDEHI'
     * </code>
     *
     * @param int    $length        <p>Length of the random string.</p>
     * @param string $possibleChars [optional] <p>Characters string for the random selection.</p>
     *
     * @return static
     *                <p>Object with appended random string.</p>
     */
    public function appendRandomString(int $length, string $possibleChars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789'): self
    {
        $str = $this->utf8::get_random_string($length, $possibleChars);

        return $this->append($str);
    }

    /**
     * Returns a new string with $suffix appended.
     *
     * EXAMPLE: <code>
     * </code>
     *
     * @param CollectionStringy|static ...$suffix <p>The Stringy objects to append.</p>
     *
     * @psalm-param CollectionStringy<int,static>|static ...$suffix
     *
     * @psalm-mutation-free
     *
     * @return static
     *                <p>Object with appended $suffix.</p>
     */
    public function appendStringy(...$suffix): self
    {
        $suffixStr = '';
        foreach ($suffix as $suffixTmp) {
            if ($suffixTmp instanceof CollectionStringy) {
                $suffixStr .= $suffixTmp->implode('');
            } else {
                $suffixStr .= $suffixTmp->toString();
            }
        }

        return static::create($this->str . $suffixStr, $this->encoding);
    }

    /**
     * Append an unique identifier.
     *
     * EXAMPLE: <code>
     * s('')->appendUniqueIdentifier(); // e.g.: '1f3870be274f6c49b3e31a0c6728957f'
     * </code>
     *
     * @param int|string $entropyExtra [optional] <p>Extra entropy via a string or int value.</p>
     * @param bool       $md5          [optional] <p>Return the unique identifier as md5-hash? Default: true</p>
     *
     * @return static
     *                <p>Object with appended unique identifier as md5-hash.</p>
     */
    public function appendUniqueIdentifier($entropyExtra = '', bool $md5 = true): self
    {
        return $this->append(
            $this->utf8::get_unique_string($entropyExtra, $md5)
        );
    }

    /**
     * Returns the character at $index, with indexes starting at 0.
     *
     * EXAMPLE: <code>
     * s('fòôbàř')->at(3); // 'b'
     * </code>
     *
     * @param int $index <p>Position of the character.</p>
     *
     * @psalm-mutation-free
     *
     * @return static
     *                <p>The character at $index.</p>
     */
    public function at(int $index): self
    {
        return static::create($this->utf8::char_at($this->str, $index), $this->encoding);
    }

    /**
     * Decode the base64 encoded string.
     *
     * EXAMPLE: <code>
     * </code>
     *
     * @psalm-mutation-free
     *
     * @return self
     */
    public function base64Decode(): self
    {
        return static::create(
            \base64_decode($this->str, true),
            $this->encoding
        );
    }

    /**
     * Encode the string to base64.
     *
     * EXAMPLE: <code>
     * </code>
     *
     * @psalm-mutation-free
     *
     * @return self
     */
    public function base64Encode(): self
    {
        return static::create(
            \base64_encode($this->str),
            $this->encoding
        );
    }

    /**
     * Creates a hash from the string using the CRYPT_BLOWFISH algorithm.
     *
     * WARNING: Using this algorithm, will result in the ```$this->str```
     *          being truncated to a maximum length of 72 characters.
     *
     * EXAMPLE: <code>
     * </code>
     *
     * @param array<array-key, int|string> $options [optional] <p>An array of bcrypt hasing options.</p>
     *
     * @psalm-mutation-free
     *
     * @return static
     */
    public function bcrypt(array $options = []): self
    {
        return new static(
            \password_hash(
                $this->str,
                \PASSWORD_BCRYPT,
                $options
            ),
            $this->encoding
        );
    }

    /**
     * Return part of the string occurring before a specific string.
     *
     * EXAMPLE: <code>
     * </code>
     *
     * @param string $string <p>The delimiting string.</p>
     *
     * @psalm-mutation-free
     *
     * @return static
     */
    public function before(string $string): self
    {
        $strArray = UTF8::str_split_pattern(
            $this->str,
            $string,
            1
        );

        return new static(
            $strArray[0] ?? '',
            $this->encoding
        );
    }

    /**
     * Gets the substring before the first occurrence of a separator.
     * If no match is found returns new empty Stringy object.
     *
     * EXAMPLE: <code>
     * s('</b></b>')->beforeFirst('b'); // '</'
     * </code>
     *
     * @param string $separator
     *
     * @psalm-mutation-free
     *
     * @return static
     */
    public function beforeFirst(string $separator): self
    {
        return static::create(
            $this->utf8::str_substr_before_first_separator(
                $this->str,
                $separator,
                $this->encoding
            )
        );
    }

    /**
     * Gets the substring before the first occurrence of a separator.
     * If no match is found returns new empty Stringy object.
     *
     * EXAMPLE: <code>
     * s('</B></B>')->beforeFirstIgnoreCase('b'); // '</'
     * </code>
     *
     * @param string $separator
     *
     * @psalm-mutation-free
     *
     * @return static
     */
    public function beforeFirstIgnoreCase(string $separator): self
    {
        return static::create(
            $this->utf8::str_isubstr_before_first_separator(
                $this->str,
                $separator,
                $this->encoding
            )
        );
    }

    /**
     * Gets the substring before the last occurrence of a separator.
     * If no match is found returns new empty Stringy object.
     *
     * EXAMPLE: <code>
     * s('</b></b>')->beforeLast('b'); // '</b></'
     * </code>
     *
     * @param string $separator
     *
     * @psalm-mutation-free
     *
     * @return static
     */
    public function beforeLast(string $separator): self
    {
        return static::create(
            $this->utf8::str_substr_before_last_separator(
                $this->str,
                $separator,
                $this->encoding
            )
        );
    }

    /**
     * Gets the substring before the last occurrence of a separator.
     * If no match is found returns new empty Stringy object.
     *
     * EXAMPLE: <code>
     * s('</B></B>')->beforeLastIgnoreCase('b'); // '</B></'
     * </code>
     *
     * @param string $separator
     *
     * @psalm-mutation-free
     *
     * @return static
     */
    public function beforeLastIgnoreCase(string $separator): self
    {
        return static::create(
            $this->utf8::str_isubstr_before_last_separator(
                $this->str,
                $separator,
                $this->encoding
            )
        );
    }

    /**
     * Returns the substring between $start and $end, if found, or an empty
     * string. An optional offset may be supplied from which to begin the
     * search for the start string.
     *
     * EXAMPLE: <code>
     * s('{foo} and {bar}')->between('{', '}'); // 'foo'
     * </code>
     *
     * @param string $start  <p>Delimiter marking the start of the substring.</p>
     * @param string $end    <p>Delimiter marking the end of the substring.</p>
     * @param int    $offset [optional] <p>Index from which to begin the search. Default: 0</p>
     *
     * @psalm-mutation-free
     *
     * @return static
     *                <p>Object whose $str is a substring between $start and $end.</p>
     */
    public function between(string $start, string $end, int $offset = null): self
    {
        $str = $this->utf8::between(
            $this->str,
            $start,
            $end,
            (int) $offset,
            $this->encoding
        );

        return static::create($str, $this->encoding);
    }

    /**
     * Call a user function.
     *
     * EXAMPLE: <code>
     * S::create('foo bar lall')->callUserFunction(static function ($str) {
     *     return UTF8::str_limit($str, 8);
     * })->toString(); // "foo bar…"
     * </code>
     *
     * @param callable $function
     * @param mixed    ...$parameter
     *
     * @psalm-mutation-free
     *
     * @return static
     *                <p>Object having a $str changed via $function.</p>
     */
    public function callUserFunction(callable $function, ...$parameter): self
    {
        $str = $function($this->str, ...$parameter);

        return static::create(
            $str,
            $this->encoding
        );
    }

    /**
     * Returns a camelCase version of the string. Trims surrounding spaces,
     * capitalizes letters following digits, spaces, dashes and underscores,
     * and removes spaces, dashes, as well as underscores.
     *
     * EXAMPLE: <code>
     * s('Camel-Case')->camelize(); // 'camelCase'
     * </code>
     *
     * @psalm-mutation-free
     *
     * @return static
     *                <p>Object with $str in camelCase.</p>
     */
    public function camelize(): self
    {
        return static::create(
            $this->utf8::str_camelize($this->str, $this->encoding),
            $this->encoding
        );
    }

    /**
     * Returns the string with the first letter of each word capitalized,
     * except for when the word is a name which shouldn't be capitalized.
     *
     * EXAMPLE: <code>
     * s('jaap de hoop scheffer')->capitalizePersonName(); // 'Jaap de Hoop Scheffer'
     * </code>
     *
     * @psalm-mutation-free
     *
     * @return static
     *                <p>Object with $str capitalized.</p>
     */
    public function capitalizePersonalName(): self
    {
        return static::create(
            $this->utf8::str_capitalize_name($this->str),
            $this->encoding
        );
    }

    /**
     * Returns an array consisting of the characters in the string.
     *
     * EXAMPLE: <code>
     * s('fòôbàř')->chars(); // ['f', 'ò', 'ô', 'b', 'à', 'ř']
     * </code>
     *
     * @psalm-mutation-free
     *
     * @return string[]
     *                  <p>An array of string chars.</p>
     */
    public function chars(): array
    {
        /** @var string[] */
        return $this->utf8::str_split($this->str);
    }

    /**
     * Splits the string into chunks of Stringy objects.
     *
     * EXAMPLE: <code>
     * s('foobar')->chunk(3); // ['foo', 'bar']
     * </code>
     *
     * @param int $length [optional] <p>Max character length of each array element.</p>
     *
     * @psalm-mutation-free
     *
     * @return static[]
     *                  <p>An array of Stringy objects.</p>
     *
     * @psalm-return array<int,static>
     */
    public function chunk(int $length = 1): array
    {
        if ($length < 1) {
            throw new \InvalidArgumentException('The chunk length must be greater than zero.');
        }

        if ($this->str === '') {
            return [];
        }

        $chunks = $this->utf8::str_split($this->str, $length);

        /** @noinspection AlterInForeachInspection */
        foreach ($chunks as $i => &$value) {
            $value = static::create($value, $this->encoding);
        }

        /** @noinspection PhpSillyAssignmentInspection */
        /** @var static[] $chunks */
        $chunks = $chunks;

        return $chunks;
    }

    /**
     * Splits the string into chunks of Stringy objects collection.
     *
     * EXAMPLE: <code>
     * </code>
     *
     * @param int $length [optional] <p>Max character length of each array element.</p>
     *
     * @psalm-mutation-free
     *
     * @return CollectionStringy|static[]
     *                                    <p>An collection of Stringy objects.</p>
     *
     * @psalm-return CollectionStringy<int,static>
     */
    public function chunkCollection(int $length = 1): CollectionStringy
    {
        /**
         * @psalm-suppress ImpureMethodCall -> add more psalm stuff to the collection class
         */
        return CollectionStringy::create(
            $this->chunk($length)
        );
    }

    /**
     * Trims the string and replaces consecutive whitespace characters with a
     * single space. This includes tabs and newline characters, as well as
     * multibyte whitespace such as the thin space and ideographic space.
     *
     * EXAMPLE: <code>
     * s('   Ο     συγγραφέας  ')->collapseWhitespace(); // 'Ο συγγραφέας'
     * </code>
     *
     * @psalm-mutation-free
     *
     * @return static
     *                <p>Object with a trimmed $str and condensed whitespace.</p>
     */
    public function collapseWhitespace(): self
    {
        return static::create(
            $this->utf8::collapse_whitespace($this->str),
            $this->encoding
        );
    }

    /**
     * Returns true if the string contains $needle, false otherwise. By default
     * the comparison is case-sensitive, but can be made insensitive by setting
     * $caseSensitive to false.
     *
     * EXAMPLE: <code>
     * s('Ο συγγραφέας είπε')->contains('συγγραφέας'); // true
     * </code>
     *
     * @param string $needle        <p>Substring to look for.</p>
     * @param bool   $caseSensitive [optional] <p>Whether or not to enforce case-sensitivity. Default: true</p>
     *
     * @psalm-mutation-free
     *
     * @return bool
     *              <p>Whether or not $str contains $needle.</p>
     */
    public function contains(string $needle, bool $caseSensitive = true): bool
    {
        return $this->utf8::str_contains(
            $this->str,
            $needle,
            $caseSensitive
        );
    }

    /**
     * Returns true if the string contains all $needles, false otherwise. By
     * default the comparison is case-sensitive, but can be made insensitive by
     * setting $caseSensitive to false.
     *
     * EXAMPLE: <code>
     * s('foo & bar')->containsAll(['foo', 'bar']); // true
     * </code>
     *
     * @param string[] $needles       <p>SubStrings to look for.</p>
     * @param bool     $caseSensitive [optional] <p>Whether or not to enforce case-sensitivity. Default: true</p>
     *
     * @psalm-mutation-free
     *
     * @return bool
     *              <p>Whether or not $str contains $needle.</p>
     */
    public function containsAll(array $needles, bool $caseSensitive = true): bool
    {
        return $this->utf8::str_contains_all(
            $this->str,
            $needles,
            $caseSensitive
        );
    }

    /**
     * Returns true if the string contains any $needles, false otherwise. By
     * default the comparison is case-sensitive, but can be made insensitive by
     * setting $caseSensitive to false.
     *
     * EXAMPLE: <code>
     * s('str contains foo')->containsAny(['foo', 'bar']); // true
     * </code>
     *
     * @param string[] $needles       <p>SubStrings to look for.</p>
     * @param bool     $caseSensitive [optional] <p>Whether or not to enforce case-sensitivity. Default: true</p>
     *
     * @psalm-mutation-free
     *
     * @return bool
     *              <p>Whether or not $str contains $needle.</p>
     */
    public function containsAny(array $needles, bool $caseSensitive = true): bool
    {
        return $this->utf8::str_contains_any(
            $this->str,
            $needles,
            $caseSensitive
        );
    }

    /**
     * Checks if string starts with "BOM" (Byte Order Mark Character) character.
     *
     * EXAMPLE: <code>s("\xef\xbb\xbf foobar")->containsBom(); // true</code>
     *
     * @psalm-mutation-free
     *
     * @return bool
     *              <strong>true</strong> if the string has BOM at the start,<br>
     *              <strong>false</strong> otherwise
     */
    public function containsBom(): bool
    {
        return $this->utf8::string_has_bom($this->str);
    }

    /**
     * Returns the length of the string, implementing the countable interface.
     *
     * EXAMPLE: <code>
     * </code>
     *
     * @psalm-mutation-free
     *
     * @return int
     *             <p>The number of characters in the string, given the encoding.</p>
     */
    public function count(): int
    {
        return $this->length();
    }

    /**
     * Returns the number of occurrences of $substring in the given string.
     * By default, the comparison is case-sensitive, but can be made insensitive
     * by setting $caseSensitive to false.
     *
     * EXAMPLE: <code>
     * s('Ο συγγραφέας είπε')->countSubstr('α'); // 2
     * </code>
     *
     * @param string $substring     <p>The substring to search for.</p>
     * @param bool   $caseSensitive [optional] <p>Whether or not to enforce case-sensitivity. Default: true</p>
     *
     * @psalm-mutation-free
     *
     * @return int
     */
    public function countSubstr(string $substring, bool $caseSensitive = true): int
    {
        return $this->utf8::substr_count_simple(
            $this->str,
            $substring,
            $caseSensitive,
            $this->encoding
        );
    }

    /**
     * Calculates the crc32 polynomial of a string.
     *
     * EXAMPLE: <code>
     * </code>
     *
     * @psalm-mutation-free
     *
     * @return int
     */
    public function crc32(): int
    {
        return \crc32($this->str);
    }

    /**
     * Creates a Stringy object and assigns both str and encoding properties
     * the supplied values. $str is cast to a string prior to assignment, and if
     * $encoding is not specified, it defaults to mb_internal_encoding(). It
     * then returns the initialized object. Throws an InvalidArgumentException
     * if the first argument is an array or object without a __toString method.
     *
     * @param mixed  $str      [optional] <p>Value to modify, after being cast to string. Default: ''</p>
     * @param string $encoding [optional] <p>The character encoding. Fallback: 'UTF-8'</p>
     *
     * @throws \InvalidArgumentException
     *                                   <p>if an array or object without a
     *                                   __toString method is passed as the first argument</p>
     *
     * @return static
     *                <p>A Stringy object.</p>
     * @psalm-pure
     */
    public static function create($str = '', string $encoding = null): self
    {
        return new static($str, $encoding);
    }

    /**
     * One-way string encryption (hashing).
     *
     * Hash the string using the standard Unix DES-based algorithm or an
     * alternative algorithm that may be available on the system.
     *
     * PS: if you need encrypt / decrypt, please use ```static::encrypt($password)```
     *     and ```static::decrypt($password)```
     *
     * EXAMPLE: <code>
     * </code>
     *
     * @param string $salt <p>A salt string to base the hashing on.</p>
     *
     * @psalm-mutation-free
     *
     * @return static
     */
    public function crypt(string $salt): self
    {
        return new static(
            \crypt(
                $this->str,
                $salt
            ),
            $this->encoding
        );
    }

    /**
     * Returns a lowercase and trimmed string separated by dashes. Dashes are
     * inserted before uppercase characters (with the exception of the first
     * character of the string), and in place of spaces as well as underscores.
     *
     * EXAMPLE: <code>
     * s('fooBar')->dasherize(); // 'foo-bar'
     * </code>
     *
     * @psalm-mutation-free
     *
     * @return static
     *                <p>Object with a dasherized $str</p>
     */
    public function dasherize(): self
    {
        return static::create(
            $this->utf8::str_dasherize($this->str),
            $this->encoding
        );
    }

    /**
     * Decrypt the string.
     *
     * EXAMPLE: <code>
     * </code>
     *
     * @param string $password The key for decrypting
     *
     * @psalm-mutation-free
     *
     * @return static
     */
    public function decrypt(string $password): self
    {
        /**
         * @psalm-suppress ImpureMethodCall -> add more psalm stuff to vendor stuff
         */
        return new static(
            Crypto::decryptWithPassword($this->str, $password),
            $this->encoding
        );
    }

    /**
     * Returns a lowercase and trimmed string separated by the given delimiter.
     * Delimiters are inserted before uppercase characters (with the exception
     * of the first character of the string), and in place of spaces, dashes,
     * and underscores. Alpha delimiters are not converted to lowercase.
     *
     * EXAMPLE: <code>
     * s('fooBar')->delimit('::'); // 'foo::bar'
     * </code>
     *
     * @param string $delimiter <p>Sequence used to separate parts of the string.</p>
     *
     * @psalm-mutation-free
     *
     * @return static
     *                <p>Object with a delimited $str.</p>
     */
    public function delimit(string $delimiter): self
    {
        return static::create(
            $this->utf8::str_delimit($this->str, $delimiter),
            $this->encoding
        );
    }

    /**
     * Encode the given string into the given $encoding + set the internal character encoding.
     *
     * EXAMPLE: <code>
     * </code>
     *
     * @param string $new_encoding         <p>The desired character encoding.</p>
     * @param bool   $auto_detect_encoding [optional] <p>Auto-detect the current string-encoding</p>
     *
     * @psalm-mutation-free
     *
     * @return static
     */
    public function encode(string $new_encoding, bool $auto_detect_encoding = false): self
    {
        if ($auto_detect_encoding) {
            $str = $this->utf8::encode(
                $new_encoding,
                $this->str
            );
        } else {
            $str = $this->utf8::encode(
                $new_encoding,
                $this->str,
                false,
                $this->encoding
            );
        }

        return new static($str, $new_encoding);
    }

    /**
     * Encrypt the string.
     *
     * EXAMPLE: <code>
     * </code>
     *
     * @param string $password <p>The key for encrypting</p>
     *
     * @psalm-mutation-free
     *
     * @return static
     */
    public function encrypt(string $password): self
    {
        /**
         * @psalm-suppress ImpureMethodCall -> add more psalm stuff to vendor stuff
         */
        return new static(
            Crypto::encryptWithPassword($this->str, $password),
            $this->encoding
        );
    }

    /**
     * Returns true if the string ends with $substring, false otherwise. By
     * default, the comparison is case-sensitive, but can be made insensitive
     * by setting $caseSensitive to false.
     *
     * EXAMPLE: <code>
     * s('fòôbàř')->endsWith('bàř', true); // true
     * </code>
     *
     * @param string $substring     <p>The substring to look for.</p>
     * @param bool   $caseSensitive [optional] <p>Whether or not to enforce case-sensitivity. Default: true</p>
     *
     * @psalm-mutation-free
     *
     * @return bool
     *              <p>Whether or not $str ends with $substring.</p>
     */
    public function endsWith(string $substring, bool $caseSensitive = true): bool
    {
        if ($caseSensitive) {
            return $this->utf8::str_ends_with($this->str, $substring);
        }

        return $this->utf8::str_iends_with($this->str, $substring);
    }

    /**
     * Returns true if the string ends with any of $substrings, false otherwise.
     * By default, the comparison is case-sensitive, but can be made insensitive
     * by setting $caseSensitive to false.
     *
     * EXAMPLE: <code>
     * s('fòôbàř')->endsWithAny(['bàř', 'baz'], true); // true
     * </code>
     *
     * @param string[] $substrings    <p>Substrings to look for.</p>
     * @param bool     $caseSensitive [optional] <p>Whether or not to enforce case-sensitivity. Default: true</p>
     *
     * @psalm-mutation-free
     *
     * @return bool
     *              <p>Whether or not $str ends with $substring.</p>
     */
    public function endsWithAny(array $substrings, bool $caseSensitive = true): bool
    {
        if ($caseSensitive) {
            return $this->utf8::str_ends_with_any($this->str, $substrings);
        }

        return $this->utf8::str_iends_with_any($this->str, $substrings);
    }

    /**
     * Ensures that the string begins with $substring. If it doesn't, it's
     * prepended.
     *
     * EXAMPLE: <code>
     * s('foobar')->ensureLeft('http://'); // 'http://foobar'
     * </code>
     *
     * @param string $substring <p>The substring to add if not present.</p>
     *
     * @psalm-mutation-free
     *
     * @return static
     *                <p>Object with its $str prefixed by the $substring.</p>
     */
    public function ensureLeft(string $substring): self
    {
        return static::create(
            $this->utf8::str_ensure_left($this->str, $substring),
            $this->encoding
        );
    }

    /**
     * Ensures that the string ends with $substring. If it doesn't, it's appended.
     *
     * EXAMPLE: <code>
     * s('foobar')->ensureRight('.com'); // 'foobar.com'
     * </code>
     *
     * @param string $substring <p>The substring to add if not present.</p>
     *
     * @psalm-mutation-free
     *
     * @return static
     *                <p>Object with its $str suffixed by the $substring.</p>
     */
    public function ensureRight(string $substring): self
    {
        return static::create(
            $this->utf8::str_ensure_right($this->str, $substring),
            $this->encoding
        );
    }

    /**
     * Create a escape html version of the string via "htmlspecialchars()".
     *
     * EXAMPLE: <code>
     * s('<∂∆ onerror="alert(xss)">')->escape(); // '&lt;∂∆ onerror=&quot;alert(xss)&quot;&gt;'
     * </code>
     *
     * @psalm-mutation-free
     *
     * @return static
     */
    public function escape(): self
    {
        return static::create(
            $this->utf8::htmlspecialchars(
                $this->str,
                \ENT_QUOTES | \ENT_SUBSTITUTE,
                $this->encoding
            ),
            $this->encoding
        );
    }

    /**
     * Split a string by a string.
     *
     * EXAMPLE: <code>
     * </code>
     *
     * @param string $delimiter <p>The boundary string</p>
     * @param int    $limit     [optional] <p>The maximum number of elements in the exploded
     *                          collection.</p>
     *
     *   - If limit is set and positive, the returned collection will contain a maximum of limit elements with the last
     *   element containing the rest of string.
     *   - If the limit parameter is negative, all components except the last -limit are returned.
     *   - If the limit parameter is zero, then this is treated as 1
     *
     * @psalm-mutation-free
     *
     * @return array<int,static>
     */
    public function explode(string $delimiter, int $limit = \PHP_INT_MAX): array
    {
        if ($this->str === '') {
            return [];
        }

        $strings = \explode($delimiter, $this->str, $limit);
        if ($strings === false) {
            $strings = [];
        }

        return \array_map(
            function ($str) {
                return new static($str, $this->encoding);
            },
            $strings
        );
    }

    /**
     * Split a string by a string.
     *
     * EXAMPLE: <code>
     * </code>
     *
     * @param string $delimiter <p>The boundary string</p>
     * @param int    $limit     [optional] <p>The maximum number of elements in the exploded
     *                          collection.</p>
     *
     *   - If limit is set and positive, the returned collection will contain a maximum of limit elements with the last
     *   element containing the rest of string.
     *   - If the limit parameter is negative, all components except the last -limit are returned.
     *   - If the limit parameter is zero, then this is treated as 1
     *
     * @psalm-mutation-free
     *
     * @return CollectionStringy|static[]
     *                                    <p>An collection of Stringy objects.</p>
     *
     * @psalm-return CollectionStringy<int,static>
     */
    public function explodeCollection(string $delimiter, int $limit = \PHP_INT_MAX): CollectionStringy
    {
        /**
         * @psalm-suppress ImpureMethodCall -> add more psalm stuff to the collection class
         */
        return CollectionStringy::create(
            $this->explode($delimiter, $limit)
        );
    }

    /**
     * Create an extract from a sentence, so if the search-string was found, it try to centered in the output.
     *
     * EXAMPLE: <code>
     * $sentence = 'This is only a Fork of Stringy, take a look at the new features.';
     * s($sentence)->extractText('Stringy'); // '...Fork of Stringy...'
     * </code>
     *
     * @param string   $search
     * @param int|null $length                 [optional] <p>Default: null === text->length / 2</p>
     * @param string   $replacerForSkippedText [optional] <p>Default: …</p>
     *
     * @psalm-mutation-free
     *
     * @return static
     */
    public function extractText(string $search = '', int $length = null, string $replacerForSkippedText = '…'): self
    {
        return static::create(
            $this->utf8::extract_text(
                $this->str,
                $search,
                $length,
                $replacerForSkippedText,
                $this->encoding
            ),
            $this->encoding
        );
    }

    /**
     * Returns the first $n characters of the string.
     *
     * EXAMPLE: <code>
     * s('fòôbàř')->first(3); // 'fòô'
     * </code>
     *
     * @param int $n <p>Number of characters to retrieve from the start.</p>
     *
     * @psalm-mutation-free
     *
     * @return static
     *                <p>Object with its $str being the first $n chars.</p>
     */
    public function first(int $n): self
    {
        return static::create(
            $this->utf8::first_char($this->str, $n, $this->encoding),
            $this->encoding
        );
    }

    /**
     * Return a formatted string via sprintf + named parameters via array syntax.
     *
     * <p>
     * <br>
     * It will use "sprintf()" so you can use e.g.:
     * <br>
     * <br><pre>s('There are %d monkeys in the %s')->format(5, 'tree');</pre>
     * <br>
     * <br><pre>s('There are %2$d monkeys in the %1$s')->format('tree', 5);</pre>
     * <br>
     * <br>
     * But you can also use named parameter via array syntax e.g.:
     * <br>
     * <br><pre>s('There are %:count monkeys in the %:location')->format(['count' => 5, 'location' => 'tree');</pre>
     * </p>
     *
     * EXAMPLE: <code>
     * $input = 'one: %2$d, %1$s: 2, %:text_three: %3$d';
     * s($input)->format(['text_three' => '%4$s'], 'two', 1, 3, 'three'); // 'One: 1, two: 2, three: 3'
     * </code>
     *
     * @param mixed ...$args [optional]
     *
     * @psalm-mutation-free
     *
     * @return static
     *                <p>A Stringy object produced according to the formatting string
     *                format.</p>
     */
    public function format(...$args): self
    {
        // init
        $str = $this->str;

        if (\strpos($this->str, '%:') !== false) {
            $offset = null;
            $replacement = null;
            /** @noinspection AlterInForeachInspection */
            foreach ($args as $key => &$arg) {
                if (!\is_array($arg)) {
                    continue;
                }

                foreach ($arg as $name => $param) {
                    $name = (string) $name;

                    if (\strpos($name, '%:') !== 0) {
                        $nameTmp = '%:' . $name;
                    } else {
                        $nameTmp = $name;
                    }

                    if ($offset === null) {
                        $offset = \strpos($str, $nameTmp);
                    } else {
                        $offset = \strpos($str, $nameTmp, (int) $offset + \strlen((string) $replacement));
                    }
                    if ($offset === false) {
                        continue;
                    }

                    unset($arg[$name]);

                    $str = \substr_replace($str, $param, (int) $offset, \strlen($nameTmp));
                }

                unset($args[$key]);
            }
        }

        $str = \str_replace('%:', '%%:', $str);

        return static::create(
            \sprintf($str, ...$args),
            $this->encoding
        );
    }

    /**
     * Returns the encoding used by the Stringy object.
     *
     * EXAMPLE: <code>
     * s('fòôbàř', 'UTF-8')->getEncoding(); // 'UTF-8'
     * </code>
     *
     * @psalm-mutation-free
     *
     * @return string
     *                <p>The current value of the $encoding property.</p>
     */
    public function getEncoding(): string
    {
        return $this->encoding;
    }

    /**
     * Returns a new ArrayIterator, thus implementing the IteratorAggregate
     * interface. The ArrayIterator's constructor is passed an array of chars
     * in the multibyte string. This enables the use of foreach with instances
     * of Stringy\Stringy.
     *
     * EXAMPLE: <code>
     * </code>
     *
     * @psalm-mutation-free
     *
     * @return \ArrayIterator
     *                        <p>An iterator for the characters in the string.</p>
     *
     * @psalm-return \ArrayIterator<array-key,string>
     */
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->chars());
    }

    /**
     * Wrap the string after an exact number of characters.
     *
     * EXAMPLE: <code>
     * </code>
     *
     * @param int    $width <p>Number of characters at which to wrap.</p>
     * @param string $break [optional] <p>Character used to break the string. | Default: "\n"</p>
     *
     * @psalm-mutation-free
     *
     * @return static
     */
    public function hardWrap($width, $break = "\n"): self
    {
        return $this->lineWrap($width, $break, false);
    }

    /**
     * Returns true if the string contains a lower case char, false otherwise
     *
     * EXAMPLE: <code>
     * s('fòôbàř')->hasLowerCase(); // true
     * </code>
     *
     * @psalm-mutation-free
     *
     * @return bool
     *              <p>Whether or not the string contains a lower case character.</p>
     */
    public function hasLowerCase(): bool
    {
        return $this->utf8::has_lowercase($this->str);
    }

    /**
     * Returns true if the string contains an upper case char, false otherwise.
     *
     * EXAMPLE: <code>
     * s('fòôbàř')->hasUpperCase(); // false
     * </code>
     *
     * @psalm-mutation-free
     *
     * @return bool
     *              <p>Whether or not the string contains an upper case character.</p>
     */
    public function hasUpperCase(): bool
    {
        return $this->utf8::has_uppercase($this->str);
    }

    /**
     * Generate a hash value (message digest).
     *
     * EXAMPLE: <code>
     * </code>
     *
     * @see https://php.net/manual/en/function.hash.php
     *
     * @param string $algorithm
     *                          <p>Name of selected hashing algorithm (i.e. "md5", "sha256", "haval160,4", etc..)</p>
     *
     * @psalm-mutation-free
     *
     * @return static
     */
    public function hash($algorithm): self
    {
        return static::create(\hash($algorithm, $this->str), $this->encoding);
    }

    /**
     * Decode the string from hex.
     *
     * EXAMPLE: <code>
     * </code>
     *
     * @psalm-mutation-free
     *
     * @return static
     */
    public function hexDecode(): self
    {
        $string = \preg_replace_callback(
            '/\\\\x([0-9A-Fa-f]+)/',
            function (array $matched) {
                return (string) $this->utf8::hex_to_chr($matched[1]);
            },
            $this->str
        );

        return static::create(
            $string,
            $this->encoding
        );
    }

    /**
     * Encode string to hex.
     *
     * EXAMPLE: <code>
     * </code>
     *
     * @psalm-mutation-free
     *
     * @return static
     */
    public function hexEncode(): self
    {
        $string = \array_reduce(
            $this->chars(),
            function (string $str, string $char) {
                return $str . $this->utf8::chr_to_hex($char);
            },
            ''
        );

        return static::create(
            $string,
            $this->encoding
        );
    }

    /**
     * Convert all HTML entities to their applicable characters.
     *
     * EXAMPLE: <code>
     * s('&amp;')->htmlDecode(); // '&'
     * </code>
     *
     * @param int $flags [optional] <p>
     *                   A bitmask of one or more of the following flags, which specify how to handle quotes and
     *                   which document type to use. The default is ENT_COMPAT.
     *                   <table>
     *                   Available <i>flags</i> constants
     *                   <tr valign="top">
     *                   <td>Constant Name</td>
     *                   <td>Description</td>
     *                   </tr>
     *                   <tr valign="top">
     *                   <td><b>ENT_COMPAT</b></td>
     *                   <td>Will convert double-quotes and leave single-quotes alone.</td>
     *                   </tr>
     *                   <tr valign="top">
     *                   <td><b>ENT_QUOTES</b></td>
     *                   <td>Will convert both double and single quotes.</td>
     *                   </tr>
     *                   <tr valign="top">
     *                   <td><b>ENT_NOQUOTES</b></td>
     *                   <td>Will leave both double and single quotes unconverted.</td>
     *                   </tr>
     *                   <tr valign="top">
     *                   <td><b>ENT_HTML401</b></td>
     *                   <td>
     *                   Handle code as HTML 4.01.
     *                   </td>
     *                   </tr>
     *                   <tr valign="top">
     *                   <td><b>ENT_XML1</b></td>
     *                   <td>
     *                   Handle code as XML 1.
     *                   </td>
     *                   </tr>
     *                   <tr valign="top">
     *                   <td><b>ENT_XHTML</b></td>
     *                   <td>
     *                   Handle code as XHTML.
     *                   </td>
     *                   </tr>
     *                   <tr valign="top">
     *                   <td><b>ENT_HTML5</b></td>
     *                   <td>
     *                   Handle code as HTML 5.
     *                   </td>
     *                   </tr>
     *                   </table>
     *                   </p>
     *
     * @psalm-mutation-free
     *
     * @return static
     *                <p>Object with the resulting $str after being html decoded.</p>
     */
    public function htmlDecode(int $flags = \ENT_COMPAT): self
    {
        return static::create(
            $this->utf8::html_entity_decode(
                $this->str,
                $flags,
                $this->encoding
            ),
            $this->encoding
        );
    }

    /**
     * Convert all applicable characters to HTML entities.
     *
     * EXAMPLE: <code>
     * s('&')->htmlEncode(); // '&amp;'
     * </code>
     *
     * @param int $flags [optional] <p>
     *                   A bitmask of one or more of the following flags, which specify how to handle quotes and
     *                   which document type to use. The default is ENT_COMPAT.
     *                   <table>
     *                   Available <i>flags</i> constants
     *                   <tr valign="top">
     *                   <td>Constant Name</td>
     *                   <td>Description</td>
     *                   </tr>
     *                   <tr valign="top">
     *                   <td><b>ENT_COMPAT</b></td>
     *                   <td>Will convert double-quotes and leave single-quotes alone.</td>
     *                   </tr>
     *                   <tr valign="top">
     *                   <td><b>ENT_QUOTES</b></td>
     *                   <td>Will convert both double and single quotes.</td>
     *                   </tr>
     *                   <tr valign="top">
     *                   <td><b>ENT_NOQUOTES</b></td>
     *                   <td>Will leave both double and single quotes unconverted.</td>
     *                   </tr>
     *                   <tr valign="top">
     *                   <td><b>ENT_HTML401</b></td>
     *                   <td>
     *                   Handle code as HTML 4.01.
     *                   </td>
     *                   </tr>
     *                   <tr valign="top">
     *                   <td><b>ENT_XML1</b></td>
     *                   <td>
     *                   Handle code as XML 1.
     *                   </td>
     *                   </tr>
     *                   <tr valign="top">
     *                   <td><b>ENT_XHTML</b></td>
     *                   <td>
     *                   Handle code as XHTML.
     *                   </td>
     *                   </tr>
     *                   <tr valign="top">
     *                   <td><b>ENT_HTML5</b></td>
     *                   <td>
     *                   Handle code as HTML 5.
     *                   </td>
     *                   </tr>
     *                   </table>
     *                   </p>
     *
     * @psalm-mutation-free
     *
     * @return static
     *                <p>Object with the resulting $str after being html encoded.</p>
     */
    public function htmlEncode(int $flags = \ENT_COMPAT): self
    {
        return static::create(
            $this->utf8::htmlentities(
                $this->str,
                $flags,
                $this->encoding
            ),
            $this->encoding
        );
    }

    /**
     * Capitalizes the first word of the string, replaces underscores with
     * spaces, and strips '_id'.
     *
     * EXAMPLE: <code>
     * s('author_id')->humanize(); // 'Author'
     * </code>
     *
     * @psalm-mutation-free
     *
     * @return static
     *                <p>Object with a humanized $str.</p>
     */
    public function humanize(): self
    {
        return static::create(
            $this->utf8::str_humanize($this->str),
            $this->encoding
        );
    }

    /**
     * Determine if the current string exists in another string. By
     * default, the comparison is case-sensitive, but can be made insensitive
     * by setting $caseSensitive to false.
     *
     * EXAMPLE: <code>
     * </code>
     *
     * @param string $str           <p>The string to compare against.</p>
     * @param bool   $caseSensitive [optional] <p>Whether or not to enforce case-sensitivity. Default: true</p>
     *
     * @psalm-mutation-free
     *
     * @return bool
     */
    public function in(string $str, bool $caseSensitive = true): bool
    {
        if ($caseSensitive) {
            return \strpos($str, $this->str) !== false;
        }

        return \stripos($str, $this->str) !== false;
    }

    /**
     * Returns the index of the first occurrence of $needle in the string,
     * and false if not found. Accepts an optional offset from which to begin
     * the search.
     *
     * EXAMPLE: <code>
     * s('string')->indexOf('ing'); // 3
     * </code>
     *
     * @param string $needle <p>Substring to look for.</p>
     * @param int    $offset [optional] <p>Offset from which to search. Default: 0</p>
     *
     * @psalm-mutation-free
     *
     * @return false|int
     *                   <p>The occurrence's <strong>index</strong> if found, otherwise <strong>false</strong>.</p>
     */
    public function indexOf(string $needle, int $offset = 0)
    {
        return $this->utf8::strpos(
            $this->str,
            $needle,
            $offset,
            $this->encoding
        );
    }

    /**
     * Returns the index of the first occurrence of $needle in the string,
     * and false if not found. Accepts an optional offset from which to begin
     * the search.
     *
     * EXAMPLE: <code>
     * s('string')->indexOfIgnoreCase('ING'); // 3
     * </code>
     *
     * @param string $needle <p>Substring to look for.</p>
     * @param int    $offset [optional] <p>Offset from which to search. Default: 0</p>
     *
     * @psalm-mutation-free
     *
     * @return false|int
     *                   <p>The occurrence's <strong>index</strong> if found, otherwise <strong>false</strong>.</p>
     */
    public function indexOfIgnoreCase(string $needle, int $offset = 0)
    {
        return $this->utf8::stripos(
            $this->str,
            $needle,
            $offset,
            $this->encoding
        );
    }

    /**
     * Returns the index of the last occurrence of $needle in the string,
     * and false if not found. Accepts an optional offset from which to begin
     * the search. Offsets may be negative to count from the last character
     * in the string.
     *
     * EXAMPLE: <code>
     * s('foobarfoo')->indexOfLast('foo'); // 10
     * </code>
     *
     * @param string $needle <p>Substring to look for.</p>
     * @param int    $offset [optional] <p>Offset from which to search. Default: 0</p>
     *
     * @psalm-mutation-free
     *
     * @return false|int
     *                   <p>The last occurrence's <strong>index</strong> if found, otherwise <strong>false</strong>.</p>
     */
    public function indexOfLast(string $needle, int $offset = 0)
    {
        return $this->utf8::strrpos(
            $this->str,
            $needle,
            $offset,
            $this->encoding
        );
    }

    /**
     * Returns the index of the last occurrence of $needle in the string,
     * and false if not found. Accepts an optional offset from which to begin
     * the search. Offsets may be negative to count from the last character
     * in the string.
     *
     * EXAMPLE: <code>
     * s('fooBarFoo')->indexOfLastIgnoreCase('foo'); // 10
     * </code>
     *
     * @param string $needle <p>Substring to look for.</p>
     * @param int    $offset [optional] <p>Offset from which to search. Default: 0</p>
     *
     * @psalm-mutation-free
     *
     * @return false|int
     *                   <p>The last occurrence's <strong>index</strong> if found, otherwise <strong>false</strong>.</p>
     */
    public function indexOfLastIgnoreCase(string $needle, int $offset = 0)
    {
        return $this->utf8::strripos(
            $this->str,
            $needle,
            $offset,
            $this->encoding
        );
    }

    /**
     * Inserts $substring into the string at the $index provided.
     *
     * EXAMPLE: <code>
     * s('fòôbř')->insert('à', 4); // 'fòôbàř'
     * </code>
     *
     * @param string $substring <p>String to be inserted.</p>
     * @param int    $index     <p>The index at which to insert the substring.</p>
     *
     * @psalm-mutation-free
     *
     * @return static
     *                <p>Object with the resulting $str after the insertion.</p>
     */
    public function insert(string $substring, int $index): self
    {
        return static::create(
            $this->utf8::str_insert(
                $this->str,
                $substring,
                $index,
                $this->encoding
            ),
            $this->encoding
        );
    }

    /**
     * Returns true if the string contains the $pattern, otherwise false.
     *
     * WARNING: Asterisks ("*") are translated into (".*") zero-or-more regular
     * expression wildcards.
     *
     * EXAMPLE: <code>
     * s('Foo\\Bar\\Lall')->is('*\\Bar\\*'); // true
     * </code>
     *
     * @credit Originally from Laravel, thanks Taylor.
     *
     * @param string $pattern <p>The string or pattern to match against.</p>
     *
     * @psalm-mutation-free
     *
     * @return bool
     *              <p>Whether or not we match the provided pattern.</p>
     */
    public function is(string $pattern): bool
    {
        if ($this->toString() === $pattern) {
            return true;
        }

        $quotedPattern = \preg_quote($pattern, '/');
        $replaceWildCards = \str_replace('\*', '.*', $quotedPattern);

        return $this->matchesPattern('^' . $replaceWildCards . '\z');
    }

    /**
     * Returns true if the string contains only alphabetic chars, false otherwise.
     *
     * EXAMPLE: <code>
     * s('丹尼爾')->isAlpha(); // true
     * </code>
     *
     * @psalm-mutation-free
     *
     * @return bool
     *              <p>Whether or not $str contains only alphabetic chars.</p>
     */
    public function isAlpha(): bool
    {
        return $this->utf8::is_alpha($this->str);
    }

    /**
     * Returns true if the string contains only alphabetic and numeric chars, false otherwise.
     *
     * EXAMPLE: <code>
     * s('دانيال1')->isAlphanumeric(); // true
     * </code>
     *
     * @psalm-mutation-free
     *
     * @return bool
     *              <p>Whether or not $str contains only alphanumeric chars.</p>
     */
    public function isAlphanumeric(): bool
    {
        return $this->utf8::is_alphanumeric($this->str);
    }

    /**
     * Checks if a string is 7 bit ASCII.
     *
     * EXAMPLE: <code>s('白')->isAscii; // false</code>
     *
     * @psalm-mutation-free
     *
     * @return bool
     *              <p>
     *              <strong>true</strong> if it is ASCII<br>
     *              <strong>false</strong> otherwise
     *              </p>
     *
     * @noinspection GetSetMethodCorrectnessInspection
     */
    public function isAscii(): bool
    {
        return $this->utf8::is_ascii($this->str);
    }

    /**
     * Returns true if the string is base64 encoded, false otherwise.
     *
     * EXAMPLE: <code>
     * s('Zm9vYmFy')->isBase64(); // true
     * </code>
     *
     * @param bool $emptyStringIsValid
     *
     * @psalm-mutation-free
     *
     * @return bool
     *              <p>Whether or not $str is base64 encoded.</p>
     */
    public function isBase64($emptyStringIsValid = true): bool
    {
        return $this->utf8::is_base64($this->str, $emptyStringIsValid);
    }

    /**
     * Check if the input is binary... (is look like a hack).
     *
     * EXAMPLE: <code>s(01)->isBinary(); // true</code>
     *
     * @psalm-mutation-free
     *
     * @return bool
     */
    public function isBinary(): bool
    {
        return $this->utf8::is_binary($this->str);
    }

    /**
     * Returns true if the string contains only whitespace chars, false otherwise.
     *
     * EXAMPLE: <code>
     * s("\n\t  \v\f")->isBlank(); // true
     * </code>
     *
     * @psalm-mutation-free
     *
     * @return bool
     *              <p>Whether or not $str contains only whitespace characters.</p>
     */
    public function isBlank(): bool
    {
        return $this->utf8::is_blank($this->str);
    }

    /**
     * Checks if the given string is equal to any "Byte Order Mark".
     *
     * WARNING: Use "s::string_has_bom()" if you will check BOM in a string.
     *
     * EXAMPLE: <code>s->("\xef\xbb\xbf")->isBom(); // true</code>
     *
     * @psalm-mutation-free
     *
     * @return bool
     *              <p><strong>true</strong> if the $utf8_chr is Byte Order Mark, <strong>false</strong> otherwise.</p>
     */
    public function isBom(): bool
    {
        return $this->utf8::is_bom($this->str);
    }

    /**
     * Returns true if the string contains a valid E-Mail address, false otherwise.
     *
     * EXAMPLE: <code>
     * s('lars@moelleken.org')->isEmail(); // true
     * </code>
     *
     * @param bool $useExampleDomainCheck   [optional] <p>Default: false</p>
     * @param bool $useTypoInDomainCheck    [optional] <p>Default: false</p>
     * @param bool $useTemporaryDomainCheck [optional] <p>Default: false</p>
     * @param bool $useDnsCheck             [optional] <p>Default: false</p>
     *
     * @psalm-mutation-free
     *
     * @return bool
     *              <p>Whether or not $str contains a valid E-Mail address.</p>
     */
    public function isEmail(
        bool $useExampleDomainCheck = false,
        bool $useTypoInDomainCheck = false,
        bool $useTemporaryDomainCheck = false,
        bool $useDnsCheck = false
    ): bool {
        /**
         * @psalm-suppress ImpureMethodCall -> add more psalm stuff to the email-check class
         */
        return EmailCheck::isValid($this->str, $useExampleDomainCheck, $useTypoInDomainCheck, $useTemporaryDomainCheck, $useDnsCheck);
    }

    /**
     * Determine whether the string is considered to be empty.
     *
     * A variable is considered empty if it does not exist or if its value equals FALSE.
     *
     * EXAMPLE: <code>
     * s('')->isEmpty(); // true
     * </code>
     *
     * @psalm-mutation-free
     *
     * @return bool
     *              <p>Whether or not $str is empty().</p>
     */
    public function isEmpty(): bool
    {
        return $this->utf8::is_empty($this->str);
    }

    /**
     * Determine whether the string is equals to $str.
     * Alias for isEqualsCaseSensitive()
     *
     * EXAMPLE: <code>
     * s('foo')->isEquals('foo'); // true
     * </code>
     *
     * @param string|Stringy ...$str
     *
     * @psalm-mutation-free
     *
     * @return bool
     */
    public function isEquals(...$str): bool
    {
        return $this->isEqualsCaseSensitive(...$str);
    }

    /**
     * Determine whether the string is equals to $str.
     *
     * EXAMPLE: <code>
     * </code>
     *
     * @param float|int|string|Stringy ...$str <p>The string to compare.</p>
     *
     * @psalm-mutation-free
     *
     * @return bool
     *              <p>Whether or not $str is equals.</p>
     */
    public function isEqualsCaseInsensitive(...$str): bool
    {
        $strUpper = $this->toUpperCase()->str;

        foreach ($str as $strTmp) {
            /**
             * @psalm-suppress RedundantConditionGivenDocblockType - wait for union-types :)
             */
            if ($strTmp instanceof self) {
                if ($strUpper !== $strTmp->toUpperCase()->str) {
                    return false;
                }
            } elseif (\is_scalar($strTmp)) {
                if ($strUpper !== $this->utf8::strtoupper((string) $strTmp, $this->encoding)) {
                    return false;
                }
            } else {
                throw new \InvalidArgumentException('expected: int|float|string|Stringy -> given: ' . \print_r($strTmp, true) . ' [' . \gettype($strTmp) . ']');
            }
        }

        return true;
    }

    /**
     * Determine whether the string is equals to $str.
     *
     * EXAMPLE: <code>
     * </code>
     *
     * @param float|int|string|Stringy ...$str <p>The string to compare.</p>
     *
     * @psalm-mutation-free
     *
     * @return bool
     *              <p>Whether or not $str is equals.</p>
     */
    public function isEqualsCaseSensitive(...$str): bool
    {
        foreach ($str as $strTmp) {
            /**
             * @psalm-suppress RedundantConditionGivenDocblockType - wait for union-types :)
             */
            if ($strTmp instanceof self) {
                if ($this->str !== $strTmp->str) {
                    return false;
                }
            } elseif (\is_scalar($strTmp)) {
                if ($this->str !== (string) $strTmp) {
                    return false;
                }
            } else {
                throw new \InvalidArgumentException('expected: int|float|string|Stringy -> given: ' . \print_r($strTmp, true) . ' [' . \gettype($strTmp) . ']');
            }
        }

        return true;
    }

    /**
     * Returns true if the string contains only hexadecimal chars, false otherwise.
     *
     * EXAMPLE: <code>
     * s('A102F')->isHexadecimal(); // true
     * </code>
     *
     * @psalm-mutation-free
     *
     * @return bool
     *              <p>Whether or not $str contains only hexadecimal chars.</p>
     */
    public function isHexadecimal(): bool
    {
        return $this->utf8::is_hexadecimal($this->str);
    }

    /**
     * Returns true if the string contains HTML-Tags, false otherwise.
     *
     * EXAMPLE: <code>
     * s('<h1>foo</h1>')->isHtml(); // true
     * </code>
     *
     * @psalm-mutation-free
     *
     * @return bool
     *              <p>Whether or not $str contains HTML-Tags.</p>
     */
    public function isHtml(): bool
    {
        return $this->utf8::is_html($this->str);
    }

    /**
     * Returns true if the string is JSON, false otherwise. Unlike json_decode
     * in PHP 5.x, this method is consistent with PHP 7 and other JSON parsers,
     * in that an empty string is not considered valid JSON.
     *
     * EXAMPLE: <code>
     * s('{"foo":"bar"}')->isJson(); // true
     * </code>
     *
     * @param bool $onlyArrayOrObjectResultsAreValid
     *
     * @psalm-mutation-free
     *
     * @return bool
     *              <p>Whether or not $str is JSON.</p>
     */
    public function isJson($onlyArrayOrObjectResultsAreValid = false): bool
    {
        /**
         * @psalm-suppress ImpureMethodCall -> add more psalm stuff to vendor stuff?
         */
        return $this->utf8::is_json(
            $this->str,
            $onlyArrayOrObjectResultsAreValid
        );
    }

    /**
     * Returns true if the string contains only lower case chars, false otherwise.
     *
     * EXAMPLE: <code>
     * s('fòôbàř')->isLowerCase(); // true
     * </code>
     *
     * @psalm-mutation-free
     *
     * @return bool
     *              <p>Whether or not $str contains only lower case characters.</p>
     */
    public function isLowerCase(): bool
    {
        return $this->utf8::is_lowercase($this->str);
    }

    /**
     * Determine whether the string is considered to be NOT empty.
     *
     * A variable is considered NOT empty if it does exist or if its value equals TRUE.
     *
     * EXAMPLE: <code>
     * s('')->isNotEmpty(); // false
     * </code>
     *
     * @psalm-mutation-free
     *
     * @return bool
     *              <p>Whether or not $str is empty().</p>
     */
    public function isNotEmpty(): bool
    {
        return !$this->utf8::is_empty($this->str);
    }

    /**
     * Determine if the string is composed of numeric characters.
     *
     * EXAMPLE: <code>
     * </code>
     *
     * @psalm-mutation-free
     *
     * @return bool
     */
    public function isNumeric(): bool
    {
        return \is_numeric($this->str);
    }

    /**
     * Determine if the string is composed of printable (non-invisible) characters.
     *
     * EXAMPLE: <code>
     * </code>
     *
     * @psalm-mutation-free
     *
     * @return bool
     */
    public function isPrintable(): bool
    {
        return $this->utf8::is_printable($this->str);
    }

    /**
     * Determine if the string is composed of punctuation characters.
     *
     * EXAMPLE: <code>
     * </code>
     *
     * @psalm-mutation-free
     *
     * @return bool
     */
    public function isPunctuation(): bool
    {
        return $this->utf8::is_punctuation($this->str);
    }

    /**
     * Returns true if the string is serialized, false otherwise.
     *
     * EXAMPLE: <code>
     * s('a:1:{s:3:"foo";s:3:"bar";}')->isSerialized(); // true
     * </code>
     *
     * @psalm-mutation-free
     *
     * @return bool
     *              <p>Whether or not $str is serialized.</p>
     */
    public function isSerialized(): bool
    {
        return $this->utf8::is_serialized($this->str);
    }

    /**
     * Check if two strings are similar.
     *
     * EXAMPLE: <code>
     * </code>
     *
     * @param string $str                     <p>The string to compare against.</p>
     * @param float  $minPercentForSimilarity [optional] <p>The percentage of needed similarity. | Default: 80%</p>
     *
     * @psalm-mutation-free
     *
     * @return bool
     */
    public function isSimilar(string $str, float $minPercentForSimilarity = 80.0): bool
    {
        return $this->similarity($str) >= $minPercentForSimilarity;
    }

    /**
     * Returns true if the string contains only lower case chars, false
     * otherwise.
     *
     * EXAMPLE: <code>
     * s('FÒÔBÀŘ')->isUpperCase(); // true
     * </code>
     *
     * @psalm-mutation-free
     *
     * @return bool
     *              <p>Whether or not $str contains only lower case characters.</p>
     */
    public function isUpperCase(): bool
    {
        return $this->utf8::is_uppercase($this->str);
    }

    /**
     * /**
     * Check if $url is an correct url.
     *
     * @param bool $disallow_localhost
     *
     * @psalm-mutation-free
     *
     * @return bool
     */
    public function isUrl(bool $disallow_localhost = false): bool
    {
        return $this->utf8::is_url($this->str, $disallow_localhost);
    }

    /**
     * Check if the string is UTF-16.
     *
     * @psalm-mutation-free
     *
     * @return false|int
     *                   <strong>false</strong> if is't not UTF-16,<br>
     *                   <strong>1</strong> for UTF-16LE,<br>
     *                   <strong>2</strong> for UTF-16BE
     */
    public function isUtf16()
    {
        return $this->utf8::is_utf16($this->str);
    }

    /**
     * Check if the string is UTF-32.
     *
     * @psalm-mutation-free
     *
     * @return false|int
     *                   <strong>false</strong> if is't not UTF-32,<br>
     *                   <strong>1</strong> for UTF-32LE,<br>
     *                   <strong>2</strong> for UTF-32BE
     */
    public function isUtf32()
    {
        return $this->utf8::is_utf32($this->str);
    }

    /**
     * Checks whether the passed input contains only byte sequences that appear valid UTF-8.
     *
     * EXAMPLE: <code>
     * s('Iñtërnâtiônàlizætiøn')->isUtf8(); // true
     * //
     * s("Iñtërnâtiônàlizætiøn\xA0\xA1")->isUtf8(); // false
     * </code>
     *
     * @param bool $strict <p>Check also if the string is not UTF-16 or UTF-32.</p>
     *
     * @psalm-mutation-free
     *
     * @return bool
     */
    public function isUtf8(bool $strict = false): bool
    {
        return $this->utf8::is_utf8($this->str, $strict);
    }

    /**
     * Returns true if the string contains only whitespace chars, false otherwise.
     *
     * EXAMPLE: <code>
     * </code>
     *
     * @psalm-mutation-free
     *
     * @return bool
     *              <p>Whether or not $str contains only whitespace characters.</p>
     */
    public function isWhitespace(): bool
    {
        return $this->isBlank();
    }

    /**
     * Returns value which can be serialized by json_encode().
     *
     * EXAMPLE: <code>
     * </code>
     *
     * @noinspection ReturnTypeCanBeDeclaredInspection
     *
     * @psalm-mutation-free
     *
     * @return string The current value of the $str property
     */
    public function jsonSerialize()
    {
        return (string) $this;
    }

    /**
     * Convert the string to kebab-case.
     *
     * EXAMPLE: <code>
     * </code>
     *
     * @psalm-mutation-free
     *
     * @return static
     */
    public function kebabCase(): self
    {
        $words = \array_map(
            static function (self $word) {
                return $word->toLowerCase();
            },
            $this->words('', true)
        );

        return new static(\implode('-', $words), $this->encoding);
    }

    /**
     * Returns the last $n characters of the string.
     *
     * EXAMPLE: <code>
     * s('fòôbàř')->last(3); // 'bàř'
     * </code>
     *
     * @param int $n <p>Number of characters to retrieve from the end.</p>
     *
     * @psalm-mutation-free
     *
     * @return static
     *                <p>Object with its $str being the last $n chars.</p>
     */
    public function last(int $n): self
    {
        return static::create(
            $this->utf8::str_last_char(
                $this->str,
                $n,
                $this->encoding
            ),
            $this->encoding
        );
    }

    /**
     * Gets the substring after (or before via "$beforeNeedle") the last occurrence of the "$needle".
     * If no match is found returns new empty Stringy object.
     *
     * EXAMPLE: <code>
     * </code>
     *
     * @param string $needle       <p>The string to look for.</p>
     * @param bool   $beforeNeedle [optional] <p>Default: false</p>
     *
     * @psalm-mutation-free
     *
     * @return static
     */
    public function lastSubstringOf(string $needle, bool $beforeNeedle = false): self
    {
        return static::create(
            $this->utf8::str_substr_last(
                $this->str,
                $needle,
                $beforeNeedle,
                $this->encoding
            ),
            $this->encoding
        );
    }

    /**
     * Gets the substring after (or before via "$beforeNeedle") the last occurrence of the "$needle".
     * If no match is found returns new empty Stringy object.
     *
     * EXAMPLE: <code>
     * </code>
     *
     * @param string $needle       <p>The string to look for.</p>
     * @param bool   $beforeNeedle [optional] <p>Default: false</p>
     *
     * @psalm-mutation-free
     *
     * @return static
     */
    public function lastSubstringOfIgnoreCase(string $needle, bool $beforeNeedle = false): self
    {
        return static::create(
            $this->utf8::str_isubstr_last(
                $this->str,
                $needle,
                $beforeNeedle,
                $this->encoding
            ),
            $this->encoding
        );
    }

    /**
     * Returns the length of the string.
     *
     * EXAMPLE: <code>
     * s('fòôbàř')->length(); // 6
     * </code>
     *
     * @psalm-mutation-free
     *
     * @return int
     *             <p>The number of characters in $str given the encoding.</p>
     */
    public function length(): int
    {
        return (int) $this->utf8::strlen($this->str, $this->encoding);
    }

    /**
     * Line-Wrap the string after $limit, but also after the next word.
     *
     * EXAMPLE: <code>
     * </code>
     *
     * @param int         $limit           [optional] <p>The column width.</p>
     * @param string      $break           [optional] <p>The line is broken using the optional break parameter.</p>
     * @param bool        $add_final_break [optional] <p>
     *                                     If this flag is true, then the method will add a $break at the end
     *                                     of the result string.
     *                                     </p>
     * @param string|null $delimiter       [optional] <p>
     *                                     You can change the default behavior, where we split the string by newline.
     *                                     </p>
     *
     * @psalm-mutation-free
     *
     * @return static
     */
    public function lineWrap(
        int $limit,
        string $break = "\n",
        bool $add_final_break = true,
        string $delimiter = null
    ): self {
        return static::create(
            $this->utf8::wordwrap_per_line(
                $this->str,
                $limit,
                $break,
                true,
                $add_final_break,
                $delimiter
            ),
            $this->encoding
        );
    }

    /**
     * Line-Wrap the string after $limit, but also after the next word.
     *
     * EXAMPLE: <code>
     * </code>
     *
     * @param int         $limit           [optional] <p>The column width.</p>
     * @param string      $break           [optional] <p>The line is broken using the optional break parameter.</p>
     * @param bool        $add_final_break [optional] <p>
     *                                     If this flag is true, then the method will add a $break at the end
     *                                     of the result string.
     *                                     </p>
     * @param string|null $delimiter       [optional] <p>
     *                                     You can change the default behavior, where we split the string by newline.
     *                                     </p>
     *
     * @psalm-mutation-free
     *
     * @return static
     */
    public function lineWrapAfterWord(
        int $limit,
        string $break = "\n",
        bool $add_final_break = true,
        string $delimiter = null
    ): self {
        return static::create(
            $this->utf8::wordwrap_per_line(
                $this->str,
                $limit,
                $break,
                false,
                $add_final_break,
                $delimiter
            ),
            $this->encoding
        );
    }

    /**
     * Splits on newlines and carriage returns, returning an array of Stringy
     * objects corresponding to the lines in the string.
     *
     * EXAMPLE: <code>
     * s("fòô\r\nbàř\n")->lines(); // ['fòô', 'bàř', '']
     * </code>
     *
     * @psalm-mutation-free
     *
     * @return static[]
     *                  <p>An array of Stringy objects.</p>
     *
     * @psalm-return array<int,static>
     */
    public function lines(): array
    {
        if ($this->str === '') {
            return [static::create('')];
        }

        $strings = $this->utf8::str_to_lines($this->str);
        /** @noinspection AlterInForeachInspection */
        foreach ($strings as &$str) {
            $str = static::create($str, $this->encoding);
        }

        /** @noinspection PhpSillyAssignmentInspection */
        /** @var static[] $strings */
        $strings = $strings;

        return $strings;
    }

    /**
     * Splits on newlines and carriage returns, returning an array of Stringy
     * objects corresponding to the lines in the string.
     *
     * EXAMPLE: <code>
     * </code>
     *
     * @psalm-mutation-free
     *
     * @return CollectionStringy|static[]
     *                                    <p>An collection of Stringy objects.</p>
     *
     * @psalm-return CollectionStringy<int,static>
     */
    public function linesCollection(): CollectionStringy
    {
        /**
         * @psalm-suppress ImpureMethodCall -> add more psalm stuff to the collection class
         */
        return CollectionStringy::create(
            $this->lines()
        );
    }

    /**
     * Returns the longest common prefix between the string and $otherStr.
     *
     * EXAMPLE: <code>
     * s('foobar')->longestCommonPrefix('foobaz'); // 'fooba'
     * </code>
     *
     * @param string $otherStr <p>Second string for comparison.</p>
     *
     * @psalm-mutation-free
     *
     * @return static
     *                <p>Object with its $str being the longest common prefix.</p>
     */
    public function longestCommonPrefix(string $otherStr): self
    {
        return static::create(
            $this->utf8::str_longest_common_prefix(
                $this->str,
                $otherStr,
                $this->encoding
            ),
            $this->encoding
        );
    }

    /**
     * Returns the longest common substring between the string and $otherStr.
     * In the case of ties, it returns that which occurs first.
     *
     * EXAMPLE: <code>
     * s('foobar')->longestCommonSubstring('boofar'); // 'oo'
     * </code>
     *
     * @param string $otherStr <p>Second string for comparison.</p>
     *
     * @psalm-mutation-free
     *
     * @return static
     *                <p>Object with its $str being the longest common substring.</p>
     */
    public function longestCommonSubstring(string $otherStr): self
    {
        return static::create(
            $this->utf8::str_longest_common_substring(
                $this->str,
                $otherStr,
                $this->encoding
            ),
            $this->encoding
        );
    }

    /**
     * Returns the longest common suffix between the string and $otherStr.
     *
     * EXAMPLE: <code>
     * s('fòôbàř')->longestCommonSuffix('fòrbàř'); // 'bàř'
     * </code>
     *
     * @param string $otherStr <p>Second string for comparison.</p>
     *
     * @psalm-mutation-free
     *
     * @return static
     *                <p>Object with its $str being the longest common suffix.</p>
     */
    public function longestCommonSuffix(string $otherStr): self
    {
        return static::create(
            $this->utf8::str_longest_common_suffix(
                $this->str,
                $otherStr,
                $this->encoding
            ),
            $this->encoding
        );
    }

    /**
     * Converts the first character of the string to lower case.
     *
     * EXAMPLE: <code>
     * s('Σ Foo')->lowerCaseFirst(); // 'σ Foo'
     * </code>
     *
     * @psalm-mutation-free
     *
     * @return static
     *                <p>Object with the first character of $str being lower case.</p>
     */
    public function lowerCaseFirst(): self
    {
        return static::create(
            $this->utf8::lcfirst($this->str, $this->encoding),
            $this->encoding
        );
    }

    /**
     * Determine if the string matches another string regardless of case.
     * Alias for isEqualsCaseInsensitive()
     *
     * EXAMPLE: <code>
     * </code>
     *
     * @psalm-mutation-free
     *
     * @param string|Stringy ...$str
     *                               <p>The string to compare against.</p>
     *
     * @psalm-mutation-free
     *
     * @return bool
     */
    public function matchCaseInsensitive(...$str): bool
    {
        return $this->isEqualsCaseInsensitive(...$str);
    }

    /**
     * Determine if the string matches another string.
     * Alias for isEqualsCaseSensitive()
     *
     * EXAMPLE: <code>
     * </code>
     *
     * @psalm-mutation-free
     *
     * @param string|Stringy ...$str
     *                               <p>The string to compare against.</p>
     *
     * @psalm-mutation-free
     *
     * @return bool
     */
    public function matchCaseSensitive(...$str): bool
    {
        return $this->isEqualsCaseSensitive(...$str);
    }

    /**
     * Create a md5 hash from the current string.
     *
     * @psalm-mutation-free
     *
     * @return static
     */
    public function md5(): self
    {
        return static::create($this->hash('md5'), $this->encoding);
    }

    /**
     * Replace all breaks [<br> | \r\n | \r | \n | ...] into "<br>".
     *
     * EXAMPLE: <code>
     * </code>
     *
     * @return static
     */
    public function newLineToHtmlBreak(): self
    {
        return $this->removeHtmlBreak('<br>');
    }

    /**
     * Get every nth character of the string.
     *
     * EXAMPLE: <code>
     * </code>
     *
     * @param int $step   <p>The number of characters to step.</p>
     * @param int $offset [optional] <p>The string offset to start at.</p>
     *
     * @psalm-mutation-free
     *
     * @return static
     */
    public function nth(int $step, int $offset = 0): self
    {
        $length = $step - 1;
        $substring = $this->substr($offset)->toString();

        if ($substring === '') {
            return new static('', $this->encoding);
        }

        \preg_match_all(
            "/(?:^|(?:.|\p{L}|\w){" . $length . "})(.|\p{L}|\w)/u",
            $substring,
            $matches
        );

        return new static(\implode('', $matches[1] ?? []), $this->encoding);
    }

    /**
     * Returns whether or not a character exists at an index. Offsets may be
     * negative to count from the last character in the string. Implements
     * part of the ArrayAccess interface.
     *
     * EXAMPLE: <code>
     * </code>
     *
     * @param int $offset <p>The index to check.</p>
     *
     * @psalm-mutation-free
     *
     * @return bool
     *              <p>Whether or not the index exists.</p>
     */
    public function offsetExists($offset): bool
    {
        return $this->utf8::str_offset_exists(
            $this->str,
            $offset,
            $this->encoding
        );
    }

    /**
     * Returns the character at the given index. Offsets may be negative to
     * count from the last character in the string. Implements part of the
     * ArrayAccess interface, and throws an OutOfBoundsException if the index
     * does not exist.
     *
     * EXAMPLE: <code>
     * </code>
     *
     * @param int $offset <p>The <strong>index</strong> from which to retrieve the char.</p>
     *
     * @throws \OutOfBoundsException
     *                               <p>If the positive or negative offset does not exist.</p>
     *
     * @return string
     *                <p>The character at the specified index.</p>
     *
     * @psalm-mutation-free
     */
    public function offsetGet($offset): string
    {
        return $this->utf8::str_offset_get($this->str, $offset, $this->encoding);
    }

    /**
     * Implements part of the ArrayAccess interface, but throws an exception
     * when called. This maintains the immutability of Stringy objects.
     *
     * EXAMPLE: <code>
     * </code>
     *
     * @param int   $offset <p>The index of the character.</p>
     * @param mixed $value  <p>Value to set.</p>
     *
     * @throws \Exception
     *                    <p>When called.</p>
     *
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        // Stringy is immutable, cannot directly set char
        /** @noinspection ThrowRawExceptionInspection */
        throw new \Exception('Stringy object is immutable, cannot modify char');
    }

    /**
     * Implements part of the ArrayAccess interface, but throws an exception
     * when called. This maintains the immutability of Stringy objects.
     *
     * EXAMPLE: <code>
     * </code>
     *
     * @param int $offset <p>The index of the character.</p>
     *
     * @throws \Exception
     *                    <p>When called.</p>
     *
     * @return void
     */
    public function offsetUnset($offset)
    {
        // Don't allow directly modifying the string
        /** @noinspection ThrowRawExceptionInspection */
        throw new \Exception('Stringy object is immutable, cannot unset char');
    }

    /**
     * Pads the string to a given length with $padStr. If length is less than
     * or equal to the length of the string, no padding takes places. The
     * default string used for padding is a space, and the default type (one of
     * 'left', 'right', 'both') is 'right'. Throws an InvalidArgumentException
     * if $padType isn't one of those 3 values.
     *
     * EXAMPLE: <code>
     * s('fòôbàř')->pad(9, '-/', 'left'); // '-/-fòôbàř'
     * </code>
     *
     * @param int    $length  <p>Desired string length after padding.</p>
     * @param string $padStr  [optional] <p>String used to pad, defaults to space. Default: ' '</p>
     * @param string $padType [optional] <p>One of 'left', 'right', 'both'. Default: 'right'</p>
     *
     * @throws \InvalidArgumentException
     *                                   <p>If $padType isn't one of 'right', 'left' or 'both'.</p>
     *
     * @return static
     *                <p>Object with a padded $str.</p>
     *
     * @psalm-mutation-free
     */
    public function pad(int $length, string $padStr = ' ', string $padType = 'right'): self
    {
        return static::create(
            $this->utf8::str_pad(
                $this->str,
                $length,
                $padStr,
                $padType,
                $this->encoding
            )
        );
    }

    /**
     * Returns a new string of a given length such that both sides of the
     * string are padded. Alias for pad() with a $padType of 'both'.
     *
     * EXAMPLE: <code>
     * s('foo bar')->padBoth(9, ' '); // ' foo bar '
     * </code>
     *
     * @param int    $length <p>Desired string length after padding.</p>
     * @param string $padStr [optional] <p>String used to pad, defaults to space. Default: ' '</p>
     *
     * @psalm-mutation-free
     *
     * @return static
     *                <p>String with padding applied.</p>
     */
    public function padBoth(int $length, string $padStr = ' '): self
    {
        return static::create(
            $this->utf8::str_pad_both(
                $this->str,
                $length,
                $padStr,
                $this->encoding
            )
        );
    }

    /**
     * Returns a new string of a given length such that the beginning of the
     * string is padded. Alias for pad() with a $padType of 'left'.
     *
     * EXAMPLE: <code>
     * s('foo bar')->padLeft(9, ' '); // '  foo bar'
     * </code>
     *
     * @param int    $length <p>Desired string length after padding.</p>
     * @param string $padStr [optional] <p>String used to pad, defaults to space. Default: ' '</p>
     *
     * @psalm-mutation-free
     *
     * @return static
     *                <p>String with left padding.</p>
     */
    public function padLeft(int $length, string $padStr = ' '): self
    {
        return static::create(
            $this->utf8::str_pad_left(
                $this->str,
                $length,
                $padStr,
                $this->encoding
            )
        );
    }

    /**
     * Returns a new string of a given length such that the end of the string
     * is padded. Alias for pad() with a $padType of 'right'.
     *
     * EXAMPLE: <code>
     * s('foo bar')->padRight(10, '_*'); // 'foo bar_*_'
     * </code>
     *
     * @param int    $length <p>Desired string length after padding.</p>
     * @param string $padStr [optional] <p>String used to pad, defaults to space. Default: ' '</p>
     *
     * @psalm-mutation-free
     *
     * @return static
     *                <p>String with right padding.</p>
     */
    public function padRight(int $length, string $padStr = ' '): self
    {
        return static::create(
            $this->utf8::str_pad_right(
                $this->str,
                $length,
                $padStr,
                $this->encoding
            )
        );
    }

    /**
     * Convert the string to PascalCase.
     * Alias for studlyCase()
     *
     * EXAMPLE: <code>
     * </code>
     *
     * @psalm-mutation-free
     *
     * @return static
     */
    public function pascalCase(): self
    {
        return $this->studlyCase();
    }

    /**
     * Returns a new string starting with $prefix.
     *
     * EXAMPLE: <code>
     * s('bàř')->prepend('fòô'); // 'fòôbàř'
     * </code>
     *
     * @param string ...$prefix <p>The string to append.</p>
     *
     * @psalm-mutation-free
     *
     * @return static
     *                <p>Object with appended $prefix.</p>
     */
    public function prepend(string ...$prefix): self
    {
        if (\count($prefix) <= 1) {
            /** @noinspection CallableParameterUseCaseInTypeContextInspection */
            $prefix = $prefix[0];
        } else {
            /** @noinspection CallableParameterUseCaseInTypeContextInspection */
            $prefix = \implode('', $prefix);
        }

        return static::create($prefix . $this->str, $this->encoding);
    }

    /**
     * Returns a new string starting with $prefix.
     *
     * EXAMPLE: <code>
     * </code>
     *
     * @param CollectionStringy|static ...$prefix <p>The Stringy objects to append.</p>
     *
     * @psalm-param CollectionStringy<int,static>|static ...$prefix
     *
     * @psalm-mutation-free
     *
     * @return static
     *                <p>Object with appended $prefix.</p>
     */
    public function prependStringy(...$prefix): self
    {
        $prefixStr = '';
        foreach ($prefix as $prefixTmp) {
            if ($prefixTmp instanceof CollectionStringy) {
                $prefixStr .= $prefixTmp->implode('');
            } else {
                $prefixStr .= $prefixTmp->toString();
            }
        }

        return static::create($prefixStr . $this->str, $this->encoding);
    }

    /**
     * Replaces all occurrences of $pattern in $str by $replacement.
     *
     * EXAMPLE: <code>
     * s('fòô ')->regexReplace('f[òô]+\s', 'bàř'); // 'bàř'
     * s('fò')->regexReplace('(ò)', '\\1ô'); // 'fòô'
     * </code>
     *
     * @param string $pattern     <p>The regular expression pattern.</p>
     * @param string $replacement <p>The string to replace with.</p>
     * @param string $options     [optional] <p>Matching conditions to be used.</p>
     * @param string $delimiter   [optional] <p>Delimiter the the regex. Default: '/'</p>
     *
     * @psalm-mutation-free
     *
     * @return static
     *                <p>Object with the result2ing $str after the replacements.</p>
     */
    public function regexReplace(
        string $pattern,
        string $replacement,
        string $options = '',
        string $delimiter = '/'
    ): self {
        return static::create(
            $this->utf8::regex_replace(
                $this->str,
                $pattern,
                $replacement,
                $options,
                $delimiter
            ),
            $this->encoding
        );
    }

    /**
     * Remove html via "strip_tags()" from the string.
     *
     * EXAMPLE: <code>
     * s('řàb <ô>òf\', ô<br/>foo <a href="#">lall</a>')->removeHtml('<br><br/>'); // 'řàb òf\', ô<br/>foo lall'
     * </code>
     *
     * @param string $allowableTags [optional] <p>You can use the optional second parameter to specify tags which should
     *                              not be stripped. Default: null
     *                              </p>
     *
     * @psalm-mutation-free
     *
     * @return static
     */
    public function removeHtml(string $allowableTags = ''): self
    {
        return static::create(
            $this->utf8::remove_html($this->str, $allowableTags),
            $this->encoding
        );
    }

    /**
     * Remove all breaks [<br> | \r\n | \r | \n | ...] from the string.
     *
     * EXAMPLE: <code>
     * s('řàb <ô>òf\', ô<br/>foo <a href="#">lall</a>')->removeHtmlBreak(''); // 'řàb <ô>òf\', ô< foo <a href="#">lall</a>'
     * </code>
     *
     * @param string $replacement [optional] <p>Default is a empty string.</p>
     *
     * @psalm-mutation-free
     *
     * @return static
     */
    public function removeHtmlBreak(string $replacement = ''): self
    {
        return static::create(
            $this->utf8::remove_html_breaks($this->str, $replacement),
            $this->encoding
        );
    }

    /**
     * Returns a new string with the prefix $substring removed, if present.
     *
     * EXAMPLE: <code>
     * s('fòôbàř')->removeLeft('fòô'); // 'bàř'
     * </code>
     *
     * @param string $substring <p>The prefix to remove.</p>
     *
     * @psalm-mutation-free
     *
     * @return static
     *                <p>Object having a $str without the prefix $substring.</p>
     */
    public function removeLeft(string $substring): self
    {
        return static::create(
            $this->utf8::remove_left($this->str, $substring, $this->encoding),
            $this->encoding
        );
    }

    /**
     * Returns a new string with the suffix $substring removed, if present.
     *
     * EXAMPLE: <code>
     * s('fòôbàř')->removeRight('bàř'); // 'fòô'
     * </code>
     *
     * @param string $substring <p>The suffix to remove.</p>
     *
     * @psalm-mutation-free
     *
     * @return static
     *                <p>Object having a $str without the suffix $substring.</p>
     */
    public function removeRight(string $substring): self
    {
        return static::create(
            $this->utf8::remove_right($this->str, $substring, $this->encoding),
            $this->encoding
        );
    }

    /**
     * Try to remove all XSS-attacks from the string.
     *
     * EXAMPLE: <code>
     * s('<IMG SRC=&#x6A&#x61&#x76&#x61&#x73&#x63&#x72&#x69&#x70&#x74&#x3A&#x61&#x6C&#x65&#x72&#x74&#x28&#x27&#x58&#x53&#x53&#x27&#x29>')->removeXss(); // '<IMG >'
     * </code>
     *
     * @psalm-mutation-free
     *
     * @return static
     */
    public function removeXss(): self
    {
        /**
         * @var AntiXSS|null
         *
         * @psalm-suppress ImpureStaticVariable
         */
        static $antiXss = null;

        if ($antiXss === null) {
            $antiXss = new AntiXSS();
        }

        /**
         * @psalm-suppress ImpureMethodCall -> add more psalm stuff to the anti-xss class
         */
        $str = $antiXss->xss_clean($this->str);

        return static::create($str, $this->encoding);
    }

    /**
     * Returns a repeated string given a multiplier.
     *
     * EXAMPLE: <code>
     * s('α')->repeat(3); // 'ααα'
     * </code>
     *
     * @param int $multiplier <p>The number of times to repeat the string.</p>
     *
     * @psalm-mutation-free
     *
     * @return static
     *                <p>Object with a repeated str.</p>
     */
    public function repeat(int $multiplier): self
    {
        return static::create(
            \str_repeat($this->str, $multiplier),
            $this->encoding
        );
    }

    /**
     * Replaces all occurrences of $search in $str by $replacement.
     *
     * EXAMPLE: <code>
     * s('fòô bàř fòô bàř')->replace('fòô ', ''); // 'bàř bàř'
     * </code>
     *
     * @param string $search        <p>The needle to search for.</p>
     * @param string $replacement   <p>The string to replace with.</p>
     * @param bool   $caseSensitive [optional] <p>Whether or not to enforce case-sensitivity. Default: true</p>
     *
     * @psalm-mutation-free
     *
     * @return static
     *                <p>Object with the resulting $str after the replacements.</p>
     */
    public function replace(string $search, string $replacement, bool $caseSensitive = true): self
    {
        if ($search === '' && $replacement === '') {
            return static::create($this->str, $this->encoding);
        }

        if ($this->str === '' && $search === '') {
            return static::create($replacement, $this->encoding);
        }

        if ($caseSensitive) {
            return static::create(
                \str_replace($search, $replacement, $this->str),
                $this->encoding
            );
        }

        return static::create(
            $this->utf8::str_ireplace($search, $replacement, $this->str),
            $this->encoding
        );
    }

    /**
     * Replaces all occurrences of $search in $str by $replacement.
     *
     * EXAMPLE: <code>
     * s('fòô bàř lall bàř')->replaceAll(['fòÔ ', 'lall'], '', false); // 'bàř bàř'
     * </code>
     *
     * @param string[]        $search        <p>The elements to search for.</p>
     * @param string|string[] $replacement   <p>The string to replace with.</p>
     * @param bool            $caseSensitive [optional] <p>Whether or not to enforce case-sensitivity. Default: true</p>
     *
     * @psalm-mutation-free
     *
     * @return static
     *                <p>Object with the resulting $str after the replacements.</p>
     */
    public function replaceAll(array $search, $replacement, bool $caseSensitive = true): self
    {
        if ($caseSensitive) {
            return static::create(
                \str_replace($search, $replacement, $this->str),
                $this->encoding
            );
        }

        return static::create(
            $this->utf8::str_ireplace($search, $replacement, $this->str),
            $this->encoding
        );
    }

    /**
     * Replaces all occurrences of $search from the beginning of string with $replacement.
     *
     * EXAMPLE: <code>
     * s('fòô bàř fòô bàř')->replaceBeginning('fòô', ''); // ' bàř bàř'
     * </code>
     *
     * @param string $search      <p>The string to search for.</p>
     * @param string $replacement <p>The replacement.</p>
     *
     * @psalm-mutation-free
     *
     * @return static
     *                <p>Object with the resulting $str after the replacements.</p>
     */
    public function replaceBeginning(string $search, string $replacement): self
    {
        return static::create(
            $this->utf8::str_replace_beginning($this->str, $search, $replacement),
            $this->encoding
        );
    }

    /**
     * Replaces all occurrences of $search from the ending of string with $replacement.
     *
     * EXAMPLE: <code>
     * s('fòô bàř fòô bàř')->replaceEnding('bàř', ''); // 'fòô bàř fòô '
     * </code>
     *
     * @param string $search      <p>The string to search for.</p>
     * @param string $replacement <p>The replacement.</p>
     *
     * @psalm-mutation-free
     *
     * @return static
     *                <p>Object with the resulting $str after the replacements.</p>
     */
    public function replaceEnding(string $search, string $replacement): self
    {
        return static::create(
            $this->utf8::str_replace_ending($this->str, $search, $replacement),
            $this->encoding
        );
    }

    /**
     * Replaces first occurrences of $search from the beginning of string with $replacement.
     *
     * EXAMPLE: <code>
     * </code>
     *
     * @param string $search      <p>The string to search for.</p>
     * @param string $replacement <p>The replacement.</p>
     *
     * @psalm-mutation-free
     *
     * @return static
     *                <p>Object with the resulting $str after the replacements.</p>
     */
    public function replaceFirst(string $search, string $replacement): self
    {
        return static::create(
            $this->utf8::str_replace_first($search, $replacement, $this->str),
            $this->encoding
        );
    }

    /**
     * Replaces last occurrences of $search from the ending of string with $replacement.
     *
     * EXAMPLE: <code>
     * </code>
     *
     * @param string $search      <p>The string to search for.</p>
     * @param string $replacement <p>The replacement.</p>
     *
     * @psalm-mutation-free
     *
     * @return static
     *                <p>Object with the resulting $str after the replacements.</p>
     */
    public function replaceLast(string $search, string $replacement): self
    {
        return static::create(
            $this->utf8::str_replace_last($search, $replacement, $this->str),
            $this->encoding
        );
    }

    /**
     * Returns a reversed string. A multibyte version of strrev().
     *
     * EXAMPLE: <code>
     * s('fòôbàř')->reverse(); // 'řàbôòf'
     * </code>
     *
     * @psalm-mutation-free
     *
     * @return static
     *                <p>Object with a reversed $str.</p>
     */
    public function reverse(): self
    {
        return static::create($this->utf8::strrev($this->str), $this->encoding);
    }

    /**
     * Truncates the string to a given length, while ensuring that it does not
     * split words. If $substring is provided, and truncating occurs, the
     * string is further truncated so that the substring may be appended without
     * exceeding the desired length.
     *
     * EXAMPLE: <code>
     * s('What are your plans today?')->safeTruncate(22, '...'); // 'What are your plans...'
     * </code>
     *
     * @param int    $length                          <p>Desired length of the truncated string.</p>
     * @param string $substring                       [optional] <p>The substring to append if it can fit. Default: ''</p>
     * @param bool   $ignoreDoNotSplitWordsForOneWord
     *
     * @psalm-mutation-free
     *
     * @return static
     *                <p>Object with the resulting $str after truncating.</p>
     */
    public function safeTruncate(
        int $length,
        string $substring = '',
        bool $ignoreDoNotSplitWordsForOneWord = true
    ): self {
        return static::create(
            $this->utf8::str_truncate_safe(
                $this->str,
                $length,
                $substring,
                $this->encoding,
                $ignoreDoNotSplitWordsForOneWord
            ),
            $this->encoding
        );
    }

    /**
     * Set the internal character encoding.
     *
     * EXAMPLE: <code>
     * </code>
     *
     * @param string $new_encoding <p>The desired character encoding.</p>
     *
     * @psalm-mutation-free
     *
     * @return static
     */
    public function setInternalEncoding(string $new_encoding): self
    {
        return new static($this->str, $new_encoding);
    }

    /**
     * Create a sha1 hash from the current string.
     *
     * EXAMPLE: <code>
     * </code>
     *
     * @psalm-mutation-free
     *
     * @return static
     */
    public function sha1(): self
    {
        return static::create($this->hash('sha1'), $this->encoding);
    }

    /**
     * Create a sha256 hash from the current string.
     *
     * EXAMPLE: <code>
     * </code>
     *
     * @psalm-mutation-free
     *
     * @return static
     */
    public function sha256(): self
    {
        return static::create($this->hash('sha256'), $this->encoding);
    }

    /**
     * Create a sha512 hash from the current string.
     *
     * EXAMPLE: <code>
     * </code>
     *
     * @psalm-mutation-free
     *
     * @return static
     */
    public function sha512(): self
    {
        return static::create($this->hash('sha512'), $this->encoding);
    }

    /**
     * Shorten the string after $length, but also after the next word.
     *
     * EXAMPLE: <code>
     * s('this is a test')->shortenAfterWord(2, '...'); // 'this...'
     * </code>
     *
     * @param int    $length   <p>The given length.</p>
     * @param string $strAddOn [optional] <p>Default: '…'</p>
     *
     * @psalm-mutation-free
     *
     * @return static
     */
    public function shortenAfterWord(int $length, string $strAddOn = '…'): self
    {
        return static::create(
            $this->utf8::str_limit_after_word($this->str, $length, $strAddOn),
            $this->encoding
        );
    }

    /**
     * A multibyte string shuffle function. It returns a string with its
     * characters in random order.
     *
     * EXAMPLE: <code>
     * s('fòôbàř')->shuffle(); // 'àôřbòf'
     * </code>
     *
     * @return static
     *                <p>Object with a shuffled $str.</p>
     */
    public function shuffle(): self
    {
        return static::create($this->utf8::str_shuffle($this->str), $this->encoding);
    }

    /**
     * Calculate the similarity between two strings.
     *
     * EXAMPLE: <code>
     * </code>
     *
     * @param string $str <p>The delimiting string.</p>
     *
     * @psalm-mutation-free
     *
     * @return float
     */
    public function similarity(string $str): float
    {
        \similar_text($this->str, $str, $percent);

        return $percent;
    }

    /**
     * Returns the substring beginning at $start, and up to, but not including
     * the index specified by $end. If $end is omitted, the function extracts
     * the remaining string. If $end is negative, it is computed from the end
     * of the string.
     *
     * EXAMPLE: <code>
     * s('fòôbàř')->slice(3, -1); // 'bà'
     * </code>
     *
     * @param int $start <p>Initial index from which to begin extraction.</p>
     * @param int $end   [optional] <p>Index at which to end extraction. Default: null</p>
     *
     * @psalm-mutation-free
     *
     * @return static
     *                <p>Object with its $str being the extracted substring.</p>
     */
    public function slice(int $start, int $end = null): self
    {
        return static::create(
            $this->utf8::str_slice($this->str, $start, $end, $this->encoding),
            $this->encoding
        );
    }

    /**
     * Converts the string into an URL slug. This includes replacing non-ASCII
     * characters with their closest ASCII equivalents, removing remaining
     * non-ASCII and non-alphanumeric characters, and replacing whitespace with
     * $separator. The separator defaults to a single dash, and the string
     * is also converted to lowercase. The language of the source string can
     * also be supplied for language-specific transliteration.
     *
     * EXAMPLE: <code>
     * s('Using strings like fòô bàř')->slugify(); // 'using-strings-like-foo-bar'
     * </code>
     *
     * @param string                $separator             [optional] <p>The string used to replace whitespace.</p>
     * @param string                $language              [optional] <p>Language of the source string.</p>
     * @param array<string, string> $replacements          [optional] <p>A map of replaceable strings.</p>
     * @param bool                  $replace_extra_symbols [optional]  <p>Add some more replacements e.g. "£" with "
     *                                                     pound ".</p>
     * @param bool                  $use_str_to_lower      [optional] <p>Use "string to lower" for the input.</p>
     * @param bool                  $use_transliterate     [optional]  <p>Use ASCII::to_transliterate() for unknown
     *                                                     chars.</p>
     *
     * @psalm-mutation-free
     *
     * @return static
     *                <p>Object whose $str has been converted to an URL slug.</p>
     *
     * @noinspection PhpTooManyParametersInspection
     */
    public function slugify(
        string $separator = '-',
        string $language = 'en',
        array $replacements = [],
        bool $replace_extra_symbols = true,
        bool $use_str_to_lower = true,
        bool $use_transliterate = false
    ): self {
        return static::create(
            $this->ascii::to_slugify(
                $this->str,
                $separator,
                $language,
                $replacements,
                $replace_extra_symbols,
                $use_str_to_lower,
                $use_transliterate
            ),
            $this->encoding
        );
    }

    /**
     * Convert the string to snake_case.
     *
     * EXAMPLE: <code>
     * </code>
     *
     * @psalm-mutation-free
     *
     * @return static
     */
    public function snakeCase(): self
    {
        $words = \array_map(
            static function (self $word) {
                return $word->toLowerCase();
            },
            $this->words('', true)
        );

        return new static(\implode('_', $words), $this->encoding);
    }

    /**
     * Convert a string to snake_case.
     *
     * EXAMPLE: <code>
     * s('foo1 Bar')->snakeize(); // 'foo_1_bar'
     * </code>
     *
     * @psalm-mutation-free
     *
     * @return static
     *                <p>Object with $str in snake_case.</p>
     */
    public function snakeize(): self
    {
        return static::create(
            $this->utf8::str_snakeize($this->str, $this->encoding),
            $this->encoding
        );
    }

    /**
     * Wrap the string after the first whitespace character after a given number
     * of characters.
     *
     * EXAMPLE: <code>
     * </code>
     *
     * @param int    $width <p>Number of characters at which to wrap.</p>
     * @param string $break [optional] <p>Character used to break the string. | Default "\n"</p>
     *
     * @psalm-mutation-free
     *
     * @return static
     */
    public function softWrap(int $width, string $break = "\n"): self
    {
        return $this->lineWrapAfterWord($width, $break, false);
    }

    /**
     * Splits the string with the provided regular expression, returning an
     * array of Stringy objects. An optional integer $limit will truncate the
     * results.
     *
     * EXAMPLE: <code>
     * s('foo,bar,baz')->split(',', 2); // ['foo', 'bar']
     * </code>
     *
     * @param string $pattern <p>The regex with which to split the string.</p>
     * @param int    $limit   [optional] <p>Maximum number of results to return. Default: -1 === no
     *                        limit</p>
     *
     * @psalm-mutation-free
     *
     * @return static[]
     *                  <p>An array of Stringy objects.</p>
     *
     * @psalm-return array<int,static>
     */
    public function split(string $pattern, int $limit = null): array
    {
        if ($this->str === '') {
            return [];
        }

        if ($limit === null) {
            $limit = -1;
        }

        $array = $this->utf8::str_split_pattern($this->str, $pattern, $limit);
        /** @noinspection AlterInForeachInspection */
        foreach ($array as $i => &$value) {
            $value = static::create($value, $this->encoding);
        }

        /** @noinspection PhpSillyAssignmentInspection */
        /** @var static[] $array */
        $array = $array;

        return $array;
    }

    /**
     * Splits the string with the provided regular expression, returning an
     * collection of Stringy objects. An optional integer $limit will truncate the
     * results.
     *
     * EXAMPLE: <code>
     * </code>
     *
     * @param string $pattern <p>The regex with which to split the string.</p>
     * @param int    $limit   [optional] <p>Maximum number of results to return. Default: -1 === no
     *                        limit</p>
     *
     * @psalm-mutation-free
     *
     * @return CollectionStringy|static[]
     *                                    <p>An collection of Stringy objects.</p>
     *
     * @psalm-return CollectionStringy<int,static>
     */
    public function splitCollection(string $pattern, int $limit = null): CollectionStringy
    {
        /**
         * @psalm-suppress ImpureMethodCall -> add more psalm stuff to the collection class
         */
        return CollectionStringy::create(
            $this->split($pattern, $limit)
        );
    }

    /**
     * Returns true if the string begins with $substring, false otherwise. By
     * default, the comparison is case-sensitive, but can be made insensitive
     * by setting $caseSensitive to false.
     *
     * EXAMPLE: <code>
     * s('FÒÔbàřbaz')->startsWith('fòôbàř', false); // true
     * </code>
     *
     * @param string $substring     <p>The substring to look for.</p>
     * @param bool   $caseSensitive [optional] <p>Whether or not to enforce case-sensitivity. Default: true</p>
     *
     * @psalm-mutation-free
     *
     * @return bool
     *              <p>Whether or not $str starts with $substring.</p>
     */
    public function startsWith(string $substring, bool $caseSensitive = true): bool
    {
        if ($caseSensitive) {
            return $this->utf8::str_starts_with($this->str, $substring);
        }

        return $this->utf8::str_istarts_with($this->str, $substring);
    }

    /**
     * Returns true if the string begins with any of $substrings, false otherwise.
     * By default the comparison is case-sensitive, but can be made insensitive by
     * setting $caseSensitive to false.
     *
     * EXAMPLE: <code>
     * s('FÒÔbàřbaz')->startsWithAny(['fòô', 'bàř'], false); // true
     * </code>
     *
     * @param string[] $substrings    <p>Substrings to look for.</p>
     * @param bool     $caseSensitive [optional] <p>Whether or not to enforce case-sensitivity. Default: true</p>
     *
     * @psalm-mutation-free
     *
     * @return bool
     *              <p>Whether or not $str starts with $substring.</p>
     */
    public function startsWithAny(array $substrings, bool $caseSensitive = true): bool
    {
        if ($caseSensitive) {
            return $this->utf8::str_starts_with_any($this->str, $substrings);
        }

        return $this->utf8::str_istarts_with_any($this->str, $substrings);
    }

    /**
     * Remove one or more strings from the string.
     *
     * EXAMPLE: <code>
     * </code>
     *
     * @param string|string[] $search One or more strings to be removed
     *
     * @psalm-mutation-free
     *
     * @return static
     */
    public function strip($search): self
    {
        if (\is_array($search)) {
            return $this->replaceAll($search, '');
        }

        return $this->replace($search, '');
    }

    /**
     * Strip all whitespace characters. This includes tabs and newline characters,
     * as well as multibyte whitespace such as the thin space and ideographic space.
     *
     * EXAMPLE: <code>
     * s('   Ο     συγγραφέας  ')->stripWhitespace(); // 'Οσυγγραφέας'
     * </code>
     *
     * @psalm-mutation-free
     *
     * @return static
     */
    public function stripWhitespace(): self
    {
        return static::create(
            $this->utf8::strip_whitespace($this->str),
            $this->encoding
        );
    }

    /**
     * Remove css media-queries.
     *
     * EXAMPLE: <code>
     * s('test @media (min-width:660px){ .des-cla #mv-tiles{width:480px} } test ')->stripeCssMediaQueries(); // 'test  test '
     * </code>
     *
     * @psalm-mutation-free
     *
     * @return static
     */
    public function stripeCssMediaQueries(): self
    {
        return static::create(
            $this->utf8::css_stripe_media_queries($this->str),
            $this->encoding
        );
    }

    /**
     * Remove empty html-tag.
     *
     * EXAMPLE: <code>
     * s('foo<h1></h1>bar')->stripeEmptyHtmlTags(); // 'foobar'
     * </code>
     *
     * @psalm-mutation-free
     *
     * @return static
     */
    public function stripeEmptyHtmlTags(): self
    {
        return static::create(
            $this->utf8::html_stripe_empty_tags($this->str),
            $this->encoding
        );
    }

    /**
     * Convert the string to StudlyCase.
     *
     * EXAMPLE: <code>
     * </code>
     *
     * @psalm-mutation-free
     *
     * @return static
     */
    public function studlyCase(): self
    {
        $words = \array_map(
            static function (self $word) {
                return $word->substr(0, 1)
                    ->toUpperCase()
                    ->appendStringy($word->substr(1));
            },
            $this->words('', true)
        );

        return new static(\implode('', $words), $this->encoding);
    }

    /**
     * Returns the substring beginning at $start with the specified $length.
     * It differs from the $this->utf8::substr() function in that providing a $length of
     * null will return the rest of the string, rather than an empty string.
     *
     * EXAMPLE: <code>
     * </code>
     *
     * @param int $start  <p>Position of the first character to use.</p>
     * @param int $length [optional] <p>Maximum number of characters used. Default: null</p>
     *
     * @psalm-mutation-free
     *
     * @return static
     *                <p>Object with its $str being the substring.</p>
     */
    public function substr(int $start, int $length = null): self
    {
        return static::create(
            $this->utf8::substr(
                $this->str,
                $start,
                $length,
                $this->encoding
            ),
            $this->encoding
        );
    }

    /**
     * Return part of the string.
     * Alias for substr()
     *
     * EXAMPLE: <code>
     * s('fòôbàř')->substring(2, 3); // 'ôbà'
     * </code>
     *
     * @param int $start  <p>Starting position of the substring.</p>
     * @param int $length [optional] <p>Length of substring.</p>
     *
     * @psalm-mutation-free
     *
     * @return static
     */
    public function substring(int $start, int $length = null): self
    {
        if ($length === null) {
            return $this->substr($start);
        }

        return $this->substr($start, $length);
    }

    /**
     * Gets the substring after (or before via "$beforeNeedle") the first occurrence of the "$needle".
     * If no match is found returns new empty Stringy object.
     *
     * EXAMPLE: <code>
     * </code>
     *
     * @param string $needle       <p>The string to look for.</p>
     * @param bool   $beforeNeedle [optional] <p>Default: false</p>
     *
     * @psalm-mutation-free
     *
     * @return static
     */
    public function substringOf(string $needle, bool $beforeNeedle = false): self
    {
        return static::create(
            $this->utf8::str_substr_first(
                $this->str,
                $needle,
                $beforeNeedle,
                $this->encoding
            ),
            $this->encoding
        );
    }

    /**
     * Gets the substring after (or before via "$beforeNeedle") the first occurrence of the "$needle".
     * If no match is found returns new empty Stringy object.
     *
     * EXAMPLE: <code>
     * </code>
     *
     * @param string $needle       <p>The string to look for.</p>
     * @param bool   $beforeNeedle [optional] <p>Default: false</p>
     *
     * @psalm-mutation-free
     *
     * @return static
     */
    public function substringOfIgnoreCase(string $needle, bool $beforeNeedle = false): self
    {
        return static::create(
            $this->utf8::str_isubstr_first(
                $this->str,
                $needle,
                $beforeNeedle,
                $this->encoding
            ),
            $this->encoding
        );
    }

    /**
     * Surrounds $str with the given substring.
     *
     * EXAMPLE: <code>
     * s(' ͜ ')->surround('ʘ'); // 'ʘ ͜ ʘ'
     * </code>
     *
     * @param string $substring <p>The substring to add to both sides.</P>
     *
     * @psalm-mutation-free
     *
     * @return static
     *                <p>Object whose $str had the substring both prepended and appended.</p>
     */
    public function surround(string $substring): self
    {
        return static::create(
            $substring . $this->str . $substring,
            $this->encoding
        );
    }

    /**
     * Returns a case swapped version of the string.
     *
     * EXAMPLE: <code>
     * s('Ντανιλ')->swapCase(); // 'νΤΑΝΙΛ'
     * </code>
     *
     * @psalm-mutation-free
     *
     * @return static
     *                <p>Object whose $str has each character's case swapped.</P>
     */
    public function swapCase(): self
    {
        return static::create(
            $this->utf8::swapCase($this->str, $this->encoding),
            $this->encoding
        );
    }

    /**
     * Returns a string with smart quotes, ellipsis characters, and dashes from
     * Windows-1252 (commonly used in Word documents) replaced by their ASCII
     * equivalents.
     *
     * EXAMPLE: <code>
     * s('“I see…”')->tidy(); // '"I see..."'
     * </code>
     *
     * @psalm-mutation-free
     *
     * @return static
     *                <p>Object whose $str has those characters removed.</p>
     */
    public function tidy(): self
    {
        return static::create(
            $this->ascii::normalize_msword($this->str),
            $this->encoding
        );
    }

    /**
     * Returns a trimmed string with the first letter of each word capitalized.
     * Also accepts an array, $ignore, allowing you to list words not to be
     * capitalized.
     *
     * EXAMPLE: <code>
     * $ignore = ['at', 'by', 'for', 'in', 'of', 'on', 'out', 'to', 'the'];
     * s('i like to watch television')->titleize($ignore); // 'I Like to Watch Television'
     * </code>
     *
     * @param array|string[]|null $ignore            [optional] <p>An array of words not to capitalize or null.
     *                                               Default: null</p>
     * @param string|null         $word_define_chars [optional] <p>An string of chars that will be used as whitespace
     *                                               separator === words.</p>
     * @param string|null         $language          [optional] <p>Language of the source string.</p>
     *
     * @psalm-mutation-free
     *
     * @return static
     *                <p>Object with a titleized $str.</p>
     */
    public function titleize(
        array $ignore = null,
        string $word_define_chars = null,
        string $language = null
    ): self {
        return static::create(
            $this->utf8::str_titleize(
                $this->str,
                $ignore,
                $this->encoding,
                false,
                $language,
                false,
                true,
                $word_define_chars
            ),
            $this->encoding
        );
    }

    /**
     * Returns a trimmed string in proper title case: Also accepts an array, $ignore, allowing you to list words not to
     * be capitalized.
     *
     * EXAMPLE: <code>
     * </code>
     *
     * Adapted from John Gruber's script.
     *
     * @see https://gist.github.com/gruber/9f9e8650d68b13ce4d78
     *
     * @param string[] $ignore <p>An array of words not to capitalize.</p>
     *
     * @psalm-mutation-free
     *
     * @return static
     *                <p>Object with a titleized $str</p>
     */
    public function titleizeForHumans(array $ignore = []): self
    {
        return static::create(
            $this->utf8::str_titleize_for_humans(
                $this->str,
                $ignore,
                $this->encoding
            ),
            $this->encoding
        );
    }

    /**
     * Returns an ASCII version of the string. A set of non-ASCII characters are
     * replaced with their closest ASCII counterparts, and the rest are removed
     * by default. The language or locale of the source string can be supplied
     * for language-specific transliteration in any of the following formats:
     * en, en_GB, or en-GB. For example, passing "de" results in "äöü" mapping
     * to "aeoeue" rather than "aou" as in other languages.
     *
     * EXAMPLE: <code>
     * s('fòôbàř')->toAscii(); // 'foobar'
     * </code>
     *
     * @param string $language          [optional] <p>Language of the source string.</p>
     * @param bool   $removeUnsupported [optional] <p>Whether or not to remove the
     *                                  unsupported characters.</p>
     *
     * @psalm-mutation-free
     *
     * @return static
     *                <p>Object whose $str contains only ASCII characters.</p>
     */
    public function toAscii(string $language = 'en', bool $removeUnsupported = true): self
    {
        return static::create(
            $this->ascii::to_ascii(
                $this->str,
                $language,
                $removeUnsupported
            ),
            $this->encoding
        );
    }

    /**
     * Returns a boolean representation of the given logical string value.
     * For example, <strong>'true', '1', 'on' and 'yes'</strong> will return true. <strong>'false', '0',
     * 'off', and 'no'</strong> will return false. In all instances, case is ignored.
     * For other numeric strings, their sign will determine the return value.
     * In addition, blank strings consisting of only whitespace will return
     * false. For all other strings, the return value is a result of a
     * boolean cast.
     *
     * EXAMPLE: <code>
     * s('OFF')->toBoolean(); // false
     * </code>
     *
     * @psalm-mutation-free
     *
     * @return bool
     *              <p>A boolean value for the string.</p>
     */
    public function toBoolean(): bool
    {
        /**
         * @psalm-suppress ArgumentTypeCoercion -> maybe the string looks like an int ;)
         * @phpstan-ignore-next-line
         */
        return $this->utf8::to_boolean($this->str);
    }

    /**
     * Converts all characters in the string to lowercase.
     *
     * EXAMPLE: <code>
     * s('FÒÔBÀŘ')->toLowerCase(); // 'fòôbàř'
     * </code>
     *
     * @param bool        $tryToKeepStringLength [optional] <p>true === try to keep the string length: e.g. ẞ -> ß</p>
     * @param string|null $lang                  [optional] <p>Set the language for special cases: az, el, lt, tr</p>
     *
     * @psalm-mutation-free
     *
     * @return static
     *                <p>Object with all characters of $str being lowercase.</p>
     */
    public function toLowerCase($tryToKeepStringLength = false, $lang = null): self
    {
        return static::create(
            $this->utf8::strtolower(
                $this->str,
                $this->encoding,
                false,
                $lang,
                $tryToKeepStringLength
            ),
            $this->encoding
        );
    }

    /**
     * Converts each tab in the string to some number of spaces, as defined by
     * $tabLength. By default, each tab is converted to 4 consecutive spaces.
     *
     * EXAMPLE: <code>
     * s(' String speech = "Hi"')->toSpaces(); // '    String speech = "Hi"'
     * </code>
     *
     * @param int $tabLength [optional] <p>Number of spaces to replace each tab with. Default: 4</p>
     *
     * @psalm-mutation-free
     *
     * @return static
     *                <p>Object whose $str has had tabs switched to spaces.</p>
     */
    public function toSpaces(int $tabLength = 4): self
    {
        if ($tabLength === 4) {
            $tab = '    ';
        } elseif ($tabLength === 2) {
            $tab = '  ';
        } else {
            $tab = \str_repeat(' ', $tabLength);
        }

        return static::create(
            \str_replace("\t", $tab, $this->str),
            $this->encoding
        );
    }

    /**
     * Return Stringy object as string, but you can also use (string) for automatically casting the object into a
     * string.
     *
     * EXAMPLE: <code>
     * s('fòôbàř')->toString(); // 'fòôbàř'
     * </code>
     *
     * @psalm-mutation-free
     *
     * @return string
     */
    public function toString(): string
    {
        return (string) $this->str;
    }

    /**
     * Converts each occurrence of some consecutive number of spaces, as
     * defined by $tabLength, to a tab. By default, each 4 consecutive spaces
     * are converted to a tab.
     *
     * EXAMPLE: <code>
     * s('    fòô    bàř')->toTabs(); // '   fòô bàř'
     * </code>
     *
     * @param int $tabLength [optional] <p>Number of spaces to replace with a tab. Default: 4</p>
     *
     * @psalm-mutation-free
     *
     * @return static
     *                <p>Object whose $str has had spaces switched to tabs.</p>
     */
    public function toTabs(int $tabLength = 4): self
    {
        if ($tabLength === 4) {
            $tab = '    ';
        } elseif ($tabLength === 2) {
            $tab = '  ';
        } else {
            $tab = \str_repeat(' ', $tabLength);
        }

        return static::create(
            \str_replace($tab, "\t", $this->str),
            $this->encoding
        );
    }

    /**
     * Converts the first character of each word in the string to uppercase
     * and all other chars to lowercase.
     *
     * EXAMPLE: <code>
     * s('fòô bàř')->toTitleCase(); // 'Fòô Bàř'
     * </code>
     *
     * @psalm-mutation-free
     *
     * @return static
     *                <p>Object with all characters of $str being title-cased.</p>
     */
    public function toTitleCase(): self
    {
        return static::create(
            $this->utf8::titlecase($this->str, $this->encoding),
            $this->encoding
        );
    }

    /**
     * Returns an ASCII version of the string. A set of non-ASCII characters are
     * replaced with their closest ASCII counterparts, and the rest are removed
     * unless instructed otherwise.
     *
     * EXAMPLE: <code>
     * </code>
     *
     * @param bool   $strict  [optional] <p>Use "transliterator_transliterate()" from PHP-Intl | WARNING: bad
     *                        performance | Default: false</p>
     * @param string $unknown [optional] <p>Character use if character unknown. (default is ?)</p>
     *
     * @psalm-mutation-free
     *
     * @return static
     *                <p>Object whose $str contains only ASCII characters.</p>
     */
    public function toTransliterate(bool $strict = false, string $unknown = '?'): self
    {
        return static::create(
            $this->ascii::to_transliterate($this->str, $unknown, $strict),
            $this->encoding
        );
    }

    /**
     * Converts all characters in the string to uppercase.
     *
     * EXAMPLE: <code>
     * s('fòôbàř')->toUpperCase(); // 'FÒÔBÀŘ'
     * </code>
     *
     * @param bool        $tryToKeepStringLength [optional] <p>true === try to keep the string length: e.g. ẞ -> ß</p>
     * @param string|null $lang                  [optional] <p>Set the language for special cases: az, el, lt, tr</p>
     *
     * @psalm-mutation-free
     *
     * @return static
     *                <p>Object with all characters of $str being uppercase.</p>
     */
    public function toUpperCase($tryToKeepStringLength = false, $lang = null): self
    {
        return static::create(
            $this->utf8::strtoupper($this->str, $this->encoding, false, $lang, $tryToKeepStringLength),
            $this->encoding
        );
    }

    /**
     * Returns a string with whitespace removed from the start and end of the
     * string. Supports the removal of unicode whitespace. Accepts an optional
     * string of characters to strip instead of the defaults.
     *
     * EXAMPLE: <code>
     * s('  fòôbàř  ')->trim(); // 'fòôbàř'
     * </code>
     *
     * @param string $chars [optional] <p>String of characters to strip. Default: null</p>
     *
     * @psalm-mutation-free
     *
     * @return static
     *                <p>Object with a trimmed $str.</p>
     */
    public function trim(string $chars = null): self
    {
        return static::create(
            $this->utf8::trim($this->str, $chars),
            $this->encoding
        );
    }

    /**
     * Returns a string with whitespace removed from the start of the string.
     * Supports the removal of unicode whitespace. Accepts an optional
     * string of characters to strip instead of the defaults.
     *
     * EXAMPLE: <code>
     * s('  fòôbàř  ')->trimLeft(); // 'fòôbàř  '
     * </code>
     *
     * @param string $chars [optional] <p>Optional string of characters to strip. Default: null</p>
     *
     * @psalm-mutation-free
     *
     * @return static
     *                <p>Object with a trimmed $str.</p>
     */
    public function trimLeft(string $chars = null): self
    {
        return static::create(
            $this->utf8::ltrim($this->str, $chars),
            $this->encoding
        );
    }

    /**
     * Returns a string with whitespace removed from the end of the string.
     * Supports the removal of unicode whitespace. Accepts an optional
     * string of characters to strip instead of the defaults.
     *
     * EXAMPLE: <code>
     * s('  fòôbàř  ')->trimRight(); // '  fòôbàř'
     * </code>
     *
     * @param string $chars [optional] <p>Optional string of characters to strip. Default: null</p>
     *
     * @psalm-mutation-free
     *
     * @return static
     *                <p>Object with a trimmed $str.</p>
     */
    public function trimRight(string $chars = null): self
    {
        return static::create(
            $this->utf8::rtrim($this->str, $chars),
            $this->encoding
        );
    }

    /**
     * Truncates the string to a given length. If $substring is provided, and
     * truncating occurs, the string is further truncated so that the substring
     * may be appended without exceeding the desired length.
     *
     * EXAMPLE: <code>
     * s('What are your plans today?')->truncate(19, '...'); // 'What are your pl...'
     * </code>
     *
     * @param int    $length    <p>Desired length of the truncated string.</p>
     * @param string $substring [optional] <p>The substring to append if it can fit. Default: ''</p>
     *
     * @psalm-mutation-free
     *
     * @return static
     *                <p>Object with the resulting $str after truncating.</p>
     */
    public function truncate(int $length, string $substring = ''): self
    {
        return static::create(
            $this->utf8::str_truncate($this->str, $length, $substring, $this->encoding),
            $this->encoding
        );
    }

    /**
     * Returns a lowercase and trimmed string separated by underscores.
     * Underscores are inserted before uppercase characters (with the exception
     * of the first character of the string), and in place of spaces as well as
     * dashes.
     *
     * EXAMPLE: <code>
     * s('TestUCase')->underscored(); // 'test_u_case'
     * </code>
     *
     * @psalm-mutation-free
     *
     * @return static
     *                <p>Object with an underscored $str.</p>
     */
    public function underscored(): self
    {
        return $this->delimit('_');
    }

    /**
     * Returns an UpperCamelCase version of the supplied string. It trims
     * surrounding spaces, capitalizes letters following digits, spaces, dashes
     * and underscores, and removes spaces, dashes, underscores.
     *
     * EXAMPLE: <code>
     * s('Upper Camel-Case')->upperCamelize(); // 'UpperCamelCase'
     * </code>
     *
     * @psalm-mutation-free
     *
     * @return static
     *                <p>Object with $str in UpperCamelCase.</p>
     */
    public function upperCamelize(): self
    {
        return static::create(
            $this->utf8::str_upper_camelize($this->str, $this->encoding),
            $this->encoding
        );
    }

    /**
     * Converts the first character of the supplied string to upper case.
     *
     * EXAMPLE: <code>
     * s('σ foo')->upperCaseFirst(); // 'Σ foo'
     * </code>
     *
     * @psalm-mutation-free
     *
     * @return static
     *                <p>Object with the first character of $str being upper case.</p>
     */
    public function upperCaseFirst(): self
    {
        return static::create($this->utf8::ucfirst($this->str, $this->encoding), $this->encoding);
    }

    /**
     * Simple url-decoding.
     *
     * e.g:
     * 'test+test' => 'test test'
     *
     * EXAMPLE: <code>
     * </code>
     *
     * @psalm-mutation-free
     *
     * @return static
     */
    public function urlDecode(): self
    {
        return static::create(\urldecode($this->str));
    }

    /**
     * Multi url-decoding + decode HTML entity + fix urlencoded-win1252-chars.
     *
     * e.g:
     * 'test+test'                     => 'test test'
     * 'D&#252;sseldorf'               => 'Düsseldorf'
     * 'D%FCsseldorf'                  => 'Düsseldorf'
     * 'D&#xFC;sseldorf'               => 'Düsseldorf'
     * 'D%26%23xFC%3Bsseldorf'         => 'Düsseldorf'
     * 'DÃ¼sseldorf'                   => 'Düsseldorf'
     * 'D%C3%BCsseldorf'               => 'Düsseldorf'
     * 'D%C3%83%C2%BCsseldorf'         => 'Düsseldorf'
     * 'D%25C3%2583%25C2%25BCsseldorf' => 'Düsseldorf'
     *
     * EXAMPLE: <code>
     * </code>
     *
     * @psalm-mutation-free
     *
     * @return static
     */
    public function urlDecodeMulti(): self
    {
        return static::create($this->utf8::urldecode($this->str));
    }

    /**
     * Simple url-decoding.
     *
     * e.g:
     * 'test+test' => 'test+test
     *
     * EXAMPLE: <code>
     * </code>
     *
     * @psalm-mutation-free
     *
     * @return static
     */
    public function urlDecodeRaw(): self
    {
        return static::create(\rawurldecode($this->str));
    }

    /**
     * Multi url-decoding + decode HTML entity + fix urlencoded-win1252-chars.
     *
     * e.g:
     * 'test+test'                     => 'test+test'
     * 'D&#252;sseldorf'               => 'Düsseldorf'
     * 'D%FCsseldorf'                  => 'Düsseldorf'
     * 'D&#xFC;sseldorf'               => 'Düsseldorf'
     * 'D%26%23xFC%3Bsseldorf'         => 'Düsseldorf'
     * 'DÃ¼sseldorf'                   => 'Düsseldorf'
     * 'D%C3%BCsseldorf'               => 'Düsseldorf'
     * 'D%C3%83%C2%BCsseldorf'         => 'Düsseldorf'
     * 'D%25C3%2583%25C2%25BCsseldorf' => 'Düsseldorf'
     *
     * EXAMPLE: <code>
     * </code>
     *
     * @psalm-mutation-free
     *
     * @return static
     */
    public function urlDecodeRawMulti(): self
    {
        return static::create($this->utf8::rawurldecode($this->str));
    }

    /**
     * Simple url-encoding.
     *
     * e.g:
     * 'test test' => 'test+test'
     *
     * EXAMPLE: <code>
     * </code>
     *
     * @psalm-mutation-free
     *
     * @return static
     */
    public function urlEncode(): self
    {
        return static::create(\urlencode($this->str));
    }

    /**
     * Simple url-encoding.
     *
     * e.g:
     * 'test test' => 'test%20test'
     *
     * EXAMPLE: <code>
     * </code>
     *
     * @psalm-mutation-free
     *
     * @return static
     */
    public function urlEncodeRaw(): self
    {
        return static::create(\rawurlencode($this->str));
    }

    /**
     * Converts the string into an URL slug. This includes replacing non-ASCII
     * characters with their closest ASCII equivalents, removing remaining
     * non-ASCII and non-alphanumeric characters, and replacing whitespace with
     * $separator. The separator defaults to a single dash, and the string
     * is also converted to lowercase.
     *
     * EXAMPLE: <code>
     * s('Using strings like fòô bàř - 1$')->urlify(); // 'using-strings-like-foo-bar-1-dollar'
     * </code>
     *
     * @param string                $separator    [optional] <p>The string used to replace whitespace. Default: '-'</p>
     * @param string                $language     [optional] <p>The language for the url. Default: 'en'</p>
     * @param array<string, string> $replacements [optional] <p>A map of replaceable strings.</p>
     * @param bool                  $strToLower   [optional] <p>string to lower. Default: true</p>
     *
     * @psalm-mutation-free
     *
     * @return static
     *                <p>Object whose $str has been converted to an URL slug.</p>
     *
     * @psalm-suppress ImpureMethodCall :/
     */
    public function urlify(
        string $separator = '-',
        string $language = 'en',
        array $replacements = [],
        bool $strToLower = true
    ): self {
        // init
        $str = $this->str;

        foreach ($replacements as $from => $to) {
            $str = \str_replace($from, $to, $str);
        }

        return static::create(
            URLify::slug(
                $str,
                $language,
                $separator,
                $strToLower
            ),
            $this->encoding
        );
    }

    /**
     * Converts the string into an valid UTF-8 string.
     *
     * EXAMPLE: <code>
     * s('DÃ¼sseldorf')->utf8ify(); // 'Düsseldorf'
     * </code>
     *
     * @psalm-mutation-free
     *
     * @return static
     */
    public function utf8ify(): self
    {
        return static::create($this->utf8::cleanup($this->str), $this->encoding);
    }

    /**
     * Convert a string into an array of words.
     *
     * EXAMPLE: <code>
     * </code>
     *
     * @param string   $char_list           [optional] <p>Additional chars for the definition of "words".</p>
     * @param bool     $remove_empty_values [optional] <p>Remove empty values.</p>
     * @param int|null $remove_short_values [optional] <p>The min. string length or null to disable</p>
     *
     * @psalm-mutation-free
     *
     * @return static[]
     *
     * @psalm-return array<int,static>
     */
    public function words(
        string $char_list = '',
        bool $remove_empty_values = false,
        int $remove_short_values = null
    ): array {
        if ($remove_short_values === null) {
            $strings = $this->utf8::str_to_words(
                $this->str,
                $char_list,
                $remove_empty_values
            );
        } else {
            $strings = $this->utf8::str_to_words(
                $this->str,
                $char_list,
                $remove_empty_values,
                $remove_short_values
            );
        }

        /** @noinspection AlterInForeachInspection */
        foreach ($strings as &$string) {
            $string = static::create($string);
        }

        /** @noinspection PhpSillyAssignmentInspection */
        /** @var static[] $strings */
        $strings = $strings;

        return $strings;
    }

    /**
     * Convert a string into an collection of words.
     *
     * EXAMPLE: <code>
     * S::create('中文空白 oöäü#s')->wordsCollection('#', true)->toStrings(); // ['中文空白', 'oöäü#s']
     * </code>
     *
     * @param string   $char_list           [optional] <p>Additional chars for the definition of "words".</p>
     * @param bool     $remove_empty_values [optional] <p>Remove empty values.</p>
     * @param int|null $remove_short_values [optional] <p>The min. string length or null to disable</p>
     *
     * @psalm-mutation-free
     *
     * @return CollectionStringy|static[]
     *                                    <p>An collection of Stringy objects.</p>
     *
     * @psalm-return CollectionStringy<int,static>
     */
    public function wordsCollection(
        string $char_list = '',
        bool $remove_empty_values = false,
        int $remove_short_values = null
    ): CollectionStringy {
        /**
         * @psalm-suppress ImpureMethodCall -> add more psalm stuff to the collection class
         */
        return CollectionStringy::create(
            $this->words(
                $char_list,
                $remove_empty_values,
                $remove_short_values
            )
        );
    }

    /**
     * Surrounds $str with the given substring.
     *
     * EXAMPLE: <code>
     * </code>
     *
     * @param string $substring <p>The substring to add to both sides.</P>
     *
     * @psalm-mutation-free
     *
     * @return static
     *                <p>Object whose $str had the substring both prepended and appended.</p>
     */
    public function wrap(string $substring): self
    {
        return $this->surround($substring);
    }

    /**
     * Returns the replacements for the toAscii() method.
     *
     * @noinspection PhpUnused
     *
     * @psalm-mutation-free
     *
     * @return array<string, array<int, string>>
     *                       <p>An array of replacements.</p>
     *
     * @deprecated   this is only here for backward-compatibly reasons
     */
    protected function charsArray(): array
    {
        return $this->ascii::charsArrayWithMultiLanguageValues();
    }

    /**
     * Returns true if $str matches the supplied pattern, false otherwise.
     *
     * @param string $pattern <p>Regex pattern to match against.</p>
     *
     * @psalm-mutation-free
     *
     * @return bool
     *              <p>Whether or not $str matches the pattern.</p>
     */
    protected function matchesPattern(string $pattern): bool
    {
        return $this->utf8::str_matches_pattern($this->str, $pattern);
    }
}
