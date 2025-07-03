<?php

namespace App\Services;

use Midtrans\Config;
use Midtrans\Snap;

class MidtransService
{
	public function __construct()
	{
		Config::$serverKey = env('MIDTRANS_SERVER_KEY');
		Config::$isProduction = false;
		Config::$isSanitized = true;
		Config::$is3ds = true;
	}

	public function createTransaction(array $params)
	{
		return Snap::createTransaction($params);
	}
}
