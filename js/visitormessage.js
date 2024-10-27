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
(function(a){if(!vBulletin.pageHasSelectors([".visitormessage-widget"]))return!1;a(function(){var b=a(".visitormessage-widget");vBulletin.conversation.initContentEvents(b);b.off("click",".js-comment-entry__post").on("click",".js-comment-entry__post",function(c){vBulletin.conversation.postComment.apply(this,[c,function(){location.reload()}])});vBulletin.conversation.bindEditFormEventHandlers("all")})})(jQuery);
