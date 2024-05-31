<?php

namespace GP_Google_Sheets\Dependencies;

// Don't redefine the functions if included multiple times.
if (!\function_exists('GP_Google_Sheets\\Dependencies\\GuzzleHttp\\describe_type')) {
    require __DIR__ . '/functions.php';
}
