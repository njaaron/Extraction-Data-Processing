/*
	Script to create and submit form on fly

	var data = {
		showtitlesummary: 'both',
		searchby: 'Exact Phrase',
		resultmax: 5000,
		pagesize: 50,
		proximity_search: '',
		proximity_threshold: 3,
		wordfield: '60 minutes',
		cataloged_uncataloged: 'both',
		available_unavailable: 'both',
		medium: 'television',
		medium: 'radio',
		medium: 'television advertising',
		medium: 'radio advertising',
		medium: 'all',
		searchfield: 'title'
	};

OR

	var data = 'showtitlesummary:both\n\
searchby:Exact Phrase\n\
resultmax:5000\n\
pagesize:50\n\
proximity_search:\n\
proximity_threshold:3\n\
wordfield:60 minutes\n\
cataloged_uncataloged:both\n\
available_unavailable:both\n\
medium:television\n\
medium:radio\n\
medium:television advertising\n\
medium:radio advertising\n\
medium:all\n\
searchfield:title';

e.g.
	<button onclick='InstantForm(data, url, "POST", "_blank")'>TEST</button>
*/

var InstantForm = {
	_form: null,
	create: function(data, url, method, target) {
		this._form = document.createElement('form');
		this._form.method = method? method : 'post';
		this._form.action = url? url : '';
		this._form.target = target? target : '';
		if (typeof data == 'object') {
			for (var item in data) {
				this.addfield(item, data[item]);
			}
		}
		else if (typeof data == 'string') {
			var rows = data.split('\n');
			for (var i = 0; i < rows.length; i++) {
				var row = rows[i];
				var pos = row.indexOf(':');
				if (pos >= 0)
					this.addfield(row.substr(0, pos), row.substr(pos + 1));
			}
		}
		document.body.appendChild(this._form);
		return this._form;
	},
	addfield: function(key, value) {
		var ele = document.createElement('input');
		ele.type = 'hidden';
		ele.name = key;
		ele.value = value;
		this._form.appendChild(ele);
		return this._form;
	},
	submit: function() {
		this._form.submit();
	}
};

/*
//helper function to create the form
function getNewSubmitForm() {
	var submitForm = document.createElement("FORM");
	submitForm.method = "POST";
	document.body.appendChild(submitForm);
	return submitForm;
}

//helper function to add elements to the form
function createNewFormElement(inputForm, elementName, elementValue) {
	var newElement = document.createElement("<input name='"+elementName+"' type='hidden'>");
	inputForm.appendChild(newElement);
	newElement.value = elementValue;
	return newElement;
}

//function that creates the form, adds some elements
//and then submits it
function createFormAndSubmit() {
	var submitForm = getNewSubmitForm();
	createNewFormElement(submitForm, "field1", "somevalue");
	createNewFormElement(submitForm, "field2", "somevalue");
	submitForm.action= "someURL";
	submitForm.submit();
}
*/