<?php

namespace Goez\BehatLaravelExtension\Context;

use PHPUnit_Framework_Assert as Assert;

trait AssertionsTrait
{
    public function __call($name, $args)
    {
        $callback = [Assert::class, $name];
        if (preg_match('/^assert/i', $name)) {
            call_user_func_array($callback, $args);
        }
    }

}