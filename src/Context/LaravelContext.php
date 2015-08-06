<?php

namespace Goez\BehatLaravelExtension\Context;

use Behat\Behat\Context\SnippetAcceptingContext;
use Behat\MinkExtension\Context\MinkContext;
use Laracasts\Behat\Context\KernelAwareContext;

class LaravelContext extends MinkContext implements KernelAwareContext, SnippetAcceptingContext
{
    use ApplicationTrait, AssertionsTrait, DatabaseTransactions, DatabaseMigrations;
}