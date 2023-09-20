<?
require_once('mime_types.php');
$ext = pathinfo($_SERVER['QUERY_STRING'], PATHINFO_EXTENSION);
header('Content-type: '.$mime_types[$ext]);
die(cURL_Request($_SERVER['QUERY_STRING']));


function cURL_Request($url, $method = 'GET', $data = null, $headers = null){
	if(!function_exists('curl_init') &&
		!function_exists('curl_setopt') &&
		!function_exists('curl_exec') &&
		!function_exists('curl_close'))
		return 'UNAVAILABLE: cURL Basic Functions';

	$host = parse_url($url, PHP_URL_HOST);
	if (!$host)
		$url = $_SERVER['HTTP_HOST'].str_replace('get.php', $url, $_SERVER['PHP_SELF']);

	$ch = curl_init();
	if (!$ch)
		return 'FAIL: curl_init()';

	$cookie = tempnam(sys_get_temp_dir(), "CURLCOOKIE");
	$timeout = 5;

	curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; rv:1.7.3) Gecko/20041001 Firefox/0.10.1");
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($ch, CURLOPT_ENCODING, "");
	curl_setopt($ch, CURLOPT_AUTOREFERER, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);	# required for https urls
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);	# required for https urls
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
	curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
	curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
	curl_setopt($ch, CURLOPT_HEADER, false);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

	if (strtoupper($method) == 'POST'){
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
	}

	$result = curl_exec($ch);
	if (!$result)
//		return 'FAIL: curl_exec()';
		return 'File not exist';

	curl_close($ch);
	return $result;
}
?>