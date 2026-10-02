<?php

return [
    'policies' => [
        'absent_deduction' => [
            'enabled' => false,
            // Supported modes: fixed_per_day, salary_divisor_per_day
            'mode' => null,
            'value' => null,
        ],
        'late_deduction' => [
            'enabled' => false,
            // Supported modes: fixed_per_minute, fixed_per_incident
            'mode' => null,
            'value' => null,
        ],
        'overtime_pay' => [
            'enabled' => false,
            // Supported modes: fixed_per_minute, fixed_per_hour
            'mode' => null,
            'value' => null,
        ],
    ],
];
