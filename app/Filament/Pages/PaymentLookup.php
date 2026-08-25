<?php

namespace App\Filament\Pages;

use App\Services\PaymentLookupService;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class PaymentLookup extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-magnifying-glass-circle';

    protected static ?string $navigationLabel = 'Payment lookup';

    protected static ?string $title = 'Payment lookup';

    protected static ?string $slug = 'payment-lookup';

    protected static ?int $navigationSort = 3;

    protected static string $view = 'filament.pages.payment-lookup';

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    /**
     * @var array{
     *     query: string,
     *     matches: list<array<string, mixed>>,
     *     paystack: array<string, mixed>|null,
     *     paystack_error: string|null
     * }|null
     */
    public ?array $result = null;

    public function mount(): void
    {
        $this->form->fill([
            'reference' => request()->query('reference', ''),
            'verify_with_paystack' => true,
        ]);

        $prefill = trim((string) request()->query('reference', ''));
        if ($prefill !== '') {
            $this->runLookup();
        }
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Find a payment')
                    ->description('Search by shop order number, health booking reference, or Paystack reference / transaction id.')
                    ->schema([
                        Forms\Components\TextInput::make('reference')
                            ->label('Reference')
                            ->placeholder('e.g. MM20260825A7K, HB-A1B2C3, or Paystack reference')
                            ->required()
                            ->maxLength(80)
                            ->autocomplete(false)
                            ->autofocus(),
                        Forms\Components\Toggle::make('verify_with_paystack')
                            ->label('Also verify with Paystack')
                            ->helperText('Checks live gateway status for this reference.')
                            ->default(true),
                    ])
                    ->columns(1),
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
        $verify = (bool) ($state['verify_with_paystack'] ?? true);

        if ($reference === '') {
            Notification::make()
                ->title('Enter a reference to look up')
                ->warning()
                ->send();

            $this->result = null;

            return;
        }

        $this->result = app(PaymentLookupService::class)->lookup($reference, $verify);

        if ($this->result['matches'] === [] && $this->result['paystack'] === null && $this->result['paystack_error'] !== null) {
            Notification::make()
                ->title('No local match found')
                ->body('Paystack verification also failed: '.$this->result['paystack_error'])
                ->warning()
                ->send();
        } elseif ($this->result['matches'] === [] && $this->result['paystack'] === null) {
            Notification::make()
                ->title('No payments found')
                ->body('No shop order or health booking matched that reference.')
                ->warning()
                ->send();
        }
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
