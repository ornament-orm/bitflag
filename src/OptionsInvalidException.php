<?php

namespace Ornament\Bitflag;

use DomainException;

class OptionsInvalidException extends DomainException
{
    public function __construct(string $enum)
    {
        parent::__construct("Enum $enum must be backed with the type int.");
    }
}

