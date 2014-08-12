<?php
require('posecuritycode.php');
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
//  IMPORTANT!  Change "yourcodehere" below to the code you want to use!

if ($_GET[code] == "yourcodehere") {
define('PACKING_LIST_FIRST_WORD', 'Packing');
define('PACKING_LIST_SECOND_WORD', 'List');
define('PACKING_LIST_MODEL_NUMBER', 'MODEL NUMBER');
define('PACKING_LIST_PRODUCT_DESCRIPTION', 'PRODUCT DESCRIPTION');
define('PACKING_LIST_QUANTITY', 'QUANTITY');
define('SHIPPING_OPTION', 'Shipping Option');
define('TRACKING_FEATURE_NOT_AVAILABLE_GOOGLE_CHECKOUT', 'Tracking link not available for this order.  Please send an
email with tracking number instead.  Thank you!');

require('includes/application_top.php');
define('TABLE_SUBCONTRACTORS', DB_PREFIX . 'subcontractors');
$currencies = new currencies();
define('FPDF_FONTPATH',PO_KNOW_ADIR.'/fpdf/font/');
require(PO_KNOW_ADIR.'/pdfpack.php');

//Update Products Default Subcontractor
if (PO_MANUFACTURER_SC == 1) {
        $query_find_sc=mysql_query("SELECT  default_subcontractor, manufacturers_id FROM ".TABLE_MANUFACTURERS);
	while($row_find_sc=mysql_fetch_array($query_find_sc, MYSQL_NUM)) {
	       $result_assign=mysql_query("UPDATE ".TABLE_PRODUCTS." SET default_subcontractor='$row_find_sc[0]' WHERE  manufacturers_id ='$row_find_sc[1]'");
	}
}

//load email templates
@ $wp1 = fopen("email/email_header.txt", 'r');
@ $wp2 = fopen("email/email_products.txt", 'r');
@ $wp3 = fopen("email/email_footer.txt", 'r');

//load text file attachment templates
@ $tf1 = fopen("email/textattach_header.txt", 'r');
@ $tf2 = fopen("email/textattach_products.txt", 'r');
@ $tf3 = fopen("email/textattach_footer.txt", 'r');

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
	  
// Get Recent PO Number
$query110=mysql_query("SELECT max(po_number) FROM ".TABLE_ORDERS_PRODUCTS."")
			or die("Failed to connect database: ");
			$row110=mysql_fetch_array($query110, MYSQL_NUM);
                        $lastponum=$row110[0];

// Preperation to Send POs -- Form Page of Admin Modified

			function sub($name, $i)
			{

			$query2=mysql_query("SELECT  subcontractors_id,short_name FROM ".TABLE_SUBCONTRACTORS." ORDER BY short_name")
			or die('Failed to connect database: ');
			$query232=mysql_query("SELECT products_id, default_subcontractor FROM ".TABLE_PRODUCTS." WHERE products_id='$name'")
			or die ("Nie mzona sie polaczcy z baza danych");
			$row232=mysql_fetch_array($query232, MYSQL_NUM);

			while($row22=mysql_fetch_array($query2, MYSQL_NUM))
			{
			if($row232[1]==$row22[0])
			{
			return $row22[0];
			}
			}
			}

			$query=mysql_query("SELECT p.orders_products_id, p.orders_id, p.orders_products_id, p.products_name, p.products_id, o.shipping_method, o.delivery_state, p.products_quantity, o.delivery_street_address, o.delivery_city, o.delivery_suburb, o.delivery_postcode, o.delivery_country, o.delivery_company, o.delivery_name, p.products_model FROM ".TABLE_ORDERS_PRODUCTS." as p, ".TABLE_ORDERS." as o WHERE  p.orders_id=o.orders_id AND po_sent='0' AND o.orders_status != '$status_shippedorder' AND po_number  IS NULL $ignore_status_database ORDER by p.orders_id ASC")
			or die('Failed to connect database: 8');

			$i=1;
			while($row2=mysql_fetch_array($query, MYSQL_NUM))
			{
                        $iless = $i - 1;
			$subk[$iless]=sub($row2[4], $i);
                        $opik[$iless]=$row2[2];
                        $idk[$iless]=$row2[0];
			$i++;
			}



if (PO_SEND_PACKING_LISTS == 0 || PO_SEND_PACKING_LISTS == 3)
    $includepackinglistoption="no";
if (PO_SEND_PACKING_LISTS == 1 || PO_SEND_PACKING_LISTS == 2)
    $includepackinglistoption="yes";
if (PO_SEND_TEXT_FILE == 0 || PO_SEND_TEXT_FILE == 3) 
    $includetextfileoption="no";
if (PO_SEND_TEXT_FILE == 1 || PO_SEND_TEXT_FILE == 2)
    $includetextfileoption="yes";

// Prep Files
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


// Prepare and Send POs

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
		{       
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
			$catmeow = nl2br(zen_db_output($oatmeal->fields['comments']));
			$catmeow=strip_tags($catmeow);
                        $catmeow=html_entity_decode($catmeow,ENT_QUOTES);

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
                        $wielowymiar[$i][27]=$row4[15]; // delivery street address
                        $wielowymiar[$i][28]=$row4[16]; // delivery city
                        $wielowymiar[$i][29]=$row4[17]; // delivery state
                        $wielowymiar[$i][30]=$row4[18]; // delivery postal code
                        $wielowymiar[$i][31]=$row4[19]; // delivery country
                        $wielowymiar[$i][32]=$row4[22]; // billing street address
                        $wielowymiar[$i][33]=$row4[23]; // billing city
                        $wielowymiar[$i][34]=$row4[24]; // billing state
                        $wielowymiar[$i][35]=$row4[25]; // billing postal code
                        $wielowymiar[$i][36]=$row4[26]; // billing country
                        $wielowymiar[$i][37]=$row4[34]; // customer suburb
                        $wielowymiar[$i][38]=$row4[35]; // delivery suburb
                        $wielowymiar[$i][39]=$row4[36]; // billing suburb
                        $wielowymiar[$i][40]=$row4[37]; // customers company
		}
	}

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
                             $pdf->Image(PO_KNOW_ADIR . "/" . PO_PDFP_PICTURE_ONE_FILE,$locationarr[0],$locationarr[1],PO_PDFP_PICTURE_ONE_WIDTH);
                        }
                        if (PO_PDFP_PICTURE_TWO_FILE != '') {
                             $locationarr = explode(',', PO_PDFP_PICTURE_TWO_LOCATION);
                             $pdf->Image(PO_KNOW_ADIR . "/" . PO_PDFP_PICTURE_TWO_FILE,$locationarr[0],$locationarr[1],PO_PDFP_PICTURE_TWO_WIDTH);
                        }
                        $pdf->addSociete( PO_PDFP_S_NAME,
                  		
              			          PO_PDFP_S_ADDRESS );
			$pdf->fact_dev( PO_PDFP_TITLE, "" );
			$invdate=date(PO_PDFP_DATE);
			$pdf->addDate($invdate);
			$pdf->addClient($tmpt[0][4]);

                        $first_pl_ad = str_replace("{cust_ad}",$tmpt[0][11]."\n".$tmpt[0][12],PO_PDFP_FA_ADDRESS);
                        $first_pl_ad = str_replace("{bill_ad}",$tmpt[0][19]."\n".$tmpt[0][6],$first_pl_ad);
                        $first_pl_ad = str_replace("{ship_ad}",$tmpt[0][15]."\n".$tmpt[0][5],$first_pl_ad);
                        $pdf->addClientBillAdresse($first_pl_ad);
                        $second_pl_ad = str_replace("{cust_ad}",$tmpt[0][11]."\n".$tmpt[0][12],PO_PDFP_SA_ADDRESS);
                        $second_pl_ad = str_replace("{bill_ad}",$tmpt[0][19]."\n".$tmpt[0][6],$second_pl_ad);
                        $second_pl_ad = str_replace("{ship_ad}",$tmpt[0][15]."\n".$tmpt[0][5],$second_pl_ad);
                        $pdf->addClientShipAdresse($second_pl_ad);
                        $querycp=mysql_query("SELECT orders_products_id FROM ".TABLE_ORDERS_PRODUCTS."  WHERE  orders_id='".$tmpt[0][4]."'  ")
			or die('Failed to connect database: 8');

			$countproducts=0;
			while($rowcp=mysql_fetch_array($querycp, MYSQL_NUM))
			{
                           $countproducts++;
                        }
                        $countproductsonpo = count($tmpt);
                        /* DEBUG echo "total - ".$countproducts."  and  onpo - ".$countproductsonpo; */
                   
                        $oatmeal = $db->Execute("select comments from " . TABLE_ORDERS_STATUS_HISTORY . " WHERE orders_id = '" . zen_db_input($tmpt[0][4]) . "' order by date_added");
			            $catmeow = nl2br(zen_db_output($oatmeal->fields['comments']));
                        $catmeow=strip_tags($catmeow); 
                        $catmeow=html_entity_decode($catmeow,ENT_QUOTES);
                        if ($countproductsonpo != $countproducts)
                            $custcommentline = str_replace("{customers_comments}",$catmeow,PO_PDFP_CUST_COMMENT_PARTIAL);
                        else
                            $custcommentline = str_replace("{customers_comments}",$catmeow,PO_PDFP_CUST_COMMENT_FULL);
                        $custcommentline = str_replace("{store_comments}","",$custcommentline);
                        $custcommentline = str_replace("{shipping_method}",$tmpt[0][7],$custcommentline);
		                $pdf->addReference($custcommentline);
                       
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
			
			for($h=0; $h<count($tmpt); $h++)
			{
				if ($y > $ynot) {  // Start New Page
                                    $pdf->AddPage();
                        if (PO_PDFP_PICTURE_ONE_FILE != '') {
                             $locationarr = explode(',', PO_PDFP_PICTURE_ONE_LOCATION);
                             $pdf->Image(PO_KNOW_ADIR . "/" . PO_PDFP_PICTURE_ONE_FILE,$locationarr[0],$locationarr[1],PO_PDFP_PICTURE_ONE_WIDTH);
                        }
                        if (PO_PDFP_PICTURE_TWO_FILE != '') {
                             $locationarr = explode(',', PO_PDFP_PICTURE_TWO_LOCATION);
                             $pdf->Image(PO_KNOW_ADIR . "/" . PO_PDFP_PICTURE_TWO_FILE,$locationarr[0],$locationarr[1],PO_PDFP_PICTURE_TWO_WIDTH);
                        }
                        $pdf->addSociete( PO_PDFP_S_NAME,
                  		
              			          PO_PDFP_S_ADDRESS );
			$pdf->fact_dev( PO_PDFP_TITLE, "" );
			$invdate=date(PO_PDFP_DATE);
			$pdf->addDate($invdate);
			$pdf->addClient($tmpt[0][4]);

                        $first_pl_ad = str_replace("{cust_ad}",$tmpt[0][11]."\n".$tmpt[0][12],PO_PDFP_FA_ADDRESS);
                        $first_pl_ad = str_replace("{bill_ad}",$tmpt[0][19]."\n".$tmpt[0][6],$first_pl_ad);
                        $first_pl_ad = str_replace("{ship_ad}",$tmpt[0][15]."\n".$tmpt[0][5],$first_pl_ad);
                        $pdf->addClientBillAdresse($first_pl_ad);
                        $second_pl_ad = str_replace("{cust_ad}",$tmpt[0][11]."\n".$tmpt[0][12],PO_PDFP_SA_ADDRESS);
                        $second_pl_ad = str_replace("{bill_ad}",$tmpt[0][19]."\n".$tmpt[0][6],$second_pl_ad);
                        $second_pl_ad = str_replace("{ship_ad}",$tmpt[0][15]."\n".$tmpt[0][5],$second_pl_ad);
                        $pdf->addClientShipAdresse($second_pl_ad);
                        
                        $oatmeal = $db->Execute("select comments from " . TABLE_ORDERS_STATUS_HISTORY . " WHERE orders_id = '" . zen_db_input($tmpt[0][4]) . "' order by date_added");
			            $catmeow = nl2br(zen_db_output($oatmeal->fields['comments']));
                        $catmeow=strip_tags($catmeow); 
                        $catmeow=html_entity_decode($catmeow,ENT_QUOTES);
                        if ($countproductsonpo != $countproducts)
                            $custcommentline = str_replace("{customers_comments}",$catmeow,PO_PDFP_CUST_COMMENT_PARTIAL);
                        else
                            $custcommentline = str_replace("{customers_comments}",$catmeow,PO_PDFP_CUST_COMMENT_FULL);
                        $custcommentline = str_replace("{store_comments}","",$custcommentline);
                        $custcommentline = str_replace("{shipping_method}",$tmpt[0][7],$custcommentline);
		                $pdf->addReference($custcommentline);
						
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
				$result9=mysql_query("SELECT products_model, products_name, final_price, products_quantity, products_id
									  FROM ".TABLE_ORDERS_PRODUCTS." WHERE orders_products_id='$tm'")
				or die("Failed to connect database: ");
				$row9=mysql_fetch_array($result9, MYSQL_NUM);
				$trescik=$tresc_robij1;
                                $trescik_tf=$tresc_robij1_tf;
				$manufacturernamed=zen_get_products_manufacturers_name($row9[4]);
				$trescik=str_replace("{manufacturers_name}",$manufacturernamed,$trescik);
				$trescik=str_replace("{products_name}",$row9[1],$trescik);
				$trescik=str_replace("{products_model}",$row9[0],$trescik);
				$trescik=str_replace("{final_price}",$currencies->format($row9[2], true, $rowcurrency[0], $rowcurrency[1]),$trescik);
				$trescik=str_replace("{products_quantity}",$row9[3],$trescik);

                                $trescik_tf=str_replace("{manufacturers_name}",$manufacturernamed,$trescik_tf);
				$trescik_tf=str_replace("{products_name}",$row9[1],$trescik_tf);
				$trescik_tf=str_replace("{products_model}",$row9[0],$trescik_tf);
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
                             $pdf->Image(PO_KNOW_ADIR . "/" . PO_PDFP_PICTURE_ONE_FILE,$locationarr[0],$locationarr[1],PO_PDFP_PICTURE_ONE_WIDTH);
                        }
                        if (PO_PDFP_PICTURE_TWO_FILE != '') {
                             $locationarr = explode(',', PO_PDFP_PICTURE_TWO_LOCATION);
                             $pdf->Image(PO_KNOW_ADIR . "/" . PO_PDFP_PICTURE_TWO_FILE,$locationarr[0],$locationarr[1],PO_PDFP_PICTURE_TWO_WIDTH);
                        }
                        $pdf->addSociete( PO_PDFP_S_NAME,
                  		
              			          PO_PDFP_S_ADDRESS );
			$pdf->fact_dev( PO_PDFP_TITLE, "" );
			$invdate=date(PO_PDFP_DATE);
			$pdf->addDate($invdate);
			$pdf->addClient($tmpt[0][4]);

                        $first_pl_ad = str_replace("{cust_ad}",$tmpt[0][11]."\n".$tmpt[0][12],PO_PDFP_FA_ADDRESS);
                        $first_pl_ad = str_replace("{bill_ad}",$tmpt[0][19]."\n".$tmpt[0][6],$first_pl_ad);
                        $first_pl_ad = str_replace("{ship_ad}",$tmpt[0][15]."\n".$tmpt[0][5],$first_pl_ad);
                        $pdf->addClientBillAdresse($first_pl_ad);
                        $second_pl_ad = str_replace("{cust_ad}",$tmpt[0][11]."\n".$tmpt[0][12],PO_PDFP_SA_ADDRESS);
                        $second_pl_ad = str_replace("{bill_ad}",$tmpt[0][19]."\n".$tmpt[0][6],$second_pl_ad);
                        $second_pl_ad = str_replace("{ship_ad}",$tmpt[0][15]."\n".$tmpt[0][5],$second_pl_ad);
                        $pdf->addClientShipAdresse($second_pl_ad);
                        
                        $oatmeal = $db->Execute("select comments from " . TABLE_ORDERS_STATUS_HISTORY . " WHERE orders_id = '" . zen_db_input($tmpt[0][4]) . "' order by date_added");
			            $catmeow = nl2br(zen_db_output($oatmeal->fields['comments']));
                        $catmeow=strip_tags($catmeow); 
                        $catmeow=html_entity_decode($catmeow,ENT_QUOTES);
                        if ($countproductsonpo != $countproducts)
                            $custcommentline = str_replace("{customers_comments}",$catmeow,PO_PDFP_CUST_COMMENT_PARTIAL);
                        else
                            $custcommentline = str_replace("{customers_comments}",$catmeow,PO_PDFP_CUST_COMMENT_FULL);
                        $custcommentline = str_replace("{store_comments}","",$custcommentline);
                        $custcommentline = str_replace("{shipping_method}",$tmpt[0][7],$custcommentline);
		                $pdf->addReference($custcommentline);
						
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
                             $pdf->Image(PO_KNOW_ADIR . "/" . PO_PDFP_PICTURE_ONE_FILE,$locationarr[0],$locationarr[1],PO_PDFP_PICTURE_ONE_WIDTH);
                        }
                        if (PO_PDFP_PICTURE_TWO_FILE != '') {
                             $locationarr = explode(',', PO_PDFP_PICTURE_TWO_LOCATION);
                             $pdf->Image(PO_KNOW_ADIR . "/" . PO_PDFP_PICTURE_TWO_FILE,$locationarr[0],$locationarr[1],PO_PDFP_PICTURE_TWO_WIDTH);
                        }
                        $pdf->addSociete( PO_PDFP_S_NAME,
                  		
              			          PO_PDFP_S_ADDRESS );
			$pdf->fact_dev( PO_PDFP_TITLE, "" );
			$invdate=date(PO_PDFP_DATE);
			$pdf->addDate($invdate);
			$pdf->addClient($tmpt[0][4]);

                        $first_pl_ad = str_replace("{cust_ad}",$tmpt[0][11]."\n".$tmpt[0][12],PO_PDFP_FA_ADDRESS);
                        $first_pl_ad = str_replace("{bill_ad}",$tmpt[0][19]."\n".$tmpt[0][6],$first_pl_ad);
                        $first_pl_ad = str_replace("{ship_ad}",$tmpt[0][15]."\n".$tmpt[0][5],$first_pl_ad);
                        $pdf->addClientBillAdresse($first_pl_ad);
                        $second_pl_ad = str_replace("{cust_ad}",$tmpt[0][11]."\n".$tmpt[0][12],PO_PDFP_SA_ADDRESS);
                        $second_pl_ad = str_replace("{bill_ad}",$tmpt[0][19]."\n".$tmpt[0][6],$second_pl_ad);
                        $second_pl_ad = str_replace("{ship_ad}",$tmpt[0][15]."\n".$tmpt[0][5],$second_pl_ad);
                        $pdf->addClientShipAdresse($second_pl_ad);
                        
                        $oatmeal = $db->Execute("select comments from " . TABLE_ORDERS_STATUS_HISTORY . " WHERE orders_id = '" . zen_db_input($tmpt[0][4]) . "' order by date_added");
			            $catmeow = nl2br(zen_db_output($oatmeal->fields['comments']));
                        $catmeow=strip_tags($catmeow); 
                        $catmeow=html_entity_decode($catmeow,ENT_QUOTES);
                        if ($countproductsonpo != $countproducts)
                            $custcommentline = str_replace("{customers_comments}",$catmeow,PO_PDFP_CUST_COMMENT_PARTIAL);
                        else
                            $custcommentline = str_replace("{customers_comments}",$catmeow,PO_PDFP_CUST_COMMENT_FULL);
                        $custcommentline = str_replace("{store_comments}","",$custcommentline);
                        $custcommentline = str_replace("{shipping_method}",$tmpt[0][7],$custcommentline);
		                $pdf->addReference($custcommentline);
						
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

			// $newzawartosc=str_replace("{po_number}",$wielowymiar[$i][4]."-".$kod,$newzawartosc);
			if ($subcontractor['email_title'] == '')
			     $tematk=PO_SUBJECT;
                        else
                             $tematk=$subcontractor['email_title'];
			$tematk=str_replace("{po_number}",$wielowymiar[$i][4]."-".$kod,$tematk);
			$tematk=str_replace("{contact_person}",$subcontractor['contact_person'],$tematk);
			$tematk=str_replace("{full_name}",$subcontractor['full_name'],$tematk);
		        $tematk=str_replace("{short_name}",$subcontractor['short_name'],$tematk);
                        $tematk = str_replace("{order_number}",$wielowymiar[$i][4],$tematk);
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
                            $tracking_link_1=TRACKING_FEATURE_NOT_AVAILABLE_GOOGLE_CHECKOUT;
                        else
			    $tracking_link_1='<a href="'.HTTP_SERVER.DIR_WS_CATALOG.'/confirm_track_sub.php?x='.$dlaemaila.'&y='.$kod.'&owner='.$securitycode.'">'.HTTP_SERVER.DIR_WS_CATALOG.'/confirm_track_sub.php?x='.$dlaemaila.'&y='.$kod.'&owner='.$securitycode.'</a>';
 /* for($t=0; $t<=count($tracking_link); $t++)
			{  
				$tracking_link_good=$tracking_link_good.str_replace("{tracking_link}",$tracking_link_1,$tracking_link[$t]);
			} */			 
/* $tracking_link_good=str_replace("{tracking_link}",$tracking_link_1,$tracking_link_good); */
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
                  $newzawartosc = str_replace("{order_number}",$wielowymiar[$i][4],$newzawartosc);
		  $newzawartosc = str_replace("{customers_address}",$wielowymiar[$i][12],$newzawartosc);
		  $newzawartosc = str_replace("{customers_phone}",$wielowymiar[$i][13],$newzawartosc);
		  $newzawartosc = str_replace("{customers_email}",$wielowymiar[$i][14],$newzawartosc);
		  $newzawartosc = str_replace("{delivery_name}",$wielowymiar[$i][15],$newzawartosc);
		  $newzawartosc = str_replace("{po_comments}","",$newzawartosc);
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
                  $newzawartosc_tf = str_replace("{order_number}",$wielowymiar[$i][4],$newzawartosc_tf);
		  $newzawartosc_tf = str_replace("{customers_address}",$wielowymiar[$i][12],$newzawartosc_tf);
		  $newzawartosc_tf = str_replace("{customers_phone}",$wielowymiar[$i][13],$newzawartosc_tf);
		  $newzawartosc_tf = str_replace("{customers_email}",$wielowymiar[$i][14],$newzawartosc_tf);
		  $newzawartosc_tf = str_replace("{delivery_name}",$wielowymiar[$i][15],$newzawartosc_tf);
		  $newzawartosc_tf = str_replace("{po_comments}","",$newzawartosc_tf);
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

if ($row978[0] == $status_neworder && $status_posentorder != '' && $status_posentorder != NULL) {			
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
			$catmeow = zen_db_output($oatmeal->fields['comments']);
   if(PO_SENT_COMMENTS != '' && PO_SENT_COMMENTS != NULL && $catmeow != PO_SENT_COMMENTS) {
       $query555=mysql_query("INSERT INTO ".TABLE_ORDERS_STATUS_HISTORY."
				(orders_status_id, orders_id, date_added,
 					customer_notified, comments)
  				 values ('$row978[0]','$tm1',now(),'0','".PO_SENT_COMMENTS."')")
				 or die(mysql_error());
   }
}
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
			


     $oatmeal = $db->Execute("select comments from " . TABLE_ORDERS_STATUS_HISTORY . " WHERE orders_id = '" . zen_db_input($tmpt[0][4]) . "' order by date_added");
     $catmeow = nl2br(zen_db_output($oatmeal->fields['comments']));
     $catmeow=strip_tags($catmeow); 
     $catmeow=html_entity_decode($catmeow,ENT_QUOTES);
     if ($countproductsonpo != $countproducts)
          $insidelines = str_replace("{customers_comments}",$catmeow,PO_PARTIALSHIP_PACKINGLIST);
     else
          $insidelines = str_replace("{customers_comments}",$catmeow,PO_FULLSHIP_PACKINGLIST);
     $insidelines = str_replace("{store_comments}","",$insidelines);

$insidelines = str_replace("{shipping_method}",$tmpt[0][7],$insidelines);    
$pdf->addNotes($insidelines);


      $pdf->Output($usepdffilename, "F");
if (PO_SEND_PACKING_LISTS == 4) {
    if ($sendpdf_sc == 'YES')
        $attachthepdf = 'yes';
    else
        $attachthepdf = 'no';
} else {
    $attachthepdf = $includepackinglistoption;
}
if (PO_SEND_TEXT_FILE == 4) {
    if ($sendtf_sc == 'YES')
        $attachthetf = 'yes';
    else
        $attachthetf = 'no';
} else {
    $attachthetf = $includetextfileoption;
}


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
echo 'Purchase Order '. $wielowymiar[$i][4].'-'.$kod .' Emailed To: ' . $adresdo . " 
";                       

			// mail($adresdo, $tematk, $newzawartosc, $po_from);
			$tracking_link_good='';
                        $tracking_link_good_tf='';
			$date=date('Y-m-d');
// unlink($pdffilename);

for($m=0; $m<count($tmpt); $m++)
			{
				$tm=$tmpt[$m][2];
				$tm2=$tmpt[$m][0];
                                
				// $check_if_po_sent = mysql_query("SELECT * FROM ".TABLE_ORDERS_PRODUCTS." WHERE orders_products_id = '$tm'");
				// $if_po_sent = mysql_fetch_assoc($check_if_po_sent);
				// $po_sent = $if_po_sent['po_sent'];

				
				$result=mysql_query("UPDATE ".TABLE_ORDERS_PRODUCTS." SET po_sent='1', item_shipped=0, po_number='$kod', po_sent_to_subcontractor='$tm2', po_date='$date' WHERE  orders_products_id='$tm' LIMIT 1")	or die("Failed to connect database: 5");
				
			}
                }
	}

} else {
   echo "I don't know you!";
}
?>