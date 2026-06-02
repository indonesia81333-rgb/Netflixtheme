<?php
// inc/tmdb-sync.php

class TMDB_Sync {
    
    private $tmdb_api;
    
    public function __construct() {
        global $tmdb_api;
        $this->tmdb_api = $tmdb_api;
        
        add_action('wp_ajax_sync_popular_movies', array($this, 'sync_popular_movies'));
        add_action('wp_ajax_sync_trending', array($this, 'sync_trending'));
        add_action('wp_ajax_update_all_movies', array($this, 'update_all_movies'));
        
        // Cron jobs
        add_action('tmdb_sync_popular', array($this, 'sync_popular_movies_cron'));
        add_action('tmdb_sync_trending', array($this, 'sync_trending_cron'));
        
        if(!wp_next_scheduled('tmdb_sync_popular')) {
            wp_schedule_event(time(), 'daily', 'tmdb_sync_popular');
        }
    }
    
    /**
     * Sync popular movies from TMDB
     */
    public function sync_popular_movies() {
        $page = 1;
        $imported = 0;
        
        for($page = 1; $page <= 5; $page++) {
            $movies = $this->tmdb_api->get_popular_movies($page);
            
            if(!$movies || !isset($movies['results'])) break;
            
            foreach($movies['results'] as $movie) {
                if($this->import_movie($movie['id'])) {
                    $imported++;
                }
            }
        }
        
        return $imported;
    }
    
    public function sync_popular_movies_cron() {
        $imported = $this->sync_popular_movies();
        error_log("TMDB Sync: Imported {$imported} popular movies");
    }
    
    /**
     * Sync trending content
     */
    public function sync_trending() {
        $trending = $this->tmdb_api->get_trending('movie', 'week');
        $imported = 0;
        
        if($trending && isset($trending['results'])) {
            foreach($trending['results'] as $item) {
                if($this->import_movie($item['id'])) {
                    $imported++;
                }
            }
        }
        
        return $imported;
    }
    
    /**
     * Import single movie from TMDB
     */
    private function import_movie($tmdb_id) {
        // Check if exists
        $existing = get_posts(array(
            'post_type' => 'movie',
            'meta_key' => '_tmdb_id',
            'meta_value' => $tmdb_id,
            'posts_per_page' => 1
        ));
        
        if(!empty($existing)) return false;
        
        $data = $this->tmdb_api->get_movie($tmdb_id);
        
        if(!$data) return false;
        
        $post_id = wp_insert_post(array(
            'post_title' => $data['title'],
            'post_content' => $data['overview'],
            'post_excerpt' => wp_trim_words($data['overview'], 30),
            'post_status' => 'publish',
            'post_type' => 'movie',
            'meta_input' => array(
                '_tmdb_id' => $tmdb_id,
                '_vote_average' => $data['vote_average'],
                '_vote_count' => $data['vote_count'],
                '_release_date' => $data['release_date'],
                '_runtime' => $data['runtime']
            )
        ));
        
        if($post_id && $data['poster_path']) {
            $this->set_featured_image($data['poster_path'], $post_id);
        }
        
        // Set genres
        if(isset($data['genres'])) {
            foreach($data['genres'] as $genre) {
                wp_set_object_terms($post_id, $genre['name'], 'genre', true);
            }
        }
        
        return true;
    }
    
    /**
     * Update all existing movies with latest TMDB data
     */
    public function update_all_movies() {
        $movies = get_posts(array(
            'post_type' => 'movie',
            'posts_per_page' => -1,
            'meta_key' => '_tmdb_id',
            'meta_compare' => 'EXISTS'
        ));
        
        $updated = 0;
        
        foreach($movies as $movie) {
            $tmdb_id = get_post_meta($movie->ID, '_tmdb_id', true);
            if($tmdb_id && $this->update_movie_data($movie->ID, $tmdb_id)) {
                $updated++;
            }
        }
        
        return $updated;
    }
    
    private function update_movie_data($post_id, $tmdb_id) {
        $data = $this->tmdb_api->get_movie($tmdb_id);
        
        if(!$data) return false;
        
        update_post_meta($post_id, '_tmdb_data', $data);
        update_post_meta($post_id, '_vote_average', $data['vote_average']);
        update_post_meta($post_id, '_vote_count', $data['vote_count']);
        
        return true;
    }
    
    private function set_featured_image($poster_path, $post_id) {
        $image_url = $this->tmdb_api->get_image_url($poster_path, 'original');
        
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        
        $attachment_id = media_sideload_image($image_url, $post_id, null, 'id');
        
        if(!is_wp_error($attachment_id)) {
            set_post_thumbnail($post_id, $attachment_id);
        }
    }
}

new TMDB_Sync();
?>
