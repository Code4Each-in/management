<?php

namespace App\Services\Upwork\Api;

/** Thrown when anything other than an allowlisted read-only query would be sent to Upwork. */
class UpworkWriteRefused extends \RuntimeException
{
}
