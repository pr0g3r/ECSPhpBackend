<?php
/*
http://192.168.0.125/ecs/tools/export_claims.php
*/

/**
 * 
 * 
 * 
 * 
 */


$db = new PDO('sqlite:../db.sqlite3');
// $db = new PDO('sqlite:db.sqlite3');

$sql = "SELECT name,id FROM `order_option` WHERE `claimable` = 1";
$results = $db->query($sql);
$options_lkup = $results->fetchAll(PDO::FETCH_KEY_PAIR);
/*
  [Lost - Part Tracking] => 2
  [Damaged In Transit] => 3
  [Arrived Damaged] => 4
  [Partial Damage] => 6
  [Damaged In Transit Scan] => 16
  [Delivered Not Recieved] => 25
  [damaged storage bucket] => 27
  [claim no action needed] => 28
*/

$courier_lkup = [
	'H' => 1,
	'HI' => 2,
	'W24' => 12,
	'W24I' => 13,
];

/*
Lost - Part Tracking
Damaged In Transit
Arrived Damaged
Partial Damage
Damaged In Transit Scan
Delivered Not Recieved
damaged storage bucket
*/

if( isset($_POST['courier']) ){
	$courier_id = $_POST['courier'];
}
if( isset($_POST['option']) ){
	$option_id = $_POST['option'];
}

$tbls = [
	'order_refund',
	'order_resend',
	'order_claims',
];

$order_ids = [];
$order_ids_created_lkup = [];
$order_ids_option_id_lkup = [];
foreach( $tbls as $tbl ){
	$where = isset($option_id) ? " WHERE `option_id` = $option_id" : '';
	
	$sql = 'order_claims' == $tbl ? "SELECT order_id,'1' FROM `$tbl`" : "SELECT order_id,created,option_id FROM `$tbl`$where";
	/*
	SELECT order_id,created,option_id FROM `order_refund`
	SELECT order_id,created,option_id FROM `order_resend`
	SELECT order_id,'1' FROM `order_claims`
	*/
	
	$results = $db->query($sql);
	if( 'order_claims' == $tbl ){
		$order_ids[$tbl] = $results->fetchAll(PDO::FETCH_KEY_PAIR);
	}
	else{
		$tmp = $results->fetchAll(PDO::FETCH_ASSOC);
		$order_ids_arr = [];
		foreach( $tmp as $rec ){
			$order_ids_arr[] = $rec['order_id'];
			$order_ids_created_lkup[$rec['order_id']] = $rec['created'];
			/*
			  [1188071] => 2022-03-28
			  [202-2726031-8844335-a] => 2022-03-28
			  [1185528] => 2022-03-29
			  etc.
			*/
			$order_ids_option_id_lkup[$rec['order_id']] = $rec['option_id'];
			/*
			  [1188071] => 13
			  [202-2726031-8844335-a] => 4
			  [1185528] => 23
			  etc.
			*/
		}
		$order_ids[$tbl] = $order_ids_arr;
	}
}

$unique_order_ids = array_keys( array_flip( array_merge($order_ids['order_refund'],$order_ids['order_resend']) ) );
$in_order_ids = implode("','", $unique_order_ids);

$and = isset($courier_id) ? " AND `courier_id` = $courier_id" : '';

$sql = "SELECT order_id,courier_id FROM `order_order` WHERE `order_id` IN ('$in_order_ids')$and";
$results = $db->query($sql);
$order_ids['permitted_couriers'] = $results->fetchAll(PDO::FETCH_KEY_PAIR);

$flip_courier_lkup = array_flip($courier_lkup);
$flip_options_lkup = array_flip($options_lkup);

/*
Array
(
    [2] => Lost - Part Tracking
    [3] => Damaged In Transit
    [4] => Arrived Damaged
    [6] => Partial Damage
    [16] => Damaged In Transit Scan
    [25] => Delivered Not Recieved
    [27] => damaged storage bucket
)
*/



/**
 * 
 */

$claims = [];
$order_refund_resend = array_merge($order_ids['order_refund'],$order_ids['order_resend']);
foreach ( $order_refund_resend as $order_id ) {
	if( isset($order_ids['permitted_couriers'][$order_id])
		&& !isset($order_ids['order_claims'][$order_id])
		&& isset($flip_courier_lkup[$order_ids['permitted_couriers'][$order_id]])
		&& isset($flip_options_lkup[$order_ids_option_id_lkup[$order_id]])
		){
		$claims[$order_id] = [
			'order_id' => $order_id,
			'created' => $order_ids_created_lkup[$order_id],
			'courier' => $flip_courier_lkup[$order_ids['permitted_couriers'][$order_id]],
			'option' => $flip_options_lkup[$order_ids_option_id_lkup[$order_id]],
			
		];
	}
}

// echo '<pre style="background:#111; color:#b5ce28; font-size:11px;">'; print_r($claims); echo '</pre>'; die(); //DEBUG

$redirect = false;

if (isset($_POST['export_csv'])) {
	header('Content-Type: text/csv');
	header('Content-Disposition: attachment;filename=Claims.csv');

	$csvHandle = fopen('php://output', 'w');
	fprintf($csvHandle, chr(0xEF).chr(0xBB).chr(0xBF)); // output utf-8 (stops £ becoming Â£)
	fputcsv($csvHandle, [
	    'Status',
	    'Order ID',
	]);


	foreach ($claims as $claim) {
	    fputcsv($csvHandle, [
	        $claim['option'],
	        $claim['order_id'],
	    ]);
	}
	fclose($csvHandle);
	die();
}
elseif (isset($_POST['remove_claims_yes'])) {
	$claims_ids = array_keys($claims);
	$total_order_ids = count($claims_ids);
	
	if ( $total_order_ids ) {
		$tbl_name = 'order_claims';
		
		$sql = "SELECT `form_id` FROM `$tbl_name` ORDER BY `id` DESC LIMIT 1";
        $results = $db->query($sql);
        $last_form_id_plus1 = $results->fetchAll(PDO::FETCH_COLUMN)[0] +1;
        
        $sql = "INSERT INTO `$tbl_name` (`form_id`,`order_id`,`rejected`,`total`) VALUES ('$last_form_id_plus1', '$order_id', '0', '8')";
        
        $stmt = $db->prepare("INSERT INTO `$tbl_name` (`form_id`,`order_id`,`rejected`,`total`) VALUES (?,?,?,?)");
        $db->beginTransaction();
        foreach ($claims_ids as $order_id) {
            $stmt->execute([ $last_form_id_plus1, $order_id, 0, 8 ]);
            unset($claims[$order_id]);
            
            $redirect = true;
        }
        $db->commit();
	}
}



usort($claims, function($a, $b) {
	return $a['created'] <=> $b['created'];
});


// $in = str_replace(PHP_EOL, '', $in);
// $in = preg_replace('/\s+/', '', $in);


if( count($claims) ){
	$args_array_to_table = fmt_array2tbl_array_fnc($claims);
}

/**
 * [description]
 * @param  array $array [0] =>	[
									[order_id] => 206-0798536-3541910-a
									[created] => 2022-05-31
									[courier] => W24I
									[option] => Damaged In Transit
								]

						[1] => 	[
									[order_id] => 206-0798536-3541910-a
									[created] => 2022-05-31
									[courier] => W24I
									[option] => Damaged In Transit
								]
						etc.

 * @return array        [header] =>	[
										[0] => order_id
										[1] => created
										[2] => courier
										[3] => option
									]

						[body] =>	[
										[0] =>	[
													[0] => 206-0798536-3541910-a
													[1] => 2022-05-31
													[2] => W24I
													[3] => Damaged In Transit
												]
										
										[1] =>	[
													[0] => 206-0798536-3541910-a
													[1] => 2022-05-31
													[2] => W24I
													[3] => Damaged In Transit
												]
									]
 */
function fmt_array2tbl_array_fnc($array)
{
	$return = [];
	$return['header'] = array_keys($array[0]);
	
	foreach( $array as $rec ){
		$return['body'][] = array_values($rec);
	}
	
	return $return;
}

function array_to_table_fnc($args)
{
	$tbl_class = isset($args['tbl_class']) ? $args['tbl_class'] : 'style-tbl';
	
	$html = [];
	$html[] = '<table class="'.$tbl_class.'"><thead><tr>';
	foreach ($args['header'] as $h_cell) {
		$html[] = "<th>$h_cell</th>";
	}
	$html[] = '</tr></thead>';

	$html[] = '<tbody>';

	foreach( $args['body'] as $row ){
		$html[] = '<tr>';
		foreach( $row as $cell ){
			$html[] = "<td>$cell</td>";
		}
		$html[] = '</tr>';
	}

	$html[] = '</tbody>';
	$html[] = '</table>';

	return implode("\n", $html);
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Export Claims</title>

<?php if ($redirect) { ?>
<script>window.location.href = "export_claims.php";</script>
<?php } ?>
<style>
	*{
		font-size:12px;
	}
	/* Format Table */
	table.style-tbl{
		border-collapse:collapse;
		margin-bottom:40px;
	}
	.style-tbl th,.style-tbl td{
		border:1px solid #000;
		padding:5px;
		vertical-align:top;
	}
	.style-tbl td{ text-align:left; }
	.style-tbl tr:nth-child(2n+2){ background: rgb(228, 238, 250); } /* light blue */
	.style-tbl thead tr{ background: rgb(238, 238, 238); } /* light grey */
	
	.fl{ float:left; }
	.ml300{ margin-left:200px; }
	.mr30{ margin-right:30px; }
	.fs40{ font-size: 40px; }
	.w100{ width: 100px; }
	.w500{ width: 500px; }
	.curspointer{ cursor: pointer; }
	
	/* Button  */
	.btn {
		background: #579;
		border: 1px solid #124;
		color: #fff;
		padding: 4px 8px;
		text-align: center;
		text-decoration: none;
		display: inline-block;
		font-family: arial;
		font-size: 1.6em;
		border-radius: 4px;
		cursor: pointer;
		-webkit-transition-duration: 0.1s;
		transition-duration: 0.1s;
	}
	.btn:hover {
	  background: #005987;
	  color: #fff;
	}
	
	.warning {
		background: #900;
	}
	.warning:hover {
	  background: #f00;
	  color: #fff;
	}
</style>

</head>
<body>

<div class="fl w500">
	<?php if( count($claims) ){ ?>
	<?= array_to_table_fnc($args_array_to_table); ?>
	<?php }else{ ?>
	<h2 class="fs40">No results!</h2>
	<?php } ?>
</div>

<div class="fl">
	<form method="post">
		<select name="courier" class="btn">
			<option value="" disabled selected>Courier</option>
			<?php foreach ($courier_lkup as $key => $val){ ?>
			<option value="<?= $val ?>"<?= isset($_POST['courier']) && $val == $_POST['courier'] ? ' SELECTED' : '' ?>><?= $key ?></option>
			<?php } ?>
		</select>
		
		<select name="option" class="btn">
			<option value="" disabled selected>Option</option>
			<?php foreach ($options_lkup as $key => $val){ ?>
			<option value="<?= $val ?>"<?= isset($_POST['option']) && $val == $_POST['option'] ? ' SELECTED' : '' ?>><?= $key ?></option>
			<?php } ?>
		</select>
		
		<input type="submit" name="submit" value="Update" class="btn w100 curspointer">
		<input type="submit" name="export_csv" value="Export CSV" class="btn  curspointer">
		<input type="submit" name="remove_claims" value="Remove Claims" class="btn  curspointer">
		<?php if (isset($_POST['remove_claims'])) { ?>
		<input type="submit" name="remove_claims_yes" value="SURE?" class="btn warning  curspointer">
		<?php } ?>
	</form>
	
	<h2 class="fs40">Total: <?= count($claims) ?></h2>
</div>


</body>
</html>
