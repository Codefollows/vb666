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
(function(a){function g(h){let b=a(h.currentTarget);b.find(".js-serialize-form-data").each(function(c){let d=(c=a(this).data("source"))&&b.find('input[name="'+c+'[]"]:checked')||[],e=[],f="";d.length&&(d.each(function(l,k){e.push(parseInt(k.value,10))}),d.prop("disabled",!0),f=e.join(","),b.append('<input type="hidden" name="'+c+'_csv" value="'+f+'"/>'))});return!0}a(()=>{a(".js-serialize-form-data").closest("form").on("submit",g);a(".js-prune-no-permission").on("click",()=>alert(vBAdmin.renderPhrase("you_may_not_delete_move_this_user")));
vBAdmin.initJumpControl("ufind",{edit:"admincp/user.php?do=edit&userid={id}",kill:"admincp/user.php?do=remove&userid={id}","default":"admincp/user.php?do=emailpassword&userid={id}&email={value}"});vBAdmin.initJumpControl("uql",{"default":"{value}"})})})(jQuery);
