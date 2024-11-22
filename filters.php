<?php

// Set default settings
function wcapf_default_settings($settings) {
	$settings['shop_loop_container'] = '.another-container';
	$settings['custom_scripts'] = 'alert("hello");';
	$settings['scroll_to_top'] = null;
	return $settings;
}
add_filter('wcapf_settings', 'wcapf_default_settings');