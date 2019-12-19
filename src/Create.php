<?php

namespace Stringy;

if (!\function_exists('Stringy\create')) {
    /**
     * Creates a Stringy object and returns it on success.
     *
     * @param mixed  $str      Value to modify, after being cast to string
     * @param string $encoding The character encoding
     *
     * @throws \InvalidArgumentException if an array or object without a
     *                                   __toString method is passed as the first argument
     *
     * @return Stringy A Stringy object
     */
    function create($str, string $encoding = null)
    {
        return new Stringy($str, $encoding);
    }
}

if (!\function_exists('Stringy\collection')) {
    /**
     * @param string[]|Stringy[]|null $input
     *
     * @throws \TypeError
     *
     * @return CollectionStringy<int,Stringy>
     */
    function collection($input = null)
    {
        // init
        $newCollection = new CollectionStringy();

        if ($input === null) {
            return $newCollection;
        }

        /**
         * @psalm-suppress DocblockTypeContradiction
         */
        if (!\is_array($input)) {
            $input = [$input];
        }

        foreach ($input as &$stringOrStringy) {
            if (\is_string($stringOrStringy)) {
                $stringOrStringy = new Stringy($stringOrStringy);
            }

            $newCollection[] = $stringOrStringy;
        }

        return $newCollection;
    }
}
