<?php
// functions.php - Main Theme Functions

// Define theme constants
define('NETFLIX_THEME_DIR', get_template_directory());
define('NETFLIX_THEME_URI', get_template_directory_uri());
define('NETFLIX_VERSION', '3.0.0');

// Include required files
require_once NETFLIX_THEME_DIR . '/inc/custom-post-types.php';
require_once NETFLIX_THEME_DIR . '/inc/tmdb-api.php';
require_once NETFLIX_THEME_DIR . '/inc/tmdb-sync.php';
require_once NETFLIX_THEME_DIR . '/inc/tmdb-shortcodes.php';
require_once NETFLIX_THEME_DIR . '/inc/ajax-handlers.php';
require_once NETFLIX_THEME_DIR . '/inc/theme-options.php';

// Admin only files
if(is_admin()) {
    require_once NETFLIX_THEME_DIR . '/admin/tmdb-settings.php';
}

// Theme setup
function netflix_theme_setup() {
    // Theme supports
    add_theme_support('post-thumbnails');
    add_theme_support('custom-logo');
    add_theme_support('menus');
    add_theme_support('html5', array('search-form', 'comment-form', 'comment-list', 'gallery', 'caption'));
    add_theme_support('title-tag');
    
    // Image sizes
    add_image_size('movie-card', 300, 450, true);
    add_image_size('movie-hero', 1920, 1080, true);
    
    // Register menus
    register_nav_menus(array(
        'primary' => __('Primary Menu', 'netflix-clone'),
        'footer' => __('Footer Menu', 'netflix-clone')
    ));
}
add_action('after_setup_theme', 'netflix_theme_setup');

// Enqueue scripts and styles
function netflix_enqueue_scripts() {
    // Styles
    wp_enqueue_style('netflix-main', NETFLIX_THEME_URI . '/assets/css/main.css', array(), NETFLIX_VERSION);
    wp_enqueue_style('netflix-tmdb', NETFLIX_THEME_URI . '/assets/css/tmdb.css', array(), NETFLIX_VERSION);
    wp_enqueue_style('netflix-responsive', NETFLIX_THEME_URI . '/assets/css/responsive.css', array(), NETFLIX_VERSION);
    
    // Scripts
    wp_enqueue_script('netflix-main', NETFLIX_THEME_URI . '/assets/js/main.js', array('jquery'), NETFLIX_VERSION, true);
    wp_enqueue_script('netflix-tmdb', NETFLIX_THEME_URI . '/assets/js/tmdb.js', array('jquery'), NETFLIX_VERSION, true);
    wp_enqueue_script('netflix-infinite', NETFLIX_THEME_URI . '/assets/js/infinite-scroll.js', array('jquery'), NETFLIX_VERSION, true);
    
    // Localize script for AJAX
    wp_localize_script('netflix-main', 'netflix_ajax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('netflix_ajax_nonce'),
        'site_url' => site_url()
    ));
}
add_action('wp_enqueue_scripts', 'netflix_enqueue_scripts');

// Register sidebars
function netflix_widgets_init() {
    register_sidebar(array(
        'name' => __('Sidebar', 'netflix-clone'),
        'id' => 'sidebar-1',
        'description' => __('Add widgets here.', 'netflix-clone'),
        'before_widget' => '<section id="%1$s" class="widget %2$s">',
        'after_widget' => '</section>',
        'before_title' => '<h2 class="widget-title">',
        'after_title' => '</h2>',
    ));
}
add_action('widgets_init', 'netflix_widgets_init');

// Custom excerpt length
function netflix_excerpt_length($length) {
    return 20;
}
add_filter('excerpt_length', 'netflix_excerpt_length');

// Add custom body classes
function netflix_body_classes($classes) {
    if(is_singular('movie') || is_singular('tv_show')) {
        $classes[] = 'single-movie';
    }
    return $classes;
}
add_filter('body_class', 'netflix_body_classes');
?>
