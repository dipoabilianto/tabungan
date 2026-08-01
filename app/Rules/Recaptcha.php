<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Http;

class Recaptcha implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $secret = config('services.recaptcha.secret');

        // Belum dikonfigurasi -> lewati verifikasi (mis. di lingkungan lokal).
        if (blank($secret)) {
            return;
        }

        if (blank($value)) {
            $fail('Harap selesaikan verifikasi reCAPTCHA.');

            return;
        }

        try {
            $response = Http::asForm()->timeout(10)
                ->post('https://www.google.com/recaptcha/api/siteverify', [
                    'secret' => $secret,
                    'response' => $value,
                    'remoteip' => request()->ip(),
                ]);
        } catch (\Throwable $e) {
            $fail('Layanan verifikasi reCAPTCHA sedang bermasalah, silakan coba lagi.');

            return;
        }

        $data = $response->json();

        if (! $response->ok() || ! ($data['success'] ?? false)) {
            $fail('Verifikasi reCAPTCHA gagal, silakan coba lagi.');
        }
    }
}
