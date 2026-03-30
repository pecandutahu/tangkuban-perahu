<?php

namespace App\Modules\Payroll\Services;

use App\Models\PayrollTemplate;
use App\Models\Employee;

class PayrollTemplateResolver
{
    public static function resolve(Employee $employee, $cachedTemplates = null): PayrollTemplate
    {
        // 1. Coba cari template Spesifik: (employment_type + position_id)
        if ($employee->position_id) {
            $specificTemplate = null;

            if ($cachedTemplates) {
                $specificTemplate = $cachedTemplates->first(function ($t) use ($employee) {
                    return $t->employment_type === $employee->employment_type &&
                           $t->position_id === $employee->position_id;
                });
            } else {
                $specificTemplate = PayrollTemplate::where('employment_type', $employee->employment_type)
                    ->where('position_id', $employee->position_id)
                    ->first();
            }

            if ($specificTemplate) {
                return $cachedTemplates ? $specificTemplate : $specificTemplate->load('components.component');
            }
        }

        // 2. Fallback: Cari template General: (employment_type + position_id IS NULL)
        $generalTemplate = null;
        if ($cachedTemplates) {
            $generalTemplate = $cachedTemplates->first(function ($t) use ($employee) {
                return $t->employment_type === $employee->employment_type &&
                       $t->position_id === null;
            });
        } else {
            $generalTemplate = PayrollTemplate::where('employment_type', $employee->employment_type)
                ->whereNull('position_id')
                ->first();
        }

        if ($generalTemplate) {
            return $cachedTemplates ? $generalTemplate : $generalTemplate->load('components.component');
        }

        // 3. Nyerah: Tidak Ada Template
        throw new \DomainException(
            "Payroll template not found. Missing template for employment type: {$employee->employment_type}" .
            ($employee->position_id ? " or position ID: {$employee->position_id}" : "")
        );
    }
}
