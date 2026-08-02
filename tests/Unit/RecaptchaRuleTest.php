<?php

namespace Tests\Unit;

use App\Rules\Recaptcha;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RecaptchaRuleTest extends TestCase
{
    public function test_lewat_ketika_belum_dikonfigurasi(): void
    {
        config()->set('services.recaptcha.secret', null);

        $failed = false;
        (new Recaptcha)->validate('g-recaptcha-response', null, function () use (&$failed) {
            $failed = true;
        });

        $this->assertFalse($failed);
    }

    public function test_gagal_ketika_token_kosong(): void
    {
        config()->set('services.recaptcha.secret', 'secret');

        $failed = false;
        (new Recaptcha)->validate('g-recaptcha-response', '', function () use (&$failed) {
            $failed = true;
        });

        $this->assertTrue($failed);
    }

    public function test_lewat_ketika_google_menerima(): void
    {
        Http::fake([
            'google.com/recaptcha/*' => Http::response(['success' => true, 'score' => 0.9], 200),
        ]);
        config()->set('services.recaptcha.secret', 'secret');

        $failed = false;
        (new Recaptcha)->validate('g-recaptcha-response', 'token-valid', function () use (&$failed) {
            $failed = true;
        });

        $this->assertFalse($failed);
    }

    public function test_gagal_ketika_google_menolak(): void
    {
        Http::fake([
            'google.com/recaptcha/*' => Http::response(['success' => false], 200),
        ]);
        config()->set('services.recaptcha.secret', 'secret');

        $failed = false;
        (new Recaptcha)->validate('g-recaptcha-response', 'token-invalid', function () use (&$failed) {
            $failed = true;
        });

        $this->assertTrue($failed);
    }

    public function test_gagal_ketika_skor_rendah(): void
    {
        Http::fake([
            'google.com/recaptcha/*' => Http::response(['success' => true, 'score' => 0.1], 200),
        ]);
        config()->set('services.recaptcha.secret', 'secret');

        $failed = false;
        (new Recaptcha)->validate('g-recaptcha-response', 'token-robot', function () use (&$failed) {
            $failed = true;
        });

        $this->assertTrue($failed);
    }
}
