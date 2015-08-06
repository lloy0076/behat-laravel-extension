<?php

namespace Goez\BehatLaravelExtension\Context;

use Exception;
use Illuminate\Contracts\Auth\Authenticatable as UserContract;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Foundation\Application;
use Mockery;
use Mockery\Mock;
use Symfony\Component\HttpKernel\HttpKernelInterface;

trait ApplicationTrait
{
    /**
     * The Laravel application.
     *
     * @var Application
     */
    protected $app;

    /**
     * Set the application.
     *
     * @param HttpKernelInterface $app
     */
    public function setApp(HttpKernelInterface $app)
    {
        $this->app = $app;
    }

    /**
     * Get the application.
     *
     * @return Application
     */
    public function app()
    {
        return $this->app;
    }


    /**
     * The callbacks that should be run before the application is destroyed.
     *
     * @var array
     */
    protected $beforeApplicationDestroyedCallbacks = [];

    /**
     * Clean up the testing environment before the next test.
     *
     * @AfterScenario
     * @return void
     */
    public function shutdownApplication()
    {
        if (class_exists('Mockery')) {
            Mockery::close();
        }

        if ($this->app) {
            foreach ($this->beforeApplicationDestroyedCallbacks as $callback) {
                call_user_func($callback);
            }

            $this->app->flush();

            $this->app = null;
        }
    }

    /**
     * Register a callback to be run before the application is destroyed.
     *
     * @param  callable $callback
     * @return void
     */
    protected function beforeApplicationDestroyed(callable $callback)
    {
        $this->beforeApplicationDestroyedCallbacks[] = $callback;
    }

    /**
     * Register an instance of an object in the container.
     *
     * @param  string $abstract
     * @param  object $instance
     * @return object
     */
    protected function instance($abstract, $instance)
    {
        $this->app->instance($abstract, $instance);

        return $instance;
    }

    /**
     * Specify a list of events that should be fired for the given operation.
     *
     * These events will be mocked, so that handlers will not actually be executed.
     *
     * @param  array|mixed $events
     * @return $this
     */
    public function expectsEvents($events)
    {
        $events = is_array($events) ? $events : func_get_args();

        $mock = Mockery::spy(Dispatcher::class);
        /* @var $mock Mock */

        $mock->shouldReceive('fire')->andReturnUsing(function ($called) use (&$events) {
            foreach ($events as $key => $event) {
                if ((is_string($called) && $called === $event) ||
                    (is_string($called) && is_subclass_of($called, $event)) ||
                    (is_object($called) && $called instanceof $event)
                ) {
                    unset($events[$key]);
                }
            }
        });

        $this->beforeApplicationDestroyed(function () use (&$events) {
            if ($events) {
                throw new Exception(
                    'The following events were not fired: [' . implode(', ', $events) . ']'
                );
            }
        });

        $this->app->instance('events', $mock);

        return $this;
    }

    /**
     * Mock the event dispatcher so all events are silenced.
     *
     * @return $this
     */
    protected function withoutEvents()
    {
        $mock = Mockery::mock(Dispatcher::class);
        /* @var $mock Mock */

        $mock->shouldReceive('fire');

        $this->app->instance('events', $mock);

        return $this;
    }

    /**
     * Specify a list of jobs that should be dispatched for the given operation.
     *
     * These jobs will be mocked, so that handlers will not actually be executed.
     *
     * @param  array|mixed $jobs
     * @return $this
     */
    protected function expectsJobs($jobs)
    {
        $jobs = is_array($jobs) ? $jobs : func_get_args();

        $mock = Mockery::mock('Illuminate\Bus\Dispatcher[dispatch]', [$this->app]);
        /* @var $mock Mock */

        foreach ($jobs as $job) {
            $mock->shouldReceive('dispatch')->atLeast()->once()
                ->with(Mockery::type($job));
        }

        $this->app->instance(
            'Illuminate\Contracts\Bus\Dispatcher', $mock
        );

        return $this;
    }

    /**
     * Set the session to the given array.
     *
     * @param  array $data
     * @return $this
     */
    public function withSession(array $data)
    {
        $this->session($data);

        return $this;
    }

    /**
     * Set the session to the given array.
     *
     * @param  array $data
     * @return void
     */
    public function session(array $data)
    {
        $this->startSession();

        foreach ($data as $key => $value) {
            $this->app['session']->put($key, $value);
        }
    }

    /**
     * Start the session for the application.
     *
     * @return void
     */
    protected function startSession()
    {
        if (!$this->app['session']->isStarted()) {
            $this->app['session']->start();
        }
    }

    /**
     * Flush all of the current session data.
     *
     * @return void
     */
    public function flushSession()
    {
        $this->startSession();

        $this->app['session']->flush();
    }

    /**
     * Set the currently logged in user for the application.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable $user
     * @param  string|null $driver
     * @return $this
     */
    public function actingAs(UserContract $user, $driver = null)
    {
        $this->be($user, $driver);

        return $this;
    }

    /**
     * Set the currently logged in user for the application.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable $user
     * @param  string|null $driver
     * @return void
     */
    public function be(UserContract $user, $driver = null)
    {
        $this->app['auth']->driver($driver)->setUser($user);
    }

    /**
     * Assert that a given where condition exists in the database.
     *
     * @param  string $table
     * @param  array $data
     * @param  string $connection
     * @return $this
     */
    protected function seeInDatabase($table, array $data, $connection = null)
    {
        $database = $this->app->make('db');

        $connection = $connection ?: $database->getDefaultConnection();

        $count = $database->connection($connection)->table($table)->where($data)->count();

        $this->assertGreaterThan(0, $count, sprintf(
            'Unable to find row in database table [%s] that matched attributes [%s].', $table, json_encode($data)
        ));

        return $this;
    }

    /**
     * Assert that a given where condition does not exist in the database.
     *
     * @param  string $table
     * @param  array $data
     * @param  string $connection
     * @return $this
     */
    protected function missingFromDatabase($table, array $data, $connection = null)
    {
        return $this->notSeeInDatabase($table, $data, $connection);
    }

    /**
     * Assert that a given where condition does not exist in the database.
     *
     * @param  string $table
     * @param  array $data
     * @param  string $connection
     * @return $this
     */
    protected function notSeeInDatabase($table, array $data, $connection = null)
    {
        $database = $this->app->make('db');

        $connection = $connection ?: $database->getDefaultConnection();

        $count = $database->connection($connection)->table($table)->where($data)->count();

        $this->assertEquals(0, $count, sprintf(
            'Found unexpected records in database table [%s] that matched attributes [%s].', $table, json_encode($data)
        ));

        return $this;
    }

    /**
     * Seed a given database connection.
     *
     * @param  string $class
     * @return void
     */
    public function seed($class = 'DatabaseSeeder')
    {
        $this->artisan('db:seed', ['--class' => $class]);
    }

    /**
     * Call artisan command and return code.
     *
     * @param string $command
     * @param array $parameters
     * @return int
     */
    public function artisan($command, $parameters = [])
    {
        return $this->code = $this->app['Illuminate\Contracts\Console\Kernel']->call($command, $parameters);
    }
}