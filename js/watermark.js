Watermark={container:null,id:null,element:null,orientation:0,init(a){cssAdd("\n\t\t\t.watermark {\n\t\t\t\tposition: absolute;\n\t\t\t\topacity: 0.5;\n\t\t\t\tcolor: lightgrey;\n\t\t\t\tdisplay: block;\n\t\t\t\tfont-size: 1.0in;\n\t\t\t\twidth: 200%;\n\t\t\t}\n\t\t\t.watermark_portrait {\n\t\t\t\ttransform: rotate(300deg);\n\t\t\t\t-webkit-transform: rotate(300deg);\n\t\t\t\ttop: 1in;\n\t\t\t\tleft: -2in;\n\t\t\t}\n\t\t\t.watermark_landscape {\n\t\t\t\ttransform: rotate(330deg);\n\t\t\t\t-webkit-transform: rotate(330deg);\n\t\t\t\ttop: 0in;\n\t\t\t\tleft: 0in;\n\t\t\t}\n\t\t");
this.container=a.container||$("printable")||document.body;this.id=a.id||"watermark";this.text=a.text||"NOT COMPLETED";this.orientation=a.orientation||_PREVIEW_ORIENTATION;this.element=new Element("div",{id:this.id,class:"watermark",text:this.text});window.top==window&&this.ChangeOrientation(this.orientation);this.container.grab(this.element)},ChangeOrientation(a){(this.orientation=a||_PREVIEW_ORIENTATION)?this.element.removeClass("watermark_portrait").addClass("watermark_landscape"):this.element.removeClass("watermark_landscape").addClass("watermark_portrait")},
PrintIf(a,b){this.ChangeOrientation(b);this.element.setStyle("display",a?"":"none")}};addEvent("domready",()=>{Watermark.init({container:$("printable"),id:"watermark",text:"NOT COMPLETED"})});
