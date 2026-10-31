<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeaveBalance extends Model
{
    protected $table = 'ems_leave_balances';
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'remaining_days' => 'decimal:2',
            'as_of' => 'date:Y-m-d',
            'source_updated_at' => 'datetime',
        ];
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
