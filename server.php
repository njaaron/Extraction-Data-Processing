<?php
/*
if(!session_id()){
	@session_start();
}

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
if(isset($_SESSION['form_submitted'])) {
	unset($_SESSION['form_submitted']);
	header('Location: ?' . uniqid());
	#header('Refresh: 0');
}
*/
if ($_SERVER['REQUEST_METHOD'] == 'GET')
	header('Location: index.php');

require_once 'functions.inc.php';
//require_once 'db_pdo_oracle.inc.php';
require_once 'database.inc.php';

$_MATRIX = 'bMATRIX';
$_GROUP = 'bGROUP';
$_BATCH = 'BATCH_GC';
$_DATA = 'DATA_GC';
$_DATAF = 'DATA_GCF';
$_REFERENCE = 'GC_REFERENCE';
$_REAGENT = 'REAGENT_GC';
$_REAGENT_REFERENCE = 'REAGENT_GC_REFERENCE';
$_TESTLIST = 'TEST_LIST';
$_BID = 'GE_ID';
$_BATCHED_ON = 'bDATE';
$_BATCHED_BY = 'TECHNICIAN';
$_IS_LEACHATE = "(TEST.NAME LIKE '%SPLP%' OR TEST.NAME LIKE '%TCLP%')";
$_TO_EXT_MATRIX = "CASE
	WHEN ALIQUOT.MATRIX_TYPE IN ('Aqueous', 'Drinking Water', 'Waste Water') OR $_IS_LEACHATE THEN 'Aqueous'
	WHEN ALIQUOT.MATRIX_TYPE NOT IN ('Liquid', 'Solid by SW-846 3580A (Waste Dilution)', 'Wipes') THEN 'Soil'
	ELSE ALIQUOT.MATRIX_TYPE
END";

$_TO_EXT_GROUP = "CASE
	WHEN TEST.GROUP_ID IN (5) AND U_TEST_SELECTION_USER.U_EXT_TYPE LIKE '%Herb%' THEN 'Herb'
	WHEN TEST.GROUP_ID IN (5) AND U_TEST_SELECTION_USER.U_EXT_TYPE IN ('PCB', 'Pesticides') THEN (
		CASE
			WHEN NVL(U_METHOD_LIST.NAME, '') LIKE '%608%' THEN 'PCB/Pest by 608.3'
			ELSE 'PCB/Pest'
		END
	)
	WHEN TEST.GROUP_ID IN (3, 5) AND NVL(U_METHOD_LIST.NAME, '') LIKE '%8011%' THEN '8011'
	WHEN TEST.GROUP_ID IN (3, 5) AND NVL(U_METHOD_LIST.NAME, '') LIKE '%504.1%' THEN '504.1'
	ELSE U_TEST_SELECTION_USER.U_EXT_TYPE
END";

$_TO_EXT_TEST = "CASE
	WHEN NOT $_IS_LEACHATE AND TEST.NAME LIKE '%PCB%' AND TEST.NAME LIKE '%Pest%' THEN 'PCB/Pest'
	WHEN NOT $_IS_LEACHATE AND TEST.NAME LIKE '%PCB%' THEN 'PCB'
	WHEN NOT $_IS_LEACHATE AND TEST.NAME LIKE '%Pest%' THEN 'Pesticides'
	WHEN TEST.NAME LIKE '%SPLP%' AND TEST.NAME LIKE '%PCB%' AND TEST.NAME LIKE '%Pest%' THEN 'SPLP PCB/Pest'
	WHEN TEST.NAME LIKE '%SPLP%' AND TEST.NAME LIKE '%PCB%' THEN 'SPLP PCB'
	WHEN TEST.NAME LIKE '%SPLP%' AND TEST.NAME LIKE '%Pest%' THEN 'SPLP Pesticides'
	WHEN TEST.NAME LIKE '%TCLP%' AND TEST.NAME LIKE '%PCB%' AND TEST.NAME LIKE '%Pest%' THEN 'TCLP PCB/Pest'
	WHEN TEST.NAME LIKE '%TCLP%' AND TEST.NAME LIKE '%PCB%' THEN 'TCLP PCB'
	WHEN TEST.NAME LIKE '%TCLP%' AND TEST.NAME LIKE '%Pest%' THEN 'TCLP Pesticides'
	WHEN NOT $_IS_LEACHATE AND TEST.NAME LIKE '%Herb%' THEN 'Herbicides'
	WHEN TEST.NAME LIKE '%SPLP%' AND TEST.NAME LIKE '%Herb%' THEN 'SPLP Herbicide'
	WHEN TEST.NAME LIKE '%TCLP%' AND TEST.NAME LIKE '%Herb%' THEN 'TCLP Herbicide'
	WHEN TEST.NAME LIKE '%NJ-EPH%' AND TEST.NAME LIKE '%DRO)%' THEN 'NJ-EPH-DRO'
	WHEN TEST.NAME LIKE '%NJ-EPH%' AND TEST.NAME LIKE '%C40)%' THEN 'NJ-EPH-C40'
	WHEN TEST.NAME LIKE '%NJ-EPH%' AND TEST.NAME LIKE '%Fraction%' THEN 'NJ-EPH'
	WHEN TEST.NAME NOT LIKE '%GC Fingerprint%' AND TEST.NAME NOT LIKE '%TPH-DOR-C44%' THEN 'TPH-DRO'
	WHEN TEST.NAME LIKE '%TPH-DRO-C44%' THEN 'TPH-DRO-C44'
	WHEN TEST.NAME LIKE '%GC Fingerprint%' THEN 'GC Fingerprint'
	WHEN TEST.NAME LIKE '%TPH-QAM025%' THEN 'TPH-QAM025'
	WHEN TEST.NAME LIKE '%CT ETPH%' THEN 'CT ETPH'
	ELSE TEST.NAME
END";

$q = $_POST['q'];

switch($q){
	case 'Retrieve U_FIELD_ID':
		$aliquot = $_POST['aliquot'];
		die(db_query_json_encode_first_result("
			SELECT ALIQUOT_USER.U_FIELD_ID
			FROM
				ALIQUOT,
				ALIQUOT_USER
			WHERE ALIQUOT.ALIQUOT_ID = ALIQUOT_USER.ALIQUOT_ID
				AND ALIQUOT.NAME = '$aliquot'
		"));

	case 'List Matrix':
		$type = isset($_POST['type'])? $_POST['type'] : '';
		$group = isset($_POST['group'])? $_POST['group'] : '';
		die(db_query_json_encode("
			SELECT $_MATRIX FROM $_REFERENCE
			WHERE 1=1
			--	AND (EFFECTIVE_DATE IS NULL OR SYSDATE > EFFECTIVE_DATE)
				AND (EXPIRATION_DATE IS NULL OR SYSDATE < EXPIRATION_DATE)
			".($type?" AND EXTRACTION_TYPE='$type'":'')."
			".($group?" AND $_GROUP='$group'":'')."
			GROUP BY $_MATRIX
			ORDER BY $_MATRIX
		"));

	case 'List Group':
		$type = isset($_POST['type'])? $_POST['type'] : '';
		$matrix = isset($_POST['matrix'])? $_POST['matrix'] : '';
		die(db_query_json_encode("
			SELECT $_GROUP FROM $_REFERENCE
			WHERE 1=1
			--	AND (EFFECTIVE_DATE IS NULL OR SYSDATE > EFFECTIVE_DATE)
				AND (EXPIRATION_DATE IS NULL OR SYSDATE < EXPIRATION_DATE)
			".($type?" AND EXTRACTION_TYPE='$type'":'')."
			".($matrix?" AND $_MATRIX='$matrix'":'')."
			GROUP BY $_GROUP
			ORDER BY $_GROUP
		"));

	case 'Get Default Values':
		$type = isset($_POST['type'])? $_POST['type'] : '';
		$matrix = isset($_POST['matrix'])? $_POST['matrix'] : '';
		$group = isset($_POST['group'])? $_POST['group'] : '';
	//	$sample_type = isset($_POST['sample_type'])? $_POST['sample_type'] : 'Samples';
	//	if ($sample_type == 'BLK') $sample_type = 'Blank';
		die(db_query_json_encode_assoc("
			SELECT * FROM $_REFERENCE
			WHERE $_MATRIX = '$matrix' AND $_GROUP = '$group'
			".($type?" AND EXTRACTION_TYPE='$type'":'')."
		"));

	case 'Get Blank Test':
		$batch_id = isset($_POST['batch_id'])? $_POST['batch_id'] : '';
		die(json_encode(db_query_first_result("
			SELECT t.NAME
			FROM $_BATCH b
			LEFT JOIN $_DATA d ON d.BID = b.$_BID
			LEFT JOIN $_TESTLIST t ON t.TID = d.TID
			WHERE b.nBatch_ID = '$batch_id' AND d.SAMPLE_TYPE = 'BLK'
		")));
/*
	case 'Get Default Test':
		$type = isset($_POST['type'])? $_POST['type'] : '';
		$matrix = isset($_POST['matrix'])? $_POST['matrix'] : '';
		$group = isset($_POST['group'])? $_POST['group'] : '';
		$sample_type = isset($_POST['sample_type'])? $_POST['sample_type'] : 'Samples';
	//	if ($sample_type == 'BLK') $sample_type = 'Blank';
		die(db_query_json_encode_assoc("
			SELECT
				BLK_TEST			AS \"Blank Test\"
			FROM $_REFERENCE
			WHERE $_MATRIX='$matrix' AND $_GROUP='$group'
				".($type?" AND EXTRACTION_TYPE='$type'":'')."
				".($sample_type?" AND SAMPLE_TYPE LIKE '%$sample_type%'":'')."
		"));
*/
	case 'Get Prep Method':
		$matrix = isset($_POST['matrix'])? $_POST['matrix'] : '';
		$group = isset($_POST['group'])? $_POST['group'] : '';
		$blk_test = isset($_POST['blk_test'])? $_POST['blk_test'] : '';
		die(db_query_json_encode_assoc("
			SELECT
				PREP_METHOD			AS \"Prep Method\"
			FROM $_REFERENCE
			WHERE $_MATRIX='$matrix' AND $_GROUP='$group'
				".($blk_test?"AND BLK_TEST = '$blk_test'":"")."
				AND ROWNUM = 1
		"));

	case 'Get Reagent Reference':
		$group = isset($_POST['group'])? $_POST['group'] : '';
		$matrix = isset($_POST['matrix'])? $_POST['matrix'] : '';
		$blk_test = isset($_POST['blk_test'])? $_POST['blk_test'] : '';
		$task = isset($_POST['task'])? $_POST['task'] : '';
		die(db_query_json_encode_assoc("
			SELECT
				REAGENT_TYPE,
				REAGENT_ORDER,
				REAGENT_NAME,
				OPTIONAL
			FROM $_REAGENT_REFERENCE
			WHERE 1 = 1
				AND BGROUP = '$group'
				AND BMATRIX = '$matrix'
				".($blk_test?"AND BLK_TEST = '$blk_test'":'')."
				AND REAGENT_TYPE".($task=='Fractionation'?"":" NOT")." IN ('Fractionation')
			ORDER BY INSTR('Surrogate, Spike, Solvent', REAGENT_TYPE), REAGENT_ORDER, ID
		"));

	case 'Reagent Default':
		$reagent_type = $_POST['reagent_type'];
		$reagent_name = $_POST['reagent_name'];
		$group = $_POST['group'];
		$matrix = $_POST['matrix'];
		$blk_test = isset($_POST['blk_test'])? $_POST['blk_test'] : '';
		$batch_id = $_POST['batch_id'];
		die(db_query_json_encode_assoc("
			SELECT * FROM (
				SELECT
					REAGENT_LOT,
					TO_CHAR(REAGENT_EXPDATE, 'YYYY-MM-DD')	AS \"REAGENT_EXPDATE\",
					TO_CHAR(REAGENT_PASDATE, 'YYYY-MM-DD')	AS \"REAGENT_PASDATE\"
				FROM $_REAGENT
				LEFT JOIN $_BATCH ON $_BATCH.$_BID = $_REAGENT.$_BID
				LEFT JOIN $_DATA ON $_DATA.bid = $_BATCH.$_BID AND $_DATA.SAMPLE_TYPE = 'BLK'
				LEFT JOIN $_TESTLIST ON $_TESTLIST.TID = $_DATA.TID
				WHERE REAGENT_TYPE = '$reagent_type'
					AND REAGENT_NAME = '$reagent_name'
					AND bGroup='$group' AND bMatrix='$matrix'
					AND nBatch_ID < '$batch_id'
					AND (
						manual_injection = 0 AND $_BATCH.SURROGATED_ON IS NOT NULL OR
						manual_injection = 1 AND $_BATCH.$_BATCHED_ON IS NOT NULL
					)
					".($blk_test?"AND $_TESTLIST.NAME = '$blk_test'":'')."
				ORDER BY nBatch_ID DESC
			) WHERE ROWNUM = 1
		"));

	case 'Load Reagent':
		$bid = isset($_POST['bid'])? intval($_POST['bid']) : '';
		$task = isset($_POST['task'])? $_POST['task'] : '';
		die(db_query_json_encode_assoc("
			SELECT
				REAGENT_TYPE,
				REAGENT_NAME,
				REAGENT_LOT,
				TO_CHAR(REAGENT_EXPDATE, 'MM/DD/YYYY')	AS \"REAGENT_EXPDATE\",
				TO_CHAR(REAGENT_PASDATE, 'MM/DD/YYYY')	AS \"REAGENT_PASDATE\"
			FROM $_REAGENT
			WHERE $_BID = $bid
				AND REAGENT_TYPE".($task=='Fractionation'?"":" NOT")." IN ('Fractionation')
			ORDER BY INSTR('Surrogate, Spike, Solvent', REAGENT_TYPE)
		"));

	case 'Save Reagent':
		$bid = isset($_POST['bid'])? intval($_POST['bid']) : '';
		$task = isset($_POST['task'])? $_POST['task'] : '';
		$reagent = isset($_POST['reagent'])? $_POST['reagent'] : array();
		db_exec("
			DELETE FROM $_REAGENT WHERE $_BID = $bid
				AND REAGENT_TYPE".($task=='Fractionation'?"":" NOT")." IN ('Fractionation')
		");

		$ids = array();
		foreach($reagent as $row){
			$fields = array($_BID);
			$values = array($bid);
			foreach($row as $key => $value){
				array_push($fields, $key);
				array_push($values, ($key == 'REAGENT_EXPDATE'||$key == 'REAGENT_PASDATE')?
					"TO_DATE(:$key, 'YYYY-MM-DD')":
					":$key"
				);
			//	array_push($values, $key == 'REAGENT_EXPDATE'?
			//		"TO_DATE('$value', 'YYYY-MM-DD')":
			//		($key == $_BID? $value : "'$value'")
			//	);
			}
			$sql = "
				INSERT INTO $_REAGENT (".implode(', ', $fields).")
				VALUES (".implode(', ', $values).")
				RETURNING REAGENT_ID INTO :ID
			";
		//	die($sql);
			$stmt = oci_parse($conn, $sql);
			foreach($row as $key => $value){
				if (strstr($sql, ":$key"))
					oci_bind_by_name($stmt, ":$key", $row[$key]);
			}
			oci_bind_by_name($stmt, ":ID", $ID, 10);
			if (oci_execute($stmt))
				array_push($ids, $ID);
			else
				die(print_r(oci_error($stmt)));
		}
		die(json_encode($ids));

	case 'Get Start End Date':
		$batch_id = $_POST['batch_id'];
		die(json_encode(db_query_fetch_assoc("
			SELECT
				TO_CHAR(MIN(d.analyze_date), 'MM/DD/YYYY HH24:MI') AS \"Start Date\",
				TO_CHAR(MAX(
					CASE
						WHEN b.manual_injection > 0 THEN d.TRANSFERRED_ON
						ELSE (
							CASE
								WHEN bGroup = 'NJ-EPH' THEN f.FRACTIONATED_ON
								ELSE d.TRANSFERRED_ON
							END
						)
					END
				), 'MM/DD/YYYY HH24:MI') AS \"End Date\"
			FROM $_DATA d
			LEFT JOIN $_DATAF f ON f.SID = d.ID
			LEFT JOIN $_BATCH b ON b.$_BID = d.BID
			WHERE nBatch_ID = '$batch_id'
		")));

	case 'Get Max Start Date':
		$batch_id = $_POST['batch_id'];
		die(json_encode(db_query_fetch_assoc("
			SELECT
				TO_CHAR(MAX(d.analyze_date), 'MM/DD/YYYY HH24:MI') AS \"Start Date\"
			FROM $_DATA d
			LEFT JOIN $_DATAF f ON f.SID = d.ID
			LEFT JOIN $_BATCH b ON b.$_BID = d.BID
			WHERE nBatch_ID = '$batch_id'
		")));

	case 'Missing End Date':
		$batch_id = $_POST['batch_id'];
		die(json_encode(db_query_fetch_assoc("
			SELECT COUNT(*) AS \"Count\"
			FROM $_DATA d
			LEFT JOIN $_DATAF f ON f.SID = d.ID
			LEFT JOIN $_BATCH b ON b.$_BID = d.BID
			WHERE nBatch_ID = '$batch_id' AND d.TRANSFERRED_ON IS NULL
		")));

	case 'Save End Time':
		$batch_id = $_POST['batch_id'];
		$end_time = $_POST['end_time'];
		db_exec("
			UPDATE $_DATA SET
				TRANSFERRED_ON = TO_DATE('$end_time', 'MM/DD/YYYY HH24:MI')
			WHERE
				BID IN (
					SELECT $_BID FROM $_BATCH
					WHERE nBatch_ID = '$batch_id'
				)
				AND
					TRANSFERRED_ON IS NULL
		");
		die(json_encode(0));

	case '1':
		$nBatch_ID = $_POST['nBatch_ID'];
		$sql = "
			SELECT
				nBatch_ID AS \"Batch ID\",
				CASE
					WHEN manual_injection > 0 THEN 'Manual Injection'
					ELSE 'GC Extraction'
				END AS \"Type\",
				bMatrix AS \"Matrix\",
				bGroup AS \"Group\",
			--	m.PREP_METHOD AS \"Prep Method\",
				bSonication AS \"Sonication\",
				Rush AS \"Rush\",
				bStatus AS \"Status\",
				CASE
					WHEN EXTRACTION_DATE IS NULL OR SYSDATE - EXTRACTION_DATE < 23/24 THEN 0
					ELSE 1
				END AS \"Is Closed\",
				TO_CHAR(b.Extraction_Date, 'MM/DD/YYYY HH24:MI') AS \"Extraction Date\",
				TO_CHAR(x.Batched_On, 'MM/DD/YYYY HH24:MI') AS \"Batched On\",
				x.Batched_By AS \"Batched By\",
				TO_CHAR(x.Weighed_On, 'MM/DD/YYYY HH24:MI') AS \"Weighed On\",
				x.Weighed_By AS \"Weighed By\",
				TO_CHAR(x.Surrogated_On, 'MM/DD/YYYY HH24:MI') AS \"Surrogated On\",
				x.Surrogated_By AS \"Surrogated By\",
			--	TO_CHAR(x.Filtered_On, 'MM/DD/YYYY HH24:MI') AS \"Filtered On\",
			--	x.Filtered_By AS \"Filtered By\",
				TO_CHAR(x.Transferred_On, 'MM/DD/YYYY HH24:MI') AS \"Transferred On\",
				x.Transferred_By AS \"Transferred By\",
				TO_CHAR(x.Fractionated_On, 'MM/DD/YYYY HH24:MI') AS \"Fractionated On\",
				x.Fractionated_By AS \"Fractionated By\",
			--	TO_CHAR(x.Shipped_On, 'MM/DD/YYYY HH24:MI') AS \"Shipped On\",
			--	x.Shipped_By AS \"Shipped By\",
			--	Cnt_Sample,
			--	Cnt_Batched,
			--	Cnt_Transferred,
				CASE WHEN Cnt_Transferred = Cnt_Sample THEN 1 ELSE 0 END	AS \"Completed\",
				Tray AS \"Tray\",
				bIAS AS \"Sample Surrogate\",
				bIAS_SPK_1 AS \"Spike 1\",
				bIAS_SPK_2 AS \"Spike 2\",
				Solvent_Lot AS \"Solvent Lot\",
			--	f_surrogate AS \"Fractionation Surrogate\",
			--	f_solvent_lot AS \"Fractionation Tube Lot\",
				MDL_Study AS \"MDL Study\",
				NO_MS_MSD_DUP AS \"No MS/MSD/DUP\",
				REASON AS \"Reason\",
				NO_MS_MSD_DUP_2 AS \"No MS/MSD/DUP 2\",
				REASON_2 AS \"Reason 2\",
				QC_FAIL AS \"QC Fail\",
				FAIL_REASON AS \"Fail Reason\",
				FAIL_BY AS \"Fail By\",
				TO_CHAR(FAIL_ON, 'MM/DD/YYYY HH24:MI') AS \"Fail On\",
				QC_FAIL_2 AS \"QC Fail 2\",
				FAIL_REASON_2 AS \"Fail Reason 2\",
				FAIL_BY_2 AS \"Fail By 2\",
				TO_CHAR(FAIL_ON_2, 'MM/DD/YYYY HH24:MI') AS \"Fail On 2\",
				QC_APPROVAL AS \"QC Approval\",
				QC_APPROVAL_TYPE AS \"QC Type\",
				QC_APPROVAL_NOTE AS \"Approval Note\",
				QC_APPROVAL_BY AS \"Approval By\",
				TO_CHAR(QC_APPROVAL_ON, 'MM/DD/YYYY HH24:MI') AS \"Approval On\",
				QC_APPROVAL_2 AS \"QC Approval 2\",
				QC_APPROVAL_TYPE_2 AS \"QC Type 2\",
				QC_APPROVAL_NOTE_2 AS \"Approval Note 2\",
				QC_APPROVAL_BY_2 AS \"Approval By 2\",
				TO_CHAR(QC_APPROVAL_ON_2, 'MM/DD/YYYY HH24:MI') AS \"Approval On 2\",
				1-NMSD AS \"MSD\",
				$_BID AS \"ABID\"
			FROM $_BATCH b
			LEFT JOIN (
				SELECT
					COUNT(*)					AS Cnt_Sample,
					SUM(CASE WHEN d.ANALYZE_DATE IS NULL THEN 0 ELSE 1 END)	AS Cnt_Batched,
					SUM(CASE WHEN d.WEIGHED_ON IS NULL THEN 0 ELSE 1 END)		AS Cnt_Weighed,
					SUM(CASE WHEN d.SURROGATED_ON IS NULL THEN 0 ELSE 1 END)	AS Cnt_Surrogated,
					SUM(CASE WHEN d.TRANSFERRED_ON IS NULL THEN 0 ELSE 1 END)	AS Cnt_Transferred,
					SUM(CASE WHEN f.FRACTIONATED_ON IS NULL THEN 0 ELSE 1 END)	AS Cnt_Fractionated,
					SUM(CASE WHEN d.SHIPPED_ON IS NULL THEN 0 ELSE 1 END)	AS Cnt_Shipped,
					MAX(d.ANALYZE_DATE)		AS Batched_On,
					MAX(d.BATCHED_BY)		AS Batched_By,
					MAX(d.WEIGHED_ON)		AS Weighed_On,
					MAX(d.WEIGHED_BY)		AS Weighed_By,
					MAX(d.SURROGATED_ON)	AS Surrogated_On,
					MAX(d.SURROGATED_BY)	AS Surrogated_By,
					MAX(d.TRANSFERRED_ON)	AS Transferred_On,
					MAX(d.TRANSFERRED_BY)	AS Transferred_By,
					MAX(f.FRACTIONATED_ON)	AS Fractionated_On,
					MAX(f.FRACTIONATED_BY)	AS Fractionated_By,
					MAX(d.SHIPPED_ON)		AS Shipped_On,
					MAX(d.SHIPPED_BY)		AS Shipped_By,
					MAX(d.SHIPPING_INDEX)	AS Shipping_Index,
					BID
				FROM $_DATA d, $_DATAF f
				WHERE f.SID (+) = d.ID
				GROUP BY BID
			) x ON b.$_BID = x.BID
		--	LEFT JOIN GC_PREP_METHOD m ON b.bGroup = m.TEST_GROUP AND b.bMatrix = m.BATCH_MATRIX
			WHERE b.nBatch_ID = '$nBatch_ID'
		";
		$result = db_query_fetch_assoc($sql);
		$numeric_fields = array('MDL Study', 'No MS/MSD/DUP', 'No MS/MSD/DUP 2', 'QC Fail', 'QC Fail 2', 'QC Approval', 'QC Approval 2', 'Is Closed', 'MSD', 'ABID');
		foreach($numeric_fields as $field)
			$result[$field] = +$result[$field];
		die(json_encode($result));

	case '2':
		$nBatch_ID = $_POST['nBatch_ID'];
		$fields = json_decode($_POST['fields']);

		$DB_FIELD = array(
			'Type'							=> 'd.SAMPLE_TYPE',
			'V/C#'							=> 'd.VAPNO',
			'Year'							=> 'd.dYear',
			'JOB'							=> 'd.JOB',
			'SAMPLE'						=> 'd.SAMPLE',
			'Sample ID'						=> "CASE
		WHEN d.SAMPLE_TYPE = 'DUP' AND LENGTH(d.JOB) = 5 THEN d.JOB||'-'||LPAD(d.SAMPLE, 3, '0')||d.SAMPLE_TYPE
		WHEN d.SAMPLE_TYPE IS NULL THEN d.JOB||'-'||LPAD(d.SAMPLE, 3, '0')
		ELSE d.JOB
	END",
			'ALIQUOT'						=> 'd.ALIQUOT',
			'Sample ID<br>(Original Jar)'	=> 'NULL',
			'Sample ID<br>(40ml Vial)'		=> 'NULL',
			'Funnel'						=> 'd.funnel',
			'Test'							=> 'd.TID',
			'Test Name'					=> 't.NAME',
			'Initial'							=> 'd.Initial_Wt',
			'Final'							=> 'd.Final_Wt',
			'Surrogate'						=> "TRIM(SUBSTR(d.IAS_Samp, INSTR(d.IAS_Samp, ':')+1))",
			'Spike 1'						=> "TRIM(SUBSTR(d.IAS_Spike_1, INSTR(d.IAS_Spike_1, ':')+1))",
			'Spike 2'						=> "TRIM(SUBSTR(d.IAS_Spike_2, INSTR(d.IAS_Spike_2, ':')+1))",
			'Color'							=> 'd.Color',
			'%SED'							=> 'd.SILT',
			'NH'							=> 'd.NH',
			'pH'							=> 'd.PH_GC',
			'Jar'							=> 'd.Jar',
			'pH for Hydrolysis'				=> 'd.PH_HYDROLYSIS',
			'pH for Acid'					=> 'd.PH_ACID',
			'Comments'						=> 'd.Comments',
			'Moist'							=> "(SELECT
	(CASE
		WHEN t.NAME LIKE 'TCLP%' OR t.NAME LIKE 'SPLP%' THEN '100'
	ELSE
		ALIQUOT_USER.U_MOIST
	END)
FROM
	ALIQUOT,
	ALIQUOT_USER,
	TEST,
	TEST_USER,
	U_TEST_SELECTION,
	U_TEST_SELECTION_USER,
	U_METHOD_LIST,
	$_TESTLIST
WHERE ROWNUM = 1
	AND ALIQUOT.ALIQUOT_ID = TEST.ALIQUOT_ID
	AND ALIQUOT.ALIQUOT_ID = ALIQUOT_USER.ALIQUOT_ID
	AND TEST.TEST_ID = TEST_USER.TEST_ID
	AND TEST_USER.U_TLIST = U_TEST_SELECTION.U_TEST_SELECTION_ID
	AND U_TEST_SELECTION.U_TEST_SELECTION_ID = U_TEST_SELECTION_USER.U_TEST_SELECTION_ID
	AND TEST_USER.U_TMETHOD = U_METHOD_LIST.U_METHOD_LIST_ID (+)
	AND $_TESTLIST.TID = d.TID
	".MatrixCondition("b.bMatrix").GroupCondition("b.bGroup").TestCondition2()."
	AND ALIQUOT.NAME = d.ALIQUOT
)",
			'Done'							=> "d.Done",
			'Batched On'					=> "TO_CHAR(d.analyze_date, 'MM/DD/YYYY HH24:MI')",
			'Batched By'					=> "d.BATCHED_BY",
			'Batched On/By'				=> "TO_CHAR(d.analyze_date, 'MM/DD/YYYY HH24:MI')||'<br>'||d.BATCHED_BY",
			'Weighed On'					=> "TO_CHAR(d.WEIGHED_ON, 'MM/DD/YYYY HH24:MI')",
			'Weighed By'					=> "d.WEIGHED_BY",
			'Weighed On/By'				=> "TO_CHAR(d.WEIGHED_ON, 'MM/DD/YYYY HH24:MI')||'<br>'||d.WEIGHED_BY",
			'Surrogated On'					=> "TO_CHAR(d.SURROGATED_ON, 'MM/DD/YYYY HH24:MI')",
			'Surrogated By'					=> "d.SURROGATED_BY",
			'Surrogated On/By'				=> "TO_CHAR(d.SURROGATED_ON, 'MM/DD/YYYY HH24:MI')||'<br>'||d.SURROGATED_BY",
			'Transferred On'				=> "TO_CHAR(d.TRANSFERRED_ON, 'MM/DD/YYYY HH24:MI')",
			'Transferred By'				=> "d.TRANSFERRED_BY",
			'Transferred On/By'				=> "TO_CHAR(d.TRANSFERRED_ON, 'MM/DD/YYYY HH24:MI')||'<br>'||d.TRANSFERRED_BY",
			'Remove'						=> '0',
			'C40'							=> "''",
			'Aliphatic Hexane Initial'		=> 'f.ALIPHATIC_INITIAL',
			'Aliphatic Hexane Final'			=> 'f.ALIPHATIC_FINAL',
			'Aromatic CH2CI2 Initial'		=> 'f.AROMATIC_INITIAL',
			'Aromatic CH2CI2 Final'			=> 'f.AROMATIC_FINAL',
			'Surrogate IAS #'				=> 'f.SURROGATE',
			'Lot # of FR Tube'				=> 'f.LOT',
			'Fractionation Color'			=> 'f.Color',
			'Fractionation Comments'		=> 'f.Comments',
		//	'Fractionation Done'				=> 'f.Done',
			'Fractionated On'				=> "TO_CHAR(f.FRACTIONATED_ON, 'MM/DD/YYYY HH24:MI')",
			'Fractionated By'				=> "f.FRACTIONATED_BY",
			'Fractionated On/By'			=> "TO_CHAR(f.FRACTIONATED_ON, 'MM/DD/YYYY HH24:MI')||'<br>'||f.FRACTIONATED_BY",
			'Shipped On'					=> "TO_CHAR(d.SHIPPED_ON, 'MM/DD/YYYY HH24:MI')",
			'Shipped By'					=> 'd.SHIPPED_BY',
			'Shipped On/By'				=> "TO_CHAR(d.SHIPPED_ON, 'MM/DD/YYYY HH24:MI')||'<br>'||d.SHIPPED_BY",
			'Shipping Index'				=> "(SELECT CASE WHEN d.SHIPPED_ON IS NULL THEN NVL(MAX(dd.SHIPPING_INDEX), 0)+1 ELSE d.SHIPPING_INDEX END FROM $_DATA dd WHERE dd.BID = b.$_BID)",
			'Delivered On'					=> "TO_CHAR(d.DELIVERED_ON, 'MM/DD/YYYY HH24:MI')",
			'Delivered By'					=> 'd.DELIVERED_BY',
			'Delivered On/By'				=> "TO_CHAR(d.DELIVERED_ON, 'MM/DD/YYYY HH24:MI')||'<br>'||d.DELIVERED_BY",
//			'Select'							=> 'CASE WHEN d.SHIPPED_ON IS NULL THEN 1 ELSE 0 END',
			'Select'							=> 'NULL',
			'SID'							=> 'd.ID',
			'FID'							=> 'f.FID',
		//	'QC 1'							=> "(CASE WHEN d.QC_ID_1 IS NULL THEN NULL ELSE d2.JOB||'/'||d2.ID END)",
			'QC 1'							=> "(CASE WHEN d.QC_ID_1 IS NULL THEN NULL ELSE d2.JOB END)",
		//	'QC 2'							=> "(CASE WHEN d.QC_ID_2 IS NULL THEN NULL ELSE d3.JOB||'/'||d3.ID END)",
			'QC 2'							=> "(CASE WHEN d.QC_ID_2 IS NULL THEN NULL ELSE d3.JOB END)",
			'Batch ID QC 1'				=> "b2.nBatch_ID",
			'Batch ID QC 2'				=> "b3.nBatch_ID",
			'AID'							=> 'd.ID',
			'Test Desc'						=> "(SELECT
	LISTAGG(TEST.NAME||' - '||TEST.TEST_ID||'('||TEST.STATUS||')'||(CASE WHEN TEST_USER.U_EBATCH IS NULL THEN '' ELSE ' '||TEST_USER.U_EBATCH END)||(CASE WHEN TEST_USER.U_EXT_ON IS NULL THEN '' ELSE ' '||TEST_USER.U_EXT_ON END)||(CASE WHEN TEST_USER.U_EXTDONE IS NULL THEN '' ELSE '/'||TEST_USER.U_EXTDONE END), ';<br>') WITHIN GROUP (ORDER BY TEST.NAME)
--	DBMS_LOB.SUBSTR(TRIM(';' FROM CLOBAGG(TEST.NAME||';')), 2000, 1)
FROM
	ALIQUOT,
	ALIQUOT_USER,
	TEST,
	TEST_USER,
	U_TEST_SELECTION,
	U_TEST_SELECTION_USER,
	U_METHOD_LIST,
	$_TESTLIST
WHERE ALIQUOT.ALIQUOT_ID = TEST.ALIQUOT_ID
	AND ALIQUOT.ALIQUOT_ID = ALIQUOT_USER.ALIQUOT_ID
	AND TEST.TEST_ID = TEST_USER.TEST_ID
	AND TEST_USER.U_TLIST = U_TEST_SELECTION.U_TEST_SELECTION_ID
	AND U_TEST_SELECTION.U_TEST_SELECTION_ID = U_TEST_SELECTION_USER.U_TEST_SELECTION_ID
	AND TEST_USER.U_TMETHOD = U_METHOD_LIST.U_METHOD_LIST_ID (+)
	AND $_TESTLIST.TID = d.TID
	".MatrixCondition("b.bMatrix").GroupCondition("b.bGroup").TestCondition2()."
	AND ALIQUOT.NAME = d.ALIQUOT
)",
			'Completed'						=> "(SELECT
	'Yes'
FROM
	ALIQUOT,
	ALIQUOT_USER,
	TEST,
	TEST_USER,
	U_TEST_SELECTION,
	U_TEST_SELECTION_USER,
	U_METHOD_LIST,
	$_TESTLIST
WHERE ROWNUM = 1
	AND ALIQUOT.ALIQUOT_ID = TEST.ALIQUOT_ID
	AND ALIQUOT.ALIQUOT_ID = ALIQUOT_USER.ALIQUOT_ID
	AND TEST.TEST_ID = TEST_USER.TEST_ID
	AND TEST_USER.U_TLIST = U_TEST_SELECTION.U_TEST_SELECTION_ID
	AND U_TEST_SELECTION.U_TEST_SELECTION_ID = U_TEST_SELECTION_USER.U_TEST_SELECTION_ID
	AND TEST_USER.U_TMETHOD = U_METHOD_LIST.U_METHOD_LIST_ID (+)
	AND $_TESTLIST.TID = d.TID
	".MatrixCondition("b.bMatrix").GroupCondition("b.bGroup").TestCondition2()."
	AND ALIQUOT.NAME = d.ALIQUOT
	AND TEST.STATUS = 'C' AND NOT TEST.NAME LIKE 'Extract _ Hold%'
)"
		);
		$t = '';
		foreach($fields as $f)
			$t .= "\t" . ($t? ',' : '') . "{$DB_FIELD[$f]} AS \"$f\"\n";
		$sql = "SELECT\n$t
			FROM $_DATA d
			LEFT JOIN $_BATCH b ON d.BID = b.$_BID
			LEFT JOIN $_DATAF f ON d.ID = f.SID
			LEFT JOIN $_DATA d2 ON d.QC_ID_1 = d2.ID
				LEFT JOIN $_BATCH b2 ON d2.BID = b2.$_BID
			LEFT JOIN $_DATA d3 ON d.QC_ID_2 = d3.ID
				LEFT JOIN $_BATCH b3 ON d3.BID = b3.$_BID
			LEFT JOIN $_TESTLIST t ON d.TID = t.TID
			WHERE b.nBatch_ID = '$nBatch_ID'
			ORDER BY TO_NUMBER(d.OID)
		";
		die(db_query_json_encode_with_column_names($sql));

	case '3':
		die(db_query_json_encode("
			SELECT bMatrix
			FROM $_BATCH
			WHERE bMatrix IS NOT NULL
			GROUP BY bMatrix
			ORDER BY COUNT(*) DESC
		"));

	case '4':
		die(db_query_json_encode("
			SELECT bGroup
			FROM $_BATCH
			WHERE bGroup IS NOT NULL
			GROUP BY bGroup
			ORDER BY COUNT(*) DESC
		"));

	case '5':
		$group = $_POST['group'];
		$matrix = $_POST['matrix'];
		$batch_id = $_POST['batch_id'];
		die(db_query_json_encode_assoc("
			SELECT * FROM (
				SELECT
					bIAS			AS \"Sample Surrogate\",
					bIAS_SPK_1		AS \"Spike 1\",
					bIAS_SPK_2		AS \"Spike 2\",
					Solvent_Lot		AS \"Solvent Lot\",
					f_surrogate		AS \"Fractionation Surrogate\",
					f_solvent_lot	AS \"Fractionation Tube Lot\"
				FROM $_BATCH
				WHERE bGroup='$group' AND bMatrix='$matrix'
					AND nBatch_ID < '$batch_id'
					AND SURROGATED_ON IS NOT NULL
				ORDER BY nBatch_ID DESC
			) WHERE ROWNUM = 1
		"));

	case '6':
		die(db_query_json_encode("
			SELECT
				'20'||SUBSTR(bYear, 2) AS Year
			FROM $_BATCH
			GROUP BY bYear
			ORDER BY bYear DESC
		"));

	case '7':
		$year = $_POST['year'];
		$search = $_POST['search'];
		$type = $_POST['type'];
		$task = $_POST['task'];

		$where = ' WHERE rownum <= 500';
		$where .= $search? '' : ' AND NVL(manual_injection, 0) = '.($type=='Manual Injection'?1:0);
//		$order = ' ORDER BY '.($search? 'nBatch_ID DESC' : "$_BATCHED_ON DESC");
		$order = ' ORDER BY nBatch_ID DESC';
		$__Batch = '';
		$__Weight = '';
		$__Surrogate = '';
		$__Transfer = '';
		$__Fractionation = '';
		$__Shipping = '';
		$__Delivery = '';
		$task_end = $_BATCHED_ON;
		$task_user = $_BATCHED_BY;

		switch($task){
			case 'Batch':
				$task_end = 'Batched_On';
				$task_user = 'Batched_By';
				$prev_task = '';
				break;
			case 'Weight':
				$task_end = 'Weighed_On';
				$task_user = 'Weighed_By';
				$prev_task = 'Batched';
				break;
			case 'Surrogate / Solvent':
				$task_end = 'Surrogated_On';
				$task_user = 'Surrogated_By';
				$prev_task = 'Weighed';
				break;
			case 'Filter / Vap / Transfer':
				$task_end = 'Transferred_On';
				$task_user = 'Transferred_By';
				$prev_task = 'Surrogated';
				break;
			case 'Fractionation':
				$task_end = 'Fractionated_On';
				$task_user = 'Fractionated_By';
				$prev_task = 'Transferred';
				$where .= " AND bGroup = 'NJ-EPH'";
				break;
			case 'Shipping':
				$task_end = 'Shipped_On';
				$task_user = 'Shipped_By';
				$prev_task = 'Transferred';
				break;
			case 'Delivery':
				$task_end = 'Delivered_On';
				$task_user = 'Delivered_By';
				$prev_task = 'Shipped';
				break;
		}

		if (preg_match("/\d{6}-\d{2}/", $search)){
			$where .= " AND nBatch_ID = '$search'";
		}
		else if (preg_match("/(?P<mm>\d{1,2})\/(?P<dd>\d{1,2})\/(\d{2})?(?P<yy>\d{2})/", $search, $matches)){
			$mm = str_pad($matches['mm'], 2, '0', STR_PAD_LEFT);
			$dd = str_pad($matches['dd'], 2, '0', STR_PAD_LEFT);
			$yy = $matches['yy'];
			$where .= " AND TO_CHAR($_BATCHED_ON, 'MM/DD/YY') = '$mm/$dd/$yy'";
		}
		else if ($search){
			if ($year)
				$where .= " AND bYear='$year'";

			$keywords = preg_split("/[\s,\+]+/", $search);
			foreach($keywords as $key => $word){
				$where .= " AND (
					LOWER(nBatch_ID) LIKE LOWER('%$word%') OR
					LOWER(bMatrix) LIKE LOWER('%$word%') OR
					LOWER(bGroup) LIKE LOWER('%$word%') OR
					LOWER(TRAY) LIKE LOWER('%$word%') OR
					CASE
						WHEN Rush > 0 THEN 'rush'
						ELSE ''
					END = LOWER('$word') OR
					CASE
						WHEN MDL_STUDY > 0 THEN 'mdl study'
						ELSE ''
					END LIKE '%'||LOWER('$word')||'%' OR
					LOWER(NVL(bStatus, 'Preparing')) LIKE LOWER('%$word%') OR
					LOWER(NVL(x.Batched_By, 'no_batched_by')) = LOWER('$word') OR
					LOWER(NVL(x.Weighed_By, 'no_weighed_by')) = LOWER('$word') OR
					LOWER(NVL(x.Surrogated_By, 'no_surrogated_by')) = LOWER('$word') OR
					LOWER(NVL(x.Transferred_By, 'no_transfered_by')) = LOWER('$word') OR
					LOWER(NVL(x.Fractionated_By, 'no_fractionated_by')) = LOWER('$word') OR
					LOWER(NVL(x.Shipped_By, 'no_shipped_by')) = LOWER('$word') OR
					LOWER(NVL(x.Delivered_By, 'no_delivered_by')) = LOWER('$word') OR
					$_BID IN (
						SELECT BID FROM $_DATA
						LEFT JOIN $_TESTLIST ON $_DATA.tid = $_TESTLIST.tid
						WHERE LOWER(NVL(Sample_Type, 'sample')) LIKE LOWER('%$word%')
							OR LOWER(NVL(ALIQUOT, 'no_aliquot')) LIKE LOWER('%$word%')
							OR LOWER(NVL(JOB, 'no_job')) LIKE LOWER('%$word%')
							OR LOWER(NVL(Comments, 'no_sample_comments')) LIKE LOWER('%$word%')
							OR LOWER($_TESTLIST.Name) LIKE LOWER('%$word%')
							OR qc_id_1 IN (select id from $_DATA where dYear=bYear and job='$word')
							OR qc_id_2 IN (select id from $_DATA where dYear=bYear and job='$word')
					)
				)";
			}
		}
		else{
			if ($year)
				$where .= " AND bYear='$year'";

			$__Batch = '--';
			$__Weight = '--';
			$__Surrogate = '--';
			$__Transfer = '--';
			$__Fractionation = '--';
			$__Shipping = '--';
			$__Delivery = '--';
			switch($task){
			case 'Batch':
				$__Batch = '';
				break;
			case 'Weight':
				$__Batch = '';
				$__Weight = '';
				break;
			case 'Surrogate / Solvent':
				$__Weight = '';
				$__Surrogate = '';
				break;
			case 'Filter / Vap / Transfer':
				$__Surrogate = '';
				$__Transfer = '';
				break;
			case 'Fractionation':
				$__Transfer = '';
				$__Fractionation = '';
				$where .= " AND bGroup = 'NJ-EPH'";
				break;
			case 'Shipping':
				$__Transfer = '';
				$__Fractionation = '';
				$__Shipping = '';
				$__Delivery = '';
				break;
			case 'Delivery':
				$__Shipping = '';
				$__Delivery = '';
				break;
			}
			$where .= " AND (b.EXTRACTION_DATE IS NULL OR SYSDATE - b.EXTRACTION_DATE < 15)";
		//	if ($prev_task)
		//		$where .= " AND Cnt_$prev_task  = Cnt_Sample";

		// the next step can be started as soon as there are samples completed in its previous step
			if ($prev_task)
				$where .= " AND Cnt_$prev_task > 0";
		//	$where .= " AND NVL(bStatus, 'Preparing') != 'Done'";
		//	$order = "ORDER BY $task_end DESC, Rush DESC, nBatch_ID";
		}

		$sql = "
			SELECT
				(CASE WHEN QC_FAIL = 1 THEN 'Y' ELSE '' END) AS \"QC Fail\",
				$_BID AS \"ID\",
				nBatch_ID AS \"Batch ID\",
				bMatrix AS \"Matrix\",
				bGroup AS \"Group\",
				(SELECT t.NAME FROM $_TESTLIST t
					JOIN $_DATA d ON d.TID = t.TID
					WHERE d.SAMPLE_TYPE = 'BLK'
						AND d.BID = b.$_BID
						AND ROWNUM = 1
				) AS \"BLK\",
				(CASE WHEN NO_MS_MSD_DUP = 1 THEN 'Y' ELSE '' END) AS \"No QC\",
				CASE
					WHEN Rush > 0 THEN 'Rush'
					ELSE ''
				END AS \"Rush\"
			".($search && $prev_task? "
				,CASE
					WHEN Cnt_$prev_task = Cnt_Sample THEN 'Yes'
					ELSE 'No'
				END AS \"Prev. Step Completed\"
			":'')."
$__Batch		,CASE WHEN Cnt_Batched = 0 THEN '' WHEN Cnt_Batched = Cnt_Sample THEN 'Yes' ELSE Cnt_Batched||'' END	AS \"Batched\"
--				,REPLACE(TO_CHAR(x.BATCHED_ON, 'MM/DD/YYYY HH24:MI'), '/'||TO_CHAR(SYSDATE, 'YYYY'), '') AS \"Batched On\"
--				,x.BATCHED_BY AS \"Batched By\"
$__Weight		,CASE WHEN Cnt_Weighed = 0 THEN '' WHEN Cnt_Weighed = Cnt_Sample THEN 'Yes' ELSE Cnt_Weighed||'' END	AS \"Weighed\"
--				,REPLACE(TO_CHAR(x.Weighed_On, 'MM/DD/YYYY HH24:MI'), '/'||TO_CHAR(SYSDATE, 'YYYY'), '') AS \"Weighed On\"
--				,x.Weighed_By AS \"Weighed By\"
$__Surrogate	,CASE WHEN Cnt_Surrogated = 0 THEN '' WHEN Cnt_Surrogated = Cnt_Sample THEN 'Yes' ELSE Cnt_Surrogated||'' END	AS \"Surrogated\"
--				,REPLACE(TO_CHAR(x.Surrogated_On, 'MM/DD/YYYY HH24:MI'), '/'||TO_CHAR(SYSDATE, 'YYYY'), '') AS \"Surrogated On\"
--				,x.Surrogated_By AS \"Surrogated By\"
$__Transfer		,CASE WHEN Cnt_Transferred = 0 THEN '' WHEN Cnt_Transferred = Cnt_Sample THEN 'Yes' ELSE Cnt_Transferred||'' END	AS \"Transferred\"
--				,REPLACE(TO_CHAR(x.Transferred_On, 'MM/DD/YYYY HH24:MI'), '/'||TO_CHAR(SYSDATE, 'YYYY'), '') AS \"Transferred On\"
--				,x.Transferred_By AS \"Transferred By\"
$__Fractionation	,CASE WHEN Cnt_Fractionated = 0 THEN '' WHEN Cnt_Fractionated = Cnt_Sample THEN 'Yes' ELSE Cnt_Fractionated||'' END	AS \"Fractionated\"
--				,REPLACE(TO_CHAR(x.Fractionated_On, 'MM/DD/YYYY HH24:MI'), '/'||TO_CHAR(SYSDATE, 'YYYY'), '') AS \"Fractionated On\"
--				,x.Fractionated_By AS \"Fractionated By\"
$__Shipping		,CASE WHEN Cnt_Shipped = 0 THEN '' WHEN Cnt_Shipped = Cnt_Sample THEN 'Yes' ELSE Cnt_Shipped||'' END	AS \"Shipped\"
--				,REPLACE(TO_CHAR(x.Shipped_On, 'MM/DD/YYYY HH24:MI'), '/'||TO_CHAR(SYSDATE, 'YYYY'), '') AS \"Shipped On\"
--				,x.Shipped_By AS \"Shipped By\"
$__Delivery		,CASE WHEN Cnt_Delivered = 0 THEN '' WHEN Cnt_Delivered = Cnt_Sample THEN 'Yes' ELSE Cnt_Delivered||'' END	AS \"Delivered\"
--				,REPLACE(TO_CHAR(x.Delivered_On, 'MM/DD/YYYY HH24:MI'), '/'||TO_CHAR(SYSDATE, 'YYYY'), '') AS \"Delivered On\"
--				,x.Delivered_By AS \"Delivered By\"
				,Cnt_Sample	AS \"Samples\"
				,
				CASE WHEN EXTRACTION_DATE IS NOT NULL AND SYSDATE - EXTRACTION_DATE >= 23/24 THEN 0
				ELSE 20-GREATEST(
					(SELECT COUNT(*) AS CNT FROM $_DATA d
					WHERE d.BID = b.$_BID
					-- Easier and faster this way, however not compatible to old records
						AND d.SAMPLE_TYPE IS NULL
					--	AND d.JOB Not Like '%BLANK%'
					--	AND d.JOB Not Like '%MS%'
					--	AND d.JOB Not Like '%BLK%'
					--	AND REGEXP_LIKE(d.VAPNO, '^[0-9]*$')
					--	AND d.SAMPLE NOT Like '%D%'
					)
					,(SELECT COUNT(*) AS CNT FROM $_DATA d
					WHERE d.QC_ID_1 IS NOT NULL
						AND d.QC_ID_1 IN (
							SELECT d.ID FROM $_DATA d
							WHERE d.BID = b.$_BID
								AND d.SAMPLE_TYPE = 'MS'
						)
					)
					,(SELECT COUNT(*) AS CNT FROM $_DATA d
					WHERE d.QC_ID_2 IS NOT NULL
						AND d.QC_ID_2 IN (
							SELECT d.ID FROM $_DATA d
							WHERE d.BID = b.$_BID
								AND d.SAMPLE_TYPE = 'MS2'
						)
					)
				)
				END AS \"Empty\"
			FROM $_BATCH b
			LEFT JOIN (
				SELECT
					COUNT(*)					AS Cnt_Sample,
					SUM(CASE WHEN d.ANALYZE_DATE IS NULL THEN 0 ELSE 1 END)	AS Cnt_Batched,
					SUM(CASE WHEN d.WEIGHED_ON IS NULL THEN 0 ELSE 1 END)		AS Cnt_Weighed,
					SUM(CASE WHEN d.SURROGATED_ON IS NULL THEN 0 ELSE 1 END)	AS Cnt_Surrogated,
					SUM(CASE WHEN d.TRANSFERRED_ON IS NULL THEN 0 ELSE 1 END)	AS Cnt_Transferred,
					SUM(CASE WHEN f.FRACTIONATED_ON IS NULL THEN 0 ELSE 1 END)	AS Cnt_Fractionated,
					SUM(CASE WHEN d.SHIPPED_ON IS NULL THEN 0 ELSE 1 END)	AS Cnt_Shipped,
					SUM(CASE WHEN d.DELIVERED_ON IS NULL THEN 0 ELSE 1 END)	AS Cnt_Delivered,
					MAX(d.ANALYZE_DATE)		AS Batched_On,
					MAX(d.BATCHED_BY)		AS Batched_By,
					MAX(d.WEIGHED_ON)		AS Weighed_On,
					MAX(d.WEIGHED_BY)		AS Weighed_By,
					MAX(d.SURROGATED_ON)	AS Surrogated_On,
					MAX(d.SURROGATED_BY)	AS Surrogated_By,
					MAX(d.TRANSFERRED_ON)	AS Transferred_On,
					MAX(d.TRANSFERRED_BY)	AS Transferred_By,
					MAX(f.FRACTIONATED_ON)	AS Fractionated_On,
					MAX(f.FRACTIONATED_BY)	AS Fractionated_By,
					MAX(d.SHIPPED_ON)		AS Shipped_On,
					MAX(d.SHIPPED_BY)		AS Shipped_By,
					MAX(d.SHIPPING_INDEX)	AS Shipping_Index,
					MAX(d.DELIVERED_ON)		AS Delivered_On,
					MAX(d.DELIVERED_BY)		AS Delivered_By,
					BID
				FROM $_DATA d, $_DATAF f
				WHERE f.SID (+) = d.ID
				GROUP BY BID
			) x ON b.$_BID = x.BID
			$where
			$order
		";
		die(db_query_json_encode_with_column_names($sql));

	case '8':		// Dashboard
		$where = ' WHERE rownum <= 500';
		$where .= ' AND NVL(manual_injection, 0) = 0';
		$order = ' ORDER BY "Day Left", "Days" DESC, "HT Due"';
		$__Batch = '';
		$__Weight = '';
		$__Surrogate = '';
		$__Transfer = '';
		$__Fractionation = '';
		$__Shipping = '--';

	//	if ($year)
	//		$where .= " AND bYear='$year'";
		$where .= " AND SYSDATE - x.Batch_Date < 30";
	//	$where .= " AND (b.EXTRACTION_DATE IS NULL OR SYSDATE - b.EXTRACTION_DATE < 15)";
		$where .= " AND (
			CASE
				WHEN bGroup = 'NJ-EPH' THEN Cnt_Fractionated
				ELSE Cnt_Transferred
			END
		) <> Cnt_Sample";

		$sql = "
			SELECT
			--	$_BID AS \"ID\",
			--	TO_CHAR(x.\"HT Due\", 'MM/DD') AS \"HT Due\",
			--	TO_CHAR(x.\"Result Due\", 'MM/DD') AS \"Result Due\",
			--	TO_CHAR(x.\"Ext Due\", 'MM/DD') AS \"Ext Due\",
				TO_CHAR(x.\"Due Date\", 'MM/DD') AS \"Batch Due\",
				x.\"Day Left\",
				CEIL(SYSDATE - TRUNC(x.Batch_Date)) - 1 AS \"Days\",
				nBatch_ID AS \"Batch ID\",
				bMatrix AS \"Matrix\",
				bGroup AS \"Group\",
				(SELECT t.NAME FROM $_TESTLIST t
					JOIN $_DATA d ON d.TID = t.TID
					WHERE d.SAMPLE_TYPE = 'BLK'
						AND d.BID = b.$_BID
						AND ROWNUM = 1
				) AS \"BLK\"
$__Batch		,CASE WHEN Cnt_Batched = 0 THEN '' WHEN Cnt_Batched = Cnt_Sample THEN 'Yes' ELSE Cnt_Batched||'' END	AS \"Batched\"
$__Weight		,CASE WHEN Cnt_Weighed = 0 THEN '' WHEN Cnt_Weighed = Cnt_Sample THEN 'Yes' ELSE Cnt_Weighed||'' END	AS \"Weighed\"
$__Surrogate	,CASE WHEN Cnt_Surrogated = 0 THEN '' WHEN Cnt_Surrogated = Cnt_Sample THEN 'Yes' ELSE Cnt_Surrogated||'' END	AS \"Surrogated\"
$__Transfer		,CASE WHEN Cnt_Transferred = 0 THEN '' WHEN Cnt_Transferred = Cnt_Sample THEN 'Yes' ELSE Cnt_Transferred||'' END	AS \"Transferred\"
$__Fractionation	,CASE WHEN bGroup = 'NJ-EPH' THEN
					(CASE WHEN Cnt_Fractionated = 0 THEN '' WHEN Cnt_Fractionated = Cnt_Sample THEN 'Yes' ELSE Cnt_Fractionated||'' END)
				ELSE
					'-'
				END	AS \"Fractionated\"
$__Shipping		,CASE WHEN Cnt_Shipped = 0 THEN '' WHEN Cnt_Shipped = Cnt_Sample THEN 'Yes' ELSE Cnt_Shipped||'' END	AS \"Shipped\"
				,Cnt_Sample	AS \"Samples\"
			FROM $_BATCH b
			LEFT JOIN (
				SELECT
					MIN(g.\"HT Due\")			AS \"HT Due\",
					MIN(g.\"Result Due\")		AS \"Result Due\",
					MIN(g.\"Ext Due\")			AS \"Ext Due\",
					MIN(g.\"Due Date\")		AS \"Due Date\",
					MIN(g.\"Day Left\")		AS \"Day Left\",
					COUNT(*)					AS Cnt_Sample,
					SUM(CASE WHEN d.ANALYZE_DATE IS NULL THEN 0 ELSE 1 END)	AS Cnt_Batched,
					SUM(CASE WHEN d.WEIGHED_ON IS NULL THEN 0 ELSE 1 END)		AS Cnt_Weighed,
					SUM(CASE WHEN d.SURROGATED_ON IS NULL THEN 0 ELSE 1 END)	AS Cnt_Surrogated,
					SUM(CASE WHEN d.TRANSFERRED_ON IS NULL THEN 0 ELSE 1 END)	AS Cnt_Transferred,
					SUM(CASE WHEN f.FRACTIONATED_ON IS NULL THEN 0 ELSE 1 END)	AS Cnt_Fractionated,
					SUM(CASE WHEN d.SHIPPED_ON IS NULL THEN 0 ELSE 1 END)	AS Cnt_Shipped,
					MIN(d.ANALYZE_DATE)		AS Batch_Date,
					MAX(d.ANALYZE_DATE)		AS Batched_On,
					MAX(d.BATCHED_BY)		AS Batched_By,
					MAX(d.WEIGHED_ON)		AS Weighed_On,
					MAX(d.WEIGHED_BY)		AS Weighed_By,
					MAX(d.SURROGATED_ON)	AS Surrogated_On,
					MAX(d.SURROGATED_BY)	AS Surrogated_By,
					MAX(d.TRANSFERRED_ON)	AS Transferred_On,
					MAX(d.TRANSFERRED_BY)	AS Transferred_By,
					MAX(f.FRACTIONATED_ON)	AS Fractionated_On,
					MAX(f.FRACTIONATED_BY)	AS Fractionated_By,
					MAX(d.SHIPPED_ON)		AS Shipped_On,
					MAX(d.SHIPPED_BY)		AS Shipped_By,
					MAX(d.SHIPPING_INDEX)	AS Shipping_Index,
					BID
				FROM $_DATA d, $_DATAF f, $_BATCH b, (
					".GetSamples('', '', '', true)."
				) g
				WHERE f.SID (+) = d.ID
					AND b.$_BID = d.BID
					AND g.\"Batch ID\" = b.nBatch_ID
					AND g.\"Sample ID\" (+) = REPLACE(REPLACE(d.ALIQUOT, 'MSD', ''), 'MS', '')
				GROUP BY BID
			) x ON b.$_BID = x.BID
			$where
			$order
		";
		die(db_query_json_encode_with_column_names($sql));

	case '9':
		$matrix = isset($_POST['matrix'])? $_POST['matrix'] : '';
		$group = isset($_POST['group'])? $_POST['group'] : '';
		$where = "WHERE DISABLED = 0";
		$where .= $matrix? " AND (TEST_MATRIX = '$matrix' OR TEST_MATRIX IS NULL)" : '';
		$where .= $group? " AND (TEST_GROUP LIKE '$group' OR TEST_GROUP IS NULL)" : '';
		die(db_query_json_encode_assoc("
			SELECT
				TEST_MATRIX,
				REPLACE(REPLACE(TEST_GROUP,
					'PCB/Pesticides', 'PCB/Pest'),
					'Herbicide', 'Herb') AS TEST_GROUP,
				TID,
				Name
			FROM $_TESTLIST
			$where
			ORDER BY TEST_GROUP, TID, Name
		"));

	case '11':		// Generate a new nBatch_ID
		$client_date = isset($_POST['client_date'])? $_POST['client_date'] : null;
		$sql_date = $client_date? "'$client_date'" : "TO_CHAR(SYSDATE, 'YYMMDD')";
		die(db_query_json_encode_first_result("
			SELECT
				$sql_date || '-' || LTRIM(TO_CHAR(NVL(TO_NUMBER(SUBSTR(MAX(nBatch_ID), 8, 2)), 0) + 1, '00'))
			FROM $_BATCH
			WHERE SUBSTR(nBatch_ID, 1, 6) = $sql_date
		"));

	case '13':
		$fields = '';
		$values = '';
		foreach($_POST as $key => $value){
			if ($key == 'q') continue;
			$fields .= ($fields?",\n":'').$key;
			if (stripos($key, 'date') !== FALSE || EndsWith(strtolower($key), '_on') || EndsWith(strtolower($key), '_on_2'))
				$values .= ($values?",\n":'')."TO_DATE(:$key, 'MM/DD/YYYY HH24:MI')";
			else
				$values .= ($values?",\n":'').":$key";
		}
		$sql = "
			INSERT INTO $_BATCH ($fields) VALUES ($values)
			RETURNING $_BID INTO :$_BID
		";
		$stmt = oci_parse($conn, $sql);
		foreach($_POST as $key => $value){
			if (strstr($sql, ":$key"))
				oci_bind_by_name($stmt, ":$key", $_POST[$key]);
		}
		oci_bind_by_name($stmt, ":$_BID", $BID, 10);
		if (oci_execute($stmt))
			die(json_encode($BID));

	case '14':
		$sets = '';
		foreach($_POST as $key => $value){
			if ($key == 'q') continue;
			if ($key == 'nBatch_ID') continue;
			if (stripos($key, 'date') !== FALSE || EndsWith(strtolower($key), '_on') || EndsWith(strtolower($key), '_on_2'))
				$sets .= ($sets?",\n":'')."$key = ".($value=='sysdate'?"sysdate":"TO_DATE(:$key, 'MM/DD/YYYY HH24:MI')");
			else
				$sets .= ($sets?",\n":'')."$key = :$key";
		}
		$sql = "
			UPDATE $_BATCH SET $sets
			WHERE nBatch_ID = :nBatch_ID
		";
		$stmt = oci_parse($conn, $sql);
		foreach($_POST as $key => $value){
			if (strstr($sql, ":$key"))
				oci_bind_by_name($stmt, ":$key", $_POST[$key]);
		}
		if (oci_execute($stmt))
			die(json_encode('success'));
		break;

	case '15':
		$task = $_POST['task'];
		$pk = $task == 'Fractionation'? 'FID' : 'ID';
		$table = $task == 'Fractionation'? $_DATAF : $_DATA;
		$bid = $task == 'Fractionation'? 'SID' : 'BID';
		$by = $task == 'Fractionation'? 'FRACTIONATED_BY' : 'BATCHED_BY';
		$on = $task == 'Fractionation'? 'FRACTIONATED_ON' : 'ANALYZE_DATE';

		list($id, $batched_by, $batched_on) = db_query_first_row("
			SELECT $pk, $by, TO_CHAR($on, 'MM/DD/YYYY HH24:MI')
			FROM $table
			WHERE $bid = ".$_POST[$bid].($task == 'Fractionation'?'':"
				".($_POST['SAMPLE_TYPE']?"
					AND SAMPLE_TYPE  = ".sqlstr($_POST['SAMPLE_TYPE'])."
					AND JOB ".($_POST['JOB']?" = ".sqlstr($_POST['JOB']):"IS NULL")."
				":"
					AND SAMPLE_TYPE IS NULL
					AND VAPNO ".($_POST['VAPNO']?" = ".sqlstr($_POST['VAPNO']):"IS NULL")."
				")."
		"));
		if ($id){
			die(json_encode(array(
				'success' => false,
				'error' => "Records already created by $batched_by on $batched_on.  Please reload this batch.",
			//	'id' => $id
			)));
		}

		$fields = '';
		$values = '';
		foreach($_POST as $key => $value){
			if ($key == 'q') continue;
			if ($key == 'task') continue;
			$fields .= ($fields?",\n":'').$key;

			if (in_array($key, array('analyze_date','BATCHED_ON','WEIGHED_ON','SURROGATED_ON','TRANSFERRED_ON','FRACTIONATED_ON','SHIPPED_ON','SHIPPED_ON')))
				$values .= ($values?",\n":'').
					(strtolower($value) == 'null'?
						'NULL':
					(!$value || strtolower($value) == 'now'?
						'CURRENT_TIMESTAMP':
					//otherwise
						"TO_DATE(:$key, 'MM/DD/YYYY HH24:MI')"
					));
			else if (stripos($key, 'date') !== FALSE)
				$values .= ($values?",\n":'')."TO_DATE(:$key, 'MM/DD/YYYY HH24:MI')";
			else
				$values .= ($values?",\n":'').":$key";
		}
		$sql = "
			INSERT INTO $table ($fields) VALUES ($values)
			RETURNING $pk INTO :ID
		";
		$stmt = oci_parse($conn, $sql);
		foreach($_POST as $key => $value){
			if (strstr($sql, ":$key"))
				oci_bind_by_name($stmt, ":$key", $_POST[$key]);
		}
		oci_bind_by_name($stmt, ":ID", $ID, 10);
		if (oci_execute($stmt)){
			die(json_encode(array(
				'success' => true,
				'id' => $ID
			)));
		}
		else{
			die(json_encode(array(
				'success' => false,
				'error' => print_r(oci_error($stmt))
			)));
		}

	case '16':
		$task = $_POST['task'];
		$pk = $task == 'Fractionation'? 'FID' : 'ID';
		$ID = isset($_POST[$pk])? $_POST[$pk] : null;
		$table = $task == 'Fractionation'? $_DATAF : $_DATA;
		$sets = '';
		foreach($_POST as $key => $value){
			if ($key == 'q') continue;
			if ($key == 'task') continue;
			if ($key == 'ID' && $task == 'Fractionation') continue;
			if ($key == 'analyze_date' && $value) continue;

			if (in_array($key, array('analyze_date','BATCHED_ON','WEIGHED_ON','SURROGATED_ON','TRANSFERRED_ON','FRACTIONATED_ON','SHIPPED_ON')))
				$sets .= ($sets?', ':'')."$key = ".
					(strtolower($value) == 'null'?
						'NULL':
					(!$value || strtolower($value) == 'now'?
						'CURRENT_TIMESTAMP':
					//otherwise
						"TO_DATE(:$key, 'MM/DD/YYYY HH24:MI')"
					));
			else if (stripos($key, 'date') !== FALSE)
				$sets .= ($sets?', ':'')."$key = TO_DATE(:$key, 'MM/DD/YYYY HH24:MI')";
			else
				$sets .= ($sets?', ':'')."$key = :$key";
		}
		$sql = "
			UPDATE $table SET $sets
			WHERE $pk = :ID
		";
		$stmt = oci_parse($conn, $sql);
		foreach($_POST as $key => $value){
			if ($_POST[$key] == 'NULL') $_POST[$key] = NULL;
			if (strstr($sql, ":$key"))
				oci_bind_by_name($stmt, ":$key", $_POST[$key]);
		}
		oci_bind_by_name($stmt, ":ID", $ID, 10);
		if (oci_execute($stmt))
			die(json_encode(array(
				'success' => true,
			)));
		else
			die(json_encode(array(
				'success' => false,
				'error' => print_r(oci_error($stmt))
			)));

	case '17':
		$name = $_POST['name'];
		die(db_query_json_encode_first_result("
			SELECT TID
			FROM $_TESTLIST
			WHERE DISABLED = 0
				AND Name LIKE '$name%'
		"));

	case '18':
		$n = isset($_POST['n'])? $_POST['n'] : 1;
		$matrix = $_POST['matrix'];
		$group = $_POST['group'];
		$test = isset($_POST['test'])?$_POST['test']:'';
		$min_available = $_POST['min_available'];
		$batch_id = $_POST['batch_id'];
		$client_date = isset($_POST['client_date'])? $_POST['client_date'] : null;
		$sql_date = $client_date? "TO_DATE('$client_date', 'YYMMDD')" : 'SYSDATE';
		$sql = "
SELECT
	a.job AS \"JOB\",
	c.nBatch_ID AS \"OriginalBatch\",
	TO_CHAR(c.batched_on, 'MM/DD/YYYY HH24:MI') AS \"InitialDate\",
	20 - times_used AS \"Available\",
	a.id AS \"Original_ID\"
FROM $_DATA a
JOIN $_BATCH b ON b.$_BID = a.bid
JOIN (
	SELECT
		qc_id_$n,
		MIN($_BATCH.$_BATCHED_ON) AS batched_on,
		MIN($_BATCH.nBatch_ID) AS nBatch_ID,
		COUNT(*) AS times_used
	FROM $_DATA
	JOIN $_BATCH ON $_BATCH.$_BID = bid
	JOIN $_TESTLIST ON $_DATA.tid = $_TESTLIST.tid
	WHERE qc_id_$n IS NOT NULL
		AND $_BATCH.nBatch_ID != '$batch_id'
		AND $_BATCH.bMatrix = '$matrix'
		AND $_BATCH.bGroup = '$group'
		AND $_TESTLIST.Name LIKE '%".str_replace(' ', '%', $test)."%'
		AND (
			'$test' LIKE '%TCLP%' AND $_TESTLIST.Name LIKE '%TCLP%' OR
			'$test' LIKE '%SPLP%' AND $_TESTLIST.Name LIKE '%SPLP%' OR
			$_TESTLIST.Name NOT LIKE '%TCLP%' AND $_TESTLIST.Name NOT LIKE '%SPLP%'
		)
	GROUP BY qc_id_$n
) c ON a.id = c.qc_id_$n
WHERE 1=1
--	AND (
--		manual_injection = 0 AND b.TRANSFERRED_ON IS NOT NULL AND $sql_date - b.TRANSFERRED_ON > 3
--		OR manual_injection > 0 AND b.$_BATCHED_ON IS NOT NULL AND $sql_date - b.$_BATCHED_ON > 3
--	)
	AND $sql_date - TO_DATE(SUBSTR(c.nBatch_ID, 1, 6), 'YYMMDD') < 15
	AND 20 - c.times_used >= $min_available
	AND b.bMatrix = '$matrix'
	AND b.bGroup = '$group'
	AND b.QC_Fail".($n=='1'?'':'_2')." = 0
	AND b.QC_Approval".($n=='1'?'':'_2')." = 1
	AND (a.COMMENTS IS NULL OR NOT a.COMMENTS LIKE '%Extract _ Hold%')
ORDER BY c.nBatch_ID
		";
		die(db_query_json_encode_assoc($sql));

	case '18x':
		$n = isset($_POST['n'])? $_POST['n'] : 1;
		$matrix = $_POST['matrix'];
		$group = $_POST['group'];
		$test = isset($_POST['test'])? $_POST['test'] : '';
		$year = $_POST['year'];
		$qc = $_POST['qc'];	// e.g.: 34243-001MS
// Get times QC used in other batches
		$sql = "
SELECT COUNT(*) AS times_used_in_other_batches
FROM $_DATA d1
JOIN $_BATCH b1 ON b1.$_BID = d1.bid
JOIN $_TESTLIST t1 ON d1.tid = t1.tid
JOIN $_DATA d2 ON d1.qc_id_$n = d2.id
JOIN $_BATCH b2 ON b2.$_BID = d2.bid
JOIN $_TESTLIST t2 ON d2.tid = t2.tid
WHERE d2.dYear = '$year' AND d2.job = '$qc'
	AND b1.bMatrix = '$matrix'
	AND b1.bGroup = '$group'
	AND t1.Name LIKE '%".str_replace(' ', '%', $test)."%'
	AND (
		'$test' LIKE '%TCLP%' AND t1.Name LIKE '%TCLP%' OR
		'$test' LIKE '%SPLP%' AND t1.Name LIKE '%SPLP%' OR
		t1.Name NOT LIKE '%TCLP%' AND t1.Name NOT LIKE '%SPLP%'
	)
	AND b2.bMatrix = '$matrix'
	AND b2.bGroup = '$group'
	AND t2.Name LIKE '%".str_replace(' ', '%', $test)."%'
	AND (
		'$test' LIKE '%TCLP%' AND t2.Name LIKE '%TCLP%' OR
		'$test' LIKE '%SPLP%' AND t2.Name LIKE '%SPLP%' OR
		t2.Name NOT LIKE '%TCLP%' AND t2.Name NOT LIKE '%SPLP%'
	)
AND d1.bid NOT IN (
	SELECT bid
	FROM $_DATA
	JOIN $_BATCH ON $_BATCH.$_BID = bid
	JOIN $_TESTLIST ON $_DATA.tid = $_TESTLIST.tid
	WHERE $_BATCH.bMatrix = '$matrix'
		AND $_BATCH.bGroup = '$group'
		AND $_TESTLIST.Name LIKE '%".str_replace(' ', '%', $test)."%'
		AND (
			'$test' LIKE '%TCLP%' AND $_TESTLIST.Name LIKE '%TCLP%' OR
			'$test' LIKE '%SPLP%' AND $_TESTLIST.Name LIKE '%SPLP%' OR
			$_TESTLIST.Name NOT LIKE '%TCLP%' AND $_TESTLIST.Name NOT LIKE '%SPLP%'
		)
		AND dYear = '$year' AND job = '$qc'
)
		";
		die(db_query_json_encode_assoc($sql));

	case '19':		// Delete Batch
		$nBatch_ID = $_POST['nBatch_ID'];
		db_query("
			DELETE FROM $_DATA d
			WHERE d.bid IN (
				SELECT $_BID FROM $_BATCH
				WHERE nBatch_ID = '$nBatch_ID'
			)
		");
		db_query("
			DELETE FROM $_BATCH
			WHERE nBatch_ID = '$nBatch_ID'
		");
		die(json_encode('success'));

	case '20':
		$ID = $_POST['ID'];
		$sql = "
			DELETE FROM $_DATA
			WHERE ID = :ID
		";
		$stmt = oci_parse($conn, $sql);
		oci_bind_by_name($stmt, ":ID", $ID, 10);
		die(json_encode(oci_execute($stmt)? 'success' : 'failure'));
/*
	case '22':			// Re-assign No, NO LONGER IN USE
		$BID = $_POST['BID'];
		$sql = "
			update $_DATA outer
			set vapno = (
				select rnum from (
					select id, row_number() over (order by to_number(vapno)) rnum
					from $_DATA
					where bid = :BID and vapno > 0
				) inner
				where inner.id = outer.id
			)
			where bid = :BID and vapno > 0
		";
		$stmt = oci_parse($conn, $sql);
		oci_bind_by_name($stmt, ":BID", $BID);
		die(json_encode(oci_execute($stmt)? 'success' : 'failure'));
*/
	case '23':		// Check whether the sample exists in another batch
		$batch_id = $_POST['batch_id'];
		$group = $_POST['group'];
		$matrix = $_POST['matrix'];
		$test = isset($_POST['test'])?$_POST['test']:'';
		$year = $_POST['year'];
		$sid = $_POST['sid'];
		$sql = "
SELECT * FROM (
	SELECT b.nBatch_ID, b.bMatrix, t.Name
	FROM $_DATA d
		JOIN $_BATCH b ON d.bid = b.$_BID
		JOIN $_TESTLIST t ON t.tid = d.tid
	WHERE d.dYear = '$year'
		AND (
			CASE
				WHEN d.SAMPLE_TYPE = 'DUP' AND LENGTH(d.JOB) = 5 THEN d.JOB||'-'||LPAD(d.SAMPLE, 3, '0')||d.SAMPLE_TYPE
				WHEN d.SAMPLE_TYPE IS NULL THEN d.JOB||'-'||LPAD(d.SAMPLE, 3, '0')
				ELSE d.JOB
			END
		) = '$sid'
		AND b.nBatch_ID < '$batch_id'
		AND b.bGroup = '$group' AND b.bMatrix IN ('".(gettype($matrix)=='array'? implode("','", $matrix) : $matrix)."')
		AND (
			'$test' LIKE '%TCLP%' AND t.name LIKE '%TCLP%' OR
			'$test' LIKE '%SPLP%' AND t.name LIKE '%SPLP%' OR
			(
				'$test' NOT LIKE '%TCLP%' AND
				'$test' NOT LIKE '%SPLP%' AND
				t.name NOT LIKE '%TCLP%' AND
				t.name NOT LIKE '%SPLP%'
			)
		)
		AND (
			t.name LIKE '%'||REPLACE('$test',' ','%')||'%' OR
			'$test' LIKE '%'||REPLACE(t.name,' ','%')||'%'
		)
	ORDER BY b.nBatch_ID DESC
)
WHERE rownum = 1
		";
		die(db_query_json_encode_first_row($sql));

	case '24':
		$n = isset($_POST['n'])? $_POST['n'] : 1;
		$batch_id = $_POST['batch_id'];
		$year = $_POST['year'];
		$qc = $_POST['qc'];
		$sql = "
			select count(*) from $_DATA d
			join $_BATCH b on d.bid = b.$_BID
			where qc_id_$n = (
				SELECT id FROM $_DATA
				WHERE dYear = '$year'
					AND JOB = '$qc'
					AND rownum = 1
			) and b.nBatch_ID != '$batch_id'
		";
		die(db_query_json_encode_first_result($sql));

	case '26':			// Get Samples
		$matrix = isset($_POST['matrix'])? $_POST['matrix'] : '';
		$group = isset($_POST['group'])? $_POST['group'] : '';
		$test = isset($_POST['test'])? $_POST['test'] : '';
		$search = isset($_POST['search'])? $_POST['search'] : '';
		$sql = "
SELECT
	'<input type=\"checkbox\">' AS \"Sel\",
	\"Sample ID\",
	".($search? "\"Matrix\",\"Group\",\"Test\"," : '')."
	\"Ext. Type\",
	\"Test Name\",
	\"Client Field ID\",
	\"Client\",
	\"Leach Batch\",
	\"Leach On\",
	\"Leach Done\",
	\"Rush TAT\",
--	TO_CHAR(x.\"HT Due\", 'MM/DD') AS \"HT Due\",
--	TO_CHAR(x.\"Result Due\", 'MM/DD') AS \"Result Due\",
	TO_CHAR(x.\"Ext Due\", 'MM/DD') AS \"Ext Due\",
	\"Day Left\"
FROM (
	".GetSamples($matrix, $group, $test, $search, true)."
) x
WHERE ".($search?
	"ROWNUM <= 100 AND (
		LOWER(\"Sample ID\") LIKE LOWER('%$search%') OR
		LOWER(\"Matrix\") LIKE LOWER('%$search%') OR
		LOWER(\"Group\") LIKE LOWER('%$search%') OR
		LOWER(\"Test\") LIKE LOWER('%$search%')
	)" :
	'1=1'
)."
ORDER BY \"Day Left\", \"HT Due\", \"Sample ID\"
		";
		die(db_query_json_encode_with_column_names($sql));

	case '27':		// Get Leach Batch and Leach On date
		$aliquot = $_POST['aliquot'];
		$matrix = $_POST['matrix'];
		$group = $_POST['group'];
		$test = isset($_POST['test'])?$_POST['test']:'';
		$sql = "
SELECT
	TEST_USER.U_LBATCH AS \"Leach Batch\",
	TO_CHAR(TEST_USER.U_LEACH_ON, 'YYMMDD') AS \"Leach On\",
	TEST.NAME AS \"Test Name\"
FROM
	ALIQUOT,
	TEST,
	TEST_USER,
	U_TEST_SELECTION,
	U_TEST_SELECTION_USER,
	U_METHOD_LIST
WHERE
	ALIQUOT.ALIQUOT_ID = TEST.ALIQUOT_ID
	AND TEST.TEST_ID = TEST_USER.TEST_ID
	AND TEST_USER.U_TLIST = U_TEST_SELECTION.U_TEST_SELECTION_ID
	AND U_TEST_SELECTION.U_TEST_SELECTION_ID = U_TEST_SELECTION_USER.U_TEST_SELECTION_ID
	AND TEST_USER.U_TMETHOD = U_METHOD_LIST.U_METHOD_LIST_ID (+)
	AND TEST.STATUS NOT IN ('X')
	AND ALIQUOT.NAME = '$aliquot'
	".MatrixGroupTestCondition($matrix, $group, $test)."
	AND TEST_USER.U_LEACH_ON IS NOT NULL
		";
		die(db_query_json_encode_assoc($sql));

	case '27B':		// Get Leach Batch and Leach On date for 1312/8011
		$aliquot = $_POST['aliquot'];
		$sql = "
SELECT
	TEST_USER.U_LBATCH AS \"Leach Batch\",
	TO_CHAR(TEST_USER.U_LEACH_ON, 'YYMMDD') AS \"Leach On\",
	TEST.NAME AS \"Test Name\"
FROM
	ALIQUOT,
	TEST,
	TEST_USER,
	U_METHOD_LIST
WHERE
	ALIQUOT.ALIQUOT_ID = TEST.ALIQUOT_ID
	AND TEST.TEST_ID = TEST_USER.TEST_ID
	AND TEST_USER.U_TMETHOD = U_METHOD_LIST.U_METHOD_LIST_ID
	AND TEST.GROUP_ID = 3
	AND ((TEST.NAME LIKE '%1312%' AND TEST.NAME LIKE '%8011%') OR (U_METHOD_LIST.NAME LIKE '%1312%' AND U_METHOD_LIST.NAME LIKE '%8011%'))
	AND ALIQUOT.NAME = '$aliquot'
	AND TEST_USER.U_LEACH_ON IS NOT NULL
		";
		die(db_query_json_encode_assoc($sql));

	case 'Check Sample Status':		// Check Leach Not Done, 0 - pass, 1 - fail
		$aliquot = $_POST['aliquot'];
		$matrix = $_POST['matrix'];
		$group = $_POST['group'];
		$test = isset($_POST['test'])?$_POST['test']:'';
		$sql = "
SELECT
	CASE
		WHEN $_IS_LEACHATE
			AND TEST_USER.U_LEACHDONE IS NULL THEN 0
		ELSE 1
	END								AS \"Leach Done\"
FROM
	ALIQUOT,
	TEST,
	TEST_USER,
	U_TEST_SELECTION,
	U_TEST_SELECTION_USER,
	U_METHOD_LIST
WHERE
	ALIQUOT.ALIQUOT_ID = TEST.ALIQUOT_ID
	AND TEST.TEST_ID = TEST_USER.TEST_ID
	AND TEST_USER.U_TLIST = U_TEST_SELECTION.U_TEST_SELECTION_ID
	AND U_TEST_SELECTION.U_TEST_SELECTION_ID = U_TEST_SELECTION_USER.U_TEST_SELECTION_ID
	AND TEST_USER.U_TMETHOD = U_METHOD_LIST.U_METHOD_LIST_ID (+)
	AND TEST.STATUS NOT IN ('X', 'C')
	AND ALIQUOT.NAME = '$aliquot'
	".MatrixGroupTestCondition($matrix, $group, $test)."
		";
		die(db_query_json_encode_assoc($sql));

	case '27A':		// Get MS_OF MSD_OF
		$aliquot = $_POST['aliquot'];
		$sql = "
SELECT
	U_MSOF AS \"MS_OF\",
	U_MSDOF AS \"MSD_OF\"
FROM ALIQUOT
	JOIN ALIQUOT_USER ON ALIQUOT.ALIQUOT_ID = ALIQUOT_USER.ALIQUOT_ID
WHERE
	ALIQUOT.NAME = '$aliquot'
		";
		die(db_query_json_encode_assoc($sql));

	case '28':			// Get Test Name and Rush TAT from LIMS
		$matrix = $_POST['matrix'];
		$group = $_POST['group'];
		$test = isset($_POST['test'])? $_POST['test'] : '';
		$aliquot = $_POST['aliquot'];
		$sql = "
SELECT
	LISTAGG(TEST.NAME, ';') WITHIN GROUP (ORDER BY TEST.NAME) AS \"Test_Name\",
	LISTAGG(U_TEST_SELECTION_USER.U_EXT_TYPE, ';') WITHIN GROUP (ORDER BY U_TEST_SELECTION_USER.U_EXT_TYPE DESC) AS \"Ext_Type\",
	LISTAGG(CASE
		WHEN TEST_USER.U_RTAT IS NULL THEN ''
		WHEN TEST_USER.U_RTAT < 5 THEN 'R-'||TEST_USER.U_RTAT * 24
	--	WHEN TEST_USER.U_RTAT = 5 THEN 'R-1WK'
		ELSE ''
	END, ';') WITHIN GROUP (ORDER BY TEST_USER.U_RTAT) AS \"Rush_TAT\"
FROM
	ALIQUOT,
	TEST,
	TEST_USER,
	U_TEST_SELECTION,
	U_TEST_SELECTION_USER,
	U_METHOD_LIST
WHERE ALIQUOT.ALIQUOT_ID = TEST.ALIQUOT_ID
	AND TEST.TEST_ID = TEST_USER.TEST_ID
	AND TEST_USER.U_TLIST = U_TEST_SELECTION.U_TEST_SELECTION_ID
	AND U_TEST_SELECTION.U_TEST_SELECTION_ID = U_TEST_SELECTION_USER.U_TEST_SELECTION_ID
	AND TEST_USER.U_TMETHOD = U_METHOD_LIST.U_METHOD_LIST_ID (+)
	AND TEST.STATUS NOT IN ('X', 'C')
	AND ALIQUOT.NAME = '$aliquot'
	".MatrixGroupTestCondition($matrix, $group, $test)."
-- ORDER BY U_TEST_SELECTION_USER.U_EXT_TYPE DESC
		";
		die(db_query_json_encode_assoc($sql));

	case '29':
		$users = $_POST['users'];
		$arr = explode(',', $users);
		$filter = '';
		foreach($arr as $i => $user){
			$filter .= $filter?" OR ":"";
			switch($user){
				case 'Technician':
					$filter .= "(OPERATOR.DESCRIPTION LIKE '%Wet Chem%' OR OPERATOR.DESCRIPTION LIKE '%Extract%')";
					break;
				case 'Analyst':
					$filter .= "OPERATOR.DESCRIPTION LIKE '%GC%'";
					break;
			}
		}
		die(db_query_json_encode_assoc("
SELECT
	OPERATOR.OPERATOR_ID,
	OPERATOR.NAME,
--	OPERATOR.DESCRIPTION,
	OPERATOR.FULL_NAME
FROM OPERATOR
LEFT JOIN (
	SELECT $_BATCHED_BY AS NAME, COUNT(*) AS FREQUENCY FROM $_BATCH
	WHERE SYSDATE - $_BATCHED_ON < 365
	GROUP BY $_BATCHED_BY
) f ON OPERATOR.NAME = f.NAME,
OPERATOR_USER
WHERE ($filter)
	AND OPERATOR.NAME NOT LIKE '%do not use%'
	AND OPERATOR.NAME NOT LIKE '%ex-emp%'
	AND OPERATOR.OPERATOR_ID = OPERATOR_USER.OPERATOR_ID
	AND (OPERATOR_USER.U_DNU IS NULL OR OPERATOR_USER.U_DNU = 'F')
ORDER BY (
	CASE
		WHEN OPERATOR.DESCRIPTION LIKE '%GC%' THEN 2
		ELSE 1
	END
), NVL(f.FREQUENCY, 0) DESC, OPERATOR.NAME
 		"));

	case '31':			// Update LIMS
		$type = $_POST['type'];
		$matrix = $_POST['matrix'];
		$group = $_POST['group'];
		$test = isset($_POST['test'])? $_POST['test'] : '';
		$nBatch_ID = $_POST['nBatch_ID'];
		$extraction_done = isset($_POST['extraction_done']) && $_POST['extraction_done'] == 'true';
		$manual_injection = isset($_POST['manual_injection']) && $_POST['manual_injection'] == 'true';

		$test_ids = db_query_first_result("
SELECT
	DBMS_LOB.SUBSTR(TRIM(',' FROM CLOBAGG(TEST.TEST_ID||',')), 2000, 1)
".FromWhere($matrix, $group, $test, $nBatch_ID,
	$extraction_done&&$type!='Manual Injection'? ($group=='NJ-EPH'? "AND (
		$_DATAF.SID IS NOT NULL AND $_DATAF.COLOR IS NOT NULL OR
		$_DATAF.SID IS NULL AND $_DATAF.COLOR IS NULL
	)" : "AND $_DATA.Done > 0") : '')."
		");
		if (!$test_ids){
			die(json_encode(array(
				'success' => true,
				'query' => 'No records to update on LIMS.'
			)));
		}

		if (!$extraction_done)
			$sql = "
UPDATE TEST SET
	STATUS = 'P'
WHERE TEST_ID IN ($test_ids)
	AND STATUS IN ('V', 'P')";
		else
			$sql = "
UPDATE TEST SET
	STATUS = CASE WHEN NAME LIKE 'Extract _ Hold%' THEN 'C' ELSE 'P' END
WHERE TEST_ID IN ($test_ids)
	AND STATUS IN ('V', 'P')";
		if (!db_exec($sql)){
			die(json_encode(array(
				'success' => false,
				'query' => $sql
			)));
		}

		if (!$extraction_done){
			$sql = "
UPDATE TEST_USER SET
	U_EXT_ON = NULL,
	U_EXT_BY = NULL,
	U_EXTDONE = NULL
WHERE TEST_ID IN ($test_ids) AND TEST_ID IN (
	SELECT TEST_ID FROM TEST
	JOIN ALIQUOT ON ALIQUOT.ALIQUOT_ID = TEST.ALIQUOT_ID
	JOIN $_DATA ON REPLACE(REPLACE($_DATA.ALIQUOT, 'MSD', ''), 'MS', '') = ALIQUOT.NAME
	WHERE $_DATA.COMMENTS LIKE '%RE-EXTRACT%'
)
	AND (U_EBATCH IS NULL OR U_EBATCH <> '$nBatch_ID')
			";
			if (!db_exec($sql)){
				die(json_encode(array(
					'success' => false,
					'query' => $sql
				)));
			}
		}

		$sql = "
UPDATE TEST_USER SET
	U_EBATCH = '$nBatch_ID'
WHERE TEST_ID IN ($test_ids)
	AND (U_EBATCH IS NULL OR U_EBATCH <> '$nBatch_ID')
		";
		if (!db_exec($sql)){
			die(json_encode(array(
				'success' => false,
				'query' => $sql
			)));
		}

		if ($extraction_done){
			$sql = "
UPDATE TEST_USER SET
	U_EXT_ON = (
		SELECT MAX(analyze_date) FROM $_DATA d
			LEFT JOIN $_BATCH b ON b.$_BID = d.BID
			LEFT JOIN ALIQUOT a ON a.NAME = REPLACE(REPLACE(d.ALIQUOT, 'MSD', ''), 'MS', '')
			LEFT JOIN TEST t ON t.ALIQUOT_ID = a.ALIQUOT_ID
		WHERE t.TEST_ID = TEST_USER.TEST_ID
			AND b.NBATCH_ID = TEST_USER.U_EBATCH
	),
	U_EXT_BY = (
		SELECT MAX(o.OPERATOR_ID) FROM OPERATOR o
			LEFT JOIN $_DATA d ON o.NAME = d.BATCHED_BY
			LEFT JOIN $_BATCH b ON b.$_BID = d.BID
			LEFT JOIN ALIQUOT a ON a.NAME = REPLACE(REPLACE(d.ALIQUOT, 'MSD', ''), 'MS', '')
			LEFT JOIN TEST t ON t.ALIQUOT_ID = a.ALIQUOT_ID
		WHERE t.TEST_ID = TEST_USER.TEST_ID
			AND b.NBATCH_ID = TEST_USER.U_EBATCH
	),
	U_EXTDONE = (
		SELECT MAX(".($group=='NJ-EPH'? "f.FRACTIONATED_ON": ($manual_injection? "d.ANALYZE_DATE" : "d.TRANSFERRED_ON")).") FROM $_DATA d".($group=='NJ-EPH'? " LEFT JOIN $_DATAF f ON d.ID = f.SID" : '')."
			LEFT JOIN $_BATCH b ON b.$_BID = d.BID
			LEFT JOIN ALIQUOT a ON a.NAME = REPLACE(REPLACE(d.ALIQUOT, 'MSD', ''), 'MS', '')
			LEFT JOIN TEST t ON t.ALIQUOT_ID = a.ALIQUOT_ID
		WHERE t.TEST_ID = TEST_USER.TEST_ID
			AND b.NBATCH_ID = TEST_USER.U_EBATCH
	)
WHERE TEST_ID IN ($test_ids)
	AND (U_EXT_ON IS NULL OR U_EXTDONE IS NULL)
			";
			if (!db_exec($sql)){
				die(json_encode(array(
					'success' => false,
					'query' => $sql
				)));
			}
		}
		die(json_encode(array(
			'success' => true,
			'query' => $sql
		)));

	case '32':			// Get Samples to Update
		$type = $_POST['type'];
		$matrix = $_POST['matrix'];
		$group = $_POST['group'];
		$test = isset($_POST['test'])? $_POST['test'] : '';
		$nBatch_ID = $_POST['nBatch_ID'];
		$extraction_done = isset($_POST['extraction_done'])? $_POST['extraction_done'] == 'true' : false;
		$sql = SelectSamplesToUpdate($matrix, $group, $test, $nBatch_ID,
			$extraction_done&&$type!='Manual Injection'? ($group=='NJ-EPH'? "AND (
				$_DATAF.SID IS NOT NULL AND $_DATAF.COLOR IS NOT NULL OR
				$_DATAF.SID IS NULL AND $_DATAF.COLOR IS NULL
			)" : "AND $_DATA.Done > 0") : ''
		);
		die(db_query_json_encode_with_column_names($sql));

	case '34':			// Update LIMS reset sample status after sample deleted
		$matrix = $_POST['matrix'];
		$group = $_POST['group'];
		$test = isset($_POST['test'])? $_POST['test'] : '';
		$nBatch_ID = $_POST['nBatch_ID'];
		$ID = $_POST['ID'];

		$sql = "
SELECT
	DBMS_LOB.SUBSTR(TRIM(',' FROM CLOBAGG(TEST.TEST_ID||',')), 2000, 1)
".FromWhere($matrix, $group, $test, $nBatch_ID, "AND ALIQUOT.NAME IN (
		SELECT REPLACE(REPLACE(ALIQUOT, 'MSD', ''), 'MS', '') FROM $_DATA
		WHERE ID = $ID
	)
	AND TEST_USER.U_EBATCH = ".sqlstr($nBatch_ID)."
		");
		$test_ids = db_query_first_result($sql);
		if (!$test_ids){
			die(json_encode(array(
				'success' => true,
				'query' => $sql,
				'message' => 'No records to update on LIMS.'
			)));
		}

		$sql = "
UPDATE TEST SET
	STATUS = 'V'
WHERE TEST_ID IN ($test_ids)
	AND STATUS NOT IN ('V')
		";
		if (!db_exec($sql)){
			die(json_encode(array(
				'success' => false,
				'query' => $sql
			)));
		}

		$sql = "
UPDATE TEST_USER SET
	U_EBATCH = NULL
WHERE TEST_ID IN ($test_ids)
	AND U_EBATCH IS NOT NULL
		";
		if (!db_exec($sql)){
			die(json_encode(array(
				'success' => false,
				'query' => $sql
			)));
		}

		$sql = "
UPDATE TEST_USER SET
	U_EXT_ON = NULL,
	U_EXT_BY = NULL
WHERE TEST_ID IN ($test_ids)
	AND U_EXT_ON IS NOT NULL
		";
		if (!db_exec($sql)){
			die(json_encode(array(
				'success' => false,
				'query' => $sql
			)));
		}

		$sql = "
UPDATE TEST_USER SET
	U_EXTDONE = NULL
WHERE TEST_ID IN ($test_ids)
	AND U_EXTDONE IS NOT NULL
		";
		if (!db_exec($sql)){
			die(json_encode(array(
				'success' => false,
				'query' => $sql
			)));
		}
		die(json_encode(array(
			'success' => true
		)));

	case '35':		// Whether batch is closed(batch has completed for more than 24 hours)
		$nBatch_ID = $_POST['nBatch_ID'];
		die(db_query_json_encode_first_result("
			SELECT
				CASE WHEN $_BATCHED_ON IS NULL OR SYSDATE - $_BATCHED_ON < 1 THEN 0 ELSE 1 END
			FROM $_BATCH WHERE NBATCH_ID = '$nBatch_ID'
		"));
/*
	case '37':		// Dashboard
		die(db_query_json_encode_with_column_names("
SELECT
	\"Matrix\",
	\"Group\",
	\"Test\",
	\"Due Today\",
	\"Tomorrow\",
	\"After Tomorrow\"
FROM (
	SELECT
		0 AS ORD,
		'Matrix' AS \"Matrix\",
		'Group' AS \"Group\",
		'Test' AS \"Test\",
		'Due Today' AS \"Due Today\",
		'Tomorrow' AS \"Tomorrow\",
		'After Tomorrow' \"After Tomorrow\"
	FROM DUAL
	UNION
	SELECT
		1 AS ORD,
		\"Matrix\",
		\"Group\",
		\"Test\",
		TO_CHAR(SUM(\"Due Today\")) AS \"Due Today\",
		TO_CHAR(SUM(\"Tomorrow\")) AS \"Tomorrow\",
		TO_CHAR(SUM(\"After Tomorrow\")) AS \"After Tomorrow\"
	FROM (
		SELECT
			(
				SELECT EXTRACTION_MATRIX
				FROM MATRIX_MAPPING
				WHERE x.\"Group\" LIKE '%'||METHOD||'%'
					AND (MATRIX_TYPE IS NULL OR MATRIX_TYPE = x.\"Matrix\")
					AND ROWNUM = 1
			) AS \"Matrix\",
			\"Group\",
			\"Test\",
			CASE WHEN \"Day Left\" <= 1 THEN 1
				ELSE 0
			END AS \"Due Today\",
			CASE WHEN \"Day Left\" = 2 THEN 1
				ELSE 0
			END AS \"Tomorrow\",
			CASE WHEN \"Day Left\" > 2 THEN 1
				ELSE 0
			END AS \"After Tomorrow\"
		FROM (
			".GetSamples()."
		) x
	) y
	GROUP BY \"Matrix\", \"Group\", \"Test\"
)
ORDER BY ORD, \"Matrix\", \"Group\", \"Test\"
		"));
*/
	case '38':
		die(db_query_json_encode_assoc("
			SELECT COLOR_REASON
			FROM $_BATCH
			WHERE COLOR_REASON IS NOT NULL
			GROUP BY COLOR_REASON
			ORDER BY COUNT(*) DESC
		"));

	case 'Get Shipping List':
		$batch_id = $_POST['batch_id'];
		die(db_query_json_encode("
			SELECT SHIPPING_INDEX
			FROM $_DATA D
			LEFT JOIN $_BATCH B ON D.BID = B.$_BID
			WHERE NBATCH_ID = '$batch_id' AND SHIPPED_ON IS NOT NULL
			GROUP BY SHIPPING_INDEX
			ORDER BY SHIPPING_INDEX DESC
		"));

	case 'Get Server Time':
		die(db_query_json_encode_first_result("
			SELECT TO_CHAR(SYSDATE, 'MM/DD/YYYY HH24:MI') FROM DUAL
		"));

	default:
		die(db_query_json_encode_assoc($q));
}

function SelectSamplesToUpdate($matrix, $group, $test, $nBatch_ID, $and=''){
	global $_BATCH, $_DATA, $_TESTLIST, $_BID;
	return "
	SELECT
		ALIQUOT.NAME		AS ALIQUOT_NAME,
		ALIQUOT.MATRIX_TYPE,
		U_TEST_SELECTION_USER.U_EXT_TYPE		AS EXT_TYPE,
		$_TESTLIST.NAME		AS TESTLIST_NAME,
		TEST.TEST_ID,
		TEST.NAME			AS TEST_NAME,
		TEST.STATUS,
		TEST_USER.U_THOLD	AS THOLD,
		TEST_USER.U_RTAT		AS RTAT,
		(SELECT CASE WHEN COMMENTS LIKE '%RE-EXTRACT%' THEN 'Yes' ELSE 'No' END
			FROM $_DATA
			JOIN $_BATCH ON $_BATCH.$_BID = $_DATA.BID
			WHERE ROWNUM = 1
				AND $_BATCH.NBATCH_ID = '$nBatch_ID'
				AND REPLACE(REPLACE($_DATA.ALIQUOT, 'MSD', ''), 'MS', '') = ALIQUOT.NAME
		)	AS RE_EXT,
		TEST_USER.U_EBATCH	AS EBATCH,
		TO_CHAR(TEST_USER.U_EXT_ON, 'MM/DD/YYYY HH24:MI') AS EXT_ON,
		(SELECT NAME FROM OPERATOR WHERE OPERATOR_ID = TEST_USER.U_EXT_BY) AS EXT_BY,
		TO_CHAR(TEST_USER.U_EXTDONE, 'MM/DD/YYYY HH24:MI') AS EXTDONE,
		U_METHOD_LIST.NAME,
		TEST.GROUP_ID
	".FromWhere($matrix, $group, $test, $nBatch_ID, $and)."
	ORDER BY TO_NUMBER($_DATA.OID)
	";
}

function FromWhere($matrix, $group, $test, $nBatch_ID, $and=''){
	global $_BATCH, $_DATA, $_DATAF, $_TESTLIST, $_BID;
	return "FROM
		ALIQUOT,
		ALIQUOT_USER,
		TEST,
		TEST_USER,
		U_TEST_SELECTION,
		U_TEST_SELECTION_USER,
		U_METHOD_LIST,
		$_DATA,
		$_DATAF,
		$_BATCH,
		$_TESTLIST
	WHERE ALIQUOT.ALIQUOT_ID = TEST.ALIQUOT_ID
		AND ALIQUOT.ALIQUOT_ID = ALIQUOT_USER.ALIQUOT_ID
		AND TEST.TEST_ID = TEST_USER.TEST_ID
		AND TEST_USER.U_TLIST = U_TEST_SELECTION.U_TEST_SELECTION_ID
		AND U_TEST_SELECTION.U_TEST_SELECTION_ID = U_TEST_SELECTION_USER.U_TEST_SELECTION_ID
		AND TEST_USER.U_TMETHOD = U_METHOD_LIST.U_METHOD_LIST_ID (+)
		AND REPLACE(REPLACE($_DATA.ALIQUOT, 'MSD', ''), 'MS', '') = ALIQUOT.NAME
		AND $_DATAF.SID (+) = $_DATA.ID
		AND $_BATCH.$_BID = $_DATA.BID
		AND $_TESTLIST.TID = $_DATA.TID
		AND TEST.STATUS NOT IN ('X', 'C')
		".MatrixGroupTestCondition($matrix, $group, $test, true).
		($test? TestCondition2() : '')."
		AND $_BATCH.NBATCH_ID = '$nBatch_ID'
		-- Only update tests belong to GC department in LIMS
		AND TEST.GROUP_ID = 5
		$and";
}

function MatrixGroupTestCondition($matrix, $group, $test, $for_update_lims=false){
	return MatrixCondition("'$matrix'", $for_update_lims).GroupCondition("'$group'").TestCondition($test);
}

function MatrixCondition($matrix, $for_update_lims=false){
	global $_IS_LEACHATE;
	return " AND (
		$_IS_LEACHATE
		OR
		-- Aqueous
		$matrix LIKE '%Aqueous%'
			AND ALIQUOT.MATRIX_TYPE IN ('Aqueous', 'Drinking Water', 'Waste Water')
		OR
		-- Soil
		$matrix LIKE '%Soil%'
			AND ALIQUOT.MATRIX_TYPE NOT IN ('Liquid', 'Wipes', 'Aqueous', 'Drinking Water', 'Waste Water')
		OR
		-- Liquid
		$matrix LIKE '%Liquid%'
			AND ALIQUOT.MATRIX_TYPE IN ".($for_update_lims? "('Liquid', 'Solid')" : "('Liquid')")."
		-- Wipes
		OR
		$matrix = ALIQUOT.MATRIX_TYPE
	)";
}

function GroupCondition($group){
	return " AND (
		$group IN ('Herb') AND TEST.GROUP_ID IN (5) AND U_TEST_SELECTION_USER.U_EXT_TYPE LIKE '%Herb%' OR
		$group IN ('PCB/Pest') AND TEST.GROUP_ID IN (5) AND (
				U_METHOD_LIST.NAME IS NULL OR
				U_METHOD_LIST.NAME NOT LIKE '%608%'
			)
			AND (
				U_TEST_SELECTION_USER.U_EXT_TYPE LIKE '%PCB%' OR
				U_TEST_SELECTION_USER.U_EXT_TYPE LIKE '%Pesticides%'
			) OR
		$group IN ('PCB/Pest by 608','PCB/Pest by 608.3') AND TEST.GROUP_ID IN (5)
			AND U_TEST_SELECTION_USER.U_EXT_TYPE IN ('PCB', 'Pesticides')
			AND U_METHOD_LIST.NAME LIKE '%608%' OR
		$group IN ('8011', '504.1') AND TEST.GROUP_ID IN (3, 5) AND U_TEST_SELECTION_USER.U_EXT_TYPE LIKE '%8011%' OR U_TEST_SELECTION_USER.U_EXT_TYPE LIKE '%504.1%' OR
		$group IN ('Acids') AND TEST.GROUP_ID IN (5) AND U_TEST_SELECTION_USER.U_EXT_TYPE LIKE '%Acids%' OR
		$group IN ('Gas') AND TEST.GROUP_ID IN (5) AND U_TEST_SELECTION_USER.U_EXT_TYPE LIKE '%Gas%' OR
		TEST.GROUP_ID IN (5) AND U_TEST_SELECTION_USER.U_EXT_TYPE = $group
	)";
}

function TestCondition($test){
	if ($test){
		$tests = implode(',', $test);
		return "
AND (
	'$tests' LIKE '%TCLP%' AND TEST.NAME LIKE '%TCLP%' OR
	'$tests' LIKE '%SPLP%' AND TEST.NAME LIKE '%SPLP%' OR
	'$tests' NOT LIKE '%SPLP%' AND '$tests' NOT LIKE '%TCLP%'
		AND TEST.NAME NOT LIKE '%SPLP%' AND TEST.NAME NOT LIKE '%TCLP%'
)
AND (
	-- GC Department
	TEST.GROUP_ID = 5 AND '$tests' LIKE '%'||U_TEST_SELECTION_USER.U_EXT_TYPE||'%' OR
	-- Non-GC Department
	TEST.GROUP_ID = 3
)
		";
	}
	else
		return '';
}

function TestCondition2(){
	global $_TESTLIST;
	return "
	AND (
		-- MDL Study
		REGEXP_LIKE(ALIQUOT_USER.U_FIELD_ID, 'MDL SPIKE|MDL BLANK')
		AND (
			U_TEST_SELECTION_USER.U_EXT_TYPE IN ('PCB', 'Pesticides') AND $_TESTLIST.NAME = TEST.NAME
			OR
			NOT U_TEST_SELECTION_USER.U_EXT_TYPE IN ('PCB', 'Pesticides') AND $_TESTLIST.NAME LIKE '%'||REPLACE(U_TEST_SELECTION_USER.U_EXT_TYPE, ' ', '%')||'%'
		)
		OR
		-- NON MDL Study
		NOT REGEXP_LIKE(ALIQUOT_USER.U_FIELD_ID, 'MDL SPIKE|MDL BLANK')
		AND $_TESTLIST.NAME LIKE '%'||REPLACE(U_TEST_SELECTION_USER.U_EXT_TYPE, ' ', '%')||'%'
	)
	";
}

function GetSamples($matrix='', $group='', $test='', $search='', $no_mdl_study=false){
	global $_IS_LEACHATE, $_TO_EXT_MATRIX, $_TO_EXT_GROUP, $_TO_EXT_TEST;
	return "
SELECT
	z.*,
	CEIL(TRUNC(\"Due Date\") - SYSDATE) AS \"Day Left\"
FROM (
	SELECT
		w.*,
		\"Ext Due\"
			-CASE TRIM(TO_CHAR(\"Ext Due\", 'DAY'))
				WHEN 'SATURDAY' THEN 1
				WHEN 'SUNDAY' THEN 2
				ELSE 0
			END AS \"Due Date\"
	FROM (
		SELECT
			v.*,
			LEAST(\"HT Due\", \"Result Due\") AS \"Ext Due\"
		FROM (
			SELECT
				\"Batch ID\",
				\"Sample ID\",
				MIN(\"Matrix\") AS \"Matrix\",
				MIN(\"Group\") AS \"Group\",
				LISTAGG(\"Ext. Type\", '<br>') WITHIN GROUP (ORDER BY \"Ext. Type\") AS \"Ext. Type\",
				MIN(\"Test\") AS \"Test\",
				LISTAGG(\"Test Name\", '<br>') WITHIN GROUP (ORDER BY \"Test Name\") AS \"Test Name\",
				MAX(\"MS OF\") AS \"MS OF\",
				MAX(\"MSD OF\") AS \"MSD OF\",
				MIN(\"Client Field ID\") AS \"Client Field ID\",
				MIN(\"Client\") AS \"Client\",
				MAX(\"Leach Batch\") AS \"Leach Batch\",
				LISTAGG(\"Leach On\", '<br>') WITHIN GROUP (ORDER BY \"Leach On\") AS \"Leach On\",
				LISTAGG(\"Leach Done\", '<br>') WITHIN GROUP (ORDER BY \"Leach Done\") AS \"Leach Done\",
				MIN(\"Rush TAT\") AS \"Rush TAT\",
				MIN(\"HT Due\") AS \"HT Due\",
				MIN(\"Result Due\") AS \"Result Due\"
			FROM (
				SELECT
					t.*,
					\"Date Results Required\"
					+(
						SELECT MAX(DAYS) FROM \"Calc Result Due\" c
						WHERE (c.\"Test Group\" LIKE '%'||\"Group\"||'%' OR c.\"Test Group\" NOT LIKE '%'||\"Group\"||'%' AND c.\"Test Group\" = 'Others')
							AND c.U_RTAT = \"RTAT\"
					) AS \"Result Due\"
				FROM (
					SELECT
						TEST_USER.U_EBATCH AS \"Batch ID\",
						ALIQUOT.NAME AS \"Sample ID\",
						$_TO_EXT_MATRIX AS \"Matrix\",
						$_TO_EXT_GROUP AS \"Group\",
						$_TO_EXT_TEST AS \"Test\",
						U_TEST_SELECTION_USER.U_EXT_TYPE AS \"Ext. Type\",
						TEST.NAME AS \"Test Name\",
						ALIQUOT_USER.U_MSOF AS \"MS OF\",
						ALIQUOT_USER.U_MSDOF AS \"MSD OF\",
						ALIQUOT_USER.U_FIELD_ID AS \"Client Field ID\",
						CLIENT_USER.U_SHORT_NAME AS \"Client\",
						TEST_USER.U_LBATCH AS \"Leach Batch\",
						TEST_USER.U_LEACH_ON AS \"Leach On\",
						TEST_USER.U_LEACHDONE AS \"Leach Done\",
						CASE
							WHEN TEST_USER.U_RTAT < 4 THEN 'R-'||TEST_USER.U_RTAT * 24
						--	WHEN TEST_USER.U_RTAT = 5 THEN 'R-1WK'
							ELSE ''
						END AS \"Rush TAT\",
						CASE WHEN TEST_USER.U_LEACH_ON IS NULL THEN
							ALIQUOT_USER.U_SAMPLE_DATE
							+CASE
								WHEN ALIQUOT.MATRIX_TYPE IN ('Aqueous','Waste Water','Drinking Water') THEN U_TEST_SELECTION_USER.U_HTIME_AQ
								ELSE U_TEST_SELECTION_USER.U_HTIME_SOIL
							END
						ELSE
							TEST_USER.U_LEACH_ON + 7
						END AS \"HT Due\",
						TEST.DATE_RESULTS_REQUIRED AS \"Date Results Required\",
						TEST_USER.U_RTAT AS \"RTAT\"
					FROM
						CLIENT,
						CLIENT_USER,
						SAMPLE,
						SAMPLE_USER,
						ALIQUOT,
						ALIQUOT_USER,
						TEST,
						TEST_USER,
						U_TEST_SELECTION,
						U_TEST_SELECTION_USER,
						U_METHOD_LIST
					WHERE
						CLIENT.CLIENT_ID = CLIENT_USER.CLIENT_ID
						AND CLIENT.CLIENT_ID = SAMPLE.CLIENT_ID
						AND SAMPLE.SAMPLE_ID = SAMPLE_USER.SAMPLE_ID
						AND SAMPLE.SAMPLE_ID = ALIQUOT.SAMPLE_ID
						AND ALIQUOT.ALIQUOT_ID = ALIQUOT_USER.ALIQUOT_ID
						AND ALIQUOT.ALIQUOT_ID = TEST.ALIQUOT_ID
						AND TEST.TEST_ID = TEST_USER.TEST_ID
						AND TEST_USER.U_TLIST = U_TEST_SELECTION.U_TEST_SELECTION_ID
						AND U_TEST_SELECTION.U_TEST_SELECTION_ID = U_TEST_SELECTION_USER.U_TEST_SELECTION_ID
						AND TEST_USER.U_TMETHOD = U_METHOD_LIST.U_METHOD_LIST_ID (+)
						AND TEST.GROUP_ID IN (3, 5)
						AND (
							SAMPLE.EXTERNAL_REFERENCE LIKE 'E1%'
							OR
							SAMPLE.EXTERNAL_REFERENCE LIKE 'E20%'
						)
						".($no_mdl_study?"
							AND NOT REGEXP_LIKE(ALIQUOT_USER.U_FIELD_ID, 'MDL SPIKE|MDL BLANK')
						":'')."
						".($matrix && $group? MatrixGroupTestCondition($matrix, $group, $test) : "
							AND TEST_USER.U_EBATCH IS NOT NULL
							AND TEST_USER.U_EXT_ON IS NULL
							AND TEST_USER.U_EXTDONE IS NULL
							AND TEST.STATUS <> 'X'
						--	AND SYSDATE - SAMPLE.RECEIVED_ON < 60
						")."
						".($search?'':"
							AND TEST.NAME NOT LIKE '%Project Revision%'
							AND (
								NOT $_IS_LEACHATE OR TEST_USER.U_LEACHDONE IS NOT NULL
							)
							AND SAMPLE_USER.U_CSTATUS <> 'PRELOG'
							AND TO_DATE(TO_CHAR(SAMPLE.RECEIVED_ON, 'dd-mon-yyyy')||' '||SAMPLE_USER.U_RTIME, 'dd-mon-yyyy hh24:mi:ss') < SYSDATE
							AND TEST.STATUS = 'V'
							AND TEST_USER.U_EXT_ON IS NULL
							AND (TEST_USER.U_THOLD IS NULL OR TEST_USER.U_THOLD = 'F')
						")."
				) t
			) u
			GROUP BY u.\"Batch ID\", u.\"Sample ID\"
		) v
	) w
) z
	";
}
?>