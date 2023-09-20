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
				size: 1.0in 0.5in;
				/* this affects the margin in the printer settings */
				margin: 0 0 0 0;
			}
		//	@page rotated {
		//		size : landscape
		//	}
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
				display: block;
				width: 1.0in;
				max-width: 1.0in;
				height: 0.5in;
				max-height: 0.5in;
		/*		page : rotated;	*/
				page-break-after: always;
		/*		padding: 0;	*/
				border-radius: 0.125in;
				padding: 0.08in 0.04in 0.04in 0.04in;
			}
			@media screen {
				.label {
					border: dotted 1px red;
					margin: 0.0625in;
				}
			}
			@media print {
				.label {
					border: none;
					margin: 0;
				}
			}

			#test_name {
				font-size: 12px;
			}
			#batch_id {
				font-size: 9px;
				float: left;
			}
			#type {
				font-size: 12px;
				font-weight: bold;
			}
			#vc {
				font-size: 14px;
				font-weight: bold;
			}
			#sample_id {
				margin-top: 0.05in;
				white-space: nowrap;
				font-size: 11px;
				font-weight: bold;
				text-align: center;
			}
		/*	.ms, .msd, .dup {
				border: 2px solid;
			}
		*/
		</style>
		<script src="js/core.js"></script>
		<script src="js/string.js"></script>
		<script src="js/functions.js"></script>
		<script src="js/date.format.js"></script>
		<script src="settings.js"></script>
		<script>
		function PrintThis(){
			let template = $('labels').get('html')
			let batch_id = getParameterByName('batch')
			template = template.replaceAll('{{Batch ID}}', batch_id)

			let matrix = ajax({ q: 1, nBatch_ID: batch_id })['Matrix']
			template = template.replaceAll('{{Matrix}}', matrix)

			let _cTitle = ['Type','V/C#','Sample ID','Test Name']
			let s = ''
			ajax({ q: 2,
				nBatch_ID: batch_id,
				fields: JSON.stringify(_cTitle)
			})['rows'].each(function(row){
				let column = {}
				let t = template
				for (let i = 0; i < _cTitle.length; i++){
					column[_cTitle[i]] = row[i]
					t = t.replaceAll('{{'+_cTitle[i]+'}}', row[i]||'')
				}
				s += t
			})

			$('labels').set('html', s)
		//	print()
		}
		</script>
	</head>
	<body onload="PrintThis()">
		<div id='labels'>
			<div class="label">
				<div><span id="batch_id">{{Batch ID}}</span><span style="float:right"><span id="type">{{Type}}</span><span id="vc">{{V/C#}}</span></span></div><br>
				<div id="sample_id" class="{{Type}}">{{Sample ID}}</div>
			</div>
		</div>
	</body>
</html>