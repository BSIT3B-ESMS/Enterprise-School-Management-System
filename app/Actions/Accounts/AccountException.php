<?php

namespace App\Actions\Accounts;

use RuntimeException;

/**
 * An account action the rules do not allow (for example deactivating the last HR Heads). The message is safe to show.
 */
class AccountException extends RuntimeException {}
