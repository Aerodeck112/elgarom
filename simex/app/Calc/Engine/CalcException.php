<?php

namespace App\Calc\Engine;

use RuntimeException;

/** The model cannot be evaluated (unsupported function, circular reference, malformed formula). */
class CalcException extends RuntimeException {}
