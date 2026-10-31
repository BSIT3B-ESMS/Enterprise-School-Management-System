<?php
namespace App\Support;
class Money {
    public static function cents(string $value): int {
        if (!preg_match('/^(0|[1-9][0-9]{0,11})\.[0-9]{2}$/',$value)) throw new \InvalidArgumentException('Invalid decimal money string.');
        [$whole,$fraction] = explode('.',$value);
        return ((int) $whole)*100 + (int) $fraction;
    }
    public static function decimal(int $cents): string { return intdiv($cents,100).'.'.str_pad((string)($cents%100),2,'0',STR_PAD_LEFT); }
}
