<?php
require('includes/application_top.php');
include('../posecuritycode.php');
$securitycode = PO_SECURITY_KEY;
//
// +----------------------------------------------------------------------+
// |zen-cart Open Source E-commerce                                       |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003 The zen-cart developers                           |
// |                                                                      |
// | http://www.zen-cart.com/index.php                                    |
// |                                                                      |
// | Portions Copyright (c) 2003 osCommerce                               |
// +----------------------------------------------------------------------+
// | This source file is subject to version 2.0 of the GPL license,       |
// | that is bundled with this package in the file LICENSE, and is        |
// | available through the world-wide-web at the following url:           |
// | http://www.zen-cart.com/license/2_0.txt.                             |
// | If you did not receive a copy of the zen-cart license and are unable |
// | to obtain it through the world-wide-web, please send a note to       |
// | license@zen-cart.com so we can mail you a copy immediately.          |
// +----------------------------------------------------------------------+
//  $Id: customers.php 1612 2005-07-19 21:09:38Z ajeh $
//

require(DIR_WS_CLASSES . 'currencies.php');
$currencies = new currencies();                                 
define('FPDF_FONTPATH','fpdf/font/');
require('pdfpack.php');
define('PO_PDFP_CUST_COMMENT_PARTIAL',"Customer Comments\n\n{customers_comments}");
//Update Products Default Subcontractor
if (PO_MANUFACTURER_SC == 1) {
        $query_find_sc=mysql_query("SELECT  default_subcontractor, manufacturers_id FROM ".TABLE_MANUFACTURERS);
	while($row_find_sc=mysql_fetch_array($query_find_sc, MYSQL_NUM)) {
	       $result_assign=mysql_query("UPDATE ".TABLE_PRODUCTS." SET default_subcontractor='$row_find_sc[0]' WHERE  manufacturers_id ='$row_find_sc[1]'");
	}
}

//load email templates
@ $wp1 = fopen("../email/email_header.txt", 'r');
@ $wp2 = fopen("../email/email_products.txt", 'r');
@ $wp3 = fopen("../email/email_footer.txt", 'r');

//load text file attachment templates
@ $tf1 = fopen("../email/textattach_header.txt", 'r');
@ $tf2 = fopen("../email/textattach_products.txt", 'r');
@ $tf3 = fopen("../email/textattach_footer.txt", 'r');

function findStateAbbreviations($statesfullname) {
global $db;
$statetoreturn = $statesfullname;
$zone_query = "select zone_code
                       from " . TABLE_ZONES . " z 
                       left join " . TABLE_COUNTRIES . " c on c.countries_id = z.zone_country_id
                       where zone_name = '" . $statesfullname . "'";
        $zone = $db->Execute($zone_query);
        if ($zone->RecordCount() > 0 ) 
           $statetoreturn = $zone->fields['zone_code'];
return $statetoreturn;
}

function metro_attibutes($orders_products_id, $orders_id){
    global $db;
        //get attributes into a reasonable array
        $opa_array = $db->Execute("SELECT orders_id, orders_products_id, products_options, products_options_values
                                                                              FROM ".TABLE_ORDERS_PRODUCTS_ATTRIBUTES."
                                                                              WHERE orders_products_id=".$orders_products_id." AND orders_id=".$orders_id);
        while(!$opa_array->EOF){
            switch($opa_array->fields['products_options']){
                case "Longest Side Measurement Inches":
                $attr_array['long_side_in'] = $opa_array->fields['products_options_values'];
                    break;
                case "Longest Side Measurement Inches.":
                $attr_array['long_side_in'] = $opa_array->fields['products_options_values'];
                    break; 
                case "Longest Side Measurement Fractio":
                $attr_array['long_side_frac'] = $opa_array->fields['products_options_values'];
                    break;
                case "Shortest Side Measurement Inches":
                $attr_array['short_side_in'] = $opa_array->fields['products_options_values'];
                    break;
                case "Shortest Side Measurement Fracti":
                $attr_array['short_side_frac'] = $opa_array->fields['products_options_values'];
                    break;
                case "Frame Thickness":
                $attr_array['frame_thick'] = $opa_array->fields['products_options_values'];
                    break;
                case "Mesh Color":
                $attr_array['mesh_color'] = $opa_array->fields['products_options_values'];
                    break;
                case "Frame Color":
                $attr_array['frame_color'] = $opa_array->fields['products_options_values'];
                    break;
                case "Center Cross Bar":
                $attr_array['center_cross_bar'] = $opa_array->fields['products_options_values'];
                    break;
                case "Hardware Selection":
                $attr_array['hardware'] = $opa_array->fields['products_options_values'];
                    break;
                case "Screen Mesh Type":
                $attr_array['screen_mesh_type'] = $opa_array->fields['products_options_values'];
                    break;
                case "Save on shipping by ordering una":
                $attr_array['save_on_shipping'] = $opa_array->fields['products_options_values'];
                    break;
                case "Width Inches":
                $attr_array['width_in'] = $opa_array->fields['products_options_values'];
                    break;
                case "Width Fraction":
                $attr_array['width_frac'] = $opa_array->fields['products_options_values'];
                    break;
                case "Length Inches":
                $attr_array['length_in'] = $opa_array->fields['products_options_values'];
                    break;
                case "Length Fraction":
                $attr_array['length_frac'] = $opa_array->fields['products_options_values'];
                    break;
                case "Screen Material":
                    $attr_array['screen_material'] = $opa_array->fields['products_options_values'];
                    break;
                default:
                    $attr_name_other = $opa_array->fields['products_options'];
                    $attr_name_other = str_replace(" ", "_", $attr_name_other);
                    $attr_name_other = strtolower($attr_name_other);
                    $other_attr_array[$attr_name_other] = $opa_array->fields['products_options_values'];
                    break;
            }
            $opa_array->MoveNext();
        }
    if(count($attr_array) > 5 ){
        $screen_hardware = $attr_array['hardware'];
        $screen_hardware = str_replace("longest side", "LENGTH (2ND DIMENSION)", $screen_hardware);
        $screen_hardware = str_replace("shortest side", "WIDTH (1ST DIMENSION)", $screen_hardware);
        $screen_hardware = strtoupper($screen_hardware);
        
        $screen_desc = "\n";
        if($attr_array['save_on_shipping'] != ''){
          $screen_desc .=  "\n".strtoupper($attr_array['save_on_shipping']);
        }
        if($attr_array['short_side_in'] != ''){
        $screen_desc .= "\n".$attr_array['short_side_in']."-".$attr_array['short_side_frac'].'" X '.$attr_array['long_side_in']."-".$attr_array['long_side_frac'].'"'."\n";
        }
        if($attr_array['length_in'] != ''){
        $screen_desc .= "\n".$attr_array['width_in']."-".$attr_array['width_frac'].'" X '.$attr_array['length_in']."-".$attr_array['length_frac'].'"'."\n";   
        }
        $screen_desc .= "FRAME: ".strtoupper($attr_array['frame_thick']).'" '.strtoupper($attr_array['frame_color'])."\n";
        if($screen_hardware != ''){
        $screen_desc .= "HARDWARE: ".$screen_hardware."\n";
        }
        if($attr_array['center_cross_bar'] == "yes"){
            $screen_desc .= "CROSSBAR: ".$attr_array['center_cross_bar']."\n";
        }
        if($attr_array['screen_mesh_type'] != ''){
            $screen_desc .=  "Screen Mesh Type: ".strtoupper($attr_array['screen_mesh_type'])."\n";
        }
        if($attr_array['mesh_color'] != ''){
        $screen_desc .= "SCREEN TYPE: ".strtoupper($attr_array['mesh_color'])." FIBERGLASS \n";
        }
        if($attr_array['screen_material'] != ''){
            $screen_desc .= "Screen Material: ".$attr_array['screen_material']."\n";
        }
        if(is_array($attr_name_other)){
            foreach($attr_name_other as $oth_attr_name => $oth_attr_value){
                $screen_desc .= str_replace("_", " ", $oth_attr_name).": ";
                $screen_desc .= $oth_attr_value."\n";
            }
        }
        return $screen_desc;
    }
    else{
        return false;
    }
}

function get_po_price_value($orders_products_id){
    global $db;
    $po_products_price = $db->Execute("SELECT products_price, po_price FROM ".TABLE_ORDERS_PRODUCTS." WHERE orders_products_id=".$orders_products_id);
    if($po_products_price->fields['po_price'] != ''){
        return $po_products_price->fields['po_price'];
    }
    else{
        return $po_products_price->fields['products_price'];
    }
    
}

function get_po_shipping_value($orders_products_id){
    global $db;
    $po_products_price = $db->Execute("SELECT products_price, po_price, po_shipping FROM ".TABLE_ORDERS_PRODUCTS." WHERE orders_products_id=".$orders_products_id);
    if($po_products_price->fields['po_shipping'] != ''){
        return $po_products_price->fields['po_shipping'];
    }
    else{
        return 0;
    }
    
}

if (!function_exists("stripos")) {
  function stripos($str,$needle) {
   return strpos(strtolower($str),strtolower($needle));
  }
}

function extractBetweenDelimeters($inputstr,$delimeterLeft,$delimeterRight) {
    if (stripos($inputstr,$delimeterLeft) !== false) {
      $posLeft  = stripos($inputstr,$delimeterLeft)+strlen($delimeterLeft);
      if (stripos($inputstr,$delimeterRight,$posLeft+1) !== false)
        $posRight = stripos($inputstr,$delimeterRight,$posLeft+1);
      else
        return false;
      return  substr($inputstr,$posLeft,$posRight-$posLeft);
    }
    return false;
}
function areDelimetersThere($inputstr, $delimeterLeft, $delimeterRight, $delimeterLeft2, $delimeterRight2) {
    $ebd = extractBetweenDelimeters($inputstr,$delimeterLeft,$delimeterRight);
    if (stripos($inputstr, $delimeterLeft2 . $ebd . $delimeterRight2) !== false)
       return true;
    else
       return false;
}
function removeBetweenDelimeters($inputstr,$delimeterLeft,$delimeterRight) {
    if (stripos($inputstr,$delimeterLeft) !== false) { 
      $posLeft  = stripos($inputstr,$delimeterLeft);
      if (stripos($inputstr,$delimeterRight,$posLeft+1) !== false)
        $posRight = stripos($inputstr,$delimeterRight,$posLeft+1) + strlen($delimeterRight);
      else
        return $inputstr;
      $value2return = str_replace(substr($inputstr,$posLeft,$posRight-$posLeft), '', $inputstr);
      if (stripos(substr($inputstr,$posLeft+strlen($delimeterLeft),$posRight-$posLeft-strlen($delimeterLeft)), $delimeterLeft) !== false)
        return "***  ERROR - NESTED { + } OR { - } TAGS WITH SAME SUBCONTRACTOR VALUES.  ALL NESTED TAGS NEED DIFFERENT VALUES.  ***  " . $value2return;
      else
        return $value2return;
    } 
    return $inputstr;
}
if (!function_exists('zen_get_products_manufacturers_name')) {
  function zen_get_products_manufacturers_name($product_id) {
    global $db;

    $product_query = "select m.manufacturers_name
                      from " . TABLE_PRODUCTS . " p, " .
                            TABLE_MANUFACTURERS . " m
                      where p.products_id = '" . (int)$product_id . "'
                      and p.manufacturers_id = m.manufacturers_id";

    $product =$db->Execute($product_query);

    return ($product->RecordCount() > 0) ? $product->fields['manufacturers_name'] : "";
  }
}
?>
<html <?php echo HTML_PARAMS; ?>>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>">
<title><?php echo TITLE; ?></title>
<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
<link rel="stylesheet" type="text/css" href="includes/cssjsmenuhover.css" media="all" id="hoverJS">
<script language="javascript" src="includes/menu.js"></script>
<script language="javascript" src="includes/general.js"></script>

<script type="text/javascript">
  <!--
  function init()
  {
    cssjsmenu('navbar');
    if (document.getElementById)
    {
      var kill = document.getElementById('hoverJS');
      kill.disabled = true;
    }
  }
  // -->
</script>

</head>
<body onload="init()">
<!-- header //-->
<?php require(DIR_WS_INCLUDES . 'header.php'); ?>
<!-- header_eof //-->


<br>
<!-- body //--><br>
<table border="0" width='100%' cellspacing="0" cellpadding="0">

<!-- body_text //-->

<?php

// Get Order Status Numbers
$statusname = PO_NEW_ORDER_STATUS;
$querygot=mysql_query("SELECT orders_status_id FROM ".TABLE_ORDERS_STATUS." WHERE orders_status_name='$statusname'")
			or die("Failed to connect database: ");
			$rowgot=mysql_fetch_array($querygot, MYSQL_NUM);
                        $status_neworder = $rowgot[0];
$statusname = PO_SENT_PO_STATUS;
$querygot=mysql_query("SELECT orders_status_id FROM ".TABLE_ORDERS_STATUS." WHERE orders_status_name='$statusname'")
			or die("Failed to connect database: ");
			$rowgot=mysql_fetch_array($querygot, MYSQL_NUM);
                        $status_posentorder = $rowgot[0];
$statusname = PO_SHIPPED_STATUS;
$querygot=mysql_query("SELECT orders_status_id FROM ".TABLE_ORDERS_STATUS." WHERE orders_status_name='$statusname'")
			or die("Failed to connect database: ");
			$rowgot=mysql_fetch_array($querygot, MYSQL_NUM);
                        $status_shippedorder = $rowgot[0];
?>

<?php
// Get Recent PO Number
$query110=mysql_query("SELECT max(po_number) FROM ".TABLE_ORDERS_PRODUCTS."")
			or die("Failed to connect database: ");
			$row110=mysql_fetch_array($query110, MYSQL_NUM);
                        $lastponum=$row110[0];
?>

<?php
$numberofpostoreview=0;
// send reviewed e-mail
if($_POST['ereview']=='yes' && $_POST['checklastponum']==$lastponum) {
$messageStack = new messageStack();
$newzawartosc_tf = stripslashes($_POST['newzawartosc_tf']);
$html_msg['EMAIL_MESSAGE_HTML'] = str_replace('
','<br />',$_POST['ebody']);

$attachthepdf = $_POST['includepackinglistoption'];
$attachthetf = $_POST['includetextfileoption'];
$usepdffilename = $_POST['pdffilename'];
$usetffilename = $_POST['tffilename'];
$usetfmimetype = $_POST['tfmimetype'];

if ($attachthepdf == 'yes' && $attachthetf == 'yes') {
  $filestoattach = array (
    "0"  => array("file" => $usepdffilename, "mime_type" => "application/pdf"),
    "1"  => array("name" => $usetffilename, "raw_data" => $newzawartosc_tf, "mime_type" => $usetfmimetype)   
  );
  zen_mail($_POST['eaddress'],$_POST['eaddress'],$_POST['etitle'],$_POST['ebody'],PO_FROM_EMAIL_NAME,PO_FROM_EMAIL_ADDRESS, $html_msg, NULL ,$filestoattach);
}
if ($attachthepdf == 'yes' && $attachthetf != 'yes') {
  $filestoattach = array (
    "0"  => array("file" => $usepdffilename, "mime_type" => "application/pdf") 
  );
  zen_mail($_POST['eaddress'],$_POST['eaddress'],$_POST['etitle'],$_POST['ebody'],PO_FROM_EMAIL_NAME,PO_FROM_EMAIL_ADDRESS, $html_msg, NULL ,$filestoattach);
}
if ($attachthepdf != 'yes' && $attachthetf == 'yes') {
  $filestoattach = array (
    "0"  => array("name" => $usetffilename, "raw_data" => $newzawartosc_tf, "mime_type" => $usetfmimetype) 
  );
  zen_mail($_POST['eaddress'],$_POST['eaddress'],$_POST['etitle'],$_POST['ebody'],PO_FROM_EMAIL_NAME,PO_FROM_EMAIL_ADDRESS, $html_msg, NULL ,$filestoattach);
}
if ($attachthepdf != 'yes' && $attachthetf != 'yes') {
  zen_mail($_POST['eaddress'],$_POST['eaddress'],$_POST['etitle'],$_POST['ebody'],PO_FROM_EMAIL_NAME,PO_FROM_EMAIL_ADDRESS, $html_msg, NULL);
}
$messageStack->add('Purchase Order '. $_POST['tm1v'] . '-' . $_POST['kodv'] .' Emailed To: ' . $_POST['eaddress'], 'success'); 

$query978=mysql_query("SELECT orders_status FROM ".TABLE_ORDERS." WHERE orders_id='" . $_POST['passitwv'] . "'")
			or die("Failed to connect database: 1");
$row978=mysql_fetch_array($query978, MYSQL_NUM);
if ($row978[0] == $status_neworder && $status_posentorder != '' && $status_posentorder != NULL) {			
$query555=mysql_query("INSERT INTO ".TABLE_ORDERS_STATUS_HISTORY."
				(orders_status_id, orders_id, date_added,
 					customer_notified, comments)
  				 values ('$status_posentorder','" . $_POST['tm1v'] . "',now(),'0','".PO_SENT_COMMENTS."')")
				 or die(mysql_error());
mysql_query("update " . TABLE_ORDERS . "
                        set orders_status = '$status_posentorder', last_modified
 =
 now()
                        where orders_id ='" . $_POST['tm1v'] . "'");
} else {
$oatmeal = $db->Execute("select comments from " . TABLE_ORDERS_STATUS_HISTORY . " WHERE orders_id = '" . zen_db_input($_POST['tm1v']) . "' order by date_added DESC");
$catmeow = '';	
                                        $catmeow .= "{enter}".$oatmeal->fields['comments'];
                                       
                                   //$catmeow = zen_db_output($oatmeal->fields['comments']);
   if(PO_SENT_COMMENTS != '' && PO_SENT_COMMENTS != NULL && $catmeow != PO_SENT_COMMENTS) {
       $query555=mysql_query("INSERT INTO ".TABLE_ORDERS_STATUS_HISTORY."
				(orders_status_id, orders_id, date_added,
 					customer_notified, comments)
  				 values ('$row978[0]','" . $_POST['tm1v'] . "',now(),'0','".PO_SENT_COMMENTS."')")
				 or die(mysql_error());
   }
}
$date=date('Y-m-d');
for($m=0; $m<$_POST['mvalue']; $m++)
			{
                                $tmpost = 'tmv' . $m;
                                $tm=$_POST[$tmpost];
                                $tm2post = 'tm2v' . $m;
                                $tm2=$_POST[$tm2post];
                                
				$result=mysql_query("UPDATE ".TABLE_ORDERS_PRODUCTS." SET po_sent='1', item_shipped=0, po_number='" . $_POST['kodv'] . "', po_sent_to_subcontractor='$tm2', po_date='$date' WHERE  orders_products_id='$tm' LIMIT 1")	or die("Failed to connect database: 5");
				
			}
}
$running_total = 0;
$po_shipping = 0;
// send e-mail or review email before sending
if((isset($_POST['what']) and $_POST['what']=='send' and $_GET['resend'] != 'yes' and $_POST['checklastponum']==$lastponum) || ($_GET['resend'] == 'yes' and $_POST['checklastponum']==$lastponum)) {
if ($_POST['reviewthensend'] != 'yes')
    $messageStack = new messageStack();
	$k=$_POST['krotnosc'];
	$n=0;

	for($p=1; $p<$k; $p++) {
		$pos="pos".$p;
		$sub="sub".$p;
		$id="id".$p;
		$opi="opi".$p;
                $poprice="poprice".$p;
                $popricek[$n]=$_POST[$poprice];
                $poship="poship".$p;
                $poshipk[$n]=$_POST[$poship];
		$posk[$n]=$_POST[$pos];
		if($posk[$n]=='on') {
			$subk[$n]=$_POST[$sub];
			$idk[$n]=$_POST[$id];
			$opik[$n]=$_POST[$opi];
                        $popricek[$n]=$_POST[$poprice];
                        if($popricek[$n] != ''){
                        $db->Execute("UPDATE ".TABLE_ORDERS_PRODUCTS." SET po_price='".$popricek[$n]."' WHERE orders_products_id=".$opik[$n]);
                        }
                        $poshipk[$n]=$_POST[$poship];
                        if($poshipk[$n] != ''){
                        $db->Execute("UPDATE ".TABLE_ORDERS_PRODUCTS." SET po_shipping='".$poshipk[$n]."' WHERE orders_products_id=".$opik[$n]);
                        }
                        $n++;
		}
                
                
	}

	if(!$wp1)
	{
		echo "Nie mo&#191;na otworzyc pliku";
		exit;
	} else {
		$i=0;

		while(!feof($wp1))
		{
			$zamowienie[$i]=fgets($wp1,999);
			$i++;
		}

		fclose($wp1);
		for ($i=0; $i<count($zamowienie); $i++) {
			$zawartosc=$zawartosc.$zamowienie[$i];
		}
	}

	if(!$wp2)
	{
		echo "Nie mo&#191;na otworzyc pliku";
		exit;
	} else {
		$i=0;
		while(!feof($wp2))
		{
			$tresc_robij[$i]=fgets($wp2,999);
			$i++;
		}
	}

	fclose($wp2);
	$t=0;

	if(!$wp3)
	{
		echo "Nie mo&#191;na otworzyc pliku";
		exit;
	} else {
		while(!feof($wp3))
		{
			$tracking_link[$t]=fgets($wp3,999);
			$t++;
		}
	}

	fclose($wp3);

	for($i=0; $i<count($tresc_robij); $i++)
	{
		$tresc_robij1=$tresc_robij1.$tresc_robij[$i];
	}

        if(!$tf1)
	{
		echo "Nie mo&#191;na otworzyc pliku";
		exit;
	} else {
		$i=0;

		while(!feof($tf1))
		{
			$zamowienie_tf[$i]=fgets($tf1,999);
			$i++;
		}

		fclose($tf1);
		for ($i=0; $i<count($zamowienie_tf); $i++) {
			$zawartosc_tf=$zawartosc_tf.$zamowienie_tf[$i];
		}
	}

	if(!$tf2)
	{
		echo "Nie mo&#191;na otworzyc pliku";
		exit;
	} else {
		$i=0;
		while(!feof($tf2))
		{
			$tresc_robij_tf[$i]=fgets($tf2,999);
			$i++;
		}
	}

	fclose($tf2);
	$t=0;

	if(!$tf3)
	{
		echo "Nie mo&#191;na otworzyc pliku";
		exit;
	} else {
		while(!feof($tf3))
		{
			$tracking_link_tf[$t]=fgets($tf3,999);
			$t++;
		}
	}

	fclose($tf3);

	for($i=0; $i<count($tresc_robij_tf); $i++)
	{
		$tresc_robij1_tf=$tresc_robij1_tf.$tresc_robij_tf[$i];
	}

	//zbieranie danych na temat produktow i zamawiajacego
	for($i=0; $i<count($idk); $i++)
	{
		$query=mysql_query("SELECT p.orders_products_id, p.products_model, p.products_name, p.final_price, p.products_quantity,
		o.customers_name, o.customers_street_address, o.customers_city, o.customers_postcode, o.customers_state, o.customers_country, o.customers_telephone, o.customers_email_address,
		o.delivery_name, o.delivery_company, o.delivery_street_address, o.delivery_city, o.delivery_state, o.delivery_postcode, o.delivery_country,
		o.billing_name, o.billing_company, o.billing_street_address, o.billing_city, o.billing_state, o.billing_postcode, o.billing_country,
		o.payment_method,  o.date_purchased, o.currency, o.customers_id, o.orders_id, o.shipping_method, o.orders_status, o.customers_suburb, o.delivery_suburb, o.billing_suburb, o.customers_company
		FROM ".TABLE_ORDERS_PRODUCTS." as p, ".TABLE_ORDERS." as o
		WHERE
		p.orders_id=o.orders_id
		AND
		p.orders_products_id='$idk[$i]'")
		or die('Failed to connect database:  3');                                                                                                                               
		  // $shipway = '';
		while($row4=mysql_fetch_array($query, MYSQL_NUM))
		{       $row4[26] = '';
			if ($row4[37] != '' && $row4[37] != NULL)
				$adres = $row4[37]."\n";
			else
				$adres = "";
			if ($row4[34] != '' && $row4[34] != NULL)
				$adres .= $row4[6]."\n".$row4[34]."\n".$row4[7].", ".$row4[9]." ".$row4[8]."\n".$row4[10];	
			else
				$adres .= $row4[6]."\n".$row4[7].", ".$row4[9]." ".$row4[8]."\n".$row4[10];
			if ($row4[14] != '' && $row4[14] != NULL)
				$adres_deliver = $row4[14]."\n";
			else
				$adres_deliver = "";
			if ($row4[35] != '' && $row4[35] != NULL)
				$adres_deliver .= $row4[15]."\n".$row4[35]."\n".$row4[16].", ".$row4[17]." ".$row4[18]."\n".$row4[19];
			else
				$adres_deliver .= $row4[15]."\n".$row4[16].", ".$row4[17]." ".$row4[18]."\n".$row4[19];
			if ($row4[21] != '' && $row4[21] != NULL)
				$adres_biling = $row4[21]."\n";
			else
				$adres_biling = "";
			if ($row4[36] != '' && $row4[36] != NULL)
				$adres_biling .= $row4[22]."\n".$row4[36]."\n".$row4[23].", ".$row4[24]." ".$row4[25]."\n".$row4[26];
			else
				$adres_biling .= $row4[22]."\n".$row4[23].", ".$row4[24]." ".$row4[25]."\n".$row4[26];
			$price=$row4[3].' '.$row4[29];
			// $shipway=$row4[32];
			$zawartosc2=array();
                        $tracking_link_good=array();
                        $zawartosc2_tf=array();
                        $tracking_link_good_tf=array();
                        for($t=0; $t<=count($tracking_link); $t++)
			{  
				$tracking_link_good[$i]=$tracking_link_good[$i].$tracking_link[$t];
			} 
                        for($t=0; $t<=count($tracking_link_tf); $t++)
			{  
				$tracking_link_good_tf[$i]=$tracking_link_good_tf[$i].$tracking_link_tf[$t];
			} 
			//podmiana tagow dla pliku header
                        $zawartosc2[$i]=$zawartosc;
                        $zawartosc2_tf[$i]=$zawartosc_tf;
			
			$oatmeal = $db->Execute("select comments from " . TABLE_ORDERS_STATUS_HISTORY . " WHERE orders_id = '" . zen_db_input($row4[31]) . "' order by date_added");
			$catmeow = '';
                       
                            $catmeow .= "{enter}".$oatmeal->fields['comments'];
                           
                        //$catmeow = nl2br(zen_db_output($oatmeal->fields['comments']));
			$catmeow=strip_tags($catmeow);
                        $catmeow=html_entity_decode($catmeow,ENT_QUOTES);
			//echo $catmeow;
			if($row4[14]!='')
			{
                                $dcompany = $row4[14];
			} else	{
                                $dcompany = '';
			}

			if($row4[21]!='')
			{
                                $bcompany = $row4[21];
			} else {
                                $bcompany = '';
			}


			//ustawianie odpowiednich zmiennych do posortowania w celu uzyskania odpowiedneij ilosci numerow po
			//oraz wygenerowanai odpowiednije ilosci e-maili
                        //Set the appropriate variables to sort through in order to obtain adequate amounts of numbers to the
                        //and to generate adequate amounts of e-mails
                        
                        
			$wielowymiar[$i][0]=$subk[$i]; //id_subcontractors
			$wielowymiar[$i][1]=$row4[30]; //id_customers
			$wielowymiar[$i][2]=$idk[$i]; //id_produktu zamowionego
			$wielowymiar[$i][3]=$zawartosc2[$i]; //zawartosc
			$wielowymiar[$i][4]=$row4[31]; //id_orders
			$wielowymiar[$i][5]=$adres_deliver;
			$wielowymiar[$i][6]=$adres_biling;
			$wielowymiar[$i][7]=$row4[32]; //shipping
                        $wielowymiar[$i][8]=$tracking_link_good[$i];  //footer
                        $wielowymiar[$i][9]=$zawartosc2_tf[$i];  //text attachment header
                        $wielowymiar[$i][10]=$tracking_link_good_tf[$i];  //text attachment footer
                        $wielowymiar[$i][11]=$row4[5]; // customers name
		        $wielowymiar[$i][12]=$adres; // customers address
		        $wielowymiar[$i][13]=$row4[11]; // customers phone
		        $wielowymiar[$i][14]=$row4[12]; // customers email
		        $wielowymiar[$i][15]=$row4[13]; // delivery name
                        $wielowymiar[$i][16]=$catmeow; // customers comments
                        $wielowymiar[$i][17]=$dcompany; // delivery company
                        $wielowymiar[$i][18]=$bcompany; // billing company
                        $wielowymiar[$i][19]=$row4[20]; // billing name
		        $wielowymiar[$i][20]=$row4[27]; // payment method
		        $wielowymiar[$i][21]=$row4[28]; // date purchased
                        $wielowymiar[$i][22]=$row4[6]; // customer street address
                        $wielowymiar[$i][23]=$row4[7]; // customer city
                        $wielowymiar[$i][24]=$row4[8]; // customer postal code
                        $wielowymiar[$i][25]=$row4[9]; // customer state
                        $wielowymiar[$i][26]=$row4[10]; // customer country
                        $wielowymiar[$i][26]='';
                        $wielowymiar[$i][27]=$row4[15]; // delivery street address
                        $wielowymiar[$i][28]=$row4[16]; // delivery city
                        $wielowymiar[$i][29]=$row4[17]; // delivery state
                        $wielowymiar[$i][30]=$row4[18]; // delivery postal code
                        $wielowymiar[$i][31]=$row4[19]; // delivery country
                        $wielowymiar[$i][31]='';
                        $wielowymiar[$i][32]=$row4[22]; // billing street address
                        $wielowymiar[$i][33]=$row4[23]; // billing city
                        $wielowymiar[$i][34]=$row4[24]; // billing state
                        $wielowymiar[$i][35]=$row4[25]; // billing postal code
                        $wielowymiar[$i][36]=$row4[26]; // billing country
                        $wielowymiar[$i][36]='';
                        $wielowymiar[$i][37]=$row4[34]; // customer suburb
                        $wielowymiar[$i][38]=$row4[35]; // delivery suburb
                        $wielowymiar[$i][39]=$row4[36]; // billing suburb
                        $wielowymiar[$i][40]=$row4[37]; // customers company
		}
	}
        $sub_contactors_info = $db->Execute("SELECT * FROM ".TABLE_SUBCONTRACTORS." WHERE subcontractors_id".$subk[$i]);
        $sub_full_address = $sub_contactors_info->fields['full_name']."\n".
                            $sub_contactors_info->fields['street1']."\n".
                            $sub_contactors_info->fields['city'].", ".$sub_contactors_info->fields['State']." ".$sub_contactors_info->fields['zip']."\n".
                            $sub_contactors_info->fields['telephone'];
	$p=0;
	$byly=array();

	for ($i=0; $i<count($wielowymiar); $i++)
	{
		if ($byly[$i]==false)
		{
			$tmpt=array();
			$rowcounttmpt=0;
                        for ($ma=0; $ma<41; $ma++) {
			   $tmpt[$rowcounttmpt][$ma] = $wielowymiar[$i][$ma]; 
                        }
			$rowcounttmpt++;
			$byly[$i]=true;

			for ($j=$i+1; $j<count($wielowymiar); $j++)
			{
				if (($wielowymiar[$j][0]==$wielowymiar[$i][0]) && ($wielowymiar[$j][4]==$wielowymiar[$i][4]))
				{
                                        for ($ma=0; $ma<41; $ma++) {
                                           if ($ma < 4)
                                               $tmpt[$rowcounttmpt][$ma] = $wielowymiar[$j][$ma];
                                           else
                                               $tmpt[$rowcounttmpt][$ma] = $wielowymiar[$i][$ma];
                                        }
					$rowcounttmpt++;
					$byly[$j]=true;
				}
			}

			$tresc_ostateczna='';
			$trescik='';
			$newzawartosc='';
                        
                        $tresc_ostateczna_tf='';
			$trescik_tf='';
			$newzawartosc_tf='';

                        $tmpt[0][7]=strip_tags($tmpt[0][7]); 
                        $tmpt[0][7]=html_entity_decode($tmpt[0][7],ENT_QUOTES);

			//wybieranie dpowiedniego produktu i dodanie go do szablonu e-mail
			//odpowiednie tagi zpliku email_products sa zastepowane zmiennymi                               
			$pdf = new INVOICE( 'P', 'mm', 'Letter' );
			$pdf->Open();                                                             
			$pdf->AddPage();
                        if (PO_PDFP_PICTURE_ONE_FILE != '') {
                             $locationarr = explode(',', PO_PDFP_PICTURE_ONE_LOCATION);
                             $pdf->Image(PO_PDFP_PICTURE_ONE_FILE,$locationarr[0],$locationarr[1],PO_PDFP_PICTURE_ONE_WIDTH);
                        }
                        if (PO_PDFP_PICTURE_TWO_FILE != '') {
                             $locationarr = explode(',', PO_PDFP_PICTURE_TWO_LOCATION);
                             $pdf->Image(PO_PDFP_PICTURE_TWO_FILE,$locationarr[0],$locationarr[1],PO_PDFP_PICTURE_TWO_WIDTH);
                        }
                        $pdf->addSociete( PO_PDFP_S_NAME,
                  		
              			          PO_PDFP_S_ADDRESS );
			$pdf->fact_dev( PO_PDFP_TITLE, "" );
			$invdate=date(PO_PDFP_DATE);
			$pdf->addDate($invdate);
			$po_num = $tmpt[0][4].'-'.($lastponum + 1);
			$pdf->addClient($po_num);
                        
                        

      $first_pl_ad = str_replace("{cust_ad}",$tmpt[0][11]."\n".$tmpt[0][12],PO_PDFP_FA_ADDRESS);
      $first_pl_ad = str_replace("{bill_ad}",$tmpt[0][19]."\n".$tmpt[0][6],$first_pl_ad);
      $first_pl_ad = str_replace("{ship_ad}",$tmpt[0][15]."\n".$tmpt[0][5],$first_pl_ad);
      $first_pl_ad = str_replace("{cust_phone}",$tmpt[0][13],$first_pl_ad);
      $first_pl_ad = str_replace("{po_number}",$po_num,$first_pl_ad);
      $first_pl_ad = str_replace("{vendor_ad}",$sub_full_address,$first_pl_ad);
      $pdf->addClientBillAdresse($first_pl_ad,$po_num);
      $second_pl_ad = str_replace("{cust_ad}",$tmpt[0][11]."\n".$tmpt[0][12],PO_PDFP_SA_ADDRESS);
      $second_pl_ad = str_replace("{bill_ad}",$tmpt[0][19]."\n".$tmpt[0][6],$second_pl_ad);
      $second_pl_ad = str_replace("{ship_ad}",$tmpt[0][15]."\n".$tmpt[0][5],$second_pl_ad);
      $second_pl_ad = str_replace("{po_number}",$po_num,$second_pl_ad);
      $second_pl_ad = str_replace("{cust_phone}",$tmpt[0][13],$second_pl_ad);
      $pdf->addClientShipAdresse($second_pl_ad,$po_num);
      $querycp=mysql_query("SELECT orders_products_id FROM ".TABLE_ORDERS_PRODUCTS."  WHERE  orders_id='".$tmpt[0][4]."'  ")
			or die('Failed to connect database: 8');

			$countproducts=0;
			while($rowcp=mysql_fetch_array($querycp, MYSQL_NUM))
			{
                           $countproducts++;
                        }
                        $countproductsonpo = count($tmpt);
                        /* DEBUG echo "total - ".$countproducts."  and  onpo - ".$countproductsonpo; */
                        if ($_POST['addcommentstoplist'] == 1) {  			     
			     if ($_POST['plistcommentascustomer'] != 'yes') {
                                     $oatmeal = $db->Execute("select comments from " . TABLE_ORDERS_STATUS_HISTORY . " WHERE orders_id = '" . zen_db_input($tmpt[0][4]) . "' order by date_added");
			             $catmeow = '';
                                    
                                        $catmeow .= "{enter}".$oatmeal->fields['comments'];
                                       
                                   //$catmeow = nl2br(zen_db_output($oatmeal->fields['comments']));
                                     $catmeow=strip_tags($catmeow); 
                                     $catmeow=html_entity_decode($catmeow,ENT_QUOTES);
                                     if ($countproductsonpo != $countproducts)
                                          $custcommentline .= str_replace("{customers_comments}",$catmeow,PO_PDFP_CUST_COMMENT_PARTIAL);
                                     else
                                          $custcommentline .= str_replace("{customers_comments}",$catmeow,PO_PDFP_CUST_COMMENT_FULL);
                                     $custcommentline = str_replace("{store_comments}",stripslashes($_POST['plistcomments']),$custcommentline);
                             } else {
                                     if ($countproductsonpo != $countproducts)
                                          $custcommentline .= str_replace("{customers_comments}",stripslashes($_POST['plistcomments']),PO_PDFP_CUST_COMMENT_PARTIAL);
                                     else
                                          $custcommentline .= str_replace("{customers_comments}",stripslashes($_POST['plistcomments']),PO_PDFP_CUST_COMMENT_FULL);
                                     $custcommentline = str_replace("{store_comments}","",$custcommentline);
                             }
                             $custcommentline = str_replace("{shipping_method}",$tmpt[0][7],$custcommentline);
                             $custcommentline = str_replace("{cust_phone}",$tmpt[0][13],$custcommentline);
                             $custcommentline .= $tmpt[0][16];
		             //$pdf->addReference($custcommentline);
                        }   
			$cols=array( PO_PDFP_C_ONE_TITLE => PO_PDFP_C_ONE_WIDTH,
           			     PO_PDFP_C_TWO_TITLE => PO_PDFP_C_TWO_WIDTH,
        			     PO_PDFP_C_THREE_TITLE => PO_PDFP_C_THREE_WIDTH,
                                     PO_PDFP_C_FOUR_TITLE => PO_PDFP_C_FOUR_WIDTH,
        			     PO_PDFP_C_FIVE_TITLE => PO_PDFP_C_FIVE_WIDTH);
			$pdf->addCols( $cols);
			$cols=array( PO_PDFP_C_ONE_TITLE => PO_PDFP_C_ONE_JUST,
       			      	     PO_PDFP_C_TWO_TITLE => PO_PDFP_C_TWO_JUST,
       			             PO_PDFP_C_THREE_TITLE => PO_PDFP_C_THREE_JUST,
                                     PO_PDFP_C_FOUR_TITLE => PO_PDFP_C_FOUR_JUST,
        			     PO_PDFP_C_FIVE_TITLE => PO_PDFP_C_FIVE_JUST);
			$pdf->addLineFormat($cols);
			$pdf->addLineFormat($cols);
			$y    = 89;
			/* $countproductsonpo=0; */

                        // Determine Length of Product List
                        if ($countproductsonpo != $countproducts) {
                                          if ($_POST['adduppercommentstoplist'] == 1 && PO_PARTIALSHIP_PACKINGLIST != '')
                                               $ynot = 185;
                                          else
                                               $ynot = 225;
                                     } else {
                                         if ($_POST['adduppercommentstoplist'] == 1 && PO_FULLSHIP_PACKINGLIST != '')
                                               $ynot = 185;
                                          else
                                               $ynot = 225;
                        }

			for($h=0; $h<count($tmpt); $h++)
			{
                                if ($y > $ynot) {  // Start New Page
                                    $pdf->AddPage();
                        if (PO_PDFP_PICTURE_ONE_FILE != '') {
                             $locationarr = explode(',', PO_PDFP_PICTURE_ONE_LOCATION);
                             $pdf->Image(PO_PDFP_PICTURE_ONE_FILE,$locationarr[0],$locationarr[1],PO_PDFP_PICTURE_ONE_WIDTH);
                        }
                        if (PO_PDFP_PICTURE_TWO_FILE != '') {
                             $locationarr = explode(',', PO_PDFP_PICTURE_TWO_LOCATION);
                             $pdf->Image(PO_PDFP_PICTURE_TWO_FILE,$locationarr[0],$locationarr[1],PO_PDFP_PICTURE_TWO_WIDTH);
                        }
                        $pdf->addSociete( PO_PDFP_S_NAME,
                  		
              			          PO_PDFP_S_ADDRESS );
			$pdf->fact_dev( PO_PDFP_TITLE, "" );
			$invdate=date(PO_PDFP_DATE);
			$pdf->addDate($invdate);
			$pdf->addClient($tmpt[0][4]);
                        $po_num = $tmpt[0][4].'-'.$tmpt[0][2];

                        $first_pl_ad = str_replace("{cust_ad}",$tmpt[0][11]."\n".$tmpt[0][12],PO_PDFP_FA_ADDRESS);
                        $first_pl_ad = str_replace("{bill_ad}",$tmpt[0][19]."\n".$tmpt[0][6],$first_pl_ad);
                        $first_pl_ad = str_replace("{ship_ad}",$tmpt[0][15]."\n".$tmpt[0][5],$first_pl_ad);
                        $first_pl_ad = str_replace("{cust_phone}",$tmpt[0][13],$first_pl_ad);
                        $first_pl_ad = str_replace("{po_number}",$po_num,$first_pl_ad);
                        $first_pl_ad = str_replace("{vendor_ad}",$sub_full_address,$first_pl_ad);
                        $pdf->addClientBillAdresse($first_pl_ad,$po_num);
                        $second_pl_ad = str_replace("{cust_ad}",$tmpt[0][11]."\n".$tmpt[0][12],PO_PDFP_SA_ADDRESS);
                        $second_pl_ad = str_replace("{bill_ad}",$tmpt[0][19]."\n".$tmpt[0][6],$second_pl_ad);
                        $second_pl_ad = str_replace("{ship_ad}",$tmpt[0][15]."\n".$tmpt[0][5],$second_pl_ad);
                        $second_pl_ad = str_replace("{cust_phone}",$tmpt[0][13],$second_pl_ad);
                        $pdf->addClientShipAdresse($second_pl_ad,$po_num);
                        
                        if ($_POST['addcommentstoplist'] == 1) {  			     
			     if ($_POST['plistcommentascustomer'] != 'yes') {
                                     $oatmeal = $db->Execute("select comments from " . TABLE_ORDERS_STATUS_HISTORY . " WHERE orders_id = '" . zen_db_input($tmpt[0][4]) . "' order by date_added");
			              $catmeow = '';
                                    
                                        $catmeow .= "{enter}".$oatmeal->fields['comments'];
                                       
                                   //$catmeow = nl2br(zen_db_output($oatmeal->fields['comments']));
                                     $catmeow=strip_tags($catmeow); 
                                     $catmeow=html_entity_decode($catmeow,ENT_QUOTES);
                                     if ($countproductsonpo != $countproducts)
                                          $custcommentline .= str_replace("{customers_comments}",$catmeow,PO_PDFP_CUST_COMMENT_PARTIAL);
                                     else
                                          $custcommentline .= str_replace("{customers_comments}",$catmeow,PO_PDFP_CUST_COMMENT_FULL);
                                     $custcommentline = str_replace("{store_comments}",stripslashes($_POST['plistcomments']),$custcommentline);
                             } else {
                                     if ($countproductsonpo != $countproducts)
                                          $custcommentline .= str_replace("{customers_comments}",stripslashes($_POST['plistcomments']),PO_PDFP_CUST_COMMENT_PARTIAL);
                                     else
                                          $custcommentline .= str_replace("{customers_comments}",stripslashes($_POST['plistcomments']),PO_PDFP_CUST_COMMENT_FULL);
                                     $custcommentline = str_replace("{store_comments}","",$custcommentline);
                             }
                             $custcommentline = str_replace("{shipping_method}",$tmpt[0][7],$custcommentline);
		             $custcommentline = str_replace("{cust_phone}",$tmpt[0][13],$custcommentline);
                             $custcommentline .= $tmpt[0][16];
		             //$pdf->addReference($custcommentline);
                        }
			$cols=array( PO_PDFP_C_ONE_TITLE => PO_PDFP_C_ONE_WIDTH,
           			     PO_PDFP_C_TWO_TITLE => PO_PDFP_C_TWO_WIDTH,
        			     PO_PDFP_C_THREE_TITLE => PO_PDFP_C_THREE_WIDTH,
                                     PO_PDFP_C_FOUR_TITLE => PO_PDFP_C_FOUR_WIDTH,
        			     PO_PDFP_C_FIVE_TITLE => PO_PDFP_C_FIVE_WIDTH);
			$pdf->addCols( $cols);
			$cols=array( PO_PDFP_C_ONE_TITLE => PO_PDFP_C_ONE_JUST,
       			      	     PO_PDFP_C_TWO_TITLE => PO_PDFP_C_TWO_JUST,
       			             PO_PDFP_C_THREE_TITLE => PO_PDFP_C_THREE_JUST,
                                     PO_PDFP_C_FOUR_TITLE => PO_PDFP_C_FOUR_JUST,
        			     PO_PDFP_C_FIVE_TITLE => PO_PDFP_C_FIVE_JUST);
			$pdf->addLineFormat($cols);
			$pdf->addLineFormat($cols);
			$y    = 89;
                                }

				$tm=$tmpt[$h][2];
				$tm1=$tmpt[$h][4];
                                $resultcurrency=mysql_query("SELECT currency, currency_value
									  FROM ".TABLE_ORDERS." WHERE orders_id='$tm1'")
				or die("Failed to connect database: ");
                                $rowcurrency=mysql_fetch_array($resultcurrency, MYSQL_NUM);
				$result9=mysql_query("SELECT products_model, products_name, final_price, products_quantity, products_id, products_price, po_price, po_shipping
									  FROM ".TABLE_ORDERS_PRODUCTS." WHERE orders_products_id='$tm'")
				or die("Failed to connect database: ");
				$row9=mysql_fetch_array($result9, MYSQL_NUM);
				$trescik=$tresc_robij1;
                                $trescik_tf=$tresc_robij1_tf;
				$manufacturernamed=zen_get_products_manufacturers_name($row9[4]);

				$trescik=str_replace("{manufacturers_name}",$manufacturernamed,$trescik);
				$trescik=str_replace("{products_name}",$row9[1],$trescik);
				$trescik=str_replace("{products_model}",$row9[0],$trescik);
                                $trescik=str_replace("{products_price}",$row9[5],$trescik);
                                $trescik=str_replace("{po_price}",$row9[6],$trescik);
                                $line_total = $row9[3]*$row9[6];
                                $trescik=str_replace("{line_total}",$line_total,$trescik);
				$trescik=str_replace("{final_price}",$currencies->format($row9[2], true, $rowcurrency[0], $rowcurrency[1]),$trescik);
				$trescik=str_replace("{products_quantity}",$row9[3],$trescik);

                                $trescik_tf=str_replace("{manufacturers_name}",$manufacturernamed,$trescik_tf);
				$trescik_tf=str_replace("{products_name}",$row9[1],$trescik_tf);
				$trescik_tf=str_replace("{products_model}",$row9[0],$trescik_tf);
                                $trescik_tf=str_replace("{products_price}",$row9[5],$trescik_tf);
                                $trescik_tf=str_replace("{po_price}",$row9[6],$trescik);
                                $line_total = $row9[3]*$row9[6];
                                $trescik_tf=str_replace("{line_total}",$line_total,$trescik_tf);
				$trescik_tf=str_replace("{final_price}",$currencies->format($row9[2], true, $rowcurrency[0], $rowcurrency[1]),$trescik_tf);
				$trescik_tf=str_replace("{products_quantity}",$row9[3],$trescik_tf);

				$result9a=mysql_query("SELECT orders_id, orders_products_id, products_options, products_options_values
									  FROM ".TABLE_ORDERS_PRODUCTS_ATTRIBUTES."
									  WHERE orders_products_id='$tm' AND orders_id='$tm1'")
				or die("Failed to connect database: ");
				$attributes='';
				while($row9a=mysql_fetch_array($result9a, MYSQL_NUM))
				{
                                        if ($attributes=='')
					   $attributes=$row9a[2].": ".$row9a[3];
                                        else
                                           $attributes=$attributes.", ".$row9a[2].": ".$row9a[3];
				}
                                if(metro_attibutes($tm, $tm1) != false){
                                    $attributes=metro_attibutes($tm, $tm1);
                                }
				$trescik=str_replace("{products_attributes}",$attributes,$trescik);
                                $trescik_tf=str_replace("{products_attributes}",$attributes,$trescik_tf);

				$tresc_ostateczna=$tresc_ostateczna.$trescik;
				$newzawartosc=$tmpt[0][3].$tresc_ostateczna;
                                $tresc_ostateczna_tf=$tresc_ostateczna_tf.$trescik_tf;
				$newzawartosc_tf=$tmpt[0][9].$tresc_ostateczna_tf;
                                if (PO_PDFP_SHOW_ALL != 1) {
                                $s=1;
                                $column_arr[1] = PO_PDFP_C_ONE;
                                $column_arr[2] = PO_PDFP_C_TWO;
                                $column_arr[3] = PO_PDFP_C_THREE;
                                $column_arr[4] = PO_PDFP_C_FOUR;
                                $column_arr[5] = PO_PDFP_C_FIVE;
                                while ($s < 6) {
                                     $column_arr[$s]=str_replace("{products_quantity}",$row9[3],$column_arr[$s]);
                                     $column_arr[$s]=str_replace("{products_name}",$row9[1],$column_arr[$s]);
                                     $column_arr[$s]=str_replace("{products_attributes}",$attributes,$column_arr[$s]);
                                     $column_arr[$s]=str_replace("{products_model}",$row9[0],$column_arr[$s]);
                                     $column_arr[$s]=str_replace("{products_price}",$row9[5],$column_arr[$s]);
                                     $column_arr[$s]=str_replace("{po_price}",$row9[6],$column_arr[$s]);
                                     $line_total = $row9[3]*$row9[6];
                                    $column_arr[$s]=str_replace("{line_total}",$line_total,$column_arr[$s]);
                                     $column_arr[$s]=str_replace("{manufacturers_name}",$manufacturernamed,$column_arr[$s]);
                                     $column_arr[$s]=str_replace("{final_price}",$currencies->format($row9[2], true, $rowcurrency[0], $rowcurrency[1]),$column_arr[$s]);
                                     
                                     $s++;
                                }    
				$line = array( PO_PDFP_C_ONE_TITLE => $column_arr[1],
              				      PO_PDFP_C_TWO_TITLE => $column_arr[2],
             				      PO_PDFP_C_THREE_TITLE => $column_arr[3],
                                              PO_PDFP_C_FOUR_TITLE => $column_arr[4],
                                              PO_PDFP_C_FIVE_TITLE => $column_arr[5]);
				$size = $pdf->addLine( $y, $line );
				$y   += $size + 2;
                               $running_total += $line_total;
                               $po_shipping += $row9[7];
                                }
				/* $countproductsonpo++; */
			}

                        // Add All Products Option to PDF Packing List
                        if (PO_PDFP_SHOW_ALL == 1) {
                        $queryallp=mysql_query("SELECT products_model, products_name, final_price, products_quantity, products_id, orders_products_id
									  FROM ".TABLE_ORDERS_PRODUCTS." WHERE orders_id='".$tmpt[0][4]."'  ")
			or die('Failed to connect database: 8');
                        $resultcurrency=mysql_query("SELECT currency, currency_value
									  FROM ".TABLE_ORDERS." WHERE orders_id='".$tmpt[0][4]."'  ")
				or die("Failed to connect database: ");
                        $rowcurrency=mysql_fetch_array($resultcurrency, MYSQL_NUM);
			while($rowallp=mysql_fetch_array($queryallp, MYSQL_NUM))
			{
                           if ($y > $ynot) {  // Start New Page
                                    $pdf->AddPage();
                        if (PO_PDFP_PICTURE_ONE_FILE != '') {
                             $locationarr = explode(',', PO_PDFP_PICTURE_ONE_LOCATION);
                             $pdf->Image(PO_PDFP_PICTURE_ONE_FILE,$locationarr[0],$locationarr[1],PO_PDFP_PICTURE_ONE_WIDTH);
                        }
                        if (PO_PDFP_PICTURE_TWO_FILE != '') {
                             $locationarr = explode(',', PO_PDFP_PICTURE_TWO_LOCATION);
                             $pdf->Image(PO_PDFP_PICTURE_TWO_FILE,$locationarr[0],$locationarr[1],PO_PDFP_PICTURE_TWO_WIDTH);
                        }
                        $pdf->addSociete( PO_PDFP_S_NAME,
                  		
              			          PO_PDFP_S_ADDRESS );
			$pdf->fact_dev( PO_PDFP_TITLE, "" );
			$invdate=date(PO_PDFP_DATE);
			$pdf->addDate($invdate);
			$pdf->addClient($tmpt[0][4]);
                        $po_num = $tmpt[0][4].'-'.$tmpt[0][2];
                        
                        $first_pl_ad = str_replace("{cust_ad}",$tmpt[0][11]."\n".$tmpt[0][12],PO_PDFP_FA_ADDRESS);
                        $first_pl_ad = str_replace("{bill_ad}",$tmpt[0][19]."\n".$tmpt[0][6],$first_pl_ad);
                        $first_pl_ad = str_replace("{ship_ad}",$tmpt[0][15]."\n".$tmpt[0][5],$first_pl_ad);
                        $first_pl_ad = str_replace("{cust_phone}",$tmpt[0][13],$first_pl_ad);
                        $first_pl_ad = str_replace("{po_number}",$po_num,$first_pl_ad);
                        $first_pl_ad = str_replace("{vendor_ad}",$sub_full_address,$first_pl_ad);
                        $pdf->addClientBillAdresse($first_pl_ad,$po_num);
                        $second_pl_ad = str_replace("{cust_ad}",$tmpt[0][11]."\n".$tmpt[0][12],PO_PDFP_SA_ADDRESS);
                        $second_pl_ad = str_replace("{bill_ad}",$tmpt[0][19]."\n".$tmpt[0][6],$second_pl_ad);
                        $second_pl_ad = str_replace("{ship_ad}",$tmpt[0][15]."\n".$tmpt[0][5],$second_pl_ad);
                        $second_pl_ad = str_replace("{po_number}",$po_num,$second_pl_ad);
                        $second_pl_ad = str_replace("{cust_phone}",$tmpt[0][13],$second_pl_ad);
                        $pdf->addClientShipAdresse($second_pl_ad,$po_num);
                        
                        if ($_POST['addcommentstoplist'] == 1) {  			     
			     if ($_POST['plistcommentascustomer'] != 'yes') {
                                     $oatmeal = $db->Execute("select comments from " . TABLE_ORDERS_STATUS_HISTORY . " WHERE orders_id = '" . zen_db_input($tmpt[0][4]) . "' order by date_added");
			           
                                        $catmeow .= "{enter}".$oatmeal->fields['comments'];
                                        
                                   //$catmeow = nl2br(zen_db_output($oatmeal->fields['comments']));
                                     $catmeow=strip_tags($catmeow); 
                                     $catmeow=html_entity_decode($catmeow,ENT_QUOTES);
                                     if ($countproductsonpo != $countproducts)
                                          $custcommentline .= str_replace("{customers_comments}",$catmeow,PO_PDFP_CUST_COMMENT_PARTIAL);
                                     else
                                          $custcommentline .= str_replace("{customers_comments}",$catmeow,PO_PDFP_CUST_COMMENT_FULL);
                                     $custcommentline = str_replace("{store_comments}",stripslashes($_POST['plistcomments']),$custcommentline);
                             } else {
                                     if ($countproductsonpo != $countproducts)
                                          $custcommentline .= str_replace("{customers_comments}",stripslashes($_POST['plistcomments']),PO_PDFP_CUST_COMMENT_PARTIAL);
                                     else
                                          $custcommentline .= str_replace("{customers_comments}",stripslashes($_POST['plistcomments']),PO_PDFP_CUST_COMMENT_FULL);
                                     $custcommentline = str_replace("{store_comments}","",$custcommentline);
                             }
                             $custcommentline = str_replace("{shipping_method}",$tmpt[0][7],$custcommentline);
		             $custcommentline = str_replace("{cust_phone}",$tmpt[0][13],$custcommentline);
                             $custcommentline .= $tmpt[0][16];
		             //$pdf->addReference($custcommentline);
                        }
			$cols=array( PO_PDFP_C_ONE_TITLE => PO_PDFP_C_ONE_WIDTH,
           			     PO_PDFP_C_TWO_TITLE => PO_PDFP_C_TWO_WIDTH,
        			     PO_PDFP_C_THREE_TITLE => PO_PDFP_C_THREE_WIDTH,
                                     PO_PDFP_C_FOUR_TITLE => PO_PDFP_C_FOUR_WIDTH,
        			     PO_PDFP_C_FIVE_TITLE => PO_PDFP_C_FIVE_WIDTH);
			$pdf->addCols( $cols);
			$cols=array( PO_PDFP_C_ONE_TITLE => PO_PDFP_C_ONE_JUST,
       			      	     PO_PDFP_C_TWO_TITLE => PO_PDFP_C_TWO_JUST,
       			             PO_PDFP_C_THREE_TITLE => PO_PDFP_C_THREE_JUST,
                                     PO_PDFP_C_FOUR_TITLE => PO_PDFP_C_FOUR_JUST,
        			     PO_PDFP_C_FIVE_TITLE => PO_PDFP_C_FIVE_JUST);
			$pdf->addLineFormat($cols);
			$pdf->addLineFormat($cols);
			$y    = 89;
                                }
                           $s=1;
                           $manufacturernamed=zen_get_products_manufacturers_name($rowallp[4]);
                           $result9a=mysql_query("SELECT orders_id, orders_products_id, products_options, products_options_values
									  FROM ".TABLE_ORDERS_PRODUCTS_ATTRIBUTES."
									  WHERE orders_products_id='".$rowallp[5]."' AND orders_id='".$tmpt[0][4]."'  ")
				or die("Failed to connect database: ");
				$attributes='';
				while($row9a=mysql_fetch_array($result9a, MYSQL_NUM))
				{
                                        if ($attributes=='')
					   $attributes=$row9a[2].": ".$row9a[3];
                                        else
                                           $attributes=$attributes.", ".$row9a[2].": ".$row9a[3];
				}
                                if(metro_attibutes($rowallp[5], $tmpt[0][4]) != false){
                                    $attributes=metro_attibutes($rowallp[5], $tmpt[0][4]);
                                }
                           $column_arr[1] = PO_PDFP_C_ONE;
                           $column_arr[2] = PO_PDFP_C_TWO;
                           $column_arr[3] = PO_PDFP_C_THREE;
                           $column_arr[4] = PO_PDFP_C_FOUR;
                           $column_arr[5] = PO_PDFP_C_FIVE;
                           while ($s < 6) {
                                $column_arr[$s]=str_replace("{products_quantity}",$rowallp[3],$column_arr[$s]);
                                $column_arr[$s]=str_replace("{products_name}",$rowallp[1],$column_arr[$s]);
                                $column_arr[$s]=str_replace("{products_attributes}",$attributes,$column_arr[$s]);
                                $column_arr[$s]=str_replace("{products_model}",$rowallp[0],$column_arr[$s]);
                                $column_arr[$s]=str_replace("{products_price}",$row9[5],$column_arr[$s]);
                                $column_arr[$s]=str_replace("{po_price}",$row9[6],$column_arr[$s]);
                                $line_total = $row9[3]*$row9[6];
                                $column_arr[$s]=str_replace("{line_total}",$line_total,$column_arr[$s]);
                                $column_arr[$s]=str_replace("{manufacturers_name}",$manufacturernamed,$column_arr[$s]);
                                $column_arr[$s]=str_replace("{final_price}",$currencies->format($rowallp[2], true, $rowcurrency[0], $rowcurrency[1]),$column_arr[$s]);
                                $s++;
                           }    
			   $line = array( PO_PDFP_C_ONE_TITLE => $column_arr[1],
                                       PO_PDFP_C_TWO_TITLE => $column_arr[2],
             			       PO_PDFP_C_THREE_TITLE => $column_arr[3],
                                       PO_PDFP_C_FOUR_TITLE => $column_arr[4],
                                       PO_PDFP_C_FIVE_TITLE => $column_arr[5]);
		           $size = $pdf->addLine( $y, $line );
		           $y   += $size + 2;
                           $running_total += $line_total;
                           $po_shipping += $row9[7];
                        }
                        }

                        // Add Totals and Sub-Totals
                        if ((PO_PDFP_TOTALS == 2) || (PO_PDFP_TOTALS == 1 && $countproductsonpo == $countproducts)) {
                        $y = $y+5;
                        $querytots=mysql_query("SELECT title, text FROM ".TABLE_ORDERS_TOTAL."  WHERE  orders_id='".$tmpt[0][4]."' ORDER BY sort_order ")
			or die('Failed to connect database: 8');
			while($rowtots=mysql_fetch_array($querytots, MYSQL_NUM))
			{
                           if ($y > $ynot) {  // Start New Page
                                    $pdf->AddPage();
                        if (PO_PDFP_PICTURE_ONE_FILE != '') {
                             $locationarr = explode(',', PO_PDFP_PICTURE_ONE_LOCATION);
                             $pdf->Image(PO_PDFP_PICTURE_ONE_FILE,$locationarr[0],$locationarr[1],PO_PDFP_PICTURE_ONE_WIDTH);
                        }
                        if (PO_PDFP_PICTURE_TWO_FILE != '') {
                             $locationarr = explode(',', PO_PDFP_PICTURE_TWO_LOCATION);
                             $pdf->Image(PO_PDFP_PICTURE_TWO_FILE,$locationarr[0],$locationarr[1],PO_PDFP_PICTURE_TWO_WIDTH);
                        }
                        $pdf->addSociete( PO_PDFP_S_NAME,
                  		
              			          PO_PDFP_S_ADDRESS );
			$pdf->fact_dev( PO_PDFP_TITLE, "" );
			$invdate=date(PO_PDFP_DATE);
			$pdf->addDate($invdate);
			$pdf->addClient($tmpt[0][4]);
                        $po_num = $tmpt[0][4].'-'.$tmpt[0][2];

                        $first_pl_ad = str_replace("{cust_ad}",$tmpt[0][11]."\n".$tmpt[0][12],PO_PDFP_FA_ADDRESS);
                        $first_pl_ad = str_replace("{bill_ad}",$tmpt[0][19]."\n".$tmpt[0][6],$first_pl_ad);
                        $first_pl_ad = str_replace("{ship_ad}",$tmpt[0][15]."\n".$tmpt[0][5],$first_pl_ad);
                        $first_pl_ad = str_replace("{cust_phone}",$tmpt[0][13],$first_pl_ad);
                        $first_pl_ad = str_replace("{po_number}",$po_num,$first_pl_ad);
                        $first_pl_ad = str_replace("{vendor_ad}",$sub_full_address,$first_pl_ad);
                        $pdf->addClientBillAdresse($first_pl_ad,$po_num);
                        $second_pl_ad = str_replace("{cust_ad}",$tmpt[0][11]."\n".$tmpt[0][12],PO_PDFP_SA_ADDRESS);
                        $second_pl_ad = str_replace("{bill_ad}",$tmpt[0][19]."\n".$tmpt[0][6],$second_pl_ad);
                        $second_pl_ad = str_replace("{ship_ad}",$tmpt[0][15]."\n".$tmpt[0][5],$second_pl_ad);
                        $second_pl_ad = str_replace("{po_number}",$po_num,$second_pl_ad);
                        $second_pl_ad = str_replace("{cust_phone}",$tmpt[0][13],$second_pl_ad);
                        $pdf->addClientShipAdresse($second_pl_ad,$po_num);
                        
                        if ($_POST['addcommentstoplist'] == 1) {  			     
			     if ($_POST['plistcommentascustomer'] != 'yes') {
                                     $oatmeal = $db->Execute("select comments from " . TABLE_ORDERS_STATUS_HISTORY . " WHERE orders_id = '" . zen_db_input($tmpt[0][4]) . "' order by date_added");
			             $catmeow = '';
                                     
                                        $catmeow .= "{enter}".$oatmeal->fields['comments'];
                                        
                                   //$catmeow = nl2br(zen_db_output($oatmeal->fields['comments']));
                                     $catmeow=strip_tags($catmeow); 
                                     $catmeow=html_entity_decode($catmeow,ENT_QUOTES);
                                     if ($countproductsonpo != $countproducts)
                                          $custcommentline .= str_replace("{customers_comments}",$catmeow,PO_PDFP_CUST_COMMENT_PARTIAL);
                                     else
                                          $custcommentline .= str_replace("{customers_comments}",$catmeow,PO_PDFP_CUST_COMMENT_FULL);
                                     $custcommentline = str_replace("{store_comments}",stripslashes($_POST['plistcomments']),$custcommentline);
                             } else {
                                     if ($countproductsonpo != $countproducts)
                                          $custcommentline .= str_replace("{customers_comments}",stripslashes($_POST['plistcomments']),PO_PDFP_CUST_COMMENT_PARTIAL);
                                     else
                                          $custcommentline .= str_replace("{customers_comments}",stripslashes($_POST['plistcomments']),PO_PDFP_CUST_COMMENT_FULL);
                                     $custcommentline = str_replace("{store_comments}","",$custcommentline);
                             }
                             $custcommentline = str_replace("{shipping_method}",$tmpt[0][7],$custcommentline);
                             $custcommentline = str_replace("{cust_phone}",$tmpt[0][13],$custcommentline);
		             $custcommentline .= $tmpt[0][16];
                             //$pdf->addReference($custcommentline);
                        }
			$cols=array( PO_PDFP_C_ONE_TITLE => PO_PDFP_C_ONE_WIDTH,
           			     PO_PDFP_C_TWO_TITLE => PO_PDFP_C_TWO_WIDTH,
        			     PO_PDFP_C_THREE_TITLE => PO_PDFP_C_THREE_WIDTH,
                                     PO_PDFP_C_FOUR_TITLE => PO_PDFP_C_FOUR_WIDTH,
        			     PO_PDFP_C_FIVE_TITLE => PO_PDFP_C_FIVE_WIDTH);
			$pdf->addCols( $cols);
			$cols=array( PO_PDFP_C_ONE_TITLE => PO_PDFP_C_ONE_JUST,
       			      	     PO_PDFP_C_TWO_TITLE => PO_PDFP_C_TWO_JUST,
       			             PO_PDFP_C_THREE_TITLE => PO_PDFP_C_THREE_JUST,
                                     PO_PDFP_C_FOUR_TITLE => PO_PDFP_C_FOUR_JUST,
        			     PO_PDFP_C_FIVE_TITLE => PO_PDFP_C_FIVE_JUST);
			$pdf->addLineFormat($cols);
			$pdf->addLineFormat($cols);
			$y    = 89;
                                }
                           $s=1;
                           $column_arr[1] = PO_PDFP_C_ONE;
                           $column_arr[2] = PO_PDFP_C_TWO;
                           $column_arr[3] = PO_PDFP_C_THREE;
                           $column_arr[4] = PO_PDFP_C_FOUR;
                           $column_arr[5] = PO_PDFP_C_FIVE;
                           while ($s < 6) {
                                $column_arr[$s]=str_replace("{products_quantity}","",$column_arr[$s]);
                                $column_arr[$s]=str_replace("{products_name}",$rowtots[0],$column_arr[$s]);
                                $column_arr[$s]=str_replace("{products_attributes}","",$column_arr[$s]);
                                $column_arr[$s]=str_replace("{products_model}","",$column_arr[$s]);
                                $column_arr[$s]=str_replace("{products_price}",$row9[5],$column_arr[$s]);
                                $line_total = $row9[3]*$row9[6];
                                $column_arr[$s]=str_replace("{line_total}",$line_total,$column_arr[$s]);
				$column_arr[$s]=str_replace("{po_price}",$row9[6],$column_arr[$s]);
                                $column_arr[$s]=str_replace("{manufacturers_name}","",$column_arr[$s]);
                                $column_arr[$s]=str_replace("{final_price}",$rowtots[1],$column_arr[$s]);
                                $s++;
                           }    
			   $line = array( PO_PDFP_C_ONE_TITLE => $column_arr[1],
                                       PO_PDFP_C_TWO_TITLE => $column_arr[2],
             			       PO_PDFP_C_THREE_TITLE => $column_arr[3],
                                       PO_PDFP_C_FOUR_TITLE => $column_arr[4],
                                       PO_PDFP_C_FIVE_TITLE => $column_arr[5]);
		           $size = $pdf->addLine( $y, $line );
                           $y   += $size + 2;
                           $running_total += $line_total;
                           $po_shipping += $row9[7];
                        }
                        }
                        
			//wybieranie adresu pczty email poddostawcy
			$dlaemaila= ($tmpt[0][0]!='0') ? $tmpt[0][0] : 0;
			$query22=mysql_query("SELECT * FROM ".TABLE_SUBCONTRACTORS." WHERE subcontractors_id='$dlaemaila'")
			or die("Failed to connect database: 1");
			$subcontractor=mysql_fetch_assoc($query22);
			$adresdo=$subcontractor['email_address'];
                        $sendpdf_sc=strtoupper($subcontractor['sendpdf']);
                        $sendtf_sc=strtoupper($subcontractor['sendtf']);
			/* if ($dlaemaila==0) $adresdo=PO_OWN_STOCK_EMAIL; */

			//generowanie kodu, tracking link oraz, tematu
			$row110[0]='';
			//$vars = get_defined_vars();
			//print_r($vars);
			//$check_if_po_sent = mysql_query("SELECT * FROM orders_products WHERE orders_products_id = '$tm'");
			//$if_po_sent = mysql_fetch_assoc($check_if_po_sent);
			//$po_sent = $if_po_sent['po_sent'];

			//if ($po_sent == 0) {
			$query110=mysql_query("SELECT max(po_number) FROM ".TABLE_ORDERS_PRODUCTS."")
			or die("Failed to connect database: ");
			$row110=mysql_fetch_array($query110, MYSQL_NUM);
			$kod=$row110[0]+1;
			//} else {
			//	$query110=mysql_query("SELECT * FROM orders_products WHERE orders_products_id = '$tm'")
			//	or die("Failed to connect database: ");
			//	$row110=mysql_fetch_array($query110, MYSQL_NUM);
			//	$kod=$row110[0];
			//}
			if($row110[0]=='')
			{
				$kod=$kod."1";
			}	else {
				// TO TRY AND RID OF THE INCREMENTING PO_NUMBER -- $kod=$row110[0]+1;
				//$kod=$row110[0]+1;
				//$kod=$row110[0];
			}

			 $newzawartosc=str_replace("{po_number}",$wielowymiar[$i][4]."-".$kod,$newzawartosc);
                        if ($subcontractor['email_title'] == ''){
			     $tematk=PO_SUBJECT;}
                        else{
                             $tematk=$subcontractor['email_title'];}
			$tematk=str_replace("{po_number}",$wielowymiar[$i][4]."-".$kod,$tematk);
			$tematk=str_replace("{contact_person}",$subcontractor['contact_person'],$tematk);
			$tematk=str_replace("{full_name}",$subcontractor['full_name'],$tematk);
		        $tematk=str_replace("{short_name}",$subcontractor['short_name'],$tematk);
                        $tematk=str_replace("{order_number}",$wielowymiar[$i][4]."-".$kod,$tematk);
                        if ($tmpt[0][7] != PO_CHANGE_SHIPPING_FROM) {
                           $tematk=str_replace("{shipping_method}",$tmpt[0][7],$tematk);
                        } else {
                           $tematk=str_replace("{shipping_method}",PO_CHANGE_SHIPPING_TO,$tematk);
                        }

                        $passitw=$wielowymiar[$i][4];
                        $query978=mysql_query("SELECT orders_status FROM ".TABLE_ORDERS." WHERE orders_id='$passitw'")
			                           or die("Failed to connect database: 1");
                        $row978=mysql_fetch_array($query978, MYSQL_NUM);
                        if ($row978[0] > 99)
                        {           $tracking_link_1=TRACKING_FEATURE_NOT_AVAILABLE_GOOGLE_CHECKOUT;
                }
                        else
                        {	    $tracking_link_1='<a href="'.HTTP_SERVER.DIR_WS_CATALOG.'/confirm_track_sub.php?x='.$dlaemaila.'&y='.$kod.'&owner='.$securitycode.'">'.HTTP_SERVER.DIR_WS_CATALOG.'/confirm_track_sub.php?x='.$dlaemaila.'&y='.$kod.'&owner='.$securitycode.'</a>';
                }
 /* for($t=0; $t<=count($tracking_link); $t++)
			{  
				$tracking_link_good=$tracking_link_good.str_replace("{tracking_link}",$tracking_link_1,$tracking_link[$t]);
			} */			 
/* $tracking_link_good=str_replace("{tracking_link}",$tracking_link_1,$tracking_link_good); */

// find state abbreviations

// change tags
			$newzawartosc=$newzawartosc.$tmpt[0][8];
                        $newzawartosc_tf=$newzawartosc_tf.$tmpt[0][10];
                  $newzawartosc = str_replace("{po_number}",$wielowymiar[$i][4]."-".$kod,$newzawartosc);
                  $newzawartosc = str_replace("{tracking_link}",$tracking_link_1,$newzawartosc);
		  $newzawartosc = str_replace("{contact_person}",$subcontractor['contact_person'],$newzawartosc);
		  $newzawartosc = str_replace("{full_name}",$subcontractor['full_name'],$newzawartosc);
		  $newzawartosc = str_replace("{short_name}",$subcontractor['short_name'],$newzawartosc);
		  $newzawartosc = str_replace("{subcontractors_id}",$subcontractor['subcontractors_id'],$newzawartosc);
		  $newzawartosc = str_replace("{street}",$subcontractor['street1'],$newzawartosc);
		  $newzawartosc = str_replace("{city}",$subcontractor['city'],$newzawartosc);
		  $newzawartosc = str_replace("{state}",$subcontractor['state'],$newzawartosc);
		  $newzawartosc = str_replace("{zip}",$subcontractor['zip'],$newzawartosc);
		  $newzawartosc = str_replace("{telephone}",$subcontractor['telephone'],$newzawartosc);
		  $newzawartosc = str_replace("{email_address}",$subcontractor['email_address'],$newzawartosc);

                  $newzawartosc = str_replace("{customers_name}",$wielowymiar[$i][11],$newzawartosc);
                  $newzawartosc = str_replace("{po_number}",$wielowymiar[$i][4]."-".$kod,$newzawartosc);
		  $newzawartosc = str_replace("{customers_address}",$wielowymiar[$i][12],$newzawartosc);
		  $newzawartosc = str_replace("{customers_phone}",$wielowymiar[$i][13],$newzawartosc);
                  $newzawartosc = str_replace("{cust_phone}",$wielowymiar[$i][13],$newzawartosc);
		  $newzawartosc = str_replace("{customers_email}",$wielowymiar[$i][14],$newzawartosc);
		  $newzawartosc = str_replace("{delivery_name}",$wielowymiar[$i][15],$newzawartosc);
		  $newzawartosc = str_replace("{po_comments}",$_POST['posubcomments'],$newzawartosc);
                  $newzawartosc = str_replace("{customers_comments}",$wielowymiar[$i][16],$newzawartosc);
                  $newzawartosc = str_replace("{delivery_company}",$wielowymiar[$i][17],$newzawartosc);
                  $newzawartosc = str_replace("{delivery_address}",$wielowymiar[$i][5],$newzawartosc);
                  $newzawartosc = str_replace("{billing_company}",$wielowymiar[$i][18],$newzawartosc);
                  $newzawartosc = str_replace("{billing_name}",$wielowymiar[$i][19],$newzawartosc);
		  $newzawartosc = str_replace("{billing_address}",$wielowymiar[$i][6],$newzawartosc);
		  $newzawartosc = str_replace("{payment_method}",$wielowymiar[$i][20],$newzawartosc);
		  $newzawartosc = str_replace("{date_purchased}",$wielowymiar[$i][21],$newzawartosc);

                  $newzawartosc = str_replace("{customers_street_address}",$wielowymiar[$i][22],$newzawartosc);
                  $newzawartosc = str_replace("{customers_city}",$wielowymiar[$i][23],$newzawartosc);
                  $newzawartosc = str_replace("{customers_postal_code}",$wielowymiar[$i][24],$newzawartosc);
                  $newzawartosc = str_replace("{customers_state}",$wielowymiar[$i][25],$newzawartosc);
                  $newzawartosc = str_replace("{customers_state_code}",findStateAbbreviations($wielowymiar[$i][25]),$newzawartosc);
                  $newzawartosc = str_replace("{customers_country}",$wielowymiar[$i][26],$newzawartosc);
                  $newzawartosc = str_replace("{delivery_street_address}",$wielowymiar[$i][27],$newzawartosc);
                  $newzawartosc = str_replace("{delivery_city}",$wielowymiar[$i][28],$newzawartosc);
                  $newzawartosc = str_replace("{delivery_state}",$wielowymiar[$i][29],$newzawartosc);
                  $newzawartosc = str_replace("{delivery_state_code}",findStateAbbreviations($wielowymiar[$i][29]),$newzawartosc);
                  $newzawartosc = str_replace("{delivery_postal_code}",$wielowymiar[$i][30],$newzawartosc);
                  $newzawartosc = str_replace("{delivery_country}",$wielowymiar[$i][31],$newzawartosc);
                  $newzawartosc = str_replace("{billing_street_address}",$wielowymiar[$i][32],$newzawartosc);
                  $newzawartosc = str_replace("{billing_city}",$wielowymiar[$i][33],$newzawartosc);
                  $newzawartosc = str_replace("{billing_state}",$wielowymiar[$i][34],$newzawartosc);
                  $newzawartosc = str_replace("{billing_state_code}",findStateAbbreviations($wielowymiar[$i][34]),$newzawartosc);
                  $newzawartosc = str_replace("{billing_postal_code}",$wielowymiar[$i][35],$newzawartosc);
                  $newzawartosc = str_replace("{billing_country}",$wielowymiar[$i][36],$newzawartosc);
                  $newzawartosc = str_replace("{customer_suburb}",$wielowymiar[$i][37],$newzawartosc);
                  $newzawartosc = str_replace("{delivery_suburb}",$wielowymiar[$i][38],$newzawartosc);
                  $newzawartosc = str_replace("{billing_suburb}",$wielowymiar[$i][39],$newzawartosc);
                  $newzawartosc = str_replace("{customers_company}",$wielowymiar[$i][40],$newzawartosc);

                  $newzawartosc_tf = str_replace("{po_number}",$wielowymiar[$i][4]."-".$kod,$newzawartosc_tf);
                  $newzawartosc_tf = str_replace("{tracking_link}",$tracking_link_1,$newzawartosc_tf);
		  $newzawartosc_tf = str_replace("{contact_person}",$subcontractor['contact_person'],$newzawartosc_tf);
		  $newzawartosc_tf = str_replace("{full_name}",$subcontractor['full_name'],$newzawartosc_tf);
		  $newzawartosc_tf = str_replace("{short_name}",$subcontractor['short_name'],$newzawartosc_tf);
		  $newzawartosc_tf = str_replace("{subcontractors_id}",$subcontractor['subcontractors_id'],$newzawartosc_tf);
		  $newzawartosc_tf = str_replace("{street}",$subcontractor['street1'],$newzawartosc_tf);
		  $newzawartosc_tf = str_replace("{city}",$subcontractor['city'],$newzawartosc_tf);
		  $newzawartosc_tf = str_replace("{state}",$subcontractor['state'],$newzawartosc_tf);
		  $newzawartosc_tf = str_replace("{zip}",$subcontractor['zip'],$newzawartosc_tf);
		  $newzawartosc_tf = str_replace("{telephone}",$subcontractor['telephone'],$newzawartosc_tf);
		  $newzawartosc_tf = str_replace("{email_address}",$subcontractor['email_address'],$newzawartosc_tf);

                  $newzawartosc_tf = str_replace("{customers_name}",$wielowymiar[$i][11],$newzawartosc_tf);
                  $newzawartosc_tf = str_replace("{po_number}",$wielowymiar[$i][4]."-".$kod,$newzawartosc_tf);
		  $newzawartosc_tf = str_replace("{customers_address}",$wielowymiar[$i][12],$newzawartosc_tf);
		  $newzawartosc_tf = str_replace("{customers_phone}",$wielowymiar[$i][13],$newzawartosc_tf);
                  $newzawartosc_tf = str_replace("{cust_phone}",$wielowymiar[$i][13],$newzawartosc_tf);
		  $newzawartosc_tf = str_replace("{customers_email}",$wielowymiar[$i][14],$newzawartosc_tf);
		  $newzawartosc_tf = str_replace("{delivery_name}",$wielowymiar[$i][15],$newzawartosc_tf);
		  $newzawartosc_tf = str_replace("{po_comments}",$_POST['posubcomments'],$newzawartosc_tf);
                  $newzawartosc_tf = str_replace("{customers_comments}",$wielowymiar[$i][16],$newzawartosc_tf);
                  $newzawartosc_tf = str_replace("{delivery_company}",$wielowymiar[$i][17],$newzawartosc_tf);
                  $newzawartosc_tf = str_replace("{delivery_address}",$wielowymiar[$i][5],$newzawartosc_tf);
                  $newzawartosc_tf = str_replace("{billing_company}",$wielowymiar[$i][18],$newzawartosc_tf);
                  $newzawartosc_tf = str_replace("{billing_name}",$wielowymiar[$i][19],$newzawartosc_tf);
		  $newzawartosc_tf = str_replace("{billing_address}",$wielowymiar[$i][6],$newzawartosc_tf);
		  $newzawartosc_tf = str_replace("{payment_method}",$wielowymiar[$i][20],$newzawartosc_tf);
		  $newzawartosc_tf = str_replace("{date_purchased}",$wielowymiar[$i][21],$newzawartosc_tf);

                  $newzawartosc_tf = str_replace("{customers_street_address}",$wielowymiar[$i][22],$newzawartosc_tf);
                  $newzawartosc_tf = str_replace("{customers_city}",$wielowymiar[$i][23],$newzawartosc_tf);
                  $newzawartosc_tf = str_replace("{customers_postal_code}",$wielowymiar[$i][24],$newzawartosc_tf);
                  $newzawartosc_tf = str_replace("{customers_state}",$wielowymiar[$i][25],$newzawartosc_tf);
                  $newzawartosc_tf = str_replace("{customers_state_code}",findStateAbbreviations($wielowymiar[$i][25]),$newzawartosc_tf);
                  $newzawartosc_tf = str_replace("{customers_country}",$wielowymiar[$i][26],$newzawartosc_tf);
                  $newzawartosc_tf = str_replace("{delivery_street_address}",$wielowymiar[$i][27],$newzawartosc_tf);
                  $newzawartosc_tf = str_replace("{delivery_city}",$wielowymiar[$i][28],$newzawartosc_tf);
                  $newzawartosc_tf = str_replace("{delivery_state}",$wielowymiar[$i][29],$newzawartosc_tf);
                  $newzawartosc_tf = str_replace("{delivery_state_code}",findStateAbbreviations($wielowymiar[$i][29]),$newzawartosc_tf);
                  $newzawartosc_tf = str_replace("{delivery_postal_code}",$wielowymiar[$i][30],$newzawartosc_tf);
                  $newzawartosc_tf = str_replace("{delivery_country}",$wielowymiar[$i][31],$newzawartosc_tf);
                  $newzawartosc_tf = str_replace("{billing_street_address}",$wielowymiar[$i][32],$newzawartosc_tf);
                  $newzawartosc_tf = str_replace("{billing_city}",$wielowymiar[$i][33],$newzawartosc_tf);
                  $newzawartosc_tf = str_replace("{billing_state}",$wielowymiar[$i][34],$newzawartosc_tf);
                  $newzawartosc_tf = str_replace("{billing_state_code}",findStateAbbreviations($wielowymiar[$i][34]),$newzawartosc_tf);
                  $newzawartosc_tf = str_replace("{billing_postal_code}",$wielowymiar[$i][35],$newzawartosc_tf);
                  $newzawartosc_tf = str_replace("{billing_country}",$wielowymiar[$i][36],$newzawartosc_tf);
                  $newzawartosc_tf = str_replace("{customer_suburb}",$wielowymiar[$i][37],$newzawartosc_tf);
                  $newzawartosc_tf = str_replace("{delivery_suburb}",$wielowymiar[$i][38],$newzawartosc_tf);
                  $newzawartosc_tf = str_replace("{billing_suburb}",$wielowymiar[$i][39],$newzawartosc_tf);
                  $newzawartosc_tf = str_replace("{customers_company}",$wielowymiar[$i][40],$newzawartosc_tf);

if ($tmpt[0][7] != PO_CHANGE_SHIPPING_FROM) {
			$newzawartosc = str_replace("{shipping_method}",$tmpt[0][7],$newzawartosc);
                        $newzawartosc_tf = str_replace("{shipping_method}",$tmpt[0][7],$newzawartosc_tf);
} else {
			$newzawartosc = str_replace("{shipping_method}",PO_CHANGE_SHIPPING_TO,$newzawartosc);
                        $newzawartosc_tf = str_replace("{shipping_method}",PO_CHANGE_SHIPPING_TO,$newzawartosc_tf);
}

// Replacing Text For Everyone
for ($y=1;$y<4;$y++) {
      switch ($y) {
        case "1": $replacing=explode('§', PO_REPLACE_BOTH); break;
        case "2": $replacing=explode('§', PO_REPLACE_EMAIL); break;
        case "3": $replacing=explode('§', PO_REPLACE_TF); break;
      }
      $countall = count($replacing);
      if ($countall != 1 && $countall != 0)
      {
         for ($r=0;$r<$countall;$r++) {
            $replacewithnumber = $r + 1;
            switch ($y) 
            {
               case "1": $newzawartosc = str_replace($replacing[$r],$replacing[$replacewithnumber],$newzawartosc);
                         $tematk=str_replace($replacing[$r],$replacing[$replacewithnumber],$tematk);
                         $newzawartosc_tf = str_replace($replacing[$r],$replacing[$replacewithnumber],$newzawartosc_tf); break;
               case "2": $newzawartosc = str_replace($replacing[$r],$replacing[$replacewithnumber],$newzawartosc);
                         $tematk=str_replace($replacing[$r],$replacing[$replacewithnumber],$tematk);  break;
               case "3": $newzawartosc_tf = str_replace($replacing[$r],$replacing[$replacewithnumber],$newzawartosc_tf); break;
            }
            $r++;
         }
      }
}


// Replacing Text For Subcontractor
for ($y=1;$y<4;$y++) {
      switch ($y) {
        case "1": $replacing=explode('§', $subcontractor['replace_both']); break;
        case "2": $replacing=explode('§', $subcontractor['replace_email']); break;
        case "3": $replacing=explode('§', $subcontractor['replace_tf']); break;
      }
      $countall = count($replacing);
      if ($countall != 1 && $countall != 0)
      {
         for ($r=0;$r<$countall;$r++) {
            $replacewithnumber = $r + 1;
            switch ($y) 
            {  
               case "1": $newzawartosc = str_replace($replacing[$r],$replacing[$replacewithnumber],$newzawartosc);
                         $tematk=str_replace($replacing[$r],$replacing[$replacewithnumber],$tematk);
                         $newzawartosc_tf = str_replace($replacing[$r],$replacing[$replacewithnumber],$newzawartosc_tf); break;
               case "2": $newzawartosc = str_replace($replacing[$r],$replacing[$replacewithnumber],$newzawartosc); 
                         $tematk=str_replace($replacing[$r],$replacing[$replacewithnumber],$tematk); break;
               case "3": $newzawartosc_tf = str_replace($replacing[$r],$replacing[$replacewithnumber],$newzawartosc_tf); break;
            }
            $r++;
         }
      }
}

//  Check Additions For Subcontractors on Email
while (areDelimetersThere($newzawartosc, '{+', '}', '{/+', '}')) {
  $sublist = extractBetweenDelimeters($newzawartosc, '{+', '}');
  $sublistarr = explode(',', $sublist);
  if (in_array($subcontractor['subcontractors_id'],$sublistarr)) {
    $newzawartosc = str_replace('{+'.$sublist.'}', '', $newzawartosc);
    $newzawartosc = str_replace('{/+'.$sublist.'}', '', $newzawartosc);
  } else {
    $newzawartosc = removeBetweenDelimeters($newzawartosc, '{+'.$sublist.'}', '{/+'.$sublist.'}');
  }
}
if (stripos($newzawartosc,'{+') !== false)
  $newzawartosc = "***  ERROR - CHECK { + } TAGS FOR ERRORS  ***  " . $newzawartosc;

//  Check Subtractions For Subcontractors on Email
while (areDelimetersThere($newzawartosc, '{-', '}', '{/-', '}')) {
  $sublist = extractBetweenDelimeters($newzawartosc, '{-', '}');
  $sublistarr = explode(',', $sublist);
  if (!in_array($subcontractor['subcontractors_id'],$sublistarr)) {
    $newzawartosc = str_replace('{-'.$sublist.'}', '', $newzawartosc);
    $newzawartosc = str_replace('{/-'.$sublist.'}', '', $newzawartosc);
  } else {
    $newzawartosc = removeBetweenDelimeters($newzawartosc, '{-'.$sublist.'}', '{/-'.$sublist.'}');
  }
}
if (stripos($newzawartosc,'{-') !== false)
  $newzawartosc = "***  ERROR - CHECK { - } TAGS FOR ERRORS  ***  " . $newzawartosc;

//  Check Additions For Subcontractors on Text Attachment
while (areDelimetersThere($newzawartosc_tf, '{+', '}', '{/+', '}')) {
  $sublist = extractBetweenDelimeters($newzawartosc_tf, '{+', '}');
  $sublistarr = explode(',', $sublist);
  if (in_array($subcontractor['subcontractors_id'],$sublistarr)) {
    $newzawartosc_tf = str_replace('{+'.$sublist.'}', '', $newzawartosc_tf);
    $newzawartosc_tf = str_replace('{/+'.$sublist.'}', '', $newzawartosc_tf);
  } else {
    $newzawartosc_tf = removeBetweenDelimeters($newzawartosc_tf, '{+'.$sublist.'}', '{/+'.$sublist.'}');
  }
}
if (stripos($newzawartosc_tf,'{+') !== false)
  $newzawartosc_tf = "***  ERROR - CHECK { + } TAGS FOR ERRORS  ***  " . $newzawartosc_tf;

//  Check Subtractions For Subcontractors on Text Attachment
while (areDelimetersThere($newzawartosc_tf, '{-', '}', '{/-', '}')) {
  $sublist = extractBetweenDelimeters($newzawartosc_tf, '{-', '}');
  $sublistarr = explode(',', $sublist);
  if (!in_array($subcontractor['subcontractors_id'],$sublistarr)) {
    $newzawartosc_tf = str_replace('{-'.$sublist.'}', '', $newzawartosc_tf);
    $newzawartosc_tf = str_replace('{/-'.$sublist.'}', '', $newzawartosc_tf);
  } else {
    $newzawartosc_tf = removeBetweenDelimeters($newzawartosc_tf, '{-'.$sublist.'}', '{/-'.$sublist.'}');
  }
}
if (stripos($newzawartosc_tf,'{-') !== false)
  $newzawartosc_tf = "***  ERROR - CHECK { - } TAGS FOR ERRORS  ***  " . $newzawartosc_tf;

if ($row978[0] == $status_neworder && $status_posentorder != '' && $status_posentorder != NULL && $_POST['reviewthensend'] != 'yes') {			
$query555=mysql_query("INSERT INTO ".TABLE_ORDERS_STATUS_HISTORY."
				(orders_status_id, orders_id, date_added,
 					customer_notified, comments)
  				 values ('$status_posentorder','$tm1',now(),'0','".PO_SENT_COMMENTS."')")
				 or die(mysql_error());
mysql_query("update " . TABLE_ORDERS . "
                        set orders_status = '$status_posentorder', last_modified
 =
 now()
                        where orders_id ='$tm1'");
} else {
$oatmeal = $db->Execute("select comments from " . TABLE_ORDERS_STATUS_HISTORY . " WHERE orders_id = '" . zen_db_input($tm1) . "' order by date_added DESC");
			$catmeow = '';
                                        $catmeow .= "{enter}".$oatmeal->fields['comments'];
                                     
                                   //$catmeow = zen_db_output($oatmeal->fields['comments']);
   if(PO_SENT_COMMENTS != '' && PO_SENT_COMMENTS != NULL && $catmeow != PO_SENT_COMMENTS && $_POST['reviewthensend'] != 'yes') {
       $query555=mysql_query("INSERT INTO ".TABLE_ORDERS_STATUS_HISTORY."
				(orders_status_id, orders_id, date_added,
 					customer_notified, comments)
  				 values ('$row978[0]','$tm1',now(),'0','".PO_SENT_COMMENTS."')")
				 or die(mysql_error());
   }
}
//add Totals Lines
                        $line = array( PO_PDFP_C_ONE_TITLE => '',
                                       PO_PDFP_C_TWO_TITLE => '',
             			       PO_PDFP_C_THREE_TITLE => 'Sub Total',
                                       PO_PDFP_C_FOUR_TITLE => '',
                                       PO_PDFP_C_FIVE_TITLE => $running_total);
		         $size = $pdf->addLine( 240, $line );
                         $y   += $size + 2;
$line = array( PO_PDFP_C_ONE_TITLE => '',
                                       PO_PDFP_C_TWO_TITLE => '',
             			       PO_PDFP_C_THREE_TITLE => 'Shipping',
                                       PO_PDFP_C_FOUR_TITLE => '',
                                       PO_PDFP_C_FIVE_TITLE => number_format($po_shipping,2));
		         $size = $pdf->addLine( 245 , $line );
                         $y   += $size + 2;
$running_total += $po_shipping;
$line = array( PO_PDFP_C_ONE_TITLE => '',
                                       PO_PDFP_C_TWO_TITLE => '',
             			       PO_PDFP_C_THREE_TITLE => 'Total Purchase Order',
                                       PO_PDFP_C_FOUR_TITLE => '',
                                       PO_PDFP_C_FIVE_TITLE => $running_total);
		         $size = $pdf->addLine( 250 , $line );
                         $y   += $size + 2;
// determine what file names and mime types to use

if ($subcontractor['pdffilename'] == '')
    $usepdffilename = PO_PACKINGLIST_FILENAME;
else
    $usepdffilename = $subcontractor['pdffilename'];

if ($subcontractor['tffilename'] == '')
    $usetffilename = PO_TEXTFILE_FILENAME;
else
    $usetffilename = $subcontractor['tffilename'];

if ($subcontractor['tfmimetype'] == '')
    $usetfmimetype = PO_TF_MIMETYPE;
else
    $usetfmimetype = $subcontractor['tffilename'];
     
			//wysylanie e-maila
			if (PURCHASEORDERS_DEBUG == 'Yes') {
			echo "<br>DEBUG--><br>From   :".PO_FROM_EMAIL_NAME." &lt;".PO_FROM_EMAIL_ADDRESS."&gt;<br>To     :".$adresdo."<br>Subject:".$tematk."<br>Content:<br>".str_replace("\n","<br>",$newzawartosc);
		  }
if ($_POST['adduppercommentstoplist'] == 1) { 
if ($_POST['plistcommentascustomer'] != 'yes') {
     $oatmeal = $db->Execute("select comments from " . TABLE_ORDERS_STATUS_HISTORY . " WHERE orders_id = '" . zen_db_input($tmpt[0][4]) . "' order by date_added");
     $catmeow = '';
     /*while(!$oatmeal->EOF){
                                        $catmeow .= "{enter}".$oatmeal->fields['comments'];
                                        $oatmeal->MoveNext();
                                    }
     */
     $catmeow = nl2br(zen_db_output($oatmeal->fields['comments']));
     $catmeow=strip_tags($catmeow); 
     $catmeow=html_entity_decode($catmeow,ENT_QUOTES);
     if ($countproductsonpo != $countproducts)
          $insidelines .= str_replace("{customers_comments}",$catmeow,PO_PARTIALSHIP_PACKINGLIST);
     else
          $insidelines .= str_replace("{customers_comments}",$catmeow,PO_FULLSHIP_PACKINGLIST);
     $insidelines = str_replace("{store_comments}",stripslashes($_POST['plistcomments']),$insidelines);
} else {
     if ($countproductsonpo != $countproducts)
          $insidelines .= str_replace("{customers_comments}",stripslashes($_POST['plistcomments']),PO_PARTIALSHIP_PACKINGLIST);
     else
          $insidelines .= str_replace("{customers_comments}",stripslashes($_POST['plistcomments']),PO_FULLSHIP_PACKINGLIST);
     $insidelines = str_replace("{store_comments}","",$insidelines);
}
$insidelines = str_replace("{shipping_method}",$tmpt[0][7],$insidelines); 
//$insidelines .= $tmpt[0][16];
str_replace("{enter}","\n",$insidelines);
$pdf->addNotes($insidelines);
}
$oatmeal = $db->Execute("select comments from " . TABLE_ORDERS_STATUS_HISTORY . " WHERE orders_id = '" . zen_db_input($tmpt[0][4]) . "' order by date_added");
$catmeow = $oatmeal->fields['comments'];
$custcommentline = $catmeow ;
    
if($_POST['plistcommentascustomer'] == 'yes'){
    $custcommentline .= "\n\n"."Additional Comments: ".$_POST['plistcomments'];
}
                        
        
        
if($custcommentline != ''){
    $custcommentline = 'Customer Comments: '.$custcommentline;
    $pdf->addReference($custcommentline);
}


      $pdf->Output($usepdffilename, "F");
if (PO_SEND_PACKING_LISTS == 4) {
    if ($sendpdf_sc == 'YES')
        $attachthepdf = 'yes';
    else
        $attachthepdf = 'no';
} else {
    $attachthepdf = $_POST['includepackinglistoption'];
}
if (PO_SEND_TEXT_FILE == 4) {
    if ($sendtf_sc == 'YES')
        $attachthetf = 'yes';
    else
        $attachthetf = 'no';
} else {
    $attachthetf = $_POST['includetextfileoption'];
}
if ($_POST['reviewthensend'] == 'yes') {
$numberofpostoreview++;
if ($_GET[sorder] == 1) {
  $pageforreview = '<form name="editpo" action="send_pos.php?sorder=1" method="POST">';
} else {
  $pageforreview = '<form name="editpo" action="send_pos.php" method="POST">';
}
$pageforreview .= '<center>' . REVIEW_EMAIL_EMAIL_TITLE . '&nbsp;<input type="text" name="etitle" size="125" value="' . $tematk .'" /><br /><br />' .
REVIEW_EMAIL_SEND_EMAIL_TO . '&nbsp;<input type="text" name="eaddress" size="125" value="' . $adresdo . '" /><br /><br /><u><b>Email Body</u></b><br />
<textarea rows="30" name="ebody">' . $newzawartosc . '</textarea>';
if ($attachthetf == 'yes')
   $pageforreview .= '<br /><br /><u><b>Text Attachment</u></b><br /><textarea rows="20" name="newzawartosc_tf">' . $newzawartosc_tf . '</textarea>';
$pageforreview .= '<input type="hidden" name="includepackinglistoption" value="' . $attachthepdf . '" /><input type="hidden" name="includetextfileoption" value="' . $attachthetf . '" /><input type="hidden" name="ereview" value="yes" />
<input type="hidden" name="pdffilename" value="' . $usepdffilename . '" />
<input type="hidden" name="tffilename" value="' . $usetffilename . '" />
<input type="hidden" name="tfmimetype" value="' . $usetfmimetype . '" />
<input type="hidden" name="passitwv" value="' . $passitw . '" /><input type="hidden" name="tm1v" value="' . $tm1 . '" />
<input type="hidden" name="kodv" value="' . $kod . '" /><input type="hidden" name="checklastponum" value="' . $_POST['checklastponum'] . '" /><br /><br />';
if ($attachthepdf == 'yes')
    $pageforreview .= '<a href="'.HTTP_SERVER.DIR_WS_ADMIN.$usepdffilename.'" target="_blank">'.PREVIEW_PACKING_LIST.'</a><br /><a href="'.HTTP_SERVER.DIR_WS_ADMIN.$usepdffilename.'" download>Download Packing Slip</a><br /><br />'; 
$pageforreview .= '<input type="image" src="includes/languages/english/images/buttons/button_send.gif" name="insert" ONCLICK="javascript:document.pos.submit();"></center>';

} else {   
$croptions = 1;
if ($croptions == 1) { // Convert Line Ends to Windows Default CR-LF in Text File Attachment
   $newzawartosc_tf = str_replace( chr(13).chr(10) , chr(13) , $newzawartosc_tf);
   $newzawartosc_tf = str_replace( array(chr(13) , chr(10)) , chr(13).chr(10) , $newzawartosc_tf);
}
if ($croptions == 2) { // Convert Line Ends to Mac Default CR in Text File Attachment
   $newzawartosc_tf = str_replace( chr(13).chr(10) , chr(13) , $newzawartosc_tf);
   $newzawartosc_tf = str_replace( chr(10) , chr(13) , $newzawartosc_tf);
}
if ($croptions == 3) { // Convert Line Ends to *nix Default LF in Text File Attachment
   $newzawartosc_tf = str_replace( chr(13).chr(10) , chr(10) , $newzawartosc_tf);
   $newzawartosc_tf = str_replace( chr(13) , chr(10) , $newzawartosc_tf);
}
$html_msg['EMAIL_MESSAGE_HTML'] = str_replace('
','<br />',$newzawartosc);

if ($attachthepdf == 'yes' && $attachthetf == 'yes') {
  $filestoattach = array (
    "0"  => array("file" => $usepdffilename, "mime_type" => "application/pdf"),
    "1"  => array("name" => $usetffilename, "raw_data" => $newzawartosc_tf, "mime_type" => $usetfmimetype)   
  );
  zen_mail($adresdo,$adresdo,$tematk,$newzawartosc,PO_FROM_EMAIL_NAME,PO_FROM_EMAIL_ADDRESS, $html_msg, NULL, $filestoattach);
}
if ($attachthepdf == 'yes' && $attachthetf != 'yes') {
  $filestoattach = array (
    "0"  => array("file" => $usepdffilename, "mime_type" => "application/pdf") 
  );
  zen_mail($adresdo,$adresdo,$tematk,$newzawartosc,PO_FROM_EMAIL_NAME,PO_FROM_EMAIL_ADDRESS, $html_msg, NULL, $filestoattach);
}
if ($attachthepdf != 'yes' && $attachthetf == 'yes') {
  $filestoattach = array (
    "0"  => array("name" => $usetffilename, "raw_data" => $newzawartosc_tf, "mime_type" => $usetfmimetype) 
  );
  zen_mail($adresdo,$adresdo,$tematk,$newzawartosc,PO_FROM_EMAIL_NAME,PO_FROM_EMAIL_ADDRESS, $html_msg, NULL, $filestoattach);
}
if ($attachthepdf != 'yes' && $attachthetf != 'yes') {
  zen_mail($adresdo,$adresdo,$tematk,$newzawartosc,PO_FROM_EMAIL_NAME,PO_FROM_EMAIL_ADDRESS, $html_msg, NULL);
}
$messageStack->add('Purchase Order '. $wielowymiar[$i][4].'-'.$kod .' Emailed To: ' . $adresdo, 'success');                        
}
			// mail($adresdo, $tematk, $newzawartosc, $po_from);
			$tracking_link_good='';
                        $tracking_link_good_tf='';
			$date=date('Y-m-d');
// unlink($pdffilename);
                        if ($_POST['reviewthensend'] != 'yes') {
			for($m=0; $m<count($tmpt); $m++)
			{
				$tm=$tmpt[$m][2];
				$tm2=$tmpt[$m][0];
                                
				// $check_if_po_sent = mysql_query("SELECT * FROM ".TABLE_ORDERS_PRODUCTS." WHERE orders_products_id = '$tm'");
				// $if_po_sent = mysql_fetch_assoc($check_if_po_sent);
				// $po_sent = $if_po_sent['po_sent'];

				
				$result=mysql_query("UPDATE ".TABLE_ORDERS_PRODUCTS." SET po_sent='1', item_shipped=0, po_number='$kod', po_sent_to_subcontractor='$tm2', po_date='$date' WHERE  orders_products_id='$tm' LIMIT 1")	or die("Failed to connect database: 5");
				
			}
                        } else {
                        for($m=0; $m<count($tmpt); $m++)
			{
				$tm=$tmpt[$m][2];
                                $pageforreview .=  '<input type="hidden" name="tmv' . $m . '" value="' . $tm . '" />';
				$tm2=$tmpt[$m][0];
                                $pageforreview .=  '<input type="hidden" name="tm2v' . $m . '" value="' . $tm2 . '" />';
                        }
                        $pageforreview .=  '<input type="hidden" name="mvalue" value="' . $m . '" /></form>';
                        }
		}
	}
}
if($_POST['reviewthensend'] == 'yes' && $numberofpostoreview == 1)
   echo $pageforreview;
if($_POST['reviewthensend'] == 'yes' && $numberofpostoreview > 1) {
   $messageStack = new messageStack();
   $messageStack->add(REVIEW_MANY_POS_ERROR, 'error');
}

if ($messageStack->size > 0) echo $messageStack->output();

if($_POST['reviewthensend'] != 'yes' || $numberofpostoreview == 0) { 
$query110=mysql_query("SELECT max(po_number) FROM ".TABLE_ORDERS_PRODUCTS."")
			or die("Failed to connect database: ");
			$row110=mysql_fetch_array($query110, MYSQL_NUM);
                        $lastponum=$row110[0];
?>
<tr><td class="pageHeading" colspan="2"><?php  echo BOX_CUSTOMERS_SEND_POS; ?><br><br></td></tr>
<td valign="top">
<?php
$sorder = $_GET[sorder];
if ($sorder == 1 && $_POST['co']!='old')
   echo "<a href='".HTTP_SERVER.DIR_WS_ADMIN."send_pos.php'>".SHOW_OLDEST_ORDERS_FIRST."</a>"; 
if ($sorder != 1 && $_POST['co']!='old')
   echo "<a href='".HTTP_SERVER.DIR_WS_ADMIN."send_pos.php?sorder=1'>".SHOW_NEWEST_ORDERS_FIRST."</a>";
?>
</td>
           <tr>  <td valign="top"><br>

		   <?php
		   if($_POST['co']=='old')
			{
		   function sub2()
			{
			$query2=mysql_query("SELECT subcontractors_id,short_name FROM ".TABLE_SUBCONTRACTORS." ORDER BY short_name")
			or die('Failed to connect database: ');

			/*$query99=mysql_query("SELECT  subcontractors_id,short_name FROM subcontractors ORDER BY short_name")
			or die('Failed to connect database: ');*/

			echo "<select name='sub11'>".
			"<option value='%'>".TABLE_ALL_SUBCONTRACTORS."</option>";
			while($row22=mysql_fetch_array($query2, MYSQL_NUM))
			{
			echo "<option value='$row22[0]'>$row22[1]</option>";
			}
			echo '</select>';
			}

//przejscie do szbalonu ktory wyswietla wyslane juz e-maile z starymi numerami po
		   echo	"<form name='drugi' action='send_pos.php' method='post'>
		   <input type='submit' name='old' value='".BUTTON_NEW."'>
		   </form><br>&nbsp;";
//wyszukiwarka
		   echo '<form name="wyszukiwarka" action="send_pos.php" method="POST">
		   <input type="hidden" name="co" value="old">
		   <table border="0" width="100%" cellspacing="0" cellpadding="0">
		   		<tr><td align="center" colspan="2">'.TABLE_DATA_FROM_DATES.': <input type="text" size="10" name="data_od"> '.TABLE_TO.' <input type="text" size="10" name="data_do">
				&nbsp;&nbsp;'.TABLE_PO_PREOVIOUS_NUMBER.': <input type="text" size="10" name="po_number">&nbsp;&nbsp;
				'.TABLE_ORDER_NUMBER.': <input type="text" size="10" name="orders_num">&nbsp;&nbsp;
				'.TABLE_SUBCONTRACTOR.': ';
				sub2();
				echo '&nbsp;&nbsp;'.TABLE_SHOW_DELIVERED_ORDERS.' <input type="checkbox" name="showdeliv" value="1">&nbsp;&nbsp;'.TABLE_ORDEROF_ORDERS.' <input type="checkbox" name="sorderold" value="1">&nbsp;&nbsp;'.TABLE_ALL_CHECKED_BOX.' <input type="checkbox" name="ocoption" value="1">&nbsp;&nbsp;&nbsp;&nbsp;<input type="hidden" name="co2" value="wyswietl"></form><input type="image" src="includes/languages/english/images/buttons/button_search.gif" name="insert1" ONCLICK="javascript:document.wyszukiwarka.submit();"><br><br></td></tr></table></form>';


			}else                                                     
			{
                        echo "<form name='pierwszy' action='send_pos.php' method='post'>
 
		   <input type='submit' name='old' value='".BUTTON_OLD."'>
		   <input type='hidden' name='co' value='old'>
		   </form><br>&nbsp;";
			}

		//wyglad szablonu dla staryc po
		   if($_POST['co']=='old')
			{
			?>
			 <table border="0" width="100%" align="center" cellspacing="0" cellpadding="0">
              <tr class="dataTableHeadingRow">
                <td width='12%' class="dataTableHeadingContent" align="center" >
                  <?php  echo TABLE_ORDER_NUMBER; ?>
                </td>
                <td width='12%' class="dataTableHeadingContent" align="center">
                  <?php echo TABLE_ORDER_ADDRESS;  ?><br>

                </td>
				<td width='12%' class="dataTableHeadingContent" align="center">
                  <?php echo TABLE_PRODUCTS_NAME; ?><br>


                </td>
                <td width='12%' class="dataTableHeadingContent" align="center">
                  <?php echo TABLE_SEND_PO; ?><br>
				</td>
                <td width='12%' class="dataTableHeadingContent" align="center">
                  <?php echo TABLE_PO_SUBCONTRACTOR; ?><br>

                </td>
				<td width='12%' class="dataTableHeadingContent" align="center">
                  <?php echo TABLE_PO_PREOVIOUSLY_SENT_TO; ?><br>

                </td>
				<td width='12%' class="dataTableHeadingContent" align="center">
                  <?php echo TABLE_PO_PREOVIOUS_NUMBER; ?><br>

                </td>
				<td width='12%' class="dataTableHeadingContent" align="center">
                  <?php echo TABLE_PO_WHEN_SEND; ?><br>

                </td>
			</tr><form name='pos' method='post' action='send_pos.php?resend=yes'>


			<?php
//pobieranie danych ktore sa wprowadzone w wyszukiwarce
			if(isset($_POST['co2']) AND $_POST['co2']=='wyswietl')
			{

			if($_POST['data_od']!='')
			{
			$data_od=$_POST['data_od'];
			$zmienna2="AND (p.po_date>='$data_od')";
			}else
			{
			$zmienna2='';
			}

			if($_POST['data_do']!='')
			{
			$data_do=$_POST['data_do'];
			$zmienna1="AND (p.po_date<='$data_do')";
			}else
			{
			$zmienna1='';
			}

			if($_POST['showdeliv']!='1')
			{
			$zmienna3="AND (o.orders_status != '$status_shippedorder') AND (o.orders_status != 105) AND (o.orders_status != 107) AND (o.orders_status != 109) AND (o.orders_status != 111)";
			}else
			{
			$zmienna3='';
			}

			if($_POST['po_number']!='')
			{
			$po_number=$_POST['po_number'];
			}else
			{
			$po_number='%';
			}

			if($_POST['orders_num']!='')
			{
			$orders_num=$_POST['orders_num'];
			}else
			{
			$orders_num='%';
			}

			if($_POST['sub11']!='')
			{
			$sub1=$_POST['sub11'];
			}else
			{
			$sub1='%';
			}



//generowanie pola typu select ktory ma za zadanie wyswietlanie odpowiedniego subcontracotra dla odpowiedniego produktu
			function sub($name, $i, $currentcolor)
			{

			$query2=mysql_query("SELECT  subcontractors_id,short_name FROM ".TABLE_SUBCONTRACTORS." ORDER BY short_name")
			or die('Failed to connect database: ');
			$query232=mysql_query("SELECT products_id, default_subcontractor FROM ".TABLE_PRODUCTS." WHERE products_id='$name'")
			or die ("Nie mzona sie polaczcy z baza danych");
			$row232=mysql_fetch_array($query232, MYSQL_NUM);

			if ($currentcolor==0)
                           echo "<select name='sub$i' style='color:#0000FF;'>";
                        else
                           echo "<select name='sub$i' style='color:#000000;'>";
			while($row22=mysql_fetch_array($query2, MYSQL_NUM))
			{

			echo "<option value='$row22[0]'";

			if($row232[1]==$row22[0])
			{
			echo "selected";
			}
			echo ">$row22[1]</option>";
			}
			echo "</select>";
			}


                        if ($_POST['sorderold'] == 1) {
			$query=mysql_query("SELECT p.orders_products_id, p.orders_id, p.orders_products_id, p.products_name, p.po_number,  p.po_sent_to_subcontractor, p.products_id, p.po_date, o.delivery_street_address, o.delivery_city, o.delivery_suburb, o.delivery_postcode, o.delivery_country, o.delivery_company, o.delivery_name, o.delivery_state, p.products_quantity FROM ".TABLE_ORDERS_PRODUCTS." as p, ".TABLE_ORDERS." as o WHERE  p.orders_id=o.orders_id AND p.po_sent='1'
			AND  (p.orders_id LIKE '$orders_num') AND (p.po_number LIKE '$po_number') AND  (p.po_sent_to_subcontractor LIKE '$sub1') $zmienna2 $zmienna1 $zmienna3 ORDER BY orders_id ASC")
			or die('Failed to connect database: 8');
} else {
	$query=mysql_query("SELECT p.orders_products_id, p.orders_id, p.orders_products_id, p.products_name, p.po_number,  p.po_sent_to_subcontractor, p.products_id, p.po_date, o.delivery_street_address, o.delivery_city, o.delivery_suburb, o.delivery_postcode, o.delivery_country, o.delivery_company, o.delivery_name, o.delivery_state, p.products_quantity FROM ".TABLE_ORDERS_PRODUCTS." as p, ".TABLE_ORDERS." as o WHERE  p.orders_id=o.orders_id AND p.po_sent='1'
			AND  (p.orders_id LIKE '$orders_num') AND (p.po_number LIKE '$po_number') AND  (p.po_sent_to_subcontractor LIKE '$sub1') $zmienna2 $zmienna1 $zmienna3 ORDER BY orders_id DESC")
			or die('Failed to connect database: 8');
}




//wyswietlanie danych
			$i=1;
                        $lon=0;
                        $currentcolor=0;
			while($row2=mysql_fetch_array($query, MYSQL_NUM))
			{
                        $lastlon=$lon;
                        $lon=$row2[1];
                        $resultpa=mysql_query("SELECT orders_id, orders_products_id, products_options, products_options_values
									  FROM ".TABLE_ORDERS_PRODUCTS_ATTRIBUTES."
									  WHERE orders_products_id='$row2[0]' AND orders_id='$row2[1]'")
				or die("Failed to connect database: ");
                        $attributes='';
                        while($rowpa=mysql_fetch_array($resultpa, MYSQL_NUM))
				{
					$attributes=$attributes."<br />".$rowpa[2].": ".$rowpa[3];
				}
                                if(metro_attibutes($row2[0], $row2[1]) != false){
                                    $attributes=metro_attibutes($row2[0], $row2[1]);
                                }

			/* $query3=mysql_query("SELECT * FROM ".TABLE_ORDERS." as o, ".TABLE_ORDERS_PRODUCTS." as p WHERE o.orders_id = o.orders_id AND o.orders_id='$row2[1]'")
			or die('Failed to connect database: ');
			 if ($row2[5]==0)
			{
			$row100[0]="Own stock";
			}else
			{ */
			$query100=mysql_query("SELECT short_name FROM ".TABLE_SUBCONTRACTORS." WHERE subcontractors_id='$row2[5]'")
			or die('Failed to connect database: ');
			$row100=mysql_fetch_array($query100, MYSQL_NUM);
			/* } 

			$row3=mysql_fetch_array($query3, MYSQL_NUM);  */

if ($row2[12] == zen_get_country_name(STORE_COUNTRY))
	$orderaddresscountry="";
else
	$orderaddresscountry="<br />".$row2[12];
if ($row2[10] == "" || $row2[10] == NULL)
	$orderaddresssuburb="";
else
	$orderaddresssuburb="<br />".$row2[10];
if ($row2[13] == "" || $row2[13] == NULL)
	$orderaddresscompany="";
else
	$orderaddresscompany=$row2[13]."<br />";
$ordersaddress = $orderaddresscompany.$row2[8].$orderaddresssuburb."<br />".$row2[9].", ".$row2[15]." ".$row2[11].$orderaddresscountry;
                        if($lon!=$lastlon) {
                          if ($currentcolor == 1)
                             $currentcolor = 0;
                          else
                             $currentcolor = 1;
                        }
                        if ($currentcolor == 0) {
			  if($i%2==1)
			    echo "<tr style='background-color:#e7e6e0; color:#0000FF;'>";
			  else
                            echo "<tr style='background-color:#f2f1ee; color:#0000FF;'>";
                        } else {
                          if($i%2==1)
			    echo "<tr style='background-color:#e7e6e0;'>";
			  else
                            echo "<tr style='background-color:#f2f1ee;'>";
                       }
			echo "<td  align='center'>$row2[1]</td><td  align='center'>$row2[14]<br />$ordersaddress</td><td  align='center'>$row2[16] x $row2[3] $attributes</td><td align='center'><input type='checkbox' name='pos$i'";
                        if ($_POST['ocoption'] == 1)
                           echo "CHECKED";
                        echo "></td><td  align='center'>";
			sub($row2[6], $i, $currentcolor);
			echo "</td>".
			"<td  align='center'>$row100[0]</td>".
			"<td  align='center'>$row2[4]</td>".
			"<td  align='center'>$row2[7]</td>".
			"</tr><input type='hidden' name='opi$i' value='$row2[2]'><input type='hidden' name='id$i' value=$row2[0]>";
			$i++;
			}
			echo "<input type='hidden' name='krotnosc' value='$i'>";
?>
<input type='hidden' name='what' value='send'><input type='hidden' name='checklastponum' value='<?php echo $lastponum; ?>'>
 <tr><td colspan='8'align='center'><br><br></td></tr>
 <tr><td colspan='8'align='center'><?php echo TABLE_COMMENTS_FOR_POS; ?>:&nbsp;<input type="text" name="posubcomments" size="110"></td></tr>
<tr><td colspan='8'align='center'><br><br></td></tr>
<?php if (PO_SEND_PACKING_LISTS != 0) { ?>
<tr><td colspan='8'align='center'><?php echo TABLE_COMMENTS_FOR_PACKING_LISTS; ?>:&nbsp;<input type="text" name="plistcomments" size="90" maxlength="90">&nbsp;&nbsp;<input type="checkbox" name="plistcommentascustomer" value="yes"><?php echo TABLE_PO_COMMENTS_AS_CUSTOMER; ?></td></tr><?php } ?>
<tr><td colspan='8'align='center'><?php echo COMMENTS_WARNING; ?></td></tr>
<tr><td colspan='8'align='center'><br></td></tr><tr><td colspan='8'align='center'>
<?php if (PO_SEND_PACKING_LISTS == 0) { ?>
<input type="hidden" name="includepackinglistoption" value="no"> <?php } ?>
<?php if (PO_SEND_PACKING_LISTS == 1) { ?>
<input type="hidden" name="includepackinglistoption" value="yes"> <?php } ?>
<?php if (PO_SEND_PACKING_LISTS == 2) { ?>
<?php echo TABLE_INCLUDE_PACKINGLIST_OPTION; ?><input type="checkbox" name="includepackinglistoption" value="yes" CHECKED>&nbsp;&nbsp;&nbsp;&nbsp; <?php } ?>
<?php if (PO_SEND_PACKING_LISTS == 3) { ?>
<?php echo TABLE_INCLUDE_PACKINGLIST_OPTION; ?><input type="checkbox" name="includepackinglistoption" value="yes">&nbsp;&nbsp;&nbsp;&nbsp; <?php } ?>
<?php if (PO_SEND_PACKING_LISTS != 0) { ?>
<?php if (PO_PDFP_SHIP_COMMENTS_NAME == '') { ?>
<input type="hidden" name="adduppercommentstoplist" value="1" />
<?php } else { ?>
<?php echo "Add ".PO_PDFP_SHIP_COMMENTS_NAME." to Packing Lists"; ?><input type="checkbox" name="adduppercommentstoplist" value="1" CHECKED>&nbsp;&nbsp;&nbsp;&nbsp;<?php } ?>
<?php if (PO_PDFP_CUST_COMMENT_NAME == '') { ?>
<input type="hidden" name="addcommentstoplist" value="1" />
<?php } else { ?>
<?php echo "Add ".PO_PDFP_CUST_COMMENT_NAME." to Packing Lists"; ?><input type="checkbox" name="addcommentstoplist" value="1" CHECKED>&nbsp;&nbsp;&nbsp;&nbsp;<?php } } ?>
<?php if (PO_SEND_TEXT_FILE == 0) { ?>
<input type="hidden" name="includetextfileoption" value="no"><?php } ?>
<?php if (PO_SEND_TEXT_FILE == 1) { ?>
<input type="hidden" name="includetextfileoption" value="yes"><?php } ?>
<?php if (PO_SEND_TEXT_FILE == 2) { ?>
<?php echo TABLE_INCLUDE_TEXTFILE_OPTION; ?><input type="checkbox" name="includetextfileoption" value="yes" CHECKED><?php } ?>
<?php if (PO_SEND_TEXT_FILE == 3) { ?>
<?php echo TABLE_INCLUDE_TEXTFILE_OPTION; ?><input type="checkbox" name="includetextfileoption" value="yes"><?php } ?><br>
<?php if (PO_SEND_TEXT_FILE == 2 || PO_SEND_TEXT_FILE == 3 || PO_SEND_PACKING_LISTS != 0) echo "<br>"; ?>
<?php echo TABLE_REVIEW_EMAIL_OPTION; ?><input type="checkbox" name="reviewthensend" value="yes" />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type="image" src="includes/languages/english/images/buttons/button_send.gif" name='insert' ONCLICK="javascript:document.pos.submit();"></td></tr></form>

		</table>
<?php }


			}else
			{
//generowanie szablonu dla nie wysllanych numerow po
		   ?>
		   <table border="0" width="100%" align="center" cellspacing="0" cellpadding="0">
              <tr class="dataTableHeadingRow">
                <td width='5%' class="dataTableHeadingContent" align="center" >
                  <?php  echo TABLE_ORDER_NUMBER; ?>
                </td>
        
<td width='15%' class="dataTableHeadingContent" align="center">
                  <?php echo TABLE_ORDER_COMMENTS;  ?><br>
</td>
<td width='15%' class="dataTableHeadingContent" align="center">
                  <?php echo TABLE_ORDER_SHIPPING;  ?><br>

                </td>
 <td width='20%' class="dataTableHeadingContent" align="center">
                  <?php echo TABLE_ORDER_ADDRESS;  ?><br>

                </td>
<td width='5%' class="dataTableHeadingContent" align="center">
                  <?php echo TABLE_ORDER_PRODUCT_MANUFACTURER; ?><br>


                </td>
				<td width='15%' class="dataTableHeadingContent" align="center">
                  <?php echo TABLE_PRODUCTS_NAME; ?><br>


                </td>
                <td width='5%' class="dataTableHeadingContent" align="center">
                  <?php echo TABLE_SEND_PO . "<br />"; 
                if ($sorder == 1)
                          echo "<a href='send_pos.php?csoption=1&sorder=1'>" . TABLE_CHECK_ALL_LINK . "</a>";
                        else
                          echo "<a href='send_pos.php?csoption=1'>CHECK ALL</a>";
                  ?><br>
				</td>
                <td width='7%' class="dataTableHeadingContent" align="center">
                  <?php echo 'PO Price</br>Each'; ?>

                </td>                
                <td width='10%' class="dataTableHeadingContent" align="center">
                  <?php echo TABLE_PO_SUBCONTRACTOR; ?><br>

                </td>
                <td width='3%' class="dataTableHeadingContent" align="center">
                  <?php echo 'PO</br>Shipping'; ?>

                </td>

			</tr>
                        <?php
                        if ($sorder == 1)
                          echo "<form name='pos' method='post' action='send_pos.php?sorder=1'>";
                        else
                          echo "<form name='pos' method='post' action='send_pos.php'>";
		


			function sub78($name, $i, $currentcolor)
			{

			$query2=mysql_query("SELECT  subcontractors_id,short_name FROM ".TABLE_SUBCONTRACTORS." ORDER BY short_name")
			or die('Failed to connect database: ');
			$query232=mysql_query("SELECT products_id, default_subcontractor FROM ".TABLE_PRODUCTS." WHERE products_id='$name'")
			or die ("Nie mzona sie polaczcy z baza danych");
			$row232=mysql_fetch_array($query232, MYSQL_NUM);
                        
                        if ($currentcolor==0)
                           echo "<select name='sub$i' style='color:#0000FF;'>";
                        else
                           echo "<select name='sub$i' style='color:#000000;'>";
			while($row22=mysql_fetch_array($query2, MYSQL_NUM))
			{
			echo "<option value='$row22[0]'";

			if($row232[1]==$row22[0])
			{
			echo "selected";
			}
			echo ">$row22[1]</option>";
			}
			echo "</select>";
			}
                        


			$a=$_GET["a"];
$l_odp_napasku='10';
$l_odp_nastronie=PO_MAX_SEND;
$start=$a*$l_odp_nastronie;

$skrypt="send_pos.php?";

// Get Order Status Numbers For Other Excluded Orders

      $ignore_status_name=explode(',', PO_IGNORE_STATUS); 
      
      $count_ignore_status = count($ignore_status_name);
      $r=0;
      $ignore_status_database='';
      while ($r<$count_ignore_status)
      {
           $tmp=$ignore_status_name[$r];
           if ($tmp != '')
           {
                $querygot2=mysql_query("SELECT orders_status_id FROM ".TABLE_ORDERS_STATUS." WHERE orders_status_name='$tmp'")
			or die("Failed to connect database: ");
	        $rowgot2=mysql_fetch_array($querygot2, MYSQL_NUM);
                $ignore_status_database .= " AND o.orders_status != '" . $rowgot2[0] ."'";  
           }
           $r++;
      }

if ($sorder == 1) {
			$queryxx=mysql_query("SELECT p.orders_products_id, p.orders_id, p.orders_products_id, p.products_name, p.products_id, o.shipping_method, o.delivery_state, p.products_quantity, o.delivery_street_address, o.delivery_city, o.delivery_suburb, o.delivery_postcode, o.delivery_country, o.delivery_company, o.delivery_name, p.products_model FROM ".TABLE_ORDERS_PRODUCTS." as p, ".TABLE_ORDERS." as o WHERE  p.orders_id=o.orders_id AND po_sent='0' AND o.orders_status != '$status_shippedorder' AND po_number  IS NULL $ignore_status_database ORDER by p.orders_id DESC")
			or die('Failed to connect database: 8');

			$l_odp = mysql_num_rows($queryxx);

			$query=mysql_query("SELECT p.orders_products_id, p.orders_id, p.orders_products_id, p.products_name, p.products_id, o.shipping_method, o.delivery_state, p.products_quantity, o.delivery_street_address, o.delivery_city, o.delivery_suburb, o.delivery_postcode, o.delivery_country, o.delivery_company, o.delivery_name, p.products_model FROM ".TABLE_ORDERS_PRODUCTS." as p, ".TABLE_ORDERS." as o WHERE  p.orders_id=o.orders_id AND po_sent='0' AND o.orders_status != '$status_shippedorder' AND po_number  IS NULL $ignore_status_database ORDER by p.orders_id DESC LIMIT $start, $l_odp_nastronie")
			or die('Failed to connect database: 8');
} else {
	$queryxx=mysql_query("SELECT p.orders_products_id, p.orders_id, p.orders_products_id, p.products_name, p.products_id, o.shipping_method, o.delivery_state, p.products_quantity, o.delivery_street_address, o.delivery_city, o.delivery_suburb, o.delivery_postcode, o.delivery_country, o.delivery_company, o.delivery_name, p.products_model FROM ".TABLE_ORDERS_PRODUCTS." as p, ".TABLE_ORDERS." as o WHERE  p.orders_id=o.orders_id AND po_sent='0' AND o.orders_status != '$status_shippedorder' AND po_number  IS NULL $ignore_status_database ORDER by p.orders_id ASC")
			or die('Failed to connect database: 8');

			$l_odp = mysql_num_rows($queryxx);

			$query=mysql_query("SELECT p.orders_products_id, p.orders_id, p.orders_products_id, p.products_name, p.products_id, o.shipping_method, o.delivery_state, p.products_quantity, o.delivery_street_address, o.delivery_city, o.delivery_suburb, o.delivery_postcode, o.delivery_country, o.delivery_company, o.delivery_name, p.products_model FROM ".TABLE_ORDERS_PRODUCTS." as p, ".TABLE_ORDERS." as o WHERE  p.orders_id=o.orders_id AND po_sent='0' AND o.orders_status != '$status_shippedorder' AND po_number  IS NULL $ignore_status_database ORDER by p.orders_id ASC LIMIT $start, $l_odp_nastronie")
			or die('Failed to connect database: 8');
}

			$i=1;
                        $lon=0;
                        $currentcolor=0;
			while($row2=mysql_fetch_array($query, MYSQL_NUM))
			{
                        $lastlon=$lon;
                        $lon=$row2[1];
                        $resultpa=mysql_query("SELECT orders_id, orders_products_id, products_options, products_options_values
									  FROM ".TABLE_ORDERS_PRODUCTS_ATTRIBUTES."
									  WHERE orders_products_id='$row2[0]' AND orders_id='$row2[1]'")
				or die("Failed to connect database: ");
                        $attributes='';
                        while($rowpa=mysql_fetch_array($resultpa, MYSQL_NUM))
				{
					$attributes=$attributes."<br />".$rowpa[2].": ".$rowpa[3];
				}
			if(metro_attibutes($row2[0], $row2[1]) != false){
                                    $attributes=metro_attibutes($row2[0], $row2[1]);
                                }

$oatmeal = $db->Execute("select comments from " . TABLE_ORDERS_STATUS_HISTORY . " WHERE orders_id = '" . zen_db_input($row2[1]) . "' order by date_added");
$catmeow = '';			
while(!$oatmeal->EOF){
                                        $catmeow = "{enter}".$oatmeal->fields['comments'];
                                        $oatmeal->MoveNext();
                                    }
                                   
                                   //$catmeow = nl2br(zen_db_output($oatmeal->fields['comments']));
$manufacturernamed= zen_get_products_manufacturers_name($row2[4]);

if ($row2[12] == zen_get_country_name(STORE_COUNTRY))
	$orderaddresscountry="";
else
	$orderaddresscountry="<br />".$row2[12];
if ($row2[10] == "" || $row2[10] == NULL)
	$orderaddresssuburb="";
else
	$orderaddresssuburb="<br />".$row2[10];
if ($row2[13] == "" || $row2[13] == NULL)
	$orderaddresscompany="";
else
	$orderaddresscompany=$row2[13]."<br />";
$ordersaddress = $orderaddresscompany.$row2[8].$orderaddresssuburb."<br />".$row2[9].", ".$row2[6]." ".$row2[11].$orderaddresscountry; 
                        if($lon!=$lastlon) {
                          if ($currentcolor == 1)
                             $currentcolor = 0;
                          else
                             $currentcolor = 1;
                        }
                        if ($currentcolor == 0) {
			  if($i%2==1)
			    echo "<tr style='background-color:#e7e6e0; color:#0000FF;'>";
			  else
                            echo "<tr style='background-color:#f2f1ee; color:#0000FF;'>";
                        } else {
                          if($i%2==1)
			    echo "<tr style='background-color:#e7e6e0;'>";
			  else
                            echo "<tr style='background-color:#f2f1ee;'>";
                       } /* 7801 */
			echo "<td  align='center'>$row2[1]</td><td  align='center'>$catmeow</td><td  align='center'>$row2[5]</td><td  align='center'>$row2[14]<br />$ordersaddress</td><td  align='center'>$manufacturernamed $row2[15]</td><td  align='center'>$row2[7] x $row2[3] $attributes</td><td align='center'><input type='checkbox' name='pos$i'";
                        if ($_GET[csoption] == 1)
                           echo " CHECKED";
                        echo "></td><td align='center'><input type='text' name='poprice$i' value=".get_po_price_value($row2[2])."></td><td  align='center'>";
			sub78($row2[4], $i, $currentcolor);
			echo "</td>"."<td align='center'><input type='text' name='poship$i' value=".get_po_shipping_value($row2[2]).">".
			"</tr><input type='hidden' name='opi$i' value='$row2[2]'><input type='hidden' name='id$i' value=$row2[0]>";
			$i++;
			}
			echo "<input type='hidden' name='krotnosc' value='$i'>";




   ?><input type='hidden' name='what' value='send'><input type='hidden' name='checklastponum' value='<?php echo $lastponum; ?>'>
   <?php pasek($l_odp,$l_odp_nastronie,$l_odp_napasku,$skrypt,$a);  ?>
 <tr><td colspan='9'align='center'><br><br></td></tr>
 <tr><td colspan='9'align='center'><?php echo TABLE_COMMENTS_FOR_POS; ?>:&nbsp;<input type="text" name="posubcomments" size="110"></td></tr>
<tr><td colspan='9'align='center'><br><br></td></tr>
<?php if (PO_SEND_PACKING_LISTS != 0) { ?>
<tr><td colspan='9'align='center'><?php echo TABLE_COMMENTS_FOR_PACKING_LISTS; ?>:&nbsp;<input type="text" name="plistcomments" size="90" maxlength="90">&nbsp;&nbsp;<input type="checkbox" name="plistcommentascustomer" value="yes"><?php echo TABLE_PO_COMMENTS_AS_CUSTOMER; ?></td></tr><?php } ?>
<tr><td colspan='9'align='center'><?php echo COMMENTS_WARNING; ?></td></tr>
<tr><td colspan='9'align='center'><br></td></tr><tr><td colspan='8'align='center'>
<?php if (PO_SEND_PACKING_LISTS == 0) { ?>
<input type="hidden" name="includepackinglistoption" value="no"> <?php } ?>
<?php if (PO_SEND_PACKING_LISTS == 1) { ?>
<input type="hidden" name="includepackinglistoption" value="yes"> <?php } ?>
<?php if (PO_SEND_PACKING_LISTS == 2) { ?>
<?php echo TABLE_INCLUDE_PACKINGLIST_OPTION; ?><input type="checkbox" name="includepackinglistoption" value="yes" CHECKED>&nbsp;&nbsp;&nbsp;&nbsp; <?php } ?>
<?php if (PO_SEND_PACKING_LISTS == 3) { ?>
<?php echo TABLE_INCLUDE_PACKINGLIST_OPTION; ?><input type="checkbox" name="includepackinglistoption" value="yes">&nbsp;&nbsp;&nbsp;&nbsp; <?php } ?>
<?php if (PO_SEND_PACKING_LISTS != 0) { ?>
<?php if (PO_PDFP_SHIP_COMMENTS_NAME == '') { ?>
<input type="hidden" name="adduppercommentstoplist" value="1" />
<?php } else { ?>
<?php echo "Add ".PO_PDFP_SHIP_COMMENTS_NAME." to Packing Lists"; ?><input type="checkbox" name="adduppercommentstoplist" value="1" CHECKED>&nbsp;&nbsp;&nbsp;&nbsp;<?php } ?>
<?php if (PO_PDFP_CUST_COMMENT_NAME == '') { ?>
<input type="hidden" name="addcommentstoplist" value="1" />
<?php } else { ?>
<?php echo "Add ".PO_PDFP_CUST_COMMENT_NAME." to Packing Lists"; ?><input type="checkbox" name="addcommentstoplist" value="1" CHECKED>&nbsp;&nbsp;&nbsp;&nbsp;<?php } } ?>
<?php if (PO_SEND_TEXT_FILE == 0) { ?>
<input type="hidden" name="includetextfileoption" value="no"><?php } ?>
<?php if (PO_SEND_TEXT_FILE == 1) { ?>
<input type="hidden" name="includetextfileoption" value="yes"><?php } ?>
<?php if (PO_SEND_TEXT_FILE == 2) { ?>
<?php echo TABLE_INCLUDE_TEXTFILE_OPTION; ?><input type="checkbox" name="includetextfileoption" value="yes" CHECKED><?php } ?>
<?php if (PO_SEND_TEXT_FILE == 3) { ?>
<?php echo TABLE_INCLUDE_TEXTFILE_OPTION; ?><input type="checkbox" name="includetextfileoption" value="yes"><?php } ?><br>
<?php if (PO_SEND_TEXT_FILE == 2 || PO_SEND_TEXT_FILE == 3 || PO_SEND_PACKING_LISTS != 0) echo "<br>"; ?>
<?php echo TABLE_REVIEW_EMAIL_OPTION; ?><input type="checkbox" name="reviewthensend" value="yes" />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type="image" src="includes/languages/english/images/buttons/button_send.gif" name='insert' ONCLICK="javascript:document.pos.submit();"></td></tr></form>
<tr><td colspan='9'align='center'><br><br></td></tr>
		</table>
<?php } ?>
		</td>

      </tr>
</table>

<?php } require(DIR_WS_INCLUDES . 'footer.php'); ?>
<!-- footer_eof //-->
<br>
</body>
</html>
<?php require(DIR_WS_INCLUDES . 'application_bottom.php'); ?>