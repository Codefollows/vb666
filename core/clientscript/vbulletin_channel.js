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
(function(c){let e="admincp/moderator.php?",h={edit:"admincp/forum.php?do=edit&n=",remove:"admincp/forum.php?do=remove&n=",add:"admincp/forum.php?do=add&parentid=",addmod:e+"do=add&n=",listmod:e+"do=showmods&n=",view:"admincp/forum.php?do=view&n=",empty:"admincp/forum.php?do=empty&n="},k={add:e+"do=add&n=",show:e+"do=showmods&n="};c(()=>{c(".js-channeljump-select").off("change").on("change",a=>{a:{a=c(a.delegateTarget);let b=a.data("channel"),d=a.data("collapse"),g=a.val();if(!b&&(b=c("#sel_foruid").val(),
0==b)){alert(vBAdmin.renderPhrase("please_select_forum"));a=!1;break a}let f;if("perms"==g)f="admincp/forumpermission.php?do=modify",0<d&&(f+="&n="+b),f+="#channel"+b;else if(g in h)f=h[g]+b;else{a=!1;break a}a.get(0).selectedIndex=0;vBAdmin.vBRedirect(f,"view"==g);a=void 0}return a});c(".js-modjump-select").off("change").on("change",a=>{{a=c(a.delegateTarget);let b=a.data("channel"),d=a.val();a.get(0).selectedIndex=0;""==d?a=!1:(d in k?vBAdmin.vBRedirect(k[d]+b):vBAdmin.vBRedirect(e+="do=edit&moderatorid="+
d),a=void 0)}return a})})})(jQuery);
