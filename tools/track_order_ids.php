<?php
/*
http://192.168.0.125/ecs/tools/track_order_ids.php
*/

/**
 * 
 * 
 * 
 * 
 */


$db = new PDO('sqlite:../db.sqlite3');

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

/*
if( !isset($_POST['first10']) ){
	$stmt_refund = $db->prepare("UPDATE `order_refund` SET `option_id` = ? WHERE `option_id` = ?");
	$stmt_resend = $db->prepare("UPDATE `order_resend` SET `option_id` = ? WHERE `option_id` = ?");
	
	$claimable_options = array_values($options_lkup);
	
	$db->beginTransaction();
	foreach( $claimable_options as $id ){
		$new_option_id = $id + 50;
		$stmt_refund->execute([ $id, $new_option_id ]);
		$stmt_resend->execute([ $id, $new_option_id ]);
	}
	$db->commit();
}
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




$claims = [];
$order_refund_resend = array_merge($order_ids['order_refund'],$order_ids['order_resend']);
foreach( $order_refund_resend as $order_id ){
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


/*=========================================================================
| Order IDs in $order_ids['order_claims'] (973) should exist in $claims (377)
|========================================================================*/

if( isset($_GET['order_claims']) ){
	echo '<pre style="background:#111; color:#b5ce28; font-size:11px;">'; print_r( array_keys($order_ids['order_claims']) ); echo '</pre>'; die(); //DEBUG
}


// $array_intersect = array_intersect( array_keys($order_ids['order_claims']), array_keys($claims) );

// echo '<pre style="background:#111; color:#b5ce28; font-size:11px;">'; print_r( $array_intersect ); echo '</pre>'; die(); //DEBUG

// echo '<pre style="background:#111; color:#b5ce28; font-size:11px;">'; print_r( count($claims_list) ); echo '</pre>';
// echo '<pre style="background:#111; color:#b5ce28; font-size:11px;">'; print_r( array_values($array_intersect) ); echo '</pre>'; die();

usort($claims, function($a, $b) {
	return $a['created'] <=> $b['created'];
});

if( isset($_POST['first10']) ){
	$hide_order_ids = [];
	foreach( $claims as $i => $rec ){
		if( $i > 1 ){
			$hide_order_ids[] = $rec['order_id'];
		}
	}

	$stmt_refund = $db->prepare("UPDATE `order_refund` SET `option_id` = ? WHERE `option_id` = ? AND `order_id` = ?");
	$stmt_resend = $db->prepare("UPDATE `order_resend` SET `option_id` = ? WHERE `option_id` = ? AND `order_id` = ?");
	
	$new_option_id = $option_id + 50;
	$sql = [];
	$db->beginTransaction();
	foreach( $hide_order_ids as $order_id ){
		// $stmt_refund->execute([ $new_option_id, $option_id, $order_id ]);
		// $stmt_resend->execute([ $new_option_id, $option_id, $order_id ]);
	}
	$db->commit();
}

// $in = str_replace(PHP_EOL, '', $in);
// $in = preg_replace('/\s+/', '', $in);


if( count($claims) ){
	$args_array_to_table = fmt_array2tbl_array_fnc($claims);
}

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
<title>Reduce Claims</title>

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
		<select name="courier">
			<option value="" disabled selected>Courier</option>
			<?php foreach ($courier_lkup as $key => $val){ ?>
			<option value="<?= $val ?>"<?= isset($_POST['courier']) && $val == $_POST['courier'] ? ' SELECTED' : '' ?>><?= $key ?></option>
			<?php } ?>
		</select>
		
		<select name="option">
			<option value="" disabled selected>Option</option>
			<?php foreach ($options_lkup as $key => $val){ ?>
			<option value="<?= $val ?>"<?= isset($_POST['option']) && $val == $_POST['option'] ? ' SELECTED' : '' ?>><?= $key ?></option>
			<?php } ?>
		</select>
		
		<label><input type="checkbox" name="first10"<?= isset($_POST['first10']) ? ' checked' : '' ?>> Only display first 10 records.</label>
		
		<input type="submit" name="submit" value="submit" class="w100 curspointer">
	</form>
	
	<h2 class="fs40">Total: <?= count($claims) ?></h2>
</div>


</body>
</html>
