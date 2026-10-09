<?php
/**
 * Footer Settings & Explore Links Manager
 */

// Register Admin Submenu under Footer Group
add_action('admin_menu', function () {
    add_submenu_page(
        'group-footer',
        'Footer Settings',
        'Footer Settings',
        'manage_options',
        'footer-settings-manager',
        'footer_settings_manager_page'
    );
});

// Setup Table on init/admin_init
function custom_setup_footer_settings_table() {
    global $wpdb;
    $table = $wpdb->prefix . 'footer_settings';
    $charset_collate = $wpdb->get_charset_collate();
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    $sql = "CREATE TABLE $table (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        logo_url varchar(500) DEFAULT '/inclen_new.png',
        logo_alt varchar(255) DEFAULT 'INCLEN Trust International',
        about_text longtext DEFAULT '',
        twitter_url varchar(500) DEFAULT '',
        linkedin_url varchar(500) DEFAULT '',
        facebook_url varchar(500) DEFAULT '',
        instagram_url varchar(500) DEFAULT '',
        youtube_url varchar(500) DEFAULT '',
        explore_links longtext DEFAULT '[]',
        newsletter_title varchar(255) DEFAULT 'Newsletter',
        newsletter_subtitle varchar(255) DEFAULT 'Subscribe to receive the latest updates.',
        visitor_counter_url varchar(500) DEFAULT 'https://info.flagcounter.com/etB2',
        visitor_counter_img varchar(500) DEFAULT 'https://s01.flagcounter.com/count2/etB2/bg_FFFFFF/txt_000000/border_FFFFFF/columns_2/maxflags_8/viewers_0/labels_0/pageviews_0/flags_0/percent_0/',
        copyright_text varchar(500) DEFAULT '© 2026 INCLEN Trust International. All rights served.',
        privacy_policy_url varchar(500) DEFAULT '/privacy',
        terms_url varchar(500) DEFAULT '#',
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    dbDelta($sql);

    $count = $wpdb->get_var("SELECT COUNT(*) FROM $table");
    if (!$count || $count == 0) {
        $default_explore = [
            ['id' => '1', 'title' => 'Our Mission', 'url' => '/about#what-we-do', 'target' => '_self', 'is_active' => true],
            ['id' => '2', 'title' => 'Research Projects', 'url' => '/research', 'target' => '_self', 'is_active' => true],
            ['id' => '3', 'title' => 'Partner Institutes', 'url' => '/partners', 'target' => '_self', 'is_active' => true],
            ['id' => '4', 'title' => 'Publications', 'url' => '/publications', 'target' => '_self', 'is_active' => true],
            ['id' => '5', 'title' => 'Careers', 'url' => '/careers', 'target' => '_self', 'is_active' => true],
            ['id' => '6', 'title' => 'FCRA & Registration', 'url' => '/fcra', 'target' => '_self', 'is_active' => true],
        ];

        $wpdb->insert($table, [
            'logo_url'            => '/inclen_new.png',
            'logo_alt'            => 'INCLEN Trust International',
            'about_text'          => 'INCLEN Trust International is a global network dedicated to improving the health of populations by promoting equitable health care based on the best evidence of effectiveness.',
            'twitter_url'         => 'https://x.com/INCLEN_TRUST',
            'linkedin_url'        => 'https://www.linkedin.com/in/the-inclen-trust-international-663035106/',
            'facebook_url'        => 'https://www.facebook.com/profile.php?id=100021257529599#',
            'instagram_url'       => '',
            'youtube_url'         => '',
            'explore_links'       => wp_json_encode($default_explore),
            'newsletter_title'    => 'Newsletter',
            'newsletter_subtitle' => 'Subscribe to receive the latest updates.',
            'visitor_counter_url' => 'https://info.flagcounter.com/etB2',
            'visitor_counter_img' => 'https://s01.flagcounter.com/count2/etB2/bg_FFFFFF/txt_000000/border_FFFFFF/columns_2/maxflags_8/viewers_0/labels_0/pageviews_0/flags_0/percent_0/',
            'copyright_text'      => '© 2026 INCLEN Trust International. All rights served.',
            'privacy_policy_url'  => '/privacy',
            'terms_url'           => '#'
        ]);
    }
}
add_action('admin_init', 'custom_setup_footer_settings_table');

// REST API Endpoints
add_action('rest_api_init', function () {
    register_rest_route('footer-settings/v1', '/all', [
        'methods'             => 'GET',
        'callback'            => 'get_all_footer_settings_rest',
        'permission_callback' => '__return_true'
    ]);

    register_rest_route('footer-settings/v1', '/save', [
        'methods'             => ['POST', 'PUT'],
        'callback'            => 'save_footer_settings_rest',
        'permission_callback' => '__return_true'
    ]);

    register_rest_route('footer-settings/v1', '/update', [
        'methods'             => ['POST', 'PUT'],
        'callback'            => 'save_footer_settings_rest',
        'permission_callback' => '__return_true'
    ]);

    register_rest_route('footer-settings/v1', '/upload-logo', [
        'methods'             => 'POST',
        'callback'            => 'upload_footer_logo_rest',
        'permission_callback' => '__return_true'
    ]);
});

// REST Callback: Get Footer Settings
function get_all_footer_settings_rest() {
    global $wpdb;
    $table = $wpdb->prefix . 'footer_settings';
    
    // Ensure table exists
    if ($wpdb->get_var("SHOW TABLES LIKE '$table'") != $table) {
        custom_setup_footer_settings_table();
    }

    $row = $wpdb->get_row("SELECT * FROM $table ORDER BY id ASC LIMIT 1", ARRAY_A);

    if (!$row) {
        custom_setup_footer_settings_table();
        $row = $wpdb->get_row("SELECT * FROM $table ORDER BY id ASC LIMIT 1", ARRAY_A);
    }

    if ($row) {
        if (!empty($row['explore_links'])) {
            $decoded = json_decode($row['explore_links'], true);
            $row['explore_links'] = is_array($decoded) ? $decoded : [];
        } else {
            $row['explore_links'] = [];
        }
        return rest_ensure_response($row);
    }

    return rest_ensure_response([
        'logo_url'            => '/inclen_new.png',
        'logo_alt'            => 'INCLEN Trust International',
        'about_text'          => 'INCLEN Trust International is a global network dedicated to improving the health of populations by promoting equitable health care based on the best evidence of effectiveness.',
        'twitter_url'         => 'https://x.com/INCLEN_TRUST',
        'linkedin_url'        => 'https://www.linkedin.com/in/the-inclen-trust-international-663035106/',
        'facebook_url'        => 'https://www.facebook.com/profile.php?id=100021257529599#',
        'instagram_url'       => '',
        'youtube_url'         => '',
        'explore_links'       => [],
        'newsletter_title'    => 'Newsletter',
        'newsletter_subtitle' => 'Subscribe to receive the latest updates.',
        'visitor_counter_url' => 'https://info.flagcounter.com/etB2',
        'visitor_counter_img' => 'https://s01.flagcounter.com/count2/etB2/bg_FFFFFF/txt_000000/border_FFFFFF/columns_2/maxflags_8/viewers_0/labels_0/pageviews_0/flags_0/percent_0/',
        'copyright_text'      => '© 2026 INCLEN Trust International. All rights served.',
        'privacy_policy_url'  => '/privacy',
        'terms_url'           => '#'
    ]);
}

// REST Callback: Save Footer Settings
function save_footer_settings_rest($request) {
    global $wpdb;
    $table = $wpdb->prefix . 'footer_settings';

    if ($wpdb->get_var("SHOW TABLES LIKE '$table'") != $table) {
        custom_setup_footer_settings_table();
    }

    $params = $request->get_json_params();
    if (empty($params)) {
        $params = $request->get_params();
    }

    $existing = $wpdb->get_row("SELECT id FROM $table ORDER BY id ASC LIMIT 1", ARRAY_A);

    $explore_links = isset($params['explore_links']) ? $params['explore_links'] : [];
    if (is_array($explore_links)) {
        $explore_links_json = wp_json_encode($explore_links);
    } elseif (is_string($explore_links)) {
        $explore_links_json = $explore_links;
    } else {
        $explore_links_json = '[]';
    }

    $data = [
        'logo_url'            => isset($params['logo_url']) ? sanitize_text_field($params['logo_url']) : '/inclen_new.png',
        'logo_alt'            => isset($params['logo_alt']) ? sanitize_text_field($params['logo_alt']) : 'INCLEN Trust International',
        'about_text'          => isset($params['about_text']) ? wp_kses_post($params['about_text']) : '',
        'twitter_url'         => isset($params['twitter_url']) ? esc_url_raw($params['twitter_url']) : '',
        'linkedin_url'        => isset($params['linkedin_url']) ? esc_url_raw($params['linkedin_url']) : '',
        'facebook_url'        => isset($params['facebook_url']) ? esc_url_raw($params['facebook_url']) : '',
        'instagram_url'       => isset($params['instagram_url']) ? esc_url_raw($params['instagram_url']) : '',
        'youtube_url'         => isset($params['youtube_url']) ? esc_url_raw($params['youtube_url']) : '',
        'explore_links'       => $explore_links_json,
        'newsletter_title'    => isset($params['newsletter_title']) ? sanitize_text_field($params['newsletter_title']) : 'Newsletter',
        'newsletter_subtitle' => isset($params['newsletter_subtitle']) ? sanitize_text_field($params['newsletter_subtitle']) : '',
        'visitor_counter_url' => isset($params['visitor_counter_url']) ? esc_url_raw($params['visitor_counter_url']) : '',
        'visitor_counter_img' => isset($params['visitor_counter_img']) ? esc_url_raw($params['visitor_counter_img']) : '',
        'copyright_text'      => isset($params['copyright_text']) ? sanitize_text_field($params['copyright_text']) : '© 2026 INCLEN Trust International. All rights served.',
        'privacy_policy_url'  => isset($params['privacy_policy_url']) ? sanitize_text_field($params['privacy_policy_url']) : '/privacy',
        'terms_url'           => isset($params['terms_url']) ? sanitize_text_field($params['terms_url']) : '#'
    ];

    if ($existing) {
        $wpdb->update($table, $data, ['id' => $existing['id']]);
    } else {
        $wpdb->insert($table, $data);
    }

    return rest_ensure_response([
        'status'  => 'success',
        'message' => 'Footer settings updated successfully.'
    ]);
}

// REST Callback: Upload Footer Logo
function upload_footer_logo_rest() {
    if (empty($_FILES['file'])) {
        return new WP_Error('no_file', 'No file uploaded', ['status' => 400]);
    }

    if (file_exists(__DIR__ . '/cloud_config.php')) {
        require_once __DIR__ . '/cloud_config.php';
    }

    global $accountId, $accessKey, $secretKey, $bucket;

    $fileTmp = $_FILES['file']['tmp_name'];
    $fileName = time() . '-footer-logo-' . sanitize_file_name($_FILES['file']['name']);

    if (!empty($accountId) && !empty($accessKey) && !empty($secretKey) && !empty($bucket)) {
        try {
            if (!class_exists('\Aws\S3\S3Client')) {
                if (file_exists(__DIR__ . '/vendor/autoload.php')) {
                    require_once __DIR__ . '/vendor/autoload.php';
                }
            }

            if (class_exists('\Aws\S3\S3Client')) {
                $client = new \Aws\S3\S3Client([
                    'version' => 'latest',
                    'region' => 'auto',
                    'endpoint' => "https://$accountId.r2.cloudflarestorage.com",
                    'credentials' => [
                        'key'    => $accessKey,
                        'secret' => $secretKey,
                    ],
                ]);

                $client->putObject([
                    'Bucket'      => $bucket,
                    'Key'         => 'admin/footer/' . $fileName,
                    'SourceFile'  => $fileTmp,
                    'ContentType' => $_FILES['file']['type']
                ]);

                $publicUrlBase = "https://pub-0a4b820e73c14605a159d60ec5f71130.r2.dev/admin/footer";
                return rest_ensure_response(['url' => $publicUrlBase . '/' . $fileName]);
            }
        } catch (Exception $e) {
            // fallback to local upload
        }
    }

    // WordPress Local Upload Fallback
    require_once(ABSPATH . 'wp-admin/includes/file.php');
    $upload = wp_handle_upload($_FILES['file'], ['test_form' => false]);
    if (isset($upload['url'])) {
        return rest_ensure_response(['url' => $upload['url']]);
    }

    return new WP_Error('upload_error', 'Failed to upload image.', ['status' => 500]);
}

// Admin Page Rendering
function footer_settings_manager_page() {
    ?>
    <style>
        .footer-admin-wrap {
            max-width: 1200px;
            margin: 20px 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
        }
        .footer-admin-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #fff;
            padding: 16px 24px;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            margin-bottom: 24px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        .footer-admin-header h1 {
            font-size: 22px;
            font-weight: 700;
            color: #0f172a;
            margin: 0;
        }
        .footer-save-btn {
            background: #F7610C !important;
            border-color: #F7610C !important;
            color: #fff !important;
            font-weight: 600 !important;
            padding: 8px 24px !important;
            height: auto !important;
            font-size: 14px !important;
            border-radius: 6px !important;
            cursor: pointer;
            transition: background 0.2s;
        }
        .footer-save-btn:hover {
            background: #d75107 !important;
            border-color: #d75107 !important;
        }
        .footer-tabs {
            display: flex;
            gap: 8px;
            margin-bottom: 20px;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 2px;
        }
        .footer-tab-btn {
            background: transparent;
            border: none;
            padding: 10px 18px;
            font-size: 14px;
            font-weight: 600;
            color: #64748b;
            cursor: pointer;
            border-radius: 6px 6px 0 0;
            transition: all 0.2s;
            position: relative;
        }
        .footer-tab-btn.active {
            color: #F7610C;
            background: #fff;
            border-bottom: 2px solid #F7610C;
            margin-bottom: -4px;
        }
        .footer-tab-pane {
            display: none;
        }
        .footer-tab-pane.active {
            display: block;
        }
        .footer-card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 24px;
            margin-bottom: 24px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        }
        .footer-card h3 {
            font-size: 16px;
            font-weight: 700;
            color: #1e293b;
            margin-top: 0;
            margin-bottom: 16px;
            padding-bottom: 10px;
            border-bottom: 1px solid #f1f5f9;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .form-grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        .form-field {
            margin-bottom: 16px;
        }
        .form-field label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #334155;
            margin-bottom: 6px;
        }
        .form-field input[type="text"],
        .form-field input[type="url"],
        .form-field textarea {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            font-size: 14px;
            color: #0f172a;
            box-sizing: border-box;
            background: #fff;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .form-field input:focus,
        .form-field textarea:focus {
            border-color: #F7610C;
            box-shadow: 0 0 0 2px rgba(247, 97, 12, 0.15);
            outline: none;
        }
        .form-field .help-text {
            font-size: 12px;
            color: #64748b;
            margin-top: 4px;
        }
        .logo-preview-box {
            background: #0f172a;
            padding: 16px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-top: 10px;
            min-height: 80px;
            min-width: 160px;
        }
        .logo-preview-box img {
            max-height: 60px;
            max-width: 180px;
            object-fit: contain;
            background: #fff;
            padding: 4px;
            border-radius: 4px;
        }
        /* Explore Links Table */
        .links-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        .links-table th {
            background: #f8fafc;
            text-align: left;
            padding: 10px 14px;
            font-size: 12px;
            font-weight: 700;
            color: #475569;
            text-transform: uppercase;
            border-bottom: 1px solid #e2e8f0;
        }
        .links-table td {
            padding: 12px 14px;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
            font-size: 13px;
        }
        .links-table tr:hover {
            background: #f8fafc;
        }
        .links-action-btn {
            background: #f1f5f9;
            border: 1px solid #cbd5e1;
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 12px;
            cursor: pointer;
            color: #334155;
            transition: all 0.2s;
        }
        .links-action-btn:hover {
            background: #e2e8f0;
            color: #0f172a;
        }
        .links-action-btn.delete {
            color: #ef4444;
            border-color: #fca5a5;
        }
        .links-action-btn.delete:hover {
            background: #fee2e2;
        }
        .toast-notify {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: #10b981;
            color: #fff;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 600;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            display: none;
            z-index: 99999;
        }
    </style>

    <div class="wrap footer-admin-wrap">
        <div class="footer-admin-header">
            <div>
                <h1>Footer Configuration Manager</h1>
                <p style="margin:4px 0 0; color:#64748b; font-size:13px;">Manage Footer Logo, Description, Explore Links, and Social Media links displayed on the website.</p>
            </div>
            <button type="button" id="save-all-btn" class="button footer-save-btn">
                Save All Changes
            </button>
        </div>

        <div class="footer-tabs">
            <button class="footer-tab-btn active" data-tab="tab-branding">Logo &amp; Content</button>
            <button class="footer-tab-btn" data-tab="tab-explore">Explore Links</button>
            <button class="footer-tab-btn" data-tab="tab-social">Social Media</button>
            <button class="footer-tab-btn" data-tab="tab-copyright">Bottom Bar &amp; Extra</button>
        </div>

        <!-- Tab 1: Branding & Description -->
        <div id="tab-branding" class="footer-tab-pane active">
            <div class="footer-card">
                <h3><span class="dashicons dashicons-format-image" style="color:#F7610C;"></span> Footer Logo &amp; Branding</h3>
                <div class="form-grid-2">
                    <div>
                        <div class="form-field">
                            <label for="footer_logo_url">Footer Logo URL</label>
                            <input type="text" id="footer_logo_url" placeholder="/inclen_new.png or https://...">
                            <div class="help-text">Direct URL or upload a new logo image below.</div>
                        </div>

                        <div class="form-field">
                            <label for="footer_logo_file">Upload New Logo Image</label>
                            <input type="file" id="footer_logo_file" accept="image/*">
                            <span id="logo_upload_status" style="font-size:12px; color:#F7610C; margin-left:8px; display:none;">Uploading...</span>
                        </div>

                        <div class="form-field">
                            <label for="footer_logo_alt">Logo Alt Text</label>
                            <input type="text" id="footer_logo_alt" placeholder="INCLEN Trust International">
                        </div>
                    </div>

                    <div>
                        <label style="font-size:13px; font-weight:600; color:#334155;">Logo Dark Background Preview</label>
                        <div class="logo-preview-box">
                            <img id="logo_preview_img" src="/inclen_new.png" alt="Preview">
                        </div>
                    </div>
                </div>
            </div>

            <div class="footer-card">
                <h3><span class="dashicons dashicons-text-page" style="color:#F7610C;"></span> Logo Description / About Text Under Logo</h3>
                <div class="form-field">
                    <label for="footer_about_text">Description Text (Under Logo in Footer)</label>
                    <textarea id="footer_about_text" rows="4" placeholder="Enter introductory or mission statement text shown below logo in the footer..."></textarea>
                    <div class="help-text">This paragraph is displayed directly under the logo in the footer on every page.</div>
                </div>
            </div>
        </div>

        <!-- Tab 2: Explore Links -->
        <div id="tab-explore" class="footer-tab-pane">
            <div class="footer-card">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
                    <h3 style="margin:0; border:none; padding:0;"><span class="dashicons dashicons-networking" style="color:#F7610C;"></span> Explore / Quick Links</h3>
                    <button type="button" id="add-explore-btn" class="button button-primary" style="background:#2563eb; border-color:#2563eb;">
                        + Add New Explore Link
                    </button>
                </div>
                <p style="color:#64748b; font-size:13px; margin-top:0;">These links appear under the "Explore" column in the website footer.</p>

                <table class="links-table">
                    <thead>
                        <tr>
                            <th style="width:40px;">#</th>
                            <th>Link Title</th>
                            <th>Target URL (href)</th>
                            <th style="width:110px;">Target Window</th>
                            <th style="width:90px; text-align:center;">Status</th>
                            <th style="width:140px; text-align:right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="explore-links-body">
                        <tr><td colspan="6" style="text-align:center; color:#94a3b8;">Loading explore links...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Tab 3: Social Media Links -->
        <div id="tab-social" class="footer-tab-pane">
            <div class="footer-card">
                <h3><span class="dashicons dashicons-share" style="color:#F7610C;"></span> Social Media Channels</h3>
                <p style="color:#64748b; font-size:13px; margin-top:0;">Enter URLs for each social media channel. Leave empty to hide.</p>

                <div class="form-grid-2">
                    <div class="form-field">
                        <label for="footer_twitter">Twitter / X URL</label>
                        <input type="url" id="footer_twitter" placeholder="https://x.com/INCLEN_TRUST">
                    </div>

                    <div class="form-field">
                        <label for="footer_linkedin">LinkedIn URL</label>
                        <input type="url" id="footer_linkedin" placeholder="https://www.linkedin.com/in/...">
                    </div>

                    <div class="form-field">
                        <label for="footer_facebook">Facebook URL</label>
                        <input type="url" id="footer_facebook" placeholder="https://www.facebook.com/...">
                    </div>

                    <div class="form-field">
                        <label for="footer_instagram">Instagram URL (Optional)</label>
                        <input type="url" id="footer_instagram" placeholder="https://www.instagram.com/...">
                    </div>

                    <div class="form-field">
                        <label for="footer_youtube">YouTube URL (Optional)</label>
                        <input type="url" id="footer_youtube" placeholder="https://www.youtube.com/...">
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab 4: Copyright & Extra -->
        <div id="tab-copyright" class="footer-tab-pane">
            <div class="footer-card">
                <h3><span class="dashicons dashicons-email" style="color:#F7610C;"></span> Newsletter Section Text</h3>
                <div class="form-grid-2">
                    <div class="form-field">
                        <label for="footer_newsletter_title">Newsletter Title</label>
                        <input type="text" id="footer_newsletter_title" placeholder="Newsletter">
                    </div>
                    <div class="form-field">
                        <label for="footer_newsletter_subtitle">Newsletter Subtitle</label>
                        <input type="text" id="footer_newsletter_subtitle" placeholder="Subscribe to receive the latest updates.">
                    </div>
                </div>
            </div>

            <div class="footer-card">
                <h3><span class="dashicons dashicons-admin-site-alt3" style="color:#F7610C;"></span> Visitors Counter &amp; Stats</h3>
                <div class="form-grid-2">
                    <div class="form-field">
                        <label for="footer_visitor_url">Visitor Statistics Link URL</label>
                        <input type="url" id="footer_visitor_url" placeholder="https://info.flagcounter.com/etB2">
                    </div>
                    <div class="form-field">
                        <label for="footer_visitor_img">Flag Counter Image URL</label>
                        <input type="url" id="footer_visitor_img" placeholder="https://s01.flagcounter.com/count2/etB2/...">
                    </div>
                </div>
            </div>

            <div class="footer-card">
                <h3><span class="dashicons dashicons-lock" style="color:#F7610C;"></span> Bottom Bar / Copyright</h3>
                <div class="form-field">
                    <label for="footer_copyright_text">Copyright Notice</label>
                    <input type="text" id="footer_copyright_text" placeholder="© 2026 INCLEN Trust International. All rights served.">
                </div>

                <div class="form-grid-2">
                    <div class="form-field">
                        <label for="footer_privacy_url">Privacy Policy URL</label>
                        <input type="text" id="footer_privacy_url" placeholder="/privacy">
                    </div>
                    <div class="form-field">
                        <label for="footer_terms_url">Terms of Service URL</label>
                        <input type="text" id="footer_terms_url" placeholder="#">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add/Edit Explore Modal -->
    <div id="explore-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:99999; align-items:center; justify-content:center;">
        <div style="background:#fff; width:450px; border-radius:8px; padding:24px; box-shadow:0 10px 25px rgba(0,0,0,0.2);">
            <h3 id="modal-title" style="margin-top:0; font-size:16px; font-weight:700;">Add Explore Link</h3>
            <input type="hidden" id="modal_link_index" value="-1">
            
            <div class="form-field">
                <label for="modal_link_title">Link Label / Title <span style="color:red;">*</span></label>
                <input type="text" id="modal_link_title" placeholder="e.g. Research Projects" required>
            </div>

            <div class="form-field">
                <label for="modal_link_url">Link URL (href) <span style="color:red;">*</span></label>
                <input type="text" id="modal_link_url" placeholder="e.g. /research or https://..." required>
            </div>

            <div class="form-field">
                <label for="modal_link_target">Open Target</label>
                <select id="modal_link_target" style="width:100%; padding:8px 12px; border-radius:6px; border:1px solid #cbd5e1;">
                    <option value="_self">Same Tab (_self)</option>
                    <option value="_blank">New Tab (_blank)</option>
                </select>
            </div>

            <div class="form-field">
                <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                    <input type="checkbox" id="modal_link_active" checked>
                    <span>Active (Visible on website)</span>
                </label>
            </div>

            <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:20px;">
                <button type="button" id="modal-cancel-btn" class="button">Cancel</button>
                <button type="button" id="modal-save-btn" class="button button-primary" style="background:#F7610C; border-color:#F7610C;">Save Link</button>
            </div>
        </div>
    </div>

    <!-- Notification Toast -->
    <div id="footer-toast" class="toast-notify">
        Settings saved successfully!
    </div>

    <script>
    jQuery(document).ready(function($) {
        let exploreLinks = [];
        const restBase = '<?php echo esc_url_raw(rest_url('footer-settings/v1')); ?>';

        // Tab Switching
        $('.footer-tab-btn').on('click', function() {
            $('.footer-tab-btn').removeClass('active');
            $('.footer-tab-pane').removeClass('active');
            $(this).addClass('active');
            $('#' + $(this).data('tab')).addClass('active');
        });

        // Logo Input Preview
        $('#footer_logo_url').on('input', function() {
            const url = $(this).val();
            if (url) {
                $('#logo_preview_img').attr('src', url);
            }
        });

        // Logo Upload to R2 / REST
        $('#footer_logo_file').on('change', function() {
            const file = this.files[0];
            if (!file) return;

            const formData = new FormData();
            formData.append('file', file);

            $('#logo_upload_status').show().text('Uploading logo...');

            $.ajax({
                url: restBase + '/upload-logo',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                headers: { 'X-WP-Nonce': '<?php echo wp_create_nonce('wp_rest'); ?>' },
                success: function(res) {
                    if (res && res.url) {
                        $('#footer_logo_url').val(res.url);
                        $('#logo_preview_img').attr('src', res.url);
                        $('#logo_upload_status').text('Uploaded successfully!').css('color', '#10b981');
                        setTimeout(() => { $('#logo_upload_status').fadeOut(); }, 3000);
                    }
                },
                error: function(err) {
                    $('#logo_upload_status').text('Upload failed. Please use direct URL.').css('color', '#ef4444');
                }
            });
        });

        // Load Settings on start
        function loadSettings() {
            $.get(restBase + '/all', function(data) {
                if (!data) return;

                $('#footer_logo_url').val(data.logo_url || '');
                $('#logo_preview_img').attr('src', data.logo_url || '/inclen_new.png');
                $('#footer_logo_alt').val(data.logo_alt || '');
                $('#footer_about_text').val(data.about_text || '');
                
                $('#footer_twitter').val(data.twitter_url || '');
                $('#footer_linkedin').val(data.linkedin_url || '');
                $('#footer_facebook').val(data.facebook_url || '');
                $('#footer_instagram').val(data.instagram_url || '');
                $('#footer_youtube').val(data.youtube_url || '');

                $('#footer_newsletter_title').val(data.newsletter_title || '');
                $('#footer_newsletter_subtitle').val(data.newsletter_subtitle || '');
                $('#footer_visitor_url').val(data.visitor_counter_url || '');
                $('#footer_visitor_img').val(data.visitor_counter_img || '');

                $('#footer_copyright_text').val(data.copyright_text || '');
                $('#footer_privacy_url').val(data.privacy_policy_url || '');
                $('#footer_terms_url').val(data.terms_url || '');

                exploreLinks = Array.isArray(data.explore_links) ? data.explore_links : [];
                renderExploreTable();
            });
        }

        function renderExploreTable() {
            if (!exploreLinks || exploreLinks.length === 0) {
                $('#explore-links-body').html('<tr><td colspan="6" style="text-align:center; color:#94a3b8;">No explore links found. Click "+ Add New Explore Link" to create one.</td></tr>');
                return;
            }

            let html = '';
            exploreLinks.forEach(function(item, idx) {
                const isActive = item.is_active !== false;
                const statusBadge = isActive 
                    ? '<span style="background:#dcfce7; color:#166534; padding:2px 8px; border-radius:12px; font-size:11px; font-weight:700;">Active</span>'
                    : '<span style="background:#f1f5f9; color:#64748b; padding:2px 8px; border-radius:12px; font-size:11px; font-weight:700;">Hidden</span>';

                html += `
                    <tr data-index="${idx}">
                        <td style="color:#94a3b8; font-weight:600;">${idx + 1}</td>
                        <td style="font-weight:600; color:#1e293b;">${item.title || ''}</td>
                        <td style="color:#0284c7; font-family:monospace; font-size:12px;">${item.url || ''}</td>
                        <td><span style="font-size:11px; background:#f8fafc; padding:2px 6px; border:1px solid #e2e8f0; border-radius:4px;">${item.target || '_self'}</span></td>
                        <td style="text-align:center;">${statusBadge}</td>
                        <td style="text-align:right;">
                            <button type="button" class="links-action-btn move-up-btn" data-index="${idx}" ${idx === 0 ? 'disabled' : ''} title="Move Up">↑</button>
                            <button type="button" class="links-action-btn move-down-btn" data-index="${idx}" ${idx === exploreLinks.length - 1 ? 'disabled' : ''} title="Move Down">↓</button>
                            <button type="button" class="links-action-btn edit-link-btn" data-index="${idx}">Edit</button>
                            <button type="button" class="links-action-btn delete delete-link-btn" data-index="${idx}">Delete</button>
                        </td>
                    </tr>
                `;
            });

            $('#explore-links-body').html(html);
        }

        // Add Modal Open
        $('#add-explore-btn').on('click', function() {
            $('#modal-title').text('Add Explore Link');
            $('#modal_link_index').val('-1');
            $('#modal_link_title').val('');
            $('#modal_link_url').val('');
            $('#modal_link_target').val('_self');
            $('#modal_link_active').prop('checked', true);
            $('#explore-modal').css('display', 'flex');
        });

        // Edit Modal Open
        $(document).on('click', '.edit-link-btn', function() {
            const idx = parseInt($(this).data('index'), 10);
            const item = exploreLinks[idx];
            if (!item) return;

            $('#modal-title').text('Edit Explore Link');
            $('#modal_link_index').val(idx);
            $('#modal_link_title').val(item.title || '');
            $('#modal_link_url').val(item.url || '');
            $('#modal_link_target').val(item.target || '_self');
            $('#modal_link_active').prop('checked', item.is_active !== false);
            $('#explore-modal').css('display', 'flex');
        });

        // Modal Close
        $('#modal-cancel-btn').on('click', function() {
            $('#explore-modal').hide();
        });

        // Save Link Modal
        $('#modal-save-btn').on('click', function() {
            const title = $('#modal_link_title').val().trim();
            const url = $('#modal_link_url').val().trim();
            const target = $('#modal_link_target').val();
            const isActive = $('#modal_link_active').is(':checked');
            const idx = parseInt($('#modal_link_index').val(), 10);

            if (!title || !url) {
                alert('Please provide both Title and URL.');
                return;
            }

            if (idx >= 0 && idx < exploreLinks.length) {
                exploreLinks[idx] = {
                    ...exploreLinks[idx],
                    title: title,
                    url: url,
                    target: target,
                    is_active: isActive
                };
            } else {
                exploreLinks.push({
                    id: String(Date.now()),
                    title: title,
                    url: url,
                    target: target,
                    is_active: isActive
                });
            }

            $('#explore-modal').hide();
            renderExploreTable();
        });

        // Delete Link
        $(document).on('click', '.delete-link-btn', function() {
            if (!confirm('Are you sure you want to remove this explore link?')) return;
            const idx = parseInt($(this).data('index'), 10);
            exploreLinks.splice(idx, 1);
            renderExploreTable();
        });

        // Move Up / Down
        $(document).on('click', '.move-up-btn', function() {
            const idx = parseInt($(this).data('index'), 10);
            if (idx > 0) {
                const temp = exploreLinks[idx];
                exploreLinks[idx] = exploreLinks[idx - 1];
                exploreLinks[idx - 1] = temp;
                renderExploreTable();
            }
        });

        $(document).on('click', '.move-down-btn', function() {
            const idx = parseInt($(this).data('index'), 10);
            if (idx < exploreLinks.length - 1) {
                const temp = exploreLinks[idx];
                exploreLinks[idx] = exploreLinks[idx + 1];
                exploreLinks[idx + 1] = temp;
                renderExploreTable();
            }
        });

        // Save All Changes
        $('#save-all-btn').on('click', function() {
            const payload = {
                logo_url: $('#footer_logo_url').val().trim(),
                logo_alt: $('#footer_logo_alt').val().trim(),
                about_text: $('#footer_about_text').val().trim(),
                twitter_url: $('#footer_twitter').val().trim(),
                linkedin_url: $('#footer_linkedin').val().trim(),
                facebook_url: $('#footer_facebook').val().trim(),
                instagram_url: $('#footer_instagram').val().trim(),
                youtube_url: $('#footer_youtube').val().trim(),
                explore_links: exploreLinks,
                newsletter_title: $('#footer_newsletter_title').val().trim(),
                newsletter_subtitle: $('#footer_newsletter_subtitle').val().trim(),
                visitor_counter_url: $('#footer_visitor_url').val().trim(),
                visitor_counter_img: $('#footer_visitor_img').val().trim(),
                copyright_text: $('#footer_copyright_text').val().trim(),
                privacy_policy_url: $('#footer_privacy_url').val().trim(),
                terms_url: $('#footer_terms_url').val().trim()
            };

            const $btn = $('#save-all-btn');
            $btn.prop('disabled', true).text('Saving...');

            $.ajax({
                url: restBase + '/save',
                type: 'POST',
                data: JSON.stringify(payload),
                contentType: 'application/json',
                headers: { 'X-WP-Nonce': '<?php echo wp_create_nonce('wp_rest'); ?>' },
                success: function(res) {
                    $btn.prop('disabled', false).text('Save All Changes');
                    $('#footer-toast').fadeIn().delay(2500).fadeOut();
                },
                error: function(err) {
                    $btn.prop('disabled', false).text('Save All Changes');
                    alert('Error saving settings. Please try again.');
                }
            });
        });

        loadSettings();
    });
    </script>
    <?php
}
