<?php

namespace App;

enum PayrollStatus
{
    case DRAFT = 'draft';
    case REVIEWED = 'reviewed';
    case APPROVED = 'approved';
    case PAID = 'paid';
    case CLOSED = 'closed';
}
