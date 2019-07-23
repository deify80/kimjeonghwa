<?php /* Template_ 2.2.8 2016/10/28 14:57:57 /home/hbps/html/www/views/intro/map.html 000002304 */ ?>
<style>
ul.tab {}
ul.tab li {float:left;border:solid 1px #000;padding:20px 0px;cursor:pointer;font-size:18px;width:270px;text-align: center;border-left:none;}
ul.tab li.on, ul.tab li:hover {background-color:#1E1E1E;color:#FFF;}
ul.tab li:first-child {
	border-left:solid 1px #000;
}
</style>
<div class="intro-title">찾아오시는길</div>
<div class="intro-title-map">HOME &gt; HB소개 &gt; 찾아오시는길</div>
<img src="<?php echo $TPL_VAR["layout"]["path"]["assets"]?>/images/intro/introduce_5_1.jpg">
<div style="width:1230px;margin-left:120px;margin-top:20px">
	<ul class="tab">
		<li class="on" data-link="tab_1">압구정로데오역에서 오실 때</li>
		<li data-link="tab_2">압구정역에서 오실 때</li>
		<li data-link="tab_3">지도보기</li>
	</ul>
	<div id="tab_1" class="tab-contents">
		<img src="<?php echo $TPL_VAR["layout"]["path"]["assets"]?>/images/intro/map1.jpg">
	</div>
	<div id="tab_2" class="hide tab-contents">
		<img src="<?php echo $TPL_VAR["layout"]["path"]["assets"]?>/images/intro/map2.jpg">
	</div>
	<div id="tab_3" class="hide tab-contents" style="height:564px;border:solid 1px #000000;width:1230px"></div>
</div>




<img src="<?php echo $TPL_VAR["layout"]["path"]["assets"]?>/images/intro/introduce_5_2.jpg?<?php echo TODAY?>">

<script type="text/javascript" src="https://openapi.map.naver.com/openapi/v3/maps.js?clientId=bsOaEifTXygYd4CA16Lv"></script>
<script type="text/javascript">


var IntroMap = {
	init: function() {

		var map = new naver.maps.Map('tab_3', {
			center: new naver.maps.LatLng(37.5285487, 127.0350113),
			zoom: 11,
			scaleControl: false,
			logoControl: false,
			mapDataControl: false,
			zoomControl: true,

		});

		var marker = new naver.maps.Marker({
			position: new naver.maps.LatLng(37.5285487, 127.0350113),
			map: map
		});

		$('ul.tab li').on('click',function(){
			IntroMap.tab(this);
		})
	},
	tab: function(e) {
		$('ul.tab li').removeClass('on');
		$(e).addClass('on');
		var id = $(e).data('link');

		$('div.tab-contents').addClass('hide');
		$('div#'+id).removeClass('hide');

	}
}

$(function(){
	IntroMap.init();
})

</script>