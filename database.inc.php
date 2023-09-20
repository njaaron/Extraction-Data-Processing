<?
// Copyright (c) 2010 Changying Dong
// All Rights Reserved
putenv("NLS_LANG=AMERICAN_AMERICA.AL32UTF8");
//putenv("NLS_LANG=AMERICAN_AMERICA.WE8ISO8859P1");

	$connOptions = array(
		'Username'		=> 'lims',
		'Password'		=> 'lims',
		'Server'			=> 'IALORA5',
		'Port'			=> '1521',
		'Service'		=> 'IALIMS3'
	);

$conn = oci_connect($connOptions['Username'], $connOptions['Password'], $connOptions['Server'].(isset($connOptions['Port'])?':'.$connOptions['Port']:'').(isset($connOptions['Service'])?'/'.$connOptions['Service']:''));

if (!$conn){
	$e = oci_error();   // For oci_connect errors do not pass a handle
	trigger_error(htmlentities($e['message']), E_USER_ERROR);
	die();
}

$db_now = 'CURRENT_DATE';
$db_dayname = "TO_CHAR(TO_DATE(xxxxxx, 'dd/mm/yyyy'), 'DAY')";
$db_ifnull = 'NVL';

function FormatErrors($errors){
	$msg = "Error information: <br/>";
	foreach ($errors as $error){
		$msg .= "SQLSTATE: ".$error['SQLSTATE']."<br>";
		$msg .= "Code: ".$error['code']."<br>";
		$msg .= "Message: ".$error['message']."<br>";
	}
	return $msg;
}

function db_exec($sql){
	global $conn;

	$stmt = oci_parse($conn, $sql);
	if (!$stmt){
		$e = oci_error($conn);  // For oci_parse errors pass the connection handle
		trigger_error(htmlentities($e['message']), E_USER_ERROR);
		die();
	}
	return oci_execute($stmt);
}

function db_query($sql){
	global $conn;

	$stmt = oci_parse($conn, $sql);
	if (!$stmt){
		$e = oci_error($conn);  // For oci_parse errors pass the connection handle
		trigger_error(htmlentities($e['message']), E_USER_ERROR);
		die();
	}
	$result = oci_execute($stmt);
	if (!$result){
//		die($sql);
		$e = oci_error($stmt);  // For oci_execute errors pass the statement handle
		print htmlentities($e['message']);
		print "\n<pre>\n";
		print htmlentities($e['sqltext']);
		printf("\n%".($e['offset']+1)."s", "^");
		print "\n</pre>\n";
	}
	return $stmt;
}

function db_num_rows($stmt){
	$result = oci_num_rows($stmt);
	if ($result === false)
		die(FormatErrors(oci_error($conn)));
	return $result;
}

function db_num_fields($stmt){
	$result = oci_num_fields($stmt);
	if ($result === false)
		die(FormatErrors(oci_error($conn)));
	return $result;
}

function db_field_metadata($stmt){
	$result = oci_field_metadata($stmt);
	if ($result === false)
		die(FormatErrors(oci_error($conn)));
	return $result;
}

function db_field_name($stmt, $i){
	$result = oci_field_name($stmt, $i + 1);
	if ($result === false)
		die('oci_field_name'.FormatErrors(oci_error($conn)));
	return $result;
}

function db_result($stmt, $i){
	$result = oci_result($stmt, $i + 1);
	if ($result === false)
		die(FormatErrors(oci_error($conn)));
	return $result;
}

function db_field_type($stmt, $i){
	$result = oci_field_type($stmt, $i + 1);
	if ($result === false)
		die(FormatErrors(oci_error($conn)));
	return $result;
}

function db_field_len($stmt, $i){
	$result = oci_field_size($stmt, $i + 1);
	if ($result === false)
		die(FormatErrors(oci_error($conn)));
	return $result;
}

function db_field_precision($stmt, $i){
	$result = oci_field_precision($stmt, $i + 1);
	if ($result === false)
		die(FormatErrors(oci_error($conn)));
	return $result;
}

function db_field_scale($stmt, $i){
	$result = oci_field_scale($stmt, $i + 1);
	if ($result === false)
		die(FormatErrors(oci_error($conn)));
	return $result;
}

function db_field_is_null($stmt, $i){
	$result = oci_field_is_null($stmt, $i + 1);
	if ($result === false)
		die(FormatErrors(oci_error($conn)));
	return $result;
}

function db_field_flags($stmt, $i){
	$result = oci_field_metadata($stmt);
	if ($result === false)
		die(FormatErrors(oci_error($conn)));
	$arr = array();
	$arr['Nullable'] = $metadata[$i]['Nullable']==OCI_NULLABLE_YES ? true : false;
	return $arr;
}

function db_fetch_array($stmt){
	return oci_fetch_array($stmt, OCI_NUM | OCI_RETURN_NULLS | OCI_RETURN_LOBS);
}

function db_fetch_assoc($stmt){
	return oci_fetch_array($stmt, OCI_ASSOC | OCI_RETURN_NULLS | OCI_RETURN_LOBS);
}

function db_fetch_both($stmt){
	return oci_fetch_array($stmt, OCI_BOTH | OCI_RETURN_NULLS | OCI_RETURN_LOBS);
}

function db_fetch($stmt){
	$result = oci_fetch($stmt);
	if ($result === false)
		die(FormatErrors(oci_error($conn)));
	return $result;
}

function db_get_field($stmt, $i, $type = NULL){
	if (is_null($type))
		$result = oci_get_field($stmt, $i);
	else
		$result = oci_get_field($stmt, $i, $type);
	if ($result === false)
		die(FormatErrors(oci_error($conn)) . $i);
	return $result;
}

function db_affected_rows($stmt){
	$result = oci_rows_affected($stmt);
	if ($result === false)
		die(FormatErrors(oci_error($conn)));
	return $result;
}

function db_query_first_row($sql){
	return db_fetch_array(db_query($sql));
}

function db_query_json_encode_first_row($sql){
	return json_encode(db_query_first_row($sql)?:'');
}

function db_query_first_result($sql){
	$result = db_query_first_row($sql);
	return $result[0];
}

function db_query_json_encode_first_result($sql){
	return json_encode(db_query_first_result($sql)?:'');
}

function db_query_fetch_assoc($sql){
	return db_fetch_assoc(db_query($sql));
}

function db_query_results($sql){
	return db_fetch_array(db_query($sql));
}

function db_query_json_encode_assoc($sql){
	return db_json_encode_assoc(db_query($sql));
}
function db_query_json_encode($sql){
	return db_json_encode(db_query($sql));
}

function db_json_encode_assoc($stmt){
	return json_encode(db_fetch_all_assoc($stmt));
}
function db_json_encode($stmt){
	return json_encode(db_fetch_all_array($stmt));
}

function db_query_json_encode_with_metadata($sql){
	return db_json_encode_with_metadata(db_query($sql));
}

function db_json_encode_with_metadata($stmt){
	return json_encode(array(
		'metadata'=>db_field_metadata($stmt),
		'matrix'=>db_fetch_all_array($stmt)
	));
}

function db_query_json_encode_with_column_names($sql){
	return db_json_encode_with_column_names(db_query($sql), $sql);
}

function db_json_encode_with_column_names($stmt, $sql=''){
	return json_encode(array(
		'headers' => db_column_names($stmt),
		'rows' => db_fetch_all_array($stmt),
		'query' => $sql
	));
}

function db_column_names($stmt){
	$names = array();
	for ($i = 0; $i < db_num_fields($stmt); $i++){
		$names[] = db_field_name($stmt, $i);
	}
	return $names;
}

function db_fetch_all_array($stmt){
	$matrix = array();
	while ($row = db_fetch_array($stmt)){
		$matrix[] = $row;
	}
	return $matrix;
}
function db_fetch_all_assoc($stmt){
	$matrix = array();
	while($row = db_fetch_assoc($stmt)){
		$matrix[] = $row;
	}
	return $matrix;
}

function db_query_fetch_all_array($sql){
	return db_fetch_all_array(db_query($sql));
}
function db_query_fetch_all_assoc($sql){
	return db_fetch_all_assoc(db_query($sql));
}

function sqlstr($s, $if_empty='NULL'){
	if (is_string($s))
		return $s? "'".str_replace("'", "''", $s)."'" : $if_empty;
	else
		return $s? : $if_empty;
}
?>
