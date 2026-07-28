<?php

namespace Byl\Laravel\Tests\Fixtures;

use Byl\Laravel\Billing\Billable;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use Billable;

    protected $table = 'users';

    protected $guarded = [];
}
