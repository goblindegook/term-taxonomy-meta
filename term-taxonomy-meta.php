<?php
/**
 * @package Term_Taxonomy_Meta
 * @version 0.2
 */
/*
Plugin Name: Term Taxonomy Meta
Plugin URI: http://github.com/goblindegook/term-taxonomy-meta/
Description: Adds support for term taxonomy meta values in WordPress.
Author: Luís Rodrigues
Version: 0.2
Author URI: http://github.com/goblindegook/
*/

define( 'TT_META_PLUGIN_URL'  , WP_PLUGIN_URL . '/' . dirname( plugin_basename( __FILE__ ) ) . '/' );
define( 'TT_META_PLUGIN_DIR'  , WP_PLUGIN_DIR . '/' . dirname( plugin_basename( __FILE__ ) ) . '/' );
define( 'TT_META_VERSION', '0.2' );

if (!class_exists( 'TTMetaPlugin' )) :

class TTMetaPlugin
{
    public static function activate ()
    {
	    global $wpdb;
	    $wpdb->term_taxonomymeta = $wpdb->prefix . "term_taxonomymeta";
	    
	    $charset_collate = '';

    	if (!empty( $wpdb->charset ))
		    $charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
		    
	    if (!empty( $wpdb->collate ))
		    $charset_collate .= " COLLATE $wpdb->collate";
	    
	    if ($wpdb->get_var( "SHOW TABLES LIKE '$wpdb->term_taxonomymeta'" ) != $wpdb->term_taxonomymeta) {
	        require_once( ABSPATH . 'wp-admin/includes/upgrade.php');
	        $sql = "CREATE TABLE `$wpdb->term_taxonomymeta` (
	            `meta_id` bigint(20) UNSIGNED NOT NULL auto_increment,
	            `term_taxonomy_id` bigint(20) UNSIGNED NOT NULL DEFAULT '0',
	            `meta_key` varchar(255) DEFAULT NULL,
	            `meta_value` longtext,
	            PRIMARY KEY  (`meta_id`),
	            KEY `term_taxonomy_id` (`term_taxonomy_id`),
	            KEY `meta_key` (`meta_key`)
	        ) $charset_collate;";
	        dbDelta( $sql );
	    }
	}
	
	public static function deactivate ()
	{
	}
	
	public static function uninstall ()
	{
    	if (!defined( 'WP_UNINSTALL_PLUGIN' ))
    	{
        	exit();
        }
        
	    // KABOOM!
	    global $wpdb;
	    $wpdb->term_taxonomymeta = $wpdb->prefix . "term_taxonomymeta";
	    $wpdb->query( "DROP TABLE '$wpdb->term_taxonomymeta'" );
	}

    public static function init ()
    {
        // load_plugin_textdomain( 'ttmeta', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
    }

    public static function admin_init ()
    {
    }

    private static function get_term_taxonomy_id ($term_id, $taxonomy) {
    	$term_id = absint( $term_id );

        // Term taxonomy does not exist:
	    if (!$ids = term_exists( $term_id, $taxonomy ))
	    {
		    return false;
		}
		
		// WP Error:
	    if (is_wp_error( $ids ))
	    {
		    return $ids;
		}

    	return $ids['term_taxonomy_id'];
    }
    
    public static function get_term_taxonomy_meta ($term_id, $taxonomy, $meta_key = '', $single = false)
    {
        $term_taxonomy_id = self::get_term_taxonomy_id( $term_id, $taxonomy );
        if (!$term_taxonomy_id || is_wp_error( $term_taxonomy_id ))
        {
            return $term_taxonomy_id;
        }
        return get_metadata('term_taxonomy', $term_taxonomy_id, $meta_key, $single);
    }
    
    public static function add_term_taxonomy_meta ($term_id, $taxonomy, $meta_key, $meta_value, $unique = false)
    {
        $term_taxonomy_id = self::get_term_taxonomy_id( $term_id, $taxonomy );
        if (!$term_taxonomy_id || is_wp_error( $term_taxonomy_id ))
        {
            return $term_taxonomy_id;
        }
        return add_metadata('term_taxonomy', $term_taxonomy_id, $meta_key, $meta_value, $unique);
    }
    
    public static function update_term_taxonomy_meta ($term_id, $taxonomy, $meta_key, $meta_value, $prev_value = '')
    {
        $term_taxonomy_id = self::get_term_taxonomy_id( $term_id, $taxonomy );
        if (!$term_taxonomy_id || is_wp_error( $term_taxonomy_id ))
        {
            return $term_taxonomy_id;
        }
        return update_metadata('term_taxonomy', $term_taxonomy_id, $meta_key, $meta_value, $prev_value);
    }
    
    public static function delete_term_taxonomy_meta ($term_id, $taxonomy, $meta_key, $meta_value = '', $delete_all = false)
    {
        $term_taxonomy_id = self::get_term_taxonomy_id( $term_id, $taxonomy );
        if (!$term_taxonomy_id || is_wp_error( $term_taxonomy_id ))
        {
            return $term_taxonomy_id;
        }
        return delete_metadata('term_taxonomy', $term_taxonomy_id, $meta_key, $meta_value, $delete_all);
    }
    
    public static function get_term_taxonomy_custom ($term_id, $taxonomy)
    {
        return self::get_term_taxonomy_meta( $term_id, $taxonomy );
    }
    
    public static function get_term_taxonomy_custom_values ($term_id, $taxonomy, $meta_key = '')
    {
        if (!$meta_key)
        {
            return;
        }
        $custom = self::get_term_taxonomy_custom( $term_id, $taxonomy );
        return isset( $custom[$meta_key] ) ? $custom[$meta_key] : null;
    }
    
    public static function get_term_taxonomy_custom_keys ($term_id, $taxonomy)
    {
        $custom = self::get_term_taxonomy_custom( $term_id, $taxonomy );
        if (is_array( $custom ) && $keys = array_keys( $custom ))
        {
            return $keys;
        }
    }
}

/* SETUP PLUGIN */

global $wpdb;
$wpdb->term_taxonomymeta = $wpdb->prefix . "term_taxonomymeta";

/* Register hooks */
register_activation_hook( __FILE__, array( 'TTMetaPlugin', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'TTMetaPlugin', 'deactivate' ) );
register_uninstall_hook( __FILE__, array( 'TTMetaPlugin', 'uninstall' ) );
    
/* Starting actions */
add_action( 'init'              , array( 'TTMetaPlugin', 'init'       ) );
add_action( 'admin_init'        , array( 'TTMetaPlugin', 'admin_init' ) );

/* TERM TAXONOMY META API FUNCTIONS */

function get_term_taxonomy_meta ($term_id, $taxonomy, $meta_key, $single = false)
{
    return TTMetaPlugin::get_term_taxonomy_meta( $term_id, $taxonomy, $meta_key, $single );
}

function add_term_taxonomy_meta ($term_id, $taxonomy, $meta_key, $meta_value, $unique = false)
{
    return TTMetaPlugin::add_term_taxonomy_meta( $term_id, $taxonomy, $meta_key, $meta_value, $unique );
}

function update_term_taxonomy_meta ($term_id, $taxonomy, $meta_key, $meta_value, $prev_value = '')
{
    return TTMetaPlugin::update_term_taxonomy_meta( $term_id, $taxonomy, $meta_key, $meta_value, $prev_value );
}

function delete_term_taxonomy_meta ($term_id, $taxonomy, $meta_key, $meta_value = '')
{
    return TTMetaPlugin::delete_term_taxonomy_meta( $term_id, $taxonomy, $meta_key, $meta_value );
}

function get_term_taxonomy_custom ($term_id, $taxonomy)
{
    return TTMetaPlugin::get_term_taxonomy_custom ( $term_id, $taxonomy );
}

function get_term_taxonomy_custom_values ($term_id, $taxonomy, $meta_key)
{
    return TTMetaPlugin::get_term_taxonomy_custom_values( $term_id, $taxonomy, $meta_key );
}

function get_term_taxonomy_custom_keys ($term_id, $taxonomy)
{
    return TTMetaPlugin::get_term_taxonomy_custom_keys( $term_id, $taxonomy );
}

endif;

