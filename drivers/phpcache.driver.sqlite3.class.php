<?php
	
	/**
	 * This program is free software; you can redistribute it and/or modify
	 * it under the terms of the GNU General Public License as published by
	 * the Free Software Foundation; either version 2 of the License, or
	 * (at your option) any later version.
	 * 
	 * This program is distributed in the hope that it will be useful,
	 * but WITHOUT ANY WARRANTY; without even the implied warranty of
	 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	 * GNU General Public License for more details.
	 * 
	 * You should have received a copy of the GNU General Public License
	 * along with this program; if not, write to the Free Software
	 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
	 *
	 * D O  N O T  R E M O V E  T H E S E  C O M M E N T S
	 *
	 * @Package PHPCache
	 * @Author  Hamid Alipour, http://blog.code-head.com/ http://www.hamidof.com/
	 * @Author  Jan-Age Laroo
	 */

	
	class PHPCache_Driver_sqlite3 implements PHPCache_Driver_Interface {
	
		private $database_file;
		private $db_handle;
		private $error_message;
		
		
		public function __construct($params) {
			$this->database_file = $params['name'];
			
			$this->db_handle = new PDO('sqlite:'.$this->database_file);
			$this->db_handle->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			
			if( !$this->db_handle ) {
				return false;
			}
		}
		
		public function query($query) {
			
			$result = $this->db_handle->query($query);
			if( !$result ) {
				return false;
			} 
			
			// Due to limitations with PDO & sqlite return the whole resultset and parse it in PHPCache_Driver_sqlite3_Results
			$result_set = new PHPCache_Driver_sqlite3_Results($this->db_handle, $result, null, null, null);
			return $result_set;
			
		}
		
		public function escape($value) {
			$return = false;
			if( is_numeric($value) ) {
				$return = $value;
			} else {
				$return = $this->db_handle->quote($value);
			}			
			return $return;
		}
		
		public function close() {
			$this->db_handle = null;
		}
		
		public function error() {
			return $this->db_handle->errorInfo();
		}
		
		public function create_table($table_name) {
			$this->query("
				CREATE TABLE $table_name
				(
					PHPCache_key VARCHAR(41) PRIMARY KEY,
					PHPCache_value TEXT,
					PHPCache_expires INTEGER
				)
			");
			$this->query("
				CREATE INDEX 
					PHPCache_PHPCache_expires
				ON 
					PHPCache (PHPCache_expires)
			");
		}
		
		public function optimize_table($table_name) {
			return true;
		}
		
	} // Class
	
	
	class PHPCache_Driver_sqlite3_Results implements PHPCache_Driver_Results_Interface {
	
		private $db_handle;
		private $result;
		private $results_array;
		public $num_rows;
		public $insert_id;
		public $affected_rows;
		
		
		public function __construct($db_handle, $result, $num_rows, $insert_id, $affected_rows) {
			$this->db_handle = $db_handle;
			$this->result 	 = $result;
			
			$result->setFetchMode(PDO::FETCH_ASSOC);
			$this->results_array = $result->fetchAll();
			
			$this->num_rows  		= count($this->results_array);
			$this->insert_id 		= $this->db_handle->lastInsertId();
			$this->affected_rows	= $result->rowCount();
			
		}
		
		public function fetch_row() {
			return array_shift($this->results_array);
		}
		
		public function fetch_array() {
			return array_shift($this->results_array);
		}
		
		public function fetch_assoc() {
			return array_shift($this->results_array);
		}
		
	} // Class
	
?>