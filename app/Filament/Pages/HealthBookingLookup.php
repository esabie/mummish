<?php

namespace App\Filament\Pages;

use App\Services\HealthBookingLookupService;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class HealthBookingLookup extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationLabel = 'Health booking lookup';

    protected static ?string $title = 'Health booking lookup';

    protected static ?string $slug = 'health-booking-lookup';

    protected static ?int $navigationSort = 7;

    protected static string $view = 'filament.pages.health-booking-lookup';

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    /**
     * @var array<string, mixed>|null
     */
    public ?array $result = null;

    public function mount(): void
    {
        $this->form->fill([
            'reference' => request()->query('reference', ''),
            'patient_email' => request()->query('email', ''),
        ]);

        if (trim((string) request()->query('reference', '')) !== ''
            && trim((string) request()->query('email', '')) !== '') {
            $this->runLookup();
        }
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Find a health booking')
                    ->description('Search by booking reference and the patient email used at booking.')
                    ->schema([
                        Forms\Components\TextInput::make('reference')
                            ->label('Booking reference')
                            ->placeholder('e.g. HB-A1B2C3')
                            ->required()
                            ->maxLength(40)
                            ->autocomplete(false)
                            ->autofocus(),
                        Forms\Components\TextInput::make('patient_email')
                            ->label('Patient email')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->autocomplete(false),
                    ])
                    ->columns(2),
            ])
            ->statePath('data');
    }

    public function lookup(): void
    {
        $this->runLookup();
    }

    protected function runLookup(): void
    {
        $state = $this->form->getState();
        $reference = trim((string) ($state['reference'] ?? ''));
        $email = trim((string) ($state['patient_email'] ?? ''));

        if ($reference === '' || $email === '') {
            Notification::make()
                ->title('Enter a booking reference and patient email')
                ->warning()
                ->send();

            $this->result = null;

            return;
        }

        $booking = app(HealthBookingLookupService::class)
            ->findByReferenceAndEmail($reference, $email);

        if ($booking === null) {
            $this->result = null;

            Notification::make()
                ->title('No booking found')
                ->body('No health booking matched that reference and email.')
                ->warning()
                ->send();

            return;
        }

        $this->result = app(HealthBookingLookupService::class)->toAdminArray($booking);
    }

    /**
     * @return array{result: array<string, mixed>|null}
     */
    protected function getViewData(): array
    {
        return [
            'result' => $this->result,
        ];
    }
}
