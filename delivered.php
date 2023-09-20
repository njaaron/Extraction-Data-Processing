<?
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
?>
<html>
	<head>
		<style>
		* {
			margin: 0;
		}
		html,
		body{
			height: 100%;
			font-size: 16px;
			background-color: darkgray;
			text-align: center;
		}
		div#board{
			display: inline-block;
			background-color: white;
			border-radius: 12px;
			padding: 20px;
			min-width: 700px;
			min-height: 580px;
			margin: 50px;
			box-shadow: 2px 2px 2px;
		}
		input[type=text], select{
			font-size: 18px;
			padding: 10px;
			border: solid 1px gainsboro;
			width: 170px;
		}
		input[type=checkbox]{
			width: 40px;
			height: 40px;
			vertical-align: middle;
		}
		table{
			border-collapse: collapse;
			display: none;
		}
		table th:first-child{
			width: 150px;
		}
		table th:not(:last-child){
			padding: 5px;
		}
		table td:not(:last-child){
			border: solid 1px black;
			padding: 5px;
		}
		table tr{
			vertical-align: middle;
		}
		table td{
			text-align: center;
		}
		</style>
		<script src="get.php?http://ajax.googleapis.com/ajax/libs/mootools/1.4.5/mootools-yui-compressed.js"></script>
		<script src="get.php?js/string.js"></script>
		<script src="get.php?js/functions.js"></script>
		<script src="get.php?js/date.format.js"></script>
		<script src="get.php?settings.js"></script>
		<script>
		document.addEvent('domready', function(){
			var t = '';
			ajax({ q: '29', users: 'Analyst,Technician' }).each(function(item){
				t += '<option operator_id="'+item.OPERATOR_ID+'" title="'+item.FULL_NAME+'">'+item.NAME+'</option>';
			});
			$('name').addEvent('change', NameChanged)
				.set('html', t);
			$('barcode').addEvent('input', BarcodeChanged)
				.focus();
		});

		function NameChanged(e){
			$$('input[type=checkbox]:checked').each(function(checkbox){
				ajax({ q: 16, task: '', ID: checkbox.getParent('tr').id,
					DELIVERED_BY: $('name').value
				});
			});
		}

		function BarcodeChanged(e){
			var barcode = this.value;
			var match = barcode.match(/^(\d{6}\-\d{2})\-(\d)$/);

			var batch_id = match? match[1] : '';
			var delivery_index = match? match[2] : '';

			var _cTitle = ['Type','Sample ID','Test Name','Color','Comments','Shipped On','Shipped By','Shipping Index','Delivered On','Delivered By','AID'];
			var data = ajax({ q: 2,
				nBatch_ID: batch_id,
				fields: JSON.stringify(_cTitle),
				client_time: +(new Date())
			});
			var index_of_type = data.headers.indexOf('Type');
			var index_of_shipping = data.headers.indexOf('Shipping Index');
			var index_of_shipped_on = data.headers.indexOf('Shipped On');
			var index_of_shipped_by = data.headers.indexOf('Shipped By');
			var index_of_delivered_on = data.headers.indexOf('Delivered On');
			var index_of_delivered_by = data.headers.indexOf('Delivered By');
			var index_of_id = data.headers.indexOf('AID');

			var columns = ['Sample ID','Test Name'/*,'Color','Comments'*/,'Shipped On','Shipped By','Delivered On'];
			var s = '', t = '';
			var delivered_by = '', shipped_by = '';
			for (var i = 0; i < data.rows.length; i++){
				var row = data.rows[i];
				if (row[index_of_shipping] == delivery_index){
					if (!delivered_by && row[index_of_delivered_by])
						delivered_by = row[index_of_delivered_by];
					if (!shipped_by && row[index_of_shipped_by])
						shipped_by = row[index_of_shipped_by];
					var aid = row[index_of_id];
					var type = row[index_of_type];
					if (type){
						s += '<tr id='+aid+'>';
						columns.each(function(col){
							var i = data.headers.indexOf(col);
							if (col == 'Delivered On')
								s += '<td><input type="checkbox"'+(row[i]>''?' checked':'')+'></td>';
							else
								s += '<td>'+(row[i]||'')+'</td>';
						});
						s += '</tr>';
					}
					else{
						t += '<tr id='+aid+'>';
						columns.each(function(col){
							var i = data.headers.indexOf(col);
							if (col == 'Delivered On')
								t += '<td><input type="checkbox"'+(row[i]>''?' checked':'')+'></td>';
							else
								t += '<td class="'+MakeID(col)+'">'+(row[i]||'')+'</td>';
						});
						t += '</tr>';
					}
				}
			}

			if (s) $('QC').getElement('tbody').set('html', s);
			$('QC').setStyle('display', s?'inline-block':'none');
			if (t) $('samples').getElement('tbody').set('html', t);
			$('samples').setStyle('display', t?'inline-block':'none');

			$$('input[type=checkbox]').addEvent('click', function(e){
				ajax({ q: 16, task: '', ID: this.getParent('tr').id,
					DELIVERED_ON: this.checked? '' : 'NULL',
					DELIVERED_BY: this.checked? $('name').value : 'NULL'
				});
			});

			$('name').set('value', delivered_by||shipped_by);
		}
		</script>
	</head>
	<body>
		<div id="board">
			<div>
				<h2>Sample Received</h2>
			</div>
			<div style="padding-top:20px;">
				<label style="display:inline-block; text-align:right; width:80px;">Barcode:&nbsp;</label>
				<input type="text" id="barcode" />
			</div>
			<div style="padding-top:10px;">
				<label style="display:inline-block; text-align:right; width:80px">Name:&nbsp;</label>
				<select id="name"></select>
			</div>
			<div style="margin-top:10px;">
				<table id="QC">
					<thead>
						<tr>
							<th>Sample_ID</th>
							<th>Test Name</th>
						<!--	<th>Color</th>
							<th>Comments</th>
						-->	<th>Shipped On</th>
							<th>Shipped By</th>
							<th>Received</th>
						</tr>
					</thead>
					<tbody></tbody>
				</table>
			</div>
			<div style="margin-top:10px;">
				<table id="samples">
					<thead>
						<tr>
							<th>Sample_ID</th>
							<th>Test Name</th>
						<!--	<th>Color</th>
							<th>Comments</th>
						-->	<th>Shipped On</th>
							<th>Shipped By</th>
							<th>Received</th>
						</tr>
					</thead>
					<tbody></tbody>
				</table>
			</div>
		</div>
	</body>
</html>