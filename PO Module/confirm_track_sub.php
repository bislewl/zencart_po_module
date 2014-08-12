<?php
require('posecuritycode.php');
$securitycode = PO_SECURITY_KEY;
$admin_dir = PO_KNOW_ADIR;
$ownercode = $securitycode . 'yes';
if ($_POST['owner']!=$ownercode && $_POST['owner']!=$securitycode && $_GET['owner']!=$ownercode && $_GET['owner']!=$securitycode) {
   echo 'This is an invalid link.  Please contact the store owner for more information.';
} else {
// Include application configuration parameters
require ('includes/configure.php');
mysql_connect(DB_SERVER, DB_SERVER_USERNAME, DB_SERVER_PASSWORD) or die(mysql_error());
mysql_select_db(DB_DATABASE) or die(mysql_error());
define('CHARSET','iso-8859-1');
//require('admin/includes/application_top-pos.php');
//	define('CARRIER_NAME_1', 'FedEx');
//	define('CARRIER_NAME_2', 'UPS');
//	define('CARRIER_NAME_3', 'USPS');
//	define('CARRIER_NAME_4', 'DHL');
//	define('CARRIER_NAME_5', '');
//	define('CARRIER_LINK_1', 'http://www.fedex.com/Tracking?action=track&tracknumbers=');
//	define('CARRIER_LINK_2', 'http://wwwapps.ups.com/etracking/tracking.cgi?InquiryNumber1=');
//	define('CARRIER_LINK_3', 'http://trkcnfrm1.smi.usps.com/PTSInternetWeb/InterLabelInquiry.do?origTrackNum=');
//	define('CARRIER_LINK_4', 'http://track.dhl-usa.com/TrackByNbr.asp?ShipmentNumber=');
//	define('CARRIER_LINK_5', '');
require('includes/application_top.php');
require('includes/database_tables.php');
require($admin_dir.'/includes/extra_datafiles/tracker.php');
require($admin_dir.'/includes/extra_datafiles/purchaseorders.php');
require($admin_dir.'/includes/languages/english/extra_definitions/confirm_tracking.php');
require($admin_dir.'/includes/languages/english/orders.php');
require('includes/filenames.php');
//echo TRACKING_CUSTOMER_DATA;
?>
<html>
<head>
<script type='text/javascript'>
window.onload = function()
{
  taInit(false);
}
// Initialize all textareas.
// If bCols is false then columns will not be resized.
function taInit(bCols)
{
  var i, ta = document.getElementsByTagName('textarea');
  for (i = 0; i < ta.length; ++i)
  {
    ta[i]._ta_resize_cols_ = bCols;
    ta[i]._ta_default_rows_ = ta[i].rows;
    ta[i]._ta_default_cols_ = ta[i].cols;
    ta[i].onkeyup = taExpand;
    ta[i].onmouseover = taExpand;
    ta[i].onmouseout = taRestore;
    ta[i].onfocus = taOnFocus;
    ta[i].onblur = taOnBlur;
  }
}
function taOnFocus(e)
{
  this._ta_is_focused_ = true;
  this.onmouseover();
}
function taOnBlur()
{
  this._ta_is_focused_ = false;
  this.onmouseout();
}
// Set to default size if not focused.
function taRestore()
{
  if (!this._ta_is_focused_)
  {
    this.rows = this._ta_default_rows_;
    if (this._ta_resize_cols_)
    {
      this.cols = this._ta_default_cols_;
    }
  }
}
// Resize rows and cols to fit text.
function taExpand()
{
  var a, i, r, c = 0;
  a = this.value.split('\n');
  if (this._ta_resize_cols_)
  {
    for (i = 0; i < a.length; i++) // find max line length
    {
      if (a[i].length > c)
      {
        c = a[i].length;
      }
    }
    if (c < this._ta_default_cols_)
    {
      c = this._ta_default_cols_;
    }
    this.cols = c;
    r = a.length;
  }
  else
  {
    for (i = 0; i < a.length; i++)
    {
      if (a[i].length > this.cols) // find number of wrapped lines
      {
        c += Math.floor(a[i].length / this.cols);
      }
    }
    r = c + a.length; // add number of wrapped lines to number of lines
  }  
  if (r < this._ta_default_rows_)
  {
    r = this._ta_default_rows_;
  }
  this.rows = r;
}
</script>


<script type="text/javascript">

function getVal(bu){
var el=document.getElementById('inp0');
var i=0, c;
while(c=document.getElementById('chk'+(i++))) {
el.value=(bu.checked)? bu.value : null;
c!=bu? c.checked =false : null;
}
}

</script>

<TITLE>Confirm tracking</TITLE>
<link rel="STYLESHEET" type="text/css" href="<?php echo $admin_dir; ?>/includes/style_tracking.css">
</head>
<?php
if($_POST['save']=='save' && $_POST['owner']==$ownercode) {
   if ($_POST[sorder] == '') {
        if ($_POST[a] != '')
             echo '<body onload="top.window.location=\''.$admin_dir.'/confirm_track.php?a='.$_POST[a].'\'">';
        else
             echo '<body onload="top.window.location=\''.$admin_dir.'/confirm_track.php\'">';
   } else {
        if ($_POST[a] != '')
             echo '<body onload="top.window.location=\''.$admin_dir.'/confirm_track.php?sorder='.$_POST[sorder].'&a='.$_POST[a].'\'">';
        else
             echo '<body onload="top.window.location=\''.$admin_dir.'/confirm_track.php?sorder='.$_POST[sorder].'\'">';
   }
} else {
   echo '<body>';
}
?>
<?php
// Get Order Status Number
$statusname = PO_SHIPPED_STATUS;
$querygot=mysql_query("SELECT orders_status_id FROM ".TABLE_ORDERS_STATUS." WHERE orders_status_name='$statusname'")
			or die("Failed to connect database: ");
			$rowgot=mysql_fetch_array($querygot, MYSQL_NUM);
                        $status_shippedorder = $rowgot[0];
?>

<?php
if($_POST['save']=='save')
	{
		$ile=$_POST['ile'];
		//wprowadzanie zmian w tebeli orders_status_historys
			$tracka_id1=$_POST['track_id1'];
			$tracka_id2=$_POST['track_id2'];
			$tracka_id3=$_POST['track_id3'];
			$tracka_id4=$_POST['track_id4'];
			$tracka_id5=$_POST['track_id5'];
			$orders_id=$_POST['orders_id_0'];
                        $esd=$_POST['expected_ship'];
                        $esmessage=$_POST['es_message'];
                        $custommessage=$_POST['chk'];
                        $esda = '';
                        
                        if ($custommessage == PO_ED_CUSTOM_COMMENTS_ONE && PO_ED_CUSTOM_COMMENTS_ONE != '') {
                             $esda = PO_ED_CUSTOM_SHORT_NAME_ONE;
                             if ($_POST['expected_ship'] != '')
                                  $esda.=' - '.$_POST['expected_ship'];
                        }
                        if ($custommessage == PO_ED_CUSTOM_COMMENTS_TWO && PO_ED_CUSTOM_COMMENTS_TWO != '') {
                             $esda = PO_ED_CUSTOM_SHORT_NAME_TWO;
                             if ($_POST['expected_ship'] != '')
                                  $esda.=' - '.$_POST['expected_ship'];
                        }
                        if ($custommessage == PO_ED_CUSTOM_COMMENTS_THREE && PO_ED_CUSTOM_COMMENTS_THREE != '') {
                             $esda = PO_ED_CUSTOM_SHORT_NAME_THREE;
                             if ($_POST['expected_ship'] != '')
                                  $esda.=' - '.$_POST['expected_ship'];
                        }


$query978=mysql_query("SELECT orders_status FROM ".TABLE_ORDERS." WHERE orders_id='$orders_id'")
			or die("Failed to connect database: 1");
$row978=mysql_fetch_array($query978, MYSQL_NUM);
$curstatusoforder = $row978[0];


//ladowanie zmiennej orders_products_id i sprawdzenie ile zostalo zaznaczone pol typu checkbox
                        $rc=-1;
			for($k=0; $k<$ile; $k++)
					{
                                                
						if($_POST['orders_products_id_'.$k]!='')
						{

							$rc++;
                                                        $orders_products_id[$rc]=$_POST['orders_products_id_'.$k];


						}
					}

			if(count($orders_products_id)==0)
			{
			echo "<font class='tekst'>".TRACK_SAVE_ERROR."</font>";
			}else{
                        $esd = strtotime($esd);
                        // Begin Update of Expected Ship Date
                        $updatingesd = false;
                        for($k=0; $k<count($orders_products_id); $k++) {
                             $tmp=$orders_products_id[$k];
                             $queryesd=mysql_query("SELECT expected_date FROM ".TABLE_ORDERS_PRODUCTS." WHERE orders_products_id='$tmp' LIMIT 1")
				     or die(mysql_error());
		             $rowesd=mysql_fetch_array($queryesd, MYSQL_NUM);
                             if ($rowesd[0] != $esd && $esd != '') {
                                  $updatingesd = true;
                                  $query9=mysql_query("UPDATE ".TABLE_ORDERS_PRODUCTS." SET expected_date='$esd' WHERE orders_products_id='$tmp' LIMIT 1")
							or die(mysql_error());         
                             }
                        }
                        if ($updatingesd) {
                                $productlist = '';
                                for($n=0;$n<count($orders_products_id); $n++)
					{
					$tmp=$orders_products_id[$n];

					$query6c=mysql_query("SELECT products_name, products_quantity FROM ".TABLE_ORDERS_PRODUCTS." WHERE orders_id='$orders_id' AND orders_products_id='$tmp'")
					or die(mysql_error());
					$row6c=mysql_fetch_array($query6c, MYSQL_NUM);

					if ($productlist != '' && count($orders_products_id) != 2)
                                             $productlist.=', ';
                                        if ($productlist != '' && count($orders_products_id) == 2)
                                             $productlist.=' ';
                                        if (count($orders_products_id) != 1 && $n==count($orders_products_id)-1)
                                             $productlist.='and ';
                                        if ($row6c[0] != '')
					     $productlist=$productlist.$row6c[1]." ".$row6c[0];
					}
                                $comments=$esmessage;
                                if ((count($orders_products_id) == 1) && ($row6c[1] == 1)) {
                                      $comments = str_replace("{is_are}","is",$comments);
                                      $comments = str_replace("{capital_It_They}","It",$comments);
                                      $comments = str_replace("{it_them}","it",$comments);
                                } else {
                                      $comments = str_replace("{is_are}","are",$comments);
                                      $comments = str_replace("{capital_It_They}","They",$comments);
                                      $comments = str_replace("{it_them}","them",$comments);
                                }
                                $comments = str_replace("{product_list}",$productlist,$comments);
                                $comments = str_replace("{expected_ship_date}",$esda.date("F j, Y",$esd),$comments);
                                $comments=str_replace("'",'\'\'',$comments);
                                define('SEND_EMAILS', 'true');
                                $query44=mysql_query("SELECT date_purchased, customers_name, customers_email_address, orders_status FROM ".TABLE_ORDERS." WHERE orders_id='$orders_id'")
					or die(mysql_error());
			        $row44=mysql_fetch_array($query44, MYSQL_NUM);

                                $nstatus_name = PO_ED_STATUS;
                                if ($custommessage == PO_ED_CUSTOM_COMMENTS_ONE && PO_ED_CUSTOM_COMMENTS_ONE != '')
                                     $nstatus_name = PO_ED_CUSTOM_STATUS_ONE;
                                if ($custommessage == PO_ED_CUSTOM_COMMENTS_TWO && PO_ED_CUSTOM_COMMENTS_TWO != '')
                                     $nstatus_name = PO_ED_CUSTOM_STATUS_TWO;
                                if ($custommessage == PO_ED_CUSTOM_COMMENTS_THREE && PO_ED_CUSTOM_COMMENTS_THREE != '')
                                     $nstatus_name = PO_ED_CUSTOM_STATUS_THREE;
                                if ($nstatus_name != '') {
                                     $querynsn=mysql_query("SELECT orders_status_id FROM ".TABLE_ORDERS_STATUS." WHERE orders_status_name='$nstatus_name'")
				   	or die(mysql_error());
				     $rownsn=mysql_fetch_array($querynsn, MYSQL_NUM);
                                     $nstatus=$rownsn[0];   
                                } else {
                                     $nstatus=$curstatusoforder;
                                     $query55=mysql_query("SELECT orders_status_name FROM ".TABLE_ORDERS_STATUS." WHERE orders_status_id='$nstatus'")
					or die(mysql_error());
				     $row55=mysql_fetch_array($query55, MYSQL_NUM);
                                     $nstatus_name=$row55[0];
                                }
				
		                $notify_comments = '';
				if ($comments != '') {
               			 	$notify_comments = EMAIL_TEXT_COMMENTS_UPDATE . $comments .  "\n\n";
					$order_comment = $comments;
                                }
                                $message = 	STORE_NAME . "\n" . EMAIL_SEPARATOR . "\n" .
					 EMAIL_TEXT_ORDER_NUMBER . ' ' . $orders_id . "\n\n" .
					 EMAIL_TEXT_INVOICE_URL . ' <a href="' . HTTP_SERVER.DIR_WS_CATALOG. "/index.php?main_page=tracker&order_id=$orders_id" . '">' . HTTP_SERVER.DIR_WS_CATALOG. "/index.php?main_page=tracker&order_id=$orders_id" . "</a>\n\n" .
      			EMAIL_TEXT_DATE_ORDERED . ' ' . date("F j, Y",strtotime($row44[0])) . "\n\n" .
					$notify_comments .
      			EMAIL_TEXT_STATUS_UPDATED . sprintf(EMAIL_TEXT_STATUS_LABEL, $nstatus_name ) .
     	 			EMAIL_TEXT_STATUS_PLEASE_REPLY;
$html_msg['EMAIL_MESSAGE_HTML'] = str_replace('
','<br />',$message);
								// REMOVE THE TAGS FOR TEXT EMAIL
								$message = strip_tags($message);

								// SET THE TO EMAIL ADDRESS
								$email_to = $row44[2];

								// SET THE SUBJECT
								$subject = EMAIL_TEXT_SUBJECT . ' #' . $orders_id;

                                                        if (PO_NOTIFY == 1)  {
							     zen_mail($email_to, $email_to, $subject, $message, STORE_NAME, EMAIL_FROM, $html_msg, NULL);
                                                             $cnotif = 1;
                                                        }

                             $query555=mysql_query("INSERT INTO ".TABLE_ORDERS_STATUS_HISTORY."
				(orders_status_id, orders_id, date_added,
 					customer_notified,comments)
  				 values ('$nstatus','$orders_id',now(),'$cnotif','$comments')")
				 or die(mysql_error());
                             
                             mysql_query("update " . TABLE_ORDERS . " set orders_status = '". $nstatus ."', last_modified=now() where orders_id ='$orders_id'");          
                        }        
                                

                        // Begin Tracking Update
			if(($tracka_id1!='') or ($tracka_id2!='') or ($tracka_id3!='') or ($tracka_id4!='') or ($tracka_id5!='') or ($_POST[track_check1]) or ($_POST[track_check2])
                        or ($_POST[track_check3]) or ($_POST[track_check4]) or ($_POST[track_check5]))
				{
                                $trackertypes='';
                                if (($tracka_id1 != '' || $_POST[track_check1]) && (CARRIER_NAME_1 != ''))
                                     $trackertypes=CARRIER_NAME_1;
                                if (($tracka_id2 != '' || $_POST[track_check2]) && (CARRIER_NAME_2 != ''))  {
                                     if ($trackertypes=='')
                                          $trackertypes=CARRIER_NAME_2;
                                     else
                                          $trackertypes.=' and '.CARRIER_NAME_2;
                                }
                                if (($tracka_id3 != '' || $_POST[track_check3]) && (CARRIER_NAME_3 != ''))  {
                                     if ($trackertypes=='')
                                          $trackertypes=CARRIER_NAME_3;
                                     else
                                          $trackertypes.=' and '.CARRIER_NAME_3;
                                }
                                if (($tracka_id4 != '' || $_POST[track_check4]) && (CARRIER_NAME_4 != ''))  {
                                     if ($trackertypes=='')
                                          $trackertypes=CARRIER_NAME_4;
                                     else
                                          $trackertypes.=' and '.CARRIER_NAME_4;
                                }
                                if (($tracka_id5 != '' || $_POST[track_check5]) && (CARRIER_NAME_5 != ''))  {
                                     if ($trackertypes=='')
                                          $trackertypes=CARRIER_NAME_5;
                                     else
                                          $trackertypes.=' and '.CARRIER_NAME_5;
                                }
                                if ($trackertypes!='')
                                     $trackertypes=' by '.$trackertypes;
                                
//funkcja sprawdzajaca shipping complet
				function sprawdz($orders_id,$ilosc_checkboxow)
				{
				$query8=mysql_query("SELECT orders_id, item_shipped FROM ".TABLE_ORDERS_PRODUCTS." WHERE orders_id='$orders_id' AND item_shipped='0'")
				or die(mysql_error());
				$liczba_produktow=mysql_num_rows($query8);

				if($liczba_produktow==$ilosc_checkboxow)
							{
							return 1;
							}

					else{
					return 0;
					}

				}

//generowanie komentarza oraz statusu zamowienia gdy tracking jest kompletny
				if(sprawdz($orders_id, count($orders_products_id))==1)
					{
					$order_shipping_complete=1;
                                        if ($status_shippedorder != '' && $status_shippedorder != NULL)
					    $status=$status_shippedorder;
                                        else
                                            $status=$curstatusoforder;
					$comments = PO_FULLSHIP_COMMENTS."

The following items have shipped".$trackertypes.":
";

					}


				if(sprawdz($orders_id, count($orders_products_id))==0)
					{
					$order_shipping_complete=0;
					$status=$curstatusoforder;
					$comments = PO_PARTIALSHIP_COMMENTS."

The following items have shipped".$trackertypes.":
";   }
                                        $productlist = '';
					for($n=0;$n<count($orders_products_id); $n++)
					{
					$tmp=$orders_products_id[$n];

					$query6c=mysql_query("SELECT products_name, products_quantity FROM ".TABLE_ORDERS_PRODUCTS." WHERE orders_id='$orders_id' AND orders_products_id='$tmp'")
					or die(mysql_error());
					$row6c=mysql_fetch_array($query6c, MYSQL_NUM);

					if ($productlist != '' && count($orders_products_id) != 2)
                                             $productlist.=', ';
                                        if ($productlist != '' && count($orders_products_id) == 2)
                                             $productlist.=' ';
                                        if (count($orders_products_id) != 1 && $n==count($orders_products_id)-1)
                                             $productlist.='and ';
                                        if ($row6c[0] != '')
					     $productlist=$productlist.$row6c[1]." ".$row6c[0];
					}

                                        $comments.=$productlist;
					$comments=str_replace("'",'\'\'',$comments);
					


					for($k=0; $k<count($orders_products_id); $k++)
					{

							$tmp=$orders_products_id[$k];
							$query9=mysql_query("UPDATE ".TABLE_ORDERS_PRODUCTS." SET item_shipped=1 WHERE orders_products_id='$tmp' LIMIT 1")
							or die(mysql_error());

					}

					$query44=mysql_query("SELECT date_purchased, customers_name, customers_email_address, orders_status FROM ".TABLE_ORDERS." WHERE orders_id='$orders_id'")
					or die(mysql_error());
					$row44=mysql_fetch_array($query44, MYSQL_NUM);


					$query55=mysql_query("SELECT orders_status_name, orders_status_id FROM ".TABLE_ORDERS_STATUS." WHERE orders_status_id='$status'")
					or die(mysql_error());
					$row55=mysql_fetch_array($query55, MYSQL_NUM);

					$query66=mysql_query("SELECT configuration_value FROM ".TABLE_CONFIGURATION." WHERE configuration_key='STORE_NAME'")
					or die(mysql_error());
					$row66=mysql_fetch_array($query66, MYSQL_NUM);
					define('STORE_NAME',$row66[0]);

					$query66=mysql_query("SELECT configuration_value FROM ".TABLE_CONFIGURATION." WHERE configuration_key='EMAIL_FROM'")
					or die(mysql_error());
					$row66=mysql_fetch_array($query66, MYSQL_NUM);
					define('EMAIL_FROM',$row66[0]);


    				if($_POST[sendemail])
					{
					
					/* if($order_shipping_complete==1) { */
						$customer_notified=1;
					/* } else {
						$customer_notified=0; } */
					define('SEND_EMAILS', 'true');


					    $notify_comments = '';

						if ($comments != '') {
               			 	$notify_comments = EMAIL_TEXT_COMMENTS_UPDATE . $comments .  "\n\n";
									$order_comment = $comments;
              				}
if ($tracka_id1 != '') {
		  $notify_comments .= 'Your ' . CARRIER_NAME_1 . ' Tracking ID is ' . $tracka_id1 . "\n" . 'You can track your package at <a href="' . CARRIER_LINK_1 . $tracka_id1 . '">' . CARRIER_LINK_1 . $tracka_id1 . "</a>\n\n";
}
if ($tracka_id2 != '') {
		  $notify_comments .= 'Your ' . CARRIER_NAME_2 . ' Tracking ID is ' . $tracka_id2 . "\n" . 'You can track your package at <a href="' . CARRIER_LINK_2 . $tracka_id2 . '">' . CARRIER_LINK_2 . $tracka_id2 . "</a>\n\n";
}
if ($tracka_id3 != '') {
		  $notify_comments .= 'Your ' . CARRIER_NAME_3 . ' Tracking ID is ' . $tracka_id3 . "\n" . 'You can track your package at <a href="' . CARRIER_LINK_3 . $tracka_id3 . '">' . CARRIER_LINK_3 . $tracka_id3 . "</a>\n\n";
}
if ($tracka_id4 != '') {
		  $notify_comments .= 'Your ' . CARRIER_NAME_4 . ' Tracking ID is ' . $tracka_id4 . "\n" . 'You can track your package at <a href="' . CARRIER_LINK_4 . $tracka_id4 . '">' . CARRIER_LINK_4 . $tracka_id4 . "</a>\n\n";
		  }
if ($tracka_id5 != '') {
		  $notify_comments .= 'Your ' . CARRIER_NAME_5 . ' Tracking ID is ' . $tracka_id5 . "\n" . 'You can track your package at <a href="' . CARRIER_LINK_5 . $tracka_id5 . '">' . CARRIER_LINK_5 . $tracka_id5 . "</a>\n\n";
}
$message = 	STORE_NAME . "\n" . EMAIL_SEPARATOR . "\n" .
					 EMAIL_TEXT_ORDER_NUMBER . ' ' . $orders_id . "\n\n" .
					 EMAIL_TEXT_INVOICE_URL . ' <a href="' . HTTP_SERVER.DIR_WS_CATALOG. "/index.php?main_page=tracker&order_id=$orders_id" . '">' . HTTP_SERVER.DIR_WS_CATALOG. "/index.php?main_page=tracker&order_id=$orders_id" . "</a>\n\n" .
      			EMAIL_TEXT_DATE_ORDERED . ' ' . date("F j, Y",strtotime($row44[0])) . "\n\n" .
					$notify_comments .
      			EMAIL_TEXT_STATUS_UPDATED . sprintf(EMAIL_TEXT_STATUS_LABEL, $row55[0] ) .
     	 			EMAIL_TEXT_STATUS_PLEASE_REPLY;
$html_msg['EMAIL_MESSAGE_HTML'] = nl2br($message);
								// REMOVE THE TAGS FOR TEXT EMAIL
								$message = strip_tags($message);

								// SET THE TO EMAIL ADDRESS
								$email_to = $row44[2];

								// SET THE SUBJECT
								$subject = EMAIL_TEXT_SUBJECT . ' #' . $orders_id;

                                                                
								zen_mail($email_to, $email_to, $subject, $message, STORE_NAME, EMAIL_FROM, $html_msg, NULL);
						}else
						{
						$customer_notified=0;
						}

//wpisywanie odpowiednich danych do statusu
				$query555=mysql_query("INSERT INTO ".TABLE_ORDERS_STATUS_HISTORY."
				(orders_status_id, orders_id, date_added,
 					customer_notified, track_id1, track_id2, track_id3, track_id4, track_id5,comments)
  				 values ('$status','$orders_id',now(),'$customer_notified','$tracka_id1',
				  '$tracka_id2','$tracka_id3','$tracka_id4','$tracka_id5','$comments')")
				 or die(mysql_error());

if($order_shipping_complete==1)
{
 mysql_query("update " . TABLE_ORDERS . "
                        set orders_status = '". $status ."', last_modified
 =
 now()
                        where orders_id ='$orders_id'");



}
echo "<font class='tekst'>".SUBCONTRACTOR_TRACKING_THANKYOU."</font>";;



				}
				else{
                                if ($updatingesd)
                                     echo "<font class='tekst'>Thank you for updating the expected ship date.</font>";
                                else
				     echo "<font class='tekst'>".TRACK_SAVE_ERROR."</font>";
				}
	}
	}
	else{
		$x=$_GET[x];
		$y=$_GET[y];

//funkcja sprawdzajaca czy istnieje taki numer po i subkontraktor przyporzadkowany temu numerowi
		function ilosc($y, $x)
			{
			$query=mysql_query("SELECT po_number
			FROM ".TABLE_ORDERS_PRODUCTS." WHERE po_sent_to_subcontractor='$x' AND po_number='$y' AND item_shipped=0")
			or die(mysql_error());

			if(mysql_num_rows($query)>=1)
				{
				return 1;
				}
				else{
					return 0;
					}
			}

//funkcja oblsugujaca blad jezeli nic nie znajdzie
		function error($y, $x)
			{
			$query= mysql_query("SELECT po_number
			FROM ".TABLE_ORDERS_PRODUCTS." WHERE po_sent_to_subcontractor='$x' AND po_number='$y'")
			or die(mysql_error());

			if(mysql_num_rows($query)!=0)
				{
				return 1;
				} else
					{
					return 0;
					}
			}

//funkcja sprawdzajaca czy wszystkie dane zostaly juz zapisane jesli nie to pozwala na zapisanie trackingu
		function save($y, $x)
			{
			$query110a=mysql_query("SELECT po_number
			FROM ".TABLE_ORDERS_PRODUCTS." WHERE po_sent_to_subcontractor='$x' AND po_number='$y'")
			or die(mysql_error());

			$query110b=mysql_query("SELECT po_number
			FROM ".TABLE_ORDERS_PRODUCTS." WHERE po_sent_to_subcontractor='$x' AND po_number='$y' AND item_shipped=1")
			or die(mysql_error());

			if(mysql_num_rows($query110a)==mysql_num_rows($query110b))
				{
				return 0;
				}else
					{
					return 1;
					}
			}




//jezeli funckja ilosc() zwroci jedynke to bedzie wykonywany ponizszy kod
if(error($y, $x)==0)
{
echo "<font class='tekst'>".TRACKING_ERROR."</font>";;
}
else{
if(save($y, $x)==0)
{
echo "<font class='tekst'>".TRACKING_SAVING."</font>";;
}
}

if(error($y, $x)==1 AND save($y, $x)==1)
{
			if(ilosc($y, $x))
				{
				echo "<font class='tekst'>".SUBCONTRACTOR_FORM_DESCRIPTION."</font>";
				$i=0;
				?>
				<table width='950px' border="0" cellspacing="0" cellpadding="3">
					<tr>
					<td width=80% valign='top'>
						<table border="0" cellspacing="0" cellpadding="3">
						<tr>
							<td width='5%' align='center' class='td_naglowek'><font class='naglowki'><?php echo TRACKING_PO_NUMBER; ?></font></td>
							<td width='15%' align='center' class='td_naglowek'><font class='naglowki'><?php echo TRACKING_PO_DATE; ?></font></td>
							<td width='20%' align='center' class='td_naglowek'><font class='naglowki'><?php echo TRACKING_CUSTOMER_DATA;  ?></font></td>
							<td width='20%' align='center' class='td_naglowek_zak'><font class='naglowki'>Add Tracking ID</font></td>

						</tr>
				<?php

				$query2=mysql_query("SELECT po_number, po_sent_to_subcontractor, item_shipped, orders_id, po_date
				FROM ".TABLE_ORDERS_PRODUCTS." WHERE po_sent_to_subcontractor='$x' AND po_number='$y' AND item_shipped=0")
				or die(mysql_error());
				$row2=mysql_fetch_array($query2, MYSQL_NUM);


				$query3=mysql_query("SELECT delivery_name, delivery_company, delivery_street_address, delivery_city, delivery_postcode,
				delivery_state, delivery_country, delivery_suburb, customers_email_address
				FROM ".TABLE_ORDERS." WHERE orders_id='$row2[3]'")
				or die(mysql_error());
				$row3=mysql_fetch_array($query3, MYSQL_NUM);
if ($row3[7] == "" || $row3[7] == NULL)
	$orderaddresssuburb="";
else
	$orderaddresssuburb="<br />".$row3[7];
if ($row3[1] == "" || $row3[1] == NULL)
	$orderaddresscompany="";
else
	$orderaddresscompany=$row3[1]."<br />";
$ordersaddress = $row3[0]."<br />".$orderaddresscompany.$row3[2].$orderaddresssuburb."<br />".$row3[3].", ".$row3[5]." ".$row3[4]."<br />".$row3[6];
				?>
				<tr>
							<td width='5%' class='td'><font class='tekst'><?php echo $row2[3]."-".$row2[0]; ?></font></td>
							<td width='10%' class='td'><font class='tekst'><?php echo $row2[4]; ?></font></td>
							<td width='20%' class='td'><font class='tekst'><?php echo $ordersaddress; ?></font></td>
					<td class='td_zakonczenie'>
					<table border="0" cellspacing="0" cellpadding="3"><form name='save1' method='POST' action='confirm_track_sub.php'>

	       <tr>
	         <td><font class='tekst'><input type='checkbox' name='track_check1'><?php echo CARRIER_NAME_1; ?></font></td><td valign="top">

			 <?php echo "<input type='text' name='track_id1' class='sub'>"; ?>
			  </td>
	       </tr>
	       <tr>
	         <td><font class='tekst'><input type='checkbox' name='track_check2'><?php echo CARRIER_NAME_2; ?></font></td><td valign="top">

			 <?php echo "<input type='text' name='track_id2' class='sub'>"; ?>

			 </td>
	       </tr>
	       <tr>
	         <td><font class='tekst'><input type='checkbox' name='track_check3'><?php echo CARRIER_NAME_3; ?></font></td><td valign="top">
			 <?php echo "<input type='text' name='track_id3' class='sub'>"; ?>

			 </td>
	       </tr>
	       <tr>
	         <td><font class='tekst'><input type='checkbox' name='track_check4'><?php echo CARRIER_NAME_4; ?></font></td><td valign="top">
 			 <?php echo "<input type='text' name='track_id4' class='sub'>"; ?>
			 </td>
	       </tr>
	       <tr>
	         <td><font class='tekst'><input type='checkbox' name='track_check5'><?php echo CARRIER_NAME_5; ?></font></td><td valign="top">
 			 <?php echo "<input type='text' name='track_id5' class='sub'>"; ?>
			 </td>
	       </tr>
	       </table>
					</td>
						</tr>

</table>
<center>----- <b>OR</b> -----</center>
<table width='950px' border="0" cellspacing="0" cellpadding="3">
					
						
						<tr>
							<td width='80%' align='center' class='td_naglowek_zak'><font class='naglowki'>Enter or Change Expected Ship Date</font></td>

						</tr>
<tr>
							
							<td width='80%' align='center' class='td_zakonczenie'><font class='tekst'>Expected Ship Date or Date Range:&nbsp;&nbsp;&nbsp;
<?php echo "<input type='date' name='expected_ship' class='sub'>"; ?>
<?php
if (PO_ED_CUSTOM_COMMENTS_ONE != '' && $_GET['owner']==$ownercode)
     echo '&nbsp;&nbsp;&nbsp;<input type="checkbox" name="chk" value="'.PO_ED_CUSTOM_COMMENTS_ONE.'" id="chk0" onclick="getVal(this)">'.PO_ED_CUSTOM_SHORT_NAME_ONE;
if (PO_ED_CUSTOM_COMMENTS_TWO != '' && $_GET['owner']==$ownercode)
     echo '&nbsp;&nbsp;&nbsp;<input type="checkbox" name="chk" value="'.PO_ED_CUSTOM_COMMENTS_TWO.'" id="chk1" onclick="getVal(this)">'.PO_ED_CUSTOM_SHORT_NAME_TWO;
if (PO_ED_CUSTOM_COMMENTS_THREE != '' && $_GET['owner']==$ownercode)
     echo '&nbsp;&nbsp;&nbsp;<input type="checkbox" name="chk" value="'.PO_ED_CUSTOM_COMMENTS_THREE.'" id="chk2" onclick="getVal(this)">'.PO_ED_CUSTOM_SHORT_NAME_THREE;
if ($_GET['owner']==$ownercode) {
?>
<br /><table><tr><td><font class='tekst'>Message to Customer:&nbsp;&nbsp;&nbsp;</font></td><td>
<textarea name='es_message' rows='1' cols='125' class='sub' wrap='virtual' id='inp0' style='overflow:hidden;'>
<?php echo PO_ED_COMMENTS; ?>
</textarea></td></tr></table>
<?php /* echo "<input type='text' name='es_message' size='125'  value='".PO_ED_COMMENTS."'>"; */ ?></font></td></tr></table>
<?php } else { ?>
<?php echo "<input type='hidden' name='es_message' class='sub' id='inp0' value='".PO_ED_COMMENTS."'>"; ?></font></td></tr></table>
<?php } ?>
<br><table border="0" cellspacing="0" cellpadding="3">
<tr><td class='td_naglowek'><font class='naglowki'><?php echo SEND_TRACKING_YES_NO; ?></font></td>
<td align="center" class='td_naglowek'><font class='naglowki'><?php  echo TRACKING_PRODUCT_NAME; ?></font></td>
<td align="center" class='td_naglowek_zak'><font class='naglowki'>Expected Delivery Date</font></td></tr>


				<?php
				$query5=mysql_query("SELECT orders_id, products_name, orders_products_id, expected_date
				FROM ".TABLE_ORDERS_PRODUCTS." WHERE po_sent_to_subcontractor='$x' AND po_number='$y' AND item_shipped=0")
				or die(mysql_error());
				$i='0';
				while($row5=mysql_fetch_array($query5, MYSQL_NUM))
					{

					$query6=mysql_query("SELECT orders_id, orders_products_id, products_options, products_options_values
													  FROM ".TABLE_ORDERS_PRODUCTS_ATTRIBUTES."
													  WHERE orders_products_id='$row5[2]' AND orders_id='$row5[0]'")
													  or die(mysql_error());

					$attributes='';
					while($row6=mysql_fetch_array($query6, MYSQL_NUM))
								{
								$attributes=$attributes.$row6[2].": ".$row6[3]."<br>";

								}


					?>
					<tr><td align="center" class='td'><input type='checkbox' name='<?php echo "orders_products_id_". $i ?>' value='<?php echo $row5[2]; ?>' CHECKED></td>
					<td class='td'><font class='tekst'><?php  echo $row5[1]."<br>".$attributes; ?></font></td>
                                        <td align='center' class='td_zakonczenie'><font class='tekst'><?php if ($row5[3]=='') echo 'NONE'; else echo $row5[3]; ?></font></td></tr>
					<?php
					echo "<input type='hidden' name='orders_id_$i' value='$row5[0]'>";
					$i++;
					  }
					  echo "<input type='hidden' name='ile' value='$i'>";
					?>
					<tr><td COLSPAN="2" align='center'><input type='hidden' name='save' value='save'><input type='hidden' name='owner' value='<?php echo $_GET['owner']; ?>'><input type='hidden' name='sorder' value='<?php echo $_GET['sorder']; ?>'><input type='hidden' name='a' value='<?php echo $_GET['a']; ?>'>
<?php
if ($_GET['owner']==$ownercode) {
     echo "Email Customer at ".$row3[8]."&nbsp;<input type='checkbox' name='sendemail' ";
     if (PO_NOTIFY==1)
          echo "CHECKED />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
     else
          echo "/>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
} else {
     echo "<input type='hidden' name='sendemail' ";
     if (PO_NOTIFY==1)
          echo "value=true />";
     else
          echo "value=false />";
}
?>
<input type="image" src="<?php echo $admin_dir; ?>/includes/languages/english/images/buttons/button_save.gif"  ONCLICK="javascript:document.save1.submit();"></td></tr>

</table></form>

					 <?php
					}

}}
}
?>