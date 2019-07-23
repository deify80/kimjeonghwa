<?php /* Template_ 2.2.8 2016/11/04 13:41:05 /home/cmltd/html/m/views/consulting/memo.html 000000586 */ 
$TPL_memo_1=empty($TPL_VAR["memo"])||!is_array($TPL_VAR["memo"])?0:count($TPL_VAR["memo"]);?>
<ul class="nav nav-boxed nav-justified">
<?php if($TPL_memo_1){foreach($TPL_VAR["memo"] as $TPL_V1){?>
	<li>
		<?php echo nl2br($TPL_V1["memo"])?>

		<div style="font-size:11px;color:#A2A2A2;text-align:right" class=""><?php echo $TPL_V1["name"]?> <?php echo $TPL_V1["reg_date"]?></div>
	</li>
<?php }}else{?>
	<li>
		등록된 메모가 없습니다.
	</li>
<?php }?>
</ul>