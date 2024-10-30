<?php
/**
* Plugin Name: LeadBoxer Add-On for Gravity Forms
* Text Domain: leadboxer-gravityforms
* Plugin URI: leadboxer.com?utm_source=wp-plugins&utm_campaign=plugin-uri&utm_medium=plugins
* Description: This add-on allows you to easily capture form submits from the Gravity Forms plugin into LeadBoxer
* Author: LeadBoxer
* Author URI: leadboxer.com?utm_source=wp-plugins&utm_campaign=author-uri&utm_medium=plugins
* Version: 1.3
* Tested up to: 6.3
 */
define( 'GF_LEADBOXER_ADDON_VERSION', '1.3' );
defined('ABSPATH') or die();
defined('GF_LEADBOXER')  OR define('GF_LEADBOXER', plugin_dir_url(__FILE__));
//GFForms::include_payment_addon_framework();

if( !function_exists('gravity_form_missing_wc_notice') ) {
function gravity_form_missing_wc_notice() {
    /* translators: 1. URL link. */
    echo '<div class="error"><p><strong>' . sprintf( esc_html__( 'The LeadBoxer Add-On for Gravity Forms requires the Gravity Form plugin to be installed and active. You can download %s here.', 'leadboxer-gravityforms' ), '<a href="https://www.gravityforms.com/" target="_blank">Gravity Forms</a>' ) . '</strong></p></div>';
 }
}

add_action( 'plugins_loaded', 'gravity_form_alert_init' );

if( !function_exists('gravity_form_alert_init') ) {
function gravity_form_alert_init() {
    if ( ! class_exists( 'GFForms') ) {
        add_action( 'admin_notices', 'gravity_form_missing_wc_notice' );
        return;
    }
  }
}

add_action('admin_init', 'gravity_form_check_the_plugins');
function gravity_form_check_the_plugins()
{
    include_once ABSPATH . 'wp-admin/includes/plugin.php';
    if (! is_plugin_active( 'gravityforms/gravityforms.php' )) {
        deactivate_plugins( // deactivate for media_manager
            array(
                '/leadboxeraddons/init.php'
            ),
            true, // silent mode (no deactivation hooks fired)
            false // network wide
        );
         ?>
    <style>
        #message{display:none;}
    </style>
    <?php
    }

}

add_action( 'gform_loaded', array( 'GF_LEADBOXER_AddON', 'load' ), 5 );
class GF_LEADBOXER_AddON{

    public static function load() {
        wp_register_script('sweetalert_admin_js', GF_LEADBOXER . 'assets/js/sweetalert.js', array(), time(), true);
        wp_enqueue_script('sweetalert_admin_js');

        if ( ! method_exists( 'GFForms', 'include_addon_framework' ) ) {
            return;
        }

        require_once( 'class-leadboxeradd.php' );

        GFAddOn::register( 'GFLeadboxerAddOn' );
    }

}

$leadboxer = new GF_LEADBOXER_AddON;

function gf_leadboxer_addon() {
    return GFLeadboxerAddOn::get_instance();
}
