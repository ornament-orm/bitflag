<?php

namespace Ornament\Bitflag;

trait Decorator
{
    /**
     * @Decorate Bitflag
     */
    private function decorateBitflag($value, array $mapping)
    {
        return new Property($value, $mapping);
    }
}

