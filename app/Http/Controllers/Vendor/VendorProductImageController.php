<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Vendor\CheckProductImageRequest;
use App\Services\ProductImageQualityChecker;
use App\Support\AppLog;
use Illuminate\Http\JsonResponse;

class VendorProductImageController extends Controller
{
    public function check(CheckProductImageRequest $request, ProductImageQualityChecker $checker): JsonResponse
    {
        /** @var \Illuminate\Http\UploadedFile $file */
        $file = $request->file('image');

        AppLog::info('[Vendor] Product image quality check requested.', [
            'vendor_user_id' => $request->user()?->id,
            'file_name' => $file->getClientOriginalName(),
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
        ]);

        $result = $checker->check($file);

        AppLog::info('[Vendor] Product image quality check completed.', [
            'vendor_user_id' => $request->user()?->id,
            'pass' => $result->pass,
            'issue_count' => count($result->issues),
            'issues' => $result->issues,
            'width' => $result->width,
            'height' => $result->height,
        ]);

        return response()->json($result->toArray());
    }
}
