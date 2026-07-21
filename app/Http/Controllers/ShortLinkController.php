<?php

namespace App\Http\Controllers;

use App\Services\ShortLinkService;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ShortLinkController extends Controller
{
    public function __invoke(string $code, ShortLinkService $shortLinks): RedirectResponse
    {
        $target = $shortLinks->resolve($code);

        if ($target === null) {
            throw new NotFoundHttpException;
        }

        return redirect()->away($target);
    }
}
