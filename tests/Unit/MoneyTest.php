<?php
use App\Support\Money;
it('subtracts decimal strings without floating point',function(){expect(Money::decimal(Money::cents('0.30')-Money::cents('0.10')))->toBe('0.20');});
