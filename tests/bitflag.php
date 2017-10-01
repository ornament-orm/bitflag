<?php

use Ornament\Demo\BitflagModel;
use Ornament\Bitflag\Property;

return function ($test) : Generator {
    $test->beforeEach(function () use (&$model) {
        $model = new BitflagModel;
    });

    /** On an annotated model, @var is turned into a bitflag. */
    yield function () use (&$model) {
        assert($model->status instanceof Property);
        $model->status->code = true;
        $model->status->cats = true;
        assert("{$model->status}" === "6");
    };

    /** After changing some flags, they are correctly persisted and again after re-changing. */
    yield function () use (&$model) {
        $model->status->cats = true;
        $model->status->nice = true;
        assert("{$model->status}" === "3");
    };

    /** The model can be serialized after which it is a stdClass that contains the correct settings. */
    yield function () use (&$model) {
        $model->status->cats = true;
        $model->status->nice = true;
        $exported = $model->status->jsonSerialize();
        assert($exported instanceof stdClass);
        assert($exported->code == false);
        assert($exported->cats == true);
        assert($exported->nice == true);
    };
};

