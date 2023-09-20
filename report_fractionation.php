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
			overflow: hidden;
		}
		@media print{
			#printable{
				border: none;
				-webkit-print-color-adjust: exact;
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
			table-layout: fixed;
		}
		table#samples thead {
			font-size: 10px;
		}
		table#samples tbody {
			font-size: 12px;
		}
		table#samples td:first-child {
			text-align: right;
			width: 14px;
		}
		table#samples thead th:nth-child(2) {
			width: 250px;
		}
		table#samples thead th:nth-child(3) {
			width: 50px;
		}
		table#samples thead th:nth-child(4) {
			width: 200px;
		}
		table#samples thead th:nth-child(n+5):nth-child(-n+7) {
			width: 100px;
		}
		table#samples thead th:nth-child(n+8):nth-child(-n+13) {
			width: 100px;
		}
		table#samples thead th:not(:first-child) {
			border-bottom: solid 1px black;
		}
		table#samples tbody td:nth-child(2) {
			text-align: left;
		}
		table#samples tbody td:not(:first-child) {
			border-top: solid 1px black;
			border-bottom: solid 1px black;
			padding: 0px 4px;
		}
		table#samples thead th:not(:first-child) {
			padding: 0px 2px;
		//	background-color: gray;
		}

		table.solvent {
			float: left;
			margin-left: 0.15in;
			line-height: 10px;
		}
		table.surrogate {
			float: right;
			margin-right: 0.15in;
			line-height: 10px;
		}
		#div_surrogate table {
			font-size: 90%;
			display: inline-block;
			white-space: nowrap;
			text-align: center;
		}
		#div_surrogate table th, #div_surrogate table td {
			border: solid 1px black;
			padding: 2px 4px;
			min-width: 0.8in;
		}
		#div_surrogate table .reagent_type td {
			text-align: left;
			text-decoration: underline;
			font-weight: bold;
			font-style: italic;
		}
		#div_surrogate table .reagent_name {
			text-align: right;
		}

		.header {
			font-weight: bold;
		}
		#Batch_ID {
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

		table.qc_approval td {
			padding: 0px 10px 4px 10px;
		}

		.V_C_{
			width: 0.10in;
			font-size: 0.95em;
		}
		.Sample_ID{
			font-size: 0.95em;
			width: 0.8in;
			text-align: left;
		}
		.Jar{
			font-size: 0.8em;
		}
		.Test_Name{
			width: 1.5in;
			font-size: 0.8em;
			white-space: nowrap;
		}
		.Initial{
			font-size: 0.95em;
		}
		.Final{
			font-size: 0.95em;
		}
		.Surrogate{
			font-size: 0.95em;
		}
		.pH{
			font-size: 0.8em;
		}
		._SED{
			font-size: 0.8em;
		}
		.NH{
			font-size: 0.8em;
		}
		.pH_for_Hydrolysis{
			font-size: 0.8em;
		}
		.pH_for_Acid{
			font-size: 0.8em;
		}
		.Color{
			font-size: 0.8em;
		}
		.Moist{
			font-size: 0.8em;
		}
		.Comments{
			width: 2.5in;
			white-space: normal;
			font-size: 0.8em;
		}

		#abbreviations{
			position: absolute;
			left: 0;
			bottom: 0;
		}

		#unit{
			position: absolute;
			right: 0;
			bottom: 0;
		}
		.footnote{
			text-align: right;
		}
		.qc_label{
			display: inline-block;
			width: 150px;
		}
		</style>
		<script src="handlebars/handlebars-v3.0.3.js"></script>
		<script src="js/core.js"></script>
		<script src="js/array.js"></script>
		<script src="js/string.js"></script>
		<script src="js/functions.js?<?=date("YmdHis")?>"></script>
		<script src="js/date.format.js"></script>
		<script src="js/preview.js"></script>
		<script src="js/watermark.js"></script>
		<script src="settings.js?<?=date("YmdHis")?>"></script>
		<script>
		let _type, _matrix, _group, _blk_test, _batch_id
		let _task = 'Fractionation'
		let _MDL_Study, _NO_MS_MSD_DUP, _NO_MS_MSD_DUP_2, _MSD, _QC_Fail, _QC_Fail_2
		let _sample_columns = {
			'GC Extraction': {
				'V/C#': '',
				'Sample ID': 'Sample<br>ID',
				'Jar': 'Jar',
				'Test Name': 'Test<br>Name',
				'Aliphatic Hexane Initial': 'Aliphatic Hexane<br>Initial<br>(g/ml)',
				'Aliphatic Hexane Final': 'Aliphatic Hexane<br>Final<br>(ml)',
				'Aromatic CH2CI2 Initial': 'Aromatic CH<sub>2</sub>CI<sub>2</sub><br>Initial<br>(g/ml)',
				'Aromatic CH2CI2 Final': 'Aromatic CH<sub>2</sub>CI<sub>2</sub><br>Final<br>(ml)',
				'Surrogate': 'Surrogate<br>IAS (ul)',
				'Color': 'Color',
				'Fractionation Comments': 'Comments',
			},
		}

		function ColumnProperty(col, BATCH_OR_SAMPLE, sample_type){
			BATCH_OR_SAMPLE = BATCH_OR_SAMPLE||'Sample'
			sample_type = sample_type||null
			let col_not_in_settings = true
			let steps = _STEPS[_type]
			for (let step in steps){	// Column is visible in report if it is visible in any step
			//	if (Conflicting(steps[step])) continue
				let property = steps[step][BATCH_OR_SAMPLE]
				if (col in property){
					col_not_in_settings = false
					let prop = property[col]
					if (!Conflicting(prop, 'BLK', null, 1) && !Is('Hidden', prop, 'BLK', _blk_test, 1))
						return prop
				}
			}
			if (col_not_in_settings){
				console.log('Column '+col+' not in settings file.')
			}
			return col_not_in_settings? true : null
		}

		function ColumnVisible(col, BATCH_OR_SAMPLE, sample_type){
		//	if (col == 'Moist')	// it is not in settings
		//		return true
			return !!ColumnProperty(col, BATCH_OR_SAMPLE, sample_type)
		}

		function CreateReagentFields(){
			let editable = false
			let display = _batch_id > new Date(DATE_START_TRACKING_SOLVENT_EXPIRATION).format('yymmdd', true)? '' : 'display:none'
			_REAGENT = ajax({ q: 'Get Reagent Reference',
				matrix: _matrix,
				group: _group,
				blk_test: _blk_test,
				task: _task
			})
			let html = `
	<table class="surrogate">
		<tbody>
			`
			let previous_reagent_type = null
			for (let i = 0; i < _REAGENT.length; i++){
				let reagent_type = _REAGENT[i].REAGENT_TYPE
				let reagent_order = _REAGENT[i].REAGENT_ORDER
				let reagent_name = _REAGENT[i].REAGENT_NAME
				let optional = +_REAGENT[i].OPTIONAL

				let id = MakeID(reagent_type+'_'+reagent_name)
				if (reagent_type != previous_reagent_type){
					previous_reagent_type = reagent_type
					if (reagent_type == 'Solvent'){
						html += `
		</tbody>
	</table>
	<table class="solvent">
		<tbody>
						`
					}
					html += `
			<tr class='reagent_type'>
				<td colspan='`+(_task == 'Fractionation'? '4' : '3')+`'>`+(reagent_type.replace('Solvent','Solvent(s) & Reagent(s)').replace('Surrogate','Surrogate(s)').replace('Spike','Spike(s)'))+`</td>
			</tr>
			<tr class='reagent_title'>
				<th></th>
				<th>Lot/RA/IAS #</th>
				<th style="`+display+`">Exp. Date</th>`
				if (_task == 'Fractionation'){
					html += `
				<th style="`+display+`">Quadruplicate Pass Date</th>`
				}
				html += `
			</tr>
					`
				}
				html += `
			<tr class='reagent_rows'>
				<td class='reagent_name'`+(reagent_type=='Spike'?` id='Spike`+reagent_order+`'`:'')+`>`+reagent_name+`</td>
				<td class='reagent_lotno'>`+(editable?
					`<input id="`+id+`" oninput="$('Exp_`+id+`').value=''">`:
					`<label id="`+id+`"></label>`
				)+`</td>
				<td class='reagent_expdate' style="`+display+`">`+(editable?
					`<input id="Exp_`+id+`" type="date">`:
					`<label id="Exp_`+id+`"></label>`
				)+`</td>`
				if (_task == 'Fractionation'){
					html += `
				<td class='reagent_pasdate`+(optional==4?'':' gray')+`' style="`+display+`">`+(editable?
					`<input id="Pas_`+id+`" type="date">`:
					`<label id="Pas_`+id+`"></label>`
				)+`</td>`
				}
				html += `
			</tr>`
			}
			html += `
		</tbody>
	</table>
			`
			$('div_surrogate').set('html', html)

			let _SAMPLE = _STEPS[APPNAME]['Weight']['Sample']
			$$('[name=ph]').set('html', GetLabelForDisplay('pH', _SAMPLE['pH']))

			_SAMPLE = _STEPS[APPNAME]['Surrogate / Solvent']['Sample']
			$$('[name=spike1]').set('html', GetLabelForDisplay('Spike 1', _SAMPLE['Spike 1']))
			$$('[name=spike2]').set('html', GetLabelForDisplay('Spike 2', _SAMPLE['Spike 2']).replace('Pesticides', 'Pest'))
		}

		function DisplayFields(data){
			function Evaluate(formula){
				if (formula in data)
					return data[formula]

				let result = formula
				let re = new RegExp(`\{\{(.+?)\}\}`, 'g')
				let match
				while(match = re.exec(formula)){
					let field = match[1]
					if (field in data)
						result = result.replace(match[0], data[field])
				}

				try{
					return eval(result)
				}
				catch(e){
					return result
				}
			}

			for(let field in data){
				let value = data[field]
				let id = MakeID(field)
				let ele = $(id)
				if (ele){
					if (ele.getAttribute('grand-parent-display-when'))
						ele.getParent().getParent().setStyle('display', Evaluate(ele.getAttribute('grand-parent-display-when'))?'':'none')
					if (ele.getAttribute('parent-display-when'))
						ele.getParent().setStyle('display', Evaluate(ele.getAttribute('parent-display-when'))?'':'none')
					let text = value||''
					if (ele.getAttribute('display-when'))
						text = Evaluate(ele.getAttribute('display-when'))? value : ''
					if (ele.getAttribute('display-pattern'))
						text = value? ele.getAttribute('display-pattern').replace('{value}', value) : ''
					switch(ele.getAttribute('display-type')){
						case 'label':
							let properties = ColumnProperty(field, 'Batch')
							if (properties && 'Options' in properties){
								let options = properties.Options
								if (options){
									options.each(option => {
										if (+value==option.Value)
											text = option.Text
									})
								}
							}
							else{
								text = +value? GetLabelForDisplay(field, properties) : ''
							}
							break
					}
					text = (_PRINT_NAME && id in _PRINT_NAME && text in _PRINT_NAME[id])? _PRINT_NAME[id][text] : text
					ele.set('text', text)

					let display = ColumnVisible(field, 'Batch')? '' : 'none'
					if ($(id+'_container'))
						$(id+'_container').setStyle('display', display)
					else if (!ele.getStyle('display'))
						ele.setStyle('display', display)
				}
			}
		}

		function PrintThis(){
			_batch_id = getParameterByName('batch')
			$('Batch_ID').set('html', _batch_id)

			// Loading start/end date/time
			let data = ajax({ q: 'Get Start End Date',
				batch_id: _batch_id
			})
			DisplayFields(data)

			// Loading batch record
			let batch_data = ajax({ q: 1, nBatch_ID: _batch_id })
			_type = batch_data['Type']
			_matrix = batch_data['Matrix']
			_group = batch_data['Group']
			_MDL_Study = +batch_data['MDL Study']
			_NO_MS_MSD_DUP = +batch_data['No MS/MSD/DUP']
			_NO_MS_MSD_DUP_2 = +batch_data['No MS/MSD/DUP 2']
			_MSD = 1 - batch_data['MSD']
			_QC_Fail = +batch_data['QC Fail']
			_QC_Fail_2 = +batch_data['QC Fail 2']
			_bid = batch_data['ABID']
			_incomplete = !+batch_data['Completed']

			DisplayFields(batch_data)

			let column_header = ''
			for (let col in _sample_columns[_type])
				column_header += '<th field="'+col+'">'+_sample_columns[_type][col]+'</th>'
			$('column_header').set('html', column_header)

			let _report_columns = ['Type','Year','QC 1','QC 2','Batch ID QC 1','Batch ID QC 2'].merge(Object.keys(_sample_columns[_type]))

			// Loading sample record
			let sample_data = ajax({ q: 2,
				nBatch_ID: _batch_id,
				fields: JSON.stringify(_report_columns)
			})

			let idx = {}
			for (let i = 0; i < sample_data.headers.length; i++)
				idx[sample_data.headers[i]] = i

			let prev_row = null, prev_qc1 = '', prev_qc2 = '', cls_1 = ' class="gray"', cls_2 = ' class="gray"'
			let qc_sample_list = {}
			let last_sno = null
			let t = ''
			for (let row of sample_data.rows){
				let sno = row[idx['V/C#']]
				for (let i = +last_sno + 1; i < sno; i++){
					t += '<tr>'
					Object.keys(_sample_columns[_type]).each(col=>{
						t += '<td class="'+MakeID(col)+'">'
						if (col == 'V/C#')
							t += i
						t += '</td>'
					})
					t += '</tr>'
				}
				last_sno = sno

				let type = row[idx['Type']]
				let year = row[idx['Year']]
				let sample_id = year + '-' + row[idx['Sample ID']]
				let qc_1 = row[idx['QC 1']]
				if (qc_1){
					let key = qc_1+(_group.contains('PCB/Pest')? '(PCB)' : '')
					qc_sample_list[key] = qc_sample_list[key]||[]
					qc_sample_list[key].push(sample_id)
				}
				let qc_2 = row[idx['QC 2']]
				if (qc_2){
					let key = qc_2+(_group.contains('PCB/Pest')? '(Pesticides)' : '')
					qc_sample_list[key] = qc_sample_list[key]||[]
					qc_sample_list[key].push(sample_id)
				}

				t += '<tr>'
				Object.keys(_sample_columns[_type]).each(col=>{
					let i = idx[col]
					t += '<td class="'+MakeID(col)+'">'
					if (typeof(row[i])!=='undefined'){
						if (col.in('NH', 'pH', 'pH for Hydrolysis', 'pH for Acid'))
							t += +row[i]?'Yes':'No'
						else if (col.in('Spike 1', 'Spike 2'))
							t += +row[i]?+row[i]:'NA'
						else if (col == 'Test Name'){
							let x = row[i]||''
							let p = x.indexOf(' by ')
							if (p >= 0)
								x = x.substr(0, p)
							t += x
						}
						else
							t += row[i]||''
					}
					t += '</td>'
					if (type == 'BLK' && col == 'Test Name') _blk_test = row[i]
				})
				t += '</tr>'

				if (sample_id)
					prev_row = row
			}
			for (let i = +last_sno + 1; i <= 20; i++){
				t += '<tr>'
				Object.keys(_sample_columns[_type]).each(col=>{
					t += '<td class="'+MakeID(col)+'">'
					if (col == 'V/C#')
						t += i
					t += '</td>'
				})
				t += '</tr>'
			}
			$('samples').getElement('tbody').set('html', t)

			let qc = ''
			for (let key in qc_sample_list){
				let samples = qc_sample_list[key]
				qc += '<label class="qc_label">'+key+':</label> '+samples.join(', ')+'<br>'
			}
			$('div_QC').set('html', qc)

			// Hide some columns
//			if (_matrix == 'Aqueous'){
//				$$('.not_for_aqueous').setStyle('display', 'none')
//				$$('#samples tbody td.NH').setStyle('display', 'none')
//			}
//			else{
//				$$('.for_aqueous').setStyle('display', 'none')
//				$$('#samples tbody td._SED').setStyle('display', 'none')
//				$$('#samples tbody td.pH_for_Hydrolysis').setStyle('display', 'none')
//				$$('#samples tbody td.pH_for_Acid').setStyle('display', 'none')
//				// Show correct unit for Initial
//				if ($('unit_of_initial'))
//					$('unit_of_initial').set('text', 'g')
//			}

			if ($(MakeID('Prep Method'))){
				let result = ajax({ q: 'Get Prep Method',
					matrix: _matrix,
					group: _group,
					blk_test: _blk_test
				})
				$(MakeID('Prep Method')).set('text', result[0]['Prep Method'])
			}

			// Gray out some rows, bottom to top
			let trs = $$('#samples tbody tr')
			let r
			for (r = trs.length - 1; r >= 0; r--){
				let tr = trs[r]
				let sample_id = tr.getElements('td')[1].get('text')
				if (sample_id) break
			}
			for (let j = r - 1; j >= 0; j--){
				tr = trs[j]
				sample_id = tr.getElements('td')[1].get('text')
				if (!sample_id)
					tr.addClass('gray')
			}

			Object.keys(_sample_columns[_type]).each(col=>{
				if (!ColumnVisible(col, 'Sample', null)){
					let th = $$('#samples tr th:contains('+col+'), #samples tr th[field="'+col+'"]')[0]
					if (th){
						let index = th.cellIndex+1
						$$('#samples tr th:nth-child('+index+'), #samples tr td:nth-child('+index+')')
							.setStyle('display', 'none')
					}
					let footnote = $$('div.footnote[field="'+col+'"]')[0]
					if (footnote)
						footnote.setStyle('display', 'none')
				}
			})

			// Show correct unit for Initial
//			if (_matrix && _matrix.in('Soil','Liquid','Solid by SW-846 3580A (Waste Dilution)') && $('unit_of_initial'))
//				$('unit_of_initial').set('text', 'g')

//			if ($(MakeID('Prep Method'))){
//				let result = ajax({ q: 'Get Prep Method',
//					matrix: _matrix,
//					group: _group,
//					blk_test: _blk_test
//				})
//				$(MakeID('Prep Method')).set('text', result[0]['Prep Method'])
//			}

			CreateReagentFields()

			;(ajax({ q: 'Load Reagent',
				bid: _bid,
				task: _task
			})||[]).each(reagent=>{
				let reagent_type = reagent.REAGENT_TYPE
				let reagent_name = reagent.REAGENT_NAME
				let reagent_lot = reagent.REAGENT_LOT
				let reagent_expdate = reagent.REAGENT_EXPDATE
				let reagent_pasdate = reagent.REAGENT_PASDATE
				let id = MakeID(reagent_type+'_'+reagent_name)
				SetElementValue($(id), reagent_lot)
				SetElementValue($('Exp_'+id), reagent_expdate||'NA')
				SetElementValue($('Pas_'+id), reagent_pasdate||'')
			})

			SetPageSize()

			Watermark.PrintIf(_incomplete)
		}

		addEvent('load', PrintThis)
		</script>
	</head>
	<body>
		<div id="paper" style="position:relative; height:100%">
			<div id="printable" style="position:absolute;">
				<div>
					<span id="Type" style="float:left"></span>
					<span style="float:right">Integrated Analytical Laboratories, LLC</span>
				</div>
				<h2 style="clear:both; text-align: center;">
					<span>Fractionation Log</span><br>
					<span id="Group"></span><br>
					<span id="Matrix"></span>
				</h2>
				<h3 style="clear:both; text-align: center;"><span id="Reason" display-pattern="~ {value} ~"></span></h3>
				<table style="width:100%">
					<tbody>
						<tr>
							<td>Extraction Date/Time:</td>
							<td id="Start_Date"></td>
							<td style="width:70%"></td>
							<td></td>
							<td></td>
							<td></td>
						</tr>
						<tr>
							<td>Fractionation Date/Time:</td>
							<td id="Fractionated_On"></td>
							<td style="width:70%"></td>
							<td></td>
							<td></td>
							<td></td>
						</tr>
						<tr>
							<td>Batch ID:</td>
							<td id="Batch_ID"></td>
							<td></td>
							<td></td>
							<td>Tray:</td>
							<td id="Tray"></td>
						</tr>
						<tr>
							<td>Technician:</td>
							<td id="Fractionated_By"></td>
							<td style="width:70%"></td>
							<td></td>
							<td>Prep Method:</td>
							<td id="Prep_Method"></td>
						</tr>
					</tbody>
				</table>
				<table id="samples">
					<thead>
						<tr valign="bottom" id="column_header"></tr>
					</thead>
					<tbody></tbody>
				</table>
				<br>
				<span style="display: inline-block; min-width: 300px">
					<span class="header">Comments:</span>
					<span id="comments"></span>
				</span>
				<table class="qc_fail">
					<tbody>
						<tr>
							<td id="QC_Fail" display-type="label" parent-display-when="QC Fail"></td>
							<td>Reason: <span id="Fail_Reason" display-when="QC Fail"></span></td>
							<td>By: <span id="Fail_By" display-when="QC Fail"></span></td>
							<td> <span id="Fail_On" display-when="QC Fail"></span></td>
						</tr>
						<tr>
							<td id="QC_Fail_2" display-type="label" parent-display-when="QC Fail 2"></td>
							<td>Reason: <span id="Fail_Reason_2" display-when="QC Fail 2"></span></td>
							<td>By: <span id="Fail_By_2" display-when="QC Fail 2"></span></td>
							<td> <span id="Fail_On_2" display-when="QC Fail 2"></span></td>
						</tr>
					</tbody>
				</table>
				<!--
				<table class="qc_approval">
					<tbody>
						<tr valign="top">
							<td id="QC_Approval"></td>
							<td style="white-space: normal"><span id="QC_Type"></span> <span id="Approval_Note"></span></td>
							<td id="Approval_By"></td>
							<td id="Approval_On"></td>
						</tr>
						<tr valign="top">
							<td id="QC_Approval_2"></td>
							<td style="white-space: normal"><span id="QC_Type_2"></span> <span id="Approval_Note_2"></span></td>
							<td id="Approval_By_2"></td>
							<td id="Approval_On_2"></td>
						</tr>
					</tbody>
				</table>
				-->
				<div id="div_surrogate"></div>
				<div id="div_QC" style="clear: both; padding-top: 20px; font-size: 90%;"></div>
				<div style="position:absolute; bottom:0; width:100%">
					<span style="position:absolute; left:0; bottom:0">
					</span>
					<span style="position:absolute; right:0; bottom:0">
						<div class="footnote" field="NH">*NH=Non-Homogeneous</div>
						<div class="footnote" field="pH for Hydrolysis">*pH 1=pH for Hydrolysis (>12)</div>
						<div class="footnote" field="pH for Acid">*pH 2=pH for Acid (<2)</div>
						<div class="footnote">Color:1=Clear 2=Light Yellow 3=Yellow 4=Brown 5=Black 6=Other(See Comments)</div>
					</span>
				</div>
			</div>
		</div>
	</body>
</html>