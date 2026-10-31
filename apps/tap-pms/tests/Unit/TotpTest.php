<?php
it('matches the RFC HOTP value used by TOTP',function(){expect(\App\Services\Totp::code('GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ',1))->toBe('287082');});
