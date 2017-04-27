<?php

namespace Ornament\Tests;

use Ornament\Demo\BitflagModel;
use Ornament\Bitflag\Property;
use StdClass;

class BitflagTest
{
    private static $pdo;
    private $conn;

    /**
     * On an annotated model, @var is turned into a bitflag {?}. After changing
     * some flags, they are correctly persisted {?} and again after re-changing
     * {?}. The model can be serialized after which it is a StdClass {?} that
     * contains the correct settings {?} {?} {?}.
     */
    public function testBitflags()
    {
        $model = new BitflagModel;
        yield assert($model->status instanceof Property);
        $model->status->code = true;
        $model->status->cats = true;
        yield assert("{$model->status}" === "6");
        $model->status->code = false;
        $model->status->nice = true;
        yield assert("{$model->status}" === "3");
        $exported = $model->status->jsonSerialize();
        yield assert($exported instanceof StdClass);
        yield assert($exported->code == false);
        yield assert($exported->cats == true);
        yield assert($exported->nice == true);
    }
}

