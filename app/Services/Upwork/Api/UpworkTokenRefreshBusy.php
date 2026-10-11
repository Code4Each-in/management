<?php

namespace App\Services\Upwork\Api;

/** Another process held the token-refresh lock for longer than the wait; transient, not an auth failure. */
class UpworkTokenRefreshBusy extends \RuntimeException
{
}
