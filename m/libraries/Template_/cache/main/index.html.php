<?php /* Template_ 2.2.8 2016/10/28 17:30:57 /home/hbps/html/www/views/main/index.html 000006082 */ 
$TPL_visual_1=empty($TPL_VAR["visual"])||!is_array($TPL_VAR["visual"])?0:count($TPL_VAR["visual"]);?>
<!-- 메인:S -->
<style>
#visual_wrap {}
#visual_wrap > li {position:absolute;top:0;left:0;}
#visual_wrap > li > img {width:100%;}
</style>
<div style="position:relative;">
	<div><img src="<?php echo $TPL_VAR["layout"]["path"]["assets"]?>/images/main/main_visual_bg.png" style="width:100%"/></div>
	<ul id="visual_wrap">
<?php if($TPL_visual_1){$TPL_I1=-1;foreach($TPL_VAR["visual"] as $TPL_V1){$TPL_I1++;?>
		<li id="visual_<?php echo $TPL_I1?>" style="<?php if($TPL_I1> 0){?>display:none<?php }?>" ><img src="<?php echo $TPL_VAR["layout"]["path"]["assets"]?>/images/<?php echo $TPL_V1["image"]?>?11" onclick="Index.link('<?php echo $TPL_V1["href"]?>')" style="cursor:pointer" /></li>
<?php }}?>
	</ul>
</div>

<div class="main-contents">
	<div class="control">
		<div class="arrow left" onclick="Index.VisualMove('prev')"><img src="<?php echo $TPL_VAR["layout"]["path"]["assets"]?>/images/main/banner_prev.png" /></div>
		<ul class="main-bar" id="main_control">
<?php if($TPL_visual_1){$TPL_I1=-1;foreach($TPL_VAR["visual"] as $TPL_V1){$TPL_I1++;?>
			<li <?php if($TPL_I1== 0){?>class="selected"<?php }?> ><?php echo $TPL_V1["title"]?></li>
<?php }}?>
		</ul>
		<div class="arrow right" onclick="Index.VisualMove('next')"><img src="<?php echo $TPL_VAR["layout"]["path"]["assets"]?>/images/main/banner_next.png" /></div>
	</div>

	<div style="margin-top:35px;height:270px">
		<ul class="inline main_1">
			<li><a href="/intro/hospital"><img src="<?php echo $TPL_VAR["layout"]["path"]["assets"]?>/images/main/ban1.jpg" class="trans" /></a></li>
			<li><a href="/intro/doctor"><img src="<?php echo $TPL_VAR["layout"]["path"]["assets"]?>/images/main/ban2.jpg" class="trans" /></a></li>
			<li><a href="/info/body/2"><img src="<?php echo $TPL_VAR["layout"]["path"]["assets"]?>/images/main/ban3.jpg" class="trans" /></a></li>
			<li><a href="/info/hair/1"><img src="<?php echo $TPL_VAR["layout"]["path"]["assets"]?>/images/main/ban4.jpg" class="trans"/></a></li>
	</div>
	<div style="margin-top:10px;height:350px">
		<ul class="inline main_1">
			<li><img src="<?php echo $TPL_VAR["layout"]["path"]["assets"]?>/images/main/ban5.jpg" /></li>
			<li><img src="<?php echo $TPL_VAR["layout"]["path"]["assets"]?>/images/main/ban6.jpg" /></li>
		</ul>
	</div>

	<div style="margin-top:60px;height:362px">
		<ul class="inline main_1">
			<li><img src="<?php echo $TPL_VAR["layout"]["path"]["assets"]?>/images/main/ban7.jpg" /></li>
		</ul>
	</div>
</div>

<div style="margin-top:60px;padding:60px 0px;background-color:#F3F3F3">
	<div class="main-contents">
		<ul class="inline main_1" style="height:290px">
			<li onclick="document.location.href='/community/notice'" style="cursor:pointer">
				<img src="<?php echo $TPL_VAR["layout"]["path"]["assets"]?>/images/main/ban8.jpg" />
				<div style="width:100%;text-align:center;margin-top:10px">
					<span style="font-size:30px;color:#53524D;">HB공지사항</span>
					<div style="color:#8F8F8F">HB성형외과 소식을 만날 수 있습니다</div>
				</div>
			</li>
			<li onclick="document.location.href='/community/realstory'" style="cursor:pointer">
				<img src="<?php echo $TPL_VAR["layout"]["path"]["assets"]?>/images/main/ban9.jpg" />
				<div style="width:100%;text-align:center;margin-top:10px">
					<span style="font-size:30px;color:#53524D;">리얼스토리</span>
					<div style="color:#8F8F8F">그녀들의 후회없는 선택, 그 리얼한 이야기!</div>
				</div>
			</li>
			<li onclick="document.location.href='/community/online'" style="cursor:pointer">
				<img src="<?php echo $TPL_VAR["layout"]["path"]["assets"]?>/images/main/ban10.jpg" />
				<div style="width:100%;text-align:center;margin-top:10px">
					<span style="font-size:30px;color:#53524D;">온라인상담</span>
					<div style="color:#8F8F8F">고민스러운 성형, 부담없이 상담받아 보세요.</div>
				</div>
			</li>
		</ul>
	</div>
</div>

<script type="text/javascript">
var Index = {
	rolling:'',
	init: function() {
		$('#main_control >li').on('click', function(){
			Index.VisualSelect(this);
		})

		Index.rolling = setInterval(Index.VisualRolling,4000);
		$('#visual_wrap').on('mouseenter', function() {
			clearInterval(Index.rolling);
		});
		$("#visual_wrap").on('mouseleave', function() {
			Index.rolling = setInterval(Index.VisualRolling,4000);
		});

		$('img.trans').on('mouseenter', function() {
			$(this).css('opacity','.7');
		});

		$('img.trans').on('mouseleave', function() {
			$(this).css('opacity','1');
		})
	},
	link: function(href) {
		document.location.href=href;
	},
	VisualMove: function(direction) {

		var selected = $('#main_control >li.selected');
		switch(direction) {
			case 'prev':
				var li_prev = selected.prev();
				var idx = $('#main_control >li').index(li_prev);
				if(idx<0) {
					li_prev = $('#main_control >li:last');
				}
				this.VisualSelect(li_prev[0]);
			break;
			case 'next':
				var li_next = selected.next();
				var idx = $('#main_control >li').index(li_next);
				if(idx>5) {
					$('#main_control >li:first').hide();
				}
				else if(idx < 0) {
					li_next = $('#main_control >li:first');
				}
				this.VisualSelect(li_next[0]);
			break;
		}
	},
	VisualSelect: function(e) {
		clearInterval(Index.rolling);
		var idx = $('#main_control >li').index(e);
		$('#main_control >li.selected').removeClass('selected');
		$(e).addClass('selected');

		//이미지 변경
		$('#visual_wrap > li:visible').fadeOut(1000);
		$('#visual_'+idx).fadeIn(1000);

		Index.rolling = setInterval(function() {
			Index.VisualMove('next');
		},4000);
	},
	VisualRolling: function() {
		Index.VisualMove('next');
	},
	VisualRollingStop: function() {
		clearInterval(this.timer);
	},

}

$(function(){
	Index.init();
})
</script>