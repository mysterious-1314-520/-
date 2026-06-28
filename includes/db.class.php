<?php
/* 祈福导航系统 V1.3 官方开源：https://gitee.com/qifuxitong/daohang */
 
//MySQL、MySQLi、SQLite 三合一数据库操作类
if(!defined('IN_CRONLITE'))exit();

$nomysqli=false;

if(defined('SQLITE')==true){
	class DB {
		var $link = null;

		function __construct($db_file){
			global $siteurl;
		$this->link = new PDO('sqlite:'.ROOT.'includes/sqlite/'.$db_file.'.db');
		if (!$this->link) {
			if(function_exists('dh_json_exit')) dh_json_exit('数据库连接失败');
			die('Connection Sqlite failed.\n');
		}
		return true;
        }

		function fetch($q){
			return $q ? $q->fetch(PDO::FETCH_ASSOC) : false;
		}
		function get_row($q){
			$sth = $this->link->query($q);
			return $sth ? $sth->fetch(PDO::FETCH_ASSOC) : false;
		}
		function get_results($q){
			$sth = $this->link->query($q);
			if(!$sth) return array();
			return $sth->fetchAll(PDO::FETCH_ASSOC);
		}
		function count($q){
			$sth = $this->link->query($q);
			return $sth->fetchColumn();
		}
		function query($q){
			return $this->result=$this->link->query($q);
		}
		function normalize_query($q){
			$q = trim($q);
			if(preg_match("/^SHOW\s+COLUMNS\s+FROM\s+`?([a-zA-Z0-9_]+)`?\s+LIKE\s+'([^']+)'/i", $q, $m)){
				$table = str_replace("'", "''", $m[1]);
				$column = str_replace("'", "''", $m[2]);
				return "SELECT name FROM pragma_table_info('{$table}') WHERE name='{$column}'";
			}
			return $q;
		}
		function affected(){
			return $this->result ? $this->result->rowCount() : 0;
		}
		function error(){
			$error = $this->link->errorInfo();
			return '['.$error[1].'] '.$error[2];
		}
	}
}
elseif(extension_loaded('mysqli') && $nomysqli==false) {
    class DB_Statement {
        private $stmt;
        
        public function __construct($stmt) {
            $this->stmt = $stmt;
        }
        
        public function execute($params = []) {
            if(!empty($params)) {
                $types = '';
                $values = [];
                foreach($params as $param) {
                    if(is_int($param)) $types .= 'i';
                    elseif(is_float($param)) $types .= 'd';
                    else $types .= 's';
                    $values[] = $param;
                }
                call_user_func_array([$this->stmt, 'bind_param'], array_merge([$types], $this->refValues($values)));
            }
            return $this->stmt->execute();
        }
        
        public function fetch() {
            $result = $this->stmt->get_result();
            return $result ? $result->fetch_assoc() : false;
        }
        
        public function fetchAll() {
            $result = $this->stmt->get_result();
            $rows = [];
            if($result) {
                while($row = $result->fetch_assoc()) {
                    $rows[] = $row;
                }
            }
            return $rows;
        }
        
        private function refValues($arr) {
            $refs = [];
            foreach($arr as $key => $value) {
                $refs[$key] = &$arr[$key];
            }
            return $refs;
        }
    }
    
    class DB {
        var $link = null;

        function __construct($db_host,$db_user,$db_pass,$db_name,$db_port){
            
            $this->link = mysqli_connect($db_host, $db_user, $db_pass, $db_name, $db_port);
            
            if (!$this->link) {
				if(function_exists('dh_json_exit')) dh_json_exit('数据库连接失败：'.mysqli_connect_error());
				die('Connect Error (' . mysqli_connect_errno() . ') '.mysqli_connect_error());
			}
            
            //mysqli_select_db($this->link, $db_name) or die(mysqli_error($this->link));
            
 
mysqli_query($this->link,"set sql_mode = ''");
 //字符转换，读库
if(!mysqli_query($this->link,"set character set 'utf8mb4'")) mysqli_query($this->link,"set character set 'utf8'");
//写库
if(!mysqli_query($this->link,"set names 'utf8mb4'")) mysqli_query($this->link,"set names 'utf8'");
 
	return true;
	}
		function fetch($q){
			return $q ? mysqli_fetch_assoc($q) : false;
		}
		function get_row($q){
			$result = mysqli_query($this->link,$q);
			return $result ? mysqli_fetch_assoc($result) : false;
		}
		function get_results($q){
			$result = mysqli_query($this->link,$q);
			if(!$result) return array();
			$rows = array();
			while($row = mysqli_fetch_assoc($result)) $rows[] = $row;
			return $rows;
		}
		function num_rows($q){
			return $q ? mysqli_num_rows($q) : 0;
		}
		function count($q){
			$result = mysqli_query($this->link,$q);
			$count = $result ? mysqli_fetch_array($result) : array(0);
			return $count ? $count[0] : 0;
		}
		function query($q){
			$result = mysqli_query($this->link,$q);
			$this->result = $result;
			return $result;
		}
		function fetch_result($result = null){
			if($result === null) $result = $this->result;
			return $result ? mysqli_fetch_assoc($result) : false;
		}
		function escape($str){
			return mysqli_real_escape_string($this->link,(string)$str);
		}
		function insert($q){
			if(mysqli_query($this->link,$q))
				return mysqli_insert_id($this->link); 
			return false;
		}
		function affected(){
			return mysqli_affected_rows($this->link);
		}
		function prepare($sql) {
			$stmt = mysqli_prepare($this->link, $sql);
			return $stmt ? new DB_Statement($stmt) : false;
		}
		function insert_array($table,$array){
			$values = array();
			foreach($array as $value) $values[] = $this->escape($value);
			$q = "INSERT INTO `$table`";
			$q .=" (`".implode("`,`",array_keys($array))."`) ";
			$q .=" VALUES ('".implode("','",$values)."') ";
			
			if(mysqli_query($this->link,$q))
				return mysqli_insert_id($this->link);
			return false;
		}
		function error(){
			$error = mysqli_error($this->link);
			$errno = mysqli_errno($this->link);
			return '['.$errno.'] '.$error;
		}
		function close(){
			$q = mysqli_close($this->link);
			return $q;
		}
		function lastInsertId() {
			return mysqli_insert_id($this->link);
		}
	}
}
elseif(extension_loaded('pdo_mysql')) {
	class DB {
		var $link = null;
		var $result = null;

		function __construct($db_host,$db_user,$db_pass,$db_name,$db_port){
			$dsn = 'mysql:host='.$db_host.';port='.$db_port.';dbname='.$db_name.';charset=utf8mb4';
			try {
				$this->link = new PDO($dsn, $db_user, $db_pass, array(
					PDO::ATTR_ERRMODE => PDO::ERRMODE_SILENT,
					PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
				));
			} catch (PDOException $e) {
				if(function_exists('dh_json_exit')) dh_json_exit('数据库连接失败：'.$e->getMessage());
				die('Connect Error '.$e->getMessage());
			}
			$this->link->query("set sql_mode = ''");
			if(!$this->link->query("set character set 'utf8mb4'")) $this->link->query("set character set 'utf8'");
			if(!$this->link->query("set names 'utf8mb4'")) $this->link->query("set names 'utf8'");
			return true;
		}
		function fetch($q){
			return $q ? $q->fetch(PDO::FETCH_ASSOC) : false;
		}
		function get_row($q){
			$result = $this->link->query($q);
			return $result ? $result->fetch(PDO::FETCH_ASSOC) : false;
		}
		function get_results($q){
			$result = $this->link->query($q);
			return $result ? $result->fetchAll(PDO::FETCH_ASSOC) : array();
		}
		function num_rows($q){
			return $q ? $q->rowCount() : 0;
		}
		function count($q){
			$result = $this->link->query($q);
			return $result ? $result->fetchColumn() : 0;
		}
		function query($q){
			return $this->result=$this->link->query($q);
		}
		function escape($str){
			$quoted = $this->link->quote((string)$str);
			return $quoted !== false ? substr($quoted, 1, -1) : addslashes((string)$str);
		}
		function insert($q){
			if($this->link->query($q))
				return $this->link->lastInsertId();
			return false;
		}
		function affected(){
			return $this->result ? $this->result->rowCount() : 0;
		}
		function insert_array($table,$array){
			$values = array();
			foreach($array as $value) $values[] = $this->escape($value);
			$q = "INSERT INTO `$table`";
			$q .=" (`".implode("`,`",array_keys($array))."`) ";
			$q .=" VALUES ('".implode("','",$values)."') ";

			if($this->link->query($q))
				return $this->link->lastInsertId();
			return false;
		}
		function error(){
			$error = $this->link->errorInfo();
			return '['.$error[1].'] '.$error[2];
		}
		function close(){
			$this->link = null;
			return true;
		}
	}
} else { // we use the old mysql
	class DB {
		var $link = null;

		function __construct($db_host,$db_user,$db_pass,$db_name,$db_port){
		if(!function_exists('mysql_connect')) {
			if(function_exists('dh_json_exit')) dh_json_exit('服务器缺少 MySQL 数据库扩展，请开启 mysqli 或 pdo_mysql');
			die('Database extension missing: please enable mysqli or pdo_mysql.');
		}

		$this->link = @mysql_connect($db_host.':'.$db_port, $db_user, $db_pass);
            
		if (!$this->link) {
			if(function_exists('dh_json_exit')) dh_json_exit('数据库连接失败：'.mysql_error());
			die('Connect Error (' . mysql_errno() . ') '.mysql_error());
		}
            
			mysql_select_db($db_name, $this->link) or die(mysql_error($this->link));

mysql_query("set sql_mode = ''");
//字符转换，读库
if(!mysql_query("set character set 'utf8mb4'")) mysql_query("set character set 'utf8'");
//写库
if(!mysql_query("set names 'utf8mb4'")) mysql_query("set names 'utf8'");
 

	return true;
		}
		function fetch($q){
			return $q ? mysql_fetch_assoc($q) : false;
		}
		function get_row($q){
			$result = mysql_query($q, $this->link);
			return $result ? mysql_fetch_assoc($result) : false;
		}
		function get_results($q){
			$result = mysql_query($q, $this->link);
			if(!$result) return array();
			$rows = array();
			while($row = mysql_fetch_assoc($result)) $rows[] = $row;
			return $rows;
		}
        function num_rows($q){
			return $q ? mysql_num_rows($q) : 0;
		}
		function count($q){
			$result = mysql_query($q, $this->link);
			$count = $result ? mysql_fetch_array($result) : array(0);
			return $count ? $count[0] : 0;
		}
        function query($q){
			return mysql_query($q, $this->link);
		}
		function escape($str){
			return mysql_real_escape_string((string)$str, $this->link);
		}
		function affected(){
			return mysql_affected_rows($this->link);
		}
		function insert($q){
			if(mysql_query($q, $this->link))
				return mysql_insert_id($this->link);
			return false;
		}
		function insert_array($table,$array){
			$q = "INSERT INTO `$table`";
			$q .=" (`".implode("`,`",array_keys($array))."`) ";
			$q .=" VALUES ('".implode("','",array_values($array))."') ";

			if(mysql_query($q, $this->link))
				return mysql_insert_id($this->link);
			return false;
		}
		function error(){
			$error = mysql_error($this->link);
			$errno = mysql_errno($this->link);
			return '['.$errno.'] '.$error;
		}
		function close(){
			$q = mysql_close($this->link);
			return $q;
		}
	}

}
?>
