<?php

namespace Talampaya\Utils;

class RequestUtils
{
	// -----------------------------------------------------------------------------
	// Make Curl Request
	// -----------------------------------------------------------------------------
	public static function makeRequest($url, $method, $data = null, $headers = []): array
	{
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		if ($method === "POST") {
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		}

		if (!empty($headers)) {
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		}

		$response = curl_exec($ch);
		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);

		return ["response" => $response, "http_code" => $httpCode];
	}
}
