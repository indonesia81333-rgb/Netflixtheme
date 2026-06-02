<?php
// inc/tmdb-shortcodes.php

class TMDB_Shortcodes {
    
    private $tmdb_api;
    
    public function __construct() {
        global $tmdb_api;
        $this->tmdb_api = $tmdb_api;
        
        add_shortcode('tmdb_popular_movies', array($this, 'popular_movies_shortcode'));
        add_shortcode('tmdb_trending', array($this, 'trending_shortcode'));
        add_shortcode('tmdb_movie_grid', array($this, 'movie_grid_shortcode'));
        add_shortcode('tmdb_movie_carousel', array($this, 'movie_carousel_shortcode'));
    }
    
    /**
     * Popular movies shortcode
     * Usage: [tmdb_popular_movies limit="12" columns="4"]
     */
    public function popular_movies_shortcode($atts) {
        $atts = shortcode_atts(array(
            'limit' => 12,
            'columns' => 4,
            'show_title' => 'yes'
        ), $atts);
        
        $movies = $this->tmdb_api->get_popular_movies();
        
        if(!$movies || !isset($movies['results'])) {
            return '<p>Unable to load movies</p>';
        }
        
        $movies['results'] = array_slice($movies['results'], 0, $atts['limit']);
        
        ob_start();
        ?>
        <div class="tmdb-movie-grid columns-<?php echo esc_attr($atts['columns']); ?>">
            <?php if($atts['show_title'] == 'yes'): ?>
                <h2 class="section-title">Popular Movies</h2>
            <?php endif; ?>
            
            <div class="movie-grid-container">
                <?php foreach($movies['results'] as $movie): ?>
                    <div class="movie-card">
                        <a href="/movie/?tmdb_id=<?php echo $movie['id']; ?>">
                            <img src="<?php echo $this->tmdb_api->get_image_url($movie['poster_path'], 'w342'); ?>" 
                                 alt="<?php echo esc_attr($movie['title']); ?>">
                            <div class="movie-info">
                                <h3><?php echo esc_html($movie['title']); ?></h3>
                                <div class="rating">
                                    <span class="stars">★</span>
                                    <span><?php echo $movie['vote_average']; ?>/10</span>
                                </div>
                                <div class="year">
                                    <?php echo date('Y', strtotime($movie['release_date'])); ?>
                                </div>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <style>
        .tmdb-movie-grid { margin-bottom: 40px; }
        .section-title { font-size: 24px; margin-bottom: 20px; color: #fff; }
        .movie-grid-container { display: grid; gap: 20px; }
        .columns-4 .movie-grid-container { grid-template-columns: repeat(4, 1fr); }
        .columns-3 .movie-grid-container { grid-template-columns: repeat(3, 1fr); }
        .movie-card { position: relative; transition: transform 0.3s; }
        .movie-card:hover { transform: scale(1.05); z-index: 1; }
        .movie-card img { width: 100%; border-radius: 8px; }
        .movie-info { position: absolute; bottom: 0; left: 0; right: 0; background: linear-gradient(to top, rgba(0,0,0,0.9), transparent); padding: 60px 10px 10px; opacity: 0; transition: opacity 0.3s; border-radius: 8px; }
        .movie-card:hover .movie-info { opacity: 1; }
        .movie-info h3 { font-size: 14px; margin: 0 0 5px; }
        .rating { color: #ffd700; font-size: 12px; }
        .year { font-size: 11px; color: #ccc; }
        @media (max-width: 768px) { .columns-4 .movie-grid-container { grid-template-columns: repeat(2, 1fr); } }
        </style>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Trending shortcode
     * Usage: [tmdb_trending media_type="movie" time_window="week" limit="10"]
     */
    public function trending_shortcode($atts) {
        $atts = shortcode_atts(array(
            'media_type' => 'all',
            'time_window' => 'week',
            'limit' => 10,
            'layout' => 'horizontal'
        ), $atts);
        
        $trending = $this->tmdb_api->get_trending($atts['media_type'], $atts['time_window']);
        
        if(!$trending || !isset($trending['results'])) {
            return '<p>Unable to load trending content</p>';
        }
        
        $items = array_slice($trending['results'], 0, $atts['limit']);
        
        ob_start();
        ?>
        <div class="tmdb-trending layout-<?php echo esc_attr($atts['layout']); ?>">
            <h2 class="section-title">Trending Now</h2>
            
            <div class="trending-container">
                <?php foreach($items as $item): 
                    $title = $item['title'] ?? $item['name'];
                    $date = $item['release_date'] ?? $item['first_air_date'];
                ?>
                    <div class="trending-item">
                        <div class="trending-rank">#<?php echo $loop_index + 1; ?></div>
                        <img src="<?php echo $this->tmdb_api->get_image_url($item['poster_path'], 'w154'); ?>" alt="<?php echo esc_attr($title); ?>">
                        <div class="trending-details">
                            <h4><?php echo esc_html($title); ?></h4>
                            <p><?php echo esc_html(substr($item['overview'], 0, 100)); ?>...</p>
                            <div class="trending-meta">
                                <span>★ <?php echo $item['vote_average']; ?></span>
                                <span><?php echo date('Y', strtotime($date)); ?></span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <style>
        .tmdb-trending { margin-bottom: 40px; }
        .trending-container { display: flex; flex-direction: column; gap: 15px; }
        .trending-item { display: flex; gap: 15px; background: #2a2a2a; border-radius: 8px; padding: 15px; transition: transform 0.3s; }
        .trending-item:hover { transform: translateX(10px); background: #3a3a3a; }
        .trending-rank { font-size: 48px; font-weight: bold; color: #e50914; min-width: 70px; text-align: center; }
        .trending-item img { width: 100px; border-radius: 6px; }
        .trending-details { flex: 1; }
        .trending-details h4 { margin: 0 0 10px; font-size: 18px; }
        .trending-meta { margin-top: 10px; display: flex; gap: 15px; color: #ffd700; font-size: 12px; }
        </style>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Movie carousel shortcode
     * Usage: [tmdb_movie_carousel type="popular" limit="20"]
     */
    public function movie_carousel_shortcode($atts) {
        $atts = shortcode_atts(array(
            'type' => 'popular',
            'limit' => 20
        ), $atts);
        
        switch($atts['type']) {
            case 'popular':
                $data = $this->tmdb_api->get_popular_movies();
                break;
            case 'now_playing':
                $data = $this->tmdb_api->get_now_playing();
                break;
            case 'top_rated':
                $data = $this->tmdb_api->get_top_rated();
                break;
            case 'upcoming':
                $data = $this->tmdb_api->get_upcoming();
                break;
            default:
                $data = $this->tmdb_api->get_popular_movies();
        }
        
        if(!$data || !isset($data['results'])) return '';
        
        $movies = array_slice($data['results'], 0, $atts['limit']);
        
        ob_start();
        ?>
        <div class="tmdb-carousel">
            <div class="carousel-container">
                <button class="carousel-prev">‹</button>
                <div class="carousel-track">
                    <?php foreach($movies as $movie): ?>
                        <div class="carousel-slide">
                            <img src="<?php echo $this->tmdb_api->get_image_url($movie['poster_path'], 'w342'); ?>" 
                                 alt="<?php echo esc_attr($movie['title']); ?>">
                            <div class="slide-info">
                                <h4><?php echo esc_html($movie['title']); ?></h4>
                                <div class="slide-rating">★ <?php echo $movie['vote_average']; ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <button class="carousel-next">›</button>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            const track = $('.carousel-track');
            const slides = $('.carousel-slide');
            const slideWidth = 220;
            let currentIndex = 0;
            
            $('.carousel-next').click(function() {
                if(currentIndex < slides.length - 5) {
                    currentIndex++;
                    track.css('transform', `translateX(-${currentIndex * slideWidth}px)`);
                }
            });
            
            $('.carousel-prev').click(function() {
                if(currentIndex > 0) {
                    currentIndex--;
                    track.css('transform', `translateX(-${currentIndex * slideWidth}px)`);
                }
            });
        });
        </script>
        
        <style>
        .tmdb-carousel { margin: 40px 0; position: relative; }
        .carousel-container { position: relative; overflow: hidden; }
        .carousel-track { display: flex; transition: transform 0.5s ease; gap: 15px; }
        .carousel-slide { flex: 0 0 200px; position: relative; cursor: pointer; transition: transform 0.3s; }
        .carousel-slide:hover { transform: scale(1.05); }
        .carousel-slide img { width: 100%; border-radius: 8px; }
        .carousel-prev, .carousel-next { position: absolute; top: 50%; transform: translateY(-50%); background: rgba(0,0,0,0.7); color: white; border: none; font-size: 40px; padding: 20px 10px; cursor: pointer; z-index: 10; }
        .carousel-prev { left: 0; }
        .carousel-next { right: 0; }
        .slide-info { position: absolute; bottom: 0; left: 0; right: 0; background: linear-gradient(to top, black, transparent); padding: 40px 10px 10px; opacity: 0; transition: opacity 0.3s; }
        .carousel-slide:hover .slide-info { opacity: 1; }
        </style>
        <?php
        return ob_get_clean();
    }
}

new TMDB_Shortcodes();
?>
