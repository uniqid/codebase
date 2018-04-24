<?php
/*************************************************

Codebase - The PHP toolkit
Author: Jacky Yu <jacky325@qq.com>
Copyright (c): 2012-2018 Jacky Yu, All rights reserved
Version: 1.0.0

* This library is free software; you can redistribute it and/or modify it.
* You may contact the author of Codebase by e-mail at: jacky325@qq.com

The latest version of Codebase can be obtained from:
https://github.com/uniqid/codebase

*************************************************/

if(!defined('IN_CODEBASE')) {
	exit('Access Denied');
}

class Mysql {
	private $_host;
	private $_port;
	private $_username;
	private $_password;
	private $_database;
	private $_encoding;
	private $_conn;
	public  $message = "";

	public function __construct($username, $password, $database, $host = "localhost", $port = "3306", $encoding = "utf8") {
		$this->_host = $host;
		$this->_port = $port;
		$this->_username = $username;
		$this->_password = $password;
		$this->_database = $database;
		$this->_encoding = 	$encoding;
		$this->connect($this->_host, $this->_port, $this->_username, $this->_password);
		$this->selectDb();
        $this->setEncoding();
	}

	static function getInstance($username, $password, $database, $host = "localhost", $port = "3306", $encoding = "utf8") {
		static $instance = array();
		if (!$instance) {
			$instance[0] = new Mysql($username, $password, $database, $host, $port, $encoding);
		}
		return $instance[0];
	}

	public function connect($host, $port, $username, $password) {
        $this->_mysqli = new mysqli($host, $username, $password);
		if ($this->_mysqli->connect_error) {
			exit("Could not connect: " . $this->_mysqli->connect_error);
		}
	}

	public function selectDb($again = false) {
		if(!$this->_mysqli->select_db($this->_database)){
			$again && exit("Could not select database ".$this->_database.": " . $this->_mysqli->error);
			$this->createDb($this->_database);
			$this->selectDb(true);
		}
	}

    public function createDb($database){
        $sql = 'CREATE DATABASE IF NOT EXISTS `'.$database.'` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;';
        return $this->query($sql);
    }

	public function getDb() {
		return $this->_database;
	}

	public function setEncoding() {
		$this->query("set names {$this->_encoding}");
	}

	public function create($sql) {
		return $this->query($sql);
	}

	public function alter($sql) {
		return $this->query($sql);
	}

	public function drop($table) {
		$sql = "DROP TABLE {$table}";
		return $this->query($sql);
	}

	public function begin(){
		return $this->query("BEGIN");
	}

	public function commit(){
		return $this->query("COMMIT");
	}

	public function rollback(){
		return $this->query("ROLLBACK");
	}

	public function find($table, $options = array(), $fields = false){
		$options['type'] = 'first';
		return $this->_find($table, $options, $fields);
	}

	public function findAll($table, $options = array(), $fields = false){
		$options['type'] = 'all';
		return $this->_find($table, $options, $fields);
	}

	public function findList($table, $options = array(), $fields = false){
		$options['type'] = 'list';
		return $this->_find($table, $options, $fields);
	}

	private function _find($table, $options = array(), $fields = false) {
		if(!$fields){
			$fields = array_key_exists('fields', $options)? implode(",", $options['fields']): " `{$table}`.* ";
		}
		$order  = array_key_exists('order',  $options)? " ORDER BY {$options['order']}": "";
		$limits = "";
		if(array_key_exists('start',  $options) || array_key_exists('limit',  $options)){
			$start  = array_key_exists('start',  $options)? $options['start']: 0;
			$limit  = array_key_exists('limit',  $options)? $options['limit']: 20;
			$limits = "LIMIT {$start}, {$limit}";
		}

		$left_join = "";
		if(array_key_exists('join', $options)){
			extract($options['join'], EXTR_PREFIX_ALL, "join");
			$fields .=  isset($join_fields)? (" ,`{$join_table}`.`" . implode("`, `{$join_table}`.`", $join_fields) . "`"): " ,`{$join_table}`.* ";
			if(!isset($join_on)){
				$join_on = "`{$table}`.`id` = `{$join_table}`.`".substr($table, 0, -1)."_id`";
			}
			$left_join = " left join `{$join_table}` on {$join_on} ";
		}

		$conditions = "";
		if(array_key_exists('conditions', $options)){
			$conditions = $this->getConditions($options['conditions']);
		}
		$sql = "SELECT {$fields} FROM {$table} {$left_join} {$conditions} {$order} {$limits}";
		if($rs = $this->query($sql)){
			$row = $this->fetch_array($rs);
			if(array_key_exists('type', $options) && $options['type'] != 'first'){
				$rows = array();
				if($isIdKey = (array_key_exists('type', $options) && $options['type']=='list' && isset($row['id']))){
					$rows[$row['id']] = $row;
				}
				else{
					$row && $rows[] = $row;
				}
				while($row = $this->fetch_array($rs)){
					$isIdKey? $rows[$row['id']] = $row: $rows[] = $row;
				}
				return $rows;
			}
			else{
				return $row;
			}
		}
		else{
			return array();
		}
	}

	public function count($table, $conditions = "") {
		$condition = $this->getConditions($conditions);
		$sql = "SELECT COUNT(*) FROM {$table} {$condition}";
		if(($rs = $this->query($sql)) && ($row = $this->fetch_array($rs, MYSQL_NUM))){
			return $row[0];
		}
		else{
			return false;
		}
	}

	public function insert($table, $datas, $isMulti = false) {
		$value = "";
		$field = "";
		if($datas && is_array($datas)){
			$multidata = $isMulti? $datas: array($datas);
			$isFirstRecord = true;
			foreach($multidata as $data){
				$value!=="" && $value .= "),(";
				foreach($data as $_field => $_value){
					$_value = str_replace("\\", "\\\\", $_value);
					$_value = str_replace("'", "\'", $_value);
					if($value==""){
						$field .= "`{$_field}`";
						$value .= "'{$_value}'";
					}
					else{
						$isFirstRecord && $field .= ",`{$_field}`";
						if(substr($value, -1)=="("){
							$value .= "'{$_value}'";
						}
						else{
							$value .= ",'{$_value}'";
						}
					}
				}
				$isFirstRecord = false;
			}
		}
		else{
			return false;
		}
		$sql = "INSERT INTO `{$table}` ({$field}) VALUES ({$value})";
		return $this->query($sql) && $this->affected_rows();
	}

	public function update($table, $datas, $conditions = "") {
		if($datas && is_array($datas)){
			$data = "";
			foreach($datas as $_key => $_data){
				$_data = str_replace("\\", "\\\\", $_data);
				$_data = str_replace("'", "\'", $_data);
				if($data == ""){
					$data  = "`{$_key}` = '{$_data}'";
				}
				else{
					$data .= ", `{$_key}` = '{$_data}'";
				}
			}	
		} 
		else {
			$data = $datas;
		}
		$condition = $this->getConditions($conditions);
		$sql = "UPDATE {$table} SET {$data} {$condition}";
		return $this->query($sql);
	}

	public function delete($table, $conditions = "") {
		$condition = $this->getConditions($conditions);
		$sql = "DELETE FROM {$table} {$condition}";
		return $this->query($sql) && $this->affected_rows();
	}

	public function truncate($table) {
		$table = preg_replace("/\s+/s", "", $table);
		return $this->query("truncate {$table}")  && $this->affected_rows();
	}

	public function getConditions($conditions = "") {
		if($conditions && is_array($conditions)){
			$condition = "";
			foreach($conditions as $_key => $_condition){
				$condition !== "" && $_key = " AND {$_key}";

				if(is_array($_condition)){
					$condition .= " {$_key} IN('".implode("', '", $_condition)."')";
				}
				elseif(strpos(trim($_key), ">") || strpos(trim($_key), "<") || strpos(trim($_key), "=")){
					$condition .= " {$_key}'{$_condition}'";
				}
				elseif( strpos(trim($_key), " like") ){
					$condition .= " {$_key} '{$_condition}'";
				}
				else{
					$condition .= " {$_key}='{$_condition}'";
				}
			}
		}
		else{
			$condition = $conditions;
		}
		return $condition==""? "": " WHERE ".$condition;
	}

	public function query($sql) {
		if($query = $this->_mysqli->query($sql)){
			return $query;
		}
		else{
			$this->message = $this->_mysqli->error;
			return false;
		}
	}

	public function affected_rows() {
		return $this->_mysqli->affected_rows;;
	}

	public function fetch_array($rs, $type = MYSQLI_ASSOC) {
        return $rs->fetch_array($type);
	}

	public function insertId() {
		if(($id = $this->_mysqli->insert_id)>=1){
			return $id;
		}
		else if($rs = $this->query("SELECT last_insert_id() as id")){
			if($row = $this->fetch_array($rs, MYSQLI_NUM)){
				return $row[0];
			}
		}
		return 0;
	}

	public function close() {
        if(is_object($this->_mysqli)){
            $this->_mysqli->close();
            $this->_mysqli = null;
        }
	}

	public function __destruct() {
		$this->close();
	}
}
?>
