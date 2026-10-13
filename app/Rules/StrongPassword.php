<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/** Kamida 8 belgi, harf va raqam, ommabop parollar emas. */
class StrongPassword implements ValidationRule
{
    private const COMMON = ['12345678', '123456789', '1234567890', 'password', 'password1', 'password123', 'qwerty123', 'qwertyuiop', '11111111', '00000000', 'admin123', 'admin1234', 'changeme123', 'changeme123!', 'parol123', 'parol1234', 'klinika123', 'iloveyou1', 'abc12345', 'qwerty12', 'test1234'];

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $v = (string) $value;
        if (mb_strlen($v) < 8 || ! preg_match('/\p{L}/u', $v) || ! preg_match('/\d/', $v)) {
            $fail(__("Parol kamida 8 belgi bo'lib, harf va raqamdan iborat bo'lishi kerak."));

            return;
        }
        if (in_array(mb_strtolower($v), self::COMMON, true)) {
            $fail(__('Bu parol juda oddiy. Boshqa, murakkabroq parol tanlang.'));
        }
    }
}
