<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class IntegrationToken extends Model {
    protected $guarded = [];
    protected $hidden = ['token_hash'];
    protected function casts(): array { return ['permissions'=>'array','last_used_at'=>'datetime','revoked_at'=>'datetime']; }
}
