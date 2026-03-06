<?php

namespace App;

enum EmploymentType
{
    case PERMANENT = 'permanent';
    case CONTRACT = 'contract';
    case DAILY = 'daily';
}