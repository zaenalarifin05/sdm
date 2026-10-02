<?php

return [
    'supported_modes' => [
        'absent_deduction' => [
            'fixed_per_day',
            'salary_divisor_per_day',
        ],
        'late_deduction' => [
            'fixed_per_minute',
            'fixed_per_incident',
        ],
        'overtime_pay' => [
            'fixed_per_minute',
            'fixed_per_hour',
        ],
    ],
];
