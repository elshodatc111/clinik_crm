<?php

if (! function_exists('money')) {
    function money($v): string
    {
        return \App\Support\Tenant::money($v);
    }
}

if (! function_exists('phone_fmt')) {
    function phone_fmt(?string $p): string
    {
        $d = preg_replace('/\D+/', '', (string) $p);
        if (strlen($d) === 12) {
            return '+'.substr($d, 0, 3).' '.substr($d, 3, 2).' '.substr($d, 5, 3).' '.substr($d, 8, 2).' '.substr($d, 10, 2);
        }

        return (string) $p;
    }
}
