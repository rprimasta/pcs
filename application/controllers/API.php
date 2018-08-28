<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class API extends CI_Controller {
	public function __construct(){
		parent::__construct();
		$this->db->db_debug = True;
		$this->load->model("User_model");
		$this->load->model("Product_model");
		$this->load->model("Line_model");
	}
	
	
	public function Get_User(){
		$result = $this->User_model->Get_User();
		$obj = array();
		$obj['status'] = 1;
		foreach($result->result_array() as $row){
			$obj['data'] = $row;
			
		}
		echo json_encode($obj);
		
	}
	
	public function kbn($mode){	
		switch($mode){
			case "next_line":
				$ret = array();
				$kanban_id = $_POST["kanban_id"];
				$ret['status'] = $this->Product_model->putNextLineKanban($kanban_id);
				$ret['mode'] = $mode;
				echo json_encode($ret);
				break;
			break;
			case "timeframe":
				$ret = array();
				$from = $_POST["from"];
				$to = $_POST["to"];
				$timeframe = $_POST["timeframe"];
				$lineList = $this->Line_model->get_line_list();
				$data = array();
				foreach($lineList as $row){
					$ww = $row;
					$ww['chart'] =  $this->Product_model->production_timeframe($from, $to, $timeframe, $row['id']);
					array_push($data, $ww);
				}
				if (count($data) >= 1)
					$ret['status'] = 1;
				else
					$ret['status'] = 0;
				$ret['data'] = $data;
				$ret['mode'] = $mode;
				echo json_encode($ret);
				break;
			case "insert":
				$ret = array();
				$result = $this->Product_model->insert($_POST["kid"],$_POST["pdate"],$_POST["deadline"],$_POST["qty"]);
				$ret['status'] = $result;
				$ret['mode'] = $mode;
				echo json_encode($ret);
				break;
			case "count":
				$ret = array();
				$result = $this->Product_model->count();
				$ret['status'] = ((int)$result>0?1:0);
				$ret['mode'] = $mode;
				$ret['count'] = $result;
				echo json_encode($ret);
				break;
			case "selectrange":
				$ret = array();
				$result = $this->Product_model->selectrange($_POST["from"],$_POST["to"],$_POST["order"],$_POST["asc"]);
				$ret['status'] = ($result->num_rows()>0?1:0);
				$ret['mode'] = $mode;
				$ret['count'] = $result->num_rows();
				$ret['count_total'] = $this->Product_model->count();
				$ret['result'] = $result->result_array();
				echo json_encode($ret);
				break;
			case "search":
				$ret = array(); 
				$result = $this->Product_model->search($_POST["search"], $_POST["from"],$_POST["to"],$_POST["order"],$_POST["asc"]);
				$ret['status'] = ($result->num_rows()>0?1:0);
				$ret['mode'] = $mode;
				$ret['count'] = $result->num_rows();
				$ret['count_total'] = $this->Product_model->count_like($_POST["search"]);
				$ret['result'] = $result->result_array();
				echo json_encode($ret);
				break;
				
		}
	}

	public function Getline($mode){	
		switch($mode){
			case "select":
				
				$data = $this->Line_model->get();
				$ret = array();
				$ret['status'] = (count($data)>0?1:0);
				$ret['mode'] = $mode;
				$ret['count'] = count($data);
				$ret['result'] = $data;
				echo json_encode($ret);
				break;
			case "info":
				$ret = array();
				$data = $this->Line_model->get_line_production_info();
				if (count($data) >= 1)
					$ret['status'] = 1;
				else
					$ret['status'] = 0;
				$ret['data'] = $data;
				$ret['mode'] = $mode;
				echo json_encode($ret);
				break;
			
		}
	}
	
	public function index()
	{
		echo "WEW";
	}
}
