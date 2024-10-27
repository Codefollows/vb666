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
(a=>{function g(e){let d;e=b=>(b=(b||"").match(/^.+\[(.+?)\].*$/))?b[1]:"";this.id&&(d=e(this.id));d||(d=e(this.name));m(d);return!0}function m(e){let d=[{name:"do",value:"validate"},{name:"varname",value:e}];d.push(...a("#optionsform :input[name=adminhash]").serializeArray());d.push(...a("#tbody_"+e+" :input").serializeArray());a.post("admincp/options.php?do=validate&varname=",d,null,"xml").done(b=>{var f=a(b);b=f.find("varname").first().text();f=f.find("valid").first().text();let c=a("#tbody_error_"+
b);0<c.length&&(c.is(":visible")&&(h?l[b]=!0:c.hide()),1!=f&&(h?k[b]=f:(c.show(),a("#span_error_"+b).html(f))))})}let k={},l={},h=!1;a(()=>{let e=a("#optionsform"),d=e.find("input"),b=a(document);d.filter("[type=text],[type=password],[type=file]").on("blur",g);d.filter("[type=radio],[type=checkbox],[type=button]").on("click",g);e.find("select").on("click",g);e.find("textarea").on("blur",g);b.on("mousedown",f=>{h=!0});b.on("mouseup",f=>{h=!1;for(let c in l)a("#tbody_error_"+c).hide(),delete l[c];for(let c in k)a("#tbody_error_"+
c).show(),a("#span_error_"+c).html(k[c]),delete k[c]});a(".js-setting-form").on("submit",f=>0<a(f.currentTarget).find('tbody[id^="tbody_error_"]:visible').length?confirm(vBAdmin.renderPhrase("error_confirmation_phrase")):!0)})})(jQuery);
