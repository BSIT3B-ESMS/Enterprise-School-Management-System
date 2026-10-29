<?php

namespace App\Console\Commands;

use App\Actions\Employees\ApplyAgreement;
use App\Enums\OfferKind;
use App\Enums\OfferStatus;
use App\Models\Offer;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('agreements:apply')]
#[Description('Put accepted contract offers and changes of assignment into effect when their start date arrives')]
class ApplyDueAgreements extends Command
{
    public function handle(ApplyAgreement $apply): int
    {
        $due = Offer::query()
            ->whereIn('kind', [OfferKind::Renewal, OfferKind::Change])
            ->where('status', OfferStatus::Accepted)
            ->whereNull('applied_at')
            ->whereDate('start_date', '<=', now()->toDateString())
            ->orderBy('start_date')->orderBy('id')
            ->get();

        foreach ($due as $offer) {
            $apply->handle($offer);
        }

        $this->info("Applied {$due->count()} agreement(s).");

        return self::SUCCESS;
    }
}
