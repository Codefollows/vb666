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
(function(a){let b={edit:"admincp/subscriptions.php?do=apiedit&paymentapiid={id}",remove:"admincp/subscriptions.php?do=apirem&paymentapiid={id}"},c={edit:"admincp/subscriptions.php?do=edit&subscriptionid={id}",remove:"admincp/subscriptions.php?do=remove&subscriptionid={id}",view:"admincp/subscriptions.php?do=find&status=-1&subscriptionid={id}",addu:"admincp/subscriptions.php?do=adjust&subscriptionid={id}"};a(()=>{vBAdmin.initJumpControl("papi",b);vBAdmin.initJumpControl("sub",c)})})(jQuery);
