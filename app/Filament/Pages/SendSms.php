<?php

namespace App\Filament\Pages;

use App\Enums\SmsAudience;
use App\Models\SmsCampaign;
use App\Services\SmsCampaignService;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class SendSms extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?string $navigationLabel = 'Send SMS';

    protected static ?string $title = 'Send SMS';

    protected static ?string $slug = 'send-sms';

    protected static ?int $navigationSort = 6;

    protected static string $view = 'filament.pages.send-sms';

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    /**
     * @var array<string, int>
     */
    public array $audienceCounts = [];

    public function mount(): void
    {
        $this->audienceCounts = app(SmsCampaignService::class)->audienceCounts();

        $this->form->fill([
            'audience' => SmsAudience::Newsletter->value,
            'message' => '',
            'custom_phones' => '',
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Compose message')
                    ->description('Messages are sent with the sender ID, Mummish')
                    ->schema([
                        Forms\Components\Select::make('audience')
                            ->label('Recipient')
                            ->options(collect(SmsAudience::cases())->mapWithKeys(
                                fn (SmsAudience $audience) => [$audience->value => $audience->label()]
                            )->all())
                            ->required()
                            ->native(false)
                            ->live()
                            ->helperText(function (Get $get): string {
                                $audience = SmsAudience::tryFrom((string) $get('audience'));
                                if ($audience === null) {
                                    return '';
                                }

                                if ($audience === SmsAudience::Custom) {
                                    return $audience->description();
                                }

                                $count = $this->audienceCounts[$audience->value] ?? 0;

                                return $audience->description().' Currently '.$count.' '
                                    .($count === 1 ? 'recipient' : 'recipients').'.';
                            }),
                        Forms\Components\Textarea::make('custom_phones')
                            ->label('Phone numbers')
                            ->rows(4)
                            ->placeholder("0241234567\n0209876543")
                            ->helperText('One number per line, or separated by commas. Ghana numbers preferred (024… or 233…).')
                            ->visible(fn (Get $get): bool => $get('audience') === SmsAudience::Custom->value)
                            ->required(fn (Get $get): bool => $get('audience') === SmsAudience::Custom->value),
                        Forms\Components\Textarea::make('message')
                            ->label('Message')
                            ->required()
                            ->rows(5)
                            ->maxLength(SmsCampaignService::MAX_MESSAGE_LENGTH)
                            ->live(onBlur: false, debounce: 250)
                            ->helperText(function (Get $get): string {
                                $length = mb_strlen((string) ($get('message') ?? ''));
                                $segments = max(1, (int) ceil(max(1, $length) / 160));

                                return "{$length} / ".SmsCampaignService::MAX_MESSAGE_LENGTH
                                    ." characters · about {$segments} SMS "
                                    .($segments === 1 ? 'segment' : 'segments').' per recipient.';
                            }),
                    ]),
            ])
            ->statePath('data');
    }

    public function send(): void
    {
        $state = $this->form->getState();
        $audience = SmsAudience::tryFrom((string) ($state['audience'] ?? ''));

        if ($audience === null) {
            Notification::make()
                ->title('Choose an audience')
                ->warning()
                ->send();

            return;
        }

        try {
            $campaign = app(SmsCampaignService::class)->createAndQueue(
                audience: $audience,
                message: (string) ($state['message'] ?? ''),
                createdByUserId: Auth::id(),
                customPhonesInput: $state['custom_phones'] ?? null,
            );
        } catch (ValidationException $e) {
            $message = collect($e->errors())->flatten()->first() ?? 'Could not queue this SMS.';

            Notification::make()
                ->title((string) $message)
                ->danger()
                ->send();

            return;
        }

        $this->audienceCounts = app(SmsCampaignService::class)->audienceCounts();
        $this->form->fill([
            'audience' => $audience->value,
            'message' => '',
            'custom_phones' => '',
        ]);

        Notification::make()
            ->title('SMS queued')
            ->body("Sending to {$campaign->recipient_count} "
                .($campaign->recipient_count === 1 ? 'recipient' : 'recipients')
                .' in the background.')
            ->success()
            ->send();
    }

    /**
     * @return array{recentCampaigns: list<array<string, mixed>>}
     */
    protected function getViewData(): array
    {
        $recent = SmsCampaign::query()
            ->with('createdBy')
            ->latest('id')
            ->limit(15)
            ->get()
            ->map(fn (SmsCampaign $campaign): array => [
                'id' => $campaign->id,
                'audience' => $campaign->audience->label(),
                'message_preview' => \App\Services\MnotifySmsService::messagePreviewForLog($campaign->message, 120),
                'recipient_count' => $campaign->recipient_count,
                'sent_count' => $campaign->sent_count,
                'failed_count' => $campaign->failed_count,
                'status' => $campaign->status->label(),
                'status_value' => $campaign->status->value,
                'created_by' => $campaign->createdBy?->name ?? '—',
                'created_at' => optional($campaign->created_at)?->timezone(config('app.timezone'))->format('M j, Y g:i A'),
            ])
            ->all();

        return [
            'recentCampaigns' => $recent,
        ];
    }
}
