<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * One request to the API: who, which route, the answer, and from where. Never the token or the data returned.
 */
#[Fillable(['api_consumer_id', 'route', 'status', 'ip_address'])]
class ApiAccessLog extends Model
{
    public const UPDATED_AT = null;
}
