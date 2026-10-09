<?php
/**
 * Custom Theme functions and definitions.
 */

// Include Cloud configuration
if (file_exists(__DIR__ . '/cloud_config.php')) {
    include('cloud_config.php');
}

// Enable CORS for frontend requests (including localhost:3000 Next.js)
add_action('init', function () {
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
    header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization, Cache-Control, Pragma, Expires");
    if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        header("Access-Control-Max-Age: 86400");
        status_header(200);
        exit();
    }
});

add_action('rest_api_init', function () {
    remove_filter('rest_pre_serve_request', 'rest_send_cors_headers');
    add_filter('rest_pre_serve_request', function ($value) {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE');
        header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization, Cache-Control, Pragma, Expires');
        return $value;
    });
}, 15);

// Hide admin menus
function custom_hide_admin_menu() {
    remove_menu_page('edit.php'); 
    remove_menu_page('upload.php');     
    remove_menu_page('edit.php?post_type=page');
    remove_menu_page('edit-comments.php');
    remove_menu_page('themes.php');
    remove_menu_page('plugins.php');
    remove_menu_page('users.php');
    remove_menu_page('tools.php');
    remove_menu_page('options-general.php');
}
add_action('admin_menu', 'custom_hide_admin_menu', 999);

// Load AWS SDK
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

use Aws\S3\S3Client;

// Add Parent Menus for Managers
function custom_register_grouped_menus() {
    add_menu_page('About', 'About', 'manage_options', 'group-about', '', 'dashicons-info', 21);
    add_menu_page('Our Work', 'Our Work', 'manage_options', 'group-our-work', '', 'dashicons-portfolio', 22);
    add_menu_page('Our Impact', 'Our Impact', 'manage_options', 'group-our-impact', '', 'dashicons-chart-pie', 23);
    add_menu_page('Insight', 'Insight', 'manage_options', 'group-news', '', 'dashicons-megaphone', 25);
    add_menu_page('Careers', 'Careers', 'manage_options', 'group-careers', '', 'dashicons-businessperson', 26);
    add_menu_page('Resources', 'Resources', 'manage_options', 'group-resources', '', 'dashicons-groups', 27);
    add_menu_page('Projects & Tools', 'Projects & Tools', 'manage_options', 'group-research', '', 'dashicons-admin-tools', 28);
}
add_action('admin_menu', 'custom_register_grouped_menus', 9);

// Make Parent Menus Unclickable
function custom_make_menus_unclickable() {
    ?>
    <style>
        #toplevel_page_group-about > a,
        #toplevel_page_group-our-work > a,
        #toplevel_page_group-our-impact > a,
        #toplevel_page_group-news > a,
        #toplevel_page_group-careers > a,
        #toplevel_page_group-resources > a,
        #toplevel_page_group-research > a {
            pointer-events: none !important;
            cursor: default !important;
        }
    </style>
    <script>
        jQuery(document).ready(function($) {
            $('#toplevel_page_group-about > a, #toplevel_page_group-our-work > a, #toplevel_page_group-our-impact > a, #toplevel_page_group-news > a, #toplevel_page_group-careers > a, #toplevel_page_group-resources > a, #toplevel_page_group-research > a').on('click', function(e) {
                e.preventDefault();
            });
        });
    </script>
    <?php
}
add_action('admin_footer', 'custom_make_menus_unclickable');

// Remove duplicate parent links and enforce strict submenu ordering
function custom_organize_submenus() {
    global $submenu;

    remove_submenu_page('group-about', 'group-about');
    remove_submenu_page('group-our-work', 'group-our-work');
    remove_submenu_page('group-our-impact', 'group-our-impact');
    remove_submenu_page('group-news', 'group-news');
    remove_submenu_page('group-careers', 'group-careers');
    remove_submenu_page('group-resources', 'group-resources');
    remove_submenu_page('group-research', 'group-research');

    // Desired order for About
    $about_order = [
        'about-who-we-are',
        'about-mission',
        'home-presence',
        'fcra-registration',
        'governance-team-manager',
        'about-our-journey',
        'academic-collaborators'
    ];

    // Desired order for Our Work
    $work_order = [
        'home-research-areas',
        'research-projects',
        'somaarth-sites',
        'capacity-building-manager',
        'engagement-advocacy-manager',
        'community-activities-manager'
    ];

    // Desired order for Our Impact
    $impact_order = [
        'impact-summary',
        'partners-manager',
        'key-research-findings',
        'device-products',
        'policy-influence',
        'transforming-lives'
    ];

    $sort_group = function($parent_slug, $order_slugs) use (&$submenu) {
        if (!isset($submenu[$parent_slug])) return;
        $items = $submenu[$parent_slug];
        $sorted = [];
        $remaining = [];
        
        foreach ($items as $item) {
            $slug = $item[2];
            $idx = array_search($slug, $order_slugs);
            if ($idx !== false) {
                $sorted[$idx] = $item;
            } else {
                $remaining[] = $item;
            }
        }
        ksort($sorted);
        $submenu[$parent_slug] = array_merge(array_values($sorted), $remaining);
    };

    $sort_group('group-about', $about_order);
    $sort_group('group-our-work', $work_order);
    $sort_group('group-our-impact', $impact_order);
}
add_action('admin_menu', 'custom_organize_submenus', 999);

// Include Setup Tables automatically
if (file_exists(__DIR__ . '/setup-tables.php')) {
    include_once(__DIR__ . '/setup-tables.php');
}

// Include Manager Files
$managers = [
    'home-manager.php',
    'about-manager.php',
    'impact-manager.php',
    'somaarth-sites-manager.php',
    'impact-subpages-manager.php',
    'capacity-building-manager.php',
    'engagement-advocacy-manager.php',
    'community-activities-manager.php',
    'news-manager.php',
    'announcement.php',
    'events.php',
    'headings.php',
    'current-openings.php',
    'internship-manager.php',
    'publications-manager.php',
    'governance-team-manager.php',
    'academic-collaborators.php',
    'document-library.php',
    'research-projects.php',
    'inclen-tools.php',
    'training-materials.php',
    'annual-reports.php',
    'blog-manager.php',
    'completed-projects.php',
    'contact-manager.php',
    'data-repository.php',
    'device-products.php',
    'download-leads.php',
    'download-requests.php',
    'fcra-registration.php',
    'navigation-manager.php',
    'newsletters-manager.php',
    'partners-manager.php',
    'priority-settings.php'
];

foreach ($managers as $manager) {
    if (file_exists(__DIR__ . '/' . $manager)) {
        include_once(__DIR__ . '/' . $manager);
    }
}

/**
 * Theme Functions - Custom Login with CAPTCHA
 */
function custom_serve_login_page() {
    $path = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
    if (preg_match('#(^|/)login$#', $path)) {
        if (is_user_logged_in()) {
            wp_redirect(admin_url());
            exit;
        }
        $template = get_template_directory() . '/page-login.php';
        if (file_exists($template)) {
            include $template;
            exit;
        }
    }
}
add_action('init', 'custom_serve_login_page', 2);

function custom_redirect_login_page() {
    $login_page = home_url('/login/');
    $page_viewed = basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

    if ($page_viewed === 'wp-login.php' && $_SERVER['REQUEST_METHOD'] === 'GET') {
        if (isset($_REQUEST['action']) && in_array($_REQUEST['action'], ['logout', 'lostpassword', 'rp', 'resetpass'])) {
            return;
        }
        wp_redirect($login_page);
        exit;
    }
}
add_action('init', 'custom_redirect_login_page', 1);

function custom_logout_redirect() {
    wp_redirect(home_url('/login/'));
    exit;
}
add_action('wp_logout', 'custom_logout_redirect');

function custom_verify_user_pass($user, $username, $password) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return $user;
    }
    if (empty($username) || empty($password)) {
        wp_redirect(home_url('/login/?login=failed'));
        exit;
    }
    return $user;
}
add_filter('authenticate', 'custom_verify_user_pass', 20, 3);

function custom_login_failed() {
    wp_redirect(home_url('/login/?login=failed'));
    exit;
}
add_action('wp_login_failed', 'custom_login_failed', 10);
?>
