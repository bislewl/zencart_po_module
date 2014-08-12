<?php
require('../posecuritycode.php');
$securitycode = PO_SECURITY_KEY;
$ownercode = $securitycode . 'yes';
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



  require('includes/application_top.php');

  require(DIR_WS_CLASSES . 'currencies.php');
@ $wp1 = fopen("../email/email_supplier.txt", 'r');

?>
<html <?php echo HTML_PARAMS; ?>>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>">
<title><?php echo TITLE; ?></title>
<script type="text/javascript">
        var GB_ROOT_DIR = "greybox/";
    </script>

    <script type="text/javascript" src="greybox/AJS.js"></script>
    <script type="text/javascript" src="greybox/AJS_fx.js"></script>

    <script type="text/javascript" src="greybox/gb_scripts.js"></script>
    <link href="greybox/gb_styles.css" rel="stylesheet" type="text/css" media="all" />

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
<script type="text/javascript">AJS.AEV(window, 'load', function() {init()});</script>

</head>

<?php
// Begin Send Email to Subcontractor
        if ($_POST['action'] == 'sendpo') {
             $messageStack->add('Sending email');
             $ponum = $_POST['ponum'];
             $query=mysql_query("SELECT checked_status FROM ".TABLE_ORDERS_PRODUCTS." WHERE po_sent=1 AND item_shipped=0 AND po_number='$ponum' LIMIT 1")  or die("Nie mozna sie polaczyc z baza danych1");
             $row=mysql_fetch_array($query, MYSQL_NUM);
            // if ($_POST[isitokay] == $row[0]) {
             $messageStack = new messageStack();
             $recordsup = 'Inquiry Sent ' . date("m-d-Y") . '<br />';
             $html_msg['EMAIL_MESSAGE_HTML'] = str_replace('
','<br />',$_POST['ebody']);
             zen_mail($_POST['eaddress'],$_POST['eaddress'],$_POST['etitle'],$_POST['ebody'],PO_FROM_EMAIL_NAME,PO_FROM_EMAIL_ADDRESS, $html_msg, NULL);
             $queryup=mysql_query("SELECT checked_status, orders_products_id FROM ".TABLE_ORDERS_PRODUCTS." WHERE po_sent=1 AND item_shipped=0 AND 
             po_number='$ponum'")  or die("Nie mozna sie polaczyc z baza danych2");
             while($rowup=mysql_fetch_array($queryup, MYSQL_NUM))
             {
                  $records = $rowup[0] . $recordsup;
                  mysql_query("UPDATE " . TABLE_ORDERS_PRODUCTS . " SET checked_status='$records' WHERE po_number='$ponum' AND orders_products_id='$rowup[1]'");
             }
                  $messageStack->add('Inquiry Regarding Purchase Order '. $_POST['ordernum'].'-'.$ponum .' Emailed To: ' . $_POST['eaddress'], 'success');
            // }
        }
     
?>
<body>
<!-- header //-->
<?php require(DIR_WS_INCLUDES . 'header.php'); ?>
<!-- header_eof //-->

<br>
<?php
// Begin Send Email to Subcontractor Preview
if ($_GET[action]=='check') {
?>
     <?php
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
     $ponum = $_GET[ponum];
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
     $query=mysql_query("SELECT orders_id, po_date, po_sent_to_subcontractor, expected_date, checked_status, products_name, products_quantity FROM ".TABLE_ORDERS_PRODUCTS." WHERE
     po_sent=1 AND item_shipped=0 AND po_number='$ponum'")  or die("Nie mozna sie polaczyc z baza danych3");
     $row=mysql_fetch_array($query, MYSQL_NUM);
     $ordid = $row[0];
     $checkingit = $row[4];
     $query2=mysql_query("SELECT delivery_name, delivery_company, delivery_street_address, delivery_suburb, delivery_city, delivery_postcode, delivery_state, delivery_country, 
     delivery_name, shipping_method FROM ".TABLE_ORDERS." WHERE orders_id='$row[0]'")  or die("Nie mozna sie polaczyc z baza danych4");
     $row2=mysql_fetch_array($query2, MYSQL_NUM);
     $subcontractor_query = mysql_query("SELECT * FROM ".TABLE_SUBCONTRACTORS." WHERE subcontractors_id = '$row[2]'");
     $subcontractor = mysql_fetch_assoc($subcontractor_query);
     if ($row2[1] != '' && $row2[1] != NULL)
				$adres_deliver = $row2[1]."\n";
			else
				$adres_deliver = "";
     if ($row2[3] != '' && $row2[3] != NULL)
				$adres_deliver .= $row2[2]."\n".$row2[3]."\n".$row2[4].", ".$row2[6]." ".$row2[5]."\n".$row2[7];
			else
				$adres_deliver .= $row2[2]."\n".$row2[4].", ".$row2[6]." ".$row2[5]."\n".$row2[7];
     $zawartosc = str_replace("{po_number}",$row[0]."-".$ponum,$zawartosc);
     $zawartosc = str_replace("{order_number}",$row[0],$zawartosc);
     $zawartosc = str_replace("{contact_person}",$subcontractor['contact_person'],$zawartosc);
     $zawartosc = str_replace("{full_name}",$subcontractor['full_name'],$zawartosc);
     $zawartosc = str_replace("{short_name}",$subcontractor['short_name'],$zawartosc);
     $zawartosc = str_replace("{subcontractors_id}",$subcontractor['subcontractors_id'],$zawartosc);
     $zawartosc = str_replace("{street}",$subcontractor['street1'],$zawartosc);
     $zawartosc = str_replace("{city}",$subcontractor['city'],$zawartosc);
     $zawartosc = str_replace("{state}",$subcontractor['state'],$zawartosc);
     $zawartosc = str_replace("{zip}",$subcontractor['zip'],$zawartosc);
     $zawartosc = str_replace("{telephone}",$subcontractor['telephone'],$zawartosc);
     $zawartosc = str_replace("{email_address}",$subcontractor['email_address'],$zawartosc);

     $zawartosc = str_replace("{po_date}",$row[1],$zawartosc);
     $zawartosc = str_replace("{expected_date}",$row[3],$zawartosc);

     $zawartosc = str_replace("{delivery_name}",$row2[8],$zawartosc);
     $zawartosc = str_replace("{delivery_company}",$row2[1],$zawartosc);
     $zawartosc = str_replace("{delivery_address}",$adres_deliver,$zawartosc);
     $zawartosc = str_replace("{delivery_street_address}",$row2[2],$zawartosc);
     $zawartosc = str_replace("{delivery_city}",$row2[4],$zawartosc);
     $zawartosc = str_replace("{delivery_state}",$row2[6],$zawartosc);
     $zawartosc = str_replace("{delivery_state_code}",findStateAbbreviations($row2[6]),$zawartosc);
     $zawartosc = str_replace("{delivery_postal_code}",$row2[5],$zawartosc);
     $zawartosc = str_replace("{delivery_country}",$row2[7],$zawartosc);
     if ($row2[9] != PO_CHANGE_SHIPPING_FROM) {
			$zawartosc = str_replace("{shipping_method}",$row2[9],$zawartosc);
     } else {
			$zawartosc = str_replace("{shipping_method}",PO_CHANGE_SHIPPING_TO,$zawartosc);
     }
     

     $productlist = $row[6] . " " . $row[5];
                                while($row=mysql_fetch_array($query, MYSQL_NUM))
					{
                                             $productlist.=', ';
					     $productlist=$productlist.$row[6]." ".$row[5];
					}
     $zawartosc = str_replace("{products}",$productlist,$zawartosc);
     $pageforreview = zen_draw_form('editpo', 'confirm_track.php', '', 'post', 'enctype="multipart/form-data"') . zen_hide_session_id();
     /*
     if (isset($_GET[sorder])) {
          $pageforreview = '<form name="editpo" action="confirm_track.php?sorder='.$_GET[sorder];
          if (isset($_GET[a]))
               $pageforreview .= '&a='.$_GET[a];
          $pageforreview .= '" method="POST">';
     } else {
          $pageforreview = '<form name="editpo" action="'.HTTP_SERVER.DIR_WS_ADMIN.'confirm_track.php';
          if (isset($_GET[a]))
               $pageforreview .= '?a='.$_GET[a];
          $pageforreview .= '" method="POST">';
     }
     */
     $pageforreview .= '<center>' . REVIEW_EMAIL_EMAIL_TITLE . '&nbsp;<input type="text" name="etitle" size="125" value="Purchase Order ' . $ordid . '-' . $ponum . '" /><br /><br />' .
     REVIEW_EMAIL_SEND_EMAIL_TO . '&nbsp;<input type="text" name="eaddress" size="125" value="' . $subcontractor['email_address'] . '" /><br /><br /><u><b>Email Body</u></b><br />
     <textarea rows="30" name="ebody">' . $zawartosc . '</textarea>';
     $pageforreview .= '<input type="hidden" name="isitokay" value="'.$checkingit.'" /><input type="hidden" name="ponum" value="'.$ponum.'" /><input type="hidden" name="ordernum" value="'.$ordid.'" /><input type="hidden" name="action" value="sendpo" />';
     $pageforreview .= '<br /><br /><input type="image" src="includes/languages/english/images/buttons/button_send.gif" name="insert" ONCLICK="javascript:document.pos.submit();"></center>';
     ?>
     <table border="0" width='100%' cellspacing="0" cellpadding="0">
     <tr><td class="pageHeading" colspan="2"><?php  echo 'Send Email to '.$subcontractor[full_name].' Inquiring About Purchase Order Status'; ?><br><br></td></tr></table>
     <?php
     echo $pageforreview . "<br /><br /></form>";

} else {
     fclose($wp1);
?>


<!-- Begin Regular Enter Tracking Page //-->
<!-- body //-->
<table border="0" width='100%' cellspacing="0" cellpadding="0">

<!-- body_text //-->

<?php
// Get Order Status Number For Shipped Orders
$tmp=PO_SHIPPED_STATUS;
$querygot=mysql_query("SELECT orders_status_id FROM ".TABLE_ORDERS_STATUS." WHERE orders_status_name='$tmp'")
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
?>

<tr><td class="pageHeading" colspan="2"><?php  echo HEADING_TITLE_TRACKING; ?><br><br></td></tr>
<td valign="top">
<?php
$sorder = $_GET[sorder];
/* Nothing = Oldest First, Show Everything   1 = Newest First, Show Everything   2 = Oldest First, Show No Dates Only   3 = Newest First, Show No Dates Only */
switch ($sorder) {
   case '' :  echo "<a href='".HTTP_SERVER.DIR_WS_ADMIN."confirm_track.php?sorder=1'>".SHOW_NEWEST_PO_FIRST."</a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;-----&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
              echo "<a href='".HTTP_SERVER.DIR_WS_ADMIN."confirm_track.php?sorder=2'>".SHOW_NODATES_ONLY."</a>";
              break;
   case '1':  echo "<a href='".HTTP_SERVER.DIR_WS_ADMIN."confirm_track.php'>".SHOW_OLDEST_PO_FIRST."</a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;-----&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
              echo "<a href='".HTTP_SERVER.DIR_WS_ADMIN."confirm_track.php?sorder=3'>".SHOW_NODATES_ONLY."</a>";
              break;
   case '2' : echo "<a href='".HTTP_SERVER.DIR_WS_ADMIN."confirm_track.php?sorder=3'>".SHOW_NEWEST_PO_FIRST."</a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;-----&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
              echo "<a href='".HTTP_SERVER.DIR_WS_ADMIN."confirm_track.php'>".SHOW_EVERYTHING."</a>";
              break;
   case '3':  echo "<a href='".HTTP_SERVER.DIR_WS_ADMIN."confirm_track.php?sorder=2'>".SHOW_OLDEST_PO_FIRST."</a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;-----&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
              echo "<a href='".HTTP_SERVER.DIR_WS_ADMIN."confirm_track.php?sorder=1'>".SHOW_EVERYTHING."</a>";
              break;
}

?></td>
           <tr>  <td valign="top">
		   <table border="0" width="100%" align="center" cellspacing="0" cellpadding="0">
              <tr class="dataTableHeadingRow">
                <td  class="dataTableHeadingContent" align="center" valign="top">
                  <?php  echo NUMBER_POS_TRACKING; ?>
                </td>
			    <td  class="dataTableHeadingContent" align="center" valign="top">
                  <?php  echo DATA_POS_TRACKING; ?>
                </td>
    <td  class="dataTableHeadingContent" align="center" valign="top">
                  <?php  echo PO_SENT_TO_NAME; ?>
                </td>
				<td  class="dataTableHeadingContent" align="center" valign="top">
                  <?php  echo DELIVER_NAME_TRACKING; ?>
                </td>
                                <td  class="dataTableHeadingContent" align="center" valign="top">
                 <?php  echo EXPECTED_DELIVERY_DATE; ?>
                </td>
                                <td  class="dataTableHeadingContent" align="center" valign="top">
                 <?php  echo WHERE_IS_PACKAGE; ?>
                </td>
			   	</tr>
				<?php
//generowanie zmienncyh i przypisywanie zmiennych dla porcjowania danych
$a=$_GET["a"];
$l_odp_napasku='10';
$l_odp_nastronie=PO_MAX_TRACK;
$start=$a*$l_odp_nastronie;
$i=0;

				$sort_orders=array();
switch ($sorder) {
   case ''  :  $query210b=mysql_query("SELECT p.po_number FROM ".TABLE_ORDERS_PRODUCTS." as p, ".TABLE_ORDERS." as o WHERE p.orders_id=o.orders_id AND p.po_sent=1 AND p.item_shipped=0 AND o.orders_status !='$status_shippedorder' AND o.orders_status !=105 AND o.orders_status !=107 AND o.orders_status !=109 AND o.orders_status !=111 $ignore_status_database ORDER by p.orders_id ASC")
    or die("Nie mozna sie polaczyc z baza danych");
               break;
   case '1' :  $query210b=mysql_query("SELECT p.po_number FROM ".TABLE_ORDERS_PRODUCTS." as p, ".TABLE_ORDERS." as o WHERE p.orders_id=o.orders_id AND p.po_sent=1 AND p.item_shipped=0 AND o.orders_status !='$status_shippedorder' AND o.orders_status !=105 AND o.orders_status !=107 AND o.orders_status !=109 AND o.orders_status !=111 $ignore_status_database ORDER by p.orders_id DESC")
    or die("Nie mozna sie polaczyc z baza danych");
               break;
   case '2'  :  $query210b=mysql_query("SELECT p.po_number FROM ".TABLE_ORDERS_PRODUCTS." as p, ".TABLE_ORDERS." as o WHERE p.orders_id=o.orders_id AND p.po_sent=1 AND p.item_shipped=0 AND o.orders_status !='$status_shippedorder' AND o.orders_status !=105 AND o.orders_status !=107 AND o.orders_status !=109 AND o.orders_status !=111 AND p.expected_date = '' $ignore_status_database ORDER by p.orders_id ASC")
    or die("Nie mozna sie polaczyc z baza danych");
               break;
   case '3' :  $query210b=mysql_query("SELECT p.po_number FROM ".TABLE_ORDERS_PRODUCTS." as p, ".TABLE_ORDERS." as o WHERE p.orders_id=o.orders_id AND p.po_sent=1 AND p.item_shipped=0 AND o.orders_status !='$status_shippedorder' AND o.orders_status !=105 AND o.orders_status !=107 AND o.orders_status !=109 AND o.orders_status !=111 AND p.expected_date = '' $ignore_status_database ORDER by p.orders_id DESC")
    or die("Nie mozna sie polaczyc z baza danych");
               break;
}  

$p=0;

while($row210b=mysql_fetch_array($query210b, MYSQL_NUM))
{
   $sort_orders[$p]=$row210b[0];
   $p++;
} 



$temp=array_unique($sort_orders);
$wyjscie_temp=array_values($temp);

$l_odp=0;
foreach ($wyjscie_temp as $value) {
    if ($l_odp >= $start && $l_odp < ($start+$l_odp_nastronie)) {
         $wyjscie[] = $wyjscie_temp[$l_odp];
    }
    $l_odp++;         
}

// $arr is now array(2, 4, 6, 8)

				for($h=0; $h<count($wyjscie); $h++)
{  
				$query=mysql_query("SELECT orders_id, po_number, po_date, po_sent_to_subcontractor, expected_date, checked_status FROM ".TABLE_ORDERS_PRODUCTS." WHERE po_sent=1 AND item_shipped=0 AND po_number='$wyjscie[$h]'");
				$i=0;
				$row=mysql_fetch_array($query);
                                $expecteddate='';
                                if ($row[4] != '')
                                     $expecteddate = $row[4];
                                while($rowredo=mysql_fetch_array($query))
                                {
                                if (stripos($expecteddate, $rowredo[4]) !== false) {} else {
                                     if ($expecteddate == '')
                                          $expecteddate=$rowredo[4];
                                     else
                                          $expecteddate.="<br />".$rowredo[4];
                                }
                                }
                                

				$query1=mysql_query("SELECT delivery_name, delivery_company, delivery_street_address, delivery_city, delivery_postcode, delivery_state, delivery_country, delivery_suburb, orders_status FROM ".TABLE_ORDERS." WHERE orders_id='$row[0]'")
				or die("Nie mozna sie polaczcy z baza danych");
				$row1=mysql_fetch_array($query1, MYSQL_NUM);

			$subcontractor_query = mysql_query("SELECT full_name, short_name FROM ".TABLE_SUBCONTRACTORS." WHERE subcontractors_id = '$row[3]'");
			$subcontractor = mysql_fetch_assoc($subcontractor_query);
				if($h%2==1)
				     echo "<tr class='dataTableRowSelected'>";
                                else
                                     echo "<tr class='dataTableRow'>";
?>
                <td align="center" valign="center">
<?php
if ($row1[8] < 99 || $row1[8] > 111) {
if (isset($_GET[a]))
     echo "<a href='".HTTP_SERVER.DIR_WS_CATALOG."confirm_track_sub.php?x=".$row[3]."&y=".$row[1]."&owner=".$ownercode."&a=".$_GET[a]."&sorder=".$sorder."' title='Enter Tracking' rel='gb_page_center[1000, 450]'>".$row[0]."-".$row[1]."</a>";
else
     echo "<a href='".HTTP_SERVER.DIR_WS_CATALOG."confirm_track_sub.php?x=".$row[3]."&y=".$row[1]."&owner=".$ownercode."&sorder=".$sorder."' title='Enter Tracking' rel='gb_page_center[1000, 450]'>".$row[0]."-".$row[1]."</a>";
} else {
echo "<a href='".HTTP_SERVER.DIR_WS_ADMIN."orders.php?oID=". $row[0] ."&action=edit'>GC ".$row[0]."-".$row[1]."</a>";
}

?> 
               
                </td>
			    <td align="center" valign="center">
                  <?php  echo date("m-d-Y",strtotime($row[2])); ?>
                </td>
  <td align="center" valign="center">
                  <?php  echo $subcontractor[full_name]; ?>
                </td>
				<td align="center" valign="top">
                  <?php
if ($row1[6] == zen_get_country_name(STORE_COUNTRY))
	$orderaddresscountry="";
else
	$orderaddresscountry="<br />".$row1[6];
if ($row1[7] == "" || $row1[7] == NULL)
	$orderaddresssuburb="";
else
	$orderaddresssuburb="<br />".$row1[7];
if ($row1[1] == "" || $row1[1] == NULL)
	$orderaddresscompany="";
else
	$orderaddresscompany=$row1[1]."<br />";
echo $row1[0]."<br />".$orderaddresscompany.$row1[2].$orderaddresssuburb."<br />".$row1[3].", ".$row1[5]." ".$row1[4]."<br />".$orderaddresscountry;
					 ?>
                </td>
                <td align="center" valign="center">
                <?php  echo date("m-d-Y",$expecteddate); ?>
                </td>
                <td align="center" valign="center">
                <?php if ($row[5] != '') {
                           echo $row[5];
                } ?>
                <a href="confirm_track.php?action=check&ponum=<?php echo $row[1];   if (isset($_GET[sorder]))  echo '&sorder='.$_GET[sorder];  if (isset($_GET[a]))  echo '&a='.$_GET[a];  ?>">Email <?php echo $subcontractor[short_name]; ?></a>
                </td>
			   	</tr>
				<?php
				}
				?>
<?php
                        if (isset($_GET[sorder]))
                             $skrypt="confirm_track.php?sorder=".$_GET[sorder]."&";
                        else
                             $skrypt="confirm_track.php?";
//uruchomienie funkcji porcjujacej dane
			 pasek($l_odp,$l_odp_nastronie,$l_odp_napasku,$skrypt,$a);
?>
				</table>
		</td>

      </tr>
</table>
<?php } ?>
<?php require(DIR_WS_INCLUDES . 'footer.php'); ?>
<!-- footer_eof //-->
<br>
</body>
</html>
<?php require(DIR_WS_INCLUDES . 'application_bottom.php'); ?>