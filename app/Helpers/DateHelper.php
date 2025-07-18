<?php

namespace App\Helpers;

use Carbon\Carbon;

class DateHelper
{
	//! Format Tanggal Indonesia
	/**
	 * Konversi format tanggal ke format Indonesia.
	 *
	 * @param string $dateTime
	 * @param int $formatType
	 * @return string
	 */
	public static function formatTanggalIndonesia($dateTime, $formatType = 1)
	{
		if (!$dateTime) {
			return '-';
		}

		$carbonDate = Carbon::parse($dateTime)->setTimezone('Asia/Jakarta');
		// $carbonDate = Carbon::parse($dateTime)->setTimezone('Asia/Jakarta')->locale('id');

		// Mapping nama hari dan bulan dalam bahasa Indonesia
		$hari = $carbonDate->translatedFormat('l');
		$tanggal = $carbonDate->translatedFormat('j');
		$bulan = $carbonDate->translatedFormat('F');
		$tahun = $carbonDate->translatedFormat('Y');
		$jamMenit = $carbonDate->translatedFormat('H:i');

		switch ($formatType) {
			case 1: // Senin, 1 Januari 2025
				return "$hari, $tanggal $bulan $tahun";

			case 2: // Senin, 1 Januari 2025 pukul 15:30 WIB
				return "$hari, $tanggal $bulan $tahun pukul $jamMenit WIB";

			case 3: // 1 Januari 2025
				return "$tanggal $bulan $tahun";

			case 4: // 01-01-2025
				return $carbonDate->translatedFormat('d-m-Y');

			case 5: // 2025-01-01 15:30:00
				return $carbonDate->translatedFormat('Y-m-d H:i:s');

			case 6: // 01/01/2025
				return $carbonDate->translatedFormat('d/m/Y');

			default:
				return "$tanggal $bulan $tahun";
		}
	}
	//! Format Tanggal Indonesia

	//? Konversi Timezone (kalo diperlukan)
	/**
	 * Konversi datetime ke UTC sebelum disimpan ke database
	 */
	public static function toUTC($datetime)
	{
		return self::parseDatetime($datetime)->setTimezone('UTC');
	}

	/**
	 * Pastikan datetime bisa diubah ke format yang benar
	 */
	private static function parseDatetime($datetime, $timezone = null)
	{
		if ($datetime instanceof Carbon) {
			return $datetime;
		}

		return Carbon::parse($datetime, $timezone);
	}
	//? Konversi Timezone
}
