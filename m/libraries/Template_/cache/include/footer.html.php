<?php /* Template_ 2.2.8 2016/10/25 15:24:47 /home/hbps/html/www/views/include/footer.html 000004150 */ ?>
<div class="footer">
	<div class="footer-contents">
		<div class="footer-info">
			<img src="<?php echo $TPL_VAR["layout"]["path"]["assets"]?>/images/footer/copy_info.jpg" />
			<div class="footer-db">
				<h2 style="font-weight:normal;color:#1f232f">빠르고 편하게 <span style="font-weight:700;">상담</span>받으세요<span style="font-style:italic;">!</span></h2>

				<form id="FrmFooter">
				<input type="hidden" name="media_code" value="homeO" />
				<input type="hidden" name="url" value="<?php echo $TPL_VAR["layout"]["page"]["url"]?>" />
				<input type="hidden" name="hope_date" value="" />
				<input type="hidden" name="age" value="" />
				<input type="hidden" name="gender" value="" />
				<div style="margin-top:15px">
					<div style="width:175px;float:left">
						<div><input type="text" name="name" value="" class="validate[required] input-footer" placeholder="이름" data-errormessage-value-missing="이름을 입력하세요." /></div>
						<div style="margin:5px 0px"><input type="text" name="tel" value="" class="validate[required] input-footer" placeholder="연락처" data-errormessage-value-missing="연락처를 입력하세요."/></div>
						<div>
							<select name="category" class="validate[required] input-footer" data-errormessage-value-missing="시술부위를 선택하세요."/>
								<option value="" disabled selected style="color:#DBCFC1">시술부위</option>
<?php if(is_array($TPL_R1=$TPL_VAR["layout"]["api"]["category"])&&!empty($TPL_R1)){foreach($TPL_R1 as $TPL_K1=>$TPL_V1){?>
								<option value="<?php echo $TPL_K1?>"><?php echo $TPL_V1?></option>
<?php }}?>
							</select>
						</div>
						<div style="margin-top:3px"><label><input type="checkbox" name="" value="" class="hj footer" checked /><span class="lbl" style="padding:0px;font-size:12px;vertical-align: 2px" > 개인정보처리방침동의 <a href="javascript:;" onclick="Footer.cpa.agree()" style="color:#fff">[보기]</a></span></label></div>
					</div>
					<div style="width:130px;float:left;margin:0px 5px">
						<textarea class="input-footer" name="memo" style="height:115px" placeholder="문의내용"></textarea>
					</div>
					<div><input type="image" src="<?php echo $TPL_VAR["layout"]["path"]["assets"]?>/images/footer/db_btn.jpg" /></div>
				</div>
				</form>
			</div>
		</div>
		<hr class="footer-line"></hr>
		<div class="sitemap">
<?php if(is_array($TPL_R1=$TPL_VAR["layout"]["menu"])&&!empty($TPL_R1)){foreach($TPL_R1 as $TPL_V1){?>
			<div style="float:left;width:193.3px">
				<ul style="margin-bottom:20px">
					<li class="sitemap_1"><?php echo $TPL_V1["name"]?></li>
<?php if(is_array($TPL_R2=$TPL_V1["children"])&&!empty($TPL_R2)){foreach($TPL_R2 as $TPL_V2){?>
					<li ><a href="<?php echo $TPL_V2["href"]?>" class="sitemap_2"><?php echo $TPL_V2["name"]?></a></li>
<?php }}?>
				</ul>
			</div>
<?php }}?>
			<div style="clear:both"></div>
		</div>
		<hr class="footer-line"></hr>
		<div class="copyright"><img src="<?php echo $TPL_VAR["layout"]["path"]["assets"]?>/images/footer/copyright.jpg" /></div>
	</div>
</div>

<div id="agree" class="agree-wrap">
	<div class="agree-box">
		<div class="agree-title">개인정보처리방침
			<div style="position:absolute;right:0px;top:-5px;" ><i class="fa fa-times" style="font-size:18px;cursor:pointer" onclick="Footer.cpa.agree()"></i></div>
		</div>
		<div class="agree-contents">
			<?php echo $TPL_VAR["layout"]["cfg"]["privacy"]?>

		</div>
	</div>
</div>
<div id="backdrop" class="modal-backdrop"></div>

<script type="text/javascript">
var Footer = {
	init: function() {
		var me = this;
		me.cpa =  new CPA('FrmFooter');
		var option = $.extend({},validation_option, {
			display:'alert',
			showOneMessage:true,
			validationEventTrigger:'submit',
			onValidationComplete: function(form, status){
				if(status) me.cpa.save();
			}
		});
		$("#FrmFooter").validationEngine('attach',option);
	}
}

$(function() {
	Footer.init();
})
</script>