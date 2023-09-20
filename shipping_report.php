<?
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

$n = isset($_GET['n'])? intval($_GET['n']) : 1;
?>
<html>
	<head>
		<style>
			* {
				margin: 0;
			}
			html,
			body {
				height: 100%;
				font-size: 12px;
			}
			table {
				border-collapse: collapse;
				font-size: 12px;
			}
			table td {
				white-space: nowrap;
			}

			table#QC {
				margin-left: 18px;
				margin-right: 18px;
				white-space: nowrap;
				text-align: center;
			}
			table#QC thead th {
				padding: 0px 6px;
			}
			table#QC thead th:nth-child(1) {
				width: 180px;
			}
			table#QC thead th:nth-child(2) {
				width: 100px;
			}
			table#QC thead th:nth-child(n+3):nth-child(-n+7) {
				width: 180px;
			}
			table#QC tbody td {
				border-top: solid 1px black;
				border-bottom: solid 1px black;
				padding: 0px 4px;
			}
			table#QC tbody td:first-child {
				text-align: left;
			}

			table#samples {
				margin-left: 18px;
				margin-right: 18px;
				white-space: nowrap;
				text-align: center;
			}
			table#samples thead th {
				padding: 0px 6px;
			}
			table#samples thead th:nth-child(1) {
				width: 180px;
			}
			table#samples thead th:nth-child(2) {
				width: 100px;
			}
			table#samples thead th:nth-child(n+3):nth-child(-n+7) {
				width: 180px;
			}
			table#samples tbody td {
				border-top: solid 1px black;
				border-bottom: solid 1px black;
				padding: 0px 4px;
			}
			table#samples tbody td:first-child {
				text-align: left;
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
		<!--script src="http://code.jquery.com/jquery-latest.min.js"></script-->
		<!--script src="js/jquery.js"></script-->
		<script src="get.php?http://code.jquery.com/jquery-latest.min.js"></script>
		<script>var jq = jQuery.noConflict();</script>
		<script src="get.php?jQuery-Barcode-Generator/jquery-barcode.min.js"></script>
		<script src="get.php?http://ajax.googleapis.com/ajax/libs/mootools/1.4.5/mootools-yui-compressed.js"></script>
		<script src="get.php?js/string.js"></script>
		<script src="get.php?js/functions.js"></script>
		<script src="get.php?js/date.format.js"></script>
		<script src="get.php?settings.js"></script>
		<script>
		var delivery_index = <?=$n?>;

		function PrintThis(){
			var batch_id = getParameterByName('batch');
			var delivery_id = batch_id+'-'+delivery_index;
			$('printed_on').set('text', new Date().format("mm/dd/yyyy HH:MM"));
			$('batch_id').set('text', batch_id);
			var data = ajax({ q: 1, nBatch_ID: batch_id });
			$(MakeID('Tray')).set('text', data['Tray']);
			$(MakeID('Type')).set('text', data['Type']);
			$(MakeID('Group')).set('text', data['Group']);
			$(MakeID('Matrix')).set('text', data['Matrix']);
			$('delivery_location').set('text', data['Group'].in('PCB/Pest','Herb')?'ECD':'FID');
			let multiplier = data['Group'] == 'NJ-EPH'? 2 : 1;

			var _cTitle = ['Type','Sample ID','Test Name','Color','Comments','Shipped On','Shipping Index'];
			data = ajax({ q: 2,
				nBatch_ID: batch_id,
				fields: JSON.stringify(_cTitle)
			});

			var index_of_type = data.headers.indexOf('Type');
			var index_of_shipped_on = data.headers.indexOf('Shipped On');
			var index_of_shipping = data.headers.indexOf('Shipping Index');
			var s = '', count_of_QC = 0;
			var t = '', count_of_sample = 0;
			var shipped_on = '';
			for (var i = 0; i < data.rows.length; i++){
				var row = data.rows[i];
				if (row[index_of_shipping] == delivery_index){
					if (!shipped_on || row[index_of_shipped_on] < shipped_on)
						shipped_on = row[index_of_shipped_on];
					var type = row[index_of_type];
					if (type){
						s += '<tr>';
						['Sample ID','Test Name','Color','Comments'/*,'Shipped On'*/]
						.each(function(col){
							var i = data.headers.indexOf(col);
							s += '<td>'+(row[i]||'')+'</td>';
						});
						s += '</tr>';
						count_of_QC++;
					}
					else{
						t += '<tr>';
						['Sample ID','Test Name','Color','Comments'/*,'Shipped On'*/]
						.each(function(col){
							var i = data.headers.indexOf(col);
							t += '<td class="'+MakeID(col)+'">'+(row[i]||'')+'</td>';
						});
						t += '</tr>';
						count_of_sample++;
					}
				}
			}

			if (s) $('QC').getElement('tbody').set('html', s);
			else $('QC').setStyle('display', 'none');
			if (t) $('samples').getElement('tbody').set('html', t);
			else $('samples').setStyle('display', 'none');

			$('count_of_QC').set('text', count_of_QC * multiplier);
			$('count_of_sample').set('text', count_of_sample * multiplier);
			$('total').set('text', (count_of_QC+count_of_sample) * multiplier);
			$('shipped_on').set('text', shipped_on);

			// Create Bar Code
			jq('#barcode').barcode(delivery_id, 'code39', {
				barWidth: 1,
				barHeight: 30,
				output: 'svg',
			});

		//	setTimeout(function(){
		//		print();
		//	}, 100);
		}
		</script>
	</head>
	<body onload="PrintThis()">
		<div style="position:relative; height:100%">
			<div style="clear:both">
				<span id="Type" style="float:left">GC Extraction</span>
				<span style="float:right">Integrated Analytical Laboratories, LLC</span>
			</div>
			<div style="clear:both; padding-top:30px">
				<span style="float:left; width:200px; text-align:center">
					<div>Delivery Location:</div>
					<div style="font-weight: heavy"><h1 id="delivery_location">ECD</h1></div>
				</span>
				<span style="float:right; text-align:center;">
					<div id="barcode">[BAR CODE HERE]</div>
					<div>Shipped On: <span id="shipped_on"></span></div>
				</span>
			</div>
			<h2 style="clear:both; text-align: center;">
				<span id="Group"></span><br>
				<span id="Matrix"></span>
			</h2>
			<!--
			<h3 style="clear:both; text-align: center;"><span id="Reason"></span></h3>
			-->
			<div style="clear:both">
				<span>Batch ID: <span id="batch_id"></span></span>
				<span style="float:right">Tray: <span id="Tray" style="display:inline-block; min-width:100px"></span></span>
			</div>
			<table style="margin-top:50px">
				<tbody>
					<tr>
						<td>
							<table id="QC">
								<thead>
									<tr>
										<th style="width:200px">Sample_ID</th>
										<th>Test Name</th>
										<th>Color</th>
										<th>Comments</th>
										<!--
										<th>Shipped On</th>
										-->
									</tr>
								</thead>
								<tbody></tbody>
							</table>
						</td>
					</tr>
					<tr>
						<td>
							<br>
							<br>
						</td>
					</tr>
					<tr>
						<td>
							<table id="samples">
								<thead>
									<tr>
										<th style="width:200px">Sample_ID</th>
										<th>Test Name</th>
										<th>Color</th>
										<th>Comments</th>
										<!--
										<th>Shipped On</th>
										-->
									</tr>
								</thead>
								<tbody></tbody>
							</table>
						</td>
					</tr>
				</tbody>
			</table>
			<div style="clear:both">
				<table class="" style="float:right; margin-top:50px; margin-right:180px; text-align:right">
					<tbody>
						<tr><td>QCs: </td><td id="count_of_QC" style="width:60px"></td></tr>
						<tr><td>Samples: </td><td id="count_of_sample"></td></tr>
						<tr><td>Total: </td><td id="total" style="font-weight:bold"></td></tr>
					</tbody>
				</table>
			</div>
			<div style="position:absolute; bottom:0; width:100%">
				<span style="float:left">Printed On: <span id="printed_on"></span></span>
				<span style="float:right">Color:1=Clear 2=Light Yellow 3=Yellow 4=Brown 5=Black</span>
			</div>
		<div>
	</body>
</html>