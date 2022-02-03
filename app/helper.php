<?php
if (!function_exists( 'generateRandomString' )) {
	function generateRandomString($length = 10) {
		$time = time ();
		$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$randomString = '';
		for($i = 0; $i < $length; $i ++) {
			$randomString .= $time . $characters [rand ( 0, strlen ( $characters ) - 1 )];
		}
		return $randomString;
	}
}