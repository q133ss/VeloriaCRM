<?php

namespace App\Services\Booking;

use RuntimeException;

/** ЮKassa would not take the payment (shop misconfigured, API down); the booking was rolled back. */
class PrepaymentFailedException extends RuntimeException
{
}
