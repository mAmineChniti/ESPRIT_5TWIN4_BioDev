<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class TwoFactorAuthenticationService
{
    private const BASE32_ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    /**
     * Generate a random 32-character Base32 secret key.
     */
    public function generateSecretKey(): string
    {
        return $this->base32Encode(random_bytes(20));
    }

    /**
     * Generate a collection of random recovery codes.
     *
     * @return list<string>
     */
    public function generateRecoveryCodes(int $count = 8): array
    {
        $codes = [];

        for ($i = 0; $i < $count; $i++) {
            $codes[] = Str::upper(Str::random(5)).'-'.Str::upper(Str::random(5));
        }

        return $codes;
    }

    /**
     * Build the otpauth:// URI for the authenticator app.
     */
    public function qrCodeUrl(string $companyName, string $email, string $secret): string
    {
        return sprintf(
            'otpauth://totp/%s:%s?secret=%s&issuer=%s',
            rawurlencode($companyName),
            rawurlencode($email),
            $secret,
            rawurlencode($companyName)
        );
    }

    /**
     * Generate the inline SVG QR Code string.
     */
    public function qrCodeSvg(string $email, string $secret): string
    {
        $url = $this->qrCodeUrl(config('app.name', 'NutriTrace'), $email, $secret);

        return (string) QrCode::format('svg')
            ->size(200)
            ->margin(1)
            ->generate($url);
    }

    /**
     * Verify a 6-digit TOTP code against a secret key.
     */
    public function verify(string $secret, string $code, int $window = 1): bool
    {
        $code = trim($code);

        if (strlen($code) !== 6 || ! ctype_digit($code)) {
            return false;
        }

        $currentTime = time();
        $secretBytes = $this->base32Decode($secret);

        if ($secretBytes === '') {
            return false;
        }

        for ($i = -$window; $i <= $window; $i++) {
            $timestamp = $currentTime + ($i * 30);
            $calculatedCode = $this->calculateCode($secretBytes, $timestamp);

            if (hash_equals($calculatedCode, $code)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Verify and consume a recovery code for the user.
     */
    public function verifyAndConsumeRecoveryCode(User $user, string $code): bool
    {
        $code = strtoupper(trim($code));
        $codes = $user->two_factor_recovery_codes ?? [];

        foreach ($codes as $index => $recoveryCode) {
            if (hash_equals(strtoupper($recoveryCode), $code)) {
                unset($codes[$index]);
                $user->forceFill([
                    'two_factor_recovery_codes' => array_values($codes),
                ])->save();

                return true;
            }
        }

        return false;
    }

    /**
     * Calculate 6-digit TOTP code for a given timestamp.
     */
    public function calculateCode(string $secretBytes, int $timestamp): string
    {
        $counter = (int) floor($timestamp / 30);
        $binaryCounter = pack('N*', 0).pack('N*', $counter);
        $hash = hash_hmac('sha1', $binaryCounter, $secretBytes, true);

        $offset = ord(substr($hash, -1)) & 0x0F;
        $truncatedHash = substr($hash, $offset, 4);
        $code = unpack('N', $truncatedHash)[1] & 0x7FFFFFFF;

        return str_pad((string) ($code % 1000000), 6, '0', STR_PAD_LEFT);
    }

    public function base32Encode(string $data): string
    {
        $binary = '';
        $length = strlen($data);

        for ($i = 0; $i < $length; $i++) {
            $binary .= str_pad(decbin(ord($data[$i])), 8, '0', STR_PAD_LEFT);
        }

        $b32 = '';
        $binaryLength = strlen($binary);

        for ($i = 0; $i < $binaryLength; $i += 5) {
            $chunk = substr($binary, $i, 5);
            if (strlen($chunk) < 5) {
                $chunk = str_pad($chunk, 5, '0', STR_PAD_RIGHT);
            }
            $b32 .= self::BASE32_ALPHABET[bindec($chunk)];
        }

        return $b32;
    }

    public function base32Decode(string $b32): string
    {
        $b32 = strtoupper(trim($b32));
        $binary = '';
        $length = strlen($b32);

        for ($i = 0; $i < $length; $i++) {
            $pos = strpos(self::BASE32_ALPHABET, $b32[$i]);
            if ($pos === false) {
                continue;
            }
            $binary .= str_pad(decbin($pos), 5, '0', STR_PAD_LEFT);
        }

        $bytes = '';
        $binaryLength = strlen($binary);

        for ($i = 0; $i + 8 <= $binaryLength; $i += 8) {
            $bytes .= chr(bindec(substr($binary, $i, 8)));
        }

        return $bytes;
    }
}
