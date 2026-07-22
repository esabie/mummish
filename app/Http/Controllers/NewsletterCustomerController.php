<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreNewsletterCustomerRequest;
use App\Models\NewsletterCustomer;
use Illuminate\Http\RedirectResponse;

class NewsletterCustomerController extends Controller
{
    public function store(StoreNewsletterCustomerRequest $request): RedirectResponse
    {
        $phone = trim($request->validated('phone'));
        $name = trim((string) ($request->validated('name') ?? ''));

        NewsletterCustomer::query()->updateOrCreate(
            ['phone' => $phone],
            ['name' => $name !== '' ? $name : null],
        );

        return back()->with('newsletterJoined', true);
    }
}
