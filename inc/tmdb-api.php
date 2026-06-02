<?php
// inc/tmdb-api.php - Complete TMDB Integration

class TMDB_API {
    
    private $api_key;
    private $api_url = 'https://api.themoviedb.org/3/';
    private $image_url = 'https://image.tmdb.org/t/p/';
    private $cache_time = 86400; // 24 hours
    
    public function __construct() {
        $this->api_key = get_option('tmdb_api_key', '');
        
        if($this->api_key) {
            add_action('wp_ajax_search_tmdb', array($this, 'ajax_search_tmdb'));
            add_action('wp_ajax_fetch_tmdb_data', array($this, 'ajax_fetch_tmdb_data'));
            add_action('wp_ajax_import_tmdb_movie', array($this, 'ajax_import_tmdb_movie'));
        }
    }
    
    /**
     * Make API request to TMDB
     */
    public function request($endpoint, $params = array()) {
        if(!$this->api_key) return false;
        
        $cache_key = 'tmdb_' . md5($endpoint . serialize($params));
        $cached = get_transient($cache_key);
        
        if($cached !== false) {
            return $cached;
        }
        
        $params['api_key'] = $this->api_key;
        $url = $this->api_url . $endpoint . '?' . http_build_query($params);
        
        $response = wp_remote_get($url, array(
            'timeout' => 30,
            'headers' => array('Accept' => 'application/json')
        ));
        
        if(is_wp_error($response)) {
            return false;
        }
        
        $data = json_decode(wp_remote_retrieve_body($response), true);
        
        if(isset($data['success']) && $data['success'] === false) {
            return false;
        }
        
        set_transient($cache_key, $data, $this->cache_time);
        
        return $data;
    }
    
    /**
     * Search movies or TV shows
     */
    public function search($query, $type = 'movie', $page = 1) {
        $endpoint = ($type == 'movie') ? 'search/movie' : 'search/tv';
        return $this->request($endpoint, array(
            'query' => $query,
            'page' => $page,
            'include_adult' => false
        ));
    }
    
    /**
     * Get movie details
     */
    public function get_movie($id, $append = 'videos,credits,similar,recommendations,images') {
        return $this->request("movie/{$id}", array(
            'append_to_response' => $append,
            'language' => get_option('tmdb_language', 'en-US'),
            'include_image_language' => 'en,null'
        ));
    }
    
    /**
     * Get TV show details
     */
    public function get_tv($id, $append = 'videos,credits,similar,recommendations,images,season/1') {
        return $this->request("tv/{$id}", array(
            'append_to_response' => $append,
            'language' => get_option('tmdb_language', 'en-US')
        ));
    }
    
    /**
     * Get popular movies
     */
    public function get_popular_movies($page = 1) {
        return $this->request('movie/popular', array('page' => $page));
    }
    
    /**
     * Get trending for the week
     */
    public function get_trending($media_type = 'all', $time_window = 'week') {
        return $this->request("trending/{$media_type}/{$time_window}");
    }
    
    /**
     * Get now playing movies
     */
    public function get_now_playing($page = 1) {
        return $this->request('movie/now_playing', array('page' => $page));
    }
    
    /**
     * Get top rated movies
     */
    public function get_top_rated($page = 1) {
        return $this->request('movie/top_rated', array('page' => $page));
    }
    
    /**
     * Get upcoming movies
     */
    public function get_upcoming($page = 1) {
        return $this->request('movie/upcoming', array('page' => $page));
    }
    
    /**
     * Get on the air TV shows
     */
    public function get_on_the_air($page = 1) {
        return $this->request('tv/on_the_air', array('page' => $page));
    }
    
    /**
     * Get popular TV shows
     */
    public function get_popular_tv($page = 1) {
        return $this->request('tv/popular', array('page' => $page));
    }
    
    /**
     * Get movie genres
     */
    public function get_movie_genres() {
        $data = $this->request('genre/movie/list');
        return isset($data['genres']) ? $data['genres'] : array();
    }
    
    /**
     * Get TV genres
     */
    public function get_tv_genres() {
        $data = $this->request('genre/tv/list');
        return isset($data['genres']) ? $data['genres'] : array();
    }
    
    /**
     * Discover movies by filters
     */
    public function discover_movies($filters = array()) {
        return $this->request('discover/movie', $filters);
    }
    
    /**
     * Get image URL
     */
    public function get_image_url($path, $size = 'w500') {
        if(!$path) return '';
        return $this->image_url . $size . $path;
    }
    
    /**
     * Get backdrop image URL
     */
    public function get_backdrop_url($path, $size = 'original') {
        return $this->get_image_url($path, $size);
    }
    
    /**
     * Get YouTube trailer key
     */
    public function get_trailer_key($videos) {
        if(empty($videos['results'])) return '';
        
        foreach($videos['results'] as $video) {
            if($video['type'] == 'Trailer' && $video['site'] == 'YouTube') {
                return $video['key'];
            }
        }
        
        return isset($videos['results'][0]['key']) ? $videos['results'][0]['key'] : '';
    }
    
    /**
     * AJAX: Search TMDB
     */
    public function ajax_search_tmdb() {
        check_ajax_referer('tmdb_search_nonce', 'nonce');
        
        $search = sanitize_text_field($_POST['search']);
        $type = sanitize_text_field($_POST['type']);
        
        if(strlen($search) < 2) {
            wp_send_json_error(array('message' => 'Please enter at least 2 characters'));
        }
        
        $results = $this->search($search, $type);
        
        if($results && isset($results['results'])) {
            wp_send_json_success(array('results' => $results['results']));
        } else {
            wp_send_json_error(array('message' => 'No results found'));
        }
    }
    
    /**
     * AJAX: Fetch TMDB data and populate post
     */
    public function ajax_fetch_tmdb_data() {
        check_ajax_referer('tmdb_fetch_nonce', 'nonce');
        
        $tmdb_id = intval($_POST['tmdb_id']);
        $type = sanitize_text_field($_POST['type']);
        $post_id = intval($_POST['post_id']);
        
        if($type == 'movie') {
            $data = $this->get_movie($tmdb_id);
        } else {
            $data = $this->get_tv($tmdb_id);
        }
        
        if(!$data) {
            wp_send_json_error(array('message' => 'Failed to fetch data from TMDB'));
        }
        
        // Update post data
        $title = $data['title'] ?? $data['name'];
        $overview = $data['overview'];
        $release_date = $data['release_date'] ?? $data['first_air_date'];
        
        wp_update_post(array(
            'ID' => $post_id,
            'post_title' => $title,
            'post_excerpt' => wp_trim_words($overview, 30),
            'post_content' => $overview
        ));
        
        // Save TMDB data as meta
        update_post_meta($post_id, '_tmdb_data', $data);
        update_post_meta($post_id, '_tmdb_id', $tmdb_id);
        update_post_meta($post_id, '_vote_average', $data['vote_average']);
        update_post_meta($post_id, '_vote_count', $data['vote_count']);
        update_post_meta($post_id, '_release_date', $release_date);
        
        // Save poster as featured image
        if($data['poster_path']) {
            $image_url = $this->get_image_url($data['poster_path'], 'original');
            $this->set_featured_image($image_url, $post_id);
        }
        
        // Save genres
        if(isset($data['genres'])) {
            $genre_ids = array();
            foreach($data['genres'] as $genre) {
                $genre_ids[] = $genre['id'];
                wp_set_object_terms($post_id, $genre['name'], 'genre', true);
            }
            update_post_meta($post_id, '_genre_ids', $genre_ids);
        }
        
        // Save cast
        if(isset($data['credits']['cast'])) {
            $cast_names = array();
            $limit = 10;
            foreach($data['credits']['cast'] as $i => $cast) {
                if($i >= $limit) break;
                $cast_names[] = $cast['name'];
                wp_set_object_terms($post_id, $cast['name'], 'cast', true);
            }
            update_post_meta($post_id, '_cast', $cast_names);
        }
        
        // Save trailer
        if(isset($data['videos'])) {
            $trailer_key = $this->get_trailer_key($data['videos']);
            if($trailer_key) {
                update_post_meta($post_id, '_trailer_key', $trailer_key);
            }
        }
        
        wp_send_json_success(array('message' => 'Data imported successfully'));
    }
    
    /**
     * Set featured image from URL
     */
    private function set_featured_image($image_url, $post_id) {
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        
        $attachment_id = media_sideload_image($image_url, $post_id, null, 'id');
        
        if(!is_wp_error($attachment_id)) {
            set_post_thumbnail($post_id, $attachment_id);
        }
    }
    
    /**
     * AJAX: Import multiple movies
     */
    public function ajax_import_tmdb_movie() {
        check_ajax_referer('tmdb_import_nonce', 'nonce');
        
        $tmdb_id = intval($_POST['tmdb_id']);
        
        // Check if already exists
        $existing = get_posts(array(
            'post_type' => 'movie',
            'meta_key' => '_tmdb_id',
            'meta_value' => $tmdb_id,
            'posts_per_page' => 1
        ));
        
        if(!empty($existing)) {
            wp_send_json_error(array('message' => 'Movie already exists'));
        }
        
        $data = $this->get_movie($tmdb_id);
        
        if(!$data) {
            wp_send_json_error(array('message' => 'Failed to fetch movie data'));
        }
        
        // Create new movie post
        $post_id = wp_insert_post(array(
            'post_title' => $data['title'],
            'post_content' => $data['overview'],
            'post_excerpt' => wp_trim_words($data['overview'], 30),
            'post_status' => 'publish',
            'post_type' => 'movie',
            'meta_input' => array(
                '_tmdb_id' => $tmdb_id,
                '_tmdb_data' => $data,
                '_vote_average' => $data['vote_average'],
                '_vote_count' => $data['vote_count'],
                '_release_date' => $data['release_date']
            )
        ));
        
        if($post_id && $data['poster_path']) {
            $image_url = $this->get_image_url($data['poster_path'], 'original');
            $this->set_featured_image($image_url, $post_id);
        }
        
        wp_send_json_success(array('post_id' => $post_id, 'message' => 'Movie imported successfully'));
    }
}

// Initialize TMDB API
global $tmdb_api;
$tmdb_api = new TMDB_API();
?>
