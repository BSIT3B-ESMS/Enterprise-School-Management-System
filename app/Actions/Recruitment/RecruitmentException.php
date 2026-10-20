<?php

namespace App\Actions\Recruitment;

use RuntimeException;

/**
 * A recruitment action the rules do not allow (for example scheduling an interview in the past). The message is safe to show
 * to the person doing it.
 */
class RecruitmentException extends RuntimeException {}
