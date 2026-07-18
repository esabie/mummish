<?php

namespace Tests\Unit;

use App\Services\MnotifySmsService;
use PHPUnit\Framework\TestCase;

class MnotifySmsServiceTest extends TestCase
{
    public function test_normalize_leading_zero_to_ghana_country_code(): void
    {
        $this->assertSame('233241234567', MnotifySmsService::normalizeToInternationalDigits('0241234567'));
        $this->assertSame('233241234567', MnotifySmsService::normalizeToInternationalDigits('+233 24 123 4567'));
    }

    public function test_normalize_preserves_existing_233_prefix(): void
    {
        $this->assertSame('233241234567', MnotifySmsService::normalizeToInternationalDigits('233241234567'));
    }

    public function test_normalize_nine_digit_local_without_leading_zero(): void
    {
        $this->assertSame('233241234567', MnotifySmsService::normalizeToInternationalDigits('241234567'));
    }

    public function test_is_mnotify_success_code_1000(): void
    {
        $this->assertTrue(MnotifySmsService::isMnotifySuccess(['code' => '1000']));
        $this->assertTrue(MnotifySmsService::isMnotifySuccess(['code' => 1000]));
    }

    public function test_is_mnotify_success_code_2000_bms_v2(): void
    {
        $payload = [
            'status' => 'success',
            'code' => '2000',
            'message' => 'messages sent successfully',
        ];
        $this->assertTrue(MnotifySmsService::isMnotifySuccess($payload));
        $this->assertTrue(MnotifySmsService::isMnotifySuccess(['code' => '2000']));
    }

    public function test_is_mnotify_success_rejects_non_success_status_with_ok_code(): void
    {
        $this->assertFalse(MnotifySmsService::isMnotifySuccess([
            'status' => 'error',
            'code' => '2000',
        ]));
    }

    public function test_is_mnotify_success_rejects_other_codes(): void
    {
        $this->assertFalse(MnotifySmsService::isMnotifySuccess(['code' => '1004']));
        $this->assertFalse(MnotifySmsService::isMnotifySuccess(['status' => 'success']));
        $this->assertFalse(MnotifySmsService::isMnotifySuccess([]));
        $this->assertFalse(MnotifySmsService::isMnotifySuccess(null));
    }

    public function test_format_recipient_for_quick_api_uses_leading_zero_local(): void
    {
        $this->assertSame('0241234567', MnotifySmsService::formatRecipientForQuickApi('0241234567'));
        $this->assertSame('0241234567', MnotifySmsService::formatRecipientForQuickApi('+233 24 123 4567'));
        $this->assertSame('0241234567', MnotifySmsService::formatRecipientForQuickApi('241234567'));
    }

    public function test_append_query_key(): void
    {
        $this->assertSame(
            'https://api.mnotify.com/api/sms/quick?key=ab%2Fc',
            MnotifySmsService::appendQueryKey('https://api.mnotify.com/api/sms/quick', 'ab/c')
        );
        $this->assertSame(
            'https://api.mnotify.com/api/sms/quick?x=1&key=secret',
            MnotifySmsService::appendQueryKey('https://api.mnotify.com/api/sms/quick?x=1', 'secret')
        );
    }

    public function test_mask_phone_for_log_masks_digits(): void
    {
        $this->assertSame('233***4567', MnotifySmsService::maskPhoneForLog('233241234567'));
        $this->assertSame('024***4567', MnotifySmsService::maskPhoneForLog('0241234567'));
    }

    public function test_mask_phone_for_log_empty_and_short(): void
    {
        $this->assertSame('(empty)', MnotifySmsService::maskPhoneForLog(''));
        $this->assertSame('***', MnotifySmsService::maskPhoneForLog('12345'));
    }
}
