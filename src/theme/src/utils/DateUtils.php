<?php

namespace App\Utils;

class DateUtils
{
	public static function formatDate($date, $format = "Y-m-d H:i:s"): string
	{
		$dateTime = new \DateTime($date);
		return $dateTime->format($format);
	}
}
