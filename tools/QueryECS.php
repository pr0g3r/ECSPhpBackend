<?php
/*
http://localhost/ecs/QueryECS.php
http://192.168.0.125/ecs/tools/QueryECS.php

INFO:
Copy order IDs and paste into http://192.168.0.24/FESP-REFACTOR/tools/CourierTrackingName.php
then click submit.
Copy and paste results back to this page. This will create a refunds CSV.
 */


class QueryECS
{
    private $ecs_db;
    
    public function __construct()
    {
        ini_set('display_errors', '1');
        ini_set('display_startup_errors', '1');
        error_reporting(E_ALL);
        // set_time_limit(0);
        // ini_set("memory_limit", "-1");
        
        $this->ecs_db = new PDO('sqlite:../db.sqlite3');
    }

    public function all_refunds_fnc($args=[])
    {
        $missing_data = [
            [
                'i' => '1188071',
                'c' => 'W48I',
                't' => 'JD0002219852358740',
                'n' => 'wahoo54321',
            ],[
                'i' => '202-2726031-8844335-a',
                'c' => 'W24I',
                't' => 'JD0002219852250515',
                'n' => 'Anita Dale',
            ],[
                'i' => '1185528',
                'c' => 'W48I',
                't' => 'JD0002219852281353',
                'n' => 'dkh041270',
            ],
        ];
        
        // [{"i":"1188071","c":"W48I","t":"JD0002219852358740","n":"wahoo54321"},{"i":"202-2726031-8844335-a","c":"W24I","t":"JD0002219852250515","n":"Anita Dale"},{"i":"1185528","c":"W48I","t":"JD0002219852281353","n":"dkh041270"}]
        
        
        $sql = "SELECT * FROM `order_reason`";
        $lkup_reasons = $this->ecs_db->query($sql)->fetchAll(PDO::FETCH_KEY_PAIR);
        
        $sql = "SELECT `id`,`name` FROM `order_option`";
        $lkup_options = $this->ecs_db->query($sql)->fetchAll(PDO::FETCH_KEY_PAIR);
        
        
        $sql = "SELECT * FROM `order_refund`";
        $ecs_records = $this->ecs_db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        
        $tmp = [];
        foreach ($ecs_records as $i => $rec) {
            
            if ( preg_match('/[A-Z]/', $rec['order_id']) ) {
                $source = 'Manual';
            }
            
            elseif (6 == strlen($rec['order_id'])) {
                $source = 'Website';
            }
            elseif (7 == strlen($rec['order_id'])) {
                $source = 'Ebay';
            }
            elseif ( preg_match('/^\d{3}-\d{7}-\d{6}/', $rec['order_id']) ) {
                $source = 'Amazon';
            }
            
            elseif ( preg_match('/[0-9A-Z]/', $rec['order_id']) ) {
                $source = 'Manual';
            }
            
            if ( $rec['full_refund']) { $full_refund = 'true'; }
            else { $full_refund = 'false'; }
            
            if ( $rec['void_order']) { $void_order = 'true'; }
            else { $void_order = 'false'; }
            
            if ( $rec['dor']) { $dor = 'true'; }
            else { $dor = 'false'; }
            
            
            $tmp[] = [
                'order'    => $rec['order_id'],
                'source'   => $source,
                'courier'  => '',
                'tracking' => '',
                'name'     => '',
                'reason'   => $lkup_reasons[$rec['reason_id']],
                'option'   => $lkup_options[$rec['option_id']],
                'created'  => $rec['created'],
                'refund'   => $full_refund,
                'amount'   => $rec['amount'],
                'void'     => $void_order,
                'dor'      => $dor,
                'notes'    => $rec['notes'],
            ];
        }
        
        return $tmp;
    }

    public function resends_gallup_fnc($arr=[])
    {
        if (count($arr) > 0) {
            $meta_db = new PDO('sqlite:meta.db3');
            
            $orderIDs = array_keys($arr);
            $orderIDs_str = implode("','", $orderIDs);
            
            $sql = "SELECT * FROM `meta` WHERE `orderID` IN ('$orderIDs_str')";
            $meta_records = $meta_db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
            
            // echo '<pre style="background:#111; color:#b5ce28; font-size:11px;">'; print_r($meta_records); echo '</pre>'; die(); //DEBUG
            
            $meta = [];
            foreach( $meta_records as $rec ){
                $tracking = json_decode($rec['info'], True)['tracking_id'];
                
                if ('H' == substr($tracking, 0,1) && 16 == strlen($tracking)) {
                    $meta[$rec['orderID']] = 1;
                }
            }
            
            $tmp = [];
            foreach( $meta_records as $rec ){
                if (isset($meta[$rec['orderID']])) {
                    $tmp[] = $rec;
                }
            }
            
            $tmp = array_map(function($rec) use ($arr){
                return [
                    'orderID' => $rec['orderID'],
                    'created' => $arr[$rec['orderID']],
                    'tracking_id' => json_decode($rec['info'], True)['tracking_id'],
                ];
            }, $tmp);
            
            
            $return = [];
            $return[] = "orderID\tcreated\ttracking_id";
            foreach( $tmp as $rec ){
                $return[] = $rec['orderID'] ."\t".$rec['created'] ."\t".$rec['tracking_id'];
            }
            
            return implode("\n", $return);
        }
        else{
            $sql = "SELECT `order_id`,`created` FROM `order_resend` WHERE `room_id` = 6 AND `created` > '2022-05-31' AND `created` < '2022-07-01'";
            $ecs_records = $this->ecs_db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
            
            $ecs_records = array_map(function($rec){
                return "'".$rec['order_id']."'=>'".$rec['created']."'";
            }, $ecs_records);
            
            return implode(",\n    ", $ecs_records);
        }
    }
}

$obj = new QueryECS();

$results = $obj->all_refunds_fnc();

if (!isset($_POST['missing_data'])) {
    $order_ids = array_column($results, 'order');
    $order_ids_str = implode("\n", $order_ids);
    // echo '<pre style="background:#111; color:#b5ce28; font-size:11px;">'; print_r($order_ids_str); echo '</pre>'; die();
}
else {
    $missing_data = json_decode($_POST['missing_data'],true);
    
    foreach ($results as $i => $rec) {
        $results[$i]['courier']  = $missing_data[$rec['order']]['courier'];
        $results[$i]['tracking'] = $missing_data[$rec['order']]['tracking'];
        $results[$i]['name']     = $missing_data[$rec['order']]['buyer'];
    }
    
    $csv = [];
    $csv[] = "Order\tSource\tCourier\tTracking\tName\tReason\tOption\tCreated\tRefund\tAmount\tVoid\tDOR\tNotes";

    foreach ($results as $rec) {
        $csv[] = $rec['order']."\t".$rec['source']."\t".$rec['courier']."\t".$rec['tracking']."\t".$rec['name']."\t".$rec['reason']."\t".$rec['option']."\t".$rec['created']."\t".$rec['refund']."\t".$rec['amount']."\t".$rec['void']."\t".$rec['dor']."\t".$rec['notes'];
    }
    
    $filepath = 'refunds_'.date('Y-m-d').'.csv';
    $file = fopen($filepath,"w");
    fwrite($file,  implode("\n", $csv) );
    fclose($file);
    
    if( file_exists($filepath) ){
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="'.basename($filepath).'"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($filepath));
        readfile($filepath);
        unlink($filepath);
        // exit;
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>ECS Refunds</title>

<style>
    .w200 { width: 200px; }
    .h200 { height: 200px; }
    .ml20 { margin-left: 20px; }
    .fl { float: left; }
</style>

</head>
<body>

<?php if (isset($order_ids_str)) { ?>
<textarea id="order_ids" class="w200 h200 fl" autofocus><?= $order_ids_str ?></textarea>

<form method="post" class="fl">
    <textarea name="missing_data" class="w200 h200 ml20" placeholder="add missing data"></textarea>
    
    <input type="submit" name="submit">
</form>

<?php } ?>




<script src="https://code.jquery.com/jquery-1.12.4.min.js"></script>
<!-- <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script> -->
<script>
$(function() {
    $("#order_ids").select();
});
</script>
</body>
</html>





<!--
// echo '<pre style="background:#111; color:#b5ce28; font-size:11px;">'; print_r($results); echo '</pre>'; die();

/*
$arr = [
    '026-4668363-1465107-a'=>'2022-06-01',
    '206-2048584-4342753-a'=>'2022-06-01',
    '1207468-b'=>'2022-06-06',
    '202-9165227-3773156-a'=>'2022-06-06',
    '026-6826690-9833133-a'=>'2022-06-06',
    '204-6137574-0753129-a'=>'2022-06-07',
    '026-1051330-6761100-a'=>'2022-06-07',
    '202-9893255-2928323-a'=>'2022-06-07',
    '202-6993205-3949955-a'=>'2022-06-09',
    '1214407-a'=>'2022-06-13',
    '1213378-a'=>'2022-06-14',
    '1216210-a'=>'2022-06-15',
    '202-4003450-7125904-a'=>'2022-06-15',
    '1215163-a'=>'2022-06-15',
    '206-4280002-1631556-a'=>'2022-06-16',
    '203-9726779-0064309-a'=>'2022-06-16',
    '205-7184534-1209120-a'=>'2022-06-17',
    '178596-a'=>'2022-06-17',
    '204-8482697-9813943-a'=>'2022-06-17',
    '206-5280397-3208369-a'=>'2022-06-17',
    '203-8887841-1081129-a'=>'2022-06-17',
    '202-9726811-9142737-a'=>'2022-06-17',
    '204-1853837-9403509-a'=>'2022-06-21',
    '204-2925704-2232356-a'=>'2022-06-21',
    '204-1592944-3477120-a'=>'2022-06-21',
    '204-9751347-9927515-a'=>'2022-06-22',
    '205-1948046-6402700-a'=>'2022-06-22',
    '206-3935403-9601140-a'=>'2022-06-21',
    '206-0692480-2203510-a'=>'2022-06-22',
    '206-0626169-5111523-a'=>'2022-06-23',
    '203-8237847-2393901-a'=>'2022-06-24',
    '181540-a'=>'2022-06-27',
    '026-3352508-2227500-a'=>'2022-06-27',
    '181409-a'=>'2022-06-28',
    '203-1912402-0573942-a'=>'2022-06-28',
    '202-1992751-5488346-a'=>'2022-06-28',
    '206-0338928-2049131-a'=>'2022-06-28',
    '206-3636450-5088365-a'=>'2022-06-28',
    '206-8957698-3156321-a'=>'2022-06-29',
    '1220533-a'=>'2022-06-29',
    '204-7279141-5961128-a'=>'2022-06-29',
    '204-6137899-4335546-a'=>'2022-06-29',
    '203-7979660-3541962-a'=>'2022-06-30',
    '202-3595517-1861146-a'=>'2022-06-30',
    '204-0297081-3649917-a'=>'2022-06-30',
    '203-0620188-0505130-a'=>'2022-06-30',
    '206-4047694-5565901-a'=>'2022-06-30',
    '206-3081124-8730756-a'=>'2022-06-30',
    '203-2972979-6153153-a'=>'2022-06-30'
];
$results = $obj->resends_gallup_fnc($arr);

echo '<pre style="background:#111; color:#b5ce28; font-size:11px;">'; print_r($results); echo '</pre>';
*/
/*
1 Amazon
2 Ebay
3 Website
4 Onbuy
5 Manual
6 Prosalt
 */

-->