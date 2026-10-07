<?php

/*
Plugin Name: WP All Import - Yoast WordPress SEO Add-On
Plugin URI: http://www.wpallimport.com/
Description: Import data into Yoast WordPress SEO with WP All Import.
Version: 2.0
Author: Soflyy
*/

namespace WPAI_Yoast_SEO_Import_Add_On;

// Prevent direct access to the file
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * The main Add-On class.
 * 
 * This class adds all of our constants and includes the other classes to build the import fields in the UI. Note
 *  that the only way to access this class is by using the get_instance() method.
 * 
 * @author Soflyy <support@wpallimport.com>
 * 
 * @since 1.0
 */

 final class Add_On {
    protected static $instance;
    
    protected $add_on;

    private $addon_name;
    private $addon_slug;
    
    public $post_type = '';
    
    /**
     * The method used to get the add-on instance.
     */
    static public function get_instance() {
        if ( self::$instance == NULL ) {
            self::$instance = new self();
        }
        
        return self::$instance;
    }

    /**
     * The construct method, which initializes the add-on and sets up constants & includes.
     */
    protected function __construct() {
        $this->addon_name = 'Yoast WordPress SEO Add-On';
        $this->addon_slug = 'yoast_addon';

        if ( ! class_exists( 'PMXI_Plugin' ) || ! class_exists( 'PMXI_RapidAddon' ) ) {
            add_action( 'admin_notices', function () {
                $this->pmxi_display_admin_notice( $this->addon_name, $this->addon_slug );
            } );
            return;
        }
        
        $this->constants();
        $this->includes();
        
        $this->add_on = new \PMXI_RapidAddon( $this->addon_name, $this->addon_slug );
        
        $this->hooks();
    }

    /**
     * The hooks method.
     * 
     * Adds all necessary actions and filters.
     */
    protected function hooks() {
        $importer = new Importer_Posts( $this->add_on );

        add_action( 'init', [ $this, 'init' ] );
        add_action( 'pmxi_saved_post', [ $importer, 'import_primary_category' ], 10, 1 );

    }

    /**
     * Method for setting up constants.
     */
    public function constants() {
        if ( ! defined( 'WPAI_YOASTSEO_PLUGIN_DIR_PATH' ) ) {
            // Dir path
            define( 'WPAI_YOASTSEO_PLUGIN_DIR_PATH', plugin_dir_path( __FILE__ ) );
        }
        
        if ( ! defined( 'WPAI_YOASTSEO_ROOT_DIR' ) ) {
            // Root directory for the plugin.
            define( 'WPAI_YOASTSEO_ROOT_DIR', str_replace( '\\', '/', dirname( __FILE__ ) ) );
        }
        
        if ( ! defined( 'WPAI_YOASTSEO_PLUGIN_PATH' ) ) {
            // Path to the main plugin file.
            define( 'WPAI_YOASTSEO_PLUGIN_PATH', WPAI_YOASTSEO_ROOT_DIR . '/' . basename( __FILE__ ) );
        }
        
        if ( ! defined( 'WPAI_YOASTSEO_ADDON_FIELD_PREFIX' ) ) {
            define( 'WPAI_YOASTSEO_ADDON_FIELD_PREFIX', 'wpai_yoast_addon_' );
        }
    }
     
    /** 
     * Method for including the Rapid Add-On API and our other classes for importing.
     */
    public function includes() {
        include_once( WPAI_YOASTSEO_PLUGIN_DIR_PATH . 'classes/class-importer-posts.php' );;
        include_once( WPAI_YOASTSEO_PLUGIN_DIR_PATH . 'classes/class-importer-taxonomies.php' );       
        include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
        include_once( WPAI_YOASTSEO_PLUGIN_DIR_PATH . 'classes/class-field-factory-posts.php' );            
        include_once( WPAI_YOASTSEO_PLUGIN_DIR_PATH . 'classes/class-field-factory-taxonomies.php' );
        include_once( WPAI_YOASTSEO_PLUGIN_DIR_PATH . 'classes/class-helper.php' ); 

    }

    public function pmxi_display_admin_notice( $addon_name, $addon_slug, $notice_text = false ) {
        if ( ! \is_admin() ) {
            return;
        }
        
        if ( ! $notice_text ) {
            $notice_text = $addon_name . ' requires WP All Import 3.7.6 or later, or WP All Import Pro 4.8.9 or later. Install or update <a href="https://wordpress.org/plugins/wp-all-import/" target="_blank">WP All Import</a> or <a href="https://www.wpallimport.com/" target="_blank">WP All Import Pro</a>.';
        }

        if ( ! get_option( sanitize_key( $addon_slug ) . '_notice_ignore' ) ) {
            ?>
            <div class="error notice is-dismissible wpallimport-dismissible" style="margin-top: 10px;" rel="<?php echo esc_attr( sanitize_key( $addon_slug ) ); ?>">
                <p><?php echo \wp_kses_post( $notice_text ); ?></p>
            </div>
            <?php
        }
    }

    /**
     * The init method.
     * 
     * This method sets up the import fields for each post type.
     */
    public function init() {
        // Helper functions to get post type and other things.
        $helper = new Helper();
        $this->post_type = $helper->get_post_type();
        
        // We have to check the post type to output different fields.
        switch ( $this->post_type ) {
            case 'taxonomies':
                $field_factory = new Field_Factory_Taxonomies( $this->add_on );
                $field_factory->output_fields();
                break;

            case 'shop_order':
            case 'import_users':
            case 'shop_customer':
                // Do not show fields for order/user/customer imports.
                break;
                
            default:
                $field_factory = new Field_Factory_Posts( $this->add_on );
                $field_factory->output_fields();
                break;
        }
                
        $this->add_on->set_import_function( array( $this, 'import' ) );

        if ( function_exists( 'is_plugin_active' ) ) {

			if ( ! is_plugin_active( 'wordpress-seo/wp-seo.php' ) && ! is_plugin_active( 'wordpress-seo-premium/wp-seo-premium.php' ) ) {

				$this->add_on->admin_notice(
					'The Yoast WordPress SEO Add-On requires WP All Import <a href="http://www.wpallimport.com/order-now/?utm_source=free-plugin&utm_medium=dot-org&utm_campaign=yoast" target="_blank">Pro</a> or <a href="http://wordpress.org/plugins/wp-all-import" target="_blank">Free</a>, and the <a href="https://yoast.com/wordpress/plugins/seo/">Yoast WordPress SEO</a> plugin.',
					array(
						'plugins' => array( 'wordpress-seo/wp-seo.php' )
					)
				);
			}

			if ( is_plugin_active( 'wordpress-seo/wp-seo.php' ) || is_plugin_active( 'wordpress-seo-premium/wp-seo-premium.php' ) ) {
				
				$this->add_on->run();
				
			}
		}
    }

    public function import( $post_id, $data, $import_options, $article ) { 

        switch( $this->post_type ) {
             case 'taxonomies':
                $importer = new Importer_Taxonomies( $this->add_on, $this->post_type );
                $importer->import( $post_id, $data, $import_options, $article );
                break;

            default:
                $importer = new Importer_Posts( $this->add_on );
                $importer->import( $post_id, $data, $import_options, $article );
                break;
        }

    }
 }

 $Yoast_SEO_Add_On_Instance = Add_On::get_instance();