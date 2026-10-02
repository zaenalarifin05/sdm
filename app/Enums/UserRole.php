<?php

namespace App\Enums;

enum UserRole: string
{
    case Employee = 'employee';
    case Manager = 'manager';
    case HrAdmin = 'hr_admin';
    case Finance = 'finance';
    case SystemAdmin = 'system_admin';
}
