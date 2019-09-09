<?php
// include_once("db.inc");
// include_once("utility.inc");
// 
// $pdo = connect();
// 
// $request = "UPDATE users SET groupe = :groupe WHERE id = :id";
// $stmt = $pdo->prepare($request);
// $success = $stmt->execute(array(
//     ":id"=>5,
//     ":groupe"=>'mod'
// ));
// if($success){
//     echo "OK";
// }
?>
<!DOCTYPE html>
<html>
<head>
<meta charset='utf-8'/>
<title>Lab for testing</title>
</head>
<body>
<pre>
<?php
$micro = microtime(true);
$val = $micro.random_bytes(16);
echo hash('sha512',($val)) . "<br />";
echo hash('sha512',($micro.random_bytes(16))) . "<br />";
echo $val . "<br />";
?>
</pre>
</body>
