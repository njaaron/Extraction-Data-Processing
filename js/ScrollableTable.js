/*
---
description: Class that makes table rows scrollable keeping thead and tfoot fixed.
license: MIT-style

authors:
- Massimiliano Torromeo

requires:
  core/1.2.4: '*'

provides:
- ScrollableTable

Usage:

var tbl = new ScrollableTable('sample_table', {
	wrapperClass: 'scrollable-container',
	scrollbarWidth: 17,
	styles: {
		'max-height': '500px'
	}
}).toElement();
*/

var ScrollableTable = new Class({
	Implements: Options,

	options: {
		wrapperClass: 'scrollable-container',
		scrollbarWidth: 0
	},

	initialize: function(table, options){
		if (!this.options.scrollbarWidth)
			this.options.scrollbarWidth = getScrollbarWidth();
		this.setOptions(options);
		if (typeof table == 'string')
			table = document.id(table);
		if (table.tagName != 'TABLE'){
			console.error('Table element expected in ScrollableTable');
			return;
		}
		this.table = table;
		this.thead = this.table.getElement('thead');
		if (this.thead){
			this.theadTable = new Element('table.scrollable-head').addClass(this.table.get('class'));
			this.theadTable.adopt(this.thead).inject(this.table, 'before');
		}

		this.tfoot = this.table.getElement('tfoot');
		if (this.tfoot){
			this.tfootTable = new Element('table.scrollable-foot').addClass(this.table.get('class'));
			this.tfootTable.adopt(this.tfoot).inject(this.table, 'after');
		}

		var styles = Object.merge({
			overflow: 'auto'
		}, this.options.styles);
		this.scrollableContainer = new Element('div', {
			'class': this.options.wrapperClass,
			styles: styles
		}).inject(this.table, 'after').adopt(this.table);

		this.update();
	},

	update: function(){
		var widths = [];
		this.table.getParent().getParent().getElements('tr').each(function(tr){
			tr.getChildren().each(function(td, i){
				widths[i] = widths[i]||0;
				var w = td.getWidth();
				if (widths[i] < w)
					widths[i] = w;
			});
		});
//console.log(widths);
		var table_width = 0;
		widths.each(function(w){
			table_width += w;
		});
//console.log('total width: '+table_width);

		this.table.setStyle('width', table_width + 18);
		this.table.getElements('tr:first-child').each(function(tr){
			tr.getChildren().each(function(td, i){
				var padding = (+td.getStyle('padding-left').replace('px', ''))+(+td.getStyle('padding-right').replace('px', ''));
				if (td.getWidth() != widths[i])
					td.setStyle('width', widths[i] - padding);
			});
		});
//console.log(this.table.getWidth());

		if (this.thead){
			this.thead.getParent().setStyle('width', this.table.getWidth());
			this.thead.getFirst().getChildren().each(function(td, i){
				var padding = (+td.getStyle('padding-left').replace('px', ''))+(+td.getStyle('padding-right').replace('px', ''));
				if (td.getWidth() != widths[i])
					td.setStyle('width', widths[i] - padding);
			});
		}

		if (this.tfoot){
			this.tfoot.getParent().setStyle('width', this.table.getWidth());
			this.tfoot.getFirst().getChildren().each(function(td, i){
				var padding = (+td.getStyle('padding-left').replace('px', ''))+(+td.getStyle('padding-right').replace('px', ''));
				if (td.getWidth() != widths[i])
					td.setStyle('width', widths[i] - padding);
			});
		}

//		this.table.getParent().getParent().getElements('tr:first-child').each(function(tr){
//			tr.getChildren().each(function(td, i){
//				td.setStyle('width', widths[i]);
//			});
//		});

//		var table_width = this.table.getWidth();
//		if (this.thead && table_width < this.thead.getParent().getWidth())
//			table_width = this.thead.getParent().getWidth();
//		if (this.tfoot && table_width < this.tfoot.getParent().getWidth())
//			table_width = this.tfoot.getParent().getWidth();
//
//		this.table.setStyle('width', table_width);
//		if (this.thead)
//			this.thead.getParent().setStyle('width', table_width);
//		if (this.tfoot)
//			this.tfoot.getParent().setStyle('width', table_width);

		this.scrollableContainer.setStyle('width', this.table.getWidth() + this.options.scrollbarWidth);
//		this.scrollableContainer.setStyle('width', this.table.getWidth() + this.table.offsetWidth - this.table.clientWidth);

		return this;
	},

	toElement: function(){
		return this.table;
	}
});

function getScrollbarWidth(){
	var outer = document.createElement("div");
	outer.style.visibility = "hidden";
	outer.style.width = "100px";
	document.body.appendChild(outer);

	var widthNoScroll = outer.offsetWidth;
	// force scrollbars
	outer.style.overflow = "scroll";

	// add innerdiv
	var inner = document.createElement("div");
	inner.style.width = "100%";
	outer.appendChild(inner);

	var widthWithScroll = inner.offsetWidth;

	// remove divs
	outer.parentNode.removeChild(outer);

	return widthNoScroll - widthWithScroll;
}

Element.implement({
	ScrollableTable: function(options){
		return new ScrollableTable(this, options).toElement();
	}
});
