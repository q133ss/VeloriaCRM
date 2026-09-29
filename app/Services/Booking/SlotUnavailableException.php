<?php

namespace App\Services\Booking;

use RuntimeException;

/** The requested time is not free (any more): taken, outside the schedule or in the past. */
class SlotUnavailableException extends RuntimeException
{
}
