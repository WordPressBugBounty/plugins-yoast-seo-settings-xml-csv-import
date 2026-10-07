<?php

namespace WPAI_Yoast_SEO_Import_Add_On;

// Prevent direct access to the file
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class Field_Factory_Posts {
    protected $add_on;
    
    public $helper;
    
    public $post_type;
    
    /**
     * @param \RapidAddon $addon_object
     */
    public function __construct( \PMXI_RapidAddon $addon_object ) {
        $this->add_on    = $addon_object;
        $this->helper    = new Helper();
        $this->post_type = $this->helper->get_post_type();
    }

    public function output_fields() {
        // Main fields
        $fields = $this->get_all_fields( 'Main' );
        if ( ! empty( $fields ) && is_array( $fields ) ) {
            foreach ( $fields as $field_title => $field_info ) {
                $this->add_on->add_field( $field_info['name'], $field_title, $field_info['type'], $field_info['options'], $field_info['tooltip'] );
            }
        }

        // Facebook fields
        $this->add_on->add_options(
            $this->add_on->add_field( '_yoast_wpseo_opengraph-title', 'Facebook Title', 'text', null, "If you don't want to use the post title for sharing the post on Facebook but instead want another title there, import it here." ),
            'Facebook Options',
            [
                $this->add_on->add_field( '_yoast_wpseo_opengraph-description', 'Description', 'text', null, "If you don't want to use the meta description for sharing the post on Facebook but want another description there, write it here." ),
                $this->add_on->add_field( '_yoast_wpseo_opengraph-image', 'Image', 'image', null, "If you want to override the image used on Facebook for this post, import one here. The recommended image size for Facebook is 1200 x 628px.")
            ]
        );

        // X fields
        $this->add_on->add_options(
            $this->add_on->add_field( '_yoast_wpseo_twitter-title', 'X Title', 'text', null, "If you don't want to use the post title for sharing the post on X but instead want another title there, import it here." ),
            'X Options',
            [
                $this->add_on->add_field( '_yoast_wpseo_twitter-description', 'Description', 'text', null, "If you don't want to use the meta description for sharing the post on X but want another description there, import it here." ),
                $this->add_on->add_field( '_yoast_wpseo_twitter-image', 'Image', 'image', null, "If you want to override the image used on X for this post, import one here. The recommended image size for X is 1024 x 512px."),
            ]
        );

        // Advanced SEO Options
        $this->add_on->add_options(
            null,
            'Advanced SEO Options',
            array(
                $this->add_on->add_field( '_yoast_wpseo_meta-robots-noindex', 'Meta Robots Index', 'radio', 
                    array(
                        ''  => 'default',
                        '2' => 'index',
                        '1' => 'noindex',
                    ),
                    "This setting can be overwritten by Yoast WordPress SEO's sitewide privacy settings"
                ),
                $this->add_on->add_field( '_yoast_wpseo_meta-robots-nofollow', 'Meta Robots Nofollow', 'radio', 
                    array(
                        ''  => 'Follow',
                        '1' => 'Nofollow'
                    ) ),
                    $this->add_on->add_field( '_yst_is_cornerstone', 'This article is cornerstone content', 'radio', 
                    array(
                        ''  => 'No',
                        '1' => 'Yes'
                    ) ),
                    $this->add_on->add_field( '_yoast_wpseo_meta-robots-adv', 'Meta Robots Advanced', 'radio', 
                    array(
                        ''             => 'default',
                        'none'         => 'None',
                        'noimageindex' => 'No Image Index',
                        'noarchive'    => 'No Archive',
                        'nosnippet'    => 'No Snippet'
                    ),
                    'Advanced meta robots settings for this page.'
                ),
                $this->add_on->add_field( '_yoast_wpseo_bctitle', 'Breadcrumbs Title', 'text' ),
                $this->add_on->add_field( '_yoast_wpseo_canonical', 'Canonical URL', 'text', null, 'The canonical URL that this page should point to, leave empty to default to permalink. Cross domain canonical supported too.' ),
                $this->add_on->add_field( '_yoast_wpseo_primary_category_addon', 'Primary Category', 'text', null, 'The name or slug of the primary category' )
            )
        );
    }

    public function get_all_fields( $section = '' ) {
        if ( empty( $section ) ) {
            return [];
        }

        $fields = [
            'Main' => [
                'Focus Keyword' => [
                    'name'    => '_yoast_wpseo_focuskw',
                    'type'    => 'text',
                    'options' => '',
                    'tooltip' => 'Pick the main keyword or keyphrase that this post/page is about.'
                ],
                'SEO Title' => [
                    'name'    => '_yoast_wpseo_title',
                    'type'    => 'text',
                    'options' => '',
                    'tooltip' => 'The SEO title defaults to what is generated based on this sites title template for this posttype.'
                ],
                'Meta Description' => [
                    'name'    => '_yoast_wpseo_metadesc',
                    'type'    => 'text',
                    'options' => '',
                    'tooltip' => 'The meta description will be limited to 156 chars. It is often shown as the black text under the title in a search result. For this to work it has to contain the keyword that was searched for.'
                ]
            ]
        ];

        if ( isset( $fields[ $section ] ) ) {
            return $fields[ $section ];
        } else {
            return [];
        }
    }
}