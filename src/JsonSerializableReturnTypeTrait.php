<?php

declare(strict_types=1);

namespace Stringy;

if (\PHP_VERSION_ID >= 80100) {
    eval(<<<'PHP'
namespace Stringy;

trait JsonSerializableReturnTypeTrait
{
    /**
     * Returns value which can be serialized by json_encode().
     *
     * @psalm-mutation-free
     *
     * @return string The current value of the $str property
     */
    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return (string) $this;
    }
}
PHP
    );
} else {
    trait JsonSerializableReturnTypeTrait
    {
        /**
         * Returns value which can be serialized by json_encode().
         *
         * @psalm-mutation-free
         *
         * @return string The current value of the $str property
         */
        public function jsonSerialize()
        {
            return (string) $this;
        }
    }
}
