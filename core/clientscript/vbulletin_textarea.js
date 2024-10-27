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
(c=>{c(()=>{let b=c("#popuptextarea"),a=opener.document.getElementsByName(b.data("source"));a&&(a=a[0],b.val(a.value));b.on("keypress",()=>{a.value=b.val()});c("#sendbutton").on("click",()=>{window.close()})})})(jQuery);
