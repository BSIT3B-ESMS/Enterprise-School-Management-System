<?php

namespace App\Actions\Leave;

use RuntimeException;

/**
 * A leave action the rules do not allow (for example deciding a request that was already decided). The message is safe
 * to show to the person doing it.
 */
class LeaveActionException extends RuntimeException {}
