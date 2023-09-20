<?
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<title>Extraction</title>
	<script src="handlebars/handlebars-v3.0.3.js"></script>
	<script src="js/core.js"></script>
	<script src="js/more.js"></script>
	<link href="tabPane/tabPane.css" rel="stylesheet">
	<script src="tabPane/tabPane.js"></script>
	<link href="grid/grid.css" rel="stylesheet">
	<script src="grid/grid.js?<?=date("YmdHis")?>"></script>
	<link href="sexyAlertBox/sexyalertbox.css" rel="stylesheet">
	<script src="sexyAlertBox/sexyalertbox.js"></script>
	<script src="js/ScrollableTable.js"></script>
	<link href="css/style.css?<?=date("YmdHis")?>" rel="stylesheet">
	<script src="xregexp-3.1.1/xregexp-all.js"></script>
	<script src="js/array.js"></script>
	<script src="js/string.js"></script>
	<script src="js/functions.js?<?=date("YmdHis")?>"></script>
	<script src="js/print.js?<?=date("YmdHis")?>"></script>
	<script src="js/date.format.js"></script>
	<script src="js/shortcut.js"></script>
	<script src="InstantForm/form.js"></script>
	<script src="mask/Mask.js"></script>
	<script src="mask/Mask.Fixed.js"></script>
	<script src="mask/Mask.Regexp.js"></script>
	<script src="mask/Mask.Repeat.js"></script>
	<script src="mask/Mask.Reverse.js"></script>
	<script src="mask/Mask.Extras.js"></script>
	<script src="settings.js?<?=date("YmdHis")?>"></script>
	<script src="js/debug.js"></script>
	<script>
	document.title = APPNAME
	let batch_data
	let _has_manual_injection = 'Manual Injection' in _STEPS
	let _type = APPNAME
	let _user, _task, _matrix, _matrix_initial, _group, _blk_test = ''
	let _bid, _batch_id, _tDate
	let _status = {
		'Save On': null,
		'Done On': null
	}
	let _MDL_Study, _NO_MS_MSD_DUP, _NO_MS_MSD_DUP_2, _MSD, _QC_Fail, _QC_Fail_2
	let _batch_is_closed = false
	let _DEFAULTS = {}

	let _TASK, _BATCH, _SAMPLE, _REAGENT
	let _cTitle, _cWidth, _rTitle
	let _timeoutHnd
	let _SexyBox
	let _ROWS			// Table rows
	let _GRID, _gridHeaders
	let _TEST_LIST = {}
	let _STORAGE_KEY, _SETTINGS

	addEvents({
		domready(){
			if ($('debug'))
				$('debug').checked = _DEBUG_

			_STORAGE_KEY = MakeID(APPNAME)+'_settings'
			if (_STORAGE_KEY in localStorage && localStorage[_STORAGE_KEY]){
				_SETTINGS = JSON.parse(localStorage[_STORAGE_KEY])
				_type = _SETTINGS.type
			// *** DEBUG state will not be saved, always disabled at loading ***
			//	_DEBUG_ = $('debug').checked = _SETTINGS.debugMode
			//	$('txtSearch').value = _SETTINGS.searchBatch
				$('txtSearchSample').value = _SETTINGS.searchSample
			}

			GetTestList()

			_SexyBox = new SexyAlertBox()

			ShowUser()
		},
		unload(){
			localStorage[_STORAGE_KEY] = JSON.stringify({
				type: _type,
				debugMode: $('debug').checked,
				searchBatch: $('txtSearch').value,
				searchSample: $('txtSearchSample').value,
			})
		}
	})
	AddConfirmNavigationNotification(
		"You haven't saved changed yet. Data will be lost if you leave the page, are you sure?",
		$('save_batch') && !$('save_batch').disabled
	)

	function SetTask(task){
		_task = task
		_TASK = _STEPS[_type][_task]
		_BATCH = _TASK['Batch']
		_SAMPLE = _TASK['Sample']
		CreateTab()
	}

	function GetDefaultValue(default_value, type){
		while(typeOf(default_value) == 'object'){
			if ('ForType' in default_value){
				let for_type = default_value.ForType
				if (!for_type || typeOf(for_type)=='array' && (for_type.contains('*') || for_type.contains(type)) || for_type == 'QC' && type || for_type == 'Sample' && !type || for_type == '*'){
					default_value = default_value.Value
					continue
				}
			}
			if ('ByGroup' in default_value && _group in default_value.ByGroup){
				default_value = default_value.ByGroup[_group]
				continue
			}
			if ('ByMatrix' in default_value && _matrix in default_value.ByMatrix){
				default_value = default_value.ByMatrix[_matrix]
				continue
			}
			break
		}

		if (typeOf(default_value) == 'string'){
			if (default_value.toLowerCase() == 'now'){
				default_value = ajax({ q: 'Get Server Time' })
			}
			else if (default_value.toLowerCase() == 'batch date'){
				default_value = BatchDate().format('isoDateTime')
			}
			else if (_GRID.columnExists(default_value)){
				default_value = _GRID.getCell(r, default_value)
			}
		}

		return default_value
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

	function GetDefaultTest(sample_type){
		return Default_Test[_group]||_group
	}

	function IsTumbleBlank(){
		return _blk_test && (_blk_test.contains('TCLP')||_blk_test.contains('SPLP')||
			_type=='Manual Injection'&&_group=='8011')
	}

	function GetTestList(){
		let props = ['TEST_MATRIX','TEST_GROUP','TID']
		ajax({ q: 9,
			matrix: _matrix,
			group: _group
		}).each(item => {
			let target = _TEST_LIST
			for (let i = 0; i < props.length; i++){
				let prop = props[i]
				let value = item[prop]
				if (i < props.length - 1){
					target[value] = target[value]||{}
					target = target[value]
				}
				else
					target[value] = item.NAME
			}
		})
	}

	function ShowUser(){
		PromptForUser(result => {
			if (result){
				_user = result.user

			//	if (SHOW_DASH_BOARD_FIRST)
					ShowDashboard()
			//	else
			//		ShowTypeAndTask()
			}
		})
	}

	function ShowTypeAndTask(){
		PromptForTypeAndTask(result => {
			if (result){
				_type = 'manual_injection' in result && result.manual_injection? 'Manual Injection' : APPNAME
				SetTask(result.task)

				let buttons = $$('div.headerScreen button:not(:disabled)')
				if (buttons.length == 1)
					buttons[0].click()
			}
		})
	}

	function PromptForUser(onComplete){
		_SexyBox.custom(Handlebars.compile($('tmpl_user').get('html'))({}), {
			'user': 'value',
		},{
			'textBoxBtnOk': 'Continue',
			'textBoxBtnCancel': null,
			onShowComplete: RefreshUserOptions,
			onComplete: onComplete
		})
	}

	function PromptForTypeAndTask(onComplete){
		_SexyBox.custom(Handlebars.compile($('tmpl_type_task').get('html'))({
			has_manual_injection: _has_manual_injection,
			extraction_name: APPNAME,
			Extraction_checked: _type=='Manual Injection'?'':' checked',
			Manual_Injection_checked: _type=='Manual Injection'?' checked':''
		}), {
			'manual_injection': 'checked',
			'task': 'value',
		},{
			'textBoxBtnOk': 'Continue',
			'textBoxBtnCancel': null,
			onShowComplete: ManualInjectionChanged,
			onComplete: onComplete
		})
	}

	function ManualInjectionChanged(){
		_type = $('manual_injection') && $('manual_injection').checked? 'Manual Injection' : APPNAME
		$('Type').set('text', _type)

		$('task').set('html', '<option>'+Object.keys(_STEPS[_type]).join('</option><option>')+'</option>')
		//	.addEvent('change', RefreshUserOptions)
		//	.fireEvent('change')
		$('task').options.length > 1? $('divTask').show() : $('divTask').hide()
	}

	function RefreshUserOptions(){
		if ($('user').tagName == 'SELECT'){
			let curr_user = $('user').get('value')
			$('user').set('html', ajax({ q: '29', users: 'Technician,Analyst' }).map(item => '<option operator_id="'+item.OPERATOR_ID+'" title="'+item.FULL_NAME+'">'+item.NAME+'</option>').join(''))
			$('user').options.length > 1? $('divUser').show() : $('divUser').hide()
			if (curr_user)
				$('user').set('value', curr_user)
			if (!$('user').get('value'))
				$('user').selectedIndex = 0
		}
	}

	function TumbleBLK(add_tumble_blank){
		if (typeof add_tumble_blank == 'undefined')
			add_tumble_blank = true
		for (let r = 1; r <= _rTitle.length; r++){
			if (!GridGetCell(r, 'Sample ID')) continue

			let aliquot = GridGetCell(r, 'ALIQUOT')
			if (aliquot)
				GetTestNameFromLIMS(r, aliquot, false, add_tumble_blank)
		}
	}

	function CreateTab(){
		let BUTTONS = _TASK['Buttons']
		let buttons = []
		for (let item in BUTTONS){
			let button = BUTTONS[item]
			if (Conflicting(button)) continue
			button.text = item
		//	button.id = MakeID(item)
			buttons.push(button)
		}

		$('task_tabs').set('html', Handlebars.compile($('tmpl_tabs').get('html'))({
			step: _task,
			user: _user,
			buttons: buttons
		}))
		/* Set button styles
		buttons.each(button => {
			if ('styles' in button)
				$(button.id).setStyles(button.styles)
		})
		*/

		let tabPane = new TabPane('task_tabs', {
			onChange: idx => console.log('tab'+idx+' is now selected')
		})

		EnableButtonShortcuts('.headerScreen button')
	}

	function SetColumnRowTitles(){
		// Set Titles for Rows
		let g_index
		for (g_index in _ROW_TITLE){
			if (_group.contains(g_index))
				break
		}
		let m_index = _matrix.in(AQUEOUS)? 'Aqueous' :
				_matrix.in(LIQUID_SOLID_WIPES)? 'Liquid or Wipes' : 'Soil or Other'

		_rTitle = _ROW_TITLE[g_index][m_index].clone()
		for (let i = 1; i <= 20; i++)
			_rTitle.push(i)

		// Set Titles for Columns
		_cTitle = [], _gridHeaders = []
		for (let field in _SAMPLE){
			let properties = _SAMPLE[field]
			let include_field = false
			for (let i = 0; i < _rTitle.length; i++){
				let type = _rTitle[i]
				if (!Conflicting(properties, type, _blk_test, i)){
					include_field = true
					break
				}
			}
			if (include_field){
				_cTitle.push(field)
				_gridHeaders.push(GetLabelForDisplay(field, properties))
			}
		}
	}

	function ShowReagent(){
		if ('Reagent' in _BATCH){
			CreateReagentFields()

			ajax({ q: 'Load Reagent',
				bid: _bid,
				task: _task
			}).each(reagent => {
				let reagent_type = reagent.REAGENT_TYPE
				let reagent_name = reagent.REAGENT_NAME
				let reagent_lot = reagent.REAGENT_LOT
				let reagent_expdate = reagent.REAGENT_EXPDATE
				let reagent_pasdate = reagent.REAGENT_PASDATE
				let id = MakeID(reagent_type+'_'+reagent_name)
				SetElementValue($(id), reagent_lot)
				SetElementValue($('Exp_'+id), reagent_expdate)
				SetElementValue($('Pas_'+id), reagent_pasdate)
			})

			// Set Reagent Default Values
			for (let i = 0; i < _REAGENT.length; i++){
				let reagent_type = _REAGENT[i].REAGENT_TYPE
				let reagent_name = _REAGENT[i].REAGENT_NAME
				let id = MakeID(reagent_type+'_'+reagent_name)
				let input = $(id)
				if (input && input.get('tag') == 'input' && !input.value){
					let rows = ajax({
						q: 'Reagent Default',
						reagent_type: reagent_type,
						reagent_name: reagent_name,
						group: _group,
						matrix: _matrix,
					// Per Robert, no need to match blank test when loading reagent default values
					//	blk_test: _blk_test,
						batch_id: _batch_id
					})
					if (rows.length){
						let defaults = rows[0]
						if (defaults){
							input.value = defaults['REAGENT_LOT']
							if ($('Exp_'+id))
								$('Exp_'+id).value = defaults['REAGENT_EXPDATE']
							if ($('Pas_'+id))
								$('Pas_'+id).value = defaults['REAGENT_PASDATE']
						}
					}
				}
			}
		}
	}

	function CreateReagentFields(){
		let editable = Is('Editable', _BATCH['Reagent'])
	//	let display = _batch_id > new Date(DATE_START_TRACKING_SOLVENT_EXPIRATION).format('yymmdd', true)? '' : 'display:none'
		_REAGENT = ajax({ q: 'Get Reagent Reference',
			matrix: _matrix,
			group: _group,
			blk_test: _blk_test,
			task: _task
		})
		if (!_REAGENT.length)
			Warning('Reagent reference load error')
		let html = `
<table class="surrogate">
	<tbody>`
		let previous_reagent_type = null
		for (let i = 0; i < _REAGENT.length; i++){
			let reagent_type = _REAGENT[i].REAGENT_TYPE
			let reagent_order = _REAGENT[i].REAGENT_ORDER
			let reagent_name = _REAGENT[i].REAGENT_NAME
			let optional = +_REAGENT[i].OPTIONAL

			let lbl = reagent_name
			let match = reagent_name.match(/\(.*\)/)
			if (match){
				let formula = match[0].replace(/(\d+)/g, '<sub>$1</sub>')
				lbl = reagent_name.replace(/\(.*\)/, formula)
			}

			let id = MakeID(reagent_type+'_'+reagent_name)
			if (reagent_type != previous_reagent_type){
				previous_reagent_type = reagent_type
				html += `
		<tr>
			<td colspan='`+(_task == 'Fractionation'? '4' : '3')+`'>
				<span class='reagent_type'>`+reagent_type+`</span>`+(reagent_type=='Solvent'?' <span class="reagent_optional">(items in blue color are optional)</span>':'')+`
			</td>
		</tr>
		<tr class='reagent_title'>
			<th></th>
			<th>Lot/RA/IAS #</th>
			<th>Exp. Date</th>`
				if (_task == 'Fractionation'){
					html += `
			<th>Quadruplicate Pass Date</th>`
				}
				html += `
		</tr>`
			}
			html += `
		<tr>
			<td id='`+reagent_type+reagent_order+`' class='reagent_name`+(optional==1||optional==3||optional==4?` reagent_optional`:``)+`'>`+lbl+`</td>
			<td>`+(editable?
				`<input id="`+id+`" oninput="$('Exp_`+id+`').value=''">`:
				`<label id="`+id+`"></label>`
			)+`</td>`
			if (optional==0||optional==1||optional==4){
				html += `
				<td style="text-align: right">`+(editable?
					`<input id="Exp_`+id+`" type="date">`:
					`<label id="Exp_`+id+`"></label>`
				)+`</td>`
			}
			else{
				html += `
				<td style="text-align: right"></td>`
			}
			if (_task == 'Fractionation'){
				if (optional==4){
					html += `
					<td style="text-align: right">`+(editable?
						`<input id="Pas_`+id+`" type="date">`:
						`<label id="Pas_`+id+`"></label>`
					)+`</td>`
				}
				else{
					html += `
					<td></td>`
				}
			}
			html += `
		</tr>
			`
		}
		html += `
	</tbody>
</table>`
		$('div_surrogate').set('html', html)
	}

	function CreateBatchFields(){
		if (Is('Editable', _BATCH['QC Fail']))
			$('div_qc_fail').set('html', Handlebars.compile($('tmpl_qc_fail').get('html'))())

		$('batch').set('html', '')
		html = ''
		for (let field in _BATCH){
			let properties = _BATCH[field]
			if (Is('Hidden', properties)||Conflicting(properties)) continue
			let id = MakeID(field)
			let lbl = GetLabelForDisplay(field, properties)
			let s = '<span>'
			let is_editable = Is('Editable', properties)
			let input_type = 'Type' in properties? properties.Type : is_editable? 'text' : ''
			if (typeOf(input_type) == 'object'){
				for (let item in input_type){
					let when = input_type[item]
					while ('When' in when) when = when.When
					if (ConditionMatches(when, null)){
						input_type = item
						break
					}
				}
			}
			let styles = 'Styles' in properties?' style="'+properties.Styles+'"':''
			switch(input_type){
				case 'checkbox': {
					s += '<label>'+lbl+': </label><input type="'+input_type+'" id="'+id+'" value="1"'+styles+(is_editable?'':' disabled')+('Click' in properties? ' onclick="'+properties.Click+'"' : '')+'>'
					break
				}
				case 'text': {
					if ('Options' in properties){		// A Combo box
						s += '<label>'+lbl+': </label><input type="'+input_type+'" id="'+id+'" value="" list="'+MakeID(field)+'_list"'+styles+'>'
						let options = properties.Options
						if (typeOf(options) === 'object')
							options = GetAllKeys(options)
						let t = typeof options == 'function'?
							options():
							(options?'<option>'+options.join('</option><option>')+'</option>':'')
						s += '<datalist id="'+MakeID(field)+'_list'+'">'+t+'</datalist>'
					}
					else{
						s += '<label>'+lbl+': </label><input type="'+input_type+'" id="'+id+'" value=""'+styles+(is_editable?'':' disabled')+'>'
					}
					break
				}
				case 'text-readonly': {
					s += '<label>'+lbl+': </label><input type="'+input_type+'" id="'+id+'" value=""'+styles+' readonly>'
					break
				}
				case 'radio': {
					let options = 'Options' in properties? properties.Options : null
					s += lbl+': '
					options.each(option => s += '<input type="'+input_type+'" name="'+id+'" value="'+option.Value+'"'+styles+'><label>'+option.Text+'</label>')
					s += "	"
					break
				}
				case 'select': {
					let options = 'Options' in properties? properties.Options : null
					if (options == 'Get Technician,Analyst'){
						let t = ajax({ q: '29', users: 'Technician,Analyst' }).map(item => '<option>'+item.NAME+'</option>').join('')
						s += '<label>'+lbl+': </label><select id="'+id+'"'+styles+'>'+t+'</select>'
					}
					else{
						let t = options?'<option>'+options.join('</option><option>')+'</option>':''
						s += '<label>'+lbl+': </label><select id="'+id+'"'+styles+(is_editable?'':' disabled')+'>'+t+'</select>'
					}
					break
				}
				case 'number': {
					let min = 'Min' in properties? properties.Min : ''
					let max = 'Max' in properties? properties.Max : ''
					let step = 'Step' in properties? properties.Step : '0.01'
					s += '<label>'+lbl+': </label><input type="'+input_type+'" min="'+min+'" max="'+max+'" step="'+step+'"'+styles+'>'
					break
				}
				case 'date': {
					s += '<label>'+lbl+': </label><input type="'+input_type+'" id="'+id+'"'+styles+'>'
					break
				}
				case 'label': {
					s += '<label id="'+id+'"'+styles+'></label>'
					break
				}
				case 'hidden': {
					s += '<input type="hidden" id="'+id+'">'
					break
				}
				default: {
					s += '<label>'+lbl+': </label><span id="'+id+'"'+styles+'></span>'
				}
			}
			s += '</span>	'

			if ($(id) && $(id).getParent().get('tag')=='td'){
				if ($('lbl_'+id)) $('lbl_'+id).set('text', lbl)
			}
			else if ($('container_'+id)){
				$('container_'+id).set('html', s)
			}
			else{
				html += s
			}
		}
		$('batch').set('html', html)

		for (let field in _BATCH){
			let properties = _BATCH[field]
			if (Conflicting(properties)||Is('Hidden', properties)||!Is('Editable', properties))
				continue
			let obj = $(MakeID(field))
			if (obj){
				// save field name in elements
				obj.set('field', field)
				addValidator(obj, field)
			}
		}
	}

	function GetInputType(properties){
		let input_type = 'Type' in properties? properties.Type : ''
		if (typeOf(input_type) == 'object'){
			for (let item in input_type){
				let when = input_type[item]
				while ('When' in when) when = when.When
				if (ConditionMatches(when, null)){
					input_type = item
					break
				}
			}
		}
		return input_type
	}

	function addValidator(input, field){
		input.addEvent('blur', function(e){
			let field = this.get('field')
			let required = IsRequired(field, null, null)
			if (required.isTrue && !this.value){
				Error(required.message)
				this.focus()
			}

			let properties = _BATCH[field]
			if (this.value && 'Range' in properties){
				let is_editable = Is('Editable', properties)
				let value_type = GetInputType(properties)||(is_editable? 'text' : '')
				let result = ValidateField(null, field, this.value, properties.Range, value_type)
				if (!result.isValid){
					if (result.messageType == 'warning'){
						Warning(result.message)
					}
					else{
						Error(result.message)
						this.focus()
					}
				}
			}
		})
	}

	function GetDateValue(r, setting_value){
		let x

		if (setting_value == 'Next Day')
			return GetNextDay()

		if (setting_value.in('batch date', 'batch date start'))
			return BatchDate()

		if (setting_value.in('batch date end'))
			return new Date(BatchDate().setHours(23, 59, 59, 999))

		if (_GRID.columnExists(setting_value))
			return new Date((_GRID.getCell(r, setting_value)||'').replace('T', ' '))

		x = setting_value.match(/(next|previous)\s+(year|month|day|hour|minute|second)\s+of\s+\[(.+)\]/i)
		if (x)
			return CalculateDate(GetDateValue(r, x[3]), x[1].toLowerCase()=='previous'? -1 : 1, x[2])

		x = setting_value.match(/(\d+)\s+(year|month|day|hour|minute|second)s?\s+(before|after)\s+\[(.+)\]/i)
		if (x)
			return CalculateDate(GetDateValue(r, x[4]), (x[3].toLowerCase()=='before'? -1 : 1)*(+x[1]), x[2])

		x = setting_value.match(/^\[(.+)\]$/i)
		if (x)
			return GetDateValue(r, x[1])

		return new Date(setting_value)
	}

	function MatrixType(matrix){
		let type = ['Aqueous','Soil'].find(type => matrix.startsWith(type))
		return type? 'All '+type : matrix
	}

	function ValidateField(r, column, v, range_settings, type, blk_value){
		if ('ByMatrix' in range_settings){
			let rangeByMatrix = range_settings['ByMatrix']
			let matrix = [MatrixType(_matrix), _matrix, 'Others'].find(matrix => matrix in rangeByMatrix)
			if (matrix)
				return ValidateField(r, column, v, rangeByMatrix[matrix], type, blk_value)
		}
		else if ('ByGroup' in range_settings){
			let rangeByGroup = range_settings['ByGroup']
			let group = [_group, 'Others'].find(group => group in rangeByGroup)
			if (group)
				return ValidateField(r, column, v, rangeByGroup[group], type, blk_value)
		}
		else{
			let warningOnly = 'WarningOnly' in range_settings && range_settings['WarningOnly']
			if (typeof type === 'string' && type.contains('date')){
				let dt = new Date((v||'').replace('T', ' '))
				if (dt == 'Invalid Date'){
					return {
						isValid: false,
						messageType: 'error',
						message: '\''+v+'\' is an invalid date.'
					}
				}
				if ('MinValue' in range_settings){
					let min_value = GetDateValue(r, range_settings['MinValue'])
					if (dt < min_value){
						if (warningOnly){
							return {
								isValid: false,
								messageType: 'warning',
								message: column+' normally is at least '+min_value
							}
						}
						else{
							return {
								isValid: false,
								messageType: 'error',
								message: column+' must be at least '+min_value
							}
						}
					}
				}
				if ('MaxValue' in range_settings){
					let max_value = GetDateValue(r, range_settings['MaxValue'])
					if (dt > max_value){
						if (warningOnly){
							return {
								isValid: false,
								messageType: 'warning',
								message: column+' normally is not greater than '+max_value
							}
						}
						else{
							return {
								isValid: false,
								messageType: 'error',
								message: column+' must not be greater than '+max_value
							}
						}
					}
				}
			}
			else{
				if ('MinValue' in range_settings){
					let min_value = range_settings['MinValue']

					if (min_value == 'Next Day'){
						min_value = GetNextDay()
						v = new Date(v)
						v.setTime(v.getTime() + v.getTimezoneOffset()*60*1000)
					}

					if (v < min_value){
						if (warningOnly){
							return {
								isValid: false,
								messageType: 'warning',
								message: column+' normally is at least '+min_value
							}
						}
						else{
							return {
								isValid: false,
								messageType: 'error',
								message: column+' must be at least '+min_value
							}
						}
					}
				}
				if ('MaxValue' in range_settings){
					let max_value = range_settings['MaxValue']

					if (max_value == 'Next Day'){
						max_value = GetNextDay()
						v = new Date(v)
						v.setTime(v.getTime() + v.getTimezoneOffset()*60*1000)
					}

					if (v > max_value){
						if (warningOnly){
							return {
								isValid: false,
								messageType: 'warning',
								message: column+' normally is not greater than '+max_value
							}
						}
						else{
							return {
								isValid: false,
								messageType: 'error',
								message: column+' must not be greater than '+max_value
							}
						}
					}
				}
			}
			if (typeof blk_value !== 'undefined'){
				if ('MinDiff' in range_settings){
					let min_value = +blk_value + range_settings['MinDiff']
					if (v < min_value){
						if (warningOnly){
							return {
								isValid: false,
								messageType: 'warning',
								message: column+' normally is at least '+min_value
							}
						}
						else{
							return {
								isValid: false,
								messageType: 'error',
								message: column+' must be at least '+min_value
							}
						}
					}
				}
				if ('MaxDiff' in range_settings){
					let max_value = +blk_value + range_settings['MaxDiff']
					if (v > max_value){
						if (warningOnly){
							return {
								isValid: false,
								messageType: 'warning',
								message: column+' normally is not bigger than '+max_value
							}
						}
						else{
							return {
								isValid: false,
								messageType: 'error',
								message: column+' must not be bigger than '+max_value
							}
						}
					}
				}
			}
		}
		return {
			isValid: true
		}
	}

	function EnableChangeMonitor(){
		// Enable [Save] button if any batch field value is changed
		$$('.contentScreen input[type=text][id], .contentScreen textarea')
			.addEvent('input', EnableSave)
		$$('.contentScreen input[type=checkbox][id]')
			.addEvent('click', EnableSave)
		$$('.contentScreen select[id]')
			.addEvent('change', EnableSave)
	//	$$('.contentScreen input, .contentScreen textarea').addEvent('input', EnableSave)
	}

	function ClearHeader(){
		GetDefaultValues()
		CreateBatchFields()
		SetColumnRowTitles()
	}

	function InitRows(){
		_ROWS = _rTitle.map(item => {
			let row = new Array(_cTitle.length)
			isNumber(item)? row[1] = item : row[0] = item
			return row
		})
	}

	function InitButtons(){
		$$('#get_samples,#print_labels,#print_report,#sample_status_report,#shipping_report,#done,#next_step').set('disabled', false)
		$$('#save_batch').disabled = true
		if ($('tumble_blk')){
			IsTumbleBlank()? $('tumble_blk').show() : $('tumble_blk').hide()
		}
		let empty_spots = _GRID.countInColumn('Sample ID', '', true)
		if ($('get_samples'))
			$('get_samples').disabled = _batch_is_closed || !empty_spots

		if ($('MDL_Study'))
			$('MDL_Study').addEvent('click', MDL_Study_changed)
				.fireEvent('click')
		if ($('No_MS_MSD_DUP'))
			$('No_MS_MSD_DUP').addEvent('click', No_MS_MSD_DUP_changed)
				.fireEvent('click')
		if ($('No_MS_MSD_DUP_2'))
			$('No_MS_MSD_DUP_2').addEvent('click', No_MS_MSD_DUP_2_changed)
				.fireEvent('click')
		if ($('MSD'))
			$('MSD').addEvent('click', MSD_changed)
				.fireEvent('click')
		if ($('Reason'))
			$('Reason').addEvent('input', EnableSave)
		if ($('Reason_2'))
			$('Reason_2').addEvent('input', EnableSave)

		if ($('QC_Fail')){
			$('QC_Fail').addEvent('click', QC_Fail_changed)
				.fireEvent('click')
		}
		if ($('QC_Fail_2')){
			$('QC_Fail_2').addEvent('click', QC_Fail_2_changed)
				.fireEvent('click')
		}

		$$('#Fail_By, #Fail_By_2').addEvent('input', EnableSave)
	}

	function EnableSave(){
		$$('#save_batch').set('disabled', false)
	}

	function MDL_Study_changed(e){
		if (e && $('MDL_Study'))
			_MDL_Study = $('MDL_Study').checked? 1 : 0
		if (_MDL_Study){
			_NO_MS_MSD_DUP = 0
			if ($('No_MS_MSD_DUP')){
				$('No_MS_MSD_DUP').set('checked', false).fireEvent('click')
				$('No_MS_MSD_DUP').getParent().hide()
			}
			_NO_MS_MSD_DUP_2 = 0
			if ($('No_MS_MSD_DUP_2')){
				$('No_MS_MSD_DUP_2').set('checked', false).fireEvent('click')
				$('No_MS_MSD_DUP_2').getParent().hide()
			}
		}
		else{
			if ($('No_MS_MSD_DUP')){
				$('No_MS_MSD_DUP').getParent().show()
			}
			if ($('No_MS_MSD_DUP_2')){
				$('No_MS_MSD_DUP_2').getParent().show()
			}
		}
		if ($('MSD'))
			$('MSD').getParent().setStyle('display', _MDL_Study||_NO_MS_MSD_DUP||_NO_MS_MSD_DUP_2? 'none':'inline')
		if (e) EnableSave()

		ShowHideColumnsAndRows()
	}

	function No_MS_MSD_DUP_changed(e){
		if (e && $('No_MS_MSD_DUP'))
			_NO_MS_MSD_DUP = $('No_MS_MSD_DUP').checked? 1 : 0
		if (_NO_MS_MSD_DUP){
			_MSD = 0
			if ($('Reason'))
				$('Reason').getParent().show()
			if ($('MSD'))
				$('MSD').set('checked', false).fireEvent('click')
		}
		else{
			if ($('Reason')){
				$('Reason').set('value', '')
				$('Reason').getParent().hide()
			}
		}
		if ($('MSD'))
			$('MSD').getParent().setStyle('display', _MDL_Study||_NO_MS_MSD_DUP? 'none':'inline')
		if (_NO_MS_MSD_DUP||_matrix.in(LIQUID_SOLID_WIPES)||_group.in('NJ-EPH','NJ-EPH-DRO','NJ-EPH-C40')){
			GridSetCell('LCSD', 'Sample ID', 'LCSD'+_matrix_initial+_batch_id)
		}
		else{
			GridSetCell('LCSD', 'Sample ID', null)
		}
		ShowHideColumnsAndRows()
		if (e) EnableSave()
	}

	function No_MS_MSD_DUP_2_changed(e){
		if (e && $('No_MS_MSD_DUP_2'))
			_NO_MS_MSD_DUP_2 = $('No_MS_MSD_DUP_2').checked? 1 : 0
		if (_NO_MS_MSD_DUP_2){
			_MSD = 0
			if ($('Reason_2'))
				$('Reason_2').getParent().show()
			if ($('MSD'))
				$('MSD').set('checked', false).fireEvent('click')
		}
		else{
			if ($('Reason_2')){
				$('Reason_2').set('value', '')
				$('Reason_2').getParent().hide()
			}
		}
		if ($('MSD'))
			$('MSD').getParent().setStyle('display', _MDL_Study||_NO_MS_MSD_DUP_2? 'none':'inline')
		if (_NO_MS_MSD_DUP_2||_matrix.in(LIQUID_SOLID_WIPES)||_group.in('NJ-EPH','NJ-EPH-DRO','NJ-EPH-C40')){
			GridSetCell('LCSD2', 'Sample ID', 'LCSD'+_matrix_initial+_batch_id)
		}
		else{
			GridSetCell('LCSD2', 'Sample ID', null)
		}
		ShowHideColumnsAndRows()
		if (e) EnableSave()
	}

	function MSD_changed(e){
		if ('checked' in this)
			_MSD = this.checked? 1 : 0
		let msd_exists = _GRID.isRowDisplayed('MSD')? 1 : 0
		if (_MSD != msd_exists){
			let ms = GridGetCell('MS', 'Sample ID')
			if (ms)
				GridSetCell('MSD', 'Sample ID', _MSD? ms+'D' : null)
		}
	}

	function QC_Fail_changed(e){
		if ('checked' in this)
			_QC_Fail = this.checked? 1 : 0
		if (_QC_Fail){
			$('Fail_Reason').getParent().show()
			$('Fail_By').getParent().show()
			if ($('Fail_On')){
				if (!$('Fail_On').value)
					$('Fail_On').set('value', new Date().format('mm/dd/yyyy HH:MM'))
				$('Fail_On').getParent().show()
			}
		}
		else{
			$('Fail_Reason').set('value', '')
			$('Fail_Reason').getParent().hide()
			$('Fail_By').set('value', '')
			$('Fail_By').getParent().hide()
			if ($('Fail_On')){
				$('Fail_On').set('value', '')
				$('Fail_On').getParent().hide()
			}
		}
		if (e) EnableSave()
	}

	function QC_Fail_2_changed(e){
		if ('checked' in this)
			_QC_Fail_2 = this.checked? 1 : 0
		if (_QC_Fail_2){
			$('Fail_Reason_2').getParent().show()
			$('Fail_By_2').getParent().show()
			if ($('Fail_On_2')){
				if (!$('Fail_On_2').value)
					$('Fail_On_2').set('value', new Date().format('mm/dd/yyyy HH:MM'))
				$('Fail_On_2').getParent().show()
			}
		}
		else{
			$('Fail_Reason_2').set('value', '')
			$('Fail_Reason_2').getParent().hide()
			$('Fail_By_2').set('value', '')
			$('Fail_By_2').getParent().hide()
			if ($('Fail_On_2')){
				$('Fail_On_2').set('value', '')
				$('Fail_On_2').getParent().hide()
			}
		}
		if (e) EnableSave()
	}

	function GetNextTask(data){
		let steps = _STEPS[_type]
		let task
		for(task in steps){
			if (Conflicting(steps[task])) continue
			let done = steps[task]['Done']
			let field = done.Status+' On'
			if (!data[field])
				return task
		}
		return task
	}

	function LoadBatch(batch_id){
		batch_data = ajax({ q: 1, nBatch_ID: batch_id })
		_bid = batch_data['ABID']
		_batch_id = batch_data['Batch ID']
		_type = batch_data['Type']
		_matrix = batch_data['Matrix']
		_matrix_initial = _matrix.substr(0, 1)
		_group = batch_data['Group']
		_MDL_Study = +batch_data['MDL Study']
		_NO_MS_MSD_DUP = +batch_data['No MS/MSD/DUP']
		_NO_MS_MSD_DUP_2 = +batch_data['No MS/MSD/DUP 2']
		_MSD = 1 - batch_data['MSD']
		_QC_Fail = +batch_data['QC Fail']
		_QC_Fail_2 = +batch_data['QC Fail 2']
		_QC_Approval = +batch_data['QC Approval']
		_QC_Approval_2 = +batch_data['QC Approval 2']
		_batch_is_closed = +batch_data['Is Closed']
		if (!(_task in _STEPS[_type])||Conflicting(_STEPS[_type][_task])){
			SetTask(GetNextTask(batch_data))
		}
		['Save','Done'].each(x => {
			if (x in _TASK)
				_status[x+' On'] = batch_data[_TASK[x].Status+' On']
		})

		_blk_test = ajax({ q: 'Get Blank Test', batch_id: _batch_id })||Default_Test[_group]||_group
		ShowReagent()
		ClearHeader()

		for (let key in batch_data){
			let id = MakeID(key)
			let obj = $(id)
			if (!obj) continue

			let value = batch_data[key]||''
			switch(key){
				case 'MDL Study':
				case 'No MS/MSD/DUP':
				case 'No MS/MSD/DUP 2':
				case 'QC Fail':
				case 'QC Fail 2':
					if (obj.get('tag') == 'label')
						obj.set('text', +batch_data[key]? GetLabelForDisplay(key, _BATCH[key]) : '')
					else
						SetElementValue(obj, +batch_data[key])
					break
				case 'MSD':
					if (obj.get('tag') == 'label')
						obj.set('text', _MSD?'W/ MSD':'W/O MSD')
					else
						SetElementValue(obj, _MSD)
					break
				default:
					SetElementValue(obj, value)
			}
		}
		// Set min expiration date, should be greater or equal than tomorrow
		$$('#div_surrogate input[type=date]').set('min', GetNextDay().format('yyyy-mm-dd', true))

		InitRows()

		ajax({ q: 2,
			nBatch_ID: batch_id,
			fields: JSON.stringify(_cTitle)
		})['rows'].each(row => {
			_ROWS.some(_row => {
				if (!_row[2] && (_row[0]&&_row[0]==row[0]||_row[1]&&_row[1]==row[1])){
					for (let i = 2; i < row.length; i++)
						_row[i] = row[i]
					return true
				}
			})
		})
		ShowTable()

		// Hide some rows, bottom to top
		if (_batch_is_closed){
			for (let r = _rTitle.length; r >= 0; r--){
				if (!GridGetCell(r, 'Type') && !GridGetCell(r, 'Sample ID'))
					_GRID.hideRow(r)
				else
					break
			}
		}

		// Something wrong if counter still greater than 0
	//	if (counter > 0){
	//		alert('QC used over 20 times!!!')
	//	}

		// Show Footnotes, Added 6/12/2015
		let task = _STEPS[_type][_task]
		if ('Footnotes' in task){
			let footnotes = ''
			let obj = task['Footnotes']
			if (typeOf(obj) == 'array'){
				obj.each(note => {
					if (typeOf(note) == 'object'){
						for (let item in note){
							if (!Conflicting(note[item]))
								footnotes += '<br>' + item
						}
					}
					else if (typeOf(note) == 'string'){
						footnotes += '<br>' + note
					}
				})
			}
			else if (typeOf(obj) == 'string'){
				footnotes = obj
			}
			$('footnotes').set('html', footnotes)
		}

		// Make the batch undeletable if any sample is completed
	//	let hasCompleted = _GRID.countInColumn('Completed', 'Yes') > 0
	//	$$('#delete_batch').set('disabled', hasCompleted)
		$$('#print_labels,#print_report,#sample_status_report,#shipping_report').set('disabled', false)
	}

	function BatchDate(){
		let yy = _batch_id.substr(0, 2)
		let mm = _batch_id.substr(2, 2)
		let dd = _batch_id.substr(4, 2)
		return new Date(mm+'/'+dd+'/'+yy)
	}

	function GetNextDay(){
		return CalculateDate(BatchDate(), +1, 'day')
	}

	function CalculateDate(dt, diff, unit){
		let new_dt = new Date(dt.getTime())
		switch(unit.toLowerCase()){
			case 'year':
				new_dt.setYear(new_dt.getYear()+diff)
				break
			case 'month':
				new_dt.setMonth(new_dt.getMonth()+diff)
				break
			case 'day':
				new_dt.setDate(new_dt.getDate()+diff)
				break
			case 'hour':
				new_dt.setHours(new_dt.getHours()+diff)
				break
			case 'minute':
				new_dt.setMinutes(new_dt.getMinutes()+diff)
				break
			case 'second':
				new_dt.setSeconds(new_dt.getSeconds()+diff)
				break
		}
		return new_dt
	}

	function getTID(test, is_MDL_Study){
//		is_MDL_Study = is_MDL_Study||false
//
//		if (!test)
//			test = GetDefaultTest()
//
//		if (test.contains('PCB') && test.contains('Pesticides')){
//			let lp = test.contains('TCLP')? 'TCLP ' : test.contains('SPLP')? 'SPLP ' : ''
//			test = lp+'PCB/Pesticides'
//		}
//
//		let item = _TEST_LIST[is_MDL_Study? _matrix : null][_group]
//		let tests = test.split(';')
//
//		for (let test of tests)
//			for (let tid in item)
//				if (item[tid].NAME == test)
//					return tid
//
//		for (let test of tests)
//			for (let tid in item)
//				if (item[tid].NAME.contains(test) || test.contains(item[tid]))
//					return tid
//
//		return null

		if (typeof test == 'undefined')
			test = Default_Test[_group]||_group

		let test_list_by_matrix = Object.assign({}, _TEST_LIST[_matrix], _TEST_LIST[null])
		let item = Object.assign({}, test_list_by_matrix[_group], test_list_by_matrix[null])

		for (let tid in item)
			if (item[tid] == (EXT_TYPE_to_Test[test]||test))
				return tid

		let tests = test.split(';')

		for (let test of tests)
			for (let tid in item)
				if (item[tid] == test)
					return tid

		for (let test of tests)
			for (let tid in item)
				if (item[tid].contains(test) || test.contains(item[tid]))
					return tid

		return null
	}

	function SampleDefaults(){
		GridSetCell('BLK', 'Sample ID', 'BLK'+_matrix_initial+_batch_id, 0, 1, 0)
		ShowHideColumnsAndRows()
	}

	function ShowHideRows(){
		if (!_rTitle || !_rTitle.length) return
		for (let r = 1; r <= _rTitle.length; r++){
			if (_DEBUG_){
				_GRID.showRow(r)
				continue
			}

			let type = GridGetCell(r, 'Type')
			if (type && type != 'BLK'){
				if (!GridGetCell(r, 'Sample ID') || _MDL_Study){
					_GRID.hideRow(r)
					continue
				}

				let show_row = true
				if (type.in('LCS','LCS2')){
					show_row = true
				}
				else if (type == 'LCSD'){
					show_row = _NO_MS_MSD_DUP||_matrix.in(LIQUID_SOLID_WIPES)||_group.in('NJ-EPH','NJ-EPH-DRO','NJ-EPH-C40')
				}
				else if (type == 'LCSD2'){
					show_row = _NO_MS_MSD_DUP_2||_matrix.in(LIQUID_SOLID_WIPES)||_group.in('NJ-EPH','NJ-EPH-DRO','NJ-EPH-C40')
				}
				else if (type.in('MS','MSD','DUP')){
					show_row = !_NO_MS_MSD_DUP
				}
				else if (type.in('MS2','MSD2','DUP2')){
					show_row = !_NO_MS_MSD_DUP_2
				}
				show_row? _GRID.showRow(r) : _GRID.hideRow(r)
			}
		}
	}

	function SetQCwithTest(test){
		if (_group.in('PCB/Pest','PCB/Pest by 608','PCB/Pest by 608.3')){
			let lp = test.contains('TCLP')? 'TCLP ' : test.contains('SPLP')? 'SPLP ' : ''
			if (test.contains('PCB')){
				if (!_MDL_Study){
					GridSetCell('LCS', 'Sample ID', 'LCS'+_matrix_initial+_batch_id)
					GridSetCell('LCS', 'Test', getTID(lp+'PCB'))
				}
				else{
					GridSetCell('LCS', 'Sample ID', null)
				}
				if (_NO_MS_MSD_DUP||_matrix.in(LIQUID_SOLID_WIPES)||_group.in('NJ-EPH','NJ-EPH-DRO','NJ-EPH-C40')){
					GridSetCell('LCSD', 'Sample ID', 'LCSD'+_matrix_initial+_batch_id)
					GridSetCell('LCSD', 'Test', getTID(lp+'PCB'))
				}
				else{
					GridSetCell('LCSD', 'Sample ID', null)
				}
				GridSetCell('MS', 'Test', getTID(lp+'PCB'))
				GridSetCell('MSD', 'Test', getTID(lp+'PCB'))
			}
			else{
				GridSetCell('LCS', 'Sample ID', null)
				GridSetCell('LCSD', 'Sample ID', null)
				GridSetCell('MS', 'Sample ID', null)
				GridSetCell('MSD', 'Sample ID', null)
			}
			if (test.contains('Pesticides')){
				if (!_MDL_Study){
					GridSetCell('LCS2', 'Sample ID', 'LCS'+_matrix_initial+_batch_id)
					GridSetCell('LCS2', 'Test', getTID(lp+'Pesticides'))
				}
				else{
					GridSetCell('LCS2', 'Sample ID', null)
				}
				if (_NO_MS_MSD_DUP_2||_matrix.in(LIQUID_SOLID_WIPES)||_group.in('NJ-EPH','NJ-EPH-DRO','NJ-EPH-C40')){
					GridSetCell('LCSD2', 'Sample ID', 'LCSD'+_matrix_initial+_batch_id)
					GridSetCell('LCSD2', 'Test', getTID(lp+'Pesticides'))
				}
				else{
					GridSetCell('LCSD2', 'Sample ID', null)
				}
				GridSetCell('MS2', 'Test', getTID(lp+'Pesticides'))
				GridSetCell('MSD2', 'Test', getTID(lp+'Pesticides'))
			}
			else{
				GridSetCell('LCS2', 'Sample ID', null)
				GridSetCell('LCSD2', 'Sample ID', null)
				GridSetCell('MS2', 'Sample ID', null)
				GridSetCell('MSD2', 'Sample ID', null)
			}
		}
		else{
			if (!_MDL_Study){
				GridSetCell('LCS', 'Sample ID', 'LCS'+_matrix_initial+_batch_id, false, true, false)
				GridSetCell('LCS', 'Test', getTID(test))
			}
			else{
				GridSetCell('LCS', 'Sample ID', null)
			}
			if (_NO_MS_MSD_DUP||_matrix.in(LIQUID_SOLID_WIPES)||_group.in('NJ-EPH','NJ-EPH-DRO','NJ-EPH-C40')){
				GridSetCell('LCSD', 'Sample ID', 'LCSD'+_matrix_initial+_batch_id)
				GridSetCell('LCSD', 'Test', getTID(test))
			}
			else{
				GridSetCell('LCSD', 'Sample ID', null)
			}
		}
	}

	function NewQC(n, sample_id, comments){
		let ms = n==1?'MS':'MS2'
		let msd = n==1?'MSD':'MSD2'
		let newQC
		if (sample_id){
			newQC = sample_id+'MS'
			if (!comments.contains('MSD OF #')){
				let qc_row = FindQCInThisBatch(ms, newQC)
				if (qc_row < 0){
					GridSetCell(ms, 'Sample ID', newQC)
					GridSetCell(ms, 'Comments', comments)
				}
			}
			if (_group.in('NJ-EPH','NJ-EPH-DRO','NJ-EPH-C40','Gas')){
				if (comments.contains('MSD OF #')){
					if ($('MSD'))
						$('MSD').set('checked', true).fireEvent('click')
				}
				let dup_id = sample_id
				if (comments.match(/MSD? OF #/)){
					let no = comments.replace(/^.*MSD? OF #(\d+).*$/, '$1')
					let parts = sample_id.split('-')
					dup_id = parts[0]+'-'+no.padLeft(3, '0')
				}
				let dup_row = FindQCInThisBatch('DUP', dup_id+'DUP')
				if (dup_row < 0){	// not exist
					GridSetCell('DUP', 'Sample ID', dup_id+'DUP')
					GridSetCell('DUP', 'Comments', null)
				}
			}
			if (!_MDL_Study && (Conflicting(_BATCH['MSD'])||_MSD)){
				if (!comments.contains('MS OF #')){
					let qc_row = FindQCInThisBatch(msd, sample_id+'MSD')
					if (qc_row < 0){
						GridSetCell(msd, 'Sample ID', sample_id+'MSD')
						GridSetCell(msd, 'Comments', comments)
					}
				}
			}
		}
		else{
			GridSetCell(ms, 'Sample ID', null)
			GridSetCell(msd, 'Sample ID', null)
			GridSetCell('DUP', 'Sample ID', null)
		}
		if (!comments.contains('MSD OF #'))
			QC_Changed(n, newQC)
		return newQC
	}

	function SetQC(sample_id, test, comments, on_completed){
		if (test.contains('PCB') || test.contains('Pesticides')){
			if (test.contains('PCB/Pesticides')){
				let ms = _GRID.tbody.getElements('td:first-child:match("^MS2?$"):not(.disabled)')
				let all_empty = true, all_filled = true, ms_empty = null
				for (let i = 0; i < ms.length; i++){
					if (GridGetCell(ms[i].getParent().rowIndex, 'Sample ID'))
						all_empty = false
					else{
						ms_empty = i
						all_filled = false
					}
				}
				let client_specific = comments.match(/MSD? OF #/)
				if (all_empty || all_filled || client_specific){
					let msg = (client_specific? '<br>This is a client specific MS/MSD sample.' : '') + '<br>For PCB or Pesticides?'
					_SexyBox.confirm(msg, {
						textBoxBtnOk: 'PCB',
						textBoxBtnCancel: 'Pesticides',
						onComplete: pcb => {
							NewQC(pcb?1:2, sample_id, comments)
							if (on_completed) on_completed()
						}
					})
				}
				else{
					NewQC(ms_empty+1, sample_id, comments)
					if (on_completed) on_completed()
				}
			}
			else{
				if (test.contains('PCB')){
					NewQC(1, sample_id, comments)
					if (on_completed) on_completed()
				}
				if (test.contains('Pesticides')){
					NewQC(2, sample_id, comments)
					if (on_completed) on_completed()
				}
			}
		}
		else{
			NewQC(1, sample_id, comments)
			if (on_completed) on_completed()
		}
	}

	function AddTumbleBlank(r, test){
		let comments = GridGetCell(r, 'Comments')||''
		;(ajax(test == '1312/8011'? { q: '27B',
			aliquot: GridGetCell(r, 'ALIQUOT'),
		} : { q: 27,
			aliquot: GridGetCell(r, 'ALIQUOT'),
			matrix: LIMS_Matrix[_matrix]||_matrix,
			group: _group,
			test: (GridGetText('LCS', 'Test')||GridGetText('LCS2', 'Test'))?[GridGetText('LCS', 'Test'), GridGetText('LCS2', 'Test')] : [_blk_test],
		})||[]).each((fields, i) => {
			let lp = fields['Test Name'].match(/TCLP|SPLP/)[0]
			let tumble_blank = fields['Leach Batch']||lp+fields['Leach On']
			let LP = _GRID.searchInColumn('Sample ID', '^'+tumble_blank+'$')
			if (!LP.length){
				LP = _GRID.searchInColumn('Type', lp)
				for (let k = 0; k < LP.length; k++){
					let lp_r = LP[k].getParent().rowIndex
					if (!GridGetCell(lp_r, 'Sample ID')){
						GridSetCell(lp_r, 'Sample ID', tumble_blank)
						break
					}
				}
			}
			if (!comments.contains(tumble_blank))
				comments += (comments?'; ':'')+tumble_blank
		})
		GridSetCell(r, 'Comments', comments)
	}

	function PickQCSample(r){
		let sample_id = GridGetCell(r, 'Sample ID')
		let test = GridGetText(r, 'Test')
		let comments = GridGetCell(r, 'Comments')||''
		SetQC(sample_id, test, comments)
	}

	function CellKeydown(e){
		let shift = e.shift?'shift-':''
		let ctrl = e.control?'ctrl-':''
		let key = shift+ctrl+e.key

		if (key=='s' || key=="shift-'"){
			sameAsAbove(e)
			return true
		}
		else if (key=='a'){
			aboveAddOne(e)
			return true
		}
		else if (key=='f7'||key=='q'){
			let r = e.target.getParent().rowIndex
			PickQCSample(r)
			return true
		}
		else if (key=='f8'||key=='d'){
			AddDUP(e)
			return true
		}
		else if (key=='ctrl-down'){
			CopyDown(e)
			return true
		}
		return false

		function AddDUP(e){
			let current = e.target
			let data = current.get('data')
			if (data)
				GridSetCell('DUP', 'Sample ID', data+'DUP')
		}

		function CopyDown(e){
			let current = e.target
			let field = _GRID.getColumnHeader(current)
			let properties = _SAMPLE[field]
			if (Is('DisableCopyDown', properties)){
				Warning('Copy-down is not allowed for \''+field+'\'')
				return
			}

			let data = current.get('data')
			if (!data){
				let upRow = current.getParent().getPrevious(':visible:enabled')
				if (upRow)
					current = upRow.getElement('td:nth-child(' + (current.cellIndex+1) + ')')
				data = current.get('data')
			}
			if (data){
				let col = current.cellIndex + 1
				let row = current.getParent().rowIndex
				for (let r = row+1; r <= _rTitle.length; r++){
					if (GridGetCell(r, 'Sample ID'))
						GridSetCell(r, col, data, 0, 1, 1, /*validate*/true)
				}
			}
		}

		function aboveAddOne(e){
			let current = e.target
			if (!current.get('data')){
				let upRow = current.getParent().getPrevious(':visible:enabled')
				if (upRow){
					let up = upRow.getElement('td:nth-child(' + (current.cellIndex+1) + ')')
					if (up){
						let data = up.get('data')
						let last_2_digit = data.substr(-2)
						if (data && isdigit(last_2_digit))
							current.setValue(data.substr(0, data.length-2)+('00'+(+last_2_digit+1)).substr(-2))
					}
				}
			}
		}

		function sameAsAbove(e){
			let current = e.target
			if (!current.get('data')){
				let upRow = current.getParent().getPrevious(':visible:enabled')
				if (upRow){
					let up = upRow.getElement('td:nth-child(' + (current.cellIndex+1) + ')')
					if (up){
						let data = up.get('data')
						if (data)
							current.setValue(data)
					}
				}
			}
		}
	}

	function ShowTable(blank_test){
		$('tabl').set('html', '')
		_GRID = new AccessibleGrid({
			headers: _gridHeaders,
			rows: _ROWS,
			formatter: Formatter,
//			isEditable: IsEditable,
			getInputElement: GetInputElement,
			validator: Validator,
			cellChanging: CellChanging,
			cellChanged: CellChanged,
			cellKeydown: CellKeydown,
			editingmodeChanged: CellEditModeChanged,
			nextField: NextField,
			autoSelect: true,
//			sortable: true,
//			zebra: true
		}).inject($('tabl'))

		InitButtons()
	//	ShowHideColumnsAndRows()

		if (blank_test)
			GridSetCell('BLK', 'Test', blank_test)

		SampleDefaults()
		SetQCwithTest(_blk_test)

		EnableChangeMonitor()
	}

	function NextField(td){
		let r = td.getParent().rowIndex
		let header = td.getColumnHeader()
		let column = GetFieldNameFromColumnHeader(header)
		let properties = _SAMPLE[column]
		let next_column = 'Next' in properties? GetPropValue(properties.Next, '{Next Row}', r) : '{Next Row}'
		switch(next_column){
			case '{Next Row}': {	// Goto next row in the same column
				let nextRow = td.getParent().getNext(':visible:enabled')
				if (nextRow)
					return nextRow.getElement('td:nth-child(' + (td.cellIndex+1) + ')')
				break
			}
			case '{Next Column}': {	// Goto next column in the same row
				let next = td.getNext('td:visible:enabled')
				if (next)
					return next
				let nextRow = td.getParent().getNext(':visible:enabled')
				if (nextRow)
					return nextRow.getElement('td:visible:enabled')
				break
			}
			default: {
				let n = _GRID.columnIndex(next_column)
				let next = td.getNext('td:nth-child('+n+'):visible:enabled')
				if (next)
					return next
				let nextRow = td.getParent()
				while(nextRow = nextRow.getNext(':visible:enabled')){
					next = nextRow.getElement('td:nth-child('+n+'):visible:enabled')
					if (next)
						return next
				}
			}
		}
	}

	function CellEditModeChanged(editing){
		if ($('delete_sample'))
			$('delete_sample').disabled = editing
	}

	function ShowHideBatchFields(){
		for (let field in _BATCH){
			let properties = _BATCH[field]
			let display = Conflicting(properties, null, _blk_test)||Is('Hidden', properties, null, _blk_test)?'none':''
			if ($(MakeID(field)+'_container'))
				$(MakeID(field)+'_container').setStyle('display', display)
			else if ($(MakeID(field)))
				$(MakeID(field)).setStyle('display', display)
		}
	}

	function EnableDisableColumns(r){
		let type = GridGetCell(r, 'Type')
		let test = GridGetText(r, 'Test')||_blk_test

		for (let column in _SAMPLE){
			let properties = _SAMPLE[column]
			if (Conflicting(properties, 'BLK', _blk_test, 1) && Conflicting(properties, null, _blk_test, 1))
				continue

		//	let header = GetLabelForDisplay(column, properties)
			let cell = GridCell(r, column)
			if (cell){
				if (CellEditable(r, properties, type, test)){
					// Field is editable
					cell.removeClass('disabled')

					// Set default values for QC samples
					if (type && GridGetCell(r, 'Sample ID')){
						if ('ReferenceTableName' in properties){
							let ref_name = properties['ReferenceTableName']
							let ref_type = Reference_Sample_Type[type]||type
							if (typeof _DEFAULTS[_blk_test] == 'undefined')
								console.log('Blank test \''+_blk_test+'\' is not found in reference table.')
							else{
								let defaults = _DEFAULTS[_blk_test][ref_type]||_DEFAULTS[_blk_test][type]
								if (typeof defaults == 'undefined')
									console.log('Default values for test \''+test+'\' - '+type+' is not found in reference table.')
								else{
									if (properties['PlaceHolder']){
										GridSetCell(r, column, '', 0, 1, 1)
										GridCell(r, column).placeholder = defaults[ref_name]
									}
									else
										GridSetCell(r, column, defaults[ref_name], 0, 1, 1)
								}
							}
						}
						else if ('DefaultValue' in properties){
							GridSetCell(r, column, GetDefaultValue(properties.DefaultValue, type), 0, 1, 1)
						}
					}
				}
				else
					cell.addClass('disabled')
			}
		}
	}

	function ShowHideColumns(){
		if (!_GRID) return

		ShowHideBatchFields()

		for (let r = 1; r <= _rTitle.length; r++){
			EnableDisableColumns(r)
		}

		for (let column in _SAMPLE){
			let properties = _SAMPLE[column]
			let header = GetLabelForDisplay(column, properties)
			_GRID.setColumnWidth(header, properties.Width)
			_GRID.setColumnStyles(header, properties.Styles)
			!_DEBUG_ && (
				Conflicting(properties, 'BLK', null, 1)||
				Is('Hidden', properties, 'BLK', _blk_test, 1)
			)?
				_GRID.hideColumn(header) : _GRID.showColumn(header)
		}

		for (let r = 1; r <= _rTitle.length; r++){
			let type = GridGetCell(r, 'Type')
			let re_extract = (GridGetCell(r, 'Comments')||'').contains('RE-EXTRACT')
			let completed = GridGetCell(r, 'Completed') == 'Yes'
			let no_sample_id = !GridGetCell(r, 'Sample ID')
			if (!_DEBUG_ && (IsQC(r) || completed || _batch_is_closed && no_sample_id)){
				if (completed && !re_extract || _batch_is_closed && no_sample_id){
					for (let j = 0; j < _cTitle.length; j++)
						GridCell(r, _cTitle[j]).addClass('disabled')
				}
				else{
					GridCell(r, 'Sample ID').addClass('disabled')
					if (['LCS','LCSD','LCS2','LCSD2'].contains(type) && GridCell(r, 'Test'))
						GridCell(r, 'Test').addClass('disabled')
					let qc = GridCell(r, 'QC')
					if (qc && qc.get('data')) qc.addClass('disabled')
					let qc1 = GridCell(r, 'QC 1')
					if (qc1 && qc1.get('data')) qc1.addClass('disabled')
					let qc2 = GridCell(r, 'QC 2')
					if (qc2 && qc2.get('data')) qc2.addClass('disabled')
				}
			}
		}

		!_DEBUG_ && Is('Hidden', _SAMPLE['QC'], null, null, 1)?
			_GRID.hideColumn('QC') : _GRID.showColumn('QC')
		!_DEBUG_ && Is('Hidden', _SAMPLE['QC 1'], null, null, 1)?
			_GRID.hideColumn('QC 1') : _GRID.showColumn('QC 1')
		!_DEBUG_ && Is('Hidden', _SAMPLE['QC 2'], null, null, 1)?
			_GRID.hideColumn('QC 2') : _GRID.showColumn('QC 2')
	}

	function IsOldQC(qc){
		let sample_id = qc.substr(0, 9)
		for (let i = 1; i <= _rTitle.length; i++){
			if (GridGetCell(i, 'Type')) continue
			if (GridGetCell(i, 'Sample ID') == sample_id)
				return false
		}
		return true
	}

	function FindRowInThisBatch(type, v, r){
		for (let i = 1; i <= _rTitle.length; i++){
			if (i == r || type != GridGetCell(i, 'Type')) continue
			if (GridGetCell(i, 'Sample ID') == v)
				return i
		}
		return -1
	}
	function FindSampleInThisBatch(v, r){
		return FindRowInThisBatch(null, v, r)
	}
	function FindQCInThisBatch(type, v){
		return FindRowInThisBatch(type, v)
	}

	function IsQC(r){
		return !!GridGetCell(r, 'Type')
	}
	function IsSample(r){
		return !IsQC(r)
	}

	function FindSampleInAnotherBatch(v, r){
		let parts = v.split('-')
		let job = parts[0]
		let yy = GetYearFromJob(job)
		return ajax({
			q: 23,
			batch_id: _batch_id,
			group: _group,
			/* May 17, 2017 Per Sophia, For PCB/Pest, we do not differenciate Soil and Soil (Sonic) when checking re-extraction */
		//	matrix: _group=='PCB/Pest' && _matrix.includes('Soil')? ['Soil','Soil (Sonic)'] : _matrix,
			matrix: _matrix,
			test: GridGetText(r, 'Test'),
			year: 'E'+yy,
			sid: v
		})
	}

	function SampleExistInAnotherBatchAndNotReextract(v, r){
		const [other_batch_id, other_matrix, other_test] = FindSampleInAnotherBatch(v, r)
		let comments = GridGetCell(r, 'Comments')||''
		let re_extract = 'RE-EXTRACT: ${batch_id}'
		if ('RE-EXTRACT FORMAT' in _MISC_SETTINGS)
			re_extract = _MISC_SETTINGS['RE-EXTRACT FORMAT']
		let regex = new RegExp(re_extract.replace('${batch_id}', '\\d{6}-\\d{2}'), 'g')
		return {
			isTrue: other_batch_id && !comments.match(regex),
			batch_id: other_batch_id,
			matrix: other_matrix,
			test: other_test,
		}
	}

	function Validator(td, old_v, v, isEditing){
		if (typeof isEditing === 'undefined')
			isEditing = true
		let r = td.getParent().rowIndex
		let type = td.getRowHeader()
		let header = td.getColumnHeader()
		let column = GetFieldNameFromColumnHeader(header)
		let properties = _SAMPLE[column]
		let test_name = GridGetText(r, 'Test')
		let required = IsCellRequired(r, column, type, test_name)
		if (required.isTrue && !v){
			Error(required.message)
			return { isValid: false }
		}
		if (v && !type && CellEditable(r, properties, type, test_name) && 'Range' in properties){
			let blk_value = GridGetCell('BLK', column)
			if (blk_value){
				let result = ValidateField(r, column, v, properties.Range, properties.Type, blk_value)
				if (!result.isValid){
					if (result.messageType == 'warning'){
						Warning(result.message)
					}
					else{
					//	_SexyBox.error(result.message, {
					//		onComplete: () => GridCell(r, column).startEdit()
					//	})
						Error(result.message)
						return { isValid: false }
					}
				}
			}
		}

		switch(column){
			case 'Sample ID': {
				if (v && v.length > 3 && !v.contains('-')){
					v = parseInt(v, 36).toString(10).padLeft(9, '0')
					v = v.slice(0, 8) + '-LT' + v.slice(8)
					v = v.slice(0, 5) + '-' + v.slice(5)
				}
				if (v){
					if ('ToUpperCase' in properties && properties.ToUpperCase){
						v = v.toUpperCase()
					}
					let job, sno
					let prevRow = td.getParent().getPrevious(':visible:enabled')
					let prevRowIndex = prevRow? prevRow.rowIndex : null
					let prev_sample = !prevRowIndex||IsQC(prevRowIndex)? null : GridGetCell(prevRowIndex, 'Sample ID')
					if ('PatternValid' in properties){
						let xpattern = GetInputPattern(properties, r)
						xpattern = typeof xpattern === 'string'?
							XRegExp(xpattern, properties['PatternOption']):
							XRegExp(xpattern)

						let match = XRegExp.exec(v, xpattern)
						if (match){
							if (typeof match['job'] != 'undefined' && match['job'].length <= 3 && typeof match['sno'] == 'undefined' && prev_sample){
								let parts = prev_sample.split('-')
								job = parts[0].padLeft(5, '0')
								sno = match['job'].padLeft(3, '0')
							}
							else if (typeof match['job'] == 'undefined' && typeof match['sno'] != 'undefined' && match['sno'].length <= 3 && prev_sample){
								let parts = prev_sample.split('-')
								job = parts[0].padLeft(5, '0')
								sno = match['sno'].padLeft(3, '0')
							}
							else{
								job = match['job'].padLeft(5, '0')
								sno = (match['sno']||'').padLeft(3, '0')
							}
							if (!(+sno)){
								Error('Sample # must be a number >= 1')
								return { isValid: false }
							}
							v = job+'-'+sno
							if (match['jar'])
								GridSetCell(r, 'Jar', match['jar'])
						}
					}

					// Check sample log-in status
				//	if (!SampleIsLogged(v)){
				//		Error('Sample/Test <b>'+v+'</b> is not logged or it is prelogged')
				//		td.getFirst().set('value', '')
				//		return { isValid: false }
				//	}

					// Check Sample Status
					let data = ajax({ q: 'Check Sample Status',
						aliquot: GetAliquotFromSampleID(v),
						matrix: LIMS_Matrix[_matrix]||_matrix,
						group: _group,
						test: (GridGetText('LCS', 'Test')||GridGetText('LCS2', 'Test'))?[GridGetText('LCS', 'Test'), GridGetText('LCS2', 'Test')] : [_blk_test],
					})

					if (!data.length){
					//	Warning('Sample not logged in.')
					}
					else if (data.length > 1){
						console.log('More than one test matched')
					}
					else if (!+data[0]['Leach Done']){
						Error('Cannot add this sample. Leach not done yet.')
						return { isValid: false }
					}

					let U_FIELD_ID = ajax({ q: 'Retrieve U_FIELD_ID',
						aliquot: GetAliquotFromSampleID(v)
					})
					let test_is_MDL_Study = U_FIELD_ID.match(/MDL SPIKE|MDL BLANK|IDOC/i)
					if (_MDL_Study && !test_is_MDL_Study){
					//	if (+job < 20000){
					//		Error('Sample # below 20000 are reserved for clients.')
					//		return { isValid: false }
					//	}
						if (+job == 99999){
							Error('Sample # 99999 is reserved for testing.')
							return { isValid: false }
						}
					}
					if (!(test_is_MDL_Study && _group.in(['PCB','PCB/Pest','PCB/Pest by 608','PCB/Pest by 608.3']))){
						// Check duplicates in this batch
						let same_sample_row = FindSampleInThisBatch(v, r)
						if (same_sample_row >= 0){
							_SexyBox.confirm('Sample ID \''+v+'\' already exists in this batch.  Is it a QC?', {
								textBoxBtnOk: 'Yes',
								textBoxBtnCancel: 'No',
								onComplete: yes => {
									if (yes){
										PickQCSample(same_sample_row)
										if (isEditing)
											td.getFirst().set('value', '')
									}
								}
							})
							return { isValid: false }
						}
					}
				}
				break
			}
			case 'Sample ID<br>(Original Jar)': {
				v = (v||'').toUpperCase()
				let parts = v.split('-')
				let job = parts[0]
				let sample = parts[1]||''
				let type = (GridGetCell(r, 'Type')||'').replace(/2$/, '')
				let sample_id_jar = job+'-'+sample+type
				let jar = parts[2]||''
				let sample_id = GridGetCell(r, 'Sample ID')
				let sample_id_vial = GridGetCell(r, 'Sample ID<br>(40ml Vial)')
				if (sample_id && sample_id_jar != sample_id){
					Error('Sample ID does not match.')
					return { isValid: false }
				}
				else{
					GridSetCell(r, 'Jar', jar, 1, 0, 1)
					CheckSamplesAndJars(r, sample_id, sample_id_jar, sample_id_vial, jar)
					return { isValid: true, value: v }
				}
				break
			}
			case 'Sample ID<br>(40ml Vial)': {
				v = (v||'').toUpperCase()
				if (v && !v.contains('-')){
					v = parseInt(v, 36).toString(10).padLeft(9, '0')
					if (v.endsWith('3'))
						v = v.slice(0, 8) + 'DUP'
					else if (v.endsWith('2'))
						v = v.slice(0, 8) + 'MSD'
					else if (v.endsWith('1'))
						v = v.slice(0, 8) + 'MS'
					else
						v = v.slice(0, 8)

					v = v.slice(0, 5) + '-' + v.slice(5)
				//	console.log(v)
				}
				let sample_id = GridGetCell(r, 'Sample ID')
				let jar = GridGetCell(r, 'Jar')
				let sample_id_jar = sample_id
				if (GridGetCell(r, 'Sample ID<br>(Original Jar)')){
					let parts = GridGetCell(r, 'Sample ID<br>(Original Jar)').split('-')
					let type = (GridGetCell(r, 'Type')||'').replace(/2$/, '')
					sample_id_jar = parts[0]+'-'+parts[1]+type
				}
				let sample_id_vial = v
				if (sample_id && sample_id_vial != sample_id){
					Error('Sample ID does not match.')
					return { isValid: false }
				}
				else{
					CheckSamplesAndJars(r, sample_id, sample_id_jar, sample_id_vial, jar)
					return { isValid: true, value: v }
				}
				break
			}
			default: {
				let input_type = 'Type' in properties? properties.Type : 'text'
				switch(input_type){
					case 'date': {
						let value = (v||'').replace(/(\d{4})-(\d{2})-(\d{2})/, "$2/$3/$1")
						return { isValid: true, value: value }
					}
					case 'datetime':
					case 'datetime-local': {
						let value = (v||'').replace(/(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2})(?::(\d{2}))?/, "$2/$3/$1 $4:$5")
						return { isValid: true, value: value }
					}
					default: {
						if (v){
							if ('ToUpperCase' in properties && properties.ToUpperCase){
								v = v.toUpperCase()
							}
							if ('PatternValid' in properties){
								let xpattern = GetInputPattern(properties, r)
								xpattern = typeof xpattern === 'string'?
									XRegExp(xpattern, properties['PatternOption']):
									XRegExp(xpattern)

								let match = XRegExp.exec(v, xpattern)
								if (!match){
								//	Error('Sample ID does not match.')
									return { isValid: false }
								}
							}
						}
					}
				}
				break
			}
		}
		return { isValid: true, value: v }
	}

	function GetInputPattern(properties, r){
		let xpattern = properties['PatternValid']
		if (typeOf(xpattern) === 'regexp')
			return xpattern
		if (typeof xpattern === 'object'){
			let spattern = '.*'
			for (let pattern in xpattern){
				let prop = xpattern[pattern]
				if (typeof prop === 'object' && 'When' in prop){
					if (ConditionMatches(prop.When, r)){
						spattern = pattern
						break
					}
				}
			}
			xpattern = spattern
		}
		return xpattern
	}

	function AssignQCforJob(n, qc, job){
		for (let r = 1; r <= _rTitle.length; r++){
			let type = GridGetCell(r, 'Type')
			if (type) continue

			let sample_id = GridGetCell(r, 'Sample ID')
			if (!sample_id) continue

			let job_id = sample_id.left(5)
			if ((!job||job_id==job) && !GridCell(r, 'QC '+n).hasClass('disabled'))
				GridSetCell(r, 'QC '+n, qc)
		}
	}

	function CountSamplesInJob(job){
		let counter = 0
		for (let r = 1; r <= _rTitle.length; r++){
			let type = GridGetCell(r, 'Type')
			if (type) continue

			let sample_id = GridGetCell(r, 'Sample ID')
			if (!sample_id) continue

			let job_id = sample_id.left(5)
			if (job_id == job)
				counter++
		}
		return counter
	}

	function GetAliquotFromSampleID(sample_id){
		let parts = sample_id.split('-')
		let job = parts[0]
		let yy = GetYearFromJob(job)
		return 'E'+yy+'-'+sample_id
	}

	function GetYearFromJob(job){
		let year = new Date().getFullYear()
		if (job >= 7500 && new Date().getMonth() < 8)
			year--
		return (''+year).substr(2)
	}

	function ClearSampleRow(r){
		let index_sample_id = _cTitle.indexOf('Sample ID')
		for (let j = 0; j < _cTitle.length; j++){
			if (_cTitle[j].in('Type','V/C#','Sample ID','AID')) continue

			let cell = GridCell(r, j+1)
			if (!cell.hasClass('disabled')){
				cell.store('was_enabled', true)
				cell.addClass('disabled')
			}
			// Clear Cells after Sample ID, make sure events are enabled so that TCLP SPLP rows will be updated
			cell.setValue('', false)
		}
	}

	function GetTestNameFromLIMS(r, aliquot, change_test, add_tumble_blank){
		if (typeof change_test == 'undefined')
			change_test = true
		let lp = _blk_test.match(/TCLP|SPLP/)
		if (lp) lp = lp[0]
		let item = ajax({ q: 28,
			matrix: LIMS_Matrix[_matrix]||_matrix,
			group: _group,
			test: (GridGetText('LCS', 'Test')||GridGetText('LCS2', 'Test'))?[GridGetText('LCS', 'Test'), GridGetText('LCS2', 'Test')] : [_blk_test],
			aliquot: aliquot
		})[0]
		let test_name = item['Test_Name']||''
		if (lp = test_name.match(/TCLP|SPLP/)){
			lp = lp[0]
			add_tumble_blank = true
		}
		GridSetCell(r, 'Test Desc', test_name)

		let comments = GridGetCell(r, 'Comments')||''
		let rush_tat = (item['Rush_TAT']||'').replace(/(?:(.*?);)+\1/, '$1')
		if (!comments.contains(rush_tat))
			comments += (comments?'; ':'')+rush_tat
		if (test_name.contains('Extract & Hold')){
			test_name = test_name.replace('Extract & Hold', '')
			if (!comments.contains('Extract & Hold'))
				comments += (comments?'; ':'')+'Extract & Hold'
		}
		if (test_name.contains('SIM')){
			test_name = test_name.replace(/[\ \+]+SIMS?/, '')
			if (!comments.contains('SIM'))
				comments += (comments?'; ':'')+'SIM'
		}
		if (_group != 'NJ-EPH-C40' && test_name.contains('C40')){
			if (!comments.contains('FROM C40'))
				comments += (comments?'; ':'')+'FROM C40'
		}
		// per Sophia just ignore 'TCL ' and ' + 15' etc. in test names
		if (test_name.match(/ \+ (?:10|15|20)/)){
			test_name = test_name.replace(/ \+ (?:10|15|20)/, '')
		}
		if (test_name.contains('TCL ')){
			test_name = test_name.replace('TCL ', '')
		}
		GridSetCell(r, 'Comments', comments)

		if (change_test){
			let ext_type = item['Ext_Type']||''
		//	console.log(getTID(ext_type))
			let test = ext_type? getTID(ext_type)||getTID() : GridGetCell('BLK', 'Test')
			GridSetCell(r, 'Test', test)
		}
		if (add_tumble_blank)
			AddTumbleBlank(r, GridGetText(r, 'Test'))
	}

	function CellChanged(td, oldValue){
		if (!td) return
		let r = td.getParent().rowIndex
		let v = td.get('data')
		let type = td.getRowHeader()
		let header = td.getColumnHeader()
		let column = GetFieldNameFromColumnHeader(header)
		switch(column){
			case 'pH': {
				if (v == 0){
					let comments = GridGetCell(r, 'Comments')||''
					if (!comments.contains('Adjusted pH')){
						comments += (comments?'; ':'')+'Adjusted pH'
						GridSetCell(r, 'Comments', comments)
					}
				}
				else if (v == 1){
					let comments = GridGetCell(r, 'Comments')||''
					if (comments.contains('Adjusted pH')){
						comments = comments.replace(/(?:; *)?Adjusted pH/, '')
						GridSetCell(r, 'Comments', comments)
					}
				}
				break
			}
			case 'Sample ID': {
				if (!td.get('original_value') && oldValue)
					td.set('original_value', oldValue)

				// Reset and clear other fields in the same row
				if (oldValue)
					ClearSampleRow(r)

				if (v){
					for (let j = 0; j < _cTitle.length; j++){
						if (_cTitle[j] == 'Sample ID') continue

						let cell = GridCell(r, j+1)
						if (cell.retrieve('was_enabled'))
							cell.removeClass('disabled')
					}

					let parts = v.split('-')
					let job = parts[0]
					let yy = GetYearFromJob(job)
					GridSetCell(r, 'Year', 'E'+yy, 1)
					if (!type){
						GridSetCell(r, 'JOB', job, 1)
						GridSetCell(r, 'SAMPLE', parts.length>1?+parts[1]:'', 1)
					}
					else{
						GridSetCell(r, 'JOB', v, 1)
						GridSetCell(r, 'SAMPLE', '', 1)
					}
					if (!type||['MS','MSD','MS2','MSD2','DUP'].contains(type))
						GridSetCell(r, 'ALIQUOT', 'E'+yy+'-'+v)

					//************** Thursday, December 28, 2017 ********************
					let U_FIELD_ID = ajax({ q: 'Retrieve U_FIELD_ID',
						aliquot: GetAliquotFromSampleID(v)
					})
					let test_is_MDL_Study = false
					if (U_FIELD_ID && U_FIELD_ID.match(/MDL SPIKE|MDL BLANK|IDOC/i)){
						let comments = GridGetCell(r, 'Comments')||''
						if (!comments.contains(U_FIELD_ID))
							GridSetCell(r, 'Comments', (comments?comments+'; ':'')+U_FIELD_ID, 1)

						test_is_MDL_Study = true
					}
					//************** Thursday, December 28, 2017 ********************

					if (test_is_MDL_Study){
//						if (_group == '8011'){
//							GridSetCell(r, 'Test', getTID('Microextractable MDL Study by 504.1/8011', test_is_MDL_Study))
//						}
//						else
						if (!_group.in(['PCB/Pest','PCB/Pest by 608','PCB/Pest by 608.3'])){
							let tid = GridGetCell('BLK', 'Test')
							GridSetCell(r, 'Test', tid)
						}
					}
					else{
						let blk_test = GridGetText('BLK', 'Test')||''
						if (type){
							let tid = type=='BLK'? getTID() : _GRID.getCell('BLK', 'Test')
							let test = type=='BLK'? _group : blk_test
							let lp = test.contains('TCLP')? 'TCLP ' : test.contains('SPLP')? 'SPLP ' : ''
							if (_group.in(['PCB/Pest','PCB/Pest by 608','PCB/Pest by 608.3']) && type.in(['LCS','LCSD','LCS2','LCSD2','MS','MSD','MS2','MSD2'])){
								test = lp+(type.in(['LCS','LCSD','MS','MSD'])?'PCB':'Pesticides')
								tid = getTID(test)
							}
							GridSetCell(r, 'Test', tid)

							if (type == 'BLK'){
								if (!GridGetCell('BLK', 'Test')){
									_blk_test = Default_Test[_group]||_group
									GridSetCell(r, 'Test', getTID(_blk_test))
								}

								if ($('tumble_blk'))
									IsTumbleBlank()? $('tumble_blk').show() : $('tumble_blk').hide()
							}
						}
						else{	// is sample
							GetTestNameFromLIMS(r, 'E'+yy+'-'+v)
							if (!GridGetCell(r, 'Test')){
								if (!_blk_test)
									_blk_test = Default_Test[_group]||_group
								GridSetCell(r, 'Test', getTID(_blk_test))
							}
						}

						if (!type || !type.in('TCLP','SPLP')){
							let comments = GridGetCell(r, 'Comments')||''
							let re_extract = 'RE-EXTRACT: ${batch_id}'
							if ('RE-EXTRACT FORMAT' in _MISC_SETTINGS)
								re_extract = _MISC_SETTINGS['RE-EXTRACT FORMAT']
							let regex = new RegExp(re_extract.replace('${batch_id}', '\\d{6}-\\d{2}'), 'g')

							// Check existence in other batches
							const [other_batch_id, other_matrix, other_test] = FindSampleInAnotherBatch(v, r)
							if (other_batch_id){
								if (!comments.match(regex)){
									_SexyBox.confirm("Sample ID <b>"+v+"</b> already exists in <b>"+other_matrix+"</b> batch <b>"+other_batch_id+"</b> for <b>"+other_test+"</b>.  Is it a sample for re-extraction?", {
										textBoxBtnOk: 'Yes',
										textBoxBtnCancel: 'No',
										onComplete: yes => {
											if (yes){
												let t = re_extract.replace('${batch_id}', other_batch_id)
												let comments = GridGetCell(r, 'Comments')||''
												if (!comments.contains(t))
													GridSetCell(r, 'Comments', (comments?comments+'; ':'')+t, 1)
												if (_GRID.editingCell)
													_GRID.editingCell.getFirst().focus()
											}
											else{
												if (_group=='PCB/Pest' && _matrix.includes('Soil')){
													let new_test
													if (!other_test.includes('PCB/Pesticides')){
														if (other_test.includes('PCB'))
															new_test = other_test.replace('PCB','Pesticides')
														else
															new_test = other_test.replace('Pesticides','PCB')
													}
													if (new_test){
														GridSetCell(r, 'Test', getTID(new_test))
													}
													else{
														_GRID.setTD(td, '')
														_GRID.startEdit(td)
													}
												}
												else{
													_GRID.setTD(td, '')
													_GRID.startEdit(td)
												}
											}
										}
									})
								}
							}
							else{
								if (comments.match(regex)){
									GridSetCell(r, 'Comments', comments.replace(regex, ''))
								}
							}
						}

						if (!type){
							// MS of and MSD of, 01/16/25
							let sample_id = v
							let comments = GridGetCell(r, 'Comments')||''
							let data = ajax({ q: '27A',
								aliquot: GetAliquotFromSampleID(v)
							})
							let MS_OF, MSD_OF
							if (data && data.length){
								MS_OF = data[0]['MS_OF']
								MSD_OF = data[0]['MSD_OF']
							}
							if (MS_OF||MSD_OF){
								if (MS_OF){
									if (!comments.contains('MS OF #'+MS_OF))
										comments += (comments?'; ':'')+'MS OF #'+MS_OF
								}
								if (MSD_OF){
									if (!comments.contains('MSD OF #'+MSD_OF))
										comments += (comments?'; ':'')+'MSD OF #'+MSD_OF
								}
								GridSetCell(r, 'Comments', comments)

								// Now set the QC
								SetQC(sample_id, _blk_test, comments, () => {
									GridSetCell(r, 'Sample ID', '', 1)
									ClearSampleRow(r)
								})
								return
							}
						}
					}
				}

				if (type.in('MS','MSD','MS2','MSD2','DUP','LCS','LCSD','LCS2','LCSD2','TCLP','SPLP'))
					v? _GRID.showRow(r) : _GRID.hideRow(r)

				let empty_spots = _GRID.countInColumn('Sample ID', '', true)
				if ($('get_samples'))
					$('get_samples').disabled = !empty_spots

				// After Sample ID changed, need remove related QC items
				if (!type){
					let comments = GridGetCell(r, 'Comments')||''

					let ms = _GRID.searchInColumn('Type', '^MS2?$')
					ms.each((qc, index) => {
						let row_ms = qc.getParent().rowIndex
						let ms_sample_id = GridGetCell(row_ms, 'Sample ID')
						if (ms_sample_id == oldValue+'MS'){
							NewQC(index+1, v, comments)
						}
						// Assign QC for this sample
						if (v)
							GridSetCell(r, 'QC '+(index+1), ms_sample_id, 1, 0, 1)
					})
				}

				if (_type=='Manual Injection' && v)
					ajax({ q: 'Get Server Time' }, result => GridSetCell(r, 'Batched On', result, 1, 0, 0))
				break
			}
			case 'Test': {
				if (!td.get('original_value') && oldValue)
					td.set('original_value', oldValue)

				let test = td.get('text')
				if (type == 'BLK'){
					_blk_test = test
					ShowReagent()
					ShowHideColumns()
					SetQCwithTest(test)

					// Clear QCs when blank test changed
					for (let r = 1; r <= _rTitle.length; r++){
						if (GridGetCell(r, 'Type')) continue
						if (GridGetCell(r, 'Sample ID')){
							GridSetCell(r, 'QC 1', '')
							GridSetCell(r, 'QC 2', '')
							GridSetCell(r, 'Comments', '')
						}
					}

					// Update Sample Test Column when blank test changed
					for (let r = 1; r <= _rTitle.length; r++){
						if (GridGetCell(r, 'Type')) continue
						// Sample rows
						if (GridGetCell(r, 'Sample ID')){
							CellChanged(GridCell(r, 'Sample ID'))
//							GridCell(r, 'Test').removeClass('disabled')
//							let test = GridGetText(r, 'Test')
//							if (_group.in('PCB/Pest','PCB/Pest by 608','PCB/Pest by 608.3')){
//								if (GridCell(r, 'QC 1')){
//									test.contains('PCB')?
//										GridCell(r, 'QC 1').removeClass('disabled'):
//										GridCell(r, 'QC 1').setValue('').addClass('disabled')
//								}
//								if (GridCell(r, 'QC 2')){
//									test.contains('Pesticides')?
//										GridCell(r, 'QC 2').removeClass('disabled'):
//										GridCell(r, 'QC 2').setValue('').addClass('disabled')
//								}
//							}
						}
					}
				}
				else if (type){	// QC
				}
				else{			// a sample
					let comments = GridGetCell(r, 'Comments')||''
					let matches = comments.match(/(?:; *)?((?:TCLP|SPLP)\d{6}(?:-\d{2})?)/)
					if (matches){
						let tumble_blank = matches[1]
						GridSetCell(r, 'Comments', comments.replace(/(?:; *)?((?:TCLP|SPLP)\d{6}(?:-\d{2})?)/, ''), 1)
						if (_GRID.countInColumn('Comments', '.*'+tumble_blank+'$') == 0){
							let LP = _GRID.searchInColumn('Sample ID', '^'+tumble_blank+'$')
							LP.each(td => {
								let k = td.getParent().rowIndex
								GridSetCell(k, 'Sample ID', '')
							})
						}
					}
					if (_group.in('PCB/Pest','PCB/Pest by 608','PCB/Pest by 608.3')){
						if (GridCell(r, 'QC 1')){
							test.contains('PCB')?
								GridCell(r, 'QC 1').removeClass('disabled'):
								GridCell(r, 'QC 1').setValue('').addClass('disabled')
						}
						if (GridCell(r, 'QC 2')){
							test.contains('Pesticides')?
								GridCell(r, 'QC 2').removeClass('disabled'):
								GridCell(r, 'QC 2').setValue('').addClass('disabled')
						}
					}

					_GRID.searchInColumn('Type', '^MS2?$').each((qc, index) => {
						let row_ms = qc.getParent().rowIndex
						let ms_sample_id = GridGetCell(row_ms, 'Sample ID')
						if (v)
							GridSetCell(r, 'QC '+(index+1), ms_sample_id, 1, 0, 1)
					})

					if (v)
						AddTumbleBlank(r, test)
				}
				if (GridGetCell(r, 'Sample ID'))
					SetDefaults(r, type, test)
				break
			}
			case 'Color': {
				if (v == 6){
					_SexyBox.prompt("Please enter color:", '', {
						onComplete: returnvalue => {
							if(returnvalue){
								let comments = GridGetCell(r, 'Comments')||''
								if (!comments.contains(returnvalue))
									GridSetCell(r, 'Comments', comments+(comments?' ':'')+returnvalue)
							}
						}
					})
				//	Notice('Please specify the color in comments.')
				//	_GRID.startEdit(GridCell(r, 'Comments'))
				}
				break
			}
			case 'Comments': {
				if (oldValue){
					let matches = oldValue.match(/(?:; *)?((?:TCLP|SPLP)\d{6}(?:-\d{2})?)/)
					if (matches){
						let tumble_blank = matches[1]
						if (_GRID.countInColumn('Comments', '.*'+tumble_blank+'$') == 0){
							_GRID.searchInColumn('Sample ID', '^'+tumble_blank+'$').each(td => {
								let k = td.getParent().rowIndex
								GridSetCell(k, 'Sample ID', '')
							})
						}
					}
				}
				if (v && v.match(/R-(24|48|72|96|1WK)/))
					SetFieldValue('Rush', true)
				break
			}
			case 'QC':
			case 'QC 1':
			case 'QC 2': {
				let n = column=='QC 2'?2:1
				if (!v && oldValue){
					let ms = n==1?'MS':'MS2'
					let msd = n==1?'MSD':'MSD2'
					let orig_qc = GridGetCell(ms, 'Sample ID')

					let orig_qc_no_longer_in_use = _GRID.countInColumn('QC '+n, orig_qc) == 0
					if (orig_qc_no_longer_in_use){
						GridSetCell(ms, 'Sample ID', null)
						GridSetCell(msd, 'Sample ID', null)
						GridSetCell('DUP', 'Sample ID', null)
					}
				}
				else if (v == '(new)'){		// new QC
					let comments = GridGetCell(r, 'Comments')||''
					NewQC(n, GridGetCell(r, 'Sample ID'), comments)
				}
				else{
					if (v && IsOldQC(v)){
						// Assign QC to as many jobs as possible
						let available = +(td.get('available')||0)
						let jobs = []
						let used = 0
						for (let i = r; i <= _rTitle.length; i++){
							let sample_id = GridGetCell(i, 'Sample ID')
							if (!sample_id) continue

							let job_id = sample_id.left(5)
							let count = CountSamplesInJob(job_id)
							if (!jobs.contains(job_id) && used + count <= available){
								jobs.push(job_id)
								used += count
								AssignQCforJob(n, v, job_id)
							}
						}

						let ms = n==1?'MS':'MS2'
						let msd = n==1?'MSD':'MSD2'
						let orig_qc = GridGetCell(ms, 'Sample ID')

						if (v != orig_qc){
							// Clear New QCs if an existing QC is picked
							for (let i = 1; i <= _rTitle.length; i++){
								let type = GridGetCell(i, 'Type')
								if (type) continue
								let sample_id = GridGetCell(i, 'Sample ID')
								if (!sample_id) continue

								if (GridGetCell(i, 'QC '+n) == orig_qc)
									GridSetCell(i, 'QC '+n, null, false)
							}

							let orig_qc_no_longer_in_use = _GRID.countInColumn('QC '+n, orig_qc) == 0
							if (orig_qc_no_longer_in_use){
								GridSetCell(ms, 'Sample ID', null)
								GridSetCell(msd, 'Sample ID', null)
								GridSetCell('DUP', 'Sample ID', null)
							}
						}
					}
				}
				break
			}
		}
		EnableDisableColumns(r)
		EnableSave()

		let properties = _SAMPLE[column]
		if ('SaveSampleAfterChange' in properties && properties['SaveSampleAfterChange'])
			SaveSample(r)
	}

	function CheckSamplesAndJars(r, sample_id, sample_id_jar, sample_id_vial, jar){
		return
		let type = GridGetCell(r, 'Type')
		let ok = sample_id_jar == sample_id && sample_id_vial == sample_id && (type||jar)
		let cell_initial = GridCell(r, 'Initial')
		if (ok){
		//	cell_initial.removeClass('disabled')
			let test = GridGetText(r, 'Test')
			if (type && !cell_initial.get('data'))
				cell_initial.setValue(_DEFAULTS[test][type]['Initial'], 0, 1, 0)
		}
		else{
		//	cell_initial.setValue('', 1).addClass('disabled')
		}
	}

	function GetColumnDefault(column, test){
		let default_value = null
		let para = _STEPS[_type][_group]
		if (para){
			if (column in para)
				default_value = para[column]
			if (_matrix in para){
				default_value = para[_matrix][column]
				if ('ForTest' in para[_matrix]){
					let for_test = para[_matrix]['ForTest']
					for (let pattern in for_test){
						if (test.match(pattern) && column in for_test[pattern])
							default_value = for_test[pattern][column]
					}
				}
			}
		}
		return default_value
	}

	function SetDefaults(r, type, test){
		let fields = (type||_matrix.in(AQUEOUS)?['Initial']:[]).concat(['Final','Surrogate','Spike_1','Spike_2'])
		fields.each(item => {
			if (Is('Editable', _SAMPLE[item], type, test, r))
				GridSetCell(r, item, GetColumnDefault(item, test))
		})
	}

	function QC_Changed(n, newqc){
		for (let r = 1; r <= _rTitle.length; r++){
			let type = GridGetCell(r, 'Type')
			if (type) continue

			let sample_id = GridGetCell(r, 'Sample ID')
			if (sample_id)
				GridSetCell(r, 'QC '+n, newqc, 1, 0, 1)
		}
	}

	function Formatter(i, value, cell){
		let field = _cTitle[i]
		let properties = _SAMPLE[field]
		// for Header
		if (cell.get('tag') == 'th'){
			if ('Title' in properties)
				cell.title = properties.Title
			if ('Styles' in properties)
				cell.setStyles(properties.Styles)
			return value
		}
		// for Body
		else{
			switch(field){
				case 'Test':
					if (value){
						if (_matrix in _TEST_LIST && _group in _TEST_LIST[_matrix] && value in _TEST_LIST[_matrix][_group])
							return _TEST_LIST[_matrix][_group][value]
						else
							return _TEST_LIST[null][_group][value]
					}
				case 'QC':
				case 'QC 1':
				case 'QC 2': {
					if (value && value.contains('/')){
						let parts = value.split('/')
						return {
							text: parts[0],
							title: parts[1]
						}
					}
					else
						return value
				}
				default: {
					let type = 'Type' in properties? properties.Type : 'text'
					switch(type){
						case 'select':
							if ('Values' in properties){
								let valueByIndex = ('ValueByIndex' in properties) && properties.ValueByIndex
								return valueByIndex? properties.Values[value] : value
							}
						case 'checkbox':
							if ('Values' in properties){
								let values = properties.Values
								if (typeOf(values) == 'array')
									return value? (+value? values[0] : values[1]) : values[2]
								else			// object
									return values[value]
							}
							else
								return value? (+value? 'Yes' : 'No') : ''
						case 'date':
							if (value && value.contains('-')){
								let part = value.split('-')
								if (part.length > 2){
									let year = +part[0]
									let month = +part[1]-1
									let date = +part[2]-1
									return (month+1).toString().padLeft(2, '0')+'/'+(date+1).toString().padLeft(2, '0')+'/'+year.toString()
								}
								else{
									let year = new Date().getFullYear()
									let month = +part[0]-1
									let date = +part[1]-1
									return (month+1).toString().padLeft(2, '0')+'/'+(date+1).toString().padLeft(2, '0')+'/'+year.toString()
								}
							}
						case 'datetime':
						case 'datetime-local':
					//		if (!value)
								return value
					//		else
					//			return new Date(value).format('isoDateTime')
					}
					return value
				}
			}
		}
	}

	function TimesUsedInOtherJobsInThisBatch(n, qc, job_id){
		let countInJob = 0
		for (let r = 1; r <= _rTitle.length; r++){
			let type = GridGetCell(r, 'Type')
			if (type) continue

			let sample_id = GridGetCell(r, 'Sample ID')
			if (!sample_id) continue

			if (job_id != sample_id.left(5)) continue
			if (GridGetCell(r, 'QC '+n) == qc)
				countInJob++
		}
		return _GRID.countInColumn('QC '+n, qc) - countInJob
	}

	function CellChanging(td, newValue){
		let header = td.getColumnHeader()
		let column = GetFieldNameFromColumnHeader(header)
		switch(column){
			case 'QC':
			case 'QC 1':
			case 'QC 2':
				let select = td.getFirst()
				if (select)
					td.set('available', select.options[select.selectedIndex].get('available'))
		}
	}

	function GetInputElement(td){
		let r = td.getParent().rowIndex
		let header = td.getColumnHeader()
		let column = GetFieldNameFromColumnHeader(header)
		let value = td.get('data')
		let properties = _SAMPLE[column]
		switch(column){
			case 'QC':
			case 'QC 1':
			case 'QC 2': {
				let n = header=='QC 2'? 2:1
				let sample_id = GridGetCell(r, 'Sample ID')
				if (!sample_id) return null
				let job_id = sample_id.left(5)
				let min_available = CountSamplesInJob(job_id)
				let test = _blk_test
				let lp = null
				if (test.contains('TCLP')) lp = 'TCLP'
				if (test.contains('SPLP')) lp = 'SPLP'
				if (test.contains('PCB') || test.contains('Pesticides'))
					test = (lp? lp+' ' : '') + (header=='QC 2'? 'Pesticides':'PCB')
				let options = []
				if (!value)
					options.push(new Element('option', {
						text: '',
						value: ''
					}))
				// Thursday, June 21, 2018 disabled QC sharing
//				ListAvailableQC(n, test, min_available).each(item => {
//					let qc = item['JOB']
//					let original_id = item['Original_ID']
//					let original_batch = item['OriginalBatch']
//					// +item['Available'] = QCs available for this batch
//					let available = +item['Available'] - TimesUsedInOtherJobsInThisBatch(n, qc, job_id)
//					if (available >= min_available){
//						options.push(new Element('option', {
//							text: qc+' ('+available+' available)',
//							value: qc+'/'+original_id,
//							available: available,
//							original_batch: original_batch,
//							original_id: original_id,
//							title: 'Init. Date: '+item['InitialDate']+'\nOrig. Batch: '+original_batch+'\nOrig. ID: '+original_id/*+'\nUsed in: '+item['Batch_IDs']*/,
//							selected: qc==value
//						}))
//					}
//				})
				options.push(new Element('option', {
					text: '(New QC)',
					value: '(new)'
				}))
				return new Element('select', {
					events: {
						keydown: select_keydown,
						change: () => _GRID.finishEdit()
					}
				}).adopt(options)
				break
			}
			case 'Test': {
				let comments = GridGetCell(r, 'Comments')||''
				let is_MDL_Study = comments.match(/MDL SPIKE|MDL BLANK/i)
				let tests = _TEST_LIST[is_MDL_Study? _matrix : null][_group]
				if (tests && Object.keys(tests).length > 1){
					let ele = new Element('select', {
						events: {
							keydown: select_keydown,
							change: () => _GRID.finishEdit()
						}
					})
					for (let tid in tests){
						if (GridGetCell(r, 'Type') != 'BLK'){
							if (_blk_test.contains('TCLP') && !tests[tid].contains('TCLP')) continue
							if (_blk_test.contains('SPLP') && !tests[tid].contains('SPLP')) continue
							if (!_blk_test.contains('TCLP') && !_blk_test.contains('SPLP') &&
								(tests[tid].contains('TCLP')||tests[tid].contains('SPLP'))) continue
							if (!_blk_test.contains('PCB') && tests[tid].contains('PCB')) continue
							if (!_blk_test.contains('Pesticides') && tests[tid].contains('Pesticides')) continue

							// no duplicate test for the same sample id
							let sample_id = GridGetCell(r, 'Sample ID')
							let already_exists = false
							for (let i = 1; i <= _rTitle.length; i++){
								if (i == r || GridGetCell(i, 'Type')) continue
								if (GridGetCell(i, 'Sample ID') == sample_id && GridGetCell(i, 'Test') == tid)
									already_exists = true
							}
							if (already_exists) continue
						}
						ele.grab(new Element('option', {value: tid, text: tests[tid], selected: tid==value}))
					}
					return ele
				}
				return false
			}
			default: {
				let type = 'Type' in properties? properties.Type : 'text'
				let ele
				switch(type){
					case 'select': {
						ele = new Element('select', {
							events: {
								keyup: e => {
									if (e.key == 'enter') return
									if (_GRID.finishEdit())
										_GRID.startEdit(NextField(td))
								},
							//	change: e => {
							//		if (_GRID.finishEdit())
							//			_GRID.startEdit(NextField(td))
							//	},
							}
						})
						let values = properties.Values
						if (typeOf(values) == 'array'){
							let valueByIndex = 'ValueByIndex' in properties && properties.ValueByIndex
							values.each((item, index) => {
								let v = valueByIndex? index : item
								ele.grab(new Element('option', { value: v, text: item, selected: v==value }))
							})
						}
						else{
							for (let key in properties.Values)
								ele.grab(new Element('option', { value: key, text: properties.Values[key], selected: key==value }))
						}
						break
					}
					case 'checkbox': {
						ele = new Element('input[type=checkbox]', {
							checked: +value,
							value: +value? 1 : 0,
							events: {
								click: 'Click' in properties? properties.Click : function(e){
									this.value = this.checked? 1 : 0
									e.stopPropagation()
								}
							}
						})
						break
					}
					case 'text': {
						let mask_type = 'MaskType' in properties? properties.MaskType : null
						let mask_options = 'MaskOptions' in properties? properties.MaskOptions : null
						ele = new Element('input[type='+type+']',
							/* ['BLK','LCS','LCSD','LCS2','LCSD2'].includes(GridGetCell(r, 'Type')) && */
							properties.PlaceHolder?
								{ value: value, placeholder: td.placeholder } : { value: value }
						)
						if (mask_type)
							ele.meiomask(mask_type, mask_options)

						if ('PatternValid' in properties){
							let xpattern = GetInputPattern(properties, r)
							ele.meiomask('regexp', {
								regex: xpattern,
								regexOptions: 'i',
							})
						}
						if ('PatternCompleted' in properties)
							ele.addEvent('keyup', function(e){
								if (this.value.match(properties['PatternCompleted'])){
									if (_GRID.finishEdit())
										_GRID.startEdit(NextField(td))
								}
							})
						break
					}
					case 'number': {
						ele = new Element('input[type='+type+']', {
							value: value
						})
						break
					}
					case 'date': {
						let dt = td.get('data')
						let value = dt
						if (dt && dt.contains('/')){
							let part = dt.split('/')
							let month = +part[0]-1
							let date = +part[1]-1
							let year = +part[2]
							value = year.toString()+'-'+(month+1).toString().padLeft(2, '0')+'-'+(date+1).toString().padLeft(2, '0')
						}
						ele = new Element('input[type='+type+']', {
							value: value
						})
						break
					}
					case 'datetime':
					case 'datetime-local': {
						let dt = td.get('data')
						let value = dt
						if (dt){
							let server_date_format = /(\d{1,2})\/(\d{1,2})\/(\d{4}) (\d{1,2}):(\d{2})/
							let editable_date_format = "$3-$1-$2T$4:$5:00"
							let m = dt.match(server_date_format)
							if (m && m.length){
								value = dt.replace(server_date_format, editable_date_format)
							}
						}
						ele = new Element('input[type='+type+']', {
							value: value
						})
						break
					}
					default: {
						ele = new Element('input[type='+type+']', {
							value: value
						})
						break
					}
				}
				return ele
			}
		}

		function select_keydown(e){
			switch(e.key){
				case 'up':
				case 'left':
					e.stop()
					if (this.selectedIndex > 0) this.selectedIndex--
					break
				case 'down':
				case 'right':
					e.stop()
					if (this.selectedIndex < this.options.length - 1) this.selectedIndex++
					break
			}
		}
	}

	// Thursday, June 21, 2018 disabled QC sharing
//	function ListAvailableQC(n, test, min_available){
//		return ajax({
//			q: 18,
//			n: n,
//			matrix: LIMS_Matrix[_matrix]||_matrix,
//			group: _group,
//			test: test,
//			min_available: min_available,
//			batch_id: _batch_id
//		})
//	}

	function ShowDashboard(){
		$('overlay').show()
		$('div_dashboard').setStyle('visibility', 'visible').Draggable({
			handle: 'div_dashboard_title',
			droppables: 'doc_body',
			container: 'doc_body',
			includeMargins: true,
		}).EnableButtonShortcuts()

		$('div_dashboard_table').set('html', TableFromData(ajax({ q: 8 }), {
			table_id: 'dashboard_table',
			table_class: '',
		//	transpose: true
		}))
		$$('td:match(-)').setStyle('background-color', 'gray')
	//	$$('td:match(Yes)').setStyle('background-color', 'rgb(0, 255, 0)')

		let idx_days = $('div_dashboard_table').getElement('th:match(Days)').cellIndex
		let idx_day_left = $('div_dashboard_table').getElement('th:match(Day Left)').cellIndex
		$('dashboard_table').getElements('tbody tr').each(tr => {
			let tds = tr.getElements('td')
			let days = +tds[idx_days].get('text')
			let day_left = +tds[idx_day_left].get('text')
			if (days >= 2 || day_left < 0)
				tr.setStyle('color', 'red')
			else if (day_left == 0)
				tr.setStyle('color', 'darkorange')
		})

		let table = new ScrollableTable('dashboard_table').toElement()
		table.getElements('tbody tr').addEvents({
			'mouseenter': function(){
				this.setStyle('background-color', 'pink')
			},
			'mouseleave': function(){
				this.setStyle('background-color', '')
			},
			'click': function(){
				HideDashboard()

				let idx1 = $('div_dashboard_table').getElement('th:match(Batched)').cellIndex+1
				let idx2 = $('div_dashboard_table').getElement('th:match(Fractionated)').cellIndex+1
				let idx = this.getElements('td:nth-child(n+'+idx1+'):nth-child(-n+'+idx2+'):not(:match(Yes))')[0].cellIndex
				let incompleted_task = $('div_dashboard_table').getElements('th')[idx].get('text')
				let task = ({
					'Batched': 'Batch',
					'Weighed': 'Weight',
					'Surrogated': 'Surrogate / Solvent',
					'Transferred': 'Filter / Vap / Transfer',
					'Fractionated': 'Fractionation',
				})[incompleted_task]

				let index = $('div_dashboard_table').getElement('th:match(Batch ID)').cellIndex
				_batch_id = this.getElements('td')[index].get('text')

				GotoStep(task)
			}
		})
	}

	function GetTests(type){
		let tests = _TEST_LIST[null][_group]
		let filtered_tests = {}
		for (let tid in tests){
			if (typeOf(tests[tid]) == 'string'){
				let test = {
					NAME: tests[tid]
				}
				filtered_tests[tid] = test
			}
			else{
				let test = tests[tid]
				if (type && +test.SAMPLE_ONLY ||
					test.MATRIX != _matrix ||
					test.TEST_GROUP != _group ||
					_blk_test.contains('TCLP') && !+test.TCLP_ONLY ||
					_blk_test.contains('SPLP') && !+test.SPLP_ONLY
				) continue
				filtered_tests[tid] = test
			}
		}
		return filtered_tests
	}

	function HideDashboard(){
		$('div_dashboard').DisableButtonShortcuts().setStyle('visibility', 'hidden')
		$('overlay').hide()
	}

	function CloseDashboard(){
		HideDashboard()
		ShowTypeAndTask()
	}

	function ShowNewBatch(){
		MatrixChanged()
		GroupChanged()

		$('div_new_batch').setStyle('visibility', 'visible').Draggable({
			handle: 'div_new_batch_title',
			droppables: 'doc_body',
			container: 'doc_body',
			includeMargins: true,
		}).EnableButtonShortcuts()
	}

	function ShowBLKTestSelector(tests, default_test, callback_OK, callback_Cancel){
		let html = '<div>'
		for (let tid in tests){
			html += '<label><input type="radio" name="blk_tests" value="'+tid+'"'+(tid==default_test?' checked':'')+'>'+tests[tid].NAME+'</label>'
		}
		html += '</div>'
		$('div_blk_tests').set('html', html)

		$('div_select_blk_test').setStyle('visibility', 'visible').makeDraggable({
			handle: 'div_select_blk_test_title',
			droppables: 'doc_body',
			container: 'doc_body',
			includeMargins: true,
		})
		EnableButtonShortcuts('#div_select_blk_test button')

		$$('#div_select_blk_test_form #buttons #OK').addEvent('click', () => {
			this.disabled = true
			let selected_test = $$('input[name=blk_tests]:checked').length? $$('input[name=blk_tests]:checked')[0].value : ''
			if (selected_test)
				callback_OK(selected_test)
			else
				Warning('Please select a blank test to continue.')
			this.disabled = false
		})
		$$('#div_select_blk_test_form #buttons #Cancel').addEvent('click', callback_Cancel)
	}
	function HideBLKTestSelector(){
		$('div_select_blk_test').setStyle('visibility', 'hidden')
		DisableButtonShortcuts('#div_select_blk_test button')
	}

	function ShowBatchList(){
		if (!$('selYear').options.length){
			let html = ''
			ajax({ q: 6 }).each(row => {
				let f = typeOf(row)=='array'? row[0] : row
				html += '<option>'+f+'</option>'
			})
			$('selYear').set('html', html)
				.addEvent('input', ListBatches)
				.fireEvent('input')
		}
		else
			$('selYear').fireEvent('input')

		$$('#div_load_batch_form input[type=text]').addEvents({
			'input': e => {
				if (_timeoutHnd)
					clearInterval(_timeoutHnd)
				_timeoutHnd = setInterval(ListBatches, 1000)
			}
		})

		$('div_load_batch').setStyle('visibility', 'visible').Draggable({
			handle: 'div_load_batch_title',
			droppables: 'doc_body',
			container: 'doc_body',
			includeMargins: true,
		}).EnableButtonShortcuts()
	}

	let _saved_year
	let _saved_search
	function ListBatches(){
		let year = $('selYear').value
		let search = $('txtSearch').value.trim()
		if (year == _saved_year && search == _saved_search)
			return

		_saved_year = year
		_saved_search = search
		let data = ajax({
			q: 7,
			year: year? 'E'+year.substr(2) : '',
			search: search,
			type: _type,
			task: _task
		})
		$('counter').set('text', data.rows.length)
		$('Load').disabled = true

		$('div_SelectBatch').set('html', TableFromData(data, {
			table_id: 'batch_table',
			table_class: 'batch_table',
			column_id: 'ID',
			column_status: typeof _TASK == 'undefined'? null : _TASK.Done.Status,
			row_class_by_status: {
				'todo': "==null"
			}
		}))

		let table = new ScrollableTable('batch_table').toElement()
		table.getElements('tr').addEvents({
			'click': function(){
				this.addClass('selected').getSiblings().removeClass('selected')
				let disabled = false
				let th = $('div_SelectBatch').getElement('th:match(^Prev. Step Completed$)')
				if (th)
					disabled = this.getElements('td')[th.cellIndex].get('text') == ''
				$('Load').disabled = disabled
			},
			'dblclick': () => $('Load').click()
		})
	}

	function ShowSampleList(){
		$$('#div_get_samples_form input[type=text]').addEvent('input', e => {
			GetSamples()
		})

		$('div_get_samples').setStyle('visibility', 'visible').Draggable({
			handle: 'div_get_samples_title',
			droppables: 'doc_body',
			container: 'doc_body',
			includeMargins: true,
		}).EnableButtonShortcuts()
		if (_GRID)
			$('CreateNewBatch').hide(), $('AddToBatch').show()
		else
			$('CreateNewBatch').show(), $('AddToBatch').hide()

		GetSamples()
	}

	function GetSamples(){
		let existing_samples = []
		if (_GRID){
			for (let r = 1; r <= 20; r++){
				let sample_id = GridGetCell(r.toString(), 'Sample ID')
				if (sample_id)
					existing_samples.push(sample_id)
			}
		}
		let search = $('txtSearchSample').value.trim()
		let data = ajax({
			q: 26,
			matrix: LIMS_Matrix[_matrix]||_matrix,
			group: _group,
			test: (GridGetText('LCS', 'Test')||GridGetText('LCS2', 'Test'))?[GridGetText('LCS', 'Test'), GridGetText('LCS2', 'Test')] : [_blk_test],
			search: search,
			existing_samples: JSON.stringify(existing_samples),
			debug: _DEBUG_
		})
		if (!search){
			$('span_get_samples_mgt').show()
			$('span_get_samples_matrix').set('text', _matrix)
			$('span_get_samples_group').set('text', _group)
			$('span_get_samples_test').set('text', _blk_test)
		}
		else{
			$('span_get_samples_mgt').hide()
		}
		$('span_get_samples_counter').set('text', data.rows.length)
		$('Load').disabled = true

		$('div_SelectSamples').set('html', TableFromData(data))
		let tbl = new ScrollableTable('sample_table').toElement()
		tbl.getElements('tr').addEvents('click', function(){
			RowSelectionChanged(tbl, this, !this.getElement('input[type=checkbox]').checked)
		})
		tbl.getElements('input[type=checkbox]').addEvent('click', function(e){
			if (e) e.stopPropagation()
			RowSelectionChanged(tbl, this.getParent().getParent(), this.checked)
			EnableDisableButtons()
		})
		tbl.getElements('tr').setStyle('border-bottom', _DEBUG_?'solid 1px #3F709B':'')

		let empty_spots = _GRID? _GRID.countInColumn('Sample ID', '', true) : 20
		let boxes = $$('#sample_table input[type=checkbox]')
		for (let i = 0; i < Math.min(empty_spots, boxes.length); i++)
			boxes[i].checked = true

		EnableDisableButtons()

		function EnableDisableButtons(){
			let checked_boxes = $$('#sample_table input[type=checkbox]:checked')
			if ($('CreateNewBatch'))
				$('CreateNewBatch').disabled = !checked_boxes.length
			if ($('AddToBatch'))
				$('AddToBatch').disabled = !checked_boxes.length
		}
	}

	function CreateNewBatch(){
		let sample_ids = $$('#sample_table input[type=checkbox]:checked').map(box => box.getParent('tr').id)
		CreateBatch(sample_ids)
		CancelGetSamples()
	}

	function RowSelectionChanged(tbl, row, is_selected){
		let sample_id = row.getElement('td:nth-child(2)').get('text')
		tbl.getElements('td:nth-child(2):match(^'+sample_id+'$)').each(td => {
			let tr = td.getParent()
			let checkbox = tr.getElement('input[type=checkbox]')
			checkbox.checked = is_selected
			is_selected? tr.addClass('selected') : tr.removeClass('selected')
		})
		$('AddToBatch').disabled = !$$('#sample_table input[type=checkbox]:checked').length
	}

	function CancelDashboardGetSamples(){
		$('div_dashboard_get_samples').DisableButtonShortcuts().setStyle('visibility', 'hidden')
	}

	function CancelGetSamples(){
		$('div_get_samples').DisableButtonShortcuts().setStyle('visibility', 'hidden')
	}

	function CancelNewBatch(){
		$('div_new_batch').DisableButtonShortcuts().setStyle('visibility', 'hidden')
	}

	function CancelLoadBatch(){
		$('div_load_batch').DisableButtonShortcuts().setStyle('visibility', 'hidden')
	}

	function PrintLabels(){
		PrintReport('labels.php', {}, true)
	}

	function PrintSmallLabels(){
		PrintReport('small_labels.php', {}, true)
	}

	function PrintReport(report_url, data, no_validation){
		if (!_batch_is_closed && !SaveBatch(no_validation))
			return
		PrintOut(report_url||'report.php', Object.merge({ batch: _batch_id }, data), {
			preview: _DEBUG_,	// preview report when debug mode is on
			beforeprint: (report_url, data) => {
				// the document title will become the default file name in 'Save as PDF'
				document.title = _type+' '+report_url.split('/').pop().split('.').shift().replace(/_/g, ' ').toTitleCase()+' ('+_batch_id+')'
			},
			afterprint: (report_url, data) => {
				document.title = _type
			},
		})
	}

	function ShippingReport(){
		_SexyBox.custom(Handlebars.compile($('tmpl_shipping').get('html'))({
		}), {
			'shipping': 'value'
		},{
			'textBoxBtnOk': 'Print Report',
			'textBoxBtnCancel': 'Cancel',
			onShowComplete(){
				$('shipping').set('html', '<option>'+ajax({
					q: 'Get Shipping List',
					batch_id: _batch_id
				}).join('</option><option>')+'</option>')
			},
			onComplete(result){
				if (result){
					PrintReport('shipping_report.php', { n: result.shipping })
					InitialPage()
				}
			}
		})
	}

	function AddToBatch(){
		$('sample_table').getElements('input[type=checkbox]:checked').each(checkbox => {
			let tr = checkbox.getParent('tr')
			let tds = tr.getElements('td')
			let sample_id = tds[1].get('text').substr(4)
			let test_name = tds[3].get('html').split('<br>')
		//	let rush_tat = tds[7].get('text')

			tds = _GRID.searchInColumn('Sample ID', '^'+sample_id+'$')
			if (tds.length == 1){
				let td = tds[0]
				let k = td.getParent().rowIndex
				let test = GridGetText(k, 'Test')
				if (test.contains('PCB/Pesticides')) return
				if (test_name.contains('Pesticides') && !test.contains('Pesticides') ||
					test_name.contains('PCB') && !test.contains('PCB')){
					GridSetCell(k, 'Test', test.replace(/PCB|Pesticides/, 'PCB/Pesticides'))
					return
				}
			}

			let empty_row = FindFirstEmptyRow()
			if (empty_row){
				let td = GridCell(empty_row, 'Sample ID')
				let old_v = td.get('data')
				let result = Validator(td, old_v, sample_id, false)
				if (result.isValid){
					GridSetCell(empty_row, 'Sample ID', result.value)
				}
				else{
					Warning("Sample '"+old_v+"' is invalid. Skipped.")
				}
			}
			else
				Notice('Unable add the sample to batch.  Batch is already full.')
		})

		$('div_get_samples').DisableButtonShortcuts().setStyle('visibility', 'hidden')
	}

	function FindFirstEmptyRow(){
		for (let r = 1; r <= _rTitle.length; r++){
			if (!GridGetCell(r, 'Type') && !GridGetCell(r, 'Sample ID'))
				return r
		}
		return -1
	}

	function Load(){
		$('div_load_batch').DisableButtonShortcuts().setStyle('visibility', 'hidden')
		let index = $$('#div_SelectBatch table')[0].getElements('th:match("^Batch ID$")')[0].cellIndex
		let batch_id = $('batch_table').getElement('tr.selected').getElements('td')[index].get('text')
		LoadBatch(batch_id)
	}

	function ShowUpdateLIMS(){
		$('div_UpdateLIMS').setStyle('visibility', 'visible').Draggable({
			handle: 'div_UpdateLIMS_title',
			droppables: 'doc_body',
			container: 'doc_body',
			includeMargins: true,
		}).EnableButtonShortcuts()
	}

	function HideUpdateLIMS(){
		$('div_UpdateLIMS').DisableButtonShortcuts().setStyle('visibility', 'hidden')
	}

	function OKUpdateLIMS(){
		HideUpdateLIMS()
		let extraction_done = Is('ExtractionDone', _TASK.UpdateLIMS)
		let result = ajax({ q: 31,
			type: _type,
			matrix: LIMS_Matrix[_matrix]||_matrix,
			group: _group,
			test: (GridGetText('LCS', 'Test')||GridGetText('LCS2', 'Test'))?[GridGetText('LCS', 'Test'), GridGetText('LCS2', 'Test')] : [_blk_test],
			nBatch_ID: _batch_id,
			extraction_done: extraction_done,
			manual_injection: _type=='Manual Injection'
		})
		result.success?
			Notice('LIMS updated successfully.'+(extraction_done?' Extraction is done.':'')):
			Error('Error in updating LIMS')
		Completed()
	}

	function NewBatch(){
		if (!$('selMatrix').value){
			_SexyBox.alert('<br>Please select a matrix.')
			$('selMatrix').focus()
			return
		}
		_matrix = $('selMatrix').value
		_matrix_initial = _matrix.substr(0, 1)

		if (!_group){
			_SexyBox.alert('<br>Please select a group.')
			return
		}

		if (_group == 'PCB/Pest' && _matrix == 'Soil by 3550C (Sonic)'){
			_SexyBox.alert('Pestcide<br>Do not use without QA approval – No MDLs or DOCs on file.')
		}

		CancelNewBatch()

		let blk_tests = GetTests('BLK')
		if (SHOW_BLANK_TEST_SELECTOR && Object.keys(blk_tests).length > 1){
			ShowBLKTestSelector(blk_tests, '', selected_test => {
				CreateBatch(selected_test)
				HideBLKTestSelector()
			}, HideBLKTestSelector)
		}
		else{
			_blk_test = _TEST_LIST[null][_group][getTID()]
			CreateBatch()
		}
	}

	function CreateBatch(blank_test){
		_batch_id = ajax({ q: 11,
			client_date: new Date().format('yymmdd')
		})
		if (!_batch_id||!_matrix||!_group)
			return

		let options = {
			q: 13,
		//	bYear: _bYear,			// instead set a default value for column bYear
			nBatch_ID: _batch_id,
			bMatrix: _matrix,
			bGroup: _group
		}
		if (_has_manual_injection)
			options.manual_injection = _type=='Manual Injection'?1:0
		_bid = ajax(options)

		_MDL_Study = 0, _NO_MS_MSD_DUP = 0, _NO_MS_MSD_DUP_2 = 0, _MSD = 0
		_QC_Fail = 0, _QC_Fail_2 = 0
		_batch_is_closed = false

	//	GetSurrogateDefaults()
		ShowReagent()
		ClearHeader()

		$('Matrix').set('text', _matrix)
		$('Group').set('text', _group)
		$('Batch_ID').set('text', _batch_id)
		if ($('ABID')) $('ABID').set('text', _bid)

		InitRows()
		ShowTable(blank_test)
	}

	function BLKTestSelected(){
		CreateBatch()
	}

	function DeleteBatch(){
		_SexyBox.confirm('<br>Are you sure to delete this batch(\''+_batch_id+'\')?', {
			textBoxBtnOk: 'Yes',
			textBoxBtnCancel: 'No',
			onComplete: yes => {
				if (yes){
					if (ajax({ q: 19, nBatch_ID: _batch_id }) == 'success'){
						Notice('Batch \''+_batch_id+'\' is deleted successfully.')
						$('batch').set('html', '')
						$('tabl').set('html', '')
						_saved_year = null

						$$('#print_labels,#print_report,#shipping_report,#sample_status_report,#get_samples,#delete_batch,#save_batch,#done,#next_step').set('disabled', true)
					}
				}
			}
		})
	}

	function InvalidValuesFound(){
		if (_REAGENT && 'Reagent' in _BATCH){
			for (let i = 0; i < _REAGENT.length; i++){
				let reagent_type = _REAGENT[i].REAGENT_TYPE
				let reagent_name = _REAGENT[i].REAGENT_NAME
				let id = MakeID(reagent_type+'_'+reagent_name)
				let obj = { 'Lot #': id, 'Expiration date': 'Exp_'+id }
				for (let item in obj){
					if (item in _BATCH['Reagent']){
						let input = $(obj[item])
						let properties = _BATCH['Reagent'][item]
						if (input && 'Range' in properties){
							let result = ValidateField(0, item+' of '+reagent_type+' '+reagent_name, input.value, properties.Range, properties.Type)
							if (!result.isValid){
								if (result.messageType == 'warning'){
									Warning(result.message)
								}
								else{
									_SexyBox.error(result.message, {
										onComplete: () => input.focus()
									})
									return true
								}
							}
						}
					}
				}
			}
		}

		for (let field in _BATCH){
			let properties = _BATCH[field]
			if (Conflicting(properties)||Is('Hidden', properties)||!Is('Editable', properties))
				continue
			let input = $(MakeID(field))
			if (!input) continue

			let lbl = GetLabelForDisplay(field, properties)

			if ('Range' in properties){
				let result = ValidateField(0, field, input.value, properties.Range, properties.Type)
				if (!result.isValid){
					if (result.messageType == 'warning'){
						Warning(result.message)
					}
					else{
						_SexyBox.error(result.message, {
							onComplete: () => input.focus()
						})
						return true
					}
				}
			}
		}

		for (let r = 1; r <= _rTitle.length; r++){
			if (!_GRID.isRowDisplayed(r)) continue
			let sample_id = GridGetCell(r, 'Sample ID')
			if (sample_id){
				let type = GridGetCell(r, 'Type')
				if ((!type || !type.in('TCLP','SPLP')) && Is('Editable', _SAMPLE['Sample ID'], null, null, r)){
					let result = SampleExistInAnotherBatchAndNotReextract(sample_id, r)
					if (result.isTrue){
						_SexyBox.error("Sample <b>"+sample_id+"</b> exists in <b>"+result.matrix+"</b> batch <b>"+result.batch_id+"</b> for <b>"+result.test+"</b>, and it is not a re-extraction in this batch.", {
							onComplete: () => GridCell(r, 'Sample ID').startEdit()
						})
						return true
					}
				}

				let test_name = GridGetText(r, 'Test')
				for (let column in _SAMPLE){
					let properties = _SAMPLE[column]
					let required = IsCellRequired(r, column, type, test_name)
					if (required.isTrue && 'Range' in properties){
						let blk_value = GridGetCell('BLK', column)
						if (blk_value){
							let v = GridGetCell(r, column)
							let result = ValidateField(0, column, v, properties.Range, properties.Type, blk_value)
							if (!result.isValid){
								if (result.messageType == 'warning'){
									Warning(result.message)
								}
								else{
									_SexyBox.error(result.message, {
										onComplete: () => GridCell(r, column).startEdit()
									})
									return true
								}
							}
						}
					}
				}
			}
		}
		return false
	}

	function RequiredFieldsMissing(){
		if (_REAGENT && 'Reagent' in _BATCH){
			for (let i = 0; i < _REAGENT.length; i++){
				let reagent_type = _REAGENT[i].REAGENT_TYPE
				let reagent_name = _REAGENT[i].REAGENT_NAME
				let optional = +_REAGENT[i].OPTIONAL

				let id = MakeID(reagent_type+'_'+reagent_name)
			//	let obj = { 'Lot #': id, 'Expiration date': 'Exp_'+id }

				let input = $(id)
				if ((optional==0||optional==2) && input && input.isVisible() && !input.value){
					_SexyBox.alert('Lot #'+' of '+reagent_type+' '+reagent_name+' is required!', {
						onComplete: () => input.focus()
					})
					return true
				}

				input = $('Exp_'+id)
				if (!optional && input && input.isVisible() && !input.value){
					_SexyBox.alert('Expiration date'+' of '+reagent_type+' '+reagent_name+' is required!', {
						onComplete: () => input.focus()
					})
					return true
				}

				input = $('Pas_'+id)
				if (optional==4 && input && input.isVisible() && !input.value){
					_SexyBox.alert('Quadruplicate pass date'+' of '+reagent_type+' '+reagent_name+' is required!', {
						onComplete: () => input.focus()
					})
					return true
				}
			}
		}

		for (let field in _BATCH){
			let input = $(MakeID(field))
			if (!input || !input.isDisplayed() || !input.getParent().isDisplayed() || input.value) continue	// skip if input object not exist or not displayed or its value is not empty

			let required = IsRequired(field, null, null)
			if (required.isTrue && !input.value){
				_SexyBox.alert(required.message, {
					onComplete: () => input.focus()
				})
				return true
			}
		}

		for (let r = 1; r <= _rTitle.length; r++){
			if (!_GRID.isRowDisplayed(r)) continue
			let sample_id = GridGetCell(r, 'Sample ID')
			if (sample_id){
				let type = GridGetCell(r, 'Type')
				let test_name = GridGetText(r, 'Test')
				for (let column in _SAMPLE){
					let required = IsCellRequired(r, column, type, test_name)
					if (required.isTrue && !GridGetCell(r, column)){
						_SexyBox.alert(required.message)
						GridCell(r, column).startEdit()
						return true
					}
				}
			}
		}
		return false
	}

	function IsEditable(column, type, test_name){
		let properties = _BATCH[column]
		return !Conflicting(properties, type, test_name)
			&& !Is('Hidden', properties, type, test_name)
			&& Is('Editable', properties, type, test_name)
	}

	function CellEditable(r, properties, type, test_name){
		return 'Editable' in properties && IsCellEditable(r, properties.Editable, type, test_name)
	}

	function IsCellEditable(r, properties, type, test_name){
		if (Conflicting(properties, type, test_name, r)) return false

		if (typeOf(properties) === 'string'){
			let parts = properties.split(/\s*[!=<>]+\s*/)
			if (parts.length == 1){
				if (_GRID.columnExists(properties) && !GridGetCell(r, properties))
					return false
			}
			else{	// parts.length > 1
				let name = parts[0]
				if (_GRID.columnExists(name)){
					let re = new RegExp(escapeRegExp(name), 'g')
					let exp = properties.replace(/<>/g,'!=')
						.replace(re, GridGetCell(r, name))
					if (!eval(exp))
						return false
				}
			}
		}
		else if (typeOf(properties) === 'object' && 'When' in properties){
			return ConditionMatches(properties.When, r)
		}
		else if (typeOf(properties) === 'array'){
			for (let prop of properties){
				if (IsCellEditable(r, prop, type, test_name))
					return true
			}
			return false
		}
		else if (typeOf(properties) === 'object'){
			for (let key in properties)
				if (!IsCellEditable(r, properties[key], type, test_name))
					return false
			return true
		}
		return true
	}

	function IsRequired(column, type, test_name){
		if (!IsEditable(column, type, test_name))
			return { isTrue: false }

		let properties = _BATCH[column]
		if ('Required' in properties){
			let lbl = GetLabelForDisplay(column, properties)
			let isRequired = false
			let msg = ''
			if (typeof properties.Required == 'object' && ('When' in properties.Required) && ('Is' in properties.Required)){
				let requirement = properties.Required
				isRequired = $(MakeID(requirement.When)).get(requirement.Is)
				if (isRequired)
					msg = lbl+' is required for the batch when '+requirement.When+' is '+requirement.Is+'!'
			}
			else{
				isRequired = Is('Required', properties, type, test_name)
				if (isRequired)
					msg = lbl+' is required for the batch!'
			}
			return isRequired? { isTrue: true, message: msg } : { isTrue: false }
		}
		return { isTrue: false }
	}

	function IsCellRequired(r, column, type, test_name){
		let properties = _SAMPLE[column]

		if (Conflicting(properties, 'BLK', _blk_test, r) && Conflicting(properties, null, _blk_test, r))
			return { isTrue: false }

		if (!CellEditable(r, properties, type, test_name))
			return { isTrue: false }

		if ('Required' in properties){
			let lbl = GetLabelForDisplay(column, properties)
			let isRequired = false
			let msg = ''
			let requirement = properties.Required
			if (typeOf(requirement) === 'object' && 'When' in requirement){
				isRequired = ConditionMatches(requirement.When, r)
			}
			else{
				isRequired = Is('Required', properties, type, test_name, r)
			}
			if (isRequired)
				msg = lbl+' is required for sample '+GridGetCell(r, 'Sample ID')+' !'
			return isRequired? { isTrue: true, message: msg } : { isTrue: false }
		}
		return { isTrue: false }
	}

	function SaveBatch(no_validation){
		// Nothing changed therefore no need to save
		if ($('save_batch') && $('save_batch').disabled) return true

		_GRID.finishEdit()

		no_validation = no_validation||false
		if (!no_validation){
			if (InvalidValuesFound()) return false
			if (RequiredFieldsMissing()) return false
		}

	//	if (!$('tabl').getElement('table')) return false

		let data = { q: 14, nBatch_ID: _batch_id }
		let need_update_batch = false
		for (let field in _BATCH){
			let properties = _BATCH[field]
			if (!Conflicting(properties) && ('DBName' in properties) && Is('Editable', properties, null, _blk_test)){
				data[properties.DBName] = GetFieldValue(field)
				need_update_batch = true
			}
		}
		if (need_update_batch)
			ajax(data)

		if (_REAGENT && ('Reagent' in _BATCH)){
			let reagent = []
			for (let i = 0; i < _REAGENT.length; i++){
				let reagent_type = _REAGENT[i].REAGENT_TYPE
				let reagent_name = _REAGENT[i].REAGENT_NAME
				let id = MakeID(reagent_type+'_'+reagent_name)
				let item = {
					REAGENT_TYPE: reagent_type,
					REAGENT_NAME: reagent_name,
				}
				if ($(id))
					item['REAGENT_LOT'] = $(id).value
				if ($('Exp_'+id))
					item['REAGENT_EXPDATE'] = $('Exp_'+id).value
				if ($('Pas_'+id))
					item['REAGENT_PASDATE'] = $('Pas_'+id).value
				reagent.push(item)
			}
			ajax({ q: 'Save Reagent',
				bid: _bid,
				task: _task,
				reagent: reagent
			})
		}

		// Save Data
		let need_reprint_labels = false
		for (let r = 1; r <= _rTitle.length; r++)
			SaveSample(r)

		if (need_reprint_labels)
			Notice('Samples deleted. Please reprint labels.')
		Notice('Batch \''+_batch_id+'\' is saved.')

// Reload the batch right after saving, usually not necessary. For Debugging
		if (_DEBUG_||_task=='Shipping')
			LoadBatch(_batch_id)

		$$('#print_labels,#print_report,#sample_status_report,#shipping_report,#get_samples').set('disabled', false)
		$$('#save_batch').disabled = true
		_saved_year = null
		return true
	}

	function SaveSample(r){
		let aid = GridGetCell(r, 'AID')
		let type = GridGetCell(r, 'Type')
		let vapno = GridGetCell(r, 'V/C#')
		let sample_id = GridGetCell(r, 'Sample ID')
		let original_sample_id = GridCell(r, 'Sample ID').get('original_value')
		let test_id = GridGetCell(r, 'Test')
		let original_test_id = GridCell(r, 'Test')?GridCell(r, 'Test').get('original_value'):''
		let test_name = GridGetText(r, 'Test')
		let fid = GridGetCell(r, 'FID')
		let selected = !_GRID.columnExists('Select')||+GridGetCell(r, 'Select')

		if (!sample_id && !aid || !selected) return false

		if (aid && (!sample_id || original_sample_id && sample_id != original_sample_id || original_test_id && test_id != original_test_id || GridCell(r, (_task != 'Fractionation'? 'AID' : 'FID')).hasClass('deleted'))){
			// Update TEST status on LIMS
			ajax({ q: 34,
				matrix: LIMS_Matrix[_matrix]||_matrix,
				group: _group,
			//	test: (GridGetText('LCS', 'Test')||GridGetText('LCS2', 'Test'))? [GridGetText('LCS', 'Test'), GridGetText('LCS2', 'Test')] : [_blk_test],
				nBatch_ID: _batch_id,
				ID: aid
			})
			need_reprint_labels = true
		}

		if (!sample_id || !_GRID.isRowDisplayed(r) || GridCell(r, (_task != 'Fractionation'? 'AID' : 'FID')).hasClass('deleted')){
			// Remove sample
			if (aid)
				ajax({ q: 20, ID: aid })
		}
		else{
			let data = _task != 'Fractionation'? (
				aid? { q: 16, task: _task, ID: aid, BID: _bid, OID: r } : { q: 15, task: _task, BID: _bid, OID: r }
			) : (
				fid? { q: 16, task: _task, SID: aid, FID: fid } : { q: 15, task: _task, SID: aid }
			)

			for (let field in _SAMPLE){
				let properties = _SAMPLE[field]
				if ('DBName' in properties){
			//	if (!Conflicting(properties, type) && 'DBName' in properties){
			//	if (IsCellEditable(r, properties, type, test_name)){
					let header = GetLabelForDisplay(field, properties)
					let dbName = properties.DBName
					// Using client time for testing purpose, should use server time in production
					if ('DBReadOnly' in properties && properties.DBReadOnly) continue
					// *** Make sure JOB is 5 digit, Friday, May 11, 2018
					if (!type && dbName == 'JOB'){
						let td = GridCell(r, header)
						if (td && td.get('data'))
							data[dbName] = td.get('data').padLeft(5, '0')
						else
							data[dbName] = ''
					}
					else if (!type && dbName.in('QC_ID_1','QC_ID_2')){
						let td = GridCell(r, header)
						if (td && td.get('data')){
							let title = td.get('title')
							if (!title){
								let ms = dbName == 'QC_ID_1'? 'MS' : 'MS2'
								if (GridCell(ms, 'Sample ID'))
									title = GridGetCell(ms, 'AID')
							}
							data[dbName] = title
						}
						else
							data[dbName] = ''
					}
					else{
						let ok_to_update = true
						if ('UpdateOnlyOnce' in properties && properties['UpdateOnlyOnce'] && GridGetCell(r, header))
							ok_to_update = false
						if ('Prerequisite' in properties){
							let prerequisite_fields = properties['Prerequisite']
							if (typeOf(prerequisite_fields) != 'array')
								prerequisite_fields = [prerequisite_fields]
							prerequisite_fields.each(item => {
								if (typeof item === 'string'){
									let parts = item.split(/\s*[!=<>]+\s*/)
									if (parts.length == 1){
										if (_GRID.columnExists(item) && !GridGetCell(r, item))
											ok_to_update = false
									}
									else{	// parts.length > 1
										let name = parts[0]
										if (_GRID.columnExists(name)){
											let re = new RegExp(escapeRegExp(name), 'g')
											let exp = item.replace(/<>/g,'!=')
												.replace(re, GridGetCell(r, name))
											if (!eval(exp))
												ok_to_update = false
										}
									}
								}
								else if (typeof item === 'object'){
									for(let col in item){
										if (!Conflicting(item[col])){
											if (_GRID.columnExists(col) && !GridGetCell(r, col))
												ok_to_update = false
										}
									}
								}
							})
						}
						if (ok_to_update){
							if ('ValueFromBatchField' in properties){
								let batchField = properties['ValueFromBatchField']
								if (batchField === true)
									data[dbName] = GetFieldValue(field)
								else if (batchField === '_user')
									data[dbName] = _user
								else
									data[dbName] = GetFieldValue(batchField)
							}
							else
								data[dbName] = GridGetCell(r, header)||''
						}
					}
				}
			}

			let result = ajax(data)
			if (result.success){
				if (result.id)
					GridSetCell(r, (_task != 'Fractionation'? 'AID' : 'FID'), result.id, 0, 0, 0)
			}
			else{
				_SexyBox.error(result.error)
				return false
			}
		}
		return true
	}

	function GetFieldValue(field){
		if (field == 'Batch ID') return _batch_id
		if (field == 'MDL_Study') return _MDL_Study
		if (field == 'NO_MS_MSD_DUP') return _NO_MS_MSD_DUP
		if (field == 'NO_MS_MSD_DUP_2') return _NO_MS_MSD_DUP_2
		if (field == 'MSD') return _MSD
		if (field == 'QC_Fail') return _QC_Fail
		if (field == 'QC_Fail_2') return _QC_Fail_2

		let ele = $(MakeID(field))
		return ele && ele.tagName.in('INPUT','SELECT','TEXTAREA')? GetElementValue(ele) : batch_data[field]
	}

	function SetFieldValue(field, value){
		field = $(MakeID(field))
		SetElementValue(field, value)
	}

	function Done(updateLIMS){
		_GRID.finishEdit()
		if (InvalidValuesFound()) return
		if (RequiredFieldsMissing()) return

		// Check color when transfer done
		if (_task == 'Filter / Vap / Transfer' && $(MakeID('Color Reason'))){
			let ms = GridGetCell('MS', 'Sample ID')
			if (ms){
				let qc = ms.replace('MS', '')
				let qc_row = FindSampleInThisBatch(qc)
				let qc_color = GridGetCell(qc_row, 'Color')
				let ms_color = GridGetCell('MS', 'Color')
				let ms_comment = GridGetCell('MS', 'Comments')
				if (ms_comment && ms_comment.contains('MS OF #')){
					let ms_of = ms_comment.replace('MS OF #', '')
					qc = GridGetCell('MS', 'Sample ID').split('-')[0]+'-'+ms_of.padLeft(3, '0')
					qc_row = FindSampleInThisBatch(qc)
					ms_color = GridGetCell(qc_row, 'Color')
				}

				let msd_color = GridGetCell('MSD', 'Color')
				let msd_comment = GridGetCell('MSD', 'Comments')
				if (msd_comment && msd_comment.contains('MSD OF #')){
					let msd_of = msd_comment.replace('MSD OF #', '')
					qc = GridGetCell('MSD', 'Sample ID').split('-')[0]+'-'+msd_of.padLeft(3, '0')
					qc_row = FindSampleInThisBatch(qc)
					msd_color = GridGetCell(qc_row, 'Color')
				}

				let dup_color = GridGetCell('DUP', 'Color')
				if (ms_color && (ms_color - qc_color < 0||ms_color - qc_color > 1)||
					msd_color && (msd_color - qc_color < 0||msd_color - qc_color > 1)){
					EnterColorReason('MSD', qc, qc_color)
				}
				if (dup_color && (dup_color - qc_color < 0||dup_color - qc_color > 1)){
					EnterColorReason('DUP', qc, qc_color)
				}
			}
		}

		// Check SIM when steps are done. 7/8/15
		TumbleBLK(false)

		_SexyBox.confirm('<br>Are you sure all data are correct?', {
			textBoxBtnOk: 'Yes',
			textBoxBtnCancel: 'No',
			onComplete: yes => {
				if (yes){
					if (!SaveBatch()) return

					if (updateLIMS)
						UpdateStatusAndUpdateLIMS()
					else
						Completed()
				}
			}
		})
	}
/*
	function EnterEndTime(start_time){
		_SexyBox.custom(Handlebars.compile($('tmpl_end_time').get('html'))({
			start_time: new Date(start_time).format('mm/dd/yyyy hh:MM TT'),
			start_time_iso: new Date(start_time).format('isoDateTime'),
			value: ''
		}), {
			'end_time': 'value'
		},{
			'textBoxBtnOk': 'OK',
			'textBoxBtnCancel': null,
			onShowComplete(){
				$('BoxPromptBtnOk').disabled = true
				$('end_time').addEvent('input', () => {
				//	console.log(new Date(start_time))
				//	let end_time = new Date($('end_time').value)
				//	console.log(end_time)
					$('BoxPromptBtnOk').disabled = new Date(start_time) > new Date($('end_time').value)
				})
			},
			onComplete(result){
				ajax({ q: 'Save End Time',
					batch_id: _batch_id,
					end_time: new Date(result.end_time).format('mm/dd/yyyy HH:MM')
				})
			}
		})
	}
*/
	function EnterColorReason(type, qc, qc_color){
		let qc_row = FindSampleInThisBatch(qc)
		_SexyBox.custom(Handlebars.compile($('tmpl_color_reason').get('html'))({
			Sample_ID_QC: qc,
			Color_QC: GridGetText(qc_row, 'Color'),
			Sample_ID_MS: GridGetCell('MS', 'Sample ID'),
			Color_MS: GridGetText('MS', 'Color'),
			Sample_ID_MSD: GridGetCell('MSD', 'Sample ID'),
			Color_MSD: GridGetText('MSD', 'Color'),
			value: $(MakeID('Color Reason')).value
		}), {
			'color_reason_text': 'value'
		},{
			'textBoxBtnOk': 'OK',
			'textBoxBtnCancel': null,
			onShowComplete: () => {
				$('color_reason_list').set('html', GetColorReasons())
				$('color_reason_text')
					.addEvent('input', () => $('BoxPromptBtnOk').disabled = !this.value.trim())
					.fireEvent('input')
			},
			onComplete: result => {
				$(MakeID('Color Reason')).value = result.color_reason_text
				EnableSave()
			}
		})
	}

	function GetColorReasons(){
		return ajax({ q: 38 }).map(item => '<option>'+item['COLOR_REASON']+'</option>').join('')
	}

	function UpdateStatusAndUpdateLIMS(){
		// Update status in Batch_GC, no longer necessary, will remove eventually
		['Save','Done'].each(x => {
			if ((x in _TASK) && !_status[x+' On']){
				let stage = _TASK[x]
				if ('By' in stage || 'On' in stage){
					let data = { q: 14, nBatch_ID: _batch_id }
					if ('By' in stage)
						data[stage.By] = _user
					if ('On' in stage)
						data[stage.On] = 'sysdate';		// new Date().format('mm/dd/yyyy HH:MM')
					let result = ajax(data)
					if (result != 'success'){
						Error(result)
					}
					_saved_year = null
				}
			}
		})

		if ('UpdateLIMS' in _TASK){
			let data = ajax({ q: 32,			// Get Samples to Update
				type: _type,
				matrix: LIMS_Matrix[_matrix]||_matrix,
				group: _group,
				test: (GridGetText('LCS', 'Test')||GridGetText('LCS2', 'Test'))?[GridGetText('LCS', 'Test'), GridGetText('LCS2', 'Test')] : [_blk_test],
				nBatch_ID: _batch_id,
				extraction_done: Is('ExtractionDone', _TASK.UpdateLIMS)
			})

			if (data && data.rows.length){
				ShowUpdateLIMS()
				$('div_UpdateLIMS_counter').set('text', data.rows.length)
				$('OKUpdateLIMS').setStyle('disabled', !data.rows.length)
				$('div_UpdateLIMS_samples').set('html', TableFromData(data))

				let tbl = new ScrollableTable('sample_table', {
					wrapperClass: 'scrollable-container',
					scrollbarWidth: 0,
					styles: {
						'max-height': '500px'
					}
				}).toElement()
				$('div_UpdateLIMS').setStyle('width', Math.max(880, tbl.getWidth() + 4))
			}
			else{
				if ('query' in data)
					console.log(data.query)
				Warning("Samples are either completed or not logged in yet.  No samples to update in LIMS.")
				Completed()
			}
		}
		else
			Completed()
	}

	function Completed(){
		let next_step = NextStep()
		if (_task=='Shipping')
			ShippingReport()
		else{
			_SexyBox.confirm('<br>Congratulations! You have completed '+(next_step?'the step':'all steps')+' successfully.<br><br>Do you want to print extraction report??', {
				textBoxBtnOk: 'Yes',
				textBoxBtnCancel: 'No',
				onComplete: yes => {
					if (yes){
						_task != 'Fractionation'? PrintReport() : PrintReport('report_fractionation.php')
					}

					if (next_step){
						_SexyBox.confirm('<br>Do you want to proceed to the next step??', {
							textBoxBtnOk: 'Yes',
							textBoxBtnCancel: 'No',
							onComplete: yes => yes? GotoStep(next_step) : InitialPage()
						})
					}
					else{
						InitialPage()
					}
				}
			})
		}
	}

	function InitialPage(){
		let steps = Object.keys(_STEPS[_type])
		SetTask(steps[0])

		// Clear matrix and group, force user to select matrix and group when creating a new batch
		_matrix = null
		_group = null
		$('Matrix').set('text', _matrix)
		$('Group').set('text', _group)
		fireEvent('domready')
	}

	function NextStep(){
		let steps = Object.keys(_STEPS[_type])
		let index = steps.indexOf(_task)
		let step = null
		while(index+1 < steps.length){
			index++
			let try_step = steps[index]
			let step_settings = _STEPS[_type][try_step]
			if (!Conflicting(step_settings)){
				step = try_step
				break
			}
		}
		return step
	}

	function GotoStep(step){
		SetTask(step)
		LoadBatch(_batch_id)
	}

	function ShowHideColumnsAndRows(){
		ShowHideColumns()
		ShowHideRows()
	}

	function MatrixChanged(){
		_matrix = $('selMatrix').value
		let html = '<div>'
		ajax({
			q: 'List Group',
			type: _type,
			matrix: _matrix
		}).each((group, i) => {
			if (i % 8 == 0)
				html += '</div><div>'
			html += '<label><input type="radio" name="Group" value="'+group+'"'+(group==_group?' checked':'')+'>'+group+'</label>'
		})
		html += '</div>'
		$('div_Group').set('html', html)
		$$('input[name=Group]').addEvent('click', GroupChanged)
	}

	function GroupChanged(){
		_group = $$('input[name=Group]:checked').length? $$('input[name=Group]:checked')[0].value : ''
		let html = '<option></option>'
		ajax({
			q: 'List Matrix',
			type: _type,
			group: _group
		}).each(row => {
			let matrix = typeOf(row)=='array'? row[0] : row
			html += '<option'+(matrix==_matrix?' selected':'')+'>'+matrix+'</option>'
		})
		$('selMatrix').set('html', html)
		if ($('selMatrix').options.length==2)
			$('selMatrix').selectedIndex = 1
	}

	function DebugChanged(){
		_DEBUG_ = $('debug').checked
		ShowHideColumnsAndRows()
	}

	function MDL_Study_Click(){
		if ($('MDL_Study').checked){
			_SexyBox.alert(`
<style>
table#MDL_Study_Message tr{
	vertical-align: top;
}
table#MDL_Study_Message td:first-child{
	white-space: nowrap;
	font-weight: bold;
}
</style>
<table id="MDL_Study_Message">
<tbody>
	<tr><td>· New Test Only</td><td></td></tr>
	<tr><td>· Ongoing IDOC</td><td>See Lauren</td></tr>
	<tr><td>· MDL Study</td><td>Running a batch with only MDL Study samples (BLK/SPIKE) is not recommend, but if this must be done - MS/MSD/DUP and LCS/LCSD are not required. <br>
See QA Memo and Training Presentation.(J:\\MDL - New Procedure)</td></tr>
</tbody>
</table>
			`)
		}
		return true
	}

	function GetAllKeys(json){
		let result = []
		for (let key in json){
			let value = json[key]
			if (value){
				let keys = GetAllKeys(value)
				Array.prototype.push.apply(result, keys)
			}
			else
				result.push(key)
		}
		return result
	}
	</script>
</head>
<body>
	<h2 onclick="location.reload()"><span id="Type"></span> - <span id="Group"></span> - <span id="Matrix"></span></h2>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type="checkbox" id="debug" onclick="DebugChanged()"><label for="debug">Debug Mode</label>
	<!--img src="img/notes.png" onclick="ToggleNotes()" style="width:64px;height:64px;float:right;margin-right:350px"-->
	<style>
	.overlay{
		display: none;
		position: fixed;
		top: 0;
		left: 0;
		bottom: 0;
		right: 0;
		background-color: black;
		-moz-opacity: 0.8;
		opacity: 0.8;
		filter: alpha(opacity=80);
	}
	</style>
	<span id="task_tabs"></span>
	<div id="overlay" class="overlay"></div>
	<!-- popup windows -->
	<style>
	#div_dashboard{
	//	width: 1100px;
		top: 50%;
		left: 50%;
		transform: translate(-50%, -50%);
	}
	#div_dashboard table{
		border-collapse: collapse;
	//	margin: 10px;
	}
	#div_dashboard table.scrollable-head{
		border-collapse: collapse;
		margin-top: 10px;
	}
	#div_dashboard table tr td{
		min-width: 70px;
		font-size: 12px;
		white-space: nowrap;
	}
	#div_dashboard table tbody tr td{
		max-width: 120px;
	}
	#div_dashboard table thead tr th{
		padding: 6px;
		color: white;
		background-color: darkblue;
	}
	#dashboard_table tbody{
		background-color: white;
	}
	#div_dashboard table tbody tr td{
		padding-top: 6px;
		padding-bottom: 6px;
	}
	#div_dashboard table tbody tr td{
		text-align: center;
		border: solid 1px blue;
	}
	#div_dashboard #div_dashboard_title{
		background-color: #999;
		border-top-left-radius: 4px;
		border-top-right-radius: 4px;
		height: 24px;
		text-align: center;
		padding-top: 5px;
		cursor: move;
	}
	#div_dashboard label{
		display: inline-block;
		width: 140px;
		text-align: right;
	}
	#div_dashboard fieldset label{
		width: 160px;
		text-align: left;
		font-weight: normal;
	}
	#div_dashboard fieldset legend{
		font-weight: bold;
	}
	#div_dashboard div#buttons{
		float: right;
	}
	</style>
	<div id="div_dashboard" class="float_window">
		<div id="div_dashboard_title">Dashboard - Batches in Queue</div>
		<div id="div_dashboard_table"></div>
		<div id="buttons">
			<button id="Close" onclick="CloseDashboard()" title="Shortcut: Esc">Close</button>
		</div>
	</div>
	<div id="div_new_batch" class="float_window">
		<div id="div_new_batch_title">New Batch</div>
		<div id="div_new_batch_form">
			<fieldset>
				<legend>GC Group</legend>
				<div id="div_Group"></div>
			</fieldset>
			<div>
				<label>Matrix</label>
				<select id="selMatrix" onchange="MatrixChanged()"></select>
			</div>
			<div id="buttons">
				<button id="OK" onclick="this.disabled=true; NewBatch(); this.disabled=false;" title="Shortcut: Enter">OK</button>
				<button id="Cancel" onclick="CancelNewBatch()" title="Shortcut: Esc">Cancel</button>
			</div>
		</div>
	</div>
	<div id="div_select_blk_test" class="float_window">
		<div id="div_select_blk_test_title">Please select a test for BLK</div>
		<div id="div_select_blk_test_form">
			<fieldset>
				<legend>BLK Tests</legend>
				<div id="div_blk_tests"></div>
			</fieldset>
			<div id="buttons">
				<button id="OK" onclick="//BLKTestSelected()" title="Shortcut: Enter">OK</button>
				<button id="Cancel" onclick="//HideBLKTestSelector()" title="Shortcut: Esc">Cancel</button>
			</div>
		</div>
	</div>
	<div id="div_load_batch" class="float_window">
		<div id="div_load_batch_title">Load Batch - <span id="counter">0</span> item(s) listed.</div>
		<div id="div_load_batch_form">
			<div>
				<label>Year<select id="selYear"></select></label>
				&nbsp;&nbsp;<label>Search<input type="text" id="txtSearch"></label><img src="img/clear.png" width="16px" height="16px" style="vertical-align:middle" onclick="$('txtSearch').value=''">
				<span id="buttons">
					<button id="Load" onclick="Load()" title="Shortcut: Enter">Load</button>
					<button id="Cancel" onclick="CancelLoadBatch()" title="Shortcut: Esc">Cancel</button>
				</span><br>
				<div id="div_SelectBatch"></div>
			</div>
		</div>
	</div>
	<div id="div_get_samples" class="float_window">
		<div id="div_get_samples_title">Get Samples <span id="span_get_samples_mgt">for <span id="span_get_samples_matrix"></span> / <span id="span_get_samples_group"></span> / <span id="span_get_samples_test"></span></span> - <span id="span_get_samples_counter">0</span> item(s) listed.</div>
		<div id="div_get_samples_form">
			<div>
				<label>Search<input type="text" id="txtSearchSample"></label>
				<div id="buttons" style="float:right">
					<button id="CreateNewBatch" onclick="CreateNewBatch()" title="Shortcut: Enter" disabled>Create Batch</button>
					<button id="AddToBatch" onclick="AddToBatch()" title="Shortcut: Enter" disabled>Add To Batch</button>
					<button id="Cancel" onclick="CancelGetSamples()" title="Shortcut: Esc">Cancel</button>
				</div><br>
				<div id="div_SelectSamples"></div>
			</div>
		</div>
	</div>
	<div id="div_dashboard_get_samples" class="float_window">
		<div id="div_dashboard_get_samples_title">Get Samples <span id="span_dashboard_get_samples_mgt">for <span id="span_dashboard_get_samples_matrix"></span> / <span id="span_dashboard_get_samples_group"></span> / <span id="span_dashboard_get_samples_test"></span></span> - <span id="span_dashboard_get_samples_counter">0</span> item(s) listed.</div>
		<div id="div_dashboard_get_samples_form">
			<div>
				<label>Search<input type="text" id="txtSearchSample"></label>
				<div id="buttons">
					<button id="CreateNewBatch" onclick="CreateNewBatch()" title="Shortcut: Enter" disabled>New Batch</button>
					<span id="add_to_existing_batch">
						<!--
						<button id="AddToExistingBatch" onclick="AddToExistingBatch()" title="Shortcut: Enter" disabled>Add To Batch</button>
						-->
					</span>
					<button id="Cancel" onclick="CancelDashboardGetSamples()" title="Shortcut: Esc">Cancel</button>
				</div><br>
				<div id="div_dashboard_SelectSamples"></div>
			</div>
		</div>
	</div>
	<div id="div_UpdateLIMS" class="float_window">
		<div id="div_UpdateLIMS_title">Samples to update - <span id="div_UpdateLIMS_counter">0</span> item(s) listed.</div>
		<div id="div_UpdateLIMS_form">
			<div>
				<div id="div_UpdateLIMS_samples"></div>
				<div id="buttons">
					<button id="OKUpdateLIMS" onclick="OKUpdateLIMS()" title="Shortcut: Enter">OK, Update!!!</button>
					<button id="CancelUpdateLIMS" onclick="HideUpdateLIMS()" title="Shortcut: Esc">Cancel</button>
				</div><br>
			</div>
		</div>
	</div>

	<link href="css/notes.css" rel="stylesheet">
	<script src="js/notes.js"></script>
	<div class="fixed_pane draggable resizable">
		<table style="width:100%; height:100%">
			<tr>
				<td>
					<div class="draggable_handle">Notes <button onclick="SwitchMode(this)" style="float: right">Edit</button></div>
				</td>
			</tr>
			<tr style="height:100%">
				<td>
					<div type="editable" id="notes"></div>
				</td>
			</tr>
			<tr>
				<td>
					<div class="resizable_handle"></div>
				</td>
			</tr>
		</table>
	</div>
	<link href="notimoo/Notimoo.css" rel="stylesheet">
	<script src="notimoo/Notimoo.js"></script>
</body>
</html>
<script id="tmpl_shipping" type="text/x-handlebars-template">
	<div>
	Please select a shipping:<br>
	<select id="shipping"></select>
	</div>
	<br><br>
</script>
<script id="tmpl_user" type="text/x-handlebars-template">
	<b>Before start ...</b><br><br>
	<div id="divUser">
		<label>Please select your name:</label>
		<select id="user"></select>
	</div>
	<br><br>
</script>
<script id="tmpl_type_task" type="text/x-handlebars-template">
	<b>Please select ...</b><br><br>
	{{#if has_manual_injection}}
	<fieldset>
		<legend>{{extraction_name}} or Manual Injection?</legend>
		<input type="radio" id="extraction" name="extraction_or_manual_injection"{{Extraction_checked}} onclick="ManualInjectionChanged()"><label for="extraction">{{extraction_name}}</label>
		<input type="radio" id="manual_injection" name="extraction_or_manual_injection"{{Manual_Injection_checked}} onclick="ManualInjectionChanged()"><label for="manual_injection">Manual Injection</label>
	</fieldset><br>
	{{/if}}
	<div id="divTask">
		<label>Please select your task:</label>
		<select id="task"></select>
	</div>
	<br><br>
</script>
<script id="tmpl_tabs" type="text/x-handlebars-template">
	<ul class="tabs" role="tablist">
		<li class="tab active" tabindex="0" aria-selected="true" role="tab" id="tab_0">{{step}}</li>
	</ul>
	<div class="content" tabindex="0" aria-hidden="false" id="panel_0" role="tabpanel" aria-labelledby="tab_0" style="display:block">
		<div class="headerScreen">
		{{#each buttons}}
			<button id="{{id}}" title="{{title}}" style="{{style}}" onclick="{{click}}" {{#if disabled}}disabled{{/if}}>{{text}}</button>
		{{/each}}
			<span id="span_user" style="float:right">Are you <b>{{user}}</b>? If not please <a href="">sign-in here.</a></span>
		</div>
		<div class="contentScreen">
			<div id="batch"></div><div id="div_surrogate"></div>
			<div id="tabl"></div>
			<div id="div_qc_fail"></div>
			<div id="status"></div>
			<div id="footnotes"></div>
		</div>
	</div>
</script>
<script id="tmpl_qc_fail" type="text/x-handlebars-template">
	<table class="qc_fail" style="">
		<tbody>
			<tr>
				<td id="container_QC_Fail"></td>
				<td id="container_Fail_Reason"></td>
				<td id="container_Fail_By"></td>
				<td id="container_Fail_On"></td>
			</tr>
			<tr>
				<td id="container_QC_Fail_2"></td>
				<td id="container_Fail_Reason_2"></td>
				<td id="container_Fail_By_2"></td>
				<td id="container_Fail_On_2"></td>
			</tr>
		</tbody>
	</table>
</script>
<script id="tmpl_end_time" type="text/x-handlebars-template">
	<label>Please provide END Time<br> (it must be greater than {{start_time}})</label>
	<label style="margin-left: 30px; margin-top: 8px;">End Time:
		<input type="datetime-local" id="end_time" value="{{value}}" min="{{start_time_iso}}">
	</label>
</script>
<script id="tmpl_color_reason" type="text/x-handlebars-template">
	<style>
	#table_color_reason th {
		padding: 2px;
	}
	#table_color_reason td {
		padding: 6px;
	}
	#table_color_reason td:nth-child(2) {
		text-align: center;
	}
	</style>
	<br>
	<table id="table_color_reason" border="1" style="border-collapse: collapse; border: 1px solid blue; padding: 5px; width: 80%">
		<thead style="font-weight: bold; text-align: center;">
			<tr>
				<th width="60%">Sample ID</th>
				<th width="40%">Color</th>
			</tr>
		</thead>
		<tbody>
			<tr>
				<td id="Sample_ID_QC">{{Sample_ID_QC}}</td>
				<td id="Color_QC">{{Color_QC}}</td>
			</tr>
			<tr>
				<td id="Sample_ID_MS">{{Sample_ID_MS}}</td>
				<td id="Color_MS">{{Color_MS}}</td>
			</tr>
			<tr>
				<td id="Sample_ID_MSD">{{Sample_ID_MSD}}</td>
				<td id="Color_MSD">{{Color_MSD}}</td>
			</tr>
		</tbody>
	</table>
	<br>
	<label>Please provide a reason for color difference:</label>
	<input type="text" id="color_reason_text" list="color_reason_list" value="{{value}}" style="width: 350px">
	<datalist id="color_reason_list"></datalist>
</script>