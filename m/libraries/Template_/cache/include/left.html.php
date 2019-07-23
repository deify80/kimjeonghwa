<?php /* Template_ 2.2.8 2016/10/28 10:18:02 /home/hbps/html/www/views/include/left.html 000005446 */ ?>
<div id="left">
	<div class="left-menu" style="overflow:hidden">
		<div class="logo">
			<a href="/"><img src="<?php echo $TPL_VAR["layout"]["path"]["assets"]?>/images/left/logo.jpg" /></a>
			<ul class="mini">
				<li><a href="/member/login">LOGIN</a></li>
				<li style="border:none"><a href="/member/join">JOIN</a></li>
			</ul>
		</div>
		<ul class="menu" id="m1">
<?php if(is_array($TPL_R1=$TPL_VAR["layout"]["menu"])&&!empty($TPL_R1)){foreach($TPL_R1 as $TPL_V1){?>
			<li class="<?php if($TPL_VAR["layout"]["page"]["m1"]==$TPL_V1["id"]){?>selected<?php }?>" data-on="<?php if($TPL_VAR["layout"]["page"]["m1"]==$TPL_V1["id"]){?>yes<?php }else{?>no<?php }?>" data-id="<?php echo $TPL_V1["id"]?>" data-top='<?php echo $TPL_V1["top"]?>'><?php echo $TPL_V1["name"]?></li>
<?php }}?>
		</ul>
	</div>
	<div id="menu_sub" class="left-menu-sub"  style="overflow:hidden;padding-top:<?php echo $TPL_VAR["layout"]["menu"][$TPL_VAR["layout"]["page"]["m1"]]["top"]?>px">
		<div class="left-title">
			에이치비
			<div id="title"><?php echo $TPL_VAR["layout"]["menu"][$TPL_VAR["layout"]["page"]["m1"]]["name"]?></div>
			<div style="margin-top:8px;font-size:12px;letter-spacing:2px;font-weight:200">HB Plasticsurgery</div>
		</div>

		<ul id="m2" class="menu-sub">
<?php if(is_array($TPL_R1=$TPL_VAR["layout"]["menu"])&&!empty($TPL_R1)){foreach($TPL_R1 as $TPL_V1){?>
<?php if(is_array($TPL_R2=$TPL_V1["children"])&&!empty($TPL_R2)){foreach($TPL_R2 as $TPL_V2){?>
				<li style="<?php if($TPL_VAR["layout"]["page"]["m1"]!=$TPL_V1["id"]){?>display:none<?php }?>" class="<?php if($TPL_VAR["layout"]["page"]["m2"]==$TPL_V2["id"]){?>selected<?php }?> <?php if(!$TPL_V2["href"]){?>leaf<?php }?>" data-group="<?php echo $TPL_V1["id"]?>" data-id="<?php echo $TPL_V2["id"]?>" data-leaf="<?php if($TPL_V2["href"]){?>yes<?php }else{?>no<?php }?>" data-on="<?php if($TPL_VAR["layout"]["page"]["m2"]==$TPL_V2["id"]){?>yes<?php }else{?>no<?php }?>">
					<div onclick="Left.href('<?php echo $TPL_V2["href"]?>')"><?php echo $TPL_V2["name"]?></div>
<?php if($TPL_V2["children"]){?>
					<ul style="<?php if($TPL_VAR["layout"]["page"]["m2"]!=$TPL_V2["id"]){?>display:none<?php }?>" data-on="<?php if($TPL_VAR["layout"]["page"]["m2"]==$TPL_V2["id"]){?>yes<?php }?>" data-parent="<?php echo $TPL_V2["id"]?>" class="m3">
<?php if(is_array($TPL_R3=$TPL_V2["children"])&&!empty($TPL_R3)){foreach($TPL_R3 as $TPL_V3){?>
						<li onclick="Left.href('<?php echo $TPL_V3["href"]?>')" class="<?php if($TPL_VAR["layout"]["page"]["m3"]==$TPL_V3["id"]){?>selected<?php }?>"><?php echo $TPL_V3["name"]?></li>
<?php }}?>
					</ul>
<?php }?>
				</li>
<?php }}?>
<?php }}?>
		</ul>
	</div>
</div>

<script type="text/javascript">
var Left = {
	m1:'<?php echo $TPL_VAR["layout"]["page"]["m1"]?>',
	m2:'<?php echo $TPL_VAR["layout"]["page"]["m2"]?>',
	m3:'<?php echo $TPL_VAR["layout"]["page"]["m3"]?>',
	init: function() {
		$(".left-menu").mousewheel(function(e) {
			var scrollgap = 30;
			var top = $(this).scrollTop() - e.deltaY * scrollgap ;
			if(top < 0) top = 0;
			$(this).scrollTop(top);
			return false;
		});

		$("#menu_sub").mousewheel(function(e) {
			var scrollgap = 30;
			var top = $(this).scrollTop() - e.deltaY * scrollgap ;
			if(top < 0) top = 0;
			$(this).scrollTop(top);
			return false;
		});

		$('#m1 > li').on('mouseenter', function(event, e) {
			$('#menu_sub').animate({
				left: 210
			},300)

			var e = $(this);
			e.siblings('li').removeClass('selected');
			e.addClass('selected');


			$('#menu_sub').css('paddingTop', e.data('top'));
			var group = e.data('id');
			$('.menu-sub > li').css('display','none');//addClass('hide');
			$('.menu-sub > li').filter('[data-group="'+group+'"]').fadeIn(300);//removeClass('hide');
			$('#title').html(e.text());
		});

		$('#m2 > li[data-leaf="no"]').on('mouseenter', function() {
			var group = $(this).data('id');
			var m3 = $('ul.m3[data-parent="'+group+'"]');
			m3.slideDown(500);

			$('ul.m3[data-parent!="'+group+'"]').slideUp(500);
		});

		$('#m2 > li').on('mouseenter', function() {
			$('#m2 > li').removeClass('selected');
			$(this).addClass('selected');
			if($(this).data('leaf')=='yes') {
				$('ul.m3').slideUp(500);
			}
		});

		$('.main #left').on('mouseleave',function() {
			$('#menu_sub').animate({
				left: 60
			},300, function() {
				$('#m1 > li').removeClass('selected');
			})
		});

		$('.sub #left').on('mouseleave',function() {
			$('#m1 > li').removeClass("selected");
			$('#m1 > li[data-on="yes"]').addClass("selected");

			$('#m2 > li[data-group!="'+Left.m1+'"]').css('display','none');
			$('#m2 > li').removeClass('selected');

			$('#m2 > li[data-group="'+Left.m1+'"]').not(":visible").fadeIn(300);
			$('#m2 > li[data-on="yes"]').addClass('selected');

			var top = $('#m1 > li[data-on="yes"]').data('top');
			$('#menu_sub').css('paddingTop', top);

			var m3 = $('ul[data-parent="'+Left.m2+'"]');
			if(m3.not(':visible')) {
				$('ul[data-parent="'+Left.m2+'"]').slideDown(500);
			}

			$('ul.m3[data-parent!="'+Left.m2+'"]').slideUp(500);

			$('#title').html($('#m1 > li.selected').html());
		});
	},
	href: function(href) {
		if(href) document.location.href=href;
	}
}

$(function(){
	Left.init();
})
</script>