<?php
/**
* send po to unknown customer
*/

require('includes/application_top.php');
require(DIR_WS_CLASSES . 'currencies.php');
define('FPDF_FONTPATH','fpdf/font/');
require('pdfpack.php');

//load email templates
@ $wp1 = fopen("../email/email_header.txt", 'r');
@ $wp2 = fopen("../email/email_products.txt", 'r');
@ $wp3 = fopen("../email/email_footer.txt", 'r');

//load text file attachment templates
@ $tf1 = fopen("../email/textattach_header.txt", 'r');
@ $tf2 = fopen("../email/textattach_products.txt", 'r');
@ $tf3 = fopen("../email/textattach_footer.txt", 'r');

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
cssjsmenu("navbar");
if (document.getElementById)
{
var kill = document.getElementById("hoverJS");
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



<!-- body //-->

<br>
<!-- body_text //--><br>
<?php
if ($_POST[numberofproducts] >= 1)
    $numberofproducts = $_POST[numberofproducts];
else
    $numberofproducts = 0;
$step = zen_db_prepare_input($_POST['step']);
$add_product_categories_id = zen_db_prepare_input($_POST['add_product_categories_id']);
$add_product_products_id = zen_db_prepare_input($_POST['add_product_products_id']);
$add_product_quantity = zen_db_prepare_input($_POST['add_product_quantity']);
$partialship = $_POST[partialship];
$sub = $_POST[sub];

if ($_POST[includepackinglistoption] != "" || $_POST[includepackinglistoption] != NULL) {
    if (PO_SEND_PACKING_LISTS != 2 && PO_SEND_PACKING_LISTS != 3)
        $includeplistoption = "";
    else
        $includeplistoption = $_POST[includepackinglistoption];	
} else {
    if (PO_SEND_PACKING_LISTS == 2)
        $includeplistoption = "yes";
    if (PO_SEND_PACKING_LISTS == 2 && ($numberofproducts > 0 || $_POST[step] > 1))
        $includeplistoption = "no";
    if (PO_SEND_PACKING_LISTS == 3)
        $includeplistoption = "no";
    if (PO_SEND_PACKING_LISTS != 2 && PO_SEND_PACKING_LISTS != 3)
        $includeplistoption = "";
}


if ($_POST[includetextfileoption] != "" || $_POST[includetextfileoption] != NULL) { 
    if (PO_SEND_TEXT_FILE != 2 && PO_SEND_TEXT_FILE != 3)
        $includetfoption = "";
    else
        $includetfoption = $_POST[includetextfileoption];	
} else {
    if (PO_SEND_TEXT_FILE == 2)
        $includetfoption = "yes";
    if (PO_SEND_TEXT_FILE == 2 && ($numberofproducts > 0 || $_POST[step] > 1))
        $includetfoption = "no";
    if (PO_SEND_TEXT_FILE == 3)
        $includetfoption = "no";
    if (PO_SEND_TEXT_FILE != 2 && PO_SEND_PACKING_LISTS != 3)
        $includetfoption = "";
}

if ($_POST[plistcommentascustomer] != "" || $_POST[plistcommentascustomer] != NULL)
    $plistcommentascustomer = $_POST[plistcommentascustomer];
for ($i=0; $i<=$numberofproducts; $i++) {
         $passiton = "productlistid".$i;
	 $productlistid[$i] = $_POST[$passiton];
         $passiton = "quantitylist".$i;
	 $quantitylist[$i] = $_POST[$passiton]; 
         $passiton = "attributelist".$i;
	 $attributelist[$i] = $_POST[$passiton];
         $passiton = "manufacturerlist".$i;
	 $manufacturerlist[$i] = $_POST[$passiton];
         $passiton = "productnamelist".$i;
	 $productnamelist[$i] = $_POST[$passiton];
         $passiton = "productmodellist".$i;
	 $productmodellist[$i] = $_POST[$passiton];
$attributelist[$i] = stripslashes($attributelist[$i]);
$manufacturerlist[$i] = stripslashes($manufacturerlist[$i]);
$productnamelist[$i] = stripslashes($productnamelist[$i]);
$productmodellist[$i] = stripslashes($productmodellist[$i]);

}
if($_POST[postonc]=='yes') {
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

   $tresc_robij1_tf = str_replace( chr(13).chr(10) , chr(13) , $tresc_robij1_tf);
   $tresc_robij1_tf = str_replace( array(chr(13) , chr(10)) , chr(13).chr(10) , $tresc_robij1_tf);
   $zawartosc_tf = str_replace( chr(13).chr(10) , chr(13) , $zawartosc_tf);
   $zawartosc_tf = str_replace( array(chr(13) , chr(10)) , chr(13).chr(10) , $zawartosc_tf);

                        $billto = $_POST[billingaddress];
                        $billto = stripslashes($billto);
                        if ($_POST[shippingaddress] == '' || $_POST[shippingaddress] == NULL) {
                             $shipto=$billto; }
                        else {
                             $shipto = $_POST[shippingaddress];
                             $shipto = stripslashes($shipto); }
                        $pdf = new INVOICE( 'P', 'mm', 'Letter' );
			$pdf->Open();
			$pdf->AddPage();
			$storeaddressnocr = str_replace(STORE_NAME.chr(13).chr(10),"",STORE_NAME_ADDRESS);
                        $storeaddressnocr = str_replace(STORE_NAME.chr(13),"",$storeaddressnocr);
                        $storeaddressnocr = str_replace(STORE_NAME." ","",$storeaddressnocr);
                        $storeaddressnocr = str_replace(STORE_NAME,"",$storeaddressnocr);
                        $pdf->addSociete( STORE_NAME,

              			          $storeaddressnocr );
			$pdf->fact_dev( PACKING_LIST_FIRST_WORD." ", PACKING_LIST_SECOND_WORD );
			$invdate=date("m-d-Y");
			$pdf->addDate($invdate);
			$pdf->addClient(stripslashes($_POST[ponumber]));
			$pdf->addClientShipAdresse($shipto);
			$pdf->addClientBillAdresse($billto);
			
			if ($_POST[shippingoption] == '' or $_POST[shippingoption] == NULL)
				$shipping=PO_CHANGE_SHIPPING_TO;
			else
				$shipping=stripslashes($_POST[shippingoption]);
			
			if ($_POST[plistcommentascustomer] != 'yes') {
			if ($_POST[partialship] == 1)
				$insidelines = str_replace("{customers_comments}","",PO_PDFP_CUST_COMMENT_PARTIAL);
			else
				$insidelines = str_replace("{customers_comments}","",PO_PDFP_CUST_COMMENT_FULL);
			$insidelines = str_replace("{store_comments}",stripslashes($_POST[plistcomments]),$insidelines);
			} else {
			if ($_POST[partialship] == 1)
				$insidelines = str_replace("{customers_comments}",stripslashes($_POST[plistcomments]),PO_PDFP_CUST_COMMENT_PARTIAL);
			else
				$insidelines = str_replace("{customers_comments}",stripslashes($_POST[plistcomments]),PO_PDFP_CUST_COMMENT_FULL);
			$insidelines = str_replace("{store_comments}","",$insidelines);
			}
			$insidelines = str_replace("{shipping_method}",$shipping,$insidelines);    
			$pdf->addReference($insidelines);

			
			$cols=array( PACKING_LIST_MODEL_NUMBER    => 40,
           			     PACKING_LIST_PRODUCT_DESCRIPTION  => 120.9,
        			     PACKING_LIST_QUANTITY     => 25 );
			$pdf->addCols($cols);
			$cols=array( PACKING_LIST_MODEL_NUMBER    => "L",
       			      	     PACKING_LIST_PRODUCT_DESCRIPTION  => "L",
       			             PACKING_LIST_QUANTITY     => "C" );
			$pdf->addLineFormat($cols);
			$pdf->addLineFormat($cols);
			$y    = 89;
			
			// Determine Length of Product List
                        if ($countproductsonpo != $countproducts) {
                                          if (PO_PARTIALSHIP_PACKINGLIST != '')
                                               $ynot = 185;
                                          else
                                               $ynot = 225;
                                     } else {
                                         if (PO_FULLSHIP_PACKINGLIST != '')
                                               $ynot = 185;
                                          else
                                               $ynot = 225;
                        }
			
                        for ($i=1; $i<=$numberofproducts; $i++) {
						
						if ($y > $ynot) {  // Start New Page
						$pdf->AddPage();
			$storeaddressnocr = str_replace(STORE_NAME.chr(13).chr(10),"",STORE_NAME_ADDRESS);
                        $storeaddressnocr = str_replace(STORE_NAME.chr(13),"",$storeaddressnocr);
                        $storeaddressnocr = str_replace(STORE_NAME." ","",$storeaddressnocr);
                        $storeaddressnocr = str_replace(STORE_NAME,"",$storeaddressnocr);
                        $pdf->addSociete( STORE_NAME,

              			          $storeaddressnocr );
			$pdf->fact_dev( PACKING_LIST_FIRST_WORD." ", PACKING_LIST_SECOND_WORD );
			$invdate=date("m-d-Y");
			$pdf->addDate($invdate);
			$pdf->addClient(stripslashes($_POST[ponumber]));
			$pdf->addClientShipAdresse($shipto);
			$pdf->addClientBillAdresse($billto);
			
			if ($_POST[plistcommentascustomer] != 'yes') {
			if ($_POST[partialship] == 1)
				$insidelines = str_replace("{customers_comments}","",PO_PDFP_CUST_COMMENT_PARTIAL);
			else
				$insidelines = str_replace("{customers_comments}","",PO_PDFP_CUST_COMMENT_FULL);
			$insidelines = str_replace("{store_comments}",stripslashes($_POST[plistcomments]),$insidelines);
			} else {
			if ($_POST[partialship] == 1)
				$insidelines = str_replace("{customers_comments}",stripslashes($_POST[plistcomments]),PO_PDFP_CUST_COMMENT_PARTIAL);
			else
				$insidelines = str_replace("{customers_comments}",stripslashes($_POST[plistcomments]),PO_PDFP_CUST_COMMENT_FULL);
			$insidelines = str_replace("{store_comments}","",$insidelines);
			}
			$insidelines = str_replace("{shipping_method}",$shipping,$insidelines);    
			$pdf->addReference($insidelines);
			
			$cols=array( PACKING_LIST_MODEL_NUMBER    => 40,
           			     PACKING_LIST_PRODUCT_DESCRIPTION  => 120.9,
        			     PACKING_LIST_QUANTITY     => 25 );
			$pdf->addCols($cols);
			$cols=array( PACKING_LIST_MODEL_NUMBER    => "L",
       			      	     PACKING_LIST_PRODUCT_DESCRIPTION  => "L",
       			             PACKING_LIST_QUANTITY     => "C" );
			$pdf->addLineFormat($cols);
			$pdf->addLineFormat($cols);
			$y    = 89;
			}
			
                        $line = array( PACKING_LIST_MODEL_NUMBER    => $productmodellist[$i],
              		    PACKING_LIST_PRODUCT_DESCRIPTION  => $productnamelist[$i] . " " . $attributelist[$i],
             		    PACKING_LIST_QUANTITY     => $quantitylist[$i] );
		        $size = $pdf->addLine( $y, $line );
			$y   += $size + 2;  }
	
if ($_POST[plistcommentascustomer] != 'yes') {
     if ($_POST[partialship] == 1)
          $insidelines = str_replace("{customers_comments}","",PO_PARTIALSHIP_PACKINGLIST);
     else
          $insidelines = str_replace("{customers_comments}","",PO_FULLSHIP_PACKINGLIST);
     $insidelines = str_replace("{store_comments}",stripslashes($_POST[plistcomments]),$insidelines);
} else {
     if ($_POST[partialship] == 1)
          $insidelines = str_replace("{customers_comments}",stripslashes($_POST[plistcomments]),PO_PARTIALSHIP_PACKINGLIST);
     else
          $insidelines = str_replace("{customers_comments}",stripslashes($_POST[plistcomments]),PO_FULLSHIP_PACKINGLIST);
     $insidelines = str_replace("{store_comments}","",$insidelines);
}
$insidelines = str_replace("{shipping_method}",$shipping,$insidelines);    
$pdf->addNotes($insidelines);

      // $pdf->Output(PO_PACKINGLIST_FILENAME, "F");


                        $tresc_ostateczna='';
			$trescik='';
			$newzawartosc='';
                        $tresc_ostateczna_tf='';
			$trescik_tf='';
			$newzawartosc_tf='';
for($i=1; $i<=$numberofproducts; $i++)
			{
				$trescik=$tresc_robij1;
				$trescik=str_replace("{manufacturers_name}",$manufacturerlist[$i],$trescik);
				$trescik=str_replace("{products_name}",$productnamelist[$i],$trescik);
				$trescik=str_replace("{products_model}",$productmodellist[$i],$trescik);
				$trescik=str_replace("{final_price}","",$trescik);
				$trescik=str_replace("{products_quantity}",$quantitylist[$i],$trescik);
				$trescik=str_replace("{products_attributes}",$attributelist[$i]." ",$trescik);

				$tresc_ostateczna=$tresc_ostateczna.$trescik;
				$newzawartosc=$zawartosc.$tresc_ostateczna;

                                $trescik_tf=$tresc_robij1_tf;
				$trescik_tf=str_replace("{manufacturers_name}",$manufacturerlist[$i],$trescik_tf);
				$trescik_tf=str_replace("{products_name}",$productnamelist[$i],$trescik_tf);
				$trescik_tf=str_replace("{products_model}",$productmodellist[$i],$trescik_tf);
				$trescik_tf=str_replace("{final_price}","",$trescik_tf);
				$trescik_tf=str_replace("{products_quantity}",$quantitylist[$i],$trescik_tf);
				$trescik_tf=str_replace("{products_attributes}",$attributelist[$i]." ",$trescik_tf);

				$tresc_ostateczna_tf=$tresc_ostateczna_tf.$trescik_tf;
				$newzawartosc_tf=$zawartosc_tf.$tresc_ostateczna_tf;

			}
$dlaemaila= ($sub!='0') ? $sub : 0;
$query22=mysql_query("SELECT * FROM ".TABLE_SUBCONTRACTORS." WHERE subcontractors_id='$dlaemaila'")
			or die("Failed to connect database: 1");
			$subcontractor=mysql_fetch_assoc($query22);

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

$pdf->Output($usepdffilename, "F");
			$adresdo=$subcontractor['email_address'];
			/* if ($dlaemaila==0) $adresdo=PO_OWN_STOCK_EMAIL; */


			if ($subcontractor['email_title'] == '')
			     $tematk=PO_SUBJECT;
                        else
                             $tematk=$subcontractor['email_title'];
			$tematk=str_replace("{po_number}",$_POST[ponumber],$tematk);
			$tematk=str_replace("{contact_person}",$subcontractor['contact_person'],$tematk);
			$tematk=str_replace("{full_name}",$subcontractor['full_name'],$tematk);
		        $tematk=str_replace("{short_name}",$subcontractor['short_name'],$tematk);
                        $tematk=str_replace("{order_number}",$_POST[ponumber],$tematk);
                        $tematk=str_replace("{shipping_method}",$shipping,$tematk);
                       

			for($t=0; $t<=count($tracking_link); $t++)
			{
				$tracking_link_good=$tracking_link_good.$tracking_link[$t];
			}
                        for($t=0; $t<=count($tracking_link_tf); $t++)
			{
				$tracking_link_good_tf=$tracking_link_good_tf.$tracking_link_tf[$t];
			}
                        $tracking_link_good_tf = str_replace( chr(13).chr(10) , chr(13) , $tracking_link_good_tf);
                        $tracking_link_good_tf = str_replace( array(chr(13) , chr(10)) , chr(13).chr(10) , $tracking_link_good_tf);
			$newzawartosc=$newzawartosc.$tracking_link_good;
                        $newzawartosc=str_replace("{tracking_link}","","$newzawartosc");
                        $newzawartosc=str_replace("{customers_name}","","$newzawartosc");
			$newzawartosc=str_replace("{order_number}",$_POST[ponumber],"$newzawartosc");
			$newzawartosc=str_replace("{customers_address}",$billto,"$newzawartosc");
			$newzawartosc=str_replace("{customers_phone}","Not Available","$newzawartosc");
			$newzawartosc=str_replace("{customers_email}","","$newzawartosc");
			$newzawartosc=str_replace("{delivery_name}","","$newzawartosc");
			$newzawartosc=str_replace("{po_comments}",stripslashes($_POST[posubcomments]),"$newzawartosc");
                        $newzawartosc=str_replace("{customers_comments}","","$newzawartosc");
			$newzawartosc=str_replace("{delivery_company}","","$newzawartosc");
			$newzawartosc=str_replace("{delivery_address}",$shipto,"$newzawartosc");	
			$newzawartosc=str_replace("{billing_company}","","$newzawartosc");
			$newzawartosc=str_replace("{billing_name}","","$newzawartosc");
			$newzawartosc=str_replace("{billing_address}",$billto,"$newzawartosc");
			$newzawartosc=str_replace("{payment_method}","","$newzawartosc");
			$newzawartosc=str_replace("{date_purchased}",$invdate,"$newzawartosc");
			$newzawartosc=str_replace("{shipping_method}",$shipping,"$newzawartosc");
                  $newzawartosc = str_replace("{po_number}",$_POST[ponumber],$newzawartosc);
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
		  $newzawartosc = str_replace("{shipping_method}",$shipping,$newzawartosc);
                  $newzawartosc = str_replace("{customers_street_address}","",$newzawartosc);
                  $newzawartosc = str_replace("{customers_city}","",$newzawartosc);
                  $newzawartosc = str_replace("{customers_postal_code}","",$newzawartosc);
                  $newzawartosc = str_replace("{customers_state}","",$newzawartosc);
                  $newzawartosc = str_replace("{customers_state_code}","",$newzawartosc);
                  $newzawartosc = str_replace("{customers_country}","",$newzawartosc);
                  $newzawartosc = str_replace("{delivery_street_address}","",$newzawartosc);
                  $newzawartosc = str_replace("{delivery_city}","",$newzawartosc);
                  $newzawartosc = str_replace("{delivery_state}","",$newzawartosc);
                  $newzawartosc = str_replace("{delivery_state_code}","",$newzawartosc);
                  $newzawartosc = str_replace("{delivery_postal_code}","",$newzawartosc);
                  $newzawartosc = str_replace("{delivery_country}","",$newzawartosc);
                  $newzawartosc = str_replace("{billing_street_address}","",$newzawartosc);
                  $newzawartosc = str_replace("{billing_city}","",$newzawartosc);
                  $newzawartosc = str_replace("{billing_state}","",$newzawartosc);
                  $newzawartosc = str_replace("{billing_state_code}","",$newzawartosc);
                  $newzawartosc = str_replace("{billing_postal_code}","",$newzawartosc);
                  $newzawartosc = str_replace("{billing_country}","",$newzawartosc);
                  $newzawartosc = str_replace("{customer_suburb}","",$newzawartosc);
                  $newzawartosc = str_replace("{delivery_suburb}","",$newzawartosc);
                  $newzawartosc = str_replace("{billing_suburb}","",$newzawartosc);
                  $newzawartosc = str_replace("{customers_company}","",$newzawartosc);
                        $newzawartosc_tf=$newzawartosc_tf.$tracking_link_good_tf;
                        $newzawartosc_tf=str_replace("{tracking_link}","","$newzawartosc_tf");
                        $newzawartosc_tf=str_replace("{customers_name}","","$newzawartosc_tf");
			$newzawartosc_tf=str_replace("{order_number}",$_POST[ponumber],"$newzawartosc_tf");
			$newzawartosc_tf=str_replace("{customers_address}",$billto,"$newzawartosc_tf");
			$newzawartosc_tf=str_replace("{customers_phone}","Not Available","$newzawartosc_tf");
			$newzawartosc_tf=str_replace("{customers_email}","","$newzawartosc_tf");
			$newzawartosc_tf=str_replace("{delivery_name}","","$newzawartosc_tf");
			$newzawartosc_tf=str_replace("{po_comments}",stripslashes($_POST[posubcomments]),"$newzawartosc_tf");
                        $newzawartosc_tf=str_replace("{customers_comments}","","$newzawartosc_tf");
			$newzawartosc_tf=str_replace("{delivery_company}","","$newzawartosc_tf");
			$newzawartosc_tf=str_replace("{delivery_address}",$shipto,"$newzawartosc_tf");	
			$newzawartosc_tf=str_replace("{billing_company}","","$newzawartosc_tf");
			$newzawartosc_tf=str_replace("{billing_name}","","$newzawartosc_tf");
			$newzawartosc_tf=str_replace("{billing_address}",$billto,"$newzawartosc_tf");
			$newzawartosc_tf=str_replace("{payment_method}","","$newzawartosc_tf");
			$newzawartosc_tf=str_replace("{date_purchased}",$invdate,"$newzawartosc_tf");
			$newzawartosc_tf=str_replace("{shipping_method}",$shipping,"$newzawartosc_tf");
                  $newzawartosc_tf = str_replace("{po_number}",$_POST[ponumber],$newzawartosc_tf);
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
		  $newzawartosc_tf = str_replace("{shipping_method}",$shipping,$newzawartosc_tf);
                  $newzawartosc_tf = str_replace("{customers_street_address}","",$newzawartosc_tf);
                  $newzawartosc_tf = str_replace("{customers_city}","",$newzawartosc_tf);
                  $newzawartosc_tf = str_replace("{customers_postal_code}","",$newzawartosc_tf);
                  $newzawartosc_tf = str_replace("{customers_state}","",$newzawartosc_tf);
                  $newzawartosc_tf = str_replace("{customers_state_code}","",$newzawartosc_tf);
                  $newzawartosc_tf = str_replace("{customers_country}","",$newzawartosc_tf);
                  $newzawartosc_tf = str_replace("{delivery_street_address}","",$newzawartosc_tf);
                  $newzawartosc_tf = str_replace("{delivery_city}","",$newzawartosc_tf);
                  $newzawartosc_tf = str_replace("{delivery_state}","",$newzawartosc_tf);
                  $newzawartosc_tf = str_replace("{delivery_state_code}","",$newzawartosc_tf);
                  $newzawartosc_tf = str_replace("{delivery_postal_code}","",$newzawartosc_tf);
                  $newzawartosc_tf = str_replace("{delivery_country}","",$newzawartosc_tf);
                  $newzawartosc_tf = str_replace("{billing_street_address}","",$newzawartosc_tf);
                  $newzawartosc_tf = str_replace("{billing_city}","",$newzawartosc_tf);
                  $newzawartosc_tf = str_replace("{billing_state}","",$newzawartosc_tf);
                  $newzawartosc_tf = str_replace("{billing_state_code}","",$newzawartosc_tf);
                  $newzawartosc_tf = str_replace("{billing_postal_code}","",$newzawartosc_tf);
                  $newzawartosc_tf = str_replace("{billing_country}","",$newzawartosc_tf);
                  $newzawartosc_tf = str_replace("{customer_suburb}","",$newzawartosc_tf);
                  $newzawartosc_tf = str_replace("{delivery_suburb}","",$newzawartosc_tf);
                  $newzawartosc_tf = str_replace("{billing_suburb}","",$newzawartosc_tf);
                  $newzawartosc_tf = str_replace("{customers_company}","",$newzawartosc_tf);


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
                         $tematk=str_replace($replacing[$r],$replacing[$replacewithnumber],$tematk); break;
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
  if (in_array($subcontractor['subcontractors_id'],$sublistarr) || in_array('U',$sublistarr)) {
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
  if (in_array($subcontractor['subcontractors_id'],$sublistarr) || in_array('U',$sublistarr)) {
    $newzawartosc = removeBetweenDelimeters($newzawartosc, '{-'.$sublist.'}', '{/-'.$sublist.'}');
  } else {
    $newzawartosc = str_replace('{-'.$sublist.'}', '', $newzawartosc);
    $newzawartosc = str_replace('{/-'.$sublist.'}', '', $newzawartosc);
    
  }
}
if (stripos($newzawartosc,'{-') !== false)
  $newzawartosc = "***  ERROR - CHECK { - } TAGS FOR ERRORS  ***  " . $newzawartosc;

//  Check Additions For Subcontractors on Text Attachment
while (areDelimetersThere($newzawartosc_tf, '{+', '}', '{/+', '}')) {
  $sublist = extractBetweenDelimeters($newzawartosc_tf, '{+', '}');
  $sublistarr = explode(',', $sublist);
  if (in_array($subcontractor['subcontractors_id'],$sublistarr) || in_array('U',$sublistarr)) {
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
  if (in_array($subcontractor['subcontractors_id'],$sublistarr) || in_array('U',$sublistarr)) {
    $newzawartosc_tf = removeBetweenDelimeters($newzawartosc_tf, '{-'.$sublist.'}', '{/-'.$sublist.'}');
  } else {
    $newzawartosc_tf = str_replace('{-'.$sublist.'}', '', $newzawartosc_tf);
    $newzawartosc_tf = str_replace('{/-'.$sublist.'}', '', $newzawartosc_tf);
    
  }
}
if (stripos($newzawartosc_tf,'{-') !== false)
  $newzawartosc_tf = "***  ERROR - CHECK { - } TAGS FOR ERRORS  ***  " . $newzawartosc_tf;
/*
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
*/
                  $html_msg['EMAIL_MESSAGE_HTML'] = str_replace('
','<br />',$newzawartosc);

if (PO_SEND_PACKING_LISTS == 4) {
   $attachthepdf=$subcontractor[sendpdf];
} else {
   $attachthepdf=$_POST[includepackinglistoption];
}
if (PO_SEND_TEXT_FILE == 4) {
   $attachthetf=$subcontractor[sendtf];
} else {
   $attachthetf=$_POST[includetextfileoption];
}
if ($attachthepdf == 'yes' && $attachthetf == 'yes') {
  $filestoattach = array (
    "0"  => array("file" => $usepdffilename, "mime_type" => "application/pdf"),
    "1"  => array("name" => $usetffilename, "raw_data" => $newzawartosc_tf, "mime_type" => $usetfmimetype)   
  );
  zen_mail($adresdo,$adresdo,$tematk,$newzawartosc,PO_FROM_EMAIL_NAME,PO_FROM_EMAIL_ADDRESS,$html_msg, NULL, $filestoattach);
}
if ($attachthepdf == 'yes' && $attachthetf != 'yes') {
  $filestoattach = array (
    "0"  => array("file" => $usepdffilename, "mime_type" => "application/pdf") 
  );
  zen_mail($adresdo,$adresdo,$tematk,$newzawartosc,PO_FROM_EMAIL_NAME,PO_FROM_EMAIL_ADDRESS,$html_msg, NULL, $filestoattach);
}
if ($attachthepdf != 'yes' && $attachthetf == 'yes') {
  $filestoattach = array (
    "0"  => array("name" => $usetffilename, "raw_data" => $newzawartosc_tf, "mime_type" => $usetfmimetype) 
  );
  zen_mail($adresdo,$adresdo,$tematk,$newzawartosc,PO_FROM_EMAIL_NAME,PO_FROM_EMAIL_ADDRESS,$html_msg, NULL, $filestoattach);
}
if ($attachthepdf != 'yes' && $attachthetf != 'yes') {
  zen_mail($adresdo,$adresdo,$tematk,$newzawartosc,PO_FROM_EMAIL_NAME,PO_FROM_EMAIL_ADDRESS,$html_msg, NULL);
}   
$messageStack = new messageStack();
  $messageStack->add('Purchase Order '. $_POST[ponumber] .' Emailed To: ' . $adresdo, 'success'); 
if ($messageStack->size > 0) echo $messageStack->output();
/* <table border="0" width="100%" cellspacing="0" cellpadding="0">
          <tr>
            <td class="pageHeading"><?php echo PO_SENT_MESSAGE; ?></td>
          </tr>
        </table><br /><br /> */
 }



/* ***   Begin Main Page *** */
for ($i=0; $i<=$numberofproducts; $i++) {

$attributelist[$i] = htmlspecialchars($attributelist[$i],ENT_QUOTES);
$manufacturerlist[$i] = htmlspecialchars($manufacturerlist[$i],ENT_QUOTES);
$productnamelist[$i] = htmlspecialchars($productnamelist[$i],ENT_QUOTES);
$productmodellist[$i] = htmlspecialchars($productmodellist[$i],ENT_QUOTES); }
$shippingoption = stripslashes($_POST[shippingoption]);
$shippingoption = htmlspecialchars($shippingoption,ENT_QUOTES);
$ponumber = stripslashes($_POST[ponumber]);
$ponumber = htmlspecialchars($ponumber,ENT_QUOTES);
$shippingaddress = stripslashes($_POST[shippingaddress]);
$shippingaddress = htmlspecialchars($shippingaddress,ENT_QUOTES);
$billingaddress = stripslashes($_POST[billingaddress]);
$billingaddress = htmlspecialchars($billingaddress,ENT_QUOTES);
$posubcomments = stripslashes($_POST[posubcomments]);
$posubcomments = htmlspecialchars($posubcomments,ENT_QUOTES);
$plistcomments = stripslashes($_POST[plistcomments]);
$plistcomments = htmlspecialchars($plistcomments,ENT_QUOTES);
function sub($cont)
			{

			$query2=mysql_query("SELECT  subcontractors_id,short_name FROM ".TABLE_SUBCONTRACTORS." ORDER BY short_name")
			or die('Failed to connect database: ');

			echo "<select name='sub'>";
			while($row22=mysql_fetch_array($query2, MYSQL_NUM))
			{

			echo "<option value='$row22[0]'";

			if ($cont == NULL) {

                           if($row22[0] == 0)
			   {
			     echo "selected";
			   }
                        } else {
                           if($row22[0] == $cont)
			   {
			     echo "selected";
			   } }
			     echo ">$row22[1]</option>";
			   }
			echo "</select>";
			}

if ($_POST[step] == 5) {
    $numberofproducts++;
    $productlistid[$numberofproducts] = $_POST[add_product_products_id];
$result7a=mysql_query("SELECT p.products_model, o.products_name FROM ".TABLE_PRODUCTS." as p, ".TABLE_PRODUCTS_DESCRIPTION." as o WHERE p.products_id=o.products_id and p.products_id='$productlistid[$numberofproducts]'")
				or die("Failed to connect database: ");
				while($row7a=mysql_fetch_array($result7a, MYSQL_NUM)) {
                                        $productmodellist[$numberofproducts] = stripslashes($row7a[0]);                         
					$productnamelist[$numberofproducts] = stripslashes($row7a[1]);
$productmodellist[$numberofproducts] = htmlspecialchars($productmodellist[$numberofproducts],ENT_QUOTES);
$productnamelist[$numberofproducts] = htmlspecialchars($productnamelist[$numberofproducts],ENT_QUOTES);
}
    $manufacturerlist[$numberofproducts]=zen_get_products_manufacturers_name($productlistid[$numberofproducts]);
    $manufacturerlist[$numberofproducts]=stripslashes($manufacturerlist[$numberofproducts]);
    $manufacturerlist[$numberofproducts] = htmlspecialchars($manufacturerlist[$numberofproducts],ENT_QUOTES);
    $quantitylist[$numberofproducts] = stripslashes($_POST[add_product_quantity]);
    $quantitylist[$numberofproducts] = htmlspecialchars($quantitylist[$numberofproducts],ENT_QUOTES);
$attributelist[$numberofproducts] = '';
    if ($_POST[optionstoadd] != NULL) {
      for ($i=1; $i<=$_POST[optionstoadd]; $i++) {
        $sendoptionon = "add_product_options".$i;
	$result9a=mysql_query("SELECT products_options_values_name FROM ".TABLE_PRODUCTS_OPTIONS_VALUES." WHERE products_options_values_id='$_POST[$sendoptionon]' ")
				or die("Failed to connect database: ");
				while($row9a=mysql_fetch_array($result9a, MYSQL_NUM)) {
					$attributes=$row9a[0]; }
$result8a=mysql_query("SELECT products_options_id FROM ".TABLE_PRODUCTS_OPTIONS_VALUES_TO_PRODUCTS_OPTIONS." WHERE  products_options_values_id='$_POST[$sendoptionon]'")
			or die('Failed to connect database: 8');
while($row8a=mysql_fetch_array($result8a, MYSQL_NUM)) {
					$attributestypenumber=$row8a[0]; }
$result8c=mysql_query("SELECT products_options_name FROM ".TABLE_PRODUCTS_OPTIONS." WHERE products_options_id='$attributestypenumber'")
			or die('Failed to connect database: 8');
while($row8c=mysql_fetch_array($result8c, MYSQL_NUM)) {
					$attributestype=$row8c[0]; }
				
    if ($i == 1)
       $attributelist[$numberofproducts] = $attributestype.": ".$attributes;
    else
       $attributelist[$numberofproducts] .= " " . $attributestype.": ".$attributes;
    
}
$attributelist[$numberofproducts]=stripslashes($attributelist[$numberofproducts]);
$attributelist[$numberofproducts]=htmlspecialchars($attributelist[$numberofproducts],ENT_QUOTES);
}
} 
?>

   <table border="0" width="100%" cellspacing="0" cellpadding="0">
          <tr>
            <td class="pageHeading"><?php echo BOX_CUSTOMERS_SEND_POS_NC; ?><br><br></td>
          </tr><tr>  <td valign="top"><?php echo REFRESH_WARNING; ?></td></tr>
        </table><br /><br />

<form name='pos' method='post' action='send_pos_nc.php'>
<table border="0" width="100%" align="center" cellspacing="0" cellpadding="0">
<tr>
<td width="20%" align="center"><?php echo TABLE_SEND_PO_TO; ?></td>
<td width="20%" align="center"><?php echo TABLE_PO_ORDER_NUMBER; ?></td>
<td width="60%" align="center"><?php echo TABLE_PO_SHIPPING_CHOICE." ".PO_CHANGE_SHIPPING_TO; ?></td>
</tr><tr>
<td width="20%" align="center"><?php sub($sub); ?></td><?php
echo "<td width='20%' align='center'><input type='text' name='ponumber' value='$ponumber' size='25%' />"; ?> </td>
<td width="60%" align="center"><?php echo "<input type='text' name='shippingoption' value='$shippingoption' size='100%'/>"; ?> </td>
</tr></table><br /><br />
<table border="0" width="100%" align="center" cellspacing="0" cellpadding="0">
<tr>
<td width="50%" align="center"><?php echo TABLE_PO_BILLING_ADDRESS; ?></td>
<td width="50%" align="center"><?php echo TABLE_PO_SHIPPING_ADDRESS; ?></td>
</tr><tr>
<td width="50%" align="center"><textarea rows="6" name="billingaddress"><?php echo $billingaddress; ?></textarea></td>
<td width="50%" align="center"><textarea rows="6" name="shippingaddress"><?php echo $shippingaddress; ?></textarea></td>
</tr></table><br /><br />
<table border="0" width="100%" align="center" cellspacing="0" cellpadding="0">
<tr>
<td width="10%" align="center"><?php echo TABLE_PO_PRODUCTS_QUANTITY; ?></td>
<td width="10%" align="center"><?php echo TABLE_PO_PRODUCTS_MODEL_NUMBER; ?></td>
<td width="20%" align="center"><?php echo TABLE_PO_PRODUCTS_MANUFACTURER; ?></td>
<td width="40%" align="center"><?php echo TABLE_PO_PRODUCTS_DESCRIPTION; ?></td>
<td width="10%" align="center"><?php echo TABLE_PO_PRODUCTS_OPTIONS; ?></td>
</tr><tr> <?php
for ($i=1; $i<=$numberofproducts; $i++) {
   echo "<td width='10%' align='center'><input type='text' name='quantitylist$i' value='$quantitylist[$i]' size='10%'></td>";
   echo "<td width='10%' align='center'><input type='text' name='productmodellist$i' value='$productmodellist[$i]' size='20%'></td>";
   echo "<td width='20%' align='center'><input type='text' name='manufacturerlist$i' value='$manufacturerlist[$i]' size='20%'></td>";
   echo "<td width='40%' align='center'><input type='text' name='productnamelist$i' value='$productnamelist[$i]' size='75%'></td>";
   echo "<td width='10%' align='center'><input type='text' name='attributelist$i' value='$attributelist[$i]' size='20%'></td></tr>"; 
   echo "<input type='hidden' name='productlistid$i' value='$productlistid[$i]'>"; } ?>
</tr></table><br /><br />
<center>
<?php if ($_POST[step] == 5) {
echo "<input type='hidden' name='step' value='2'>"; ?>
<input type="button" name="postonc" value="Add Another Product" ONCLICK="javascript:document.pos.submit();">
  <?php } ?><br /><br />
<?php echo TABLE_COMMENTS_FOR_POS; ?>:&nbsp;<?php echo "<input type='text' name='posubcomments' value='$posubcomments' size='110' />"; ?> <br /><br />
<?php if (PO_SEND_PACKING_LISTS != 0) { ?>
<?php echo TABLE_COMMENTS_FOR_PACKING_LISTS; ?>:&nbsp;<?php echo "<input type='text' name='plistcomments' value='$plistcomments' size='90' maxlength='90' />"; ?>&nbsp;&nbsp;<input type="checkbox" name="plistcommentascustomer" value="yes" <?php if ($plistcommentascustomer == "yes")  echo "CHECKED"; ?>><?php echo TABLE_PO_COMMENTS_AS_CUSTOMER; ?><br /><br /><?php } ?>
<input type="hidden" name="numberofproducts" value="<?php echo $numberofproducts ?>">
<?php if (PO_SEND_PACKING_LISTS == 0) { ?>
<input type="hidden" name="includepackinglistoption" value="no"> <?php } ?>
<?php if (PO_SEND_PACKING_LISTS == 1) { ?>
<input type="hidden" name="includepackinglistoption" value="yes"> <?php } ?>
<?php if (PO_SEND_PACKING_LISTS == 2 || PO_SEND_PACKING_LISTS == 3) { ?>
<?php echo TABLE_INCLUDE_PACKINGLIST_OPTION; ?><input type="checkbox" name="includepackinglistoption" value="yes" <?php if ($includeplistoption == "yes")  echo "CHECKED"; ?> >&nbsp;&nbsp;&nbsp;&nbsp; <?php } ?>
<?php if (PO_SEND_PACKING_LISTS == 4) { ?>
<input type="hidden" name="includepackinglistoption" value="sc"> <?php } ?>
<?php if (PO_SEND_TEXT_FILE == 0) { ?>
<input type="hidden" name="includetextfileoption" value="no"> <?php } ?>
<?php if (PO_SEND_TEXT_FILE == 1) { ?>
<input type="hidden" name="includetextfileoption" value="yes"> <?php } ?>
<?php if (PO_SEND_TEXT_FILE == 2 || PO_SEND_TEXT_FILE == 3) { ?>
<?php echo TABLE_INCLUDE_TEXTFILE_OPTION; ?><input type="checkbox" name="includetextfileoption" value="yes" <?php if ($includetfoption == "yes")  echo "CHECKED"; ?> >&nbsp;&nbsp;&nbsp;&nbsp; <?php } ?>
<?php if (PO_SEND_TEXT_FILE == 4) { ?>
<input type="hidden" name="includetextfileoption" value="sc"> <?php } ?>
<?php if (PO_SEND_PACKING_LISTS != 0) { ?>
<?php echo TABLE_PARTIAL_SHIPMENT_OPTION; ?>
<?php if ($partialship)       
echo "<input type='checkbox' name='partialship' value='1' CHECKED />";
else
echo "<input type='checkbox' name='partialship' value='1' />";
 ?>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?php } ?>
<input type="image" src="includes/languages/english/images/buttons/button_send.gif" name='postonc' value='yes' ONCLICK="javascript:document.pos.submit();">
<?php if ($POST[step] == 5)
         echo "</form>";  ?>
</center>
<?php 
if (($postonc == "add_product" || $postonc == "") && ($_POST[step] != "5"))
{ ?>
<table border="0" width="100%" cellspacing="0" cellpadding="0">
          <tr>
            <td class="pageHeading"><?php echo ADD_A_PRODUCT_HEADER; ?></td>
          </tr>
        </table><br /><br />  <?php
	// ############################################################################
	//   Get List of All Products
	// ############################################################################

		//$result = zen_db_query("SELECT products_name, p.products_id, x.categories_name, ptc.categories_id FROM " . TABLE_PRODUCTS . " p LEFT JOIN " . TABLE_PRODUCTS_DESCRIPTION . " pd ON pd.products_id=p.products_id LEFT JOIN " . TABLE_PRODUCTS_TO_CATEGORIES . " ptc ON ptc.products_id=p.products_id LEFT JOIN " . TABLE_CATEGORIES_DESCRIPTION . " cd ON cd.categories_id=ptc.categories_id LEFT JOIN " . TABLE_CATEGORIES_DESCRIPTION . " x ON x.categories_id=ptc.categories_id ORDER BY categories_id");
		$result = $db -> Execute("SELECT products_name, p.products_id, categories_name, ptc.categories_id FROM " . TABLE_PRODUCTS . " p LEFT JOIN " . TABLE_PRODUCTS_DESCRIPTION . " pd ON pd.products_id=p.products_id LEFT JOIN " . TABLE_PRODUCTS_TO_CATEGORIES . " ptc ON ptc.products_id=p.products_id LEFT JOIN " . TABLE_CATEGORIES_DESCRIPTION . " cd ON cd.categories_id=ptc.categories_id ORDER BY categories_name");
		#hile($row = zen_db_fetch_array($result)) 		{
      while (!$result -> EOF){
 		   extract($result->fields,EXTR_PREFIX_ALL,"db");
			$ProductList[$db_categories_id][$db_products_id] = $db_products_name;
			$CategoryList[$db_categories_id] = $db_categories_name;
			$LastCategory = $db_categories_name;
         $result -> MoveNext();
		}

		// ksort($ProductList);

		$LastOptionTag = "";
		$ProductSelectOptions = "<option value='0'>Don't Add New Product" . $LastOptionTag . "\n";
		$ProductSelectOptions .= "<option value='0'>&nbsp;" . $LastOptionTag . "\n";
		foreach($ProductList as $Category => $Products)
		{
			$ProductSelectOptions .= "<option value='0'>$Category" . $LastOptionTag . "\n";
			$ProductSelectOptions .= "<option value='0'>---------------------------" . $LastOptionTag . "\n";
			asort($Products);
			foreach($Products as $Product_ID => $Product_Name)
			{
				$ProductSelectOptions .= "<option value='$Product_ID'> &nbsp; $Product_Name" . $LastOptionTag . "\n";
			}

			if($Category != $LastCategory)
			{
				$ProductSelectOptions .= "<option value='0'>&nbsp;" . $LastOptionTag . "\n";
				$ProductSelectOptions .= "<option value='0'>&nbsp;" . $LastOptionTag . "\n";
			}
		}


	// ############################################################################
	//   Add Products Steps
	// ############################################################################

		echo "<table border='0' align'center'>\n";

		// Set Defaults
			if(!IsSet($add_product_categories_id))
			$add_product_categories_id = .5;

			if(!IsSet($add_product_products_id))
			$add_product_products_id = 0;

		// Step 1: Choose Category
if ($add_product_categories_id == .5) {
$categoriesarr = zen_get_category_tree();
$catcount = count($categoriesarr);
$texttempcat1 = $categoriesarr[0][text];
$idtempcat1 = $categoriesarr[0][id];
$catcount++;
for ($i=1; $i<$catcount; $i++) {
   $texttempcat2 = $categoriesarr[$i][text];
   $idtempcat2 = $categoriesarr[$i][id];
   $categoriesarr[$i][id] = $idtempcat1;
   $categoriesarr[$i][text] = $texttempcat1;
   $texttempcat1 = $texttempcat2;
   $idtempcat1 = $idtempcat2;
}


$categoriesarr[0][text] = "Choose Category";
$categoriesarr[0][id] = .5;

			
                        $categoryselectoutput = zen_draw_pull_down_menu('add_product_categories_id', $categoriesarr, $current_category_id, 'onChange="this.form.submit();"');
                        $categoryselectoutput = str_replace('<option value="0" SELECTED>','<option value="0">',$categoryselectoutput);
                        $categoryselectoutput = str_replace('<option value=".5">','<option value=".5" SELECTED>',$categoryselectoutput);
} else {
                        $categoryselectoutput = zen_draw_pull_down_menu('add_product_categories_id', zen_get_category_tree(), $current_category_id, 'onChange="this.form.submit();"');
}
			echo "<tr class=\"dataTableRow\">\n";
			echo "<td class='dataTableContent' align='right'><b>STEP 1:</b></td><td class='dataTableContent' valign='top'>";
			echo ' ' . $categoryselectoutput;
			echo "<input type='hidden' name='step' value='2'>";
			echo "</td>\n";
			echo "</form></tr>\n";
			echo "<tr><td colspan='3'>&nbsp;</td></tr>\n";

		// Step 2: Choose Product
		if(($step > 1) && ($add_product_categories_id != .5))
		{
			echo "<tr class=\"dataTableRow\"><form action='$PHP_SELF' method='POST'>\n";
			echo "<td class='dataTableContent' align='right'><b>STEP 2:</b></td><td class='dataTableContent' valign='top'><select name=\"add_product_products_id\" onChange=\"this.form.submit();\">";
			$ProductOptions = "<option value='0'>" .  ADDPRODUCT_TEXT_SELECT_PRODUCT . "\n";
			asort($ProductList[$add_product_categories_id]);
			foreach($ProductList[$add_product_categories_id] as $ProductID => $ProductName)
			{
			$ProductOptions .= "<option value='$ProductID'> $ProductName\n";
			}
			$ProductOptions = str_replace("value='$add_product_products_id'","value='$add_product_products_id' selected", $ProductOptions);
			echo $ProductOptions;
			echo "</select></td>\n";
			echo "<input type='hidden' name='add_product_categories_id' value='$add_product_categories_id'>";
echo "<input type='hidden' name='ponumber' value='$ponumber'>";
echo "<input type='hidden' name='shippingoption' value='$shippingoption'>";
echo "<input type='hidden' name='shippingaddress' value='$shippingaddress'>";
echo "<input type='hidden' name='billingaddress' value='$billingaddress'>";
echo "<input type='hidden' name='sub' value='$sub'>";
echo "<input type='hidden' name='posubcomments' value='$posubcomments'>";
echo "<input type='hidden' name='plistcomments' value='$plistcomments'>";
echo "<input type='hidden' name='partialship' value='$partialship'>";
			echo "<input type='hidden' name='step' value='3'>";
                        echo "<input type='hidden' name='numberofproducts' value='$numberofproducts'>";
echo "<input type='hidden' name='includepackinglistoption' value='$includeplistoption'>";
echo "<input type='hidden' name='includetextfileoption' value='$includetfoption'>";
echo "<input type='hidden' name='plistcommentascustomer' value='$plistcommentascustomer'>";
                           for ($i=0; $i<=$numberofproducts; $i++) {
	                    echo "<input type='hidden' name='productlistid$i' value='$productlistid[$i]'>";
                            echo "<input type='hidden' name='quantitylist$i' value='$quantitylist[$i]'>";
                            echo "<input type='hidden' name='manufacturerlist$i' value='$manufacturerlist[$i]'>";
                            echo "<input type='hidden' name='productmodellist$i' value='$productmodellist[$i]'>";
                            echo "<input type='hidden' name='productnamelist$i' value='$productnamelist[$i]'>";
                            echo "<input type='hidden' name='attributelist$i' value='$attributelist[$i]'>"; }
			echo "</form></tr>\n";
			echo "<tr><td colspan='3'>&nbsp;</td></tr>\n";
		}

		// Step 3: Choose Options
		if(($step > 2) && ($add_product_products_id > 0))
		{
			// Get Options for Products
			$result = $db -> Execute("SELECT * FROM " . TABLE_PRODUCTS_ATTRIBUTES . " pa LEFT JOIN " . TABLE_PRODUCTS_OPTIONS . " po ON po.products_options_id=pa.options_id LEFT JOIN " . TABLE_PRODUCTS_OPTIONS_VALUES . " pov ON pov.products_options_values_id=pa.options_values_id WHERE products_id='$add_product_products_id'");

			// Skip to Step 4 if no Options
			if($result->RecordCount() == 0)
			{
				echo "<tr class=\"dataTableRow\">\n";
				echo "<td class='dataTableContent' align='right'><b>STEP 3:</b></td><td class='dataTableContent' valign='top' colspan='2'><i>No Options - Skipped...</i></td>";
				echo "</tr>\n";
				$step = 4;
			}
			else
			{
	#			while($row = zen_db_fetch_array($result))  {
            while (!$result -> EOF){
 					extract($result->fields,EXTR_PREFIX_ALL,"db");
					$Options[$db_products_options_id] = $db_products_options_name;
					$ProductOptionValues[$db_products_options_id][$db_products_options_values_id] = $db_products_options_values_name;
               $result -> MoveNext();
				}

				echo "<tr class=\"dataTableRow\"><form action='$PHP_SELF' method='POST'>\n";
				echo "<td class='dataTableContent' align='right'><b>STEP 3:</b></td><td class='dataTableContent' valign='top'>";
                                $optionstoadd=0;
				foreach($ProductOptionValues as $OptionID => $OptionValues)
				{       $optionstoadd++;
                                        $sendoptionon = "add_product_options".$optionstoadd;                    
	   		       		$OptionOption = "<b>" . $Options[$OptionID] . "</b> - <select name='$sendoptionon'>";
					foreach($OptionValues as $OptionValueID => $OptionValueName)
					{
					$OptionOption .= "<option value='$OptionValueID'> $OptionValueName\n";
					}
					$OptionOption .= "</select><br>\n";

					if(IsSet($_POST[$sendoptionon]))
					$OptionOption = str_replace("value='" . $_POST[$sendoptionon] . "'","value='" . $_POST[$sendoptionon] . "' selected",$OptionOption);

					echo $OptionOption;
				}
				echo "<input type='hidden' name='optionstoadd' value='$optionstoadd'></td>";
				echo "<td class='dataTableContent' align='center'><input type='submit' value='" . ADDPRODUCT_TEXT_OPTIONS_CONFIRM . "'>";
				echo "<input type='hidden' name='add_product_categories_id' value='$add_product_categories_id'>";
				echo "<input type='hidden' name='add_product_products_id' value='$add_product_products_id'>";
                                echo "<input type='hidden' name='numberofproducts' value='$numberofproducts'>";
echo "<input type='hidden' name='ponumber' value='$ponumber'>";
echo "<input type='hidden' name='shippingoption' value='$shippingoption'>";
echo "<input type='hidden' name='shippingaddress' value='$shippingaddress'>";
echo "<input type='hidden' name='billingaddress' value='$billingaddress'>";
echo "<input type='hidden' name='sub' value='$sub'>";
echo "<input type='hidden' name='posubcomments' value='$posubcomments'>";
echo "<input type='hidden' name='plistcomments' value='$plistcomments'>";
echo "<input type='hidden' name='partialship' value='$partialship'>";
echo "<input type='hidden' name='includepackinglistoption' value='$includeplistoption'>";
echo "<input type='hidden' name='includetextfileoption' value='$includetfoption'>";
echo "<input type='hidden' name='plistcommentascustomer' value='$plistcommentascustomer'>";
                                 for ($i=0; $i<=$numberofproducts; $i++) {
	                            echo "<input type='hidden' name='productlistid$i' value='$productlistid[$i]'>";
                                    echo "<input type='hidden' name='quantitylist$i' value='$quantitylist[$i]'>";
                                    echo "<input type='hidden' name='manufacturerlist$i' value='$manufacturerlist[$i]'>";
                                    echo "<input type='hidden' name='productmodellist$i' value='$productmodellist[$i]'>";
                                    echo "<input type='hidden' name='productnamelist$i' value='$productnamelist[$i]'>";
                                    echo "<input type='hidden' name='attributelist$i' value='$attributelist[$i]'>"; }
				echo "<input type='hidden' name='step' value='4'>";
				echo "</td>\n";
				echo "</form></tr>\n";
			}

			echo "<tr><td colspan='3'>&nbsp;</td></tr>\n";
		}

		// Step 4: Confirm
		if($step > 3)
		{
			echo "<tr class=\"dataTableRow\"><form action='$PHP_SELF' method='POST'>\n";
			echo "<td class='dataTableContent' align='right'><b>STEP 4:</b></td>";
			echo "<td class='dataTableContent' valign='top'><input name='add_product_quantity' size='2' value='1'>" . ADDPRODUCT_TEXT_CONFIRM_QUANTITY . "</td>";
			echo "<td class='dataTableContent' align='center'><input type='submit' value='" . ADDPRODUCT_TEXT_CONFIRM_ADDNOW . "'>";

			if($_POST[optionstoadd] != NULL)
			{
                                for ($i=1; $i<=$_POST[optionstoadd]; $i++) {
                                $sendoptionon = "add_product_options".$i;
                                echo "<input type='hidden' name='$sendoptionon' value='$_POST[$sendoptionon]'>"; }
			}
                        echo "<input type='hidden' name='optionstoadd' value='$_POST[optionstoadd]'>";
			echo "<input type='hidden' name='add_product_categories_id' value='$add_product_categories_id'>";
			echo "<input type='hidden' name='add_product_products_id' value='$add_product_products_id'>";
			echo "<input type='hidden' name='step' value='5'>";
                        echo "<input type='hidden' name='numberofproducts' value='$numberofproducts'>";
echo "<input type='hidden' name='ponumber' value='$ponumber'>";
echo "<input type='hidden' name='shippingoption' value='$shippingoption'>";
echo "<input type='hidden' name='shippingaddress' value='$shippingaddress'>";
echo "<input type='hidden' name='billingaddress' value='$billingaddress'>";
echo "<input type='hidden' name='sub' value='$sub'>";
echo "<input type='hidden' name='posubcomments' value='$posubcomments'>";
echo "<input type='hidden' name='plistcomments' value='$plistcomments'>";
echo "<input type='hidden' name='partialship' value='$partialship'>";
echo "<input type='hidden' name='includepackinglistoption' value='$includeplistoption'>";
echo "<input type='hidden' name='includetextfileoption' value='$includetfoption'>";
echo "<input type='hidden' name='plistcommentascustomer' value='$plistcommentascustomer'>";
                          for ($i=0; $i<=$numberofproducts; $i++) {
	                            echo "<input type='hidden' name='productlistid$i' value='$productlistid[$i]'>";
                                    echo "<input type='hidden' name='quantitylist$i' value='$quantitylist[$i]'>";
                                    echo "<input type='hidden' name='manufacturerlist$i' value='$manufacturerlist[$i]'>";
                                    echo "<input type='hidden' name='productmodellist$i' value='$productmodellist[$i]'>";
                                    echo "<input type='hidden' name='productnamelist$i' value='$productnamelist[$i]'>";
                                    echo "<input type='hidden' name='attributelist$i' value='$attributelist[$i]'>"; }
			echo "</td>\n";
			echo "</form></tr>\n";
		}

		echo "</table></td></tr>\n";
}  ?>

<?php require(DIR_WS_INCLUDES . 'footer.php'); ?>
<!-- footer_eof //-->
<br>
</body>
</html>
<?php require(DIR_WS_INCLUDES . 'application_bottom.php'); ?>