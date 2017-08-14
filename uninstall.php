<?php
if ( !defined( 'WP_UNINSTALL_PLUGIN' ) )
	exit ();
delete_option('mdc_show_delivery_time');
delete_option('mdc_msg_custom_css');