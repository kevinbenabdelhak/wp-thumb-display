<?php
/*
 * Plugin Name: WP Thumb Display
 * Plugin URI: https://kevin-benabdelhak.fr/plugins/wp-thumb-display/
 * Description: WP Thumb Display ajoute simplement une colonne d'image en avant dans l'admin WordPress pour tous les types de publication.
 * Version: 1.0
 * Author: Kevin Benabdelhak
 * Author URI: https://kevin-benabdelhak.fr/
 * Contributors: kevinbenabdelhak
*/

if (!defined('ABSPATH')) {
    exit;
}

function add_featured_image_column($columns) {
    $new_columns = array();
    foreach ($columns as $key => $title) {
        $new_columns[$key] = $title;
        if ($key === 'cb') {
            $current_order = isset($_GET['sort_by_image']) ? $_GET['sort_by_image'] : 'no_image_first';
            $next_order = ('no_image_first' === $current_order) ? 'image_first' : 'no_image_first';
            
        // Ajout du lien cliquable pour trier par la colonne image 
            $url = add_query_arg('sort_by_image', $next_order);
            $new_columns['thumb'] = '<a href="' . esc_url($url) . '">' . __('Image', 'wp-thumb-display') . '</a>';
        }
    }
    return $new_columns;
}

function display_featured_image_column($column_name, $post_id) {
    if ($column_name === 'thumb') {
        $post_thumbnail_id = get_post_thumbnail_id($post_id);
        if ($post_thumbnail_id) {
            $post_thumbnail_img = wp_get_attachment_image_src($post_thumbnail_id, 'thumbnail');
            echo '<img id="featured_image_' . esc_attr($post_id) . '" src="' . esc_url($post_thumbnail_img[0]) . '" alt="' . esc_attr__('Image en avant', 'wp-thumb-display') . '" />';
        } else {
            echo __('Aucune image.', 'wp-thumb-display');
        }
    }
}

function custom_sort_posts_by_featured_image($query) {
    global $pagenow;

    if (is_admin() && $pagenow == 'edit.php' && isset($_GET['sort_by_image'])) {
        $sort_order = $_GET['sort_by_image'];
        
        if ($sort_order === 'no_image_first') {
            $meta_query = array(
                'relation' => 'OR',
                array(
                    'key' => '_thumbnail_id',
                    'compare' => 'NOT EXISTS',
                ),
                array(
                    'key' => '_thumbnail_id',
                    'compare' => 'EXISTS',
                ),
            );
            $query->set('meta_query', $meta_query);
            $query->set('orderby', 'meta_value');
            $query->set('order', 'ASC');
        } elseif ($sort_order === 'image_first') {
            $meta_query = array(
                'relation' => 'OR',
                array(
                    'key' => '_thumbnail_id',
                    'compare' => 'EXISTS',
                ),
                array(
                    'key' => '_thumbnail_id',
                    'compare' => 'NOT EXISTS',
                ),
            );
            $query->set('meta_query', $meta_query);
            $query->set('orderby', 'meta_value');
            $query->set('order', 'DESC');
        }
    }
}

/** Image plus petite */
function add_admin_custom_styles() {
    echo '<style>
        img[id^="featured_image_"] {
            max-width: 60px;
            height: auto;
        }
        .column-thumb {
            width: 60px;
        }
        .manage-column.column-thumb a {
            text-decoration: none;
        }
    </style>';
}

function add_featured_image_column_all_post_types() {
    $post_types = get_post_types(array('public' => true), 'names');
    foreach ($post_types as $post_type) {
        add_filter("manage_{$post_type}_posts_columns", 'add_featured_image_column');
        add_action("manage_{$post_type}_posts_custom_column", 'display_featured_image_column', 10, 2);
    }
}

// Initialiser le plugin
function wp_thumb_display_init() {
    add_action('admin_init', 'add_featured_image_column_all_post_types');
    add_action('admin_head', 'add_admin_custom_styles');
    add_action('pre_get_posts', 'custom_sort_posts_by_featured_image');
}

add_action('plugins_loaded', 'wp_thumb_display_init');