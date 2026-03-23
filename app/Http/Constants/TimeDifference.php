<?php
namespace App\Http\Constants;

class TimeDifference{
    public static function timeInHours($time): int {
        $currentTime = Now();
        return $currentTime->diff($time)->h;
    }
}