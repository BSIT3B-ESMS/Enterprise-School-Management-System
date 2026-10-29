<?php

namespace Database\Factories;

use App\Enums\EmploymentType;
use App\Enums\OfferKind;
use App\Enums\OfferStatus;
use App\Models\JobTitle;
use App\Models\Offer;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Offer>
 */
class OfferFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'kind' => OfferKind::Hire,
            'issued_by' => User::factory()->hrAdmin(),
            'employment_type' => EmploymentType::FullTime,
            'weekly_hours' => 40,
            'job_title_id' => JobTitle::factory(),
            'start_date' => now()->addDays(14)->toDateString(),
            'summary' => ['position' => 'Instructor I', 'unit' => 'College of Education', 'monthly_pay' => 33947],
            'terms' => 'Sample terms and conditions.',
            'status' => OfferStatus::Pending,
        ];
    }

    public function accepted(): static
    {
        return $this->state(fn (): array => ['status' => OfferStatus::Accepted, 'responded_at' => now(), 'signed_name' => 'Signed Name']);
    }
}
