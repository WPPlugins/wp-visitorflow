<?php
	/**
	 * getToken($length)
	 * Returns a token of length $length
	 * Based on http://stackoverflow.com/questions/1846202/php-how-to-generate-a-random-unique-alphanumeric-string
	 **/
	function getToken($length)
	{
		$token = "";
		$codeAlphabet = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
		$codeAlphabet.= "abcdefghijklmnopqrstuvwxyz";
		$codeAlphabet.= "0123456789";
		$max = strlen($codeAlphabet);

		for ($i=0; $i < $length; $i++) {
			$token .= $codeAlphabet[cryptoRandSecure(0, $max-1)];
		}

		return $token;
	}

	/**
	 * cryptoRandSecure($min, $max)
	 * Returns a more secure random number than rand().
	 * Uses openssl_random_pseudo_bytes to help create a random number between $min and $max.
	 * Based on http://stackoverflow.com/questions/1846202/php-how-to-generate-a-random-unique-alphanumeric-string
	 **/
	function cryptoRandSecure($min, $max)
	{
		$range = $max - $min;
		if ($range < 1) return $min; 
		$log = ceil(log($range, 2));
		$bytes = (int) ($log / 8) + 1; 
		$bits = (int) $log + 1;
		$filter = (int) (1 << $bits) - 1; 
		do {
			$rnd = hexdec(bin2hex(openssl_random_pseudo_bytes($bytes)));
			$rnd = $rnd & $filter; 
		} while ($rnd > $range);
		return $min + $rnd;
	}
?>