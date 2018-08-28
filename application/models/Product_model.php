<?php
class Product_model extends CI_MODEL{
	
	function __construct(){
		
	}
	
	public function insert($kid,$pdate,$deadline,$qty){
		$sql1 = "INSERT INTO `tbl_kbn`(`kanban_id`, `production_date`, `deadline`, `qty`) VALUES ('".$kid."','".$pdate."','".$deadline."',".$qty.")";
		$sql2 = "INSERT INTO `tbl_proc`(`kanban_id`) VALUES ('".$kid."')";
		
		$this->db->trans_start();
		$this->db->query($sql1);
		$this->db->query($sql2);
		$this->db->trans_complete();

		if ($this->db->trans_status() === TRUE)
			return  1;
		else 
			return 0;

	}

	
	public function production_timeframe($from, $to, $timeframe, $pline){
		$timeframe = $timeframe * 3600;
		$sql = "SELECT  
				COUNT(*) as 'count',
				SUM(tbl_kbn.qty) as 'qty',
				MIN(tbl_proc_record.onStop) as 'min_completed',
				MAX(tbl_proc_record.onStop) as 'max_completed'
				FROM tbl_proc_record 
				INNER JOIN tbl_kbn ON tbl_kbn.kanban_id = tbl_proc_record.kanban_id 
				WHERE tbl_proc_record.onStop >= '$from' AND
					  tbl_proc_record.onStop <= '$to' AND
					  tbl_proc_record.pline_id = $pline
				 GROUP BY 
				 UNIX_TIMESTAMP(tbl_proc_record.onStop) DIV $timeframe
				;";
		$result = $this->db->query($sql);
		return $result->result_array();
	}

	public function count(){
		return $this->db->count_all('tbl_kbn');
	}

	public function count_like($search){
		$sql = "SELECT 
		COUNT(*)
		FROM tbl_kbn 
		INNER JOIN tbl_proc ON tbl_kbn.kanban_id = tbl_proc.kanban_id 
		LEFT JOIN tbl_pline ON tbl_proc.pline_id = tbl_pline.id 
		WHERE tbl_kbn.kanban_id LIKE '$search%'";
		$result = $this->db->query($sql);
		foreach($result->result_array() as $row)
			return $row['COUNT(*)'];
		return -1;
	}
	public function selectrange($from,$to,$order,$asc){
		$sql = "SELECT 
			tbl_kbn.id,
			tbl_pline.id as pline_id,
			tbl_pline.line_name as line_name,
			pid, 
			tbl_kbn.kanban_id, 
			production_date, deadline, qty, tbl_proc.cur_qty as cur_qty, (qty-tbl_proc.cur_qty) as remain_qty,
			tbl_proc.cycle, 
			TIMESTAMPDIFF(DAY,NOW(),production_date) AS remain_pday, 
			TIMESTAMPDIFF(DAY,NOW(),deadline) AS remain_dday
			FROM tbl_kbn 
			INNER JOIN tbl_proc ON tbl_kbn.kanban_id = tbl_proc.kanban_id 
			LEFT JOIN tbl_pline ON tbl_proc.pline_id = tbl_pline.id
			ORDER BY ".$order." ".($asc==0?"DESC":"ASC")." LIMIT ".$from.",".$to."";
		
		//echo $sql;
		$query = $this->db->query($sql);
		
		return $query;
	} 
	public function get_kanban_by_id($kanban_id){

			$sql1 = "SELECT 
			tbl_kbn.id,
			tbl_pline.id as pline_id,
			tbl_pline.line_name as line_name,
			pid, 
			tbl_kbn.kanban_id, 
			production_date, deadline, qty, tbl_proc.cur_qty as cur_qty, (qty-tbl_proc.cur_qty) as remain_qty,
			tbl_proc.cycle, 
			TIMESTAMPDIFF(DAY,NOW(),production_date) AS remain_pday, 
			TIMESTAMPDIFF(DAY,NOW(),deadline) AS remain_dday
			FROM tbl_kbn 
			INNER JOIN tbl_proc ON tbl_kbn.kanban_id = tbl_proc.kanban_id 
			LEFT JOIN tbl_pline ON tbl_proc.pline_id = tbl_pline.id 
			WHERE tbl_proc.kanban_id = $kanban_id";

			$result = $this->db->query($sql1);

			foreach($result->result_array() as $row){
				return $row;
			}
	}
	public function GetNextLine($kanban_id){
		$CI =& get_instance();
		$CI->load->model('Line_model');
		$kanban = $this->get_kanban_by_id($kanban_id);
		
		if ($kanban == null) return 0;
		$cur_pline = $kanban['pline_id'];
		$cur_line = $CI->Line_model->get_line($cur_pline);
		$next_line = $CI->Line_model->get_line_next($cur_line['seq']);
		return $next_line;
	}
	 public function putNextLineKanban($kanban_id){
		$CI =& get_instance();
		$CI->load->model('Line_model');

		$nextLine = $this->GetNextLine($kanban_id);
		if ($nextLine == null)return 0;

		$plineNext = $nextLine['id'];

		$result = $this->db->query("
			UPDATE tbl_proc 
			SET pline_id = $plineNext, cycle =  0, cur_qty = 0 
			WHERE kanban_id = '$kanban_id'");
		return $this->db->affected_rows();
	 }
	public function search($search, $from,$to,$order,$asc){
		$sql = "SELECT 
			tbl_kbn.id,
			tbl_pline.id as pline_id,
			tbl_pline.line_name as line_name,
			pid, 
			tbl_kbn.kanban_id, 
			production_date, deadline, qty, tbl_proc.cur_qty as cur_qty, (qty-tbl_proc.cur_qty) as remain_qty,
			tbl_proc.cycle, 
			TIMESTAMPDIFF(DAY,NOW(),production_date) AS remain_pday, 
			TIMESTAMPDIFF(DAY,NOW(),deadline) AS remain_dday
			FROM tbl_kbn 
			INNER JOIN tbl_proc ON tbl_kbn.kanban_id = tbl_proc.kanban_id 
			LEFT JOIN tbl_pline ON tbl_proc.pline_id = tbl_pline.id 
			WHERE tbl_kbn.kanban_id LIKE '$search%' 
			ORDER BY ".$order." ".($asc==0?"DESC":"ASC")." LIMIT ".$from.",".$to."";
			//echo $sql;
			//exit(1);
		$query = $this->db->query($sql);
		
		return $query;
	}
	
	
}


?>