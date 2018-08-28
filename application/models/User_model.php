<?php
class User_model extends CI_MODEL{
	function __construct(){
		
	}
	public function Get_User(){
		$result = $this->db->query("SELECT * FROM tbl_usr");
		return $result;
	}
	
	
}


?>