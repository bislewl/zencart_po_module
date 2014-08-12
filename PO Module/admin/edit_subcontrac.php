<?php
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
<!-- body //-->
<table border="0" width='100%' cellspacing="0" cellpadding="0">
<?php
if ($_GET['scchanges']) // Subcontractor PO Changes Page
{  
   $subcid = $_GET['scchanges']-1;

   // Update Page
   if ($_POST[updateit] == 'yes')
   { 
      if ($subcid != 99998) {
          $updateemailtitle = $_POST[emailtitle];
          $result=mysql_query("UPDATE ".TABLE_SUBCONTRACTORS." SET email_title='$updateemailtitle' WHERE subcontractors_id=$subcid LIMIT 1")	or die("Failed to connect database: 5");
      }
      for ($i=1;$i<4;$i++) {
         $countallname = 'countall' . $i;
         $countall = $_POST[$countallname];
         $updatestring = '';
         for ($r=0;$r<$countall;$r++) {
            if ( $r&1 ) 
               $ckr = $r-1;
            else
               $ckr = $r+1;
            $textname = "repstr" . $i . $r;
            $textnamepartner = "repstr" . $i . $ckr;
            if (stripslashes($_POST[$textname]) == '' && stripslashes($_POST[$textnamepartner]) == '') {
               // do nothing to remove
            } else {
               if (($r==0) || ($r==1 && $_POST[$textnamepartner] == '' && $_POST[$textname] == '')) {
                  // do nothing
               } else {
                  $updatestring .= "§";
               }
               $useone = stripslashes($_POST[$textname]);
               $updatestring .= addslashes($useone);      
            }
         }
         $textnamenew1 = "repstr" . $i . $countall;
         $countallonemore = $countall + 1;
         $textnamenew2 = "repstr" . $i . $countallonemore;
         if ($_POST[$textnamenew1] != '' || $_POST[$textnamenew2] != '') {
            if ($countall != 0)
               $updatestring .= '§';
            $useone = stripslashes($_POST[$textnamenew1]);
            $usetwo = stripslashes($_POST[$textnamenew2]);
            $updatestring .= addslashes($useone) . '§' . addslashes($usetwo); 
         }
         switch ($i) {
         case "1": $replacing="replace_both"; break;
         case "2": $replacing="replace_email"; break;
         case "3": $replacing="replace_tf"; break;
         }
         if ($subcid==99998)
         {
            switch ($i) {
            case "1": $result=mysql_query("UPDATE ".TABLE_CONFIGURATION." SET configuration_value='$updatestring' WHERE configuration_key='PO_REPLACE_BOTH' LIMIT 1") or die("Failed to connect database: 5"); break;
            case "2": $result=mysql_query("UPDATE ".TABLE_CONFIGURATION." SET configuration_value='$updatestring' WHERE configuration_key='PO_REPLACE_EMAIL' LIMIT 1") or die("Failed to connect database: 5"); break;
            case "3": $result=mysql_query("UPDATE ".TABLE_CONFIGURATION." SET configuration_value='$updatestring' WHERE configuration_key='PO_REPLACE_TF' LIMIT 1") or die("Failed to connect database: 5"); break;
            }
         } else {
            $result=mysql_query("UPDATE ".TABLE_SUBCONTRACTORS." SET $replacing='$updatestring' WHERE subcontractors_id=$subcid LIMIT 1")	or die("Failed to connect database: 5");
         }
      }
   }
         


   // Display Page
   if ($subcid==99998)
   {   
       $row[0] = 'Everyone';
       $query1=mysql_query("SELECT configuration_value FROM ".TABLE_CONFIGURATION." WHERE configuration_key='PO_REPLACE_BOTH'")
											or die("Nie mozna polaczyc");
       $row1=mysql_fetch_array($query1, MYSQL_NUM);
       $row[1] = $row1[0];
       $query2=mysql_query("SELECT configuration_value FROM ".TABLE_CONFIGURATION." WHERE configuration_key='PO_REPLACE_EMAIL'")
											or die("Nie mozna polaczyc");
       $row2=mysql_fetch_array($query2, MYSQL_NUM);
       $row[2] = $row2[0];
       $query3=mysql_query("SELECT configuration_value, configuration_group_id FROM ".TABLE_CONFIGURATION." WHERE configuration_key='PO_REPLACE_TF'")
											or die("Nie mozna polaczyc");
       $row3=mysql_fetch_array($query3, MYSQL_NUM);
       $row[3] = $row3[0];
       $row[4] = $row3[1];
   } else {
       $query=mysql_query("SELECT full_name, replace_both, replace_email, replace_tf, email_title FROM ".TABLE_SUBCONTRACTORS." WHERE subcontractors_id=$subcid")
											or die("Nie mozna polaczyc");
       $row=mysql_fetch_array($query, MYSQL_NUM);
   }
   ?><tr><td class="pageHeading" colspan="2"><br><?php  echo HEADING_TITLE_EDIT_SUBCONTRACTORS . " -> Make Changes to ". $row[0] ."'s POs"; ?><br><br></td></tr>
           <tr><td width="33%" valign="top" align="center"><strong>Replace Both Email and Text File</strong></td>
<td width="33%" valign="top" align="center"><strong>Replace in Email Only</strong></td>
<td width="33%" valign="top" align="center"><strong>Replace in Text File Only</strong></td></tr></table><table border="0" width='100%' cellspacing="0" cellpadding="0"><tr>
<form name="update" action="edit_subcontrac.php?scchanges=<?php echo $subcid + 1; ?>" method="POST">
   <?php
   for ($i=1;$i<4;$i++) {
      switch ($i) {
        case "1": $replacing=explode('§', $row[1]); break;
        case "2": $replacing=explode('§', $row[2]); break;
        case "3": $replacing=explode('§', $row[3]); break;
      }
      echo "<td width='16.66%' valign='top' align='center'>";
      echo "Find<br /><br />";
      $countall = count($replacing);
      if ($countall == 1)
         $countall--;
      $countallname = "countall" . $i;
      echo '<input type="hidden" name="updateit" value="yes"><input type="hidden" name="'.$countallname.'" value="'.$countall.'">';
      for ($r=0;$r<$countall;$r++) {
        $textname = "repstr" . $i . $r;
        /* $replacing[$r] = stripslashes($replacing[$r]); */
     $replacing[$r] = htmlentities($replacing[$r],ENT_QUOTES);
        echo '<input type="text" name="'.$textname.'" value="'.$replacing[$r].'"><br />';
        $r++;
      }
      $textname = "repstr" . $i . $countall;
      echo '<input type="text" name="'.$textname.'">';
      echo "</td><td width='16.66%' valign='top' align='center'>";
      echo "Replace With<br /><br />";
      for ($r=1;$r<count($replacing);$r++) {
        $textname = "repstr" . $i . $r;
      /*  $replacing[$r] = stripslashes($replacing[$r]); */
       $replacing[$r] = htmlentities($replacing[$r],ENT_QUOTES);
        echo '<input type="text" name="'.$textname.'" value="'.$replacing[$r].'"><br />';
        $r++;
      }
      $countall++;
      $textname = "repstr" . $i . $countall;
      echo '<input type="text" name="'.$textname.'">';
      echo "</td>";
   }
   echo '</tr></table>';
   if ($subcid!=99998)
        echo '<table border="0" width="100%" cellspacing="0" cellpadding="0"><tr><td valign="top" align="center"><br /><br />Email Title - Leave Blank For Default<br /><input type="text" name="emailtitle" value="'.$row[4].'" size="120"></td></tr></table>';
   echo '<table border="0" width="100%" cellspacing="0" cellpadding="0"><tr><td valign="top" align="center"><br /><br /><input type="image" src="includes/languages/english/images/buttons/button_save.gif"  ONCLICK="javascript:document.update.submit();"></form><br /><br /><br />';
   if ($subcid==99998)
       echo '<a href="configuration.php?gID=' . $row[4] . '">Go Back to Configuration</a></td></tr>';
   else
       echo '<a href="edit_subcontrac.php?cID='.$subcid.'&action=edit">Go Back to Edit Subcontractors</a></td></tr>';
   
   
} else { // Regular Edit Page
?>
<!-- body_text //-->
<?php
//delete
// pobieranie rozkazu i wykannie go
if(isset($_GET['what']) AND $_GET['what']=='delete')
{
$did=$_GET['did'];
$result=mysql_query("DELETE FROM ".TABLE_SUBCONTRACTORS." WHERE subcontractors_id='$did' LIMIT 1")
or die("Nie mozna usunac danych z bazy");
}

//save
if(isset($_GET['what']) AND $_GET['what']=='save')
{

$sid=$_GET['sid'];
$result=mysql_query("UPDATE ".TABLE_SUBCONTRACTORS." SET WHERE subcontractors_id='$sid' LIMIT 1")
or die("Nie mozna poprawic bazy");


}


//insert - pobieranie danych dla polecenia inser oraz wykannaie go
if(isset($_GET['pole']) AND  $_GET['pole']==1)
{
$short_name=$_GET['short_name'];
$full_name=$_GET['full_name'];
$street=$_GET['street'];
$zip=$_GET['zip'];
$city=$_GET['city'];
$state=$_GET['state'];
$telephone=$_GET['telephone'];
$contact_person=$_GET['contact_person'];
$e_mail=$_GET['e_mail'];
$sendpdf=$_GET['sendpdf'];
$sendtf=$_GET['sendtf'];
$pdffilename=$_GET['pdffilename'];
$tffilename=$_GET['tffilename'];
$tfmimetype=$_GET['tfmimetype'];

$result=mysql_query("INSERT INTO ".TABLE_SUBCONTRACTORS."(short_name, full_name, street1, zip, email_address, telephone, contact_person, city, state, sendpdf, sendtf, replace_both, replace_email, replace_tf, email_title, pdffilename, tffilename, tfmimetype)
VALUES('$short_name','$full_name','$street','$zip','$e_mail','$telephone','$contact_person', '$city', '$state', '$sendpdf', '$sendtf', '', '', '', '', '$pdffilename', '$tffilename', '$tfmimetype')")
or die("Nie mozna ustawic nowych rekordow1");
echo "<meta http-equiv=\"refresh\" content=\"0 url=edit_subcontrac.php\">";
}

//save

if(isset($_GET['pole']) AND  $_GET['pole']==0)
{
$short_name=$_GET['short_name'];
$full_name=$_GET['full_name'];
$street=$_GET['street'];
$zip=$_GET['zip'];
$city=$_GET['city'];
$state=$_GET['state'];
$telephone=$_GET['telephone'];
$contact_person=$_GET['contact_person'];
$e_mail=$_GET['e_mail'];
$key=$_GET['key'];
$sendpdf=$_GET['sendpdf'];
$sendtf=$_GET['sendtf'];
$pdffilename=$_GET['pdffilename'];
$tffilename=$_GET['tffilename'];
$tfmimetype=$_GET['tfmimetype'];

$result=mysql_query("UPDATE ".TABLE_SUBCONTRACTORS." SET short_name='$short_name', full_name='$full_name',
 street1='$street', zip='$zip', city='$city', state='$state', email_address='$e_mail', telephone='$telephone', contact_person='$contact_person', sendpdf='$sendpdf', sendtf='$sendtf', pdffilename='$pdffilename', tffilename='$tffilename', tfmimetype='$tfmimetype'
 WHERE subcontractors_id='$key' LIMIT 1")
or die("Nie mozna ustawic nowych rekordow2");
}


//ustawianie zmiennej w celu sortowania danych w odpowiednie sposob
if(isset($_GET['list_order']))
{
	if($_GET['list_order']=='firstname') $disp_order = "short_name ASC";
	if($_GET['list_order']=='firstnamedesc') $disp_order = "short_name DESC";
	if($_GET['list_order']=='lastname') $disp_order = "full_name ASC";
	if($_GET['list_order']=='lastnamedesc') $disp_order = "full_name DESC";
	if($_GET['list_order']=='company') $disp_order = "street1 ASC";
	if($_GET['list_order']=='companydesc') $disp_order = "street1 DESC";
	if($_GET['list_order']=='email') $disp_order = "email_address ASC";
	if($_GET['list_order']=='emaildesc') $disp_order = "email_address DESC";
	if($_GET['list_order']=='zip') $disp_order = " city_state_zip ASC";
	if($_GET['list_order']=='zipdesc') $disp_order = "city_state_zip DESC";
	if($_GET['list_order']=='telephone') $disp_order = "telephone ASC";
	if($_GET['list_order']=='telephonedesc') $disp_order = "telephone DESC";
	if($_GET['list_order']=='person') $disp_order = "contact_person ASC";
	if($_GET['list_order']=='persondesc') $disp_order = "contact_person DESC";
}else
{

$disp_order = "subcontractors_id ASC";
}



// ustawianie linkow dla naglowkow szablonu ktore pozwalaja na sortowanie kolumn


?><tr><td class="pageHeading" colspan="2"><br><?php  echo HEADING_TITLE_EDIT_SUBCONTRACTORS; ?><br><br></td></tr>
           <tr>  <td valign="top" width='80%'>
		   <table border="0" width='100%' cellspacing="0" cellpadding="0">
              <tr class="dataTableHeadingRow">
                <td width='3%' class="dataTableHeadingContent" align="center" valign="top">
                  <?php  echo ID; ?>
                </td>
                <td width='14%' class="dataTableHeadingContent" align="center">
                  <?php echo TABLE_HEADING_SHORTNAME;  ?><br>
                  <a href="<?php echo zen_href_link(basename($PHP_SELF) . '?list_order=firstname'); ?>"><?php echo ($_GET['list_order']=='firstname' ? '<span class="SortOrderHeader">Asc</span>' : '<span class="SortOrderHeaderLink">Asc</b>'); ?></a>&nbsp;
                  <a href="<?php echo zen_href_link(basename($PHP_SELF) . '?list_order=firstnamedesc'); ?>"><?php echo ($_GET['list_order']=='firstnamedesc' ? '<span class="SortOrderHeader">Desc</span>' : '<span class="SortOrderHeaderLink">Desc</b>'); ?></a>
                </td>
                <td width='14%' class="dataTableHeadingContent" align="center">
                  <?php echo TABLE_HEADING_FULLNAME; ?><br>
				  <a href="<?php echo zen_href_link(basename($PHP_SELF) . '?list_order=lastname'); ?>"><?php echo ($_GET['list_order']=='lastname' ? '<span class="SortOrderHeader">Asc</span>' : '<span class="SortOrderHeaderLink">Asc</b>'); ?></a>&nbsp;
                  <a href="<?php echo zen_href_link(basename($PHP_SELF) . '?list_order=lastnamedesc'); ?>"><?php echo ($_GET['list_order']=='lastnamedesc' ? '<span class="SortOrderHeader">Desc</span>' : '<span class="SortOrderHeaderLink">Desc</b>'); ?></a>
                </td>
               	<td width='14%' class="dataTableHeadingContent" align="center">
                  <?php echo TABLE_HEADING_EMAIL; ?><br>
				  <a href="<?php echo zen_href_link(basename($PHP_SELF) . '?list_order=email'); ?>"><?php echo ($_GET['list_order']=='email' ? '<span class="SortOrderHeader">Asc</span>' : '<span class="SortOrderHeaderLink">Asc</b>'); ?></a>&nbsp;
                  <a href="<?php echo zen_href_link(basename($PHP_SELF) . '?list_order=emaildesc'); ?>"><?php echo ($_GET['list_order']=='emaildesc' ? '<span class="SortOrderHeader">Desc</span>' : '<span class="SortOrderHeaderLink">Desc</b>'); ?></a>
                </td>

                <td width='14%' class="dataTableHeadingContent" align="center">
                  <?php echo TABLE_HEADING_TELEPHONE; ?><br>
				  <a href="<?php echo zen_href_link(basename($PHP_SELF) . '?list_order=telephone'); ?>"><?php echo ($_GET['list_order']=='telephone' ? '<span class="SortOrderHeader">Asc</span>' : '<span class="SortOrderHeaderLink">Asc</b>'); ?></a>&nbsp;
                  <a href="<?php echo zen_href_link(basename($PHP_SELF) . '?list_order=telephonedesc'); ?>"><?php echo ($_GET['list_order']=='telephonedesc' ? '<span class="SortOrderHeader">Desc</span>' : '<span class="SortOrderHeaderLink">Desc</b>'); ?></a>
                </td>

                <td width='14%' class="dataTableHeadingContent" align="center">
                  <?php echo TABLE_CONTACT_PERSON; ?><br>
				  <a href="<?php echo zen_href_link(basename($PHP_SELF) . '?list_order=person'); ?>"><?php echo ($_GET['list_order']=='person' ? '<span class="SortOrderHeader">Asc</span>' : '<span class="SortOrderHeaderLink">Asc</b>'); ?></a>&nbsp;
                  <a href="<?php echo zen_href_link(basename($PHP_SELF) . '?list_order=persondesc'); ?>"><?php echo ($_GET['list_order']=='persondesc' ? '<span class="SortOrderHeader">Desc</span>' : '<span class="SortOrderHeaderLink">Desc</b>'); ?></a>
                </td>


                <td width='15%' class="dataTableHeadingContent" align="right"><?php echo ACTION; ?>&nbsp;<br>

              </tr>



					<?php

					$cid=$_GET['cID'];
	if($cid=='')
	{
	$query2=mysql_query("SELECT subcontractors_id, short_name,  full_name, email_address,  telephone, contact_person FROM ".TABLE_SUBCONTRACTORS." LIMIT 1")
		or die("Nie mozna sie polaczyc z baza danych");

	$row2=mysql_fetch_array($query2, MYSQL_NUM);
	$cid=$row2[0];
	}
					$query=mysql_query("SELECT subcontractors_id, short_name,  full_name, email_address,  telephone, contact_person	FROM ".TABLE_SUBCONTRACTORS." order by $disp_order")
											or die("Nie mozna polaczyc");
											$k=0;
					while($row=mysql_fetch_array($query, MYSQL_NUM))
					{
					if($cid!=$row[0])
					{
 echo '<tr class="dataTableRow" onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)" onclick="document.location.href=\'' . zen_href_link(edit_subcontrac, zen_get_all_get_params(array('cID', 'action')) . 'cID=' . $row[0] . '&action=edit', 'NONSSL') . '\'">' . "\n";
 					}
					else{
 echo '<tr id="defaultSelected" class="dataTableRowSelected" onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)" onclick="document.location.href=\'' . zen_href_link(edit_subcontrac, zen_get_all_get_params(array('cID', 'action')) . 'cID=' . $row[0] . '&action=edit', 'NONSSL') . '\'">' . "\n";


					}

						for($i=0; $i<count($row); $i++)
						{

						echo "<td align='center'>$row[$i]</td>";
						}
						if($k=='0')
						{
						$fond[0]=$row[0];
						$fond[1]=$row[1];
						}
						$k++;

									?>
					<td align="right"> <?php if($cid!=$row[0])

					{  ?>
					<img src="images/icon_info.gif" border="0" alt="Info" title=" Info ">
					<?php } else
					{ ?>
					<img src="images/icon_arrow_right.gif" border="0" alt="">
					<?php
					}
					?>

					</td></tr>
				<?php	}?>


        </table>
		</td>
		<td valign="top" >

		<table border="0" width='100%' cellspacing="0" cellpadding="2" align="center">
		<tr>
		<?php
$query2=mysql_query("SELECT * FROM ".TABLE_SUBCONTRACTORS." WHERE subcontractors_id='$cid'")
or die("Nie mozna sie polaczyc z baza danych");
$row2=mysql_fetch_array($query2, MYSQL_NUM);

// projekt szablonu do wyswietlania subcontracotow oraz wyswietlanie ich
?>
<td colspan="2" width='' class="infoBoxHeading">
<?php echo "ID:$row2[0] Full name:$row2[2] "; ?>
</td>
</tr>

		<form name='form1' action="edit_subcontrac.php" METHOD="get">
		<tr>
		<td align="right" class="infoBoxContent"><?php echo TABLE_HEADING_SHORTNAME; ?></td>
		<td width='75%' align="left" class="infoBoxContent"><input type='text' name="short_name" value="<?php echo $row2[1]; ?>"></td>
		</tr>
		<tr>
		<td align="right" class="infoBoxContent"><?php echo TABLE_HEADING_FULLNAME; ?></td>
		<td align="left" class="infoBoxContent"><input type='text' name="full_name" value="<?php echo $row2[2]; ?>"></td>
		</tr>
		<tr>
		<td align="right" class="infoBoxContent"><?php echo TABLE_HEADING_STREET; ?></td>
		<td align="left" class="infoBoxContent"><input type='text' name="street" value="<?php echo $row2[3]; ?>"></td>
		</tr>
		<tr>
		<td align="right" class="infoBoxContent"><?php echo TABLE_HEADING_CITY; ?></td></td>
		<td align="left" class="infoBoxContent"><input type='text' name="city" value="<?php echo $row2[4]; ?>"></td>
		</tr>
		<tr>
		<td align="right" class="infoBoxContent"><?php echo TABLE_HEADING_STATE; ?></td></td>
		<td align="left" class="infoBoxContent"><input type='text' name="state" value="<?php echo $row2[5]; ?>"></td>
		</tr>
		<tr>
		<td align="right" class="infoBoxContent"><?php echo TABLE_HEADING_ZIP; ?></td></td>
		<td align="left" class="infoBoxContent"><input type='text' name="zip" value="<?php echo $row2[6]; ?>"></td>
		</tr>
		<tr>
		<td align="right" class="infoBoxContent"><?php echo TABLE_HEADING_EMAIL; ?></td>
		<td align="left" class="infoBoxContent"><input type='text' name="e_mail" value="<?php echo $row2[7]; ?>"></td>
		</tr>
		<tr>
		<td align="right" class="infoBoxContent"><?php echo TABLE_HEADING_TELEPHONE; ?></td>
		<td align="left" class="infoBoxContent"><input type='text' name="telephone" value="<?php echo $row2[8]; ?>"></td>
		</tr>
		<tr>
		<td align="right" class="infoBoxContent"><?php echo TABLE_CONTACT_PERSON; ?></td>
		<td align="left" class="infoBoxContent"><input type='text' name="contact_person" value="<?php echo $row2[9]; ?>"></td>
		</tr>
                <tr>
		<td align="right" class="infoBoxContent"><?php echo TABLE_SEND_PDF_SUBDEF; ?></td>
		<td align="left" class="infoBoxContent"><input type='text' name="sendpdf" value="<?php echo $row2[10]; ?>"></td>
		</tr>
                <tr>
		<td align="right" class="infoBoxContent"><?php echo TABLE_FILENAME_OF_PDF_ATTACHMENT; ?></td>
		<td align="left" class="infoBoxContent"><input type='text' name="pdffilename" value="<?php echo $row2[16]; ?>"></td>
		</tr>
                <tr>
		<td align="right" class="infoBoxContent"><?php echo TABLE_SEND_TEXT_SUBDEF; ?></td>
		<td align="left" class="infoBoxContent"><input type='text' name="sendtf" value="<?php echo $row2[11]; ?>"></td>
		</tr>
                <tr>
		<td align="right" class="infoBoxContent"><?php echo TABLE_FILENAME_OF_TF_ATTACHMENT; ?></td>
		<td align="left" class="infoBoxContent"><input type='text' name="tffilename" value="<?php echo $row2[17]; ?>"></td>
		</tr>
                <tr>
		<td align="right" class="infoBoxContent"><?php echo TABLE_MIMETYPE_OF_TF_ATTACHMENT; ?></td>
		<td align="left" class="infoBoxContent"><input type='text' name="tfmimetype" value="<?php echo $row2[18]; ?>"></td>
		</tr><input type="hidden" name="pole"><input type='hidden' name="key" value="<?php echo $row2[0]; ?>">
<tr>
<td colspan="2" class="infoBoxContent"><br />
<a href="edit_subcontrac.php?scchanges=<?php echo $row2[0]+1; ?>">Make Changes to Subcontractor's POs</a><br /><br />
<input type="image" src="includes/languages/english/images/buttons/button_insert.gif" name='insert' ONCLICK="javascript: document.form1.pole.value=1;document.form1.submit();">
<input type="image" src="includes/languages/english/images/buttons/button_save.gif"  ONCLICK="javascript:document.form1.pole.value=0;document.form1.submit();">
<a href="edit_subcontrac.php?what=delete&did=<?php echo $row2[0]; ?>"><img src="includes/languages/english/images/buttons/button_delete.gif" border="0" alt="Delete" title=" Delete "></a>
</form>

</td>
</tr>

		</table>
		</td>

      </tr>
<?php } ?>
</table>

<?php require(DIR_WS_INCLUDES . 'footer.php'); ?>
<!-- footer_eof //-->
<br>
</body>
</html>
<?php require(DIR_WS_INCLUDES . 'application_bottom.php'); ?>