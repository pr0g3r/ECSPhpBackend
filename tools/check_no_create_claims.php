<?php
/*
http://192.168.0.125/ecs/tools/check_no_create_claims.php
*/

// $path = 'C:\inetpub\wwwroot\\';
// $cache = "$path\FESP-REFACTOR\cache.db3";

// $cache = "C:\inetpub\wwwroot\FESP-REFACTOR\cache.db3";
// $db_fesp = new PDO('sqlite:'.$cache);

$db_test = new PDO('sqlite:/mnt/deepthought/FESP-REFACTOR/missingorders.db3');

$sql = "SELECT * FROM `orders` LIMIT 10";
$results = $db_test->query($sql);
$test = $results->fetchAll(PDO::FETCH_ASSOC);

echo '<pre style="background:#111; color:#b5ce28; font-size:11px;">'; print_r($test); echo '</pre>'; die(); //DEBUG






$db_ecs = new PDO('sqlite:../db.sqlite3');
// $db_fesp = new PDO('sqlite:/mnt/deepthought/FESP-REFACTOR/query_cache_db/cache.db3');
$db_fesp = new PDO('sqlite:/mnt/deepthought/FESP-REFACTOR/cache.db3');

$sql = "SELECT * FROM `orders` LIMIT 10";
$results = $db_fesp->query($sql);
$test = $results->fetchAll(PDO::FETCH_ASSOC);

echo '<pre style="background:#111; color:#b5ce28; font-size:11px;">'; print_r($test); echo '</pre>'; die(); //DEBUG






if( isset($_POST['submit']) && 'Add to Claims Table' == $_POST['submit'] ){
	$order_ids = explode(',', $_POST['order_ids']);
	
	// Get last 'form_id' value to increment
	$sql = "SELECT form_id FROM `order_claims` ORDER BY `id` DESC LIMIT 1";
	$results = $db_ecs->query($sql);
	$form_id = $results->fetchAll(PDO::FETCH_COLUMN);
	$form_id_inc = $form_id[0]+1;
	
	// $sql = "UPDATE `order_claims` SET `form_id` = '24', `total` = '0' WHERE `order_id` IN ('1182406-a','205-3042224-1593150-a')";
	// $db_ecs->query($sql); die();
	
	// echo '<pre style="background:#111; color:#b5ce28; font-size:11px;">'; print_r($form_id_inc); echo '</pre>'; die(); //DEBUG
	
	$stmt = $db_ecs->prepare("INSERT INTO `order_claims` (`form_id`,`order_id`,`rejected`,`total`) VALUES (?,?,?,?)");
	
	// $sql = [];
	$db_ecs->beginTransaction();
	foreach ($order_ids as $order_id) {
		$stmt->execute([ $form_id_inc,$order_id,'0','0' ]);
		// $sql[] = "INSERT INTO `order_claims` (`form_id`,`order_id`,`rejected`,`total`) VALUES ('$form_id_inc','$order_id','0','0')";
	}
	$db_ecs->commit();
	// echo '<pre style="background:#111; color:#b5ce28; font-size:11px;">'; print_r($sql); echo '</pre>';
}




$sql = "SELECT name,id FROM `order_option` WHERE `claimable` = 1";
$results = $db_ecs->query($sql);
$options_lkup = $results->fetchAll(PDO::FETCH_KEY_PAIR);

// $option_ids = array_values($options_lkup);
$option_ids_str = implode("','", $options_lkup);

$sql = "SELECT order_id FROM `order_refund` WHERE `option_id` IN ('$option_ids_str')";
$results = $db_ecs->query($sql);
$order_refund = $results->fetchAll(PDO::FETCH_COLUMN);

$sql = "SELECT order_id FROM `order_resend` WHERE `option_id` IN ('$option_ids_str')";
$results = $db_ecs->query($sql);
$order_resend = $results->fetchAll(PDO::FETCH_COLUMN);

$order_refund_resend = array_merge($order_refund,$order_resend);
$order_refund_resend = array_keys( array_flip($order_refund_resend) ); // remove duplicate order_ids

unset($_GET['ignore_claims']);

if( !isset($_GET['ignore_claims']) ){
	$sql = "SELECT order_id,'1' FROM `order_claims`";
	$results = $db_ecs->query($sql);
	$order_claims = $results->fetchAll(PDO::FETCH_KEY_PAIR);
	$order_claims = array_keys($order_claims);
	$order_refund_resend_minus_order_claims = array_diff($order_refund_resend, $order_claims);
	$option_ids_str = implode("','", $order_refund_resend_minus_order_claims);
}
else{ $option_ids_str = implode("','", $order_refund_resend); }




// $option_ids_str = "1182406-a";

// echo '<pre style="background:#111; color:#b5ce28; font-size:11px;">'; print_r($option_ids_str); echo '</pre>'; die(); //DEBUG

$sql = "SELECT * FROM `orders` WHERE `orderID` IN ('$option_ids_str')";
$results = $db_fesp->query($sql);
$cache = $results->fetchAll(PDO::FETCH_ASSOC);

$no_item_order_ids = [];
foreach( $cache as $rec ){
	$order = json_decode($rec['content'],true);
	
	if( !count($order['items']) ){
		$no_item_order_ids[] = $rec['orderID'];
	}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>No Item Orders</title>

<!-- <script src="https://code.jquery.com/jquery-1.12.4.min.js"></script> -->

<style>
	body{ background:#111; }
	[name="submit"]{
		cursor:pointer;
		margin-left:30px;
		font-size:20px;
		background:#9f9;
		border-radius:8px;
		border:0;
		padding:6px;
		padding-left:14px;
		padding-right:14px;
	}
	[name="submit"]:hover{ background:#4c4; }
</style>

</head>
<body>

<?php if( !count($no_item_order_ids) ){
	echo '<pre style="background:#111; color:#b5ce28; font-size:28px; padding:30px;">'; echo "No Missing Items!"; echo '</pre>';
}
else{
	echo '<pre style="background:#111; color:#b5ce28; font-size:18px; line-height:26px; padding:30px; padding-bottom:0">'; echo "No Item Orders:\n ➤ "; echo implode("\n ➤ ", $no_item_order_ids); echo '</pre>';

	$no_item_order_ids_str = implode(',', $no_item_order_ids);
	?>
	<form method="post">
		<input type="hidden" name="order_ids" value="<?= $no_item_order_ids_str ?>">
		<input type="submit" name="submit" value="Add to Claims Table"> <code style="color:#fff; font-size:13px;">* No longer displays on Claims page.</code>
	</form>
<?php } ?>


<script>
$(function() {});
</script>
</body>
</html>