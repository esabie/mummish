<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Requests\StoreAdminSetupRequest;
use App\Models\User;
use App\Support\AdminSetupLink;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AdminSetupController extends Controller
{
    public function create(string $token): Response
    {
        $this->assertValidToken($token);

        return Inertia::render('Auth/AdminSetup', [
            'token' => $token,
        ]);
    }

    public function store(StoreAdminSetupRequest $request, string $token): RedirectResponse
    {
        $this->assertValidToken($token);

        $email = (string) $request->validated('email');

        User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => $request->validated('name'),
                'password' => $request->validated('password'),
                'role' => UserRole::Admin,
                'email_verified_at' => now(),
            ],
        );

        AdminSetupLink::invalidate();

        return redirect('/admin/login')
            ->with('status', 'Admin account created. You can sign in now.');
    }

    private function assertValidToken(string $token): void
    {
        if (! AdminSetupLink::isValid($token)) {
            throw new NotFoundHttpException;
        }
    }
}
