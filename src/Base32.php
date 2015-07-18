<?php

namespace maximal\base32;

use InvalidArgumentException;

/**
 * Class Base32
 * @package maximal\base32
 */
class Base32
{
	const ALPHA_DEFAULT = '0123456789ABCDEFGHJKMNPQRTUVWXYZ';
	// @link https://en.wikipedia.org/wiki/Base32
	const ALPHA_RFC4648 = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
	const ALPHA_EXTHEX  = '0123456789ABCDEFGHIJKLMNOPQRSTUV';
	const ALPHA_NATAREA = '0123456789ABCDFGHJKLMNPQRSTVWXYZ';


	protected $alphabet = null;
	protected $table = null;
	protected $lookup = null;


	/**
	 * @param null $alphabet
	 */
	public function __construct($alphabet = null) {
		if ($alphabet === null) {
			$alphabet = self::ALPHA_DEFAULT;
		}

		if (!is_string($alphabet)) {
			throw new InvalidArgumentException('$alphabet argument must be a string.');
		}

		if (strlen($alphabet) !== 32) {
			throw new InvalidArgumentException('$alphabet argument must contain 32 characters.');
		}
		
		$table = [];
		$lookup = [];
		for ($i = 0, $len = strlen($alphabet); $i < $len; ++$i) {
			$table[$i] = $alphabet[$i];
			$lookup[$alphabet[$i]] = $i;
		}
		
		$this->alphabet = $alphabet;
		$this->table = $table;
		$this->lookup = $lookup;
	}

	/**
	 * Encode the string to Base32.
	 * @param $string
	 * @return string
	 */
	public function encode($string)
	{
		$reminder = 0;
		$reminderBits = 0;
		$ord = 0;

		$table = $this->table;
		$result = '';

		for ($i = 0, $len = strlen($string); $i < $len; ++$i) {
			$ord = ord($string[$i]);
			$reminderBits = 3 + $reminderBits;

			$index = ($ord >> $reminderBits) & 0b11111 | $reminder << (8 - $reminderBits);
			$reminder = $ord & ((0b1 << $reminderBits) - 1);
			$result .= $table[$index];

			if ($reminderBits > 4) {
				$reminderBits -= 5;
				$index = ($ord >> $reminderBits) & 0b11111;
				$reminder = $ord & ((0b1 << $reminderBits) - 1);
				$result .= $table[$index];
			}
		}

		if ($reminderBits > 0) {
			$index = $ord & ((0b1 << $reminderBits) - 1);
			$result .= $table[$index];
		}
		return $result;
	}

	/**
	 * Decode Base32 to string.
	 * @param $base32
	 * @return string
	 */
	public function decode($base32)
	{
		$bits = 0;

		$lookup = $this->lookup;

		$chunk = 0;
		$result = '';

		$len = strlen($base32);
		for ($i = 0; $i < $len; ++$i) {
			$char = $base32[$i];
			if (!isset($lookup[$char])) {
				throw new InvalidArgumentException(
					'Invalid character `' . $char . '` in $base32 argument. ' .
					'Valid characters: ' . $this->alphabet
				);
			}
			$ord = $lookup[$char];
			$chunk = $chunk << 5 | $ord;
			$bits += 5;

			if ($bits > 7) {
				$bits -= 8;
				$result .= chr($chunk >> $bits & 0b11111111);
				$chunk = $chunk & ((0b1 << $bits) - 1);
			}
		}

		if ($bits > 0) {
			$result[strlen($result) - 1] = chr(
				ord($result[strlen($result) - 1]) | $chunk & ((0b1 << $bits) - 1)
			);
		}

		return $result;
	}
}


mb_internal_encoding('UTF-8');


$base32 = new Base32;

var_dump($base32->encode('fzda0'));
var_dump($base32->decode('CTX68R9G'));

var_dump($base32->encode('fzda'));
var_dump($base32->decode('CTX68R1'));

var_dump($base32->encode('1'));
var_dump($base32->decode('61'));

var_dump($base32->encode('1024'));
var_dump($base32->decode('64R34D0'));

var_dump($base32->encode('Привет, мир!'));
var_dump($base32->decode('U2FX306GQ38B5M5NU612R86GQK8BHMC041'));

var_dump($base32->encode('Поехали!'));
var_dump($base32->decode('U2FX1FPGPQ8RBM5GU2XX1E11'));
