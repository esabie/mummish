<?php

$path = __DIR__.'/ghana_banks.json';
$payload = is_readable($path)
    ? json_decode((string) file_get_contents($path), true)
    : [];

$banks = collect($payload['banks'] ?? [])
    ->filter(fn ($bank) => is_array($bank) && filled($bank['name'] ?? null))
    ->map(fn (array $bank): array => [
        'name' => trim((string) $bank['name']),
        'code' => isset($bank['code']) ? trim((string) $bank['code']) : null,
    ])
    ->unique('name')
    ->sortBy(fn (array $bank) => mb_strtolower($bank['name']))
    ->values()
    ->all();

return [
    'banks' => $banks,
    'names' => array_column($banks, 'name'),
];
