<?php

namespace Goez\BehatLaravelExtension\Context;

trait DatabaseMigrations
{
    /**
     * @BeforeScenario
     */
    public function runDatabaseMigrations()
    {
        $this->artisan('migrate');

        $this->beforeApplicationDestroyed(function () {
            $this->artisan('migrate:rollback');
        });
    }
}
