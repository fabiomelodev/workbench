<?php

namespace Tests\Unit;

use App\Services\PhoneNumberService;
use PHPUnit\Framework\TestCase;

class PhoneNumberServiceTest extends TestCase
{
    private PhoneNumberService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PhoneNumberService();
    }

    public function test_mobile_from_whatsapp_url(): void
    {
        $result = $this->service->parse('https://wa.me/5511991234567');

        $this->assertTrue($result['valid']);
        $this->assertSame(PhoneNumberService::MOBILE, $result['type']);
        $this->assertSame('5511991234567', $result['digits']);
        $this->assertSame('+5511991234567', $result['e164']);
    }

    public function test_local_mobile_without_country_code(): void
    {
        $result = $this->service->parse('11991234567');

        $this->assertSame(PhoneNumberService::MOBILE, $result['type']);
        $this->assertSame('5511991234567', $result['digits']);
    }

    public function test_landline_is_detected(): void
    {
        $result = $this->service->parse('1133334444');

        $this->assertTrue($result['valid']);
        $this->assertSame(PhoneNumberService::LANDLINE, $result['type']);
        $this->assertSame('(11) 3333-4444', $result['national']);
    }

    public function test_formatted_landline_is_detected(): void
    {
        $result = $this->service->parse('+55 11 3333-4444');

        $this->assertSame(PhoneNumberService::LANDLINE, $result['type']);
    }

    public function test_local_number_with_area_code_55_is_not_treated_as_international(): void
    {
        // DDD 55 (Santa Maria/RS), fixo de 10 dígitos: não pode virar internacional.
        $result = $this->service->parse('5532109999');

        $this->assertSame(PhoneNumberService::LANDLINE, $result['type']);
        $this->assertSame('(55) 3210-9999', $result['national']);
    }

    public function test_garbage_input_has_no_type(): void
    {
        $result = $this->service->parse('abc');

        $this->assertFalse($result['valid']);
        $this->assertNull($result['type']);
    }

    public function test_invalid_number_is_flagged(): void
    {
        $result = $this->service->parse('123');

        $this->assertFalse($result['valid']);
        $this->assertSame(PhoneNumberService::INVALID, $result['type']);
    }

    public function test_blank_input_returns_empty(): void
    {
        $result = $this->service->parse(null);

        $this->assertFalse($result['valid']);
        $this->assertNull($result['type']);
        $this->assertNull($result['digits']);
    }

    public function test_classify_shortcut_returns_type(): void
    {
        $this->assertSame(PhoneNumberService::MOBILE, $this->service->classify('11991234567'));
        $this->assertSame(PhoneNumberService::LANDLINE, $this->service->classify('1133334444'));
    }
}
