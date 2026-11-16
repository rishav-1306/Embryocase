<?php

namespace App\Services;

use App\Models\User;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

class Google2FAService
{
    const BASE32_ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    const ISSUER = 'EMBRYOCASE';

    /**
     * Generate a cryptographically secure Base32 secret key.
     */
    public function generateSecretKey(int $length = 16): string
    {
        $secret = '';
        $alphabetLength = strlen(self::BASE32_ALPHABET);
        for ($i = 0; $i < $length; $i++) {
            $secret .= self::BASE32_ALPHABET[random_int(0, $alphabetLength - 1)];
        }
        return $secret;
    }

    /**
     * Generate otpauth URI for Google Authenticator.
     */
    public function getQrCodeUri(string $holder, string $secret, string $issuer = self::ISSUER): string
    {
        $label = rawurlencode($issuer . ':' . $holder);
        $encodedIssuer = rawurlencode($issuer);
        return "otpauth://totp/{$label}?secret={$secret}&issuer={$encodedIssuer}&algorithm=SHA1&digits=6&period=30";
    }

    /**
     * Render SVG QR Code data URI.
     */
    public function getQrCodeDataUri(string $otpauthUri): string
    {
        $qrcode = new QRCode();
        return $qrcode->render($otpauthUri);
    }

    /**
     * Calculate 6-digit TOTP code at a specific time slice (RFC 6238).
     */
    public function getCurrentOtp(string $secret, ?int $timeSlice = null): string
    {
        if ($timeSlice === null) {
            $timeSlice = (int) floor(time() / 30);
        }

        $secretKey = $this->base32Decode($secret);
        $time = pack('N*', 0) . pack('N*', $timeSlice);
        $hm = hash_hmac('sha1', $time, $secretKey, true);
        $offset = ord(substr($hm, -1)) & 0x0F;
        $hashpart = substr($hm, $offset, 4);
        $value = unpack('N', $hashpart)[1] & 0x7FFFFFFF;

        return str_pad((string) ($value % 1000000), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Verify a 6-digit code against secret with time window tolerance.
     */
    public function verifyKey(string $secret, string $key, int $window = 1): bool
    {
        $key = trim($key);
        if (strlen($key) !== 6 || !ctype_digit($key)) {
            return false;
        }

        $currentTimeSlice = (int) floor(time() / 30);
        for ($i = -$window; $i <= $window; $i++) {
            $expected = $this->getCurrentOtp($secret, $currentTimeSlice + $i);
            if (hash_equals($expected, $key)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get or initialize secret for a given user.
     */
    public function getOrCreateSecret(User $user): string
    {
        if (!empty($user->google2fa_secret)) {
            return $user->google2fa_secret;
        }

        $secret = $this->generateSecretKey(16);
        $user->google2fa_secret = $secret;
        $user->save();

        return $secret;
    }

    /**
     * Decode Base32 string to binary.
     */
    protected function base32Decode(string $b32): string
    {
        $b32 = strtoupper(trim($b32));
        $buffer = 0;
        $bits = 0;
        $binary = '';

        for ($i = 0; $i < strlen($b32); $i++) {
            $val = strpos(self::BASE32_ALPHABET, $b32[$i]);
            if ($val === false) {
                continue;
            }
            $buffer = ($buffer << 5) | $val;
            $bits += 5;
            if ($bits >= 8) {
                $bits -= 8;
                $binary .= chr(($buffer >> $bits) & 0xFF);
            }
        }

        return $binary;
    }
}
