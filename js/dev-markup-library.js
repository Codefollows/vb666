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
(function(b){function e(a){d(b(a.target).data("item-name"));return!1}function d(a){b(".js-markup-library-item").removeClass("b-comp-menu-vert__item--selected").filter(function(){return b(this).data("item-name")==a}).addClass("b-comp-menu-vert__item--selected");b(".js-markup-library-item-content").addClass("h-hide").filter(function(){return b(this).data("item-name")==a}).removeClass("h-hide")}b(function(){var a=b(".js-markup-library-item"),c=a.find(".b-comp-menu-vert__item--selected");0==c.length&&
(c=a);a.on("click",e);d(c.first().data("item-name"))})})(jQuery);
