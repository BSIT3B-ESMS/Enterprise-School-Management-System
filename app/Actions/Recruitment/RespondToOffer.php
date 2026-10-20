<?php

namespace App\Actions\Recruitment;

use App\Actions\Employees\ApplyAgreement;
use App\Enums\ApplicationStatus;
use App\Enums\AuditAction;
use App\Enums\OfferKind;
use App\Enums\OfferStatus;
use App\Models\Offer;
use App\Models\User;
use App\Services\ApplicationTimeline;
use App\Services\AuditLogger;
use App\Services\Notifier;
use Illuminate\Support\Facades\DB;

class RespondToOffer
{
    public function __construct(
        private AuditLogger $audit,
        private ApplicationTimeline $timeline,
        private Notifier $notifier,
        private ApplyAgreement $applyAgreement,
    ) {}

    /**
     * The person accepts: typing their full name is their signature. A job offer then waits for the HR Admin to complete the
     * hiring; a contract offer or change of assignment is applied (now, or on its start date).
     *
     * @throws RecruitmentException
     */
    public function accept(User $user, Offer $offer, string $signedName, ?string $ip = null): Offer
    {
        $this->guard($user, $offer);

        if (! $this->nameMatches($user, $offer, $signedName)) {
            throw new RecruitmentException('Type your full name exactly as it appears on your account to sign.');
        }

        return DB::transaction(function () use ($user, $offer, $signedName, $ip): Offer {
            $offer->forceFill([
                'status' => OfferStatus::Accepted,
                'responded_at' => now(),
                'signed_name' => trim($signedName),
                'signed_ip' => $ip,
            ])->save();

            $this->audit->log(AuditAction::Update, $offer, ['event' => 'offer_accepted'], $user);

            if ($offer->kind === OfferKind::Hire) {
                $application = $offer->application()->with('jobTitle')->firstOrFail();
                $this->timeline->add($application, 'offer_accepted', $user, ['signed_name' => trim($signedName)]);
                $this->notifier->notify($this->notifier->hrAdmins(), 'Offer accepted', "{$user->applicantProfile->full_name} accepted the offer for {$application->jobTitle->title}. Complete the hiring.", route('recruitment.show', $application));
            } else {
                $offer->loadMissing('employee', 'jobTitle');
                $this->notifier->notify(collect([$offer->issuer])->merge($this->notifier->hrAdmins()), $offer->kind->label().' accepted', "{$offer->employee->full_name} accepted: {$offer->summary['position']}.", route('employees.show', $offer->employee));

                if ($offer->start_date->lte(now()->endOfDay())) {
                    $this->applyAgreement->handle($offer, $offer->issuer);
                }
            }

            return $offer;
        });
    }

    /**
     * The person declines. A declined job offer ends the application as Cancelled; a declined contract offer or change leaves
     * the employee exactly as they are until their contract ends.
     *
     * @throws RecruitmentException
     */
    public function decline(User $user, Offer $offer, ?string $reason = null): Offer
    {
        $this->guard($user, $offer);

        return DB::transaction(function () use ($user, $offer, $reason): Offer {
            $offer->forceFill(['status' => OfferStatus::Declined, 'responded_at' => now(), 'response_note' => $reason])->save();
            $this->audit->log(AuditAction::Update, $offer, ['event' => 'offer_declined', 'reason' => $reason], $user);

            if ($offer->kind === OfferKind::Hire) {
                $application = $offer->application()->with('jobTitle')->firstOrFail();
                $application->forceFill(['status' => ApplicationStatus::Cancelled, 'status_reason' => 'The applicant declined the offer.'.($reason ? " {$reason}" : ''), 'status_changed_at' => now()])->save();
                $this->timeline->add($application, 'offer_declined', $user, array_filter(['reason' => $reason]));
                $this->notifier->notify($this->notifier->hrAdmins(), 'Offer declined', "{$user->applicantProfile->full_name} declined the offer for {$application->jobTitle->title}.", route('recruitment.show', $application));
            } else {
                $offer->loadMissing('employee');
                $this->notifier->notify(collect([$offer->issuer])->merge($this->notifier->hrAdmins()), $offer->kind->label().' declined', "{$offer->employee->full_name} declined: {$offer->summary['position']}. Their current assignment stays as it is.", route('employees.show', $offer->employee));
            }

            return $offer;
        });
    }

    /**
     * @throws RecruitmentException
     */
    private function guard(User $user, Offer $offer): void
    {
        $isOwner = $offer->kind === OfferKind::Hire
            ? $offer->application?->user_id === $user->id
            : $user->employee_id !== null && $offer->employee_id === $user->employee_id;

        if (! $isOwner) {
            throw new RecruitmentException('This offer is not yours.');
        }

        if (! $offer->isPending()) {
            throw new RecruitmentException("This offer was already {$offer->status->value}.");
        }
    }

    private function nameMatches(User $user, Offer $offer, string $signedName): bool
    {
        $typed = mb_strtolower(preg_replace('/\s+/', ' ', trim($signedName)));
        $expected = $offer->kind === OfferKind::Hire
            ? [$user->applicantProfile->first_name, $user->applicantProfile->last_name]
            : [$user->employee->first_name, $user->employee->last_name];

        foreach ($expected as $part) {
            if (! str_contains($typed, mb_strtolower(trim($part)))) {
                return false;
            }
        }

        return true;
    }
}
