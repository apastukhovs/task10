<?php
class DB
{
	private $dbh;
	private $stmt;
	private $_query;
	private $error;
	public function __construct($driver, $host, $db, $user, $pass)
	{ 	
		switch($driver){
			case 'mysql':
				$dsn = 'mysql:host='.$host.';dbname='.$db;
			break;
			case "pgsql":
				$dsn = 'pgsql:host='.$host.';dbname='.$db;
			break;
		}
		$opt = [
			PDO::ATTR_PERSISTENT => true,
			PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
		];
		
		try {
			$this->dbh = new PDO($dsn, $user, $pass, $opt);
		} catch (PDOException $e) {  
			$this->error = $e->getMessage();  
		}  
	}
	
	public function buildQuery($query)
	{
		$sql=!empty($query['distinct']) ? 'SELECT DISTINCT' : 'SELECT';
		$sql.=' '.(!empty($query['select']) ? $query['select'] : '*');
		if(!empty($query['from']))
			$sql.="\nFROM ".$query['from'];
		if(!empty($query['join']))
			$sql.="\n".$query['join'];
		if(!empty($query['where']))
			$sql.="\nWHERE ".$query['where'];
		if(!empty($query['group']))
			$sql.="\nGROUP BY ".$query['group'];
		if(!empty($query['having']))
			$sql.="\nHAVING ".$query['having'];
		if(!empty($query['order']))
			$sql.="\nORDER BY ".$query['order'];
		$limit=isset($query['limit']) ? (int)$query['limit'] : -1;
		if(!empty($query['limit']))
			$sql.="\nLIMIT ".$query['limit'];
		return $sql;
	}
	
	public function query($query){  
		$this->stmt = $this->dbh->prepare($query);  
	} 
	public function bind($param, $value, $type = null){  
		if (is_null($type)) {  
			switch (true) {  
				case is_int($value):  
					$type = PDO::PARAM_INT;  
					break;  
				case is_bool($value):  
					$type = PDO::PARAM_BOOL;  
					break;  
				case is_null($value):  
					$type = PDO::PARAM_NULL;  
					break;  
				default:  
					$type = PDO::PARAM_STR;  
			}  
		}  
		$this->stmt->bindValue($param, $value, $type);  
	}
	public function execute(){  
		return $this->stmt->execute();  
	}
	
	public function select($columns = '*', $option = '')
	{
		if(is_string($columns))
			$this->_query['select'] = $columns;
		if($option != '')
			$this->_query['select'] = $option.' '.$this->_query['select'];
		$sql = $this->buildQuery($this->_query);
		$this->query($sql);
		return $this;
	}
	public function selectDistinct($columns = '*')
	{
		$this->_query['distinct'] = true;
		return $this->select($columns);
	}
	
	public function insert($table, $columns)
	{
		$params = array();
		$names = array();
		$placeholders = array();
		foreach($columns as $name => $value)
		{
			$names[] = $name;
			$placeholders[] = ':' . $name;
			$params[':' . $name] = $value;
		}
		$sql='INSERT INTO ' . $table
			. ' (' . implode(', ',$names) . ') VALUES ('
			. implode(', ', $placeholders) . ')';
		$this->query($sql);
		foreach($params as $k => $v){
			$this->bind($k, $v);
		}
		return $this->execute();
	}
	public function update($table, $columns, $conditions='', $field, $val)
	{
		$lines=array();
		foreach($columns as $name=>$value)
		{
			$lines[] = $name . '=:' . $name;
			$params[':' . $name] = $value;
		}
		$sql='UPDATE ' . $table . ' SET ' . implode(', ', $lines);
		if($conditions != '')
			$sql.=' WHERE '.$field.$conditions.$val;
		$this->query($sql);
		foreach($params as $k => $v){
			$this->bind($k, $v);
		}
		return $this->execute();
	}
	public function delete($table, $conditions = '', $field, $value)
	{
		$sql='DELETE FROM ' . $table;
		if($conditions != '')
			$sql.=' WHERE '.$field.$conditions.$value;
		$this->query($sql);
		return $this->execute();
	}

	public function from($tables)
	{
		if(is_string($tables))
			$this->_query['from'] = $tables;
		$sql = $this->buildQuery($this->_query);
		$this->query($sql);
		return $this;
	}
	
	public function where($conditions, $field, $value)
	{
		$this->_query['where'] = $field.$conditions.$value;
		$sql = $this->buildQuery($this->_query);
		$this->query($sql);
		return $this;
	}
	
	public function group($columns)
	{
		if(is_string($columns))
			$this->_query['group'] = $columns;
		$sql = $this->buildQuery($this->_query);
		$this->query($sql);
		return $this;
	}
	public function join($table, $conditions, $val_1, $val_2)
	{
		if($conditions != '')
		{
			$table1 = $this->_query['from'];
			$conditions = ' ON ' . $table1 . '.' . $val_1 . $conditions. $table . '.' . $val_2;
		}
		$this->_query['join'] = 'JOIN ' . $table . $conditions;
		$sql = $this->buildQuery($this->_query);
		$this->query($sql);
		return $this;
	}
	public function leftJoin($table, $conditions, $val_1, $val_2)
	{
		if($conditions != '')
		{
			$table1 = $this->_query['from'];
			$conditions = ' ON ' . $table1 . '.' . $val_1 . $conditions. $table . '.' . $val_2;
		}
		$this->_query['join'] = 'LEFT JOIN ' . $table . $conditions;
		$sql = $this->buildQuery($this->_query);
		$this->query($sql);
		return $this;
	}
	public function rightJoin($table, $conditions, $val_1, $val_2)
	{
		if($conditions != '')
		{
			$table1 = $this->_query['from'];
			$conditions = ' ON ' . $table1 . '.' . $val_1 . $conditions. $table . '.' . $val_2;
		}
		$this->_query['join'] = 'RIGHT JOIN ' . $table . $conditions;
		$sql = $this->buildQuery($this->_query);
		$this->query($sql);
		return $this;
	}
	public function crossJoin($table)
	{
		$this->_query['join'] = 'CROSS JOIN ' . $table;
		$sql = $this->buildQuery($this->_query);
		$this->query($sql);
		return $this;
	}
	public function naturalJoin($table)
	{
		$this->_query['join'] = 'NATURAL JOIN ' . $table;
		$sql = $this->buildQuery($this->_query);
		$this->query($sql);
		return $this;
	}
	
	public function having($conditions, $field, $value)
	{
		$this->_query['having'] = $field.$conditions.$value;
		$sql = $this->buildQuery($this->_query);
		$this->query($sql);
		return $this;
	}
	
	public function order($columns)
	{
		if(is_string($columns))
			$this->_query['order'] = $columns;
		$sql = $this->buildQuery($this->_query);
		$this->query($sql);
		return $this;
	}
	
	public function limit($limit)
	{
		$this->_query['limit'] = (int)$limit;
		$sql = $this->buildQuery($this->_query);
		$this->query($sql);
		return $this;
	}	
	
	public function resultset(){  
		$this->execute();  
		return $this->stmt->fetchAll(PDO::FETCH_ASSOC);  
	}
	public function single(){  
		$this->execute();  
		return $this->stmt->fetch(PDO::FETCH_ASSOC);  
	}   
	public function rowCount(){  
		return $this->stmt->rowCount();  
	}
	public function lastInsertId(){  
		return $this->dbh->lastInsertId();  
	}	
}