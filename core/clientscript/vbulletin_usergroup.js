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
(function(a){let c={edit:"admincp/usergroup.php?do=edit&usergroupid={id}",kill:"admincp/usergroup.php?do=remove&usergroupid={id}",list:"admincp/user.php?do=find&user[usergroupid]={id}",list2:"admincp/user.php?do=find&user[membergroup][]={id}",reputation:"admincp/user.php?do=find&"+["options","posts","usergroup","lastvisit","reputation"].map(b=>"display["+b+"]=1").join("&")+"&orderby=reputation&direction=desc&limitnumber=25&user[usergroupid]={id}",promote:"admincp/usergroup.php?do=modifypromotion&returnug=1&usergroupid={id}"};
a(()=>{vBAdmin.initJumpControl("ugjump",c)})})(jQuery);
