/**
	* 輪播動畫v1
	*
	* Requires menu.js and menu.css to be included in your page. And jQuery, obviously.
	*
	* Usage:
	*
	$('.work').NewMenu({
	icon:'/img/logo.png',
	width:$(document).width(),//物件寬
	height:$(document).height(),//物件高
	buttontitle:"確定",//按鈕字
	proportion:0.6,//比例
	icon_animate:400,//動畫速度
	icon_width:50,//icon大小
	items: [
	{label:'橋', background:'/img/1.jpg', href:'http://yahoo.com.tw'},
	{label:'夜景', background:'/img/2.jpg', href:'http://google.com'},
	{label:'衝浪', background:'/img/3.jpg', href:'http://youtube.com'},
	{label:'101', background:'/img/4.jpg', href:'#'},
	{label:'富士山', background:'/img/5.jpg', href:'#'},
	{label:'走道', background:'/img/6.jpg', href:'#'},
	]
	});
	*
	*
	*
	* - Micahael, 2016 
	
*/
jQuery.fn.NewForm = function(option) {
	var checkboxsrc="/Public/qhand/images/form/checkbox.png";
	var optionsrc="/Public/qhand/images/form/option.png";
	var deniedsrc="/Public/qhand/images/form/denied.png";
	var deletesrc="/Public/qhand/images/form/delete.png";
	var copysrc="/Public/qhand/images/form/copy.png";
	var select;
	var num=1;
	var mysrc;
	var menu;
	// 建立圖片輪播	
	function createMenu() {
		if(option.num){
		num=option.num;
		switch(option.type){
				case 'checkbox':
				mysrc=checkboxsrc;
				break;
				case 'option':
				mysrc=optionsrc;
				break;
				
			}
		}else{
			menu = $('<li class="li_list"><hr ></li>').appendTo(document.body);
			select = $('<ul class=ul_option></ul>').appendTo(menu);			
			var content = $('<input type="hidden" value="'+option.type+'" name="'+option.name+'_type" />"').appendTo(select);	
			var title = $('<li class="li_title"><input type="text" class="q_title" placeholder="問題" name="'+option.name+'_title"/></li>').appendTo(select);
			switch(option.type){
				case 'checkbox':
				var content = $('<input type="hidden" class="opdata" name="'+option.name+'_val" />"').appendTo(select);		
				var content = $('<li class="li_content" style="background-image:url('+checkboxsrc+')"><input class="'+option.name+'_val" type="text" value="選項'+num+'" /><img class="denied"src='+deniedsrc+' /></li>').appendTo(select);
				var content = $('<li class="li_content '+option.name+'_add" style="background-image:url('+checkboxsrc+')"><input type="text" placeholder="新增選項" /></li>').appendTo(select);
				mysrc=checkboxsrc;
				break;
				case 'option':
				var content = $('<input type="hidden" class="opdata" name="'+option.name+'_val" />"').appendTo(select);
				var content = $('<li class="li_content" style="background-image:url('+optionsrc+')"><input class="'+option.name+'_val" type="text" value="選項'+num+'" /><img class="denied"src='+deniedsrc+' /></li>').appendTo(select);
				var content = $('<li class="li_content '+option.name+'_add" style="background-image:url('+optionsrc+')"><input type="text" placeholder="新增選項" /></li>').appendTo(select);
				mysrc=optionsrc;
				break;
				case 'list':
				var content = $('<input type="hidden" class="opdata" name="'+option.name+'_val" />"').appendTo(select);
				var content = $('<li class="li_content" ><font class="'+option.name+'_mb">1</font>.<input class="'+option.name+'_val" type="text" value="選項'+num+'" /><img class="denied"src='+deniedsrc+' /></li>').appendTo(select);
				var content = $('<li class="li_content '+option.name+'_addlist"><font class='+option.name+'_mb>2</font>.<input type="text" placeholder="新增選項" /></li>').appendTo(select);
				mysrc=optionsrc;
				break;
				case 'text':
				var content = $('<li class="li_content"><input type="text" readonly="readonly" class="textbox" value="簡答文字" /></li>').appendTo(select);
				
				break;
				
			}
			
			var back = $('<li class="li_back"><img class="op_delete" src='+deletesrc+' /></li>').appendTo(select);
		}
		return menu;
	}
	//運作
	return this.each(function() {
		//初始設定
		$(this).append(createMenu());
		//新增選項按鈕
		$(this).on('click','.'+option.name+'_add',function(){
			num++;
			$(this).removeClass(option.name+'_add');
			$(this).find('input').val('選項'+num);
			$(this).find('input').addClass(option.name+'_val');
			$(this).append('<img class="denied"src='+deniedsrc+' />');
			$(this).after('<li class="li_content '+option.name+'_add" style="background-image:url('+mysrc+')"><input type="text" placeholder="新增選項" /></li>');
			
			upda();
		});
		//新增清單按鈕
		$(this).on('click','.'+option.name+'_addlist',function(){
			num++;
			$(this).removeClass(option.name+'_addlist');
			$(this).find('input').val('選項'+num);
			$(this).find('input').addClass(option.name+'_val');
			$(this).append('<img class="denied"src='+deniedsrc+' />');
			$(this).after('<li class="li_content '+option.name+'_addlist"><font class='+option.name+'_mb>2</font>.<input type="text" placeholder="新增選項" /></li>');
			var mbn=1;
			$("."+option.name+"_mb").each(function(){
				
				$(this).html(mbn++);
			}); 
			
		upda();
		});
		//刪除按鈕
		$(this).on('click','.denied',function(){
			num--;
			$(this).parent('li').remove();
			if(option.type=="list"){
				var mbn=1;
				$("."+option.name+"_mb").each(function(){
					
					$(this).html(mbn++);
				}); 
			}
			upda();
		});
		$(this).on('change','.'+option.name+'_val',function(){
			upda();
			
			
		});
		//刪除
		$(this).on('click','.op_delete',function(){
			$(this).parents('.li_list').remove();
			
		});
		function upda(){
			
			var box=[];
			var i=0;
			$("."+option.name+"_val").each(function(){
				box[i++]=$(this).val();
			}); 
			$('input[name='+option.name+'_val]').attr('value',box.join("@#"));
		}
	});
	return this;
};
