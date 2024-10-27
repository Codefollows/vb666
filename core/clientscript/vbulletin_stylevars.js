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
(function(c){function v(){var a=c(this);setTimeout(()=>{w(a)},0)}function w(a){var d=a.val();a.attr("id");var b=a.nextAll(".stylevar-display-value").first(),g=a.nextAll(".stylevar-display-value-color").first(),e=a.nextAll(".inherit-param-display-value").first(),f=a.nextAll(".inherit-param-display-value-color").first(),h=vBulletin.getInheritedStylevarValue(a,d),l=h.inherited;h=h.transformed;b.val(l);e.val(h);"color"==d.substr(-5)?(g.removeClass("hide").css("background",l),f.removeClass("hide").css("background",
h),h&&a.closest(".js-inheritance-container").prevAll(".color_input_container").first().find("input").val(h).change()):(g.addClass("hide"),f.addClass("hide"))}function x(a){a=a.toString(16);2>a.length&&(a="0"+a);return a.toUpperCase()}function A(a,d){var b="",g="",e="",f="",h="1";if("string"!=typeof a&&"undefined"!=typeof a.red)b="array",g=a.red,e=a.green,f=a.blue,h=a.alpha;else if("#"==a.substr(0,1)&&(4==a.length||7==a.length))b="hex",f=a.substr(1),3==f.length?(g=f.substr(0,1),e=f.substr(1,1),f=f.substr(2,
1),g=""+g+g,e=""+e+e,f=""+f+f):(g=f.substr(0,2),e=f.substr(2,2),f=f.substr(4,2)),g=parseInt(g,16),e=parseInt(e,16),f=parseInt(f,16);else if(a=a.match(/(rgba?)\(([^)]+)\)/)){b=a[1];var l=a[2].split(",");g=l[0];e=l[1];f=l[2];"rgba"==a[1]&&(h=l[3])}a={};a.format=b;switch(d){case "array":a.value={red:g,green:e,blue:f,alpha:h};break;case "hex":a.value="#"+x(g)+x(e)+x(f);break;case "rgb":a.value="rgb("+g+", "+e+", "+f+")";break;case "rgba":a.value="rgba("+g+", "+e+", "+f+", "+h+")";break;default:throw"Unexpected color format in convertColorFormat(): "+
d;}return a}function t(a){var d={originalValue:a,originalFormat:"",red:"",green:"",blue:"",alpha:""};a=A(a,"array");d.originalFormat=a.format;d.red=a.value.red;d.green=a.value.green;d.blue=a.value.blue;d.alpha=a.value.alpha;return d}function N(a,d,b,g,e,f,h,l,O,P,Q,B){a=[{original:a,transform:O,"new":""},{original:d,transform:P,"new":""},{original:b,transform:Q,"new":""}];e=[{color:"red",inherited:e},{color:"green",inherited:f},{color:"blue",inherited:h}];for(var m in a)f=a[m],a[m].deviation=Math.abs(f.original-
128);for(m in e)f=e[m],e[m].inherited_deviation=Math.abs(f.inherited-128),e[m].direction=0<f.inherited-128?"+":"-";a.sort(function(p,q){return p.deviation==q.deviation?0:p.deviation>q.deviation?-1:1});e.sort(function(p,q){return p.inherited_deviation==q.inherited_deviation?0:p.inherited_deviation>q.inherited_deviation?-1:1});f=[0,1,2];for(var k in f)a[k].color=e[k].color,a[k].inherited=e[k].inherited,a[k].inherited_deviation=e[k].inherited_deviation,a[k].direction=e[k].direction;k={};for(m in a)f=
a[m],a[m].transformedValue="+"==f.direction?parseInt(f.inherited,10)+parseInt(f.transform,10):parseInt(f.inherited,10)-parseInt(f.transform,10),k[f.color]=a[m].transformedValue;for(m in k)k[m]=Math.max(0,Math.min(255,k[m]));""!=l&&B?(k.alpha=parseFloat(l)+parseFloat(B),k.alpha=Math.max(0,Math.min(1,k.alpha))):k.alpha=l;return k}function C(){function a(h,l){return h==l?0:parseInt((128<=h?h>l?"-":"+":h<l?"-":"+")+Math.abs(h-l),10)}var d=c(this),b=d.val(),g=d.prevAll(".js-inherit-params").first(),e=
d.prevAll(".stylevar-display-value").first().val();var f=e+"|";b=t(b);e=t(e);f+=a(e.red,b.red)+", ";f+=a(e.green,b.green)+", ";f+=a(e.blue,b.blue);g.val(f);w(d.prevAll(".stylevar-autocomplete").first())}function D(){var a=c(this),d=a.data("stylevarid"),b=a.val();a=a.closest("#cpform_table").find('input[type="text"]:visible');"undefined"!=typeof vBulletin.globalStylevarInfo[b]&&a.each(function(){var g=c(this),e=g.attr("name");if("undefined"==typeof e)return!0;e=e.split("[");if("stylevar"!=e[0]||e[1]!=
d+"]"||"stylevar_"!=e[2].substr(0,9))return!0;e=e[2].substr(9,e[2].length-10);g.val(b+"."+e)})}function E(){var a=c(this);a.data("stylevarid");a.closest("#cpform_table").find('input[type="text"]:visible, select:visible').each(function(){var d=c(this);if("undefined"==typeof d.attr("name"))return!0;d.val("")});return!1}function F(a){y(c(".js-varlist-optgroup"),a)}function y(a,d){a.each(function(){var b=c(this);b.prop("label");b.find("option").toggleClass("h-hide-imp",!d);var g=d?b.data("label-expanded"):
b.data("label-collapsed");b.prop("label",g)});G()}function G(){var a=R();c(".js-stylevar-editor__toggle-all-groups").removeClass("toggle-link-selected");a.allGroupsExpanded?c(".js-stylevar-editor__toggle-all-groups.js-toggle-expand-all").addClass("toggle-link-selected"):a.allGroupsCollapsed&&c(".js-stylevar-editor__toggle-all-groups.js-toggle-collapse-all").addClass("toggle-link-selected")}function R(){var a=!0,d=!0;c(".js-varlist-optgroup:visible").each(function(){0<c(this).find(".js-varlist-option:visible").length?
a=!1:d=!1});return{allGroupsCollapsed:a,allGroupsExpanded:d}}function H(){c(".js-stylevar-editor__text-filter").val("");n()}function n(){var a=c(".js-stylevar-editor__text-filter").val().toLowerCase(),d=c(".js-stylevar-editor__customized-filter").prop("checked");c(".js-hidden-by-text-search:not(.js-hidden-by-iscustomized-filter)").removeClass("h-hide-imp js-hidden-by-text-search");c(".js-hidden-by-text-search.js-hidden-by-iscustomized-filter").removeClass("js-hidden-by-text-search");c(".js-stylevar-editor__clear-text-filter").addClass("h-hide-imp");
c(".js-hidden-by-iscustomized-filter:not(.js-hidden-by-text-search)").removeClass("h-hide-imp js-hidden-by-iscustomized-filter");c(".js-hidden-by-iscustomized-filter.js-hidden-by-text-search").removeClass("js-hidden-by-iscustomized-filter");c(".js-varlist-optgroup").removeClass("h-hide-imp");if(""==a&&!d)return!1;F(!0);""!=a&&(c(".js-stylevar-editor__clear-text-filter").removeClass("h-hide-imp"),c(".js-varlist-option").each(function(){var b=c(this);-1==b.data("search-text").indexOf(a)&&b.addClass("h-hide-imp js-hidden-by-text-search").prop("selected",
!1)}));d&&c(".js-varlist-option:not(.col-c):not(.col-i)").addClass("h-hide-imp js-hidden-by-iscustomized-filter").prop("selected",!1);c(".js-varlist-optgroup").each(function(){var b=c(this);0==b.find(".js-varlist-option:visible").length&&b.addClass("h-hide-imp")});u();G()}function u(){var a=[],d="";c(".js-varlist-option").each(function(){var b=c(this);b.prop("selected")&&a.push("stylevarid[]="+b.prop("value"))});0<a.length&&(a.push("securitytoken="+SECURITYTOKEN),a.push("adminhash="+ADMINHASH),a.push("do=fetchstylevareditor"),
a.push("dostyleid="+c(".js-stylevar-editor-data").data("dostyleid")),d="admincp/stylevar.php?"+a.join("&"));c(".js-edit-scroller").prop("src",d)}function I(a){a="expand-all"==c(this).data("action");a||(H(),c(".js-stylevar-editor__customized-filter").prop("checked",!1),n(),c(".js-varlist-option").prop("selected",!1),u());F(a);a&&(z.call(c(".js-stylevar-editor__text-filter")),c(".js-stylevar-editor__customized-filter"),n());return!1}function z(a){r&&(clearTimeout(r),r=0);!a||a.keyCode&&13==a.keyCode?
n():r=setTimeout(()=>{r=0;n()},50);return!1}function J(a){H();return!1}function K(a){n()}function L(a){var d=c(this),b=d.find(".js-varlist-option:visible");1>b.length&&(y(d,!0),b=d.find(".js-varlist-option:visible"));var g=!0;b=d.find(".js-varlist-option:visible");b.each(function(){if(!c(this).prop("selected"))return g=!1});a.ctrlKey||a.metaKey||c(".js-varlist-option").prop("selected",!1);g?(b.prop("selected",!1),y(d,!1)):b.prop("selected",!0);n();u();return!1}function M(a){u();return!1}function S(){c(".js-varlist-option").each(function(){var a=
c(this),d=a.text().toLowerCase();a.data("search-text",d)})}vBulletin.getInheritedStylevarValue=function(a,d){a=c(a);var b=d.split(".");if(2==b.length&&(d=b[0],b=b[1],vBulletin.globalStylevarInfo&&vBulletin.globalStylevarInfo[d]&&(d=vBulletin.globalStylevarInfo[d],"undefined"!=typeof d[b]))){d={inherited:d[b],transformed:""};if("color"==b.substr(-5)&&(a=a.attr("name").replace("stylevar_","inherit_param_"),b=c('input[name="'+a+'"]').val())){a=d.inherited;b=b.split("|");var g=b[0];b=b[1].split(",");
var e=t(g);g=t(a);g.originalFormat&&(a=N(e.red,e.green,e.blue,e.alpha,g.red,g.green,g.blue,g.alpha,b[0],b[1],b[2],"undefined"!=typeof b[3]?b[3]:"1"),b=g.originalFormat,1>a.alpha&&(b="rgba"),a=A(a,b).value);d.transformed=a}return d}return{inherited:"",transformed:""}};var r=0;c(()=>{c(".js-stylevar-editor__toggle-all-groups").off("click",I).on("click",I);c(".js-stylevar-editor__text-filter").off("keyup",z).on("keyup",z);c(".js-stylevar-editor__clear-text-filter").off("click",J).on("click",J);c(".js-stylevar-editor__customized-filter").off("click",
K).on("click",K);c(".js-varlist-optgroup").off("click",L).on("click",L);c(".js-varlist-option").off("click",M).on("click",M);c(".js-stylevar-editor__toggle-all-groups.toggle-link-selected").click();S();"function"==typeof init_color_preview&&init_color_preview()});window.initStylevarInheritanceControls=function(a,d){vBulletin.globalStylevarInfo=d;c(".stylevar-autocomplete").not(".readonly").autocomplete({source:a,appendTo:".stylevar-autocomplete-menu",minLength:0,select:v}).focus(function(){var b=
c(this);b.autocomplete("search",b.val())}).on("keyup",v).trigger("keyup");c(".stylevar-autocomplete.readonly").each(function(){v.call(this)});c(".js-inherit-params").off("change keyup").on("change keyup",function(){w(c(this).prevAll(".stylevar-autocomplete").first())});c(".js-generate-transform-params").off("change keyup",C).on("change keyup",C);c(".js-inherit-all-properties").off("change",D).on("change",D);c(".js-clear-all-stylevar-fields").off("click",E).on("click",E)}})(jQuery);
