<?php


$config_group = $db->Execute("SELECT configuration_group_id FROM ".TABLE_CONFIGURATION_GROUP."
WHERE configuration_group_title= 'Purchase Orders'");
$configuration_group_id = $config_group->fields['configuration_group_id'];

$db->Execute("DELETE FROM ".TABLE_CONFIGURATION." WHERE configuration_key='PO_SEND_PACKING_LISTS';");
$db->Execute("DELETE FROM ".TABLE_CONFIGURATION." WHERE configuration_key='PO_UNKNOWN_OMIT1';");
$db->Execute("DELETE FROM ".TABLE_CONFIGURATION." WHERE configuration_key='PO_UNKNOWN_OMIT2';");
$db->Execute("DELETE FROM ".TABLE_CONFIGURATION." WHERE configuration_key='PO_UNKNOWN_OMIT3';");
$db->Execute("DELETE FROM ".TABLE_CONFIGURATION." WHERE configuration_key='PO_PACKINGLIST_FILENAME';");


$db->Execute("INSERT INTO ".TABLE_CONFIGURATION." (`configuration_id`, `configuration_title`, `configuration_key`, `configuration_value`, `configuration_description`, `configuration_group_id`, `sort_order`, `last_modified`, `date_added`, `use_function`, `set_function`) 
    VALUES 	('','PO - send pdf packing lists', 'PO_SEND_PACKING_LISTS', '4', '0 - never, 1 - always, 2 - sometimes (default yes), 3 - sometimes (default no), 4 - use subcontractor default', ".$configuration_group_id.", 80, now(), now(), NULL, NULL),
                                        ('','PO - packinglist filename', 'PO_PACKINGLIST_FILENAME', 'packinglist.pdf', 'packing list filename', ".$configuration_group_id.", 125, now(), now(), NULL, NULL);
                                       ");

$db->Execute("UPDATE ".TABLE_CONFIGURATION." SET configuration_value='1.0.4' WHERE configuration_key='PO_MODULE_VERSION'");
