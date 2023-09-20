var _PREVIEW_ORIENTATION=+localStorage._PREVIEW_ORIENTATION||0;addEvent("domready",AddPreviewButtons);
function AddPreviewButtons(){cssAdd("\n\t\t#icon_container{\n\t\t\tposition: fixed;\n\t\t//\tbackground: white;\n\t\t//\tborder: solid 1px black;\n\t\t//\tpadding: 10px;\n\t\t//\topacity: 50%;\n\t\t}\n\t\t@media print{\n\t\t\t#icon_container{\n\t\t\t\tdisplay: none;\n\t\t\t}\n\t\t}\n\t\t.icon{\n\t\t\twidth: 60px;\n\t\t\tpadding: 20px;\n\t\t\tborder-radius: 20px;\n\t\t\tborder: solid 8px #bbb;\n\t\t\tbox-shadow: 0 4px 8px 0 rgba(0, 0, 0, 0.3), 0 6px 20px 0 rgba(0, 0, 0, 0.2);\n\t\t\tcursor: pointer;\n\t\t\tbackground-color: #fff;\n\t\t\tmargin: 5px;\n\t\t\tdisplay: block;\n\t\t}\n\t\t.icon:hover{\n\t\t\tbackground-color: #eee;\n\t\t}\n\t");var a=
null,d,c,e,g,f=localStorage.toolbar_position?JSON.parse(localStorage.toolbar_position):{left:"900px",top:"100px"};f=(new Element("div",{id:"icon_container","class":"draggable",styles:{top:f.top,left:f.left}})).inject(document.body);document.body.addEvents({mousedown:function(b){for(a=b.target;!(a.classList||[]).contains("draggable");)if(a=a.parentNode,!a)return!1;b.preventDefault();b=b.changedTouches?b.changedTouches[0]:b;d=b.page.x;c=b.page.y;b=a.getBoundingClientRect();e=b.left;g=b.top},mousemove:function(b){a&&
(b.preventDefault(),b=b.changedTouches?b.changedTouches[0]:b,a.style.left=e+(b.page.x-d)+"px",a.style.top=g+(b.page.y-c)+"px")},mouseup:function(b){a&&(b.preventDefault(),localStorage.toolbar_position=JSON.stringify({left:a.style.left,top:a.style.top}),a=null)}});(new Element("img",{src:"img/Orientation-512.png",class:"icon",events:{click:function(){_PREVIEW_ORIENTATION=1-_PREVIEW_ORIENTATION;localStorage._PREVIEW_ORIENTATION=_PREVIEW_ORIENTATION;fireEvent("load")}}})).inject(f);(new Element("img",
{src:"img/print_icon.png",class:"icon",events:{click:function(){print()}}})).inject(f)}
function GetPageSize(){var a="8.5in",d="11in";if("REPORT"in _MISC_SETTINGS){var c=_MISC_SETTINGS.REPORT;"size"in c&&(d=c.size.split(/[\s,x]+/),a=d[0],d=d[1]);"width"in c&&(a=c.width);"height"in c&&(d=c.height);var e=!1;"preview"in c&&(c=c.preview,"showBorderOfPrintableArea"in c&&(e=c.showBorderOfPrintableArea));e&&$("printable").setStyle("border","dotted 1px red")}0==_PREVIEW_ORIENTATION?(c=Math.min(+a.slice(0,-2),+d.slice(0,-2))+"in",a=Math.max(+a.slice(0,-2),+d.slice(0,-2))+"in"):(c=Math.max(+a.slice(0,
-2),+d.slice(0,-2))+"in",a=Math.min(+a.slice(0,-2),+d.slice(0,-2))+"in");return{width:c,height:a}}function SetPageSize(){if(window.top==window){var a=GetPageSize();cssAdd("\n\t\t\t@page { size: "+a.width+" "+a.height+"; }\n\t\t");cssAdd("\n\t\t\t#paper{\n\t\t\t\tmin-width: "+a.width+";\n\t\t\t\tmax-width: "+a.width+";\n\t\t\t\tmin-height: "+a.height+";\n\t\t\t\tmax-height: "+a.height+";\n\t\t\t}\n\t\t")}};
