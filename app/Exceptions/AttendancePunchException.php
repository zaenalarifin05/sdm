<?php

namespace App\Exceptions;

use RuntimeException;

class AttendancePunchException extends RuntimeException
{
    public static function invalidCredentials(): self
    {
        return new self('NIP atau PIN tidak valid.');
    }

    public static function noSchedule(): self
    {
        return new self('Jadwal shift yang sesuai tidak ditemukan.');
    }

    public static function alreadyCompleted(): self
    {
        return new self('Presensi untuk shift ini sudah lengkap.');
    }
}
