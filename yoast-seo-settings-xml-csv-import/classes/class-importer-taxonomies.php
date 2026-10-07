<?php

namespace WPAI_Yoast_SEO_Import_Add_On;

// Prevent direct access to the file
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class Importer_Taxonomies {

	protected $add_on;
	public    $helper;
	public    $post_type;

    public function __construct( \PMXI_RapidAddon $addon_object, $post_type = 'taxonomy' ) {
        $this->add_on    = $addon_object;
        $this->helper    = new Helper();
        $this->post_type = $post_type;
    }

	protected function rebuild_yoast_indexable( $term_id ) {
		$watcher = 'Yoast\WP\SEO\Integrations\Watchers\Indexable_Term_Watcher';

		if ( ! function_exists( 'YoastSEO' ) || ! class_exists( $watcher ) ) {
			return;
		}

		try {
			YoastSEO()->classes->get( $watcher )->build_indexable( $term_id );
		} catch ( \Throwable $e ) {
			$this->helper->log( 'Could not refresh Yoast SEO data for term `' . $term_id . '`.' );
		}
	}

	public function import( $term_id, $data, $import_options, $article ) {
        $factory = new Field_Factory_Taxonomies( $this->add_on );

		$this->helper->log( '<strong>Yoast ADD-ON:</strong>' );

		$meta          = get_option( 'wpseo_taxonomy_meta' );
		$taxonomy_type = $import_options['options']['taxonomy_type'];

        // Main fields
        $fields  = $factory->get_all_fields( 'Main' );

        foreach ( $fields as $field_label => $field_info ) {
            if ( $this->helper->is_mapped( $field_info['name'], $import_options ) && ( empty( $article['ID'] ) || $this->add_on->can_update_meta( $field_info['name'], $import_options ) ) ) {
                $meta[ $taxonomy_type ][ $term_id ][ $field_info['name'] ] = $data[ $field_info['name'] ];
            }
        }

		// Facebook text fields
		$fields = array(
			'wpseo_opengraph-title',
			'wpseo_opengraph-description'
		);

		foreach ( $fields as $field ) {
			if ( $this->helper->is_mapped( $field, $import_options ) && ( empty( $article['ID'] ) || $this->add_on->can_update_meta( $field, $import_options ) ) ) {
                $meta[ $taxonomy_type ][ $term_id ][ $field ] = $data[ $field ];
            }
		}

		// Facebook image field
		$field = 'wpseo_opengraph-image';

		if ( $this->helper->is_mapped( $field, $import_options ) && ( empty( $article['ID'] ) || ( $this->add_on->can_update_meta( $field, $import_options ) && $this->add_on->can_update_image( $import_options ) ) ) ) {
			$meta[ $taxonomy_type ][ $term_id ][ $field ]         = wp_get_attachment_url( $data[ $field ]['attachment_id'] );
			$meta[ $taxonomy_type ][ $term_id ][ $field . '-id' ] = $data[ $field ]['attachment_id'];
        }

		// Twitter text fields
		$fields = array(
			'wpseo_twitter-title',
			'wpseo_twitter-description'
		);

		foreach ( $fields as $field ) {
			if ( $this->helper->is_mapped( $field, $import_options ) && ( empty( $article['ID'] ) || $this->add_on->can_update_meta( $field, $import_options ) ) ) {
                $meta[ $taxonomy_type ][ $term_id ][ $field ] = $data[ $field ];
            }
		}

		// Twitter image fields
		$field = 'wpseo_twitter-image';

		if ( $this->helper->is_mapped( $field, $import_options ) && ( empty( $article['ID'] ) || ( $this->add_on->can_update_meta( $field, $import_options ) && $this->add_on->can_update_image( $import_options ) ) ) ) {
            $meta[ $taxonomy_type ][ $term_id ][ $field ]         = wp_get_attachment_url( $data[ $field ]['attachment_id'] );
			$meta[ $taxonomy_type ][ $term_id ][ $field . '-id' ] = $data[ $field ]['attachment_id'];
        }

		// Advanced SEO Options
		$fields = array(
			'wpseo_noindex',
			'wpseo_sitemap_include',
			'wpseo_canonical',
			'wpseo_bctitle',
			'wpseo_is_cornerstone'
		);

		foreach ( $fields as $field ) {
			if ( $this->helper->is_mapped( $field, $import_options ) && ( empty( $article['ID'] ) || $this->add_on->can_update_meta( $field, $import_options ) ) ) {
                $meta[ $taxonomy_type ][ $term_id ][ $field ] = $data[ $field ];
            }
		}

		// Update the option to include our newly imported SEO data for taxonomies
		update_option( 'wpseo_taxonomy_meta', $meta );

		// Yoast serves terms from its indexable, which it last built when the term was saved, before this option changed.
		$this->rebuild_yoast_indexable( $term_id );

		if ( isset( $meta[ $taxonomy_type ] ) ) {
			if ( isset( $meta[ $taxonomy_type ][ $term_id ] ) ) {
				if ( is_array( $meta[ $taxonomy_type ][ $term_id ] ) ) {
					foreach ( $meta[ $taxonomy_type ][ $term_id ] as $field_name => $field_value ) {
						$field_value = maybe_serialize( $field_value );
						$field_value = wp_strip_all_tags( $field_value );
						$this->helper->log( 'Property `' . $field_name . '` updated with value `' . $field_value . '`' );
					}
				}
			}
		}
	}
}