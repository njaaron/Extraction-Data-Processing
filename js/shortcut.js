shortcut={all_shortcuts:{},disable(){for(var d in this.all_shortcuts){var c=this.all_shortcuts[d];if(!c)break;var b=c.event,a=c.target,c=c.callback;a.removeEventListener?a.removeEventListener(b,c,!1):a.detachEvent?a.detachEvent("on"+b,c):a["on"+b]=!1}},enable(){for(var d in this.all_shortcuts){var c=this.all_shortcuts[d];if(!c)break;var b=c.event,a=c.target,c=c.callback;a.addEventListener?a.addEventListener(b,c,!1):a.attachEvent?a.attachEvent("on"+b,c):a["on"+b]=c}},add(d,c,b){var a={type:"keydown",
propagate:!1,disable_in_input:!1,target:document,keycode:!1};if(b)for(var f in a)"undefined"==typeof b[f]&&(b[f]=a[f]);else b=a;a=b.target;"string"==typeof b.target&&(a=document.getElementById(b.target));d=d.toLowerCase();f=(a)=>{a=a||window.event;if(b.disable_in_input){var g;a.target?g=a.target:a.srcElement&&(g=a.srcElement);3==g.nodeType&&(g=g.parentNode);if("INPUT"==g.tagName||"TEXTAREA"==g.tagName)return}g={esc:27,escape:27,tab:9,space:32,"return":13,enter:13,backspace:8,capslock:20,numlock:144,
scrolllock:145,pause:19,"break":19,pgup:33,pgdn:34,end:35,home:36,left:37,up:38,right:39,down:40,insert:45,"delete":46,num_0:96,num_1:97,num_2:98,num_3:99,num_4:100,num_5:101,num_6:102,num_7:103,num_8:104,num_9:105,num_times:106,num_plus:107,num_minus:109,num_dot:110,num_divide:111,f1:112,f2:113,f3:114,f4:115,f5:116,f6:117,f7:118,f8:119,f9:120,f10:121,f11:122,f12:123,"=":187,plus:187,",":188,minus:189,".":190};a.keyCode?code=a.keyCode:a.which&&(code=a.which);var h="",e;for(e in g)g[e]==code&&(h+=
(h?"|":"")+e.replace(/([.?*+^$[\]\\(){}|-])/g,"\\$1"));h||(h=String.fromCharCode(code).toLowerCase());var k=0,f={"`":"~",1:"!",2:"@",3:"#",4:"$",5:"%",6:"^",7:"&",8:"*",9:"(",0:")","-":"_","=":"+",";":":","'":'"',",":"<",".":">","/":"?","\\":"|"},n=!1,p=!1,q=!1,r=!1,t=!1,u=!1,v=!1,w=!1;a.ctrlKey&&(r=!0);a.shiftKey&&(p=!0);a.altKey&&(u=!0);a.metaKey&&(w=!0);for(var l=d.split(/[+\-]/),m=0;m<l.length;m++)e=l[m],"ctrl"==e||"control"==e?(k++,q=!0):"shift"==e?(k++,n=!0):"alt"==e?(k++,t=!0):"meta"==e?(k++,
v=!0):1<e.length?g[e]==code&&k++:b.keycode?b.keycode==code&&k++:e.match("^("+escape(h)+")$")?k++:f[h]&&a.shiftKey&&(h=f[h],h==e&&k++);if(k==l.length&&r==q&&p==n&&u==t&&w==v&&(c(a),!b.propagate))return a.cancelBubble=!0,a.returnValue=!1,a.stopPropagation&&(a.stopPropagation(),a.preventDefault()),!1};this.all_shortcuts[d]={callback:f,target:a,event:b.type};a.addEventListener?a.addEventListener(b.type,f,!1):a.attachEvent?a.attachEvent("on"+b.type,f):a["on"+b.type]=f},remove(d){d=d.toLowerCase();var c=
this.all_shortcuts[d];delete this.all_shortcuts[d];if(c){d=c.event;var b=c.target,c=c.callback;b.detachEvent?b.detachEvent("on"+d,c):b.removeEventListener?b.removeEventListener(d,c,!1):b["on"+d]=!1}}};
