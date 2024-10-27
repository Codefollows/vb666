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
(function(a){a(document).ready(function(){a(".js-synlist-container .cbsubgroup-trigger").off("click").on("click",function(){var b=a(this).closest(".js-synlist-container"),c=!b.find(".cbsubgroup").toggleClass("hide").is(".hide");b.find(".js-synlist-collapseclose").toggleClass("hide",!c);b.find(".js-synlist-collapseopen").toggleClass("hide",c)});var d=a(".js-tag-phrase-data");d.length&&new vB_Inline_Mod("inlineMod_tags","tag","tagsform",d.data("gox"),"vbulletin_inline")})})(jQuery);
