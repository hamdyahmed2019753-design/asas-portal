<?php

namespace App\Exceptions;

use RuntimeException;

class DuplicateInterestException extends RuntimeException
{
    public function __construct(string $message = 'لديك طلب اهتمام قائم على هذا العقد قيد المعالجة بالفعل.')
    {
        parent::__construct($message);
    }
}
