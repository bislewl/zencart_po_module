<?php
$config_group = $db->Execute("SELECT configuration_group_id FROM ".TABLE_CONFIGURATION_GROUP."
WHERE configuration_group_title= 'Purchase Orders'");
$configuration_group_id = $config_group->fields['configuration_group_id'];

$db->Execute("DELETE FROM ".TABLE_CONFIGURATION." WHERE configuration_key='PO_OWN_STOCK_EMAIL';");

$db->Execute("INSERT INTO ".TABLE_CONFIGURATION." (`configuration_id`, `configuration_title`, `configuration_key`, `configuration_value`, `configuration_description`, `configuration_group_id`, `sort_order`, `last_modified`, `date_added`, `use_function`, `set_function`) 
    VALUES 	
                                        ('','PO - omit from unknown email 1', 'PO_UNKNOWN_OMIT1', '\nIf you would prefer to enter tracking information for this order\ndirectly, please visit:\n', 'Text to omit from emails sent for unknown customers 1 of 3', ".$configuration_group_id.", 112, now(), now(), NULL, NULL),
					('','PO - omit from unknown email 2', 'PO_UNKNOWN_OMIT2', '{delivery_name}\n', 'Text to omit from emails sent for unknown customers 2 of 3', ".$configuration_group_id.", 113, now(), now(), NULL, NULL),
					('','PO - omit from unknown email 3', 'PO_UNKNOWN_OMIT3', '', 'Text to omit from emails sent for unknown customers 3 of 3', ".$configuration_group_id.", 114, now(), now(), NULL, NULL);
					");
$db->Execute("UPDATE ".TABLE_CONFIGURATION." SET configuration_value='1.0.3' WHERE configuration_key=PO_MODULE_VERSION");