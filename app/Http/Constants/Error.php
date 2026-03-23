<?php

namespace App\Http\Constants;

class Error
{
    public static $validationError = 'Please check your input(s).';
    public static $unauthorized    = 'You do not have permission to perform this action.';
    public static $notFound        = 'The requested resource was not found.';
    public static $serverError     = 'An internal server error occurred.';
}
