<?php

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit();
}

require_once( plugin_dir_path(__FILE__) . "/LDAP-Login.php" );
delete_option(LDAPLogin::get_field_settings_s());
delete_site_option(LDAPLogin::get_field_settings_s());
