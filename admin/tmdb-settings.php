<?php
// admin/tmdb-settings.php

class TMDB_Settings_Page {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
    }
    
    public function add_admin_menu() {
        add_menu_page(
            'TMDB Settings',
            'TMDB API',
            'manage_options',
            'tmdb-settings',
            array($this, 'render_settings_page'),
            'dashicons-admin-generic',
            20
        );
        
        add_submenu_page(
            'tmdb-settings',
            'Import Movies',
            'Import Movies',
            'manage_options',
            'tmdb-import',
            array($this, 'render_import_page')
        );
    }
    
    public function register_settings() {
        register_setting('tmdb_settings_group', 'tmdb_api_key');
        register_setting('tmdb_settings_group', 'tmdb_language');
        register_setting('tmdb_settings_group', 'tmdb_auto_sync');
        register_setting('tmdb_settings_group', 'tmdb_cache_time');
    }
    
    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h1>TMDB API Settings</h1>
            
            <div class="notice notice-info">
                <p><strong>How to get TMDB API Key:</strong></p>
                <ol>
                    <li>Register at <a href="https://www.themoviedb.org/signup" target="_blank">TMDB website</a></li>
                    <li>Go to <a href="https://www.themoviedb.org/settings/api" target="_blank">API Settings</a></li>
                    <li>Request an API key (Developer)</li>
                    <li>Copy your API key and paste below</li>
                </ol>
            </div>
            
            <form method="post" action="options.php">
                <?php settings_fields('tmdb_settings_group'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">API Key</th>
                        <td>
                            <input type="text" name="tmdb_api_key" value="<?php echo esc_attr(get_option('tmdb_api_key')); ?>" class="regular-text" />
                            <p class="description">Your TMDB API Key (required)</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">Language</th>
                        <td>
                            <select name="tmdb_language">
                                <option value="en-US" <?php selected(get_option('tmdb_language'), 'en-US'); ?>>English (US)</option>
                                <option value="es-ES" <?php selected(get_option('tmdb_language'), 'es-ES'); ?>>Spanish</option>
                                <option value="fr-FR" <?php selected(get_option('tmdb_language'), 'fr-FR'); ?>>French</option>
                                <option value="de-DE" <?php selected(get_option('tmdb_language'), 'de-DE'); ?>>German</option>
                                <option value="it-IT" <?php selected(get_option('tmdb_language'), 'it-IT'); ?>>Italian</option>
                                <option value="pt-BR" <?php selected(get_option('tmdb_language'), 'pt-BR'); ?>>Portuguese (Brazil)</option>
                                <option value="hi-IN" <?php selected(get_option('tmdb_language'), 'hi-IN'); ?>>Hindi</option>
                            </select>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">Auto Sync</th>
                        <td>
                            <input type="checkbox" name="tmdb_auto_sync" value="1" <?php checked(get_option('tmdb_auto_sync'), 1); ?> />
                            <label>Automatically sync popular movies daily</label>
                         </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">Cache Time</th>
                        <td>
                            <select name="tmdb_cache_time">
                                <option value="3600" <?php selected(get_option('tmdb_cache_time'), 3600); ?>>1 Hour</option>
                                <option value="21600" <?php selected(get_option('tmdb_cache_time'), 21600); ?>>6 Hours</option>
                                <option value="43200" <?php selected(get_option('tmdb_cache_time'), 43200); ?>>12 Hours</option>
                                <option value="86400" <?php selected(get_option('tmdb_cache_time'), 86400); ?>>24 Hours</option>
                            </select>
                         </td>
                    </tr>
                </table>
                
                <?php submit_button('Save Settings'); ?>
            </form>
            
            <hr>
            
            <h2>Test API Connection</h2>
            <button id="test-tmdb-api" class="button">Test Connection</button>
            <div id="tmdb-test-result"></div>
            
            <script>
            jQuery('#test-tmdb-api').click(function() {
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'test_tmdb_connection',
                        nonce: '<?php echo wp_create_nonce("tmdb_test_nonce"); ?>'
                    },
                    success: function(response) {
                        if(response.success) {
                            jQuery('#tmdb-test-result').html('<div class="notice notice-success"><p>✓ Connection successful! API is working.</p></div>');
                        } else {
                            jQuery('#tmdb-test-result').html('<div class="notice notice-error"><p>✗ Connection failed: ' + response.data.message + '</p></div>');
                        }
                    }
                });
            });
            </script>
        </div>
        <?php
    }
    
    public function render_import_page() {
        ?>
        <div class="wrap">
            <h1>Import Movies from TMDB</h1>
            
            <div class="import-section">
                <h2>Quick Import</h2>
                <div class="import-options">
                    <button id="import-popular" class="button button-primary">Import Popular Movies</button>
                    <button id="import-trending" class="button">Import Trending</button>
                    <button id="import-now-playing" class="button">Import Now Playing</button>
                </div>
            </div>
            
            <div class="import-section">
                <h2>Search and Import</h2>
                <input type="text" id="movie-search" placeholder="Search for movies..." style="width: 300px;">
                <button id="search-movie-btn" class="button">Search</button>
                <div id="search-results" style="margin-top: 20px;"></div>
            </div>
            
            <div id="import-progress" style="display:none;">
                <h3>Importing...</h3>
                <progress value="0" max="100"></progress>
                <p id="import-status"></p>
            </div>
            
            <script>
            jQuery(document).ready(function($) {
                $('#import-popular').click(function() {
                    startImport('popular');
                });
                
                $('#import-trending').click(function() {
                    startImport('trending');
                });
                
                $('#search-movie-btn').click(function() {
                    var search = $('#movie-search').val();
                    if(search.length < 2) return;
                    
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'search_tmdb',
                            search: search,
                            type: 'movie',
                            nonce: '<?php echo wp_create_nonce("tmdb_search_nonce"); ?>'
                        },
                        success: function(response) {
                            if(response.success) {
                                var results = $('#search-results');
                                results.empty();
                                
                                $.each(response.data.results, function(i, movie) {
                                    results.append(`
                                        <div class="search-result" style="padding:10px; border-bottom:1px solid #ddd;">
                                            <strong>${movie.title}</strong> (${movie.release_date ? movie.release_date.substring(0,4) : 'N/A'})
                                            <button class="import-single" data-id="${movie.id}">Import</button>
                                        </div>
                                    `);
                                });
                            }
                        }
                    });
                });
                
                $(document).on('click', '.import-single', function() {
                    var tmdbId = $(this).data('id');
                    
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'import_tmdb_movie',
                            tmdb_id: tmdbId,
                            nonce: '<?php echo wp_create_nonce("tmdb_import_nonce"); ?>'
                        },
                        success: function(response) {
                            if(response.success) {
                                alert('Movie imported successfully!');
                                location.reload();
                            } else {
                                alert('Error: ' + response.data.message);
                            }
                        }
                    });
                });
                
                function startImport(type) {
                    $('#import-progress').show();
                    var page = 1;
                    
                    function importPage() {
                        $.ajax({
                            url: ajaxurl,
                            type: 'POST',
                            data: {
                                action: 'import_movies_batch',
                                type: type,
                                page: page,
                                nonce: '<?php echo wp_create_nonce("import_batch_nonce"); ?>'
                            },
                            success: function(response) {
                                if(response.success) {
                                    var progress = (response.data.page / response.data.total_pages) * 100;
                                    $('progress').val(progress);
                                    $('#import-status').text(`Imported ${response.data.imported} movies...`);
                                    
                                    if(response.data.has_more) {
                                        page++;
                                        importPage();
                                    } else {
                                        $('#import-status').text('Import completed!');
                                        setTimeout(function() {
                                            $('#import-progress').hide();
                                        }, 2000);
                                    }
                                }
                            }
                        });
                    }
                    
                    importPage();
                }
            });
            </script>
        </div>
        <style>
        .import-section { background: #fff; padding: 20px; margin-bottom: 20px; border: 1px solid #ddd; }
        .import-options { display: flex; gap: 10px; margin-top: 10px; }
        .search-result { display: flex; justify-content: space-between; align-items: center; }
        </style>
        <?php
    }
}

new TMDB_Settings_Page();

// AJAX handler for testing connection
add_action('wp_ajax_test_tmdb_connection', 'test_tmdb_connection_callback');
function test_tmdb_connection_callback() {
    check_ajax_referer('tmdb_test_nonce', 'nonce');
    
    global $tmdb_api;
    $result = $tmdb_api->get_popular_movies(1);
    
    if($result && isset($result['results'])) {
        wp_send_json_success(array('message' => 'API is working'));
    } else {
        wp_send_json_error(array('message' => 'Invalid API key or network error'));
    }
}
?>
