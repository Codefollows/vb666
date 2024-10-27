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
vBulletin.ensureObj("contentslider");
(function(d){function g(){d(".js-content-slider").each(function(){var a=d(this),b=a.closest(".js-content-slider-wrapper"),c=b.innerWidth();b.find("> .js-content-slider > div").width(c);a.width(c);a.find(".js-content-slider__slides").width(c);a.find(".b-content-slider__slide").width(c);a=a.attr("id");e&&"undefined"!=typeof e[a]&&e[a].$ScaleWidth(c)})}function h(a){a=d(a.target);a.is(".b-content-slider__slide, .b-content-slider__title, .ellipsis")&&(a=a.closest(".b-content-slider__slide").find(".js-content-slider__link"),document.location.href=
a.attr("href"))}var e={};d(function(){d(".js-content-slider").each(function(){var a=d(this),b=a.closest(".js-content-slider-wrapper").innerWidth();a.width(b);a.find(".js-content-slider__slides").width(b);b=a.data("config-height");b=parseInt(b,10);if(0<b){a.height(b);a.find(".js-content-slider__slides").height(b);a.closest(".js-content-slider-wrapper").height(b);var c=a.find(".js-content-slider__slide-caption-background").outerHeight();b=b>c?parseInt(.15*b,10):0;c=b+5;var f=b+2;a.find(".js-content-slider__slide-caption").css("bottom",
c+"px");a.find(".js-content-slider__slide-caption-background").css("bottom",b+"px");a.find(".js-content-slider__arrow").css("bottom",f+"px")}b=a.attr("id");c=d("#"+b).data("config-interval");c=d.isNumeric(c)?Math.floor(1E3*c):3E3;e[b]=new $JssorSlider$(b,{$AutoPlay:!0,$AutoPlayInterval:c,$Idle:c,$SlideDuration:650,$ArrowKeyNavigation:!0,$PauseOnHover:1,$BulletNavigatorOptions:{$Class:$JssorBulletNavigator$,$ChanceToShow:2,$ActionMode:1,$AutoCenter:0,$Orientation:1},$ArrowNavigatorOptions:{$Class:$JssorArrowNavigator$,
$ChanceToShow:2,$AutoCenter:0,$Steps:1}});b=a.data("config-height");b=parseInt(b,10);if(0<b&&120>b){b=a.find(".js-content-slider__arrow--right");c=vBulletin.isRtl()?"left":"right";f=parseInt(b.css(c),10);var k=a.find(".js-content-slider__bullets").outerWidth();b.css(c,k+2*f)}d(".js-content-slider__slide",a).off("click",h).on("click",h);vBulletin.Responsive.Debounce.registerCallback(g);a.closest(".js-content-slider-wrapper").hide().removeClass("b-content-slider__wrapper--hidden").fadeIn("slow")})});
vBulletin.contentslider.handleWindowResize=g})(jQuery);
