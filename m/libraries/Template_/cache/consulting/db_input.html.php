<?php /* Template_ 2.2.8 2017/09/21 15:38:49 /home/cmltd/html/m/views/consulting/db_input.html 000007884 */ ?>
<style>

label.btn span {
  font-size: 1.5em ;
}

label input[type="radio"] ~ i.fa.fa-circle-o{
    color: #c8c8c8;    display: inline;
}
label input[type="radio"] ~ i.fa.fa-dot-circle-o{
    display: none;
}
label input[type="radio"]:checked ~ i.fa.fa-circle-o{
    display: none;
}
label input[type="radio"]:checked ~ i.fa.fa-dot-circle-o{
    color: #7AA3CC;    display: inline;
}
label:hover input[type="radio"] ~ i.fa {
color: #7AA3CC;
}

/*
label input[type="checkbox"] ~ i.fa.fa-square-o{
    color: #c8c8c8;    display: inline;
}
label input[type="checkbox"] ~ i.fa.fa-check-square-o{
    display: none;
}
label input[type="checkbox"]:checked ~ i.fa.fa-square-o{
    display: none;
}
label input[type="checkbox"]:checked ~ i.fa.fa-check-square-o{
    color: #7AA3CC;    display: inline;
}
label:hover input[type="checkbox"] ~ i.fa {
color: #7AA3CC;
}
*/
div[data-toggle="buttons"] label.active{
    color: #7AA3CC;
}

div[data-toggle="buttons"] label {
display: inline-block;
padding: 6px 12px;
margin-bottom: 0;
font-size: 12px;
font-weight: normal;
line-height: 2em;
text-align: left;
white-space: nowrap;
vertical-align: top;
cursor: pointer;
background-color: none;
border: 0px solid
#c8c8c8;
border-radius: 3px;
color: #c8c8c8;
-webkit-user-select: none;
-moz-user-select: none;
-ms-user-select: none;
-o-user-select: none;
user-select: none;
}

div[data-toggle="buttons"] label:hover {
color: #7AA3CC;
}

div[data-toggle="buttons"] label:active, div[data-toggle="buttons"] label.active {
-webkit-box-shadow: none;
box-shadow: none;
}

</style>

<section class="panel panel-default">
	<div class="panel-heading"><strong><i class="fa fa-1 fa-th"></i> 신규DB입력</strong></div>
	<div class="panel-body">
		<div class="row">
			<div class="col-md-8 col-md-offset-2">

			<form id="FrmInput" class="form-horizontal ng-pristine ng-valid" role="form">
			<div class="form-group">
				<label for="inputName" class="col-sm-2 control-label">이름</label>
				<div class="col-sm-10">
					<input type="name" name="name" id="inputName" class="validate[required]"  data-errormessage-value-missing="이름을 입력하세요."  placeholder="이름">
				</div>
			</div>
			<!-- 20170320 kruddo : 입력 항목 추가 : 황옥진 팀장 요청 -->
			<div class="form-group">
				<label for="inputSex" class="col-sm-2 control-label">성별</label>
				<div data-toggle="buttons">
					<label class="btn">
					  <input type="radio" name='sex' value="F" class=""  data-errormessage-value-missing="성별을 선택 하세요."  placeholder="성별"><i class="fa fa-circle-o fa-2x"></i><i class="fa fa-dot-circle-o fa-2x"></i> <span>  여</span>
					</label>
					<label class="btn">
					  <input type="radio" name='sex' value="M" class="aleldj
					  "  data-errormessage-value-missing="성별을 선택 하세요."  placeholder="성별"><i class="fa fa-circle-o fa-2x"></i><i class="fa fa-dot-circle-o fa-2x"></i><span> 남</span>
					</label>
				</div>
			</div>
			<!-- 20170320 kruddo : 입력 항목 추가 : 황옥진 팀장 요청 -->

			<div class="form-group">
				<label for="inputTel" class="col-sm-2 control-label">연락처</label>
				<div class="col-sm-10">
					<input type="tel" name="tel" id="inputTel" class="validate[required, custom[onlyNumber], maxSize[11]]"  placeholder="연락처" data-errormessage-value-missing="연락처를 입력하세요." />
				</div>
			</div>
			<!-- 20170320 kruddo : 입력 항목 추가 : 황옥진 팀장 요청 -->
			<div class="form-group">
				<label for="inputCategory" class="col-sm-2 control-label">상담항목</label>
				<div class="col-sm-10">
					<span class="ui-select" style="width:100%">
					<select name="category" id="inputCategory">
						<option value="">==상담항목선택==</option>
<?php if(is_array($TPL_R1=$TPL_VAR["cfg"]["category"])&&!empty($TPL_R1)){foreach($TPL_R1 as $TPL_K1=>$TPL_V1){?>
						<option value="<?php echo $TPL_K1?>"><?php echo $TPL_V1?></option>
<?php }}?>
					</select>
					</span>
				</div>
			</div>
			<!-- 20170320 kruddo : 입력 항목 추가 : 황옥진 팀장 요청 -->

			<div class="form-group">
				<label for="inputPath" class="col-sm-2 control-label">유입경로</label>
				<div class="col-sm-10">
					<span class="ui-select" style="width:100%">
					<select name="path" id="inputPath" class="validate[required]" data-errormessage-value-missing="유입경로를 선택하세요.">
						<option value="">==유입경로선택==</option>
<?php if(is_array($TPL_R1=$TPL_VAR["cfg"]["path"])&&!empty($TPL_R1)){foreach($TPL_R1 as $TPL_K1=>$TPL_V1){?>
<?php if($TPL_K1=='L'||$TPL_K1=='O'||$TPL_K1=='P'||$TPL_K1=='K'){?>
						<option value="<?php echo $TPL_K1?>"><?php echo $TPL_V1?></option>
<?php }?>
<?php }}?>
					</select>
					</span>
				</div>
			</div>
			<div class="form-group">
				<label for="inputMedia" class="col-sm-2 control-label">미디어</label>
				<div class="col-sm-10">
					<span class="ui-select" style="width:100%">
					<select name="media" id="inputMedia" class="" >
						<option value="">==미디어선택==</option>
<?php if(is_array($TPL_R1=$TPL_VAR["cfg"]["media"])&&!empty($TPL_R1)){foreach($TPL_R1 as $TPL_V1){?>
						<option value="<?php echo $TPL_V1?>"><?php echo $TPL_V1?></option>
<?php }}?>
					</select>
					</span>
				</div>
			</div>
			<div class="form-group">
				<label for="inputMemo" class="col-sm-2 control-label">메모</label>
				<div class="col-sm-10">
					<textarea name="memo" id="inputMemo" placeholder="메모" style="height:100px"></textarea>
				</div>
			</div>

			<div class="form-group">
				<div class="col-sm-10">
				<button type="submit" class="btn btn-success btn-w-md">저장하기</button>
				<button type="button" class="btn btn-info btn-w-md" onclick="document.location.href='/'">메인</button>
				<button type="button" class="btn btn-primary btn-w-md" onclick="document.location.href='/consulting/lists'">목록</button>


				<button type="button" class="btn btn-danger btn-w-md" onclick="ConsultingInput.check()">중복검색</button>
				</div>
			</div>

			</form>
			</div>
		</div>
	</div>
</section>

<script type="text/javascript">
var ConsultingInput = {
	init: function() {
		var me = this;
		var option = $.extend({},validation_option, {
			display:'alert',
			showOneMessage:true,
			validationEventTrigger:'submit',
			onValidationComplete: function(form, status){
				if(status) me.save();
			}
		});
		$("#FrmInput").validationEngine('attach',option);
	},
	save: function() {
		if($('#inputPath').val() == 'L'){
			if($('#inputMedia').val() == ''){
				//HJAlert.alert("유입경로가 '온라인'이면 미디어를 선택 해 주세요.");
				//return;
			}
		}
		else{
			$('#inputMedia').val("");
		}

		var formdata = $('#FrmInput').serialize();
		$.ajax({
			url:'/Consulting_proc/db_input',
			data:formdata,
			dataType:'json',
			type:'POST',
			success:function(r) {
				HJAlert.alert(r.msg, function() {
					if(r.success) {
						//document.location.href='/Consulting/lists';
						document.location.href='/Consulting/db_input';
					}
					else {
						$("#FrmInput")[0].reset();
					}
				});


			}
		})
	},
	check: function() {
		var tel = $('#inputTel').val();
		if(!tel) {
			HJAlert.alert('연락처를 입력하세요.');
			return false;
		}

		$.ajax({
			url:'/Consulting_proc/db_check',
			data:{
				tel:tel
			},
			dataType:'json',
			type:'POST',
			success: function(r) {
				HJAlert.alert(r.msg);
			}
		})
	}
}

$(function() {
	ConsultingInput.init();
})
</script>