<?php
// inc/custom-post-types.php

function netflix_register_post_types() {
    
    // Movie Post Type
    register_post_type('movie',
        array(
            'labels' => array(
                'name' => __('Movies', 'netflix-clone'),
                'singular_name' => __('Movie', 'netflix-clone'),
                'menu_name' => __('Movies', 'netflix-clone'),
                'add_new' => __('Add New Movie', 'netflix-clone'),
                'add_new_item' => __('Add New Movie', 'netflix-clone'),
                'edit_item' => __('Edit Movie', 'netflix-clone'),
                'new_item' => __('New Movie', 'netflix-clone'),
                'view_item' => __('View Movie', 'netflix-clone'),
                'search_items' => __('Search Movies', 'netflix-clone'),
                'not_found' => __('No movies found', 'netflix-clone'),
                'not_found_in_trash' => __('No movies found in trash', 'netflix-clone'),
            ),
            'public' => true,
            'has_archive' => true,
            'rewrite' => array('slug' => 'movie'),
            'supports' => array('title', 'editor', 'thumbnail', 'excerpt', 'custom-fields'),
            'menu_icon' => 'dashicons-format-video',
            'show_in_rest' => true, // Gutenberg support
        )
    );
    
    // TV Show Post Type
    register_post_type('tv_show',
        array(
            'labels' => array(
                'name' => __('TV Shows', 'netflix-clone'),
                'singular_name' => __('TV Show', 'netflix-clone'),
                'menu_name' => __('TV Shows', 'netflix-clone'),
                'add_new' => __('Add New Show', 'netflix-clone'),
                'add_new_item' => __('Add New TV Show', 'netflix-clone'),
                'edit_item' => __('Edit TV Show', 'netflix-clone'),
                'new_item' => __('New Show', 'netflix-clone'),
                'view_item' => __('View Show', 'netflix-clone'),
                'search_items' => __('Search TV Shows', 'netflix-clone'),
                'not_found' => __('No TV shows found', 'netflix-clone'),
                'not_found_in_trash' => __('No TV shows found in trash', 'netflix-clone'),
            ),
            'public' => true,
            'has_archive' => true,
            'rewrite' => array('slug' => 'tv-show'),
            'supports' => array('title', 'editor', 'thumbnail', 'excerpt', 'custom-fields'),
            'menu_icon' => 'dashicons-tv',
            'show_in_rest' => true,
        )
    );
    
    // Genre Taxonomy
    register_taxonomy('genre', array('movie', 'tv_show'), array(
        'labels' => array(
            'name' => __('Genres', 'netflix-clone'),
            'singular_name' => __('Genre', 'netflix-clone'),
            'menu_name' => __('Genres', 'netflix-clone'),
            'search_items' => __('Search Genres', 'netflix-clone'),
            'all_items' => __('All Genres', 'netflix-clone'),
            'edit_item' => __('Edit Genre', 'netflix-clone'),
            'update_item' => __('Update Genre', 'netflix-clone'),
            'add_new_item' => __('Add New Genre', 'netflix-clone'),
            'new_item_name' => __('New Genre Name', 'netflix-clone'),
        ),
        'public' => true,
        'hierarchical' => true,
        'show_in_rest' => true,
        'rewrite' => array('slug' => 'genre'),
    ));
    
    // Cast Taxonomy
    register_taxonomy('cast', array('movie', 'tv_show'), array(
        'labels' => array(
            'name' => __('Cast', 'netflix-clone'),
            'singular_name' => __('Cast Member', 'netflix-clone'),
        ),
        'public' => true,
        'hierarchical' => false,
        'show_in_rest' => true,
    ));
}
add_action('init', 'netflix_register_post_types');

// Add custom meta boxes for TMDB integration
function netflix_add_tmdb_meta_boxes() {
    add_meta_box(
        'tmdb_movie_data',
        __('TMDB Movie Data', 'netflix-clone'),
        'netflix_render_tmdb_meta_box',
        'movie',
        'normal',
        'high'
    );
    
    add_meta_box(
        'tmdb_tv_data',
        __('TMDB TV Show Data', 'netflix-clone'),
        'netflix_render_tmdb_meta_box',
        'tv_show',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'netflix_add_tmdb_meta_boxes');

function netflix_render_tmdb_meta_box($post) {
    wp_nonce_field('tmdb_meta_box', 'tmdb_meta_box_nonce');
    
    $tmdb_id = get_post_meta($post->ID, '_tmdb_id', true);
    $tmdb_data = get_post_meta($post->ID, '_tmdb_data', true);
    ?>
    <div class="tmdb-meta-box">
        <div class="tmdb-search">
            <label for="tmdb_search"><?php _e('Search TMDB:', 'netflix-clone'); ?></label>
            <input type="text" id="tmdb_search" name="tmdb_search" placeholder="<?php _e('Enter movie/TV show name...', 'netflix-clone'); ?>" />
            <button type="button" id="search_tmdb_btn" class="button"><?php _e('Search', 'netflix-clone'); ?></button>
            <div id="tmdb_search_results"></div>
        </div>
        
        <div class="tmdb-info">
            <label for="tmdb_id"><?php _e('TMDB ID:', 'netflix-clone'); ?></label>
            <input type="text" id="tmdb_id" name="tmdb_id" value="<?php echo esc_attr($tmdb_id); ?>" />
            <button type="button" id="fetch_tmdb_data_btn" class="button"><?php _e('Fetch Data', 'netflix-clone'); ?></button>
        </div>
        
        <div id="tmdb_data_display">
            <?php if($tmdb_data): ?>
                <div class="tmdb-preview">
                    <img src="https://image.tmdb.org/t/p/w200<?php echo $tmdb_data['poster_path']; ?>" />
                    <h3><?php echo esc_html($tmdb_data['title'] ?? $tmdb_data['name']); ?></h3>
                    <p><?php echo esc_html(substr($tmdb_data['overview'], 0, 200)); ?>...</p>
                    <p><strong>Rating:</strong> <?php echo $tmdb_data['vote_average']; ?>/10</p>
                    <p><strong>Release:</strong> <?php echo $tmdb_data['release_date'] ?? $tmdb_data['first_air_date']; ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <style>
    .tmdb-meta-box { padding: 10px; }
    .tmdb-search, .tmdb-info { margin-bottom: 15px; }
    .tmdb-search input, .tmdb-info input { width: 300px; margin-right: 10px; }
    .tmdb-preview { background: #f0f0f0; padding: 15px; margin-top: 15px; }
    .tmdb-preview img { float: left; margin-right: 15px; }
    #tmdb_search_results { margin-top: 10px; max-height: 300px; overflow-y: auto; }
    .search-result-item { padding: 10px; border-bottom: 1px solid #ddd; cursor: pointer; }
    .search-result-item:hover { background: #f0f0f0; }
    </style>
    
    <script>
    jQuery(document).ready(function($) {
        $('#search_tmdb_btn').click(function() {
            var searchTerm = $('#tmdb_search').val();
            var postType = '<?php echo $post->post_type; ?>';
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'search_tmdb',
                    search: searchTerm,
                    type: postType,
                    nonce: '<?php echo wp_create_nonce("tmdb_search_nonce"); ?>'
                },
                success: function(response) {
                    if(response.success) {
                        var results = $('#tmdb_search_results');
                        results.empty();
                        
                        $.each(response.data.results, function(i, item) {
                            var title = item.title || item.name;
                            var date = item.release_date || item.first_air_date;
                            results.append(
                                '<div class="search-result-item" data-id="' + item.id + '">' +
                                '<strong>' + title + '</strong> (' + (date ? date.substring(0,4) : 'N/A') + ')<br>' +
                                '<small>' + (item.overview ? item.overview.substring(0,100) : 'No description') + '</small>' +
                                '</div>'
                            );
                        });
                        
                        $('.search-result-item').click(function() {
                            var tmdbId = $(this).data('id');
                            $('#tmdb_id').val(tmdbId);
                            $('#tmdb_search_results').empty();
                            $('#fetch_tmdb_data_btn').click();
                        });
                    }
                }
            });
        });
        
        $('#fetch_tmdb_data_btn').click(function() {
            var tmdbId = $('#tmdb_id').val();
            var postType = '<?php echo $post->post_type; ?>';
            var postId = <?php echo $post->ID; ?>;
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'fetch_tmdb_data',
                    tmdb_id: tmdbId,
                    type: postType,
                    post_id: postId,
                    nonce: '<?php echo wp_create_nonce("tmdb_fetch_nonce"); ?>'
                },
                success: function(response) {
                    if(response.success) {
                        location.reload();
                    } else {
                        alert('Error: ' + response.data.message);
                    }
                }
            });
        });
    });
    </script>
    <?php
}

function netflix_save_tmdb_data($post_id) {
    if(!isset($_POST['tmdb_meta_box_nonce'])) return;
    if(!wp_verify_nonce($_POST['tmdb_meta_box_nonce'], 'tmdb_meta_box')) return;
    if(defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    
    if(isset($_POST['tmdb_id'])) {
        update_post_meta($post_id, '_tmdb_id', sanitize_text_field($_POST['tmdb_id']));
    }
}
add_action('save_post', 'netflix_save_tmdb_data');
?>
