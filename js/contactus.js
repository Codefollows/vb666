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
vBulletin.precache(["bademail","contact_us","nosubject","please_complete_required_fields","sentfeedback"],[]);
(function(b){b(function(){setTimeout(vBulletin.hv.reset,0);b("form.contactusForm").submit(function(h){h.preventDefault();var k=["name","email","subject","other_subject","message"],d={},e={},f,g=!0;b.each(b(this).serializeArray(),function(c,a){if(-1!=b.inArray(a.name,k)){if("other_subject"!=a.name&&0==b.trim(a.value).length)return g=!1;d[a.name]=a.value}else if(f=/^humanverify\[([^\]]*)\]/.exec(a.name))e[f[1]]=a.value});if(!g)return vBulletin.warning("contact_us","please_complete_required_fields"),
!1;vBulletin.AJAX({call:"/ajax/api/contactus/sendMail",data:{maildata:d,hvinput:e},success:function(c){vBulletin.alert("contact_us","sentfeedback",!1,function(){window.location.href=pageData.baseurl})},api_error:function(c){b.each(c,function(a,l){if(/^humanverify_/.test(c[0]))return vBulletin.hv.reset(!0),!1});return!0},title_phrase:"contact_us",error_phrase:"invalid_server_response_please_try_again"});return!1})})})(jQuery);
