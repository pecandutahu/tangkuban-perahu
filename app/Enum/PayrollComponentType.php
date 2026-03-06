<?php

namespace App\Enum;

enum PayrollComponentType: string
{
    case EARNING = 'earning';
    
    case DEDUCTION = 'deduction';
}
