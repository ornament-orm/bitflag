<?php

namespace Ornament\Bitflag;

use DomainException;

class FlagNotDefinedException extends DomainException
{
    public function __construct(string $class, string $property, string $flag)
    {
        parent::__construct("Bitflag $class::$property does not specify the option $flag");
    }
}

