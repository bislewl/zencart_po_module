<?php

$db->Execute("ALTER TABLE ".TABLE_ORDERS_PRODUCTS."
ADD `po_price` varchar(300) NOT NULL default '';");

$db->Execute("UPDATE ".TABLE_CONFIGURATION." SET configuration_value='1.1.0' WHERE configuration_key='PO_MODULE_VERSION'");
