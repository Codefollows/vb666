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
(function(c){function d(b,a){a=a||c(".js-toolbar-pagenav");a.find(".js-pagenum").val(b);a.find(".left-arrow, .right-arrow").removeClass("h-disabled");1>=b&&a.find(".left-arrow").addClass("h-disabled");var e=a.find(".js-maxpage").text();b>=e&&a.find(".right-arrow").addClass("h-disabled");1<=b&&b<=e&&a.data("curpage",b)}function g(b){var a=c(b.currentTarget);b=a.closest(".js-toolbar-pagenav");var e=b.closest('form[name="pagenavform"]'),h=parseInt(b.find(".js-maxpage").text(),10),f=parseInt(b.data("curpage"),
10);if(a.hasClass("js-pagenav-go-left"))a=f-1;else if(a.hasClass("js-pagenav-go-right"))a=f+1;else if(a.is("input:text")||a.hasClass("textbox"))a=parseInt(a.val(),10);else return;isNaN(a)||a>h||1>a?d(f,b):(d(a,b),e.submit())}function k(b){b.data("vb-paginate-init")||(b.data("vb-paginate-init",1),d(parseInt(b.find(".js-pagenum").val(),10),b),b.find(".arrow").on("click",g),b.find(".js-pagenum").on("change",g),b.find(".js-pagenum").on("keypress",function(a){13==a.keyCode&&(a.preventDefault(),a.target.blur())}),
b.find(".js-perpage").on("change",function(a){a=c(a.currentTarget).closest('form[name="pagenavform"]');d(1);a.submit()}))}c(function(){k(c(".js-toolbar-pagenav"))})})(jQuery);
