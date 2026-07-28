<?php

namespace Byl\Laravel\Tests;

use Byl\Laravel\BylServiceProvider;
use Byl\Laravel\Facades\Byl;
use Byl\Laravel\Tests\Fixtures\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [BylServiceProvider::class];
    }

    protected function getPackageAliases($app): array
    {
        return ['Byl' => Byl::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('byl.token', 'test-token');
        $app['config']->set('byl.project_id', 1);
        $app['config']->set('byl.base_url', 'https://byl.mn');
        $app['config']->set('byl.retry.times', 0);
        $app['config']->set('byl.webhook.secret', 'test-secret');
        $app['config']->set('byl.billable.model', User::class);
        $app['config']->set('auth.providers.users.model', User::class);
    }

    protected function defineDatabaseMigrations(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->timestamps();
        });

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }

    protected function tearDown(): void
    {
        Byl::resolveBillableUsing(null);

        parent::tearDown();
    }

    protected function createUser(array $attributes = []): User
    {
        return User::create([
            'name' => 'Бат-Эрдэнэ',
            'email' => 'customer@example.mn',
            ...$attributes,
        ]);
    }
}
