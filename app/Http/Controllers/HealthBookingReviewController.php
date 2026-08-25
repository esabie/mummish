<?php

namespace App\Http\Controllers;

use App\Http\Requests\LookupHealthBookingReviewRequest;
use App\Http\Requests\StoreHealthBookingReviewRequest;
use App\Models\HealthBooking;
use App\Services\HealthBookingReviewService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class HealthBookingReviewController extends Controller
{
    public function create(Request $request): Response
    {
        return Inertia::render('HealthServices/ReviewLookup', [
            'prefill' => [
                'reference' => (string) $request->query('reference', ''),
                'patient_email' => (string) $request->query('email', ''),
            ],
            'error' => session('error'),
        ]);
    }

    public function lookup(
        LookupHealthBookingReviewRequest $request,
        HealthBookingReviewService $reviews,
    ): RedirectResponse {
        $validated = $request->validated();

        $booking = $reviews->findForLookup(
            $validated['reference'],
            $validated['patient_email'],
        );

        if ($booking === null) {
            return back()
                ->withInput()
                ->with('error', 'We could not find a completed booking with those details. Check your reference and email, then try again.');
        }

        $reviews->markVerified($booking);

        return redirect()->route('health-services.bookings.review.show', $booking);
    }

    public function show(
        Request $request,
        HealthBooking $booking,
        HealthBookingReviewService $reviews,
    ): Response|RedirectResponse {
        if (! $reviews->canView($request, $booking)) {
            return redirect()
                ->route('health-services.bookings.review')
                ->with('error', 'Enter your booking reference and email to leave a review.');
        }

        return Inertia::render('HealthServices/ReviewForm', [
            'booking' => $reviews->formatReviewPagePayload($booking),
            'status' => session('status'),
            'error' => session('error'),
        ]);
    }

    public function store(
        StoreHealthBookingReviewRequest $request,
        HealthBooking $booking,
        HealthBookingReviewService $reviews,
    ): RedirectResponse {
        if (! $reviews->canView($request, $booking)) {
            return redirect()
                ->route('health-services.bookings.review')
                ->with('error', 'Enter your booking reference and email to leave a review.');
        }

        try {
            $reviews->submitReview(
                $booking,
                (int) $request->validated('rating'),
                $request->validated('comment'),
            );
        } catch (RuntimeException $exception) {
            return redirect()
                ->route('health-services.bookings.review.show', $booking)
                ->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('health-services.bookings.review.show', $booking)
            ->with('status', 'Thanks — your review has been published.');
    }
}
