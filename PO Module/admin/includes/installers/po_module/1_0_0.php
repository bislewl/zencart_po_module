<?php

$db->Execute("DROP TABLE IF EXISTS ".TABLE_SUBCONTRACTORS.";");

$db->Execute("CREATE TABLE ".TABLE_SUBCONTRACTORS." (
  `subcontractors_id` int(10) unsigned NOT NULL auto_increment,
  `short_name` varchar(20) NOT NULL default '',
  `full_name` varchar(100) NOT NULL default '',
  `street1` varchar(100) NOT NULL default '',
  `city` varchar(255) NOT NULL default '',
  `state` varchar(255) NOT NULL default '',
  `zip` varchar(10) NOT NULL default '',
  `email_address` varchar(100) NOT NULL default '',
  `telephone` varchar(32) NOT NULL default '',
  `contact_person` varchar(100) NOT NULL default '',
  `sendpdf` varchar(10) NOT NULL default 'yes',
  `sendtf` varchar(10) NOT NULL default 'no',
  `replace_both` text NOT NULL default '',
  `replace_email` text NOT NULL default '',
  `replace_tf` text NOT NULL default '',
  `email_title` text NOT NULL default '',
  `pdffilename` varchar(100) NOT NULL default '',
  `tffilename` varchar(100) NOT NULL default '',
  `tfmimetype` varchar(100) NOT NULL default '',
  PRIMARY KEY  (`subcontractors_id`)
) COMMENT='subcontractors' AUTO_INCREMENT=0;");

$db->Execute("UPDATE ".TABLE_SUBCONTRACTORS." SET subcontractors_id=0 where short_name='ownstock';");


$db->Execute("ALTER TABLE ".TABLE_ORDERS_PRODUCTS."
ADD `po_sent` char(1) NOT NULL default '0',
ADD `po_number` int(20),
ADD `po_sent_to_subcontractor` int(10),
ADD `po_date` DATE,
ADD `item_shipped` CHAR(1) NOT NULL default '0',
ADD `expected_date` varchar(100) NOT NULL default '',
ADD `checked_status` varchar(300) NOT NULL default '';");

$db->Execute("ALTER TABLE ".TABLE_PRODUCTS."
ADD `default_subcontractor` int(10) NOT NULL default '0';");

$db->Execute("ALTER TABLE ".TABLE_MANUFACTURERS."
ADD `default_subcontractor` int(10) NOT NULL default '0';");

$db->Execute("INSERT INTO ".TABLE_CONFIGURATION_GROUP." VALUES ('', 'Purchase Orders', 'Purchase Order Settings', '1', '1');");

$config_group = $db->Execute("SELECT configuration_group_id FROM ".TABLE_CONFIGURATION_GROUP."
WHERE configuration_group_title= 'Purchase Orders'");
$configuration_group_id = $config_group->fields['configuration_group_id'];

$db->Execute("DELETE FROM ".TABLE_CONFIGURATION." WHERE configuration_key='PO_OWN_STOCK_EMAIL';");
$db->Execute("DELETE FROM ".TABLE_CONFIGURATION." WHERE configuration_key='PO_NOTIFY';");
$db->Execute("DELETE FROM ".TABLE_CONFIGURATION." WHERE configuration_key='PO_SUBJECT';");

$db->Execute("INSERT INTO ".TABLE_CONFIGURATION." (`configuration_id`, `configuration_title`, `configuration_key`, `configuration_value`, `configuration_description`, `configuration_group_id`, `sort_order`, `last_modified`, `date_added`, `use_function`, `set_function`) 
    VALUES 	('','PO - send pdf packing lists', 'PO_SEND_PACKING_LISTS', '4', '0 - never, 1 - always, 2 - sometimes (default yes), 3 - sometimes (default no), 4 - use subcontractor default', ".$configuration_group_id.", 80, now(), now(), NULL, NULL),
                                        ('','PO - send text file attachments', 'PO_SEND_TEXT_FILE', '4', '0 - never, 1 - always, 2 - sometimes (default yes), 3 - sometimes (default no), 4 - use subcontractor default', ".$configuration_group_id.", 85, now(), now(), NULL, NULL),
                                        ('','PO - only use manufacturers default subcontractor', 'PO_MANUFACTURER_SC', '0', 'Purchase orders uses the product default subcontractor.  Setting this to 1 will cause purchase orders to update the product default subcontractors with the manufacturer default subcontractors everytime the send purhcase orders page is opened or an automatic PO is sent.  Only set this to 1 if you ALWAYS use the default manufacturer subcontractor instead of the default product subcontractor.  0 - no, 1 - yes', ".$configuration_group_id.", 90, now(), now(), NULL, NULL),
                                        ('','PO - max display send po screen', 'PO_MAX_SEND', '100', 'maximum number of products to show on send po screen', ".$configuration_group_id.", 95, now(), now(), NULL, NULL),
                                        ('','PO - max display enter tracking screen', 'PO_MAX_TRACK', '100', 'maximum number of pos to show on enter tracking screen', ".$configuration_group_id.", 96, now(), now(), NULL, NULL),
                                        ('','PO - max display default subcontractor screen', 'PO_MAX_DEFAULT_SC', '20', 'maximum number of products or manufacturers to show on default subcontractor screen', ".$configuration_group_id.", 97, now(), now(), NULL, NULL),
					('','PO - notify customer', 'PO_NOTIFY', '1', '0 - no customer notification of PO updates, 1 - notify customer', ".$configuration_group_id.", 102, now(), now(), NULL, NULL),
					('','PO - subject', 'PO_SUBJECT', '{contact_person}: New order (#{po_number}) for {full_name}', 'Subject of PO emails, {po_number} will be replaced with the actual number', ".$configuration_group_id.", 103, now(), now(), NULL, NULL),
					('','PO - from email name', 'PO_FROM_EMAIL_NAME', 'PurchaseOrderManager', 'The FROM email NAME for sent Purchase Orders', ".$configuration_group_id.", 104, now(), now(), NULL, NULL),
					('','PO - from email address', 'PO_FROM_EMAIL_ADDRESS', 'po_email@here.com', 'The FROM email ADDRESS for sent Purchase Orders', ".$configuration_group_id.", 105, now(), now(), NULL, NULL),
					('','PO - sent comments', 'PO_SENT_COMMENTS', 'Order Submitted to Shipping Department for Fulfillment', 'Comments added to the account when submitted to subcontractor', ".$configuration_group_id.", 106, now(), now(), NULL, NULL),
					('','PO - full ship comments', 'PO_FULLSHIP_COMMENTS', 'Thanks for your order!', 'Comments added to the account when the order has shipped in full', ".$configuration_group_id.", 107, now(), now(), NULL, NULL),
					('','PO - partial ship comments', 'PO_PARTIALSHIP_COMMENTS', 'Part of your order has shipped!  The rest of your order will ship soon. You will be notified by email when your order is complete.', 'Comments added to the account when part of the order has shipped', ".$configuration_group_id.", 108, now(), now(), NULL, NULL),
					('','PO - full ship packinglist', 'PO_FULLSHIP_PACKINGLIST', 'Thanks for your order!', 'Comments added to the packing list when the order has shipped in full', ".$configuration_group_id.", 109, now(), now(), NULL, NULL),
					('','PO - partial ship packinglist', 'PO_PARTIALSHIP_PACKINGLIST', 'This is a partial shipment.  The rest of your order has shipped or will ship separately.', 'Comments added to the packing list when part of the order has shipped', ".$configuration_group_id.", 110, now(), now(), NULL, NULL),
                                        ('','PO - po ignore status', 'PO_IGNORE_STATUS', '', 'Ignore Orders On Enter Tracking and Send PO Page With This Status (Leave Blank For None, More Than 1 Separate By Commas, Shipped Status Already Included)', ".$configuration_group_id.", 111, now(), now(), NULL, NULL),
					('','PO - new order status', 'PO_NEW_ORDER_STATUS', 'Pending', 'New Orders Status', ".$configuration_group_id.", 112, now(), now(), NULL, NULL),
					('','PO - po sent status', 'PO_SENT_PO_STATUS', 'Processing', 'Order Status After PO Sent (Leave Blank For No Status Change)', ".$configuration_group_id.", 113, now(), now(), NULL, NULL),
					('','PO - po shipped status', 'PO_SHIPPED_STATUS', 'Delivered', 'Order Status After All Products Ship (Leave Blank For No Status Change)', ".$configuration_group_id.", 114, now(), now(), NULL, NULL),
					('','PO - change shipping from', 'PO_CHANGE_SHIPPING_FROM', '', 'Change this shipping option to something else on POs and Packing Lists', ".$configuration_group_id.", 115, now(), now(), NULL, NULL),
					('','PO - change shipping to', 'PO_CHANGE_SHIPPING_TO', 'Cheapest', 'Value to change shipping option to on POs and Packing Lists', ".$configuration_group_id.", 116, now(), now(), NULL, NULL),
                                        ('','PO - packinglist filename', 'PO_PACKINGLIST_FILENAME', 'packinglist.pdf', 'packing list filename', ".$configuration_group_id.", 125, now(), now(), NULL, NULL),
                                        ('','PO - text file filename', 'PO_TEXTFILE_FILENAME', 'textfile.txt', 'text file attachment filename', ".$configuration_group_id.", 130, now(), now(), NULL, NULL),
					('','PO - text file mime type', 'PO_TF_MIMETYPE', 'text/plain', 'Mime Type of Text File Attachment', ".$configuration_group_id.", 132, now(), now(), NULL, NULL),
					('','PO - replace in both email and text file', 'PO_REPLACE_BOTH', '', 'Replace in Both Email and Text File', ".$configuration_group_id.", 135, now(), now(), NULL, 'zen_cfg_hidden_field_po('),
                                        ('','PO - replace in email only', 'PO_REPLACE_EMAIL', '', 'Replace in Email Only', ".$configuration_group_id.", 140, now(), now(), NULL, 'zen_cfg_hidden_field_po('),
                                        ('','PO - replace in text file only', 'PO_REPLACE_TF', '', 'Replace in Text File Only', ".$configuration_group_id.", 145, now(), now(), NULL, 'zen_cfg_hidden_field_po('),
                                        ('','PO - expected delivery date status', 'PO_ED_STATUS', '', 'Order Status After Expected Delivery Date Added (Leave Blank For No Status Change)', ".$configuration_group_id.", 150, now(), now(), NULL, NULL),
                                        ('','PO - expected delivery date order comments', 'PO_ED_COMMENTS', '{product_list} {is_are} not currently available for immediate shipment.  We expect {it_them} to ship on or by {expected_ship_date}.', 'Comments added to the account when an expected ship date is added.  Tags include {is_are}, {it_them}, {capital_It_They}, {product_list}, and {expected_ship_date}.', ".$configuration_group_id.", 155, now(), now(), NULL, NULL),
                                        ('','PO - custom message 1 - order comments', 'PO_ED_CUSTOM_COMMENTS_ONE', 'Your order has been canceled.', 'Allows you to choose a custom message about an orders expected delivery.  Could be used for the following examples: Order Cancellation, Wait List, etc...  Tags include {is_are}, {it_them}, {capital_It_They}, {product_list}, and {expected_ship_date}.  (Leave Blank For No Custom Message)', ".$configuration_group_id.", 160, now(), now(), NULL, NULL),
                                        ('','PO - custom message 1 - short name', 'PO_ED_CUSTOM_SHORT_NAME_ONE', 'Canceled', 'The custom message that gets added to the order comments and emailed to the customer can be quite lengthy.  This is a short name that identifies this orders expected delivery status for internal use.', ".$configuration_group_id.", 165, now(), now(), NULL, NULL),
                                        ('','PO - custom message 1 - order status', 'PO_ED_CUSTOM_STATUS_ONE', '', 'Order Status After Custom Message 1 Added (Leave Blank For No Status Change)', ".$configuration_group_id.", 170, now(), now(), NULL, NULL),
                                        ('','PO - custom message 2 - order comments', 'PO_ED_CUSTOM_COMMENTS_TWO', '', 'Allows you to choose a custom message about an orders expected delivery.  Could be used for the following examples: Order Cancellation, Wait List, etc...  Tags include {is_are}, {it_them}, {capital_It_They}, {product_list}, and {expected_ship_date}.  (Leave Blank For No Custom Message)', ".$configuration_group_id.", 175, now(), now(), NULL, NULL),
                                        ('','PO - custom message 2 - short name', 'PO_ED_CUSTOM_SHORT_NAME_TWO', '', 'The custom message that gets added to the order comments and emailed to the customer can be quite lengthy.  This is a short name that identifies this orders expected delivery status for internal use.', ".$configuration_group_id.", 180, now(), now(), NULL, NULL),
                                        ('','PO - custom message 2 - order status', 'PO_ED_CUSTOM_STATUS_TWO', '', 'Order Status After Custom Message 2 Added (Leave Blank For No Status Change)', ".$configuration_group_id.", 185, now(), now(), NULL, NULL),
                                        ('','PO - custom message 3 - order comments', 'PO_ED_CUSTOM_COMMENTS_THREE', '', 'Allows you to choose a custom message about an orders expected delivery.  Could be used for the following examples: Order Cancellation, Wait List, etc...  Tags include {is_are}, {it_them}, {capital_It_They}, {product_list}, and {expected_ship_date}.  (Leave Blank For No Custom Message)', ".$configuration_group_id.", 190, now(), now(), NULL, NULL),
                                        ('','PO - custom message 3 - short name', 'PO_ED_CUSTOM_SHORT_NAME_THREE', '', 'The custom message that gets added to the order comments and emailed to the customer can be quite lengthy.  This is a short name that identifies this orders expected delivery status for internal use.', ".$configuration_group_id.", 195, now(), now(), NULL, NULL),
                                        ('','PO - custom message 3 - order status', 'PO_ED_CUSTOM_STATUS_THREE', '', 'Order Status After Custom Message 3 Added (Leave Blank For No Status Change)', ".$configuration_group_id.", 200, now(), now(), NULL, NULL),
                                        ('','PO - PDF Packing List - packing list title (BOLD)', 'PO_PDFP_TITLE', 'Packing List', 'Packing List Title (BOLD)', ".$configuration_group_id.", 215, now(), now(), NULL, NULL),
                                        ('','PO - PDF Packing List - date title (BOLD)', 'PO_PDFP_DATE_TITLE', 'DATE', 'Packing List Date Title (BOLD)', ".$configuration_group_id.", 220, now(), now(), NULL, NULL),  
                                        ('','PO - PDF Packing List - date format', 'PO_PDFP_DATE', 'm-d-Y', 'Packing List Date Format', ".$configuration_group_id.", 225, now(), now(), NULL, NULL),
                                        ('','PO - PDF Packing List - order title (BOLD)', 'PO_PDFP_ORDER_TITLE', 'ORDER', 'Packing List Order Title (BOLD)', ".$configuration_group_id.", 230, now(), now(), NULL, NULL),                     
                                        ('','PO - PDF Packing List - store name (BOLD)', 'PO_PDFP_S_NAME', 'Store Name', 'Store Name on PDF Packing List (BOLD)', ".$configuration_group_id.", 235, now(), now(), NULL, NULL),
                                        ('','PO - PDF Packing List - store address', 'PO_PDFP_S_ADDRESS', 'Store Address', 'Store Address on PDF Packing List', ".$configuration_group_id.", 240, now(), now(), NULL, 'zen_cfg_textarea('),
                                        ('','PO - PDF Packing List - first address name (BOLD)', 'PO_PDFP_FA_NAME', 'Billing Address', 'Title of First Address on PDF Packing List (BOLD)', ".$configuration_group_id.", 245, now(), now(), NULL, NULL),
                                        ('','PO - PDF Packing List - first address', 'PO_PDFP_FA_ADDRESS', '{bill_ad}', 'First Address on PDF Packing List - Use tags: {cust_ad} - customer address, {bill_ad} - billing address, {ship_ad} - shipping address.', ".$configuration_group_id.", 250, now(), now(), NULL, 'zen_cfg_textarea('),
                                        ('','PO - PDF Packing List - second address name (BOLD)', 'PO_PDFP_SA_NAME', 'Shipping Address', 'Title of Second Address on PDF Packing List (BOLD)', ".$configuration_group_id.", 255, now(), now(), NULL, NULL),
                                        ('','PO - PDF Packing List - second address', 'PO_PDFP_SA_ADDRESS', '{ship_ad}', 'Second Address on PDF Packing List - - Use tags: {cust_ad} - customer address, {bill_ad} - billing address, {ship_ad} - shipping address.', ".$configuration_group_id.", 260, now(), now(), NULL, 'zen_cfg_textarea('),
                                        ('','PO - PDF Packing List - product list column one title', 'PO_PDFP_C_ONE_TITLE', 'MODEL NUMBER', 'Product List Column One Title on PDF Packing List', ".$configuration_group_id.", 265, now(), now(), NULL, NULL),
                                        ('','PO - PDF Packing List - product list column one', 'PO_PDFP_C_ONE', '{products_model}', 'Product List Column One on PDF Packing List - Tags include {products_quantity}, {products_name}, {products_model}, {manufacturers_name}, {products_attributes}, and {final_price}.', ".$configuration_group_id.", 270, now(), now(), NULL, NULL),
                                        ('','PO - PDF Packing List - product list column one width (mm)', 'PO_PDFP_C_ONE_WIDTH', '40', 'Product List Column One Width on PDF Packing List (mm) FYI-Must add up to 185.9mm and Default Value is 40.', ".$configuration_group_id.", 275, now(), now(), NULL, NULL),
                                        ('','PO - PDF Packing List - product list column one justification', 'PO_PDFP_C_ONE_JUST', 'L', 'Product List Column One Justification on PDF Packing List - L=Left Justified, C=Center, R=Right Justified', ".$configuration_group_id.", 277, now(), now(), NULL, NULL),
                                        ('','PO - PDF Packing List - product list column two title', 'PO_PDFP_C_TWO_TITLE', 'PRODUCT DESCRIPTION', 'Product List Column Two Title on PDF Packing List', ".$configuration_group_id.", 280, now(), now(), NULL, NULL),
                                        ('','PO - PDF Packing List - product list column two', 'PO_PDFP_C_TWO', '{products_name} {products_attributes}', 'Product List Column Two on PDF Packing List - Tags include {products_quantity}, {products_name}, {products_model}, {manufacturers_name}, {products_attributes}, and {final_price}.', ".$configuration_group_id.", 285, now(), now(), NULL, NULL),
                                        ('','PO - PDF Packing List - product list column two width (mm)', 'PO_PDFP_C_TWO_WIDTH', '120.9', 'Product List Column Two Width on PDF Packing List (mm) FYI-Must add up to 185.9mm and Default Value is 120.9.', ".$configuration_group_id.", 290, now(), now(), NULL, NULL),
                                        ('','PO - PDF Packing List - product list column two justification', 'PO_PDFP_C_TWO_JUST', 'L', 'Product List Column Two Justification on PDF Packing List - L=Left Justified, C=Center, R=Right Justified', ".$configuration_group_id.", 292, now(), now(), NULL, NULL),
                                        ('','PO - PDF Packing List - product list column three title', 'PO_PDFP_C_THREE_TITLE', 'QUANTITY', 'Product List Column Three Title on PDF Packing List', ".$configuration_group_id.", 295, now(), now(), NULL, NULL),
                                        ('','PO - PDF Packing List - product list column three', 'PO_PDFP_C_THREE', '{products_quantity}', 'Product List Column Three on PDF Packing List - Tags include {products_quantity}, {products_name}, {products_model}, {manufacturers_name}, {products_attributes}, and {final_price}.', ".$configuration_group_id.", 300, now(), now(), NULL, NULL),
                                        ('','PO - PDF Packing List - product list column three width (mm)', 'PO_PDFP_C_THREE_WIDTH', '25', 'Product List Column Three Width on PDF Packing List (mm) FYI-Must add up to 185.9mm and Default Value is 25.', ".$configuration_group_id.", 305, now(), now(), NULL, NULL),
                                        ('','PO - PDF Packing List - product list column three justification', 'PO_PDFP_C_THREE_JUST', 'C', 'Product List Column Three Justification on PDF Packing List - L=Left Justified, C=Center, R=Right Justified', ".$configuration_group_id.", 307, now(), now(), NULL, NULL),
                                        ('','PO - PDF Packing List - product list column four title', 'PO_PDFP_C_FOUR_TITLE', '', 'Product List Column Four Title on PDF Packing List', ".$configuration_group_id.", 310, now(), now(), NULL, NULL),
                                        ('','PO - PDF Packing List - product list column four', 'PO_PDFP_C_FOUR', '', 'Product List Column Four on PDF Packing List - Tags include {products_quantity}, {products_name}, {products_model}, {manufacturers_name}, {products_attributes}, and {final_price}.', ".$configuration_group_id.", 315, now(), now(), NULL, NULL),
                                        ('','PO - PDF Packing List - product list column four width (mm)', 'PO_PDFP_C_FOUR_WIDTH', '0', 'Product List Column Four Width on PDF Packing List (mm) FYI-Must add up to 185.9mm and Default Value is 0.', ".$configuration_group_id.", 320, now(), now(), NULL, NULL),
                                        ('','PO - PDF Packing List - product list column four justification', 'PO_PDFP_C_FOUR_JUST', 'L', 'Product List Column Four Justification on PDF Packing List - L=Left Justified, C=Center, R=Right Justified', ".$configuration_group_id.", 322, now(), now(), NULL, NULL),
                                        ('','PO - PDF Packing List - product list column five title', 'PO_PDFP_C_FIVE_TITLE', '', 'Product List Column Five Title on PDF Packing List', ".$configuration_group_id.", 325, now(), now(), NULL, NULL),
                                        ('','PO - PDF Packing List - product list column five', 'PO_PDFP_C_FIVE', '', 'Product List Column Five on PDF Packing List - Tags include {products_quantity}, {products_name}, {products_model}, {manufacturers_name}, {products_attributes}, and {final_price}.', ".$configuration_group_id.", 330, now(), now(), NULL, NULL),
                                        ('','PO - PDF Packing List - product list column five width (mm)', 'PO_PDFP_C_FIVE_WIDTH', '0', 'Product List Column Five Width on PDF Packing List (mm) FYI-Must add up to 185.9mm and Default Value is 0.', ".$configuration_group_id.", 335, now(), now(), NULL, NULL),
                                        ('','PO - PDF Packing List - product list column five justification', 'PO_PDFP_C_FIVE_JUST', 'L', 'Product List Column Five Justification on PDF Packing List - L=Left Justified, C=Center, R=Right Justified', ".$configuration_group_id.", 337, now(), now(), NULL, NULL),
                                        ('','PO - PDF Packing List - upper comments name', 'PO_PDFP_SHIP_COMMENTS_NAME', 'Store Comments', 'Name that easily identifies what is in the upper comments.  Leaving this blank will remove the checkbox which allows you to remove these comments as needed.', ".$configuration_group_id.", 347, now(), now(), NULL, NULL),
                                        ('','PO - PDF Packing List - upper comments location', 'PO_PDFP_SHIP_COMMENTS_LOCATION', '40-140', 'Upper comments will appear at the bottom of the product list.  The product list is 185.9mm wide.  Adjust this setting to keep these comments in one column or to let the comments spread across several columns.  Default is 40 to 140.  This means 40mm from the left side of the product list to 140mm from the left side of the product list.', ".$configuration_group_id.", 350, now(), now(), NULL, NULL), 
                                        ('','PO - PDF Packing List - full ship lower comments', 'PO_PDFP_CUST_COMMENT_FULL', '{customers_comments}', 'Comments Line on bottom of PDF Packing List when the order has shipped in full - Use tags {shipping_method}, {customers_comments}, and {store_comments}.', ".$configuration_group_id.", 355, now(), now(), NULL, 'zen_cfg_textarea('),
                                        ('','PO - PDF Packing List - partial ship lower comments', 'PO_PDFP_CUST_COMMENT_PARTIAL', '{customers_comments}', 'Comments Line on bottom of PDF Packing List when part of the order has shipped - Use tags {shipping_method}, {customers_comments}, and {store_comments}.', ".$configuration_group_id.", 360, now(), now(), NULL, 'zen_cfg_textarea('),
                                        ('','PO - PDF Packing List - lower comments name', 'PO_PDFP_CUST_COMMENT_NAME', 'Customer Comments', 'Name that easily identifies what is in the lower comments.  Leaving this blank will remove the checkbox which allows you to remove these comments as needed.', ".$configuration_group_id.", 365, now(), now(), NULL, NULL),
                                        ('','PO - PDF Packing List - include totals and subtotals', 'PO_PDFP_TOTALS', '0', 'Show totals and subtotals on packing list.  Useful if you want to turn the packing list into a sales receipt or invoice.  0 - no totals, 1 - show totals only when entire order is shipped at once, 2 - always show totals', ".$configuration_group_id.", 370, now(), now(), NULL, NULL),
                                        ('','PO - PDF Packing List - show all products', 'PO_PDFP_SHOW_ALL', '0', 'Show all products on packing list.  0 - show only products that shipped, 1 - show all products in order', ".$configuration_group_id.", 375, now(), now(), NULL, NULL),
                                        ('','PO - PDF Packing List - first picture filename', 'PO_PDFP_PICTURE_ONE_FILE', '', 'First Picture Filename to Add to PDF Packing List - Store file in admin directory.  Leave blank for no picture.  Supported files include .gif, .jpg, and .png.', ".$configuration_group_id.", 380, now(), now(), NULL, NULL),
                                        ('','PO - PDF Packing List - first picture location (mm)', 'PO_PDFP_PICTURE_ONE_LOCATION', '15,15', 'First Picture Location on PDF Packing List - x,y for top left corner of image in mm -> x is mm from left of page, y is mm from top of page.  FYI - Standard page is 215.9mm wide and 279.4mm high.', ".$configuration_group_id.", 385, now(), now(), NULL, NULL),
                                        ('','PO - PDF Packing List - first picture width (mm)', 'PO_PDFP_PICTURE_ONE_WIDTH', '45', 'Width of first picture in mm.  Height is calculated automatically and keeps picture proportional.', ".$configuration_group_id.", 390, now(), now(), NULL, NULL),
                                        ('','PO - PDF Packing List - second picture filename', 'PO_PDFP_PICTURE_TWO_FILE', '', 'Second Picture Filename to Add to PDF Packing List - Store file in admin directory.  Leave blank for no picture.  Supported files include .gif, .jpg, and .png.', ".$configuration_group_id.", 395, now(), now(), NULL, NULL),
                                        ('','PO - PDF Packing List - second picture location (mm)', 'PO_PDFP_PICTURE_TWO_LOCATION', '85.45,190', 'Second Picture Location on PDF Packing List - x,y for top left corner of image in mm -> x is mm from left of page, y is mm from top of page.  FYI - Standard page is 215.9mm wide and 279.4mm high.', ".$configuration_group_id.", 400, now(), now(), NULL, NULL),
                                        ('','PO - VERSION', 'PO_MODULE_VERSION', '1.0.0', 'Version of the PO Module', ".$configuration_group_id.", 1, now(), now(), NULL, NULL),
                                        ('','PO - PDF Packing List - second picture width (mm)', 'PO_PDFP_PICTURE_TWO_WIDTH', '45', 'Width of first picture in mm.  Height is calculated automatically and keeps picture proportional.', ".$configuration_group_id.", 405, now(), now(), NULL, NULL);

");

if(version_compare(PROJECT_VERSION_MAJOR.".".PROJECT_VERSION_MINOR, "1.5.0") >= 0) { 
  // continue Zen Cart 1.5.0
  
  // add to tools menu
  if (function_exists('zen_page_key_exists') && function_exists('zen_register_admin_page') && !zen_page_key_exists('toolsEditSubContact')) {
    zen_register_admin_page('toolsEditSubContact',
                            'BOX_TOOLS_EDIT_SUBCONTRACTORS', 
                            'FILENAME_SUBCONTRACTORS',
                            '', 
                            'tools', 
                            'Y',
                            760);
      
    $messageStack->add('Enabled Tools Edit SubContactors.', 'success');
  }
  if (function_exists('zen_page_key_exists') && function_exists('zen_register_admin_page') && !zen_page_key_exists('toolsSetSubContact')) {
    zen_register_admin_page('toolsSetSubContact',
                            'BOX_TOOLS_SET_SUBCONTRACTORS', 
                            'FILENAME_SET_SUBCONTRACTORS',
                            '', 
                            'tools', 
                            'Y',
                            761);
      
    $messageStack->add('Enabled Tools Set SubContactors.', 'success');
  }
  if (function_exists('zen_page_key_exists') && function_exists('zen_register_admin_page') && !zen_page_key_exists('toolsSetSubContactMf')) {
    zen_register_admin_page('toolsSetSubContactMf',
                            'BOX_TOOLS_SET_SUBCONTRACTORS_MANUF', 
                            'FILENAME_SET_SUBCONTRACTORS_MANUF',
                            '', 
                            'tools', 
                            'Y',
                            762);
      
    $messageStack->add('Enabled Tools Set SubContactors Manufacturers.', 'success');
  }
// Customers Menu
  if (function_exists('zen_page_key_exists') && function_exists('zen_register_admin_page') && !zen_page_key_exists('custSendPOs')) {
    zen_register_admin_page('custSendPOs',
                            'BOX_CUSTOMERS_SEND_POS', 
                            'FILENAME_SEND_POS',
                            '', 
                            'customers', 
                            'Y',
                            740);
      
    $messageStack->add('Enabled Customers Send POs.', 'success');
  }  
  if (function_exists('zen_page_key_exists') && function_exists('zen_register_admin_page') && !zen_page_key_exists('custSendPOsNc')) {
    zen_register_admin_page('custSendPOsNc',
                            'BOX_CUSTOMERS_SEND_POS_NC', 
                            'FILENAME_SEND_POS_NC',
                            '', 
                            'customers', 
                            'Y',
                            741);
      
    $messageStack->add('Enabled Customers Send POs NC.', 'success');
  } 
  if (function_exists('zen_page_key_exists') && function_exists('zen_register_admin_page') && !zen_page_key_exists('custConfTrack')) {
    zen_register_admin_page('custConfTrack',
                            'BOX_CUSTOMERS_CONFIRM_TRACKING', 
                            'FILENAME_CONFIRM_TRACKING',
                            '', 
                            'customers', 
                            'Y',
                            742);
      
    $messageStack->add('Enabled Customers Confirm Tracking', 'success');
  } 

    // add to configuration menus
  if (function_exists('zen_page_key_exists') && function_exists('zen_register_admin_page') && !zen_page_key_exists('configPOModule')) {
    zen_register_admin_page('configPOModule',
                            'PO_MODULE', 
                            'FILENAME_CONFIGURATION',
                            'gID='.(int)$configuration_group_id, 
                            'configuration', 
                            'Y',
                            760);
      
    $messageStack->add('Enabled PO Module Configuration menu.', 'success');
  }
}