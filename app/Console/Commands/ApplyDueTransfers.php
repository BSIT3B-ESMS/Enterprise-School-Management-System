<?php

namespace App\Console\Commands;

use App\Actions\Employees\ApplyTransfer;
use App\Models\CollegeTransfer;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('transfers:apply')]
#[Description('Apply transfers whose effective date has arrived')]
class ApplyDueTransfers extends Command
{
    public function handle(ApplyTransfer $applyTransfer): int
    {
        $due = CollegeTransfer::query()
            ->whereNull('applied_at')
            ->whereDate('effective_date', '<=', now()->toDateString())
            ->orderBy('effective_date')
            ->orderBy('id')
            ->get();

        foreach ($due as $transfer) {
            $applyTransfer->handle($transfer);
        }

        $this->info("Applied {$due->count()} transfer(s).");

        return self::SUCCESS;
    }
}
