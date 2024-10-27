/*
 =======================================================================*\
|| ###################################################################### ||
|| # vBulletin 6.0.6
|| # ------------------------------------------------------------------ # ||
|| # Copyright 2000-2024 MH Sub I, LLC dba vBulletin. All Rights Reserved.  # ||
|| # This file may not be redistributed in whole or significant part.   # ||
|| # ----------------- VBULLETIN IS NOT FREE SOFTWARE ----------------- # ||
|| # http://www.vbulletin.com | http://www.vbulletin.com/license.html   # ||
|| ###################################################################### ||
\*========================================================================*/
var multi_input=[];
function vB_Multi_Input(e,g,f){this.varname=e;this.count=g;this.cpstylefolder=f;this.add=function(){var a=document.createElement("div");a.id="multi_input_container_"+this.varname+"_"+this.count;a.appendChild(document.createTextNode(this.count+1+" "));a.appendChild(this.create_input(this.count+1));fetch_object("multi_input_fieldset_"+this.varname).appendChild(a);this.append_buttons(this.count++);return!1};this.create_input=function(){var a=document.createElement("input");a.type="text";a.size=40;a.className=
"bginput";a.name="setting["+this.varname+"]["+this.count+"]";a.id="multi_input_"+this.varname+"_"+this.count;a.tabIndex=1;return a};this.create_button=function(a,d,c){var b=document.createElement("a");b.varname=this.varname;b.index=a;b.moveby=c;b.href="#";b.onclick=function(){return multi_input[this.varname].move(this.index,this.moveby)};a=document.createElement("img");a.src="../cpstyles/"+this.cpstylefolder+"/move_"+d+".gif";a.alt="";a.border=0;b.appendChild(a);return b};this.append_buttons=function(a){var d=
fetch_object("multi_input_container_"+this.varname+"_"+a);d.varname=this.varname;d.index=a;d.appendChild(document.createTextNode(" "));d.appendChild(this.create_button(a,"down",1));d.appendChild(document.createTextNode(" "));d.appendChild(this.create_button(a,"up",-1))};this.fetch_input=function(a){return fetch_object("multi_input_"+this.varname+"_"+a)};this.move=function(a,d){var c,b=[];for(c=0;c<this.count;c++)b[c]=this.fetch_input(c).value;if(0==a&&0>d)for(c=0;c<this.count;c++)this.fetch_input(c).value=
c==this.count-1?b[0]:b[c+1];else if(a==this.count-1&&0<d)for(c=0;c<this.count;c++)this.fetch_input(c).value=0==c?b[this.count-1]:b[c-1];else this.fetch_input(a).value=b[a+d],this.fetch_input(a+d).value=b[a];return!1};for(e=0;e<this.count;e++)this.append_buttons(e)}
(function(e){e(function(){e("#settings-filter").trigger("focus").on("keyup",function(g){var f=e(this).val().toLowerCase();g=e("#settings-select");var a=g.find("option");if(""==f)a.show(),g.get(0).selectedIndex=0;else{var d=[];a.each(function(c,b){b=e(this);var k=b.val().toLowerCase(),h=b.text().toLowerCase();-1==k.indexOf(f)&&-1==h.indexOf(f)&&"[all]"!=k?b.hide():(b.show(),-1!=h.indexOf(f)&&d.push({index:c,text:h,$option:b}))});0<d.length&&(d.sort(function(c,b){c=c.text.match(new RegExp("(\\b|^|/)"+
f,"i"));b=b.text.match(new RegExp("(\\b|^|/)"+f,"i"));c=c&&c.length?1:0;b=b&&b.length?1:0;return c==b?0:c>b?-1:1}),a=d.shift(),g.get(0).selectedIndex=a.index)}})})})(jQuery);
