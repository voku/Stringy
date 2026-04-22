<?php

declare(strict_types=1);

namespace Stringy;

if (\PHP_VERSION_ID < 80100) {
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
} else {
    require_once __DIR__ . '/JsonSerializableReturnTypeTraitPhp81.php';
}
