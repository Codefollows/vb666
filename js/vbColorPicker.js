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
var vBulletin_ColorPicker=function(g,a){$("<link>").appendTo("head").attr({rel:"stylesheet",type:"text/css",href:pageData.baseurl+"/js/colorpicker/css/colorpicker.css"});(function(){$(g).each(function(){var b=$(this),c=$('<span class="'+(a.triggerClass?a.triggerClass:"colorPickerTrigger")+'"></span>').insertBefore(b);c.css("backgroundColor",b.val());var f=c.css("backgroundColor");b.off("keyup").on("keyup",function(){/^[0-9a-f]{3}(?:[0-9a-f]{3})?$/i.test(b.val())&&b.val("#"+b.val());c.css("backgroundColor",
b.val());c.ColorPickerSetColor(c.css("backgroundColor"));if(a.onChange){var d=c.ColorPickerGetColor();a.onChange.call(b,"#"+d.hex)}});f={color:a.color?a.color:f,onChange:function(d,e,h){b.val("#"+e);c.css("backgroundColor","#"+e);a.onChange&&a.onChange.call(b,"#"+e)},onSubmit:function(d,e,h,k){$(k).val(e);b.val("#"+e);c.css("backgroundColor","#"+e);a.onSubmit&&"function"==typeof a.onSubmit&&a.onSubmit.call(b,"#"+e)}};a.fadeIn&&(f.onShow=function(d){$(d).fadeIn(a.fadeSpeed?a.fadeSpeed:500);a.onShow&&
a.onShow.call(d);return!1});a.fadeOut&&(f.onHide=function(d){$(d).fadeIn(a.fadeSpeed?a.fadeSpeed:500);a.onHide&&a.onHide.call(d);return!1});!a.fadeIn&&a.onShow&&(f.onShow=a.onShow);!a.fadeOut&&a.onHide&&(f.onHide=a.onHide);c.ColorPicker(f)})})()};
