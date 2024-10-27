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
(function(b){function c(){var a=!!b(".js-checkable-toggle:checked").length;b(".js-checkable").prop("checked",a)}function d(){var a=b(".js-checkable"),e=b(".js-checkable:checked");a=a.length==e.length;b(".js-checkable-toggle").prop("checked",a)}b(function(){b(".js-checkable-toggle").off("click",c).on("click",c);b(".js-checkable").off("click",d).on("click",d);var a=b(".js-colorpicker-data");1==a.length&&(window.bburl=a.data("bburl"),window.cpstylefolder=a.data("cpstylefolder"),window.colorPickerWidth=
a.data("colorpickerwidth"),window.colorPickerType=a.data("colorpickertype"));"function"==typeof init_color_preview&&init_color_preview()})})(jQuery);
