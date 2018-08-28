<?php
class Line_model extends CI_MODEL{
	
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
	
	public function count(){
		return $this->db->count_all('tbl_kbn');
	}
	public function get_line_list(){
		$result = $this->db->query("SELECT * FROM tbl_pline");
		return $result->result_array();
	}
	public function get_line($pline_id){
		$result = $this->db->query("SELECT * FROM tbl_pline WHERE id = $pline_id");
		foreach($result->result_array() as $row)
			return $row;
	}
	public function get_used_line($line_id){
		$used_count = $this->db->query("SELECT COUNT(*) as 'cnt' FROM tbl_proc WHERE pline_id = $line_id AND cycle = 1;");
		$used_count = $used_count->result_array()[0]['cnt'];
		return $used_count;
	}
	public function check_available_line($line_id){
		$used_count = $this->get_used_line($line_id);
		$quota = $this->get_line($line_id)['quota'];
		
		if ($used_count  < $quota)
			return true;
		return false;
	}
	public function get_line_next($cur_seq){
		$result = $this->db->query("SELECT * FROM tbl_pline WHERE seq = (SELECT MIN(seq) FROM tbl_pline WHERE seq > $cur_seq)");
		foreach($result->result_array() as $row)
		{
			
			if ($this->check_available_line($row['id']) === true)
				return $row;
		}
		return null;
	}


	public function get_line_production_info(){
			$sql = "SELECT  
				SUM(tbl_kbn.qty) as 'qty',
				COUNT(*) as 'total_kanban',
				(SELECT COUNT(*) FROM tbl_proc WHERE tbl_proc.pline_id = tbl_proc_record.pline_id AND tbl_proc.cycle = 1) as 'running',
				(SELECT COUNT(*) FROM tbl_proc WHERE tbl_proc.pline_id = tbl_proc_record.pline_id AND tbl_proc.cycle = 2) as 'completed',
				(SELECT  (tbl_kbn.qty DIV (TIMESTAMPDIFF(SECOND,t1.onStart,t1.onStop) )*3600) as 'capacity'
								FROM tbl_proc_record t1
								INNER JOIN tbl_kbn ON tbl_kbn.kanban_id = t1.kanban_id
								WHERE t1.onStop = (SELECT MAX(t2.onStop)
								                 FROM tbl_proc_record t2
								                 WHERE pline_id = tbl_proc_record.pline_id)) as 'capacityPerHour',
				tbl_pline.quota as 'quota',
				tbl_pline.line_name as 'line_name',
				pline_id
				FROM tbl_proc_record 
				INNER JOIN tbl_kbn ON tbl_kbn.kanban_id = tbl_proc_record.kanban_id 
				INNER JOIN tbl_pline ON tbl_pline.id = tbl_proc_record.pline_id 
				WHERE tbl_proc_record.onStart IS NOT NULL AND
					  tbl_proc_record.onStop IS NOT NULL
				 GROUP BY 
				 tbl_proc_record.pline_id;";
		$result = $this->db->query($sql);
		$ret = array();
		foreach( $result->result_array() as $row){
			 $row['running_kanban'] = $this->get_kanban($row['pline_id'],1);

			 array_push($ret, $row);
		}
		return $ret;
	}
	public function get_kanban($line_id, $cycle){
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
			
			WHERE tbl_proc.pline_id = $line_id AND tbl_proc.cycle = $cycle";
			$result = $this->db->query($sql1);
			return $result->result_array();
	}
	
	public function get(){
		$sql = "SELECT *,
			(SELECT COUNT(*) FROM tbl_proc WHERE tbl_pline.id=tbl_proc.pline_id AND tbl_proc.cycle=1) AS kanban_count,
			IFNULL((SELECT SUM(tbl_kbn.qty) FROM tbl_kbn INNER JOIN tbl_proc ON tbl_kbn.kanban_id=tbl_proc.kanban_id WHERE tbl_pline.id=tbl_proc.pline_id AND tbl_proc.cycle=1),0) AS total_qty,
			IFNULL((SELECT SUM(tbl_proc.cur_qty) FROM tbl_kbn INNER JOIN tbl_proc ON tbl_kbn.kanban_id=tbl_proc.kanban_id WHERE tbl_pline.id=tbl_proc.pline_id AND tbl_proc.cycle=1),0) AS cur_qty
			FROM tbl_pline";
		$query = $this->db->query($sql);
		$data = $query->result_array(); 
		foreach ($query->result() as $key=>$row)
		{		   
			$data[$key]["onprocess"] = $this->get_kanban($row->id,1);
			$data[$key]["queue"] =  $this->get_kanban($row->id,0);
			$data[$key]["oncompleted"] =  $this->get_kanban($row->id,2);
		}
		
		return $data;
	}
	
	public function search($search){
		$sql = "SELECT 
			id,
			pid,
			tbl_kbn.kanban_id,
			production_date,
			deadline,
			qty,
			tbl_proc.cur_qty as cur_qty,
			(qty-tbl_proc.cur_qty) as remain_qty,
			tbl_proc.cycle, 
			TIMESTAMPDIFF(DAY,NOW(),production_date) AS remain_pday,
			TIMESTAMPDIFF(DAY,NOW(),deadline) AS remain_dday 
			FROM tbl_kbn INNER JOIN tbl_proc ON tbl_kbn.kanban_id = tbl_proc.kanban_id WHERE tbl_kbn.kanban_id = ".$search;
			
		$query = $this->db->query($sql);
		
		return $query;
	}
	
	
}


?>