<?php

declare(strict_types=1);

namespace Stringy;

trait JsonSerializableReturnTypeTrait
{
    /**
     * Returns value which can be serialized by json_encode().
     *
     * PHP 8.1+ deprecates omitting the jsonSerialize() return type when
     * implementing JsonSerializable, but PHP 7.1 cannot parse the attribute.
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
