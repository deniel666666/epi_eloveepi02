$(document).ready(function(){
	"use strict";



/////////////////////// dropdown menu /////////////////////////
	$('.rwd_menu_sp').click(function(){
    	$(this).parent().find('ul').stop(true, true).slideToggle();
	});
/////////////////////// 排序顯示 ///////////////////////////
	$('.adj_order_btn').show();
	$('.send_order_btn').hide();
	$('.order_input').hide();
	$('.adj_order_btn').click(function(){
		$('.item_setting').hide();
		$('.order_input').show();
		$(this).hide();
		$('.send_order_btn').show();
		$('.send_order_btn').click(function(){
			$('.item_setting').show();
			$('.order_input').hide();
			$(this).hide();
			$('.adj_order_btn').show();
		})
	})
//////////////////////// slide menu //////////////////////////

    var menuW = $(".slide_menu .left").width();
	var rightW = $(".slide_content_r").width();
	$(".click_bar").click(function(){ //滑鼠點擊時

		if ($(".slide_menu").css('left') == '-'+menuW+'px'){
			$(".slide_menu").animate({ left:'0px' }, 450 ,'swing');
			if( window.screen.width>1500){
				$(".slide_content_r").animate({ width: rightW-menuW+'px' }, 450 ,'swing');
			}
			$(".black_glass").addClass("black_glass_show");
		}
	});
	$(".black_glass").click(function(){　//滑鼠點擊主內容後
		$(".slide_menu").animate( { left:'-'+menuW+'px' }, 450 ,'swing');
		$(".slide_content_r").animate( { width: rightW+'px' }, 450 ,'swing');
		$(".black_glass").removeClass("black_glass_show");
	});	

//////////////////////// 項目超過時顯示按鈕 /////////////////////////
	var element = document.querySelector(".scroll_box .menu");
	

	if( (element.offsetHeight < element.scrollHeight) || (element.offsetWidth < element.scrollWidth)){
	   // your element have overflow
	  $(".slide_left,.slide_right").css("display","inline-block");
	}
	else{
	  $(".slide_left,.slide_right").css("display","none");
	}
	// 點擊向右滑
	$(".slide_right").click(function () {
	  var leftPos = $('.scroll_box .menu').scrollLeft();
	  $(".scroll_box .menu").animate({scrollLeft: leftPos + 150}, 800);
	});
	// 點擊向左滑
	$(".slide_left").click(function () {
	  var leftPos = $('.scroll_box .menu').scrollLeft();
	  $(".scroll_box .menu").animate({scrollLeft: leftPos - 150}, 800);
	});

//////////////////////// CRM 右下選單超過顯示按鈕 /////////////////////////
	// 點擊向右滑
	$(".manageTabs .slide_right").click(function () {
		var leftPos = $('.scroll_box ul').scrollLeft();
		$(".scroll_box ul").animate({
			scrollLeft: leftPos + 380
		}, 800);
	});
	// 點擊向左滑
	$(".manageTabs .slide_left").click(function () {
		var leftPos = $('.scroll_box ul').scrollLeft();
		$(".scroll_box ul").animate({
			scrollLeft: leftPos - 380
		}, 800);
	});

//////////////////////// CRM 等級超過顯示按鈕 /////////////////////////

	// 點擊向右滑
	$(".scroll_right").click(function () {
		var leftmenubtn = $('.level-menu ul').scrollLeft();
		$(".level-menu ul").animate({
			scrollLeft: leftmenubtn + 150
		}, 800);
	});
	// 點擊向左滑
	$(".scroll_left").click(function () {
		var leftmenubtn = $('.level-menu ul').scrollLeft();
		$(".level-menu ul").animate({
			scrollLeft: leftmenubtn - 150
		}, 800);
	});



//////////////////////// 事件簿項目超過時顯示按鈕 /////////////////////////
// 點擊向右滑
$(".slide_right").click(function () {
	var leftPos = $('.events-nav .scroll_box .menu').scrollLeft();
	$(".events-nav .scroll_box .menu").animate({
		scrollLeft: leftPos + 150
	}, 800);
});
// 點擊向左滑
$(".slide_left").click(function () {
	var leftPos = $('.events-nav .scroll_box .menu').scrollLeft();
	$(".events-nav .scroll_box .menu").animate({
		scrollLeft: leftPos - 150
	}, 800);
});

 

////////////////// 主選單 //////////////////////////////////////////
	var bootsnav = {
		initialize: function () {
			this.hoverDropdown();
		},
		hoverDropdown: function () {
			var getNav = $(".bootsnav"),
				getWindow = $(window).width(),
				getIn = getNav.find("ul.nav").data("in"),
				getOut = getNav.find("ul.nav").data("out");
				if (getWindow < 1385) {

					// Disable mouseenter event
					$("nav.navbar.bootsnav ul.nav").find("li.dropdown").off("mouseenter");
					$("nav.navbar.bootsnav ul.nav").find("li.dropdown").off("mouseleave");
					$("nav.navbar.bootsnav ul.nav").find(".title").off("mouseenter");
					$("nav.navbar.bootsnav ul.nav").off("mouseleave");
					$(".navbar-collapse").removeClass("animated");



					$("nav.navbar.bootsnav ul.nav").each(function () {
						$(".dropdown-menu", this).removeClass(getOut);
						$("a.dropdown-toggle", this).off('click');
						$("a.dropdown-toggle", this).on('click', function (e) {
							e.stopPropagation();
							$(this).closest("li.dropdown").find(".dropdown-menu").first().stop().slideToggle().toggleClass(getIn);
							$(this).closest("li.dropdown").first().toggleClass("on");
							return false;
						});
						// Hidden dropdown action
						$('li.dropdown', this).each(function () {
							$(this).find(".dropdown-menu").stop().slideUp();
							$(this).on('hidden.bs.dropdown', function () {
								$(this).find(".dropdown-menu").stop().slideUp();
							});
							return false;
						});


					});



				} else if (getWindow > 1386) {

					$("nav.navbar.bootsnav ul.nav").each(function () {

						$("li.dropdown", this).on("mouseenter", function () {
							$(".dropdown-menu", this).eq(0).removeClass(getOut);
							$(".dropdown-menu", this).eq(0).stop().slideDown().addClass(getIn);
							$(this).addClass("on");
							return false;
						});

						$("li.dropdown", this).on("mouseleave", function () {
							$(".dropdown-menu", this).eq(0).removeClass(getIn);
							$(".dropdown-menu", this).eq(0).stop().slideUp().addClass(getOut);
							$(this).removeClass("on");
						});

						$(this).on("mouseleave", function () {
							$(".dropdown-menu", this).removeClass(getIn);
							$(".dropdown-menu", this).eq(0).stop().slideUp().addClass(getOut);
							$("li.dropdown", this).removeClass("on");
							return false;
						});
					});

				}
		}
	}
	
	$(document).ready(function () {
		bootsnav.initialize();
	});

	// Reset on resize
	$(window).on("resize", function () {
		bootsnav.hoverDropdown();
		$(".navbar-collapse").removeClass("in");
		$(".navbar-collapse").removeClass("on");
	});



	

});
