<?php

namespace App\Services;

class PaymeException extends \Exception
{
    public function __construct(public int $rpcCode, public array $rpcMessage, public ?string $rpcData = null)
    {
        parent::__construct($rpcMessage['en'] ?? 'Payme error');
    }

    public static function make(int $code, string $uz, string $ru, string $en, ?string $data = null): self
    {
        return new self($code, ['uz' => $uz, 'ru' => $ru, 'en' => $en], $data);
    }
}
