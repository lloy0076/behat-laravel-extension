## Why?

I like to develop a Laravel application by Behat way. With [Laracasts' Behat Laravel Extension](https://github.com/laracasts/Behat-Laravel-Extension), it made my dreams come true.

After I upgraded to Laravel 5.1, I fall in love the testing methods such as `seeInDatabase` or `be` in the new testing framework, but I can not use them in Behat context class.

So I combined the Behat-Laravel-Extension and Laravel testing framework, and made this extension.

## What does the extension do?

This extension provides two features:

1. You can use the testing APIs of [Laravel Testing](laravel.com/docs/5.1/testing) in your Behat context class. For example:

 ```php
    /**
     * @Given a user whose name is :name
     * @param $name
     */
    public function aUserWhoseNameIs($name)
    {
        $this->user = factory(App\User::class)->create([
            'name' => $name,
        ]);
        $this->seeInDatabase('users', [
            'name' => $this->user->name,
        ]);
    }

    /**
     * @When user attempts to sign in
     */
    public function userAttemptsToSignIn()
    {
        $this->be($this->user);
    }
 ```

 The `seeInDatabase`, `be` and other testing methods are provided by `Illuminate\Foundation\Testing\ApplicationTrait` and only can be used in PHPUnit test case, but you can use them in Behat context class now.

2. You can use the `assert*` methods these provided by `PHPUnit_Framework_Assert` directly. For example:

 ```php
    /**
     * @Then user should be sign in
     */
    public function userShouldBeSignIn()
    {
        $this->assertTrue(Auth::check());
    }
 ```

This extension is still in development. If you have any issues, please let me know.

## Installation

1. Install this extension by composer:

 ```
 composer require goez/behat-laravel-extension
 ```

2. Add this extension and mink extension in your `behat.yml`:

 ```yaml
 default:
     extensions:
         Goez\BehatLaravelExtension:
         Behat\MinkExtension:
             default_session: laravel
             laravel: ~
 ```

3. Let your context class extends the `Goez\BehatLaravelExtension\Context\LaravelContext` class:

 ```php
 use Goez\BehatLaravelExtension\Context\LaravelContext;

 class FeatureContext extends LaravelContext
 {
     // ...
 }
 ```

## License

MIT