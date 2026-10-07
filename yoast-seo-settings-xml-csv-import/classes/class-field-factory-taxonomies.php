<?php

namespace WPAI_Yoast_SEO_Import_Add_On;

// Prevent direct access to the file
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class Field_Factory_Taxonomies {
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

    public function get_all_fields( $section = '' ) {
        if ( empty( $section ) ) {
            return [];
        }

        $fields = [
            'Main' => [
                'Focus Keyword' => [
                    'name'    => 'wpseo_focuskw',
                    'type'    => 'text',
                    'options' => '',
                    'tooltip' => 'Pick the main keyword or keyphrase that this post/page is about.'
                ],
                'SEO Title' => [
                    'name'    => 'wpseo_title',
                    'type'    => 'text',
                    'options' => '',
                    'tooltip' => 'The SEO title defaults to what is generated based on this site\'s title template for this post type.'
                ],
                'Meta Description' => [
                    'name'    => 'wpseo_desc',
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

    public function output_fields() {
        // Main fields
        $fields = $this->get_all_fields( 'Main' );

        if ( ! empty( $fields ) && is_array( $fields ) ) {
            foreach ( $fields as $field_title => $field_info ) {
                $this->add_on->add_field( $field_info['name'], $field_title, $field_info['type'], $field_info['options'], $field_info['tooltip'] );
            }
        }

        // Facebook section
        $this->add_on->add_options(
            $this->add_on->add_field( 'wpseo_opengraph-title', 'Facebook Title', 'text', null, "If you don't want to use the post title for sharing the post on Facebook but instead want another title there, import it here." ),
            'Facebook Options',
            [
                $this->add_on->add_field( 'wpseo_opengraph-description', 'Description', 'text', null, "If you don't want to use the meta description for sharing the post on Facebook but want another description there, write it here." ),
                $this->add_on->add_field( 'wpseo_opengraph-image', 'Image', 'image', null, "If you want to override the image used on Facebook for this post, import one here. The recommended image size for Facebook is 1200 x 628px.")
            ]
        );

        // X section
        $this->add_on->add_options(
            $this->add_on->add_field( 'wpseo_twitter-title', 'X Title', 'text', null, "If you don't want to use the post title for sharing the post on X but instead want another title there, import it here." ),
            'X Options',
            [
                $this->add_on->add_field( 'wpseo_twitter-description', 'Description', 'text', null, "If you don't want to use the meta description for sharing the post on X but want another description there, import it here." ),
                $this->add_on->add_field( 'wpseo_twitter-image', 'Image', 'image', null, "If you want to override the image used on X for this post, import one here. The recommended image size for X is 1024 x 512px."),
            ]
        );

        // Advanced SEO options
        $this->add_on->add_options(
            null,
            'Advanced SEO Options',
            [
                $this->add_on->add_field( 'wpseo_is_cornerstone', 'Mark as cornerstone content', 'radio', 
                [
                    '' => 'No',
                    '1' => 'Yes'
                ]),
                $this->add_on->add_field( 'wpseo_noindex', 'Meta Robots Index', 'radio', 
                    [
                        '' => 'default',
                        'index' => 'index',
                        'noindex' => 'noindex',
                    ],
                    "This setting can be overwritten by Yoast WordPress SEO's sitewide privacy settings"
                ),
                $this->add_on->add_field( 'wpseo_sitemap_include', 'Include in Sitemap', 'radio', 
                    [
                        '' => 'Auto detect',
                        'always' => 'Always include',
                        'never' => 'Never include'
                    ],
                    'Should this page be in the XML Sitemap at all times, regardless of Robots Meta settings?'
                ),
                $this->add_on->add_field( 'wpseo_bctitle', 'Breadcrumbs Title', 'text' ),
                $this->add_on->add_field( 'wpseo_canonical', 'Canonical URL', 'text', null, 'The canonical URL that this page should point to, leave empty to default to permalink. Cross domain canonical supported too.' )
        
            ]
            );
    }
}