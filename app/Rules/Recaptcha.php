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
            $fail('Verifikasi keamanan gagal, silakan coba lagi.');

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

        $score = (float) ($data['score'] ?? 0);
        $threshold = (float) config('services.recaptcha.min_score', 0.5);

        if (! $response->ok() || ! ($data['success'] ?? false) || $score < $threshold) {
            $fail('Verifikasi keamanan gagal, silakan coba lagi.');
        }
    }
}
