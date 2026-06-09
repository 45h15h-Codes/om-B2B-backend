<?php

namespace App\Models;

use Illuminate\Notifications\DatabaseNotification as BaseNotification;
use Illuminate\Database\Eloquent\SoftDeletes;

class Notification extends BaseNotification
{
    use SoftDeletes;

    protected $table = 'notifications';
}
