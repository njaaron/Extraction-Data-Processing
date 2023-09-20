<!doctype html>
<html>
	<head>
		<style>
			* {
				-moz-box-sizing: border-box;
				-webkit-box-sizing: border-box;
				box-sizing: border-box;
				margin: 0;
			}
			html,
			body {
				height: 100%;
				font-size: 11px;
				font-family: "arial";
				padding: 0;
				margin: 0;
			}
/*
			@page narrow {
				size: 9cm 18cm
			}
			@page rotated {
				size: landscape
			}
			@page :first {
				margin-top: 10cm
			}
			@page :left {
				margin-left: 4cm;
				margin-right: 3cm;
			}
			@page :right {
				margin-left: 3cm;
				margin-right: 4cm;
			}
*/
			@page {
			/*	size: auto;	*/		/* auto is the initial value */
			/*	size: landscape;	*/
			/*	size: portrait;	*/
			/*	size: 1in 2in;	*/
			/*	marks: cross;	*/
			/*	size: 2.25in 1.25in;	*/
				size: 2.25in 2.0in;
				/* this affects the margin in the printer settings */
				margin: 0 0 0 0;
			}
			@page rotated {
				size : landscape
			}
			@media all {
				.page-break	{ display: none; }
			}
			@media print {
				.page-break	{ display: block; page-break-before: always; }
			}

//			#labels {
//				position:relative;
//				height:100%;
//			}
			.label {
				position: relative;
				display: block;
				width: 2.25in;
				max-width: 2.25in;
		/*		height: 1.25in;	*/
		/*		max-height: 1.25in;	*/
				height: 2.0in;
				max-height: 2.0in;
		/*		page : rotated;	*/
				page-break-after: always;
				border-radius: 0.08in;
		/*		padding: 0;	*/
				padding: 0.1in 0.1in 0.1in 0.1in;
			}
			@media screen {
				.label {
					border: dotted 1px red;
				}
			}
			@media print {
				.label {
					border: none;
				}
			}
			.rotate {
				transform: rotate(90deg);
			}
			.box {
				display: inline-block;
				width: 0.13in;
				height: 0.13in;
				border: solid 2px black;
				vertical-align: top;
			}
			.spike {
				margin-top: 0.25in;
				font-size: 12px;
				font-weight: bold;
				white-space: nowrap;
			}
			.spike_, .spike_BLK, .spike_DUP, .spike_TCLP, .spike_SPLP {
				display: none;
			}
			.spike_added{
				position: absolute;
				bottom: 5px;
			}
		</style>
		<link href="css/label_2.25x1.25.css?<?=date("YmdHis")?>" rel="stylesheet">
		<!--
		<link href="css/label_2.25x2.0.css" rel="stylesheet">
		<script src="get.php?http://code.jquery.com/jquery-latest.min.js"></script>
		-->
		<script src="js/jquery-1.11.3.min.js"></script>
		<script>let jq = jQuery.noConflict()</script>
		<script src="jQuery-Barcode-Generator/jquery-barcode.min.js"></script>
		<script src="js/core.js"></script>
		<script src="js/string.js"></script>
		<script src="js/functions.js?<?=date("YmdHis")?>"></script>
		<script src="js/date.format.js"></script>
		<script src="settings.js"></script>
		<script>
		let _DEFAULTS = {}

		function PrintThis(){
			let template = $('labels').get('html')
			let batch_id = getParameterByName('batch')
			template = template.replaceAll('{{Batch ID}}', batch_id)
			let batch_data = ajax({ q: 1, nBatch_ID: batch_id })
			_type = batch_data['Type']
			_matrix = batch_data['Matrix']
			_group = batch_data['Group']

			GetDefaultValues()

			let blk_test
			let _cTitle = ['Type','V/C#','Sample ID','Test Name']
			let s = ''
			ajax({ q: 2,
				nBatch_ID: batch_id,
				fields: JSON.stringify(_cTitle)
			})['rows'].each(row => {
				let column = {}
				let t = template
				for (let i = 0; i < _cTitle.length; i++){
					let field = _cTitle[i]
					let v = row[i]||''
					if (field == 'Test Name')
						v = v.replace(/MDL Study - (.*?) by .*/, '$1')
					column[field] = v
					t = t.replaceAll('{{'+field+'}}', v)
				}

				if (column['Type'] == 'BLK')
					blk_test = column['Test Name']

				let matrix = _DEFAULTS[blk_test]['BLK']['BMATRIX_LABEL']
				matrix = matrix.replace(/\s*\(.*\)\s*/g, '')
				t = t.replaceAll('{{Matrix}}', matrix)

				let barcode = column['Sample ID']

				// encode barcode
				barcode = barcode.replace('-', '')
				if (barcode.endsWith('DUP'))
					barcode = barcode.replace('DUP', '3')
				else if (barcode.endsWith('MSD'))
					barcode = barcode.replace('MSD', '2')
				else if (barcode.endsWith('MS'))
					barcode = barcode.replace('MS', '1')
				else
					barcode = barcode+'0'
				barcode = parseInt(barcode, 10).toString(36)

				if ((column['Type']||'').in('BLK','LCS','LCSD','LCS2','LCSD2','TCLP','SPLP'))
					barcode = ''

				t = t.replaceAll('{{Barcode}}', barcode)
//console.log(barcode)
				s += t
			})

			$('labels').set('html', s)

			// Create Bar Code
			jq.each(jq('.barcode'), function(i, item){
				let code = jq(item).html()
				jq(item).barcode(code, 'code39', {
					barWidth: 2,
					barHeight: 32,
					output: 'svg',
					showHRI: false
				})
			})

			document.querySelectorAll('.matrix').forEach(span_matrix => FitText(span_matrix))

		//	print()
		}

		function GetDefaultValues(sample_type){
			let data = ajax({ q: 'Get Default Values',
				type: _type,
				matrix: _matrix,
				group: _group,
				sample_type: sample_type
			})
			if (data){
				data.each(row => {
					let blank_test = row['BLK_TEST']
					let sample_type = row['SAMPLE_TYPE']
					_DEFAULTS[blank_test] = _DEFAULTS[blank_test]||{}
					_DEFAULTS[blank_test][sample_type] = row
				})
			}
			else
				console.log('Sorry. No default values found for '+_type+' - '+_matrix+' - '+_group+' - '+sample_type+'. Please check the reference table')
		}
		</script>
	</head>
	<body onload="PrintThis()">
		<div id='labels'>
			<div class="label">
				<div class="top">
					<span class="test_name">{{Test Name}}</span> - <span class="matrix">{{Matrix}}</span>
				</div>

				<center>
					<div class="barcode">{{Barcode}}</div>
				</center>
				<div class="sample_id {{Type}}">{{Sample ID}}</div>

				<div class="bottom">
					<span class="batch_id">{{Batch ID}}</span>
					<span class="type_vc"><span class="type">{{Type}}</span><div class="vc rotate">{{V/C#}}</div></span>
				</div>

				<div class="spike spike_{{Type}}">
					<span class="spike_added"><span class="box"></span> SPIKE ADDED</span>
				</div>
			</div>
		</div>
	</body>
</html>