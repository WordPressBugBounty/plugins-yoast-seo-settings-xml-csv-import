<?php

namespace WPAI_Yoast_SEO_Import_Add_On;

// Prevent direct access to the file
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class Helper {
    public $add_on_name;

    public function __construct() {
        $this->add_on_name = 'Yoast SEO Add-On';
    }

    public function get_import_id() {
        global $argv;
        $import_id = 'new';
            
        if ( ! empty( $argv ) ) {

			// First check for the ID set by the WP_CLI code.
			$temp_id = apply_filters( 'wp_all_import_cli_import_id', false );

			if( $temp_id !== false && is_numeric( $temp_id ) ) {
				$import_id = $temp_id;
			} else {

				// Try to get the ID from the CLI arguments if it's not found otherwise.
				// `wp all-import run 3,7` runs a list; fields are registered once, for the first.
				$import_id_arr = array_filter( $argv, function ( $a ) {
					return (bool) preg_match( '/^\d+(,\d+)*$/', $a );
				} );

				if ( ! empty( $import_id_arr ) ) {
					$import_id = strtok( reset( $import_id_arr ), ',' );
				}
			}
        }
    
        if ( $import_id == 'new' ) {
            if ( isset( $_GET['import_id'] ) ) {
                $import_id = $_GET['import_id'];
            } elseif ( isset( $_GET['id'] ) ) {
                $import_id = $_GET['id'];
            }
        }

        return $import_id;
    }

    public function get_post_type() {
        $custom_type = false;
        $import_id   = $this->get_import_id();

        // Declaring $wpdb as global to access database
        global $wpdb;
    
        // Get values from import data table
        $imports_table = $wpdb->prefix . 'pmxi_imports';
    
        // Get import session from database based on import ID or 'new'
        $import_options = $wpdb->get_row( $wpdb->prepare("SELECT options FROM $imports_table WHERE id = %d", $import_id), ARRAY_A );
    
        // If this is an existing import load the custom post type from the array
        if ( ! empty($import_options) )	{
            $import_options_arr = unserialize($import_options['options']);
            $custom_type = $import_options_arr['custom_type'];
        } else {
            // If this is a new import get the custom post type data from the current session
            $handler            = $this->fetch_handler();
            if ( $handler === false ) {
                return $custom_type;
            }
            
            $import_options_arr = $handler->get_session_data();
            
            
            $custom_type = empty($import_options_arr['custom_type']) ? '' : $import_options_arr['custom_type'];		
        }

        return $custom_type;
    }

    public function fetch_handler() {
        // Check if Pro version is active
        if ( is_plugin_active( 'wp-all-import-pro/wp-all-import-pro.php' ) ) {
            $folder = 'wp-all-import-pro';
        } elseif ( is_plugin_active('wp-all-import/plugin.php' ) ) {
            $folder = 'wp-all-import';
        } else {
            return false;
        }

        $class_paths = [
            WP_PLUGIN_DIR . '/' . $folder . '/classes/session.php',
            WP_PLUGIN_DIR . '/' . $folder . '/classes/input.php',
            WP_PLUGIN_DIR . '/' . $folder . '/classes/handler.php'
        ];

        foreach ( $class_paths as $path ) {
            require_once( $path );
        }

        $handler = new \PMXI_Handler();
        return $handler;
        
    }

    /**
     * Whether the import's saved template has this field at all. Templates save every field the
     * add-on offered when they were created, so a missing key means the field did not exist yet
     * and importing it would overwrite the site's value with an empty one.
     */
    public function is_mapped( $field, $import_options ) {
        $options = isset( $import_options['options']['yoast_addon'] ) ? $import_options['options']['yoast_addon'] : null;

        return is_array( $options ) && array_key_exists( $field, $options );
    }

    public function log( $message = 'empty' ){

        if ( $message !== 'empty' ) {            
            $logger = function( $m = '' ) {
                $date    = date("H:i:s");
                $m = str_replace( '%', '%%', $m );
                printf( "<div class='progress-msg'>[%s] $m</div>\n", $date ); 
                flush(); 
            };
            call_user_func( $logger, $message );
        }

    }

    public function update_meta( $id, $field, $data, $type = 'post' ) {
        $enable_logs_settings = array( 
            'enable'                 => true, 
            'include_empty_updates'  => false,
            'include_failed_updates' => false 
        );

        $enable_logs = apply_filters( 'wpai_yoastseo_addon_enable_logs', $enable_logs_settings );

        switch ( $type ) {
            case 'post':
                $update = update_post_meta( $id, $field, $data );
                break;

            case 'taxonomy':
                $update = update_term_meta( $id, $field, $data );
                break;

            default:
                $update = false;
                break;
        
        }

        $data   = maybe_serialize( $data );
        $data   = wp_strip_all_tags( $data );

        if ( $update !== false ) {
            if ( $enable_logs['enable'] === true ) {
                if ( $enable_logs['include_empty_updates'] === false ) {
                    if ( $field !== '' && $data !== '' ) {                                
                        $this->log( 'Property `' . $field . '` updated with value `<em>' . $data . '</em>`' );
                    }
                } else {
                    $this->log( 'Property `' . $field . '` updated with value `<em>' . $data . '</em>`' );
                }
            }
        } else {
            if ( $enable_logs['enable'] === true && $enable_logs['include_failed_updates'] === true )  {
                $this->log( 'Property `' . $field . '` failed to import value `<em>' . $data . '</em>`' );
            }
        }
    }
}