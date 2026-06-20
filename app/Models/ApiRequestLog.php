<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApiRequestLog extends Model
{
    protected $fillable = [
        'request_id',
        'method',
        'path',
        'status_code',
        'duration_ms',
        'ip_address',
        'request_body_preview',
    ];
}
