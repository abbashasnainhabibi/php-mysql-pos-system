<?php
$host="localhost"; $user="root"; $pass=""; $db="new_paint_pos";
$conn=new mysqli($host,$user,$pass,$db);
date_default_timezone_set('Asia/Karachi');
$conn->query("SET time_zone = '+05:00'");
if($conn->connect_error){die("DB Error");}

?>