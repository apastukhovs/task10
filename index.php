<?php
include ('config.php');
include ('libs/DB.php');
try {
  $dbMy = new DB(DB_MYSQL, DB_HOST, DB_NAME, DB_USER, DB_PASS);
} catch (PDOException $e) {
  echo $e->getMessage() . ':(';
}
$table = 'users';
$table1 = 'tableA';
$table2 = 'tableB';
$columns = [
	'fname' => 'John',
	'lname' => 'Dow'
];


$res = $dbMy->insert($table, $columns);

//$res = $dbMy->selectDistinct('fname')->from($table)->resultset();//
//$res = $dbMy->delete($table, '=', 'id', 5);
//$res = $dbMy->update($table, $columns, '=', 'id', 4);
//$res = $dbMy->select()->from($table1)->join($table2, '=', 'name', 'name')->resultset();//
var_dump($res);

include ('template/index.php');