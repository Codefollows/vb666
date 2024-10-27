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
vBulletin.precache(["login","cancel"],["noticepreviewlength"]);((a,b,e)=>{a(()=>{let c=a(".js-notice-text"),d=e.get("noticepreviewlength");c.removeClass("h-hide-imp");d&&c.condense({condensedLength:d,minTrail:20,delim:" ",moreText:b.get("see-more"),lessText:b.get("see-less"),ellipsis:"...",moreSpeed:"fast",lessSpeed:"fast",easing:"linear"})})})(jQuery,vBulletin.phrase,vBulletin.options);
