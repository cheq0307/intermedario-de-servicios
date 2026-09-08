<?php

namespace App\Services\Accounts;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class SmsVerification
{
    public function configured(): bool
    {
        return filled(config('phone_verification.account_sid')) && filled(config('phone_verification.auth_token'))
            && filled(config('phone_verification.service_sid'));
    }

    private function request(string $endpoint, array $data): array
    {
        if (! $this->configured()) {
            throw ValidationException::withMessages(['phone' => 'El envío de SMS aún no está habilitado. Contacta a administración.']);
        }
        try {
            $response = Http::asForm()->withBasicAuth(config('phone_verification.account_sid'), config('phone_verification.auth_token'))
                ->connectTimeout(5)->timeout(15)->post('https://verify.twilio.com/v2/Services/'.config('phone_verification.service_sid').'/'.$endpoint, $data);
        } catch (ConnectionException $e) {
            throw ValidationException::withMessages(['phone' => 'No pudimos conectar con el servicio de SMS. Inténtalo más tarde.']);
        }
        if (! $response->successful()) {
            throw ValidationException::withMessages(['phone' => 'No fue posible validar el SMS. Solicita un código nuevo o inténtalo más tarde.']);
        }
        $payload = $response->json();
        if (! is_array($payload)) {
            throw ValidationException::withMessages(['phone' => 'El servicio de SMS no respondió correctamente. Inténtalo más tarde.']);
        }

        return $payload;
    }

    public function send(string $phone): string
    {
        $data = $this->request('Verifications', ['To' => '+52'.$phone, 'Channel' => 'sms', 'Locale' => 'es']);
        if (($data['status'] ?? '') !== 'pending' || empty($data['sid'])) {
            throw ValidationException::withMessages(['phone' => 'No se pudo iniciar la verificación.']);
        }

        return $data['sid'];
    }

    public function check(string $sid, string $code): bool
    {
        return ($this->request('VerificationCheck', ['VerificationSid' => $sid, 'Code' => $code])['status'] ?? '') === 'approved';
    }
}
