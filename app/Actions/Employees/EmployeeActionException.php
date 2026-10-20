<?php

namespace App\Actions\Employees;

use RuntimeException;

/**
 * An employee action the rules do not allow. The message is safe to show to the person doing it.
 */
class EmployeeActionException extends RuntimeException {}
