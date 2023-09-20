<?
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
?>
<html>
	<head>
		<?/*<!--
		<meta http-equiv="cache-control" content="max-age=0"/>
		<meta http-equiv="cache-control" content="no-cache"/>
		<meta http-equiv="expires" content="0"/>
		<meta http-equiv="expires" content="Tue, 01 Jan 1980 1:00:00 GMT"/>
		<meta http-equiv="pragma" content="no-cache"/>
		<meta http-equiv="X-UA-Compatible" content="chrome=1"/>
		-->*/?>
		<style>
		* {
			margin: 0;
		}
		html,
		body {
			height: 100%;
			font-size: 12px;
			background-color: darkgray;
		}

		@page{
		//	size: auto;   /* auto is the initial value */
		//	size: 8.5in 11in;
		//	size: 11in 8.5in;
			/* this affects the margin in the printer settings */
			margin: 0;
		}
		#paper{
			background-color: white;
			-webkit-box-shadow: 2px 3px 10px 2px rgba(0,0,0,0.48);
			-moz-box-shadow: 2px 3px 10px 2px rgba(0,0,0,0.48);
			box-shadow: 2px 3px 10px 2px rgba(0,0,0,0.48);
		//	min-width: 8.5in;
		//	max-width: 8.5in;
		//	min-height: 11in;
		//	max-height: 11in;
		//	min-width: 11in;
		//	max-width: 11in;
		//	min-height: 8.5in;
		//	max-height: 8.5in;
			margin-left: auto;
			margin-right: auto;
			margin-top: 0.5in;
			margin-bottom: 0.5in;
		}
		@media print{
			#paper{
				box-shadow: none;
				margin: 0;
			}
		}

		#printable{
			left: 0.16in;
			right: 0.16in;
			top: 0.16in;
			bottom: 0.16in;
		//	border: dotted 1px red;
		}
		@media print{
			#printable{
				border: none;
			}
		}

		table {
			border-collapse: collapse;
			font-size: 12px;
		}
		table td {
			white-space: nowrap;
		}

		table#samples {
			text-align: center;
			white-space: nowrap;
			margin-top: 10px;
		}
		table#samples thead th:nth-child(1) {
			width: 200px;
		}
		table#samples thead th:nth-child(2) {
			width: 100px;
		}
		table#samples thead th:nth-child(3) {
			width: 200px;
		}
		table#samples thead th:nth-child(n+4):nth-child(-n+6) {
			width: 100px;
		}
		table#samples thead th:nth-child(n+7):nth-child(-n+11) {
			width: 100px;
		}
		table#samples tbody td:nth-child(n+11):nth-child(-n+12) {
		/*	font-size: 0.65em;	*/
		}
		table#samples thead th:not(:first-child) {
			padding: 0px 8px;
		}
		table#samples tbody td:nth-child(2) {
			text-align: left;
		}
		table#samples tbody td {
			border-top: solid 1px black;
			border-bottom: solid 1px black;
			padding: 0px 4px;
		}

		.header {
			font-weight: bold;
		}
		#batch_id {
			font-size: 16px;
			font-weight: bold;
		}
		#Tray {
			font-size: 16px;
			font-weight: bold;
			min-width: 100px;
		}
		#MDL_Study {
			font-size: 16px;
			font-weight: bold;
			text-align: center;
		}
		.gray {
			background-color: #ddd;
			font-weight: bold;
		}

		table.qc_fail td {
			padding-right: 18px;
		}
		</style>
		<script src="js/core.js"></script>
		<script src="js/string.js"></script>
		<script src="js/functions.js"></script>
		<script src="js/date.format.js"></script>
		<script src="settings.js?<?=date("YmdHis")?>"></script>
		<script>
		function PrintThis(){
			var batch_id = getParameterByName('batch');
			$('batch_id').set('html', batch_id);
			var data = ajax({ q: 1, nBatch_ID: batch_id });
			$(MakeID('Tray')).set('text', data['Tray']);
			$(MakeID('Type')).set('text', data['Type']);
			$(MakeID('Group')).set('text', data['Group']);
			$(MakeID('Matrix')).set('text', data['Matrix']);
			$(MakeID('Reason')).set('text', data['Reason']?'~ '+data['Reason']+' ~':'');
			$(MakeID('Extraction Date')).set('text', data['Extraction Date']);

			var _cTitle = ['Sample ID','Test Name','Batched On/By','Weighed On/By','Surrogated On/By','Transferred On/By','Fractionated On/By','Shipped On/By','Delivered On/By'];
			$$('#samples thead')[0].set('html', '<th>'+_cTitle.join('</th><th>')+'</th>');

			var data = ajax({ q: 2,
				nBatch_ID: batch_id,
				fields: JSON.stringify(_cTitle)
			});

			var t = '';
			for (var r = 0; r < data.rows.length; r++){
				var row = data.rows[r];

				t += '<tr>';
				_cTitle.each(function(col){
					var i = data.headers.indexOf(col);
					t += '<td class="'+MakeID(col)+'">';
					t += (row[i]||'');
					t += '</td>';
				});
				t += '</tr>';
			}

			var ths = $$('#samples thead tr th');
			$('samples').getElement('tbody').set('html', t);

			var width = '11in';
			var height = '8.5in';
			if ('REPORT' in _MISC_SETTINGS){
				var report_settings = _MISC_SETTINGS['REPORT'];
				if ('size' in report_settings){
					size = report_settings['size'];
					var arr = size.split(/[\s,x]+/);
					width = arr[0];
					height = arr[1];
				}
				if ('width' in report_settings)
					width = report_settings['width'];
				if ('height' in report_settings)
					height = report_settings['height'];

				var showBorderOfPrintableArea = false;
				if ('preview' in report_settings){
					var report_preview_settings = report_settings['preview'];
					if ('showBorderOfPrintableArea' in report_preview_settings)
						showBorderOfPrintableArea = report_preview_settings['showBorderOfPrintableArea'];
				}
				if (showBorderOfPrintableArea)
					$('printable').setStyle('border', 'dotted 1px red');
			}
			size = width+' '+height;

			cssAdd('@page { size: '+size+'; }');
			$('paper').setStyles({
				'min-width': width,
				'max-width': width,
				'min-height': height,
				'max-height': height,
			});

			if ($(MakeID('Group')).get('text') != 'NJ-EPH'){
				$$('#samples tr :nth-child(n+7):nth-child(-n+7)')
					.setStyle('display', 'none');
			}

		//	print();
		}
		</script>
	</head>
	<body onload="PrintThis()">
		<div id="paper" style="position:relative; height:100%">
			<div id="printable" style="position:absolute;">
				<div>
					<span id="Type" style="float:left">GC Extraction</span>
					<span style="float:right">Integrated Analytical Laboratories, LLC</span>
				</div>
				<h2 style="clear:both; text-align:center;">
					<span id="Group"></span> (<span id="Matrix">Soil</span>)
				</h2>
				<h3 style="clear:both; text-align: center;"><span id="Reason" display-pattern="~ {value} ~"></span></h3>
				<table style="width:100%">
					<tbody>
						<tr>
							<td>Extraction Date/Time:</td>
							<td id="Extraction_Date">1/4/2013 4:00pm</td>
							<td style="width:70%"></td>
							<td></td>
							<td></td>
						</tr>
						<tr>
							<td>Batch ID:</td>
							<td id="batch_id"></td>
							<td id="MDL_Study"></td>
							<td>Tray:</td>
							<td id="Tray"></td>
						</tr>
					</tbody>
				</table>
				<table id="samples">
					<thead>
						<tr></tr>
					</thead>
					<tbody>
					</tbody>
				</table>
			<div>
		<div>
	</body>
</html>