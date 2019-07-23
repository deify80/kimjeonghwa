<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class X_lib {
	private $ci;
	function __construct() {
		$this->ci =& get_instance();
	}

	/**
	 * 환자숨김
	 * @return [type] [description]
	 */
	function x() {
		$CI = $this->ci;

		$this->dbdump('x');

		$sql = "SELECT no, cst_seqno FROM patient WHERE is_x='Y' AND is_delete='N'";
		$rs = $CI->adodb->Execute($sql);
		while($row  = $rs->FetchRow()) {
			$cst_seqno = $row['cst_seqno'];
			$patient_no = $row['no'];
			$db_seqno = $CI->adodb->getOne("SELECT db_seqno FROM consulting_info WHERE cst_seqno='{$cst_seqno}'");

			$CI->adodb->Execute("UPDATE db_info SET is_x='Y' WHERE db_seqno='{$db_seqno}'");
			$CI->adodb->Execute("UPDATE consulting_info SET is_x='Y' WHERE cst_seqno='{$cst_seqno}'");
		}

		$CI->db->query("UPDATE patient SET is_delete_x='Y', is_delete='Y' WHERE is_x='Y' AND is_delete='N'");


	}

	/**
	 * 환자복원
	 */
	function o() {
		$CI = $this->ci;
		$CI->db->query("UPDATE patient SET is_delete='N' WHERE is_delete_x='Y'");
		$CI->db->query("UPDATE patient SET is_delete_x='N'");
		$CI->db->query("UPDATE consulting_info SET is_x='N' WHERE is_x!='N'");
		$CI->db->query("UPDATE db_info SET is_x='N' WHERE is_x!='N'");

		//실작업
	}

	//금액숨김
	function cash_x() {
		$CI = $this->ci;
		$adodb = $CI->adodb;

		$this->dbdump('xcash');

		$sql = "SELECT * FROM patient_pay WHERE amount_paid_cash_x > 0 || amount_refund_cash_x > 0";
		$rs = $adodb->Execute($sql);
		$project_no_arr = array();
		while($row  = $rs->FetchRow()) {
			$pay_no = $row['no'];
			$pay_rs = $adodb->Execute("UPDATE patient_pay SET amount_paid_cash=amount_paid_cash_o, amount_refund_cash=amount_refund_cash_o, amount_paid=amount_paid-amount_paid_cash_x WHERE no='{$pay_no}' AND is_delete='N'");
			if($pay_rs) {
				$project_no = $row['project_no'];
				if(array_key_exists($project_no, $project_no_arr)) {
					$project_no_arr[$project_no]+=$row['amount_paid_cash_x'];
				}
				else {
					$project_no_arr[$project_no]=$row['amount_paid_cash_x'];
				}
			}
		}

		$project_no_arr = array_unique(array_filter($project_no_arr));
		$this->_cash_sync($project_no_arr, 'x');
	}

	/**
	 * 실제 데이터 삭제
	 * 반드시 db백업후에 실행할것
	 * mysqldump -uroot -p cmltd > cmltd.20171115_1419.sql
	 * @return [type] [description]
	 */
	public function x_real() {

		exit;
		$CI = $this->ci;
		$adodb = $CI->adodb;

		$this->dbdump('xreal');

		//특이사항 삭제
		$adodb->Execute("UPDATE patient_pay SET comment=''");

		//x삭제
		$adodb->Execute("UPDATE patient_pay SET amount_paid_cash_x=0, amount_refund_cash_x=0 WHERE amount_paid_cash_x>0 OR amount_refund_cash_x>0");


		//환자정보 삭제
		$sql = "SELECT no, cst_seqno FROM patient WHERE is_x='Y' AND is_delete_x='Y'";
		$rs = $CI->adodb->Execute($sql);
		while($row  = $rs->FetchRow()) {
			$patient_no = $row['no'];
			$adodb->Execute("DELETE FROM patient_agree WHERE patient_no='{$patient_no}'"); //동의서 삭제
			$adodb->Execute("DELETE FROM patient_appointment WHERE patient_no='{$patient_no}'");
			$adodb->Execute("DELETE FROM patient_chart WHERE patient_no='{$patient_no}'");
			$adodb->Execute("DELETE FROM patient_complain WHERE patient_no='{$patient_no}'");
			$adodb->Execute("DELETE FROM patient_consulting WHERE patient_no='{$patient_no}'");
			$adodb->Execute("DELETE FROM patient_doctor WHERE patient_no='{$patient_no}'");
			$adodb->Execute("DELETE FROM patient_material WHERE patient_no='{$patient_no}'");
			$adodb->Execute("DELETE FROM patient_nurse WHERE patient_no='{$patient_no}'");
			$adodb->Execute("DELETE FROM patient_pay WHERE patient_no='{$patient_no}'");
			$adodb->Execute("DELETE FROM patient_photo WHERE patient_no='{$patient_no}'");
			$adodb->Execute("DELETE FROM patient_project WHERE patient_no='{$patient_no}'");
			$adodb->Execute("DELETE FROM patient_skin WHERE patient_no='{$patient_no}'");
			$adodb->Execute("DELETE FROM patient_treat WHERE patient_no='{$patient_no}'");

			$adodb->Execute("DELETE FROM patient WHERE no='{$patient_no}'");
		}


		//팀DB삭제
		$adodb->Execute("DELETE FROM db_info WHERE is_x='Y'");
		$adodb->Execute("DELETE FROM consulting_info WHERE is_x='Y'");
		//echo '삭제완료2';
	}

	function dbdump($prefix) {
		$dbname = $this->ci->db->database;
		$command = "mysqldump -u".$this->ci->db->username."  -p".$this->ci->db->password." ".$this->ci->db->database." > /home/{$dbname}/backup/db/{$prefix}_{$dbname}.".date('Ymd_Hi').".sql";
		exec($command, $output, $return_var);
	}

	//금액복원
	function cash_o() {
		$CI = $this->ci;
		$adodb = $CI->adodb;
		$sql = "SELECT * FROM patient_pay WHERE amount_paid_cash_x > 0 || amount_refund_cash_x > 0";
		$rs = $adodb->Execute($sql);
		$project_no_arr = array();
		while($row  = $rs->FetchRow()) {

			$pay_no = $row['no'];
			//$amount_paid_cash = $row[''] ++ ''
			$pay_rs = $adodb->Execute("UPDATE patient_pay SET amount_paid_cash=amount_paid_cash_o+ amount_paid_cash_x, amount_refund_cash=amount_refund_cash_o+amount_refund_cash_x, amount_paid=amount_paid+amount_paid_cash_x WHERE no='{$pay_no}'");
			if($pay_rs) {
				$project_no = $row['project_no'];
				if(array_key_exists($project_no, $project_no_arr) === false) {
					$project_no_arr[$project_no]+=$row['amount_paid_cash_x'];
				}
				else {
					$project_no_arr[$project_no]=$row['amount_paid_cash_x'];
				}
			}
		}
		$project_no_arr = array_unique(array_filter($project_no_arr));
		$this->_cash_sync($project_no_arr, 'o');
	}

	/**
	 * @todo 미수금조정, 매출액(amount_basic) 미조정건 발생하므로 보완해야함. 2018-09-05 by hjlee
	 * UPDATE patient_project p, ( select project_no, sum(amount_paid_cash_x) as sum_x from patient_pay_20180831 where  amount_paid_cash_x>0 and is_delete='N' group by project_no) as x SET p.sum_x=x.sum_x where p.no=x.project_no
	 *
	 * @param [type] $project_no_arr
	 * @param [type] $mode
	 * @return void
	 */
	private function _cash_sync($project_no_arr, $mode) {
		if(!is_array($project_no_arr)) return false;

		$CI = $this->ci;
		$CI->load->model('Patient_Model');

		$patient_no_arr = array();

		foreach($project_no_arr as $project_no => $x) {
			$project_info = $CI->Patient_Model->select_patient_row(array('no'=>$project_no), 'patient_project', 'patient_no, amount_basic');
			$sum_field = "SUM(amount_paid) AS paid_total,
				SUM(amount_paid_cash) AS paid_cash,
				SUM(amount_paid_card) AS paid_card,
				SUM(amount_paid_bank) AS paid_bank,
				SUM(amount_refund) AS refund_total,
				SUM(amount_refund_cash) AS refund_cash,
				SUM(amount_refund_card) AS refund_card,
				SUM(amount_refund_bank) AS refund_bank,

				SUM(IF(calc_type='addition', amount_paid, 0)) AS amount_addition";

			$sum = $CI->Patient_Model->select_widget_row('patient_pay',array('project_no'=>$project_no, 'is_delete'=>'N'), $sum_field);

			if($mode == 'x') {
				$amount_basic = $project_info['amount_basic']-$x;
				//if($amount_basic<0) $amount_basic=0;
				$amount_op = $project_info['amount_op']-$x;
				//if($amount_op<0) $amount_op=0;
			}
			else {
				$amount_basic = $project_info['amount_basic']+$x;
				$amount_op = $project_info['amount_op']+$x;
			}

			$record = array(
				'amount_basic'=>$amount_basic, //매출액 조정
				'amount_op'=>$amount_op, //매출액 조정
				'amount_addition'=>$sum['amount_addition'], //추가매출액
				'amount_refund'=>$sum['amount_refund'], //환불액
				'amount_unpaid'=>($amount_basic-$sum['paid_total']+$sum['amount_addition']+$sum['amount_refund']), //미수금
				'paid_total'=>($sum['paid_total']-$sum['refund_total']), //총수납액
				'paid_cash'=>($sum['paid_cash']-$sum['refund_cash']), //현금수납액
				'paid_card'=>($sum['paid_card']-$sum['refund_card']), //카드수납액
				'paid_bank'=>($sum['paid_bank']-$sum['refund_bank']) //계좌이체수납액
			);

			$CI->Patient_Model->update_patient($record, array('no'=>$project_no), 'patient_project');

			$patient_no_arr[] = $project_info['patient_no'];
		}

		foreach($patient_no_arr as $patient_no) {
			//환자 총 매출액
			$sum = $CI->Patient_Model->select_widget_row('patient_pay',array('patient_no'=>$patient_no, 'is_delete'=>'N'), "SUM(amount_paid) AS amount_paid");
			$amount_paid = $sum['amount_paid'];

			$grade_list = $CI->Patient_Model->select_patient_all(array('is_use'=>'Y'),'patient_grade','no, standard','standard DESC','no');
			$grade_no = '1';
			foreach($grade_list as $no => $grade) {
				if($amount_paid >= $grade['standard']) {
					$grade_no = $no;
					break;
				}
			}

			$record = array(
				'grade_no'=>$grade_no,
				'amount_paid'=>$amount_paid
			);
			$CI->Patient_Model->update_patient($record, array('no'=>$patient_no), 'patient');


			//상담DB정보
			$CI->load->model('Consulting_Model');

			$patient_info = $CI->Patient_Model->select_patient_row(array('no'=>$patient_no), 'patient','no, name, cst_seqno');
			$cst_seqno = $patient_info['cst_seqno'];


			$sum = $CI->Patient_Model->select_patient_row(array('patient_no'=>$patient_no, 'is_delete'=>'N'), 'patient_project', 'SUM(amount_basic) AS basic, SUM(amount_addition) AS addition, SUM(paid_total) AS paid, SUM(amount_refund) AS refund');


			$patient_sales = $sum['basic'] + $sum['addition'];
			$patient_paid = (int)$sum['paid'];
			$refund = (int)$sum['refund'];
			$CI->Consulting_Model->update_consulting(array('patient_sales'=>$patient_sales, 'patient_paid'=>$patient_paid), array('patient_no'=>$patient_no));

		}

	}

	/**
	 * 수납금액 복원시 동기화
	 *
	 * @param string $project_no
	 * @return void
	 */
	function _sync_cash_all($project_no='') {
		$adodb = $this->ci->adodb;
		//$project_no = '8203';
		if($project_no) {
			$sql ="SELECT project_no, sum(amount_paid_cash_o+amount_paid_cash_x) as ss from patient_pay where project_no='{$project_no}'";
		}
		else {
			$sql ="SELECT project_no, sum(amount_paid_cash_o+amount_paid_cash_x) as ss from patient_pay group by project_no";
		}
	
		$rs = $adodb->getArray($sql);

		foreach($rs as $row) {
			$pno = $row['project_no'];
			$paid_cash = $row['ss'];
			$sql = "UPDATE patient_project SET paid_cash='{$paid_cash}' WHERE no='{$pno}'";
			$adodb->Execute($sql);
		}

		$adodb->Execute("update patient_project set paid_total=paid_cash+paid_card+paid_bank");//총수납액 업데이트
		$adodb->Execute("update patient_project set amount_unpaid=amount_basic-paid_total");//미수금재정리


	}

	/**
	 * 
	 * 날짜 기준으로 이전 데이터 삭제
	 * 환불금액 체크해볼것.(환불/수납유형 분리해서 삭제)
	 * 2019-01-07 날짜형식 확인
	 * 
	 * @param [type] $date 2019-01-01
	 * @return void
	 */
	public function xx($date) {
		exit;
		if(!$date) {
			return false;
		}
		if (!preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/",$date)) {
			echo '날짜 형식이 맞지 않습니다. 예)'.date('Y-m-d');
			exit;
		}

		$ci = $this->ci;
		$error = false;

		$this->dbdump('xpay');

		//$ci->db->trans_start();


		//임시삭제데이터 삭제
		$sql0 = "DELETE from patient_pay WHERE date_paid <= '{$date} 23:59:59' AND is_delete='Y'"; //임시 데이터 백업(X)
		$rs0 = $ci->db->query($sql0);
		if(!$rs0) {
			$error = true;
			echo 'error 임시삭제데이터 삭제 <br>'.$sql0;
		}

		$sql = "SELECT * FROM patient_pay WHERE date_paid <= '{$date} 23:59:59' AND (amount_paid_cash_x > 0 OR amount_refund_cash_x > 0)  order by no ASC";
	
		$rs = $ci->adodb->Execute($sql);
		$record = array();
		$sum = array();
		while($row = $rs->FetchRow()) {
	//		pre($row);

			$project_no = $row['project_no'];
			
		
			$project_row = $ci->adodb->getRow("SELECT * FROM patient_project WHERE no='{$project_no}'");
			if($project_row['date_project'] <= $date && $project_row['date_project']) { //수술일 미도래이면 건너뛰기, 기능체크
				
				$record['x'] = $row['amount_paid_cash_x'];
				$record['y'] = $row['amount_refund_cash_x'];

				if($row['pay_type'] == 'refund') {//환불
					$record['amount_refund_cash'] = $row['amount_refund_cash']-$row['amount_refund_cash_x'];
					$record['amount_refund'] = $row['amount_refund']-$row['amount_refund_cash_x'];
					$record['amount_refund_cash_x'] = 0;

					$sum[$project_no]['amount_refund']+=$row['amount_refund_cash_x'];

					//0원데이터 삭제
					if($record['amount_refund'] == 0) {
						//$ci->adodb->Execute("UPDATE patient_pay SET is_delete='Y' WHERE no='".$row['no']."'");
						//continue;
					}
				}
				else {//수납
					$record['amount_paid_cash'] = $row['amount_paid_cash']-$row['amount_paid_cash_x'];
					$record['amount_paid_cash_x'] = 0;
					$record['amount_paid'] = $row['amount_paid']-$row['amount_paid_cash_x'];

					$sum[$project_no]['amount_paid']+=$row['amount_paid_cash_x'];


					//0원데이터 삭제
					if($record['amount_paid'] == 0) {
						//$ci->adodb->Execute("UPDATE patient_pay SET is_delete='Y' WHERE no='".$row['no']."'");
						//continue;
					}
				}
			
				$no = $row['no'];
				$record_set = array();
				foreach($record as $k=>$v) {
					$record_set[] = "{$k}='{$v}'";
				}

				$s = "UPDATE patient_pay SET ".implode(',',$record_set)." WHERE no='{$no}'";
				$r = $ci->adodb->Execute($s);
				if(!$r) {
					echo 'ERROR1 : '.$s;
					exit;
				}
			}
		}

		//pre($sum);

	

		foreach($sum as $project_no => $row) {
			$project_info = $ci->adodb->getRow("SELECT * FROM patient_project WHERE no='{$project_no}'");
			//pre($project_info);

			$amount_basic = $project_info['amount_basic']-$row['amount_paid']; //매출액에서 미신고금액 빼기
			$paid_cash = $project_info['paid_cash']-$row['amount_paid']; ;

			$paid_total = $project_info['paid_card']+$project_info['paid_bank']+$paid_cash;

			$amount_refund = $project_info['amount_refund']-$row['amount_refund'];
			$amount_unpaid = $amount_basic-$paid_total+$amount_refund;
			


			$project_sql = "UPDATE patient_project SET amount_basic='{$amount_basic}', amount_unpaid='{$amount_unpaid}', amount_refund='{$amount_refund}', paid_total='{$paid_total}', paid_cash='{$paid_cash}' WHERE no='{$project_no}'";
			$project_rs = $ci->adodb->Execute($project_sql);
			if(!$project_rs) {
				echo 'ERROR2 : '.$project_sql;
				exit;
			}
		}
		exit;

		//@todo 0원데이터 삭제
		//update patient_pay set is_delete='Y' where pay_type='paid' AND date_paid <= '{$date} 23:59:59' AND amount_paid<1;
		//update patient_pay set is_delete='Y' where pay_type='refund' AND date_paid <= '{$date} 23:59:59' AND amount_refund<1;

	}

	function x_clear() {
		$ci = $this->ci;
		$adodb = $ci->adodb;
		$error = false;


		//코멘트 삭제
		$sql = "UPDATE patient_pay set comment='' WHERE x>0";
		$adodb->Execute($sql);

		// x데이터 삭제
		$sql = "UPDATE patient_pay set x='0' WHERE x>0";
		$adodb->Execute($sql);
	}

}
?>
