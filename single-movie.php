<?php
// single-movie.php

get_header();

global $post, $tmdb_api;

$tmdb_id = get_post_meta($post->ID, '_tmdb_id', true);
$tmdb_data = get_post_meta($post->ID, '_tmdb_data', true);

// If we have TMDB data, use it
if($tmdb_data && $tmdb_id) {
    $movie_data = $tmdb_data;
} else {
    // Otherwise fetch from API
    $movie_data = $tmdb_api->get_movie($tmdb_id);
}

if($movie_data):
    $backdrop = $tmdb_api->get_backdrop_url($movie_data['backdrop_path'], 'original');
    $poster = $tmdb_api->get_image_url($movie_data['poster_path'], 'w500');
    $trailer_key = $tmdb_api->get_trailer_key($movie_data['videos']);
    $year = date('Y', strtotime($movie_data['release_date']));
    $runtime_hours = floor($movie_data['runtime'] / 60);
    $runtime_minutes = $movie_data['runtime'] % 60;
?>
    <div class="movie-hero" style="background-image: linear-gradient(to right, rgba(0,0,0,0.8), rgba(0,0,0,0.4)), url('<?php echo esc_url($backdrop); ?>');">
        <div class="hero-content">
            <div class="movie-poster">
                <img src="<?php echo esc_url($poster); ?>" alt="<?php the_title(); ?>">
            </div>
            <div class="movie-info">
                <h1><?php the_title(); ?> <span class="year">(<?php echo $year; ?>)</span></h1>
                
                <div class="movie-meta">
                    <span class="rating">★ <?php echo $movie_data['vote_average']; ?>/10</span>
                    <span class="runtime"><?php echo $runtime_hours; ?>h <?php echo $runtime_minutes; ?>min</span>
                    <?php if($movie_data['release_date']): ?>
                        <span class="release-date"><?php echo date('F j, Y', strtotime($movie_data['release_date'])); ?></span>
                    <?php endif; ?>
                </div>
                
                <div class="genres">
                    <?php foreach($movie_data['genres'] as $genre): ?>
                        <span class="genre"><?php echo $genre['name']; ?></span>
                    <?php endforeach; ?>
                </div>
                
                <div class="actions">
                    <?php if($trailer_key): ?>
                        <button class="btn-play" data-trailer="<?php echo $trailer_key; ?>">▶ Play Trailer</button>
                    <?php endif; ?>
                    <button class="btn-watchlist">+ My List</button>
                </div>
                
                <div class="overview">
                    <h3>Overview</h3>
                    <p><?php echo nl2br(esc_html($movie_data['overview'])); ?></p>
                </div>
            </div>
        </div>
    </div>
    
    <div class="movie-details">
        <?php if(isset($movie_data['credits']['cast']) && !empty($movie_data['credits']['cast'])): ?>
        <div class="cast-section">
            <h2>Cast</h2>
            <div class="cast-grid">
                <?php 
                $limit = 12;
                foreach($movie_data['credits']['cast'] as $i => $cast):
                    if($i >= $limit) break;
                    $cast_photo = $tmdb_api->get_image_url($cast['profile_path'], 'w185');
                ?>
                    <div class="cast-card">
                        <?php if($cast_photo): ?>
                            <img src="<?php echo esc_url($cast_photo); ?>" alt="<?php echo esc_attr($cast['name']); ?>">
                        <?php else: ?>
                            <div class="no-photo">No Photo</div>
                        <?php endif; ?>
                        <div class="cast-info">
                            <h4><?php echo esc_html($cast['name']); ?></h4>
                            <p><?php echo esc_html($cast['character']); ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if(isset($movie_data['similar']['results']) && !empty($movie_data['similar']['results'])): ?>
        <div class="similar-section">
            <h2>You May Also Like</h2>
            <div class="similar-grid">
                <?php 
                $limit = 8;
                foreach($movie_data['similar']['results'] as $i => $similar):
                    if($i >= $limit) break;
                    $similar_poster = $tmdb_api->get_image_url($similar['poster_path'], 'w342');
                    if(!$similar_poster) continue;
                ?>
                    <div class="similar-card">
                        <a href="/movie/?tmdb_id=<?php echo $similar['id']; ?>">
                            <img src="<?php echo esc_url($similar_poster); ?>" alt="<?php echo esc_attr($similar['title']); ?>">
                            <div class="similar-info">
                                <h4><?php echo esc_html($similar['title']); ?></h4>
                                <span>★ <?php echo $similar['vote_average']; ?></span>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Trailer Modal -->
    <div id="trailer-modal" class="modal" style="display:none;">
        <div class="modal-content">
            <span class="close">&times;</span>
            <div id="trailer-container"></div>
        </div>
    </div>
    
    <style>
    .movie-hero { min-height: 70vh; background-size: cover; background-position: center; display: flex; align-items: center; padding: 100px 50px 50px; }
    .hero-content { display: flex; gap: 40px; max-width: 1200px; margin: 0 auto; width: 100%; }
    .movie-poster { flex: 0 0 300px; }
    .movie-poster img { width: 100%; border-radius: 12px; box-shadow: 0 0 30px rgba(0,0,0,0.5); }
    .movie-info { flex: 1; }
    .movie-info h1 { font-size: 48px; margin-bottom: 20px; }
    .movie-meta { display: flex; gap: 20px; margin-bottom: 20px; flex-wrap: wrap; }
    .rating { color: #ffd700; font-size: 18px; font-weight: bold; }
    .genres { display: flex; gap: 10px; margin-bottom: 30px; flex-wrap: wrap; }
    .genre { background: rgba(255,255,255,0.2); padding: 5px 15px; border-radius: 20px; font-size: 14px; }
    .actions { display: flex; gap: 15px; margin-bottom: 30px; }
    .btn-play { background: #e50914; color: white; border: none; padding: 12px 30px; font-size: 18px; border-radius: 4px; cursor: pointer; }
    .btn-watchlist { background: rgba(255,255,255,0.2); color: white; border: none; padding: 12px 30px; font-size: 18px; border-radius: 4px; cursor: pointer; }
    .overview { max-width: 800px; }
    .cast-section, .similar-section { padding: 50px; background: #141414; }
    .cast-grid, .similar-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 20px; margin-top: 20px; }
    .cast-card { background: #2a2a2a; border-radius: 8px; overflow: hidden; transition: transform 0.3s; }
    .cast-card:hover { transform: scale(1.05); }
    .cast-card img, .no-photo { width: 100%; height: 225px; object-fit: cover; background: #3a3a3a; display: flex; align-items: center; justify-content: center; }
    .cast-info { padding: 10px; text-align: center; }
    .cast-info h4 { font-size: 14px; margin-bottom: 5px; }
    .cast-info p { font-size: 12px; color: #ccc; }
    .similar-card { transition: transform 0.3s; }
    .similar-card:hover { transform: scale(1.05); }
    .similar-card img { width: 100%; border-radius: 8px; }
    .similar-info { padding: 10px; }
    .modal { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.9); z-index: 1000; }
    .modal-content { position: relative; max-width: 900px; margin: 50px auto; background: black; padding: 20px; border-radius: 8px; }
    .close { position: absolute; right: 20px; top: 10px; font-size: 40px; cursor: pointer; color: white; }
    iframe { width: 100%; height: 500px; }
    @media (max-width: 768px) { .hero-content { flex-direction: column; } .movie-poster { flex: 0 0 auto; max-width: 200px; margin: 0 auto; } .movie-info h1 { font-size: 28px; } }
    </style>
    
    <script>
    jQuery(document).ready(function($) {
        $('.btn-play').click(function() {
            var trailerKey = $(this).data('trailer');
            if(trailerKey) {
                $('#trailer-container').html('<iframe src="https://www.youtube.com/embed/' + trailerKey + '?autoplay=1" frameborder="0" allow="autoplay; encrypted-media" allowfullscreen></iframe>');
                $('#trailer-modal').show();
            }
        });
        
        $('.close, .modal').click(function(e) {
            if(e.target == this) {
                $('#trailer-modal').hide();
                $('#trailer-container').empty();
            }
        });
        
        $('.btn-watchlist').click(function() {
            $.ajax({
                url: netflix_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'add_to_watchlist',
                    post_id: <?php echo $post->ID; ?>,
                    nonce: netflix_ajax.nonce
                },
                success: function(response) {
                    if(response.success) {
                        alert('Added to your list!');
                    }
                }
            });
        });
    });
    </script>

<?php else: ?>
    <div class="error-message">Movie data not found. Please check TMDB API connection.</div>
<?php endif; ?>

<?php get_footer(); ?>
