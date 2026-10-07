<?php

namespace WPAI_Yoast_SEO_Import_Add_On;

// Prevent direct access to the file
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class Importer_Posts {
    protected $add_on;
    public $helper;
    public $logger = null;
    public $post_type;

    /**
     * Construct method that sets up the add-on object and a helper object.
     * 
     * @param RapidAddon $addon_object
     * @param string $post_type
     */
    public function __construct( \PMXI_RapidAddon $addon_object, $post_type = 'post' ) {
        $this->add_on    = $addon_object;
        $this->helper    = new Helper();
        $this->post_type = $post_type;
    }

    public function import( $post_id, $data, $import_options, $article ) {
        $factory = new Field_Factory_Posts( $this->add_on );

        $this->helper->log( '<strong>Yoast ADD-ON:</strong>' );

        // Main fields
        $fields  = $factory->get_all_fields( 'Main' );

        foreach ( $fields as $field_label => $field_info ) {
            if ( $this->helper->is_mapped( $field_info['name'], $import_options ) && ( empty( $article['ID'] ) || $this->add_on->can_update_meta( $field_info['name'], $import_options ) ) ) {
                $this->helper->update_meta( $post_id, $field_info['name'], $data[ $field_info['name'] ] );
            }
        }

        // Facebook text fields
        $facebook_text_fields = array(
            '_yoast_wpseo_opengraph-title',
            '_yoast_wpseo_opengraph-description'
        );

        foreach ( $facebook_text_fields as $field ) {
            if ( $this->helper->is_mapped( $field, $import_options ) && ( empty( $article['ID'] ) || $this->add_on->can_update_meta( $field, $import_options ) ) ) {
                $this->helper->update_meta( $post_id, $field, $data[ $field ] );
            }
        }

        // Facebook image field
        $facebook_image_field = '_yoast_wpseo_opengraph-image';

        if ( $this->helper->is_mapped( $facebook_image_field, $import_options ) && ( empty( $article['ID'] ) || ( $this->add_on->can_update_meta( $facebook_image_field, $import_options ) && $this->add_on->can_update_image( $import_options ) ) ) ) {
            $this->helper->update_meta( $post_id, $facebook_image_field, wp_get_attachment_url( $data[ $facebook_image_field ]['attachment_id'] ) );
            $this->helper->update_meta( $post_id, $facebook_image_field . '-id', (string) $data[ $facebook_image_field ]['attachment_id'] );
        }

        // Twitter text fields
        $twitter_text_fields = array(
            '_yoast_wpseo_twitter-title',
            '_yoast_wpseo_twitter-description'
        );

        foreach ( $twitter_text_fields as $field ) {
            if ( $this->helper->is_mapped( $field, $import_options ) && ( empty( $article['ID'] ) || $this->add_on->can_update_meta( $field, $import_options ) ) ) {
                $this->helper->update_meta( $post_id, $field, $data[ $field ] );
            }
        }

        $twitter_image_field    = '_yoast_wpseo_twitter-image';

        if ( $this->helper->is_mapped( $twitter_image_field, $import_options ) && ( empty( $article['ID'] ) || ( $this->add_on->can_update_meta( $twitter_image_field, $import_options ) && $this->add_on->can_update_image( $import_options ) ) ) ) {
            $this->helper->update_meta( $post_id, $twitter_image_field, wp_get_attachment_url( $data[ $twitter_image_field ]['attachment_id'] ) );
            $this->helper->update_meta( $post_id, $twitter_image_field . '-id', (string) $data[ $twitter_image_field ]['attachment_id'] );
        }

        // Advanced SEO Options
        $fields = array(
            '_yoast_wpseo_meta-robots-noindex',
            '_yoast_wpseo_meta-robots-nofollow',
            '_yst_is_cornerstone',
            '_yoast_wpseo_meta-robots-adv',
            '_yoast_wpseo_bctitle',
            '_yoast_wpseo_canonical'
        );

        foreach ( $fields as $field ) {
            if ( ! $this->helper->is_mapped( $field, $import_options ) ) {
                continue;
            }
            $import_data = $data[ $field ];
            if ( $field == '_yst_is_cornerstone' ) {
                $field = '_yoast_wpseo_is_cornerstone';
            }
            if ( empty( $article['ID'] ) || $this->add_on->can_update_meta( $field, $import_options ) ) {
                $this->helper->update_meta( $post_id, $field, $import_data );
            }
        }

        // Import primary category
        $field = '_yoast_wpseo_primary_category_addon';
        $title = $data[ $field ];

        $cat_slug = sanitize_title( $title ); // Get the slug for the Primary Category so we can match it later

        update_post_meta( $post_id, '_yoast_wpseo_addon_category_slug', $cat_slug );

        // Set post metas for regular categories and product categories so we know if we can update them after pmxi_saved_post hook fires.

        if ( empty( $article['ID'] ) or $this->add_on->can_update_meta( '_yoast_wpseo_primary_category', $import_options ) ) {

            update_post_meta( $post_id, '_yoast_wpseo_primary_category_can_update', 1 );
        
        } else {

            update_post_meta( $post_id, '_yoast_wpseo_primary_category_can_update', 0 );

        }

        if ( empty( $article['ID'] ) or $this->add_on->can_update_meta( '_yoast_wpseo_primary_product_cat', $import_options ) ) {

            update_post_meta( $post_id, '_yoast_wpseo_primary_product_cat_can_update', 1 );

        } else {

            update_post_meta( $post_id, '_yoast_wpseo_primary_product_cat_can_update', 0 );

        }
    }

    public function import_primary_category( $post_id ) {

        $this->helper->log( '<strong>Yoast ADD-ON:</strong>' );

        $product_update = get_post_meta( $post_id, '_yoast_wpseo_primary_product_cat_can_update', true ); // Can we update product primary categories?
    
        $post_update = get_post_meta( $post_id, '_yoast_wpseo_primary_category_can_update', true ); // Can we update post primary categories?
    
        // Only proceed if we have permission to update one of them.
    
        if ( $post_update == 1 or $product_update == 1 ) {
        
            $cat_slug = get_post_meta( $post_id, '_yoast_wpseo_addon_category_slug', true );
    
            if ( ! empty( $cat_slug ) ) {
    
                $post_type = get_post_type( $post_id );
    
                if ( !empty( $cat_slug ) and !empty( $post_type ) ) {
    
                    if ( $post_type == 'product' and $product_update == 1 ) { // Products use 'product_cat' instead of 'categories'.
    
                        if ( $cat = get_term_by( 'slug', $cat_slug, 'product_cat' ) ) {
                            $cat_id = $cat->term_id;
    
                            if ( !empty( $cat_id ) ) {
      
                                update_post_meta( $post_id, '_yoast_wpseo_primary_product_cat', $cat_id );
                                $this->helper->log( 'Successfully assigned primary category `' . $cat_slug . '`.' );
      
                            }
                        } else {
                            $this->helper->log( 'Could not fetch primary category term `' . $cat_slug . '`.' );
                        }
    
                    } else {
    
                        if ( $post_update == 1 ) {
    
                            if ( $cat = get_term_by( 'slug', $cat_slug, 'category' ) ) {
                                $cat_id = $cat->term_id;
    
                                if ( !empty( $cat_id ) ) {
          
                                    update_post_meta( $post_id, '_yoast_wpseo_primary_category', $cat_id );
                                    $this->helper->log( 'Property `_yoast_wpseo_primary_category` updated with value `' . $cat_slug . '`' );

                                }
                            } else {
                                $this->helper->log( 'Property `_yoast_wpseo_primary_category` failed to update, could not find category term `' . $cat_slug . '`' );
                            }
                        }
                    }
                }
            }
        }
        delete_post_meta( $post_id, '_yoast_wpseo_primary_category_can_update' );
        delete_post_meta( $post_id, '_yoast_wpseo_primary_product_cat_can_update' );
        delete_post_meta( $post_id, '_yoast_wpseo_addon_category_slug' );
    }
}