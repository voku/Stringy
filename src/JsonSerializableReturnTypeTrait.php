<?php

declare(strict_types=1);

namespace Stringy;

if (\PHP_VERSION_ID >= 80100) {
    require_once __DIR__ . '/../resources/JsonSerializableReturnTypeTraitPhp81.php';
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
