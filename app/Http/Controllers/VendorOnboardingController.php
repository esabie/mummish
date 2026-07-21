<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Enums\VendorApplicationStatus;
use App\Http\Requests\StoreVendorApplicationRequest;
use App\Jobs\SendAccountWelcomeSms;
use App\Jobs\SendAdminNewVendorRegistrationSms;
use App\Models\User;
use App\Models\VendorApplication;
use App\Services\ShopLogoService;
use App\Services\VendorReferralRewardService;
use App\Support\AppLog;
use App\Support\LogSanitizer;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class VendorOnboardingController extends Controller
{
    public function create(): Response
    {
        $user = auth()->user();
        $referralCode = strtoupper(trim((string) request()->query('ref', '')));

        return Inertia::render('Vendor/SignUp', [
            'categories' => StoreVendorApplicationRequest::categories(),
            'existingApplication' => $user?->vendorApplication,
            'isAdminAccount' => $user?->isAdmin() ?? false,
            'referral_code' => $referralCode !== '' ? $referralCode : null,
        ]);
    }

    public function store(StoreVendorApplicationRequest $request): RedirectResponse
    {
        $user = $request->user();

        if ($user?->isAdmin()) {
            throw ValidationException::withMessages([
                'email' => 'Admin accounts cannot become vendors. Sign out and apply with a different email.',
            ]);
        }

        if ($user?->vendorApplication) {
            throw ValidationException::withMessages([
                'email' => 'You have already submitted a vendor application.',
            ]);
        }

        $createdNewUser = false;

        DB::transaction(function () use ($request, &$user, &$createdNewUser) {
            if (! $user) {
                $user = User::create([
                    'name' => trim($request->first_name.' '.$request->last_name),
                    'email' => $request->email,
                    'password' => Hash::make($request->password),
                    'role' => UserRole::Vendor,
                ]);

                $createdNewUser = true;
            } else {
                $user->update([
                    'name' => trim($request->first_name.' '.$request->last_name),
                    'role' => UserRole::Vendor,
                ]);
            }

            $loginEmail = $user->email;
            $referrer = app(VendorReferralRewardService::class)->findActiveReferrer($request->referral_code);
            $logoPath = $request->hasFile('logo')
                ? app(ShopLogoService::class)->store($user, $request->file('logo'))
                : null;

            VendorApplication::create([
                'user_id' => $user->id,
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'shop_name' => $request->shop_name,
                'logo_path' => $logoPath,
                'business_email' => $loginEmail,
                'phone' => $request->phone,
                'ghana_card_id' => $request->ghana_card_id,
                'category' => $request->category,
                'referral_code' => $referrer?->code ?? $request->referral_code,
                'vendor_referrer_id' => $referrer?->id,
                'terms_accepted' => true,
                'status' => VendorApplicationStatus::Pending,
            ]);
        });

        if ($createdNewUser) {
            event(new Registered($user));
            Auth::login($user);

            SendAccountWelcomeSms::dispatch(
                $request->phone,
                trim($request->first_name),
            );

            SendAdminNewVendorRegistrationSms::dispatch(
                $user->fresh()->vendorApplication,
            );
        }

        Log::info('Vendor application stored.', [
            'created_new_user' => $createdNewUser,
            'user_id' => $user->id,
            'email_masked' => LogSanitizer::maskEmail($user->email),
            'db_connection' => config('database.default'),
            'db_database' => config('database.connections.'.config('database.default').'.database'),
        ]);

        AppLog::info('[VendorOnboarding] Application submitted.', [
            'user_id' => $user->id,
            'created_new_user' => $createdNewUser,
            'shop_name' => $request->shop_name,
            'category' => $request->category,
        ]);

        return redirect()
            ->route('vendor.inventory.index')
            ->with('vendorApplicationSubmitted', true);
    }
}
