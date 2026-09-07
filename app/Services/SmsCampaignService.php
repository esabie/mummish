<?php

namespace App\Services;

use App\Enums\HealthProfessionalApprovalStatus;
use App\Enums\SmsAudience;
use App\Enums\SmsCampaignStatus;
use App\Enums\UserRole;
use App\Enums\VendorApplicationStatus;
use App\Jobs\ProcessSmsCampaign;
use App\Models\HealthProfessional;
use App\Models\NewsletterCustomer;
use App\Models\SmsCampaign;
use App\Models\User;
use App\Models\VendorApplication;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class SmsCampaignService
{
    public const MAX_MESSAGE_LENGTH = 480;

    /**
     * @return array<string, int>
     */
    public function audienceCounts(): array
    {
        return [
            SmsAudience::Newsletter->value => $this->resolvePhones(SmsAudience::Newsletter)->count(),
            SmsAudience::Vendors->value => $this->resolvePhones(SmsAudience::Vendors)->count(),
            SmsAudience::HealthProfessionals->value => $this->resolvePhones(SmsAudience::HealthProfessionals)->count(),
            SmsAudience::Customers->value => $this->resolvePhones(SmsAudience::Customers)->count(),
            SmsAudience::Custom->value => 0,
        ];
    }

    /**
     * @param  list<string>  $customPhones
     * @return Collection<int, string>
     */
    public function resolvePhones(SmsAudience $audience, array $customPhones = []): Collection
    {
        $phones = match ($audience) {
            SmsAudience::Newsletter => NewsletterCustomer::query()
                ->whereNotNull('phone')
                ->pluck('phone'),
            SmsAudience::Vendors => VendorApplication::query()
                ->where('status', VendorApplicationStatus::Approved)
                ->whereNotNull('phone')
                ->where('phone', '!=', '')
                ->pluck('phone'),
            SmsAudience::HealthProfessionals => HealthProfessional::query()
                ->where('approval_status', HealthProfessionalApprovalStatus::Approved)
                ->whereNotNull('phone')
                ->where('phone', '!=', '')
                ->pluck('phone'),
            SmsAudience::Customers => User::query()
                ->where('role', UserRole::Customer)
                ->whereNotNull('phone')
                ->where('phone', '!=', '')
                ->pluck('phone'),
            SmsAudience::Custom => collect($customPhones),
        };

        return $phones
            ->map(fn ($phone): string => trim((string) $phone))
            ->filter(fn (string $phone): bool => $phone !== '')
            ->filter(fn (string $phone): bool => MnotifySmsService::normalizeToInternationalDigits($phone) !== '')
            ->unique(fn (string $phone): string => MnotifySmsService::normalizeToInternationalDigits($phone))
            ->values();
    }

    /**
     * @param  list<string>|string|null  $customPhonesInput
     */
    public function createAndQueue(
        SmsAudience $audience,
        string $message,
        ?int $createdByUserId,
        array|string|null $customPhonesInput = null,
    ): SmsCampaign {
        $message = trim($message);

        if ($message === '') {
            throw ValidationException::withMessages([
                'message' => 'Enter a message to send.',
            ]);
        }

        if (mb_strlen($message) > self::MAX_MESSAGE_LENGTH) {
            throw ValidationException::withMessages([
                'message' => 'Message must be '.self::MAX_MESSAGE_LENGTH.' characters or fewer.',
            ]);
        }

        $customPhones = $this->parseCustomPhones($customPhonesInput);

        if ($audience === SmsAudience::Custom && $customPhones === []) {
            throw ValidationException::withMessages([
                'custom_phones' => 'Add at least one valid phone number.',
            ]);
        }

        $phones = $this->resolvePhones($audience, $customPhones);

        if ($phones->isEmpty()) {
            throw ValidationException::withMessages([
                'audience' => 'No recipients found for that audience.',
            ]);
        }

        $campaign = SmsCampaign::query()->create([
            'audience' => $audience,
            'message' => $message,
            'custom_phones' => $audience === SmsAudience::Custom ? $phones->all() : null,
            'recipient_count' => $phones->count(),
            'sent_count' => 0,
            'failed_count' => 0,
            'status' => SmsCampaignStatus::Pending,
            'created_by_user_id' => $createdByUserId,
        ]);

        ProcessSmsCampaign::dispatch($campaign->id);

        return $campaign;
    }

    /**
     * @param  list<string>|string|null  $input
     * @return list<string>
     */
    public function parseCustomPhones(array|string|null $input): array
    {
        if ($input === null) {
            return [];
        }

        if (is_array($input)) {
            $raw = implode("\n", $input);
        } else {
            $raw = $input;
        }

        $parts = preg_split('/[\s,;]+/', $raw) ?: [];

        return collect($parts)
            ->map(fn ($phone): string => trim((string) $phone))
            ->filter(fn (string $phone): bool => $phone !== '')
            ->values()
            ->all();
    }
}
