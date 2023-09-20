<?
define('DEFAULT_CHARSET', 'gb2312');

if (!class_exists('DateTime')){
	class DateTime {
		public $date;

		public function __construct($date){
			$this->date = strtotime($date);
		}

		public function setTimeZone($timezone){
			return;
		}

		private function __getDate(){
			return date(DATE_ATOM, $this->date);
		}

		public function modify($multiplier){
			$this->date = strtotime($this->__getDate() . ' ' . $multiplier);
		}

		public function format($format){
			return date($format, $this->date);
		}
	}
}

function json_safe_encode($var){
	return json_encode(json_fix_encoding($var));
}

function json_fix_encoding($var){
	if (is_array($var)){
		$new = array();
		foreach ($var as $k => $v){
			$new[json_fix_encoding($k)] = json_fix_encoding($v);
		}
		$var = $new;
	} elseif (is_object($var)){
		$vars = get_class_vars(get_class($var));
		foreach ($vars as $m => $v){
			$var->$m = json_fix_encoding($v);
		}
	} elseif (is_string($var)){
		$var = @iconv(DEFAULT_CHARSET, 'utf-8', $var);
	}
	return $var;
}

function Transpose($array){
	if (!is_array($array)) return false;
	$return = array();
	foreach($array as $key => $value){
		if (!is_array($value)) return $array;
		foreach ($value as $key2 => $value2){
			$return[$key2][$key] = $value2;
		}
	}
	return $return;
}

function ToCSV($matrix){
	$result = '';
	for ($i = 0; $i < count($matrix); $i++){
		if ($i > 0) $result .= chr(10);
		for ($j = 0; $j < count($matrix[$i]); $j++){
			if ($j > 0) $result .= '|';
			$result .= $matrix[$i][$j];
		}
	}
	return $result;
}

function Contains($haystack, $needle, $ignore_case=true){
	if ($ignore_case)
		return stripos($haystack, $needle) !== FALSE;
	else
		return strpos($haystack, $needle) !== FALSE;
}

/**
 * StartsWith
 * Tests if a text starts with an given string.
 *
 * @param	 string
 * @param	 string
 * @return	bool
 */
function StartsWith($Haystack, $Needle){
	return strpos($Haystack, $Needle) === 0;
}

/**
 * EndsWith
 * Tests whether a text ends with the given
 * string or not.
 *
 * @param	 string
 * @param	 string
 * @return	bool
 */
function EndsWith($Haystack, $Needle){
	return strrpos($Haystack, $Needle) === strlen($Haystack) - strlen($Needle);
}

function Left($string, $chars){
	return substr($string, 0, $chars);
}

function Right($string, $chars){
	return substr($string, strlen($string)-$chars, $chars);
}

function ChineseWeekday($d){
	switch (substr($d, 0, 3)){
		case 'Mon':	return T($d,'一','一');
		case 'Tue':	return T($d,'二','二');
		case 'Wed':	return T($d,'三','三');
		case 'Thu':	return T($d,'四','四');
		case 'Fri':	return T($d,'五','五');
		case 'Sat':	return T($d,'六','六');
		case 'Sun':	return T($d,'日','日');
	}
}

function T($english, $simplified_chinese, $traditional_chinese = null){
	global $LANG;
	switch($LANG){
		case 'en':		return $english;
		case 'zh-cn':	return $simplified_chinese;
		case 'zh-tw':	return $traditional_chinese == null ? $simplified_chinese : $traditional_chinese;
	}
}

function if_null($s, $t){
	return $s ?: $t;
}

function nz($s){
	// null to zero
	return $s == '' ? 0 : str_replace(',', '', $s);
}

function CurrentURL(){
	$pageURL = 'http';
		if ($_SERVER['HTTPS'] == 'on')
		$pageURL .= 's';
	$pageURL .= '://';
	$pageURL .= $_SERVER['HTTP_HOST'];
//	$pageURL .= $_SERVER['SERVER_NAME'];
//	if ($_SERVER['SERVER_PORT'] != '80')
//		$pageURL .= ':'.$_SERVER['SERVER_PORT'];
	$pageURL .= $_SERVER['REQUEST_URI'];
	return $pageURL;
}

function GetPageName($uri){
	$p = strpos($uri, '?');
	if ($p) $uri = substr($uri, 0, $p);
	$p = strrpos($uri, '/');
	if ($p !== FALSE) $uri = substr($uri, $p + 1);
	return $uri;
}

function WriteLog($s = null){
	global $conn, $OPERATOR, $db_now, $last_log_sql;

	if (!$s) $s = CurrentURL();

	$act = sqlstr($s);
	$opr = sqlstr($OPERATOR);
	$addr = sqlstr($_SERVER['REMOTE_ADDR']);
	$agent = sqlstr($_SERVER['HTTP_USER_AGENT']);
	if (!isset($_SESSION['act'])||$_SESSION['act'] != $act){
		$stmt = db_query("INSERT INTO logfile (Time, Username, IPAddress, UserAgent, Activity) VALUES ($db_now, $opr, $addr, $agent, $act)");
		$_SESSION['act'] = $act;
	}
}
/*
function config($name){
	return db_query_first_result("SELECT $name FROM _configuration_");
}
*/
function getFilesFromDir($dir){
	$files = array();
	if ($handle = opendir($dir)){
		while (false !== ($file = readdir($handle))){
			if ($file != "." && $file != ".."){
				if(is_dir($dir.'/'.$file)){
					$dir2 = $dir.'/'.$file;
					$files[] = getFilesFromDir($dir2);
				}
				else {
					$files[] = $dir.'/'.$file;
				}
			}
		}
		closedir($handle);
	}
	return array_flat($files);
}

function array_flat($array){
	foreach($array as $a){
		if(is_array($a)){
			$tmp = array_merge($tmp, array_flat($a));
		}
		else {
			$tmp[] = $a;
		}
	}
	return $tmp;
}

/*
	$data = array('format' => '1');
	e.g. cURL_Request('http://www.wunderground.com/history/airport/KMMU/2012/1/3/CustomHistory.html', 'get', $data);
*/
function cURL_Request($url, $method = 'GET', $data = null, $headers = null){
	if(!function_exists('curl_init') &&
		!function_exists('curl_setopt') &&
		!function_exists('curl_exec') &&
		!function_exists('curl_close'))
		return 'UNAVAILABLE: cURL Basic Functions';

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
		return 'FAIL: curl_exec()';

	curl_close($ch);

	return $result;
}

/*==================================
Get url content and response headers (given a url, follows all redirections on it and returned content and response headers of final url)

@return	array[0]	content
		array[1]	array of response headers
==================================*/
function get_url($url, $javascript_loop=0, $timeout=5){
	$url = str_replace("&amp;", "&", urldecode(trim($url)));

	$cookie = tempnam(sys_get_temp_dir(), "CURLCOOKIE");
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; rv:1.7.3) Gecko/20041001 Firefox/0.10.1" );
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($ch, CURLOPT_ENCODING, "");
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_AUTOREFERER, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);	# required for https urls
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
	curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
	curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
	$content = curl_exec($ch);
	$response = curl_getinfo($ch);
	curl_close ( $ch );

	if ($response['http_code'] == 301 || $response['http_code'] == 302){
		ini_set("user_agent", "Mozilla/5.0 (Windows; U; Windows NT 5.1; rv:1.7.3) Gecko/20041001 Firefox/0.10.1");

		if ( $headers = get_headers($response['url']) ){
			foreach( $headers as $value ){
				if ( substr( strtolower($value), 0, 9 ) == "location:" )
					return get_url( trim( substr( $value, 9, strlen($value) ) ) );
			}
		}
	}

	if ( ( preg_match("/>[[:space:]]+window\.location\.replace\('(.*)'\)/i", $content, $value) || preg_match("/>[[:space:]]+window\.location\=\"(.*)\"/i", $content, $value) ) && $javascript_loop < 5
	){
		return get_url( $value[1], $javascript_loop+1 );
	}
	else{
		return array( $content, $response );
	}
}

function Authorized($page_uri){
	global $authorization;

	$page = GetPageName($page_uri);
//	if (!$page) $page = 'index.php';
	if (!$page) $page = $page_uri;

	if (isset($authorization) && array_key_exists($page, $authorization))
		return
			in_array('*', $authorization[$page]) ||
			isset($_SESSION['user_type']) && in_array($_SESSION['user_type'], $authorization[$page]) ||
			isset($_SESSION['username']) && in_array($_SESSION['username'], $authorization[$page]);

	$user_type = isset($_SESSION['user_type'])? str_replace("'", "''", $_SESSION['user_type']) : '';
	$username = isset($_SESSION['username'])? str_replace("'", "''", $_SESSION['username']) : '';

	// New approach: authorization table is now stored in database
//	$result = db_query_first_result("SELECT id FROM authorization WHERE (module='*' OR module='$page') AND (user='*' OR user='$user_type' OR user='$username')");
	$result = db_query_first_result("
SELECT id FROM authorization
WHERE (module='*'
	OR module RLIKE '^(.*[, ])?".mysql_real_escape_string(preg_quote($page))."([, ].*)?$'
)
AND (user='*'
	OR user RLIKE '^(.*[, ])?".mysql_real_escape_string(preg_quote($user_type))."([, ].*)?$'
	OR user RLIKE '^(.*[, ])?".mysql_real_escape_string(preg_quote($username))."([, ].*)?$'
)
	");
	if ($result)
		return true;

	return false;
}

function get($name){
	return isset($_REQUEST[$name])? $_REQUEST[$name] : '';
}
?>