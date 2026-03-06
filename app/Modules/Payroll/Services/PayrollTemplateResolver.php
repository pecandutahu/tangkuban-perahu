<?php

namespace App\Modules\Payroll\Services;

use App\Models\PayrollTemplate;
use App\Models\Employee;

class PayrollTemplateResolver
{
    public static function resolve(Employee $employee): PayrollTemplate
    {
        $template = PayrollTemplate::where(
            'employment_type',
            $employee->employment_type
        )->first();

        if (!$template) {
            throw new \DomainException(
                "Payroll template not found for employment type: {$employee->employment_type}"
            );
        }

        return $template->load('components.component');
    }
}
