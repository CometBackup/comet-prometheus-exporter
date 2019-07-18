<?php

/**
 * Make an HTTP request to the Comet Server
 *
 * @ref https://cometbackup.com/docs/api#php-curl
 * @return {string|boolean} HTTP response body, or false on cURL failure
 */
function comet_request($endpoint, array $params=[]) {		
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, getenv('COMET_SERVER_URL').'api/v1/admin/list-users');
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(array_merge(
		[
			'Username' => getenv('COMET_ADMIN_USER'),
			'AuthType' => 'Password',
			'Password' => getenv('COMET_ADMIN_PASS')
		],
		$params
	)));
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	$response = curl_exec($ch);
	curl_close($ch);
	return $response;
}
