<?php
/**
 * Impact Summary / Our Impact Page Manager
 */

// Register Admin Menu
add_action('admin_menu', function () {
    add_menu_page(
        'Impact Summary',
        'Impact Summary',
        'manage_options',
        'impact-summary',
        'impact_summary_manager_page',
        'dashicons-chart-pie',
        26
    );
});

// Register REST API Routes
add_action('rest_api_init', function () {
    register_rest_route('impact-summary/v1', '/all', [
        'methods'             => 'GET',
        'callback'            => 'get_impact_summary_rest',
        'permission_callback' => '__return_true'
    ]);

    register_rest_route('impact-summary/v1', '/update', [
        'methods'             => ['POST', 'PUT'],
        'callback'            => 'update_impact_summary_rest',
        'permission_callback' => '__return_true'
    ]);

    register_rest_route('impact-summary/v1', '/save', [
        'methods'             => ['POST', 'PUT'],
        'callback'            => 'update_impact_summary_rest',
        'permission_callback' => '__return_true'
    ]);
});

function get_default_impact_summary() {
    return [
        'id'                  => 1,
        'hero_title'          => 'Our Impact',
        'hero_description'    => "The INCLEN Trust measures its impact through large-scale research surveillance, policy translation, and the development of diagnostic tools that influence national health programs.\n\nKey Impact Areas: Global Research Network, SOMAARTH Surveillance Site, Diagnostic Innovation, Policy & Program Evaluation, National Research Priority Setting, Vaccine Research & COVID-19 Response.",
        'key_areas_title'     => 'Key Impact Areas',
        'key_areas'           => [
            [
                'id'          => '1',
                'title'       => 'Global Research Network',
                'description' => 'Operates across 34 countries with 89 academic institutions and over 1,800 members, creating a massive platform for interdisciplinary public health research.',
                'icon_color'  => 'brand',
                'bg_color'    => 'brand',
                'icon_name'   => 'globe'
            ],
            [
                'id'          => '2',
                'title'       => 'SOMAARTH Surveillance',
                'description' => 'Established one of the world\'s largest surveillance sites in Palwal, Haryana, covering 51 villages and a population exceeding 200,000 to monitor environmental and health transitions.',
                'icon_color'  => 'blue',
                'bg_color'    => 'green',
                'icon_name'   => 'beaker'
            ],
            [
                'id'          => '3',
                'title'       => 'Diagnostic Innovation',
                'description' => 'Developed the widely used INCLEN diagnostic tool for neurodevelopmental disorders, validated and modified by AIIMS for nationwide child health assessments.',
                'icon_color'  => 'blue',
                'bg_color'    => 'blue',
                'icon_name'   => 'flask'
            ],
            [
                'id'          => '4',
                'title'       => 'Policy & Program Evaluation',
                'description' => 'Conducted critical evaluations of India\'s Universal Immunization Program (UIP) and implemented research on managing neonatal sepsis and pneumonia to guide national health policy.',
                'icon_color'  => 'green',
                'bg_color'    => 'green',
                'icon_name'   => 'document'
            ],
            [
                'id'          => '5',
                'title'       => 'National Priority Setting',
                'description' => 'Led a massive \'crowd-sourced\' initiative involving over 2,000 experts and 250+ institutions to define public health research priorities for the Government of India.',
                'icon_color'  => 'purple',
                'bg_color'    => 'purple',
                'icon_name'   => 'clipboard'
            ],
            [
                'id'          => '6',
                'title'       => 'Vaccine & COVID-19',
                'description' => 'Actively managed clinical trials for COVID-19 vaccines (including heterologous prime-boost combinations) and sero-surveillance for Dengue and Chikungunya.',
                'icon_color'  => 'red',
                'bg_color'    => 'red',
                'icon_name'   => 'shield'
            ]
        ],
        'approach_tag'        => 'Our Approach',
        'approach_title'      => 'Bridging Evidence & Action',
        'approach_description'=> 'INCLEN Trust bridges the gap between scientific evidence and public health action through two primary pathways: translating research into national policies and programs, and converting findings into clinical practice.',
        'pathway1_badge'      => '01',
        'pathway1_title'      => 'Research to Policy & Program',
        'pathway1_description'=> 'INCLEN acts as a strategic technical partner to the Government of India, ensuring that data drives national health strategies.',
        'pathway1_items'      => [
            [
                'title' => 'National Research Priority Setting (RPS)',
                'desc'  => 'Collaborated with the Indian Council of Medical Research (ICMR) to lead a nationwide crowd-sourced exercise involving 2,000+ experts to define research priorities for maternal and child health through 2025.'
            ],
            [
                'title' => 'Immunization Policy',
                'desc'  => 'Evaluated the Universal Immunization Program (UIP) and provided the evidence base for the rollout of the Rotavirus Vaccine and the Intensified Mission Indradhanush (IMI).'
            ],
            [
                'title' => 'National Health Programs',
                'desc'  => 'Actively assists the government in adopting strategies for the National Program for Prevention & Control of Cancer, Diabetes, Cardiovascular Diseases and Stroke (NPCDCD).'
            ],
            [
                'title' => 'Childhood Pneumonia & Sepsis',
                'desc'  => 'Generated evidence to assist national governments in adopting context-sensitive strategies to reduce under-five mortality from pneumonia.'
            ]
        ],
        'pathway2_badge'      => '02',
        'pathway2_title'      => 'Research to Practice',
        'pathway2_description'=> 'The organization develops "actionable tools" that empower frontline health workers and primary care physicians to apply complex research in everyday settings.',
        'pathway2_items'      => [
            [
                'title' => 'INCLEN Diagnostic Tools (INDT)',
                'desc'  => 'Developed validated diagnostic instruments for neurodevelopmental disorders (e.g., ADHD, Neuromotor Impairments) that primary care physicians can use with minimal training.'
            ],
            [
                'title' => 'Community Interventions',
                'desc'  => 'Translates research into practice at the SOMAARTH site by engaging ASHA workers and Panchayati officers in dialogues about high-risk pregnancies and heart attack symptoms.'
            ],
            [
                'title' => 'Knowledge Translation Units',
                'desc'  => 'Established the International Institute of Global Health (IIGH) which houses a \'Policy Unit\' dedicated to turning network-generated evidence into clinical care tools and practice guidelines.'
            ],
            [
                'title' => 'Diagnostic Validation',
                'desc'  => 'Partnered with AIIMS to modify and validate INCLEN tools for a wider age range (1 month to 18 years), ensuring they are practical for diverse clinical settings.'
            ]
        ],
        'cta_title'           => 'Join Us in Making a Difference',
        'cta_description'     => 'Partner with INCLEN to drive global health innovation and policy change. Together, we can build a healthier future.',
        'cta_btn1_text'       => 'Partner With Us',
        'cta_btn1_link'       => '/contact',
        'cta_btn2_text'       => 'Explore Our Work',
        'cta_btn2_link'       => '/our-work'
    ];
}

function get_impact_summary_data() {
    global $wpdb;
    $table = $wpdb->prefix . 'impact_summary';

    if ($wpdb->get_var("SHOW TABLES LIKE '$table'") != $table) {
        if (function_exists('custom_setup_database_tables')) {
            custom_setup_database_tables();
        }
    }

    $row = $wpdb->get_row("SELECT * FROM $table ORDER BY id ASC LIMIT 1", ARRAY_A);
    $defaults = get_default_impact_summary();

    if (!$row) {
        return $defaults;
    }

    // Decode JSON fields safely
    $key_areas = [];
    if (!empty($row['key_areas'])) {
        $decoded = is_string($row['key_areas']) ? json_decode($row['key_areas'], true) : $row['key_areas'];
        if (is_array($decoded)) {
            $key_areas = $decoded;
        }
    }
    if (empty($key_areas)) {
        $key_areas = $defaults['key_areas'];
    }

    $pathway1_items = [];
    if (!empty($row['pathway1_items'])) {
        $decoded = is_string($row['pathway1_items']) ? json_decode($row['pathway1_items'], true) : $row['pathway1_items'];
        if (is_array($decoded)) {
            $pathway1_items = $decoded;
        }
    }
    if (empty($pathway1_items)) {
        $pathway1_items = $defaults['pathway1_items'];
    }

    $pathway2_items = [];
    if (!empty($row['pathway2_items'])) {
        $decoded = is_string($row['pathway2_items']) ? json_decode($row['pathway2_items'], true) : $row['pathway2_items'];
        if (is_array($decoded)) {
            $pathway2_items = $decoded;
        }
    }
    if (empty($pathway2_items)) {
        $pathway2_items = $defaults['pathway2_items'];
    }

    return [
        'id'                  => (int)($row['id'] ?? 1),
        'hero_title'          => !empty($row['hero_title']) ? $row['hero_title'] : $defaults['hero_title'],
        'hero_description'    => !empty($row['hero_description']) ? $row['hero_description'] : $defaults['hero_description'],
        'key_areas_title'     => !empty($row['key_areas_title']) ? $row['key_areas_title'] : $defaults['key_areas_title'],
        'key_areas'           => $key_areas,
        'approach_tag'        => !empty($row['approach_tag']) ? $row['approach_tag'] : $defaults['approach_tag'],
        'approach_title'      => !empty($row['approach_title']) ? $row['approach_title'] : $defaults['approach_title'],
        'approach_description'=> !empty($row['approach_description']) ? $row['approach_description'] : $defaults['approach_description'],
        'pathway1_badge'      => !empty($row['pathway1_badge']) ? $row['pathway1_badge'] : $defaults['pathway1_badge'],
        'pathway1_title'      => !empty($row['pathway1_title']) ? $row['pathway1_title'] : $defaults['pathway1_title'],
        'pathway1_description'=> !empty($row['pathway1_description']) ? $row['pathway1_description'] : $defaults['pathway1_description'],
        'pathway1_items'      => $pathway1_items,
        'pathway2_badge'      => !empty($row['pathway2_badge']) ? $row['pathway2_badge'] : $defaults['pathway2_badge'],
        'pathway2_title'      => !empty($row['pathway2_title']) ? $row['pathway2_title'] : $defaults['pathway2_title'],
        'pathway2_description'=> !empty($row['pathway2_description']) ? $row['pathway2_description'] : $defaults['pathway2_description'],
        'pathway2_items'      => $pathway2_items,
        'cta_title'           => !empty($row['cta_title']) ? $row['cta_title'] : $defaults['cta_title'],
        'cta_description'     => !empty($row['cta_description']) ? $row['cta_description'] : $defaults['cta_description'],
        'cta_btn1_text'       => !empty($row['cta_btn1_text']) ? $row['cta_btn1_text'] : $defaults['cta_btn1_text'],
        'cta_btn1_link'       => !empty($row['cta_btn1_link']) ? $row['cta_btn1_link'] : $defaults['cta_btn1_link'],
        'cta_btn2_text'       => !empty($row['cta_btn2_text']) ? $row['cta_btn2_text'] : $defaults['cta_btn2_text'],
        'cta_btn2_link'       => !empty($row['cta_btn2_link']) ? $row['cta_btn2_link'] : $defaults['cta_btn2_link'],
    ];
}

function save_impact_summary_db($params) {
    global $wpdb;
    $table = $wpdb->prefix . 'impact_summary';

    if ($wpdb->get_var("SHOW TABLES LIKE '$table'") != $table) {
        if (function_exists('custom_setup_database_tables')) {
            custom_setup_database_tables();
        }
    }

    // Process Key Areas
    $key_areas_input = $params['key_areas'] ?? [];
    if (is_string($key_areas_input)) {
        $key_areas_input = json_decode(stripslashes($key_areas_input), true) ?: [];
    }
    $sanitized_key_areas = [];
    if (is_array($key_areas_input)) {
        foreach ($key_areas_input as $idx => $card) {
            $sanitized_key_areas[] = [
                'id'          => sanitize_text_field($card['id'] ?? (string)($idx + 1)),
                'title'       => sanitize_text_field($card['title'] ?? ''),
                'description' => sanitize_textarea_field($card['description'] ?? ''),
                'icon_color'  => sanitize_text_field($card['icon_color'] ?? 'brand'),
                'bg_color'    => sanitize_text_field($card['bg_color'] ?? 'brand'),
                'icon_name'   => sanitize_text_field($card['icon_name'] ?? 'globe')
            ];
        }
    }

    // Process Pathway 1 Items
    $pathway1_input = $params['pathway1_items'] ?? [];
    if (is_string($pathway1_input)) {
        $pathway1_input = json_decode(stripslashes($pathway1_input), true) ?: [];
    }
    $sanitized_pathway1 = [];
    if (is_array($pathway1_input)) {
        foreach ($pathway1_input as $item) {
            $sanitized_pathway1[] = [
                'title' => sanitize_text_field($item['title'] ?? ''),
                'desc'  => sanitize_textarea_field($item['desc'] ?? '')
            ];
        }
    }

    // Process Pathway 2 Items
    $pathway2_input = $params['pathway2_items'] ?? [];
    if (is_string($pathway2_input)) {
        $pathway2_input = json_decode(stripslashes($pathway2_input), true) ?: [];
    }
    $sanitized_pathway2 = [];
    if (is_array($pathway2_input)) {
        foreach ($pathway2_input as $item) {
            $sanitized_pathway2[] = [
                'title' => sanitize_text_field($item['title'] ?? ''),
                'desc'  => sanitize_textarea_field($item['desc'] ?? '')
            ];
        }
    }

    $data = [
        'hero_title'           => sanitize_text_field($params['hero_title'] ?? 'Our Impact'),
        'hero_description'     => sanitize_textarea_field($params['hero_description'] ?? ''),
        'key_areas_title'      => sanitize_text_field($params['key_areas_title'] ?? 'Key Impact Areas'),
        'key_areas'            => wp_json_encode($sanitized_key_areas),
        'approach_tag'         => sanitize_text_field($params['approach_tag'] ?? 'Our Approach'),
        'approach_title'       => sanitize_text_field($params['approach_title'] ?? 'Bridging Evidence & Action'),
        'approach_description' => sanitize_textarea_field($params['approach_description'] ?? ''),
        'pathway1_badge'       => sanitize_text_field($params['pathway1_badge'] ?? '01'),
        'pathway1_title'       => sanitize_text_field($params['pathway1_title'] ?? 'Research to Policy & Program'),
        'pathway1_description' => sanitize_textarea_field($params['pathway1_description'] ?? ''),
        'pathway1_items'       => wp_json_encode($sanitized_pathway1),
        'pathway2_badge'       => sanitize_text_field($params['pathway2_badge'] ?? '02'),
        'pathway2_title'       => sanitize_text_field($params['pathway2_title'] ?? 'Research to Practice'),
        'pathway2_description' => sanitize_textarea_field($params['pathway2_description'] ?? ''),
        'pathway2_items'       => wp_json_encode($sanitized_pathway2),
        'cta_title'            => sanitize_text_field($params['cta_title'] ?? 'Join Us in Making a Difference'),
        'cta_description'      => sanitize_textarea_field($params['cta_description'] ?? ''),
        'cta_btn1_text'        => sanitize_text_field($params['cta_btn1_text'] ?? 'Partner With Us'),
        'cta_btn1_link'        => sanitize_text_field($params['cta_btn1_link'] ?? '/contact'),
        'cta_btn2_text'        => sanitize_text_field($params['cta_btn2_text'] ?? 'Explore Our Work'),
        'cta_btn2_link'        => sanitize_text_field($params['cta_btn2_link'] ?? '/our-work'),
    ];

    $existing_id = $wpdb->get_var("SELECT id FROM $table ORDER BY id ASC LIMIT 1");

    if ($existing_id) {
        $wpdb->update($table, $data, ['id' => $existing_id]);
    } else {
        $wpdb->insert($table, $data);
    }

    return true;
}

function get_impact_summary_rest() {
    $data = get_impact_summary_data();
    return rest_ensure_response($data);
}

function update_impact_summary_rest($request) {
    $params = json_decode($request->get_body(), true);
    if (empty($params)) {
        $params = $request->get_params();
    }

    save_impact_summary_db($params);

    return rest_ensure_response([
        'status'  => 'success',
        'message' => 'Impact Summary data updated successfully!',
        'data'    => get_impact_summary_data()
    ]);
}

function impact_summary_manager_page() {
    $updated = false;
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_impact_summary'])) {
        if (check_admin_referer('impact_summary_save_action', 'impact_summary_nonce')) {
            save_impact_summary_db($_POST);
            $updated = true;
        }
    }

    $data = get_impact_summary_data();
    ?>
    <div class="wrap" style="max-width: 1100px; margin-top: 20px;">
        <div style="margin-bottom: 20px;">
            <h1 style="font-size: 26px; font-weight: 700; color: #0f172a; margin: 0;">
                Impact Summary Page Manager
            </h1>
        </div>

        <?php if ($updated): ?>
            <div class="notice notice-success is-dismissible" style="padding: 12px 16px; margin-bottom: 20px; border-left-color: #10b981;">
                <p style="font-size: 14px; margin: 0;"><strong>Success!</strong> Impact Summary page details saved and live on the website!</p>
            </div>
        <?php endif; ?>

        <div id="ajax-toast" style="display: none; padding: 14px 18px; border-radius: 8px; margin-bottom: 20px; font-weight: 500;"></div>

        <form method="POST" action="" id="impact-summary-form" onsubmit="saveImpactSummaryViaAjax(event)" style="display: flex; flex-direction: column; gap: 24px;">
            <?php wp_nonce_field('impact_summary_save_action', 'impact_summary_nonce'); ?>
            <input type="hidden" name="save_impact_summary" value="1">

            <!-- SECTION 1: HERO SECTION -->
            <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 24px; box-shadow: 0 2px 6px rgba(0,0,0,0.03);">
                <div style="margin-bottom: 18px; border-bottom: 1px solid #f1f5f9; padding-bottom: 12px;">
                    <h2 style="font-size: 18px; font-weight: 600; color: #0f172a; margin: 0;">1. Hero Banner Section</h2>
                    <p style="font-size: 12px; color: #64748b; margin: 4px 0 0 0;">Control the top banner title and description on the Impact page.</p>
                </div>

                <div style="display: flex; flex-direction: column; gap: 16px;">
                    <div>
                        <label style="display: block; font-size: 13px; font-weight: 600; color: #334155; margin-bottom: 6px;">Hero Heading</label>
                        <input type="text" id="hero_title" name="hero_title" value="<?php echo esc_attr($data['hero_title']); ?>" class="widefat" style="border-radius: 6px; padding: 8px 12px;" placeholder="Our Impact">
                    </div>

                    <div>
                        <label style="display: block; font-size: 13px; font-weight: 600; color: #334155; margin-bottom: 6px;">Hero Description</label>
                        <textarea id="hero_description" name="hero_description" rows="4" class="widefat" style="border-radius: 6px; padding: 8px 12px;" placeholder="The INCLEN Trust measures its impact..."><?php echo esc_textarea($data['hero_description']); ?></textarea>
                        <small style="color: #64748b; font-size: 11px;">Separate paragraphs with an empty line.</small>
                    </div>
                </div>
            </div>

            <!-- SECTION 2: KEY IMPACT AREAS -->
            <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 24px; box-shadow: 0 2px 6px rgba(0,0,0,0.03);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px; border-bottom: 1px solid #f1f5f9; padding-bottom: 12px;">
                    <div>
                        <h2 style="font-size: 18px; font-weight: 600; color: #0f172a; margin: 0;">2. Key Impact Areas</h2>
                        <p style="font-size: 12px; color: #64748b; margin: 4px 0 0 0;">Add, modify or remove the impact cards displayed in the grid.</p>
                    </div>
                    <button type="button" onclick="addKeyAreaCard()" class="button button-secondary" style="font-weight: 600; color: #0284c7; border-color: #0284c7;">
                        + Add Impact Area
                    </button>
                </div>

                <div style="margin-bottom: 18px;">
                    <label style="display: block; font-size: 13px; font-weight: 600; color: #334155; margin-bottom: 6px;">Section Title</label>
                    <input type="text" id="key_areas_title" name="key_areas_title" value="<?php echo esc_attr($data['key_areas_title']); ?>" class="widefat" style="border-radius: 6px; padding: 8px 12px;" placeholder="Key Impact Areas">
                </div>

                <div id="key_areas_container" style="display: flex; flex-direction: column; gap: 16px;">
                    <!-- JS will populate cards here -->
                </div>
                <input type="hidden" id="key_areas_json" name="key_areas" value="<?php echo esc_attr(wp_json_encode($data['key_areas'])); ?>">
            </div>

            <!-- SECTION 3: STRATEGIC APPROACH OVERVIEW -->
            <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 24px; box-shadow: 0 2px 6px rgba(0,0,0,0.03);">
                <div style="margin-bottom: 18px; border-bottom: 1px solid #f1f5f9; padding-bottom: 12px;">
                    <h2 style="font-size: 18px; font-weight: 600; color: #0f172a; margin: 0;">3. Strategic Pathways: Section Header</h2>
                    <p style="font-size: 12px; color: #64748b; margin: 4px 0 0 0;">The overarching introductory header for &quot;Bridging Evidence &amp; Action&quot;.</p>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 16px; margin-bottom: 16px;">
                    <div>
                        <label style="display: block; font-size: 13px; font-weight: 600; color: #334155; margin-bottom: 6px;">Subtitle / Tag</label>
                        <input type="text" id="approach_tag" name="approach_tag" value="<?php echo esc_attr($data['approach_tag']); ?>" class="widefat" style="border-radius: 6px; padding: 8px 12px;" placeholder="Our Approach">
                    </div>

                    <div>
                        <label style="display: block; font-size: 13px; font-weight: 600; color: #334155; margin-bottom: 6px;">Main Title</label>
                        <input type="text" id="approach_title" name="approach_title" value="<?php echo esc_attr($data['approach_title']); ?>" class="widefat" style="border-radius: 6px; padding: 8px 12px;" placeholder="Bridging Evidence & Action">
                    </div>
                </div>

                <div>
                    <label style="display: block; font-size: 13px; font-weight: 600; color: #334155; margin-bottom: 6px;">Section Description</label>
                    <textarea id="approach_description" name="approach_description" rows="3" class="widefat" style="border-radius: 6px; padding: 8px 12px;" placeholder="INCLEN Trust bridges the gap..."><?php echo esc_textarea($data['approach_description']); ?></textarea>
                </div>
            </div>

            <!-- SECTION 4: PATHWAY 1 (Research to Policy) -->
            <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 24px; box-shadow: 0 2px 6px rgba(0,0,0,0.03);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px; border-bottom: 1px solid #f1f5f9; padding-bottom: 12px;">
                    <div>
                        <h2 style="font-size: 18px; font-weight: 600; color: #0f172a; margin: 0;">4. Pathway 1: Research to Policy &amp; Program</h2>
                        <p style="font-size: 12px; color: #64748b; margin: 4px 0 0 0;">Left sticky card and right list items.</p>
                    </div>
                    <button type="button" onclick="addPathway1Item()" class="button button-secondary" style="font-weight: 600; color: #16a34a; border-color: #16a34a;">
                        + Add Policy Item
                    </button>
                </div>

                <div style="display: grid; grid-template-columns: 120px 1fr; gap: 16px; margin-bottom: 16px;">
                    <div>
                        <label style="display: block; font-size: 13px; font-weight: 600; color: #334155; margin-bottom: 6px;">Badge No.</label>
                        <input type="text" id="pathway1_badge" name="pathway1_badge" value="<?php echo esc_attr($data['pathway1_badge']); ?>" class="widefat" style="border-radius: 6px; padding: 8px 12px;" placeholder="01">
                    </div>
                    <div>
                        <label style="display: block; font-size: 13px; font-weight: 600; color: #334155; margin-bottom: 6px;">Pathway Title</label>
                        <input type="text" id="pathway1_title" name="pathway1_title" value="<?php echo esc_attr($data['pathway1_title']); ?>" class="widefat" style="border-radius: 6px; padding: 8px 12px;" placeholder="Research to Policy & Program">
                    </div>
                </div>

                <div style="margin-bottom: 20px;">
                    <label style="display: block; font-size: 13px; font-weight: 600; color: #334155; margin-bottom: 6px;">Pathway Summary (Card Text)</label>
                    <textarea id="pathway1_description" name="pathway1_description" rows="2" class="widefat" style="border-radius: 6px; padding: 8px 12px;" placeholder="INCLEN acts as a strategic technical partner..."><?php echo esc_textarea($data['pathway1_description']); ?></textarea>
                </div>

                <h4 style="font-size: 14px; font-weight: 700; color: #334155; margin-bottom: 10px;">Items List (Policy Initiatives):</h4>
                <div id="pathway1_items_container" style="display: flex; flex-direction: column; gap: 12px;">
                    <!-- Pathway 1 items rendered via JS -->
                </div>
                <input type="hidden" id="pathway1_items_json" name="pathway1_items" value="<?php echo esc_attr(wp_json_encode($data['pathway1_items'])); ?>">
            </div>

            <!-- SECTION 5: PATHWAY 2 (Research to Practice) -->
            <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 24px; box-shadow: 0 2px 6px rgba(0,0,0,0.03);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px; border-bottom: 1px solid #f1f5f9; padding-bottom: 12px;">
                    <div>
                        <h2 style="font-size: 18px; font-weight: 600; color: #0f172a; margin: 0;">5. Pathway 2: Research to Practice</h2>
                        <p style="font-size: 12px; color: #64748b; margin: 4px 0 0 0;">Sticky card &amp; right action tools list.</p>
                    </div>
                    <button type="button" onclick="addPathway2Item()" class="button button-secondary" style="font-weight: 600; color: #f59e0b; border-color: #f59e0b;">
                        + Add Practice Item
                    </button>
                </div>

                <div style="display: grid; grid-template-columns: 120px 1fr; gap: 16px; margin-bottom: 16px;">
                    <div>
                        <label style="display: block; font-size: 13px; font-weight: 600; color: #334155; margin-bottom: 6px;">Badge No.</label>
                        <input type="text" id="pathway2_badge" name="pathway2_badge" value="<?php echo esc_attr($data['pathway2_badge']); ?>" class="widefat" style="border-radius: 6px; padding: 8px 12px;" placeholder="02">
                    </div>
                    <div>
                        <label style="display: block; font-size: 13px; font-weight: 600; color: #334155; margin-bottom: 6px;">Pathway Title</label>
                        <input type="text" id="pathway2_title" name="pathway2_title" value="<?php echo esc_attr($data['pathway2_title']); ?>" class="widefat" style="border-radius: 6px; padding: 8px 12px;" placeholder="Research to Practice">
                    </div>
                </div>

                <div style="margin-bottom: 20px;">
                    <label style="display: block; font-size: 13px; font-weight: 600; color: #334155; margin-bottom: 6px;">Pathway Summary (Card Text)</label>
                    <textarea id="pathway2_description" name="pathway2_description" rows="2" class="widefat" style="border-radius: 6px; padding: 8px 12px;" placeholder="The organization develops actionable tools..."><?php echo esc_textarea($data['pathway2_description']); ?></textarea>
                </div>

                <h4 style="font-size: 14px; font-weight: 700; color: #334155; margin-bottom: 10px;">Items List (Actionable Tools):</h4>
                <div id="pathway2_items_container" style="display: flex; flex-direction: column; gap: 12px;">
                    <!-- Pathway 2 items rendered via JS -->
                </div>
                <input type="hidden" id="pathway2_items_json" name="pathway2_items" value="<?php echo esc_attr(wp_json_encode($data['pathway2_items'])); ?>">
            </div>

            <!-- SECTION 6: CALL TO ACTION (CTA) -->
            <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 24px; box-shadow: 0 2px 6px rgba(0,0,0,0.03);">
                <div style="margin-bottom: 18px; border-bottom: 1px solid #f1f5f9; padding-bottom: 12px;">
                    <h2 style="font-size: 18px; font-weight: 600; color: #0f172a; margin: 0;">6. Call to Action (CTA) Section</h2>
                    <p style="font-size: 12px; color: #64748b; margin: 4px 0 0 0;">Bottom section with invitation buttons.</p>
                </div>

                <div style="display: flex; flex-direction: column; gap: 16px;">
                    <div>
                        <label style="display: block; font-size: 13px; font-weight: 600; color: #334155; margin-bottom: 6px;">CTA Heading</label>
                        <input type="text" id="cta_title" name="cta_title" value="<?php echo esc_attr($data['cta_title']); ?>" class="widefat" style="border-radius: 6px; padding: 8px 12px;" placeholder="Join Us in Making a Difference">
                    </div>

                    <div>
                        <label style="display: block; font-size: 13px; font-weight: 600; color: #334155; margin-bottom: 6px;">CTA Description</label>
                        <textarea id="cta_description" name="cta_description" rows="2" class="widefat" style="border-radius: 6px; padding: 8px 12px;" placeholder="Partner with INCLEN to drive global health..."><?php echo esc_textarea($data['cta_description']); ?></textarea>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                        <div style="background: #f8fafc; padding: 14px; border-radius: 8px; border: 1px solid #e2e8f0;">
                            <span style="display: block; font-size: 12px; font-weight: 700; color: #475569; text-transform: uppercase; margin-bottom: 8px;">Primary Button</span>
                            <div style="margin-bottom: 8px;">
                                <label style="font-size: 12px; color: #64748b;">Button Label</label>
                                <input type="text" id="cta_btn1_text" name="cta_btn1_text" value="<?php echo esc_attr($data['cta_btn1_text']); ?>" class="widefat" style="border-radius: 6px; padding: 6px 10px;" placeholder="Partner With Us">
                            </div>
                            <div>
                                <label style="font-size: 12px; color: #64748b;">Button Link / URL</label>
                                <input type="text" id="cta_btn1_link" name="cta_btn1_link" value="<?php echo esc_attr($data['cta_btn1_link']); ?>" class="widefat" style="border-radius: 6px; padding: 6px 10px;" placeholder="/contact">
                            </div>
                        </div>

                        <div style="background: #f8fafc; padding: 14px; border-radius: 8px; border: 1px solid #e2e8f0;">
                            <span style="display: block; font-size: 12px; font-weight: 700; color: #475569; text-transform: uppercase; margin-bottom: 8px;">Secondary Button</span>
                            <div style="margin-bottom: 8px;">
                                <label style="font-size: 12px; color: #64748b;">Button Label</label>
                                <input type="text" id="cta_btn2_text" name="cta_btn2_text" value="<?php echo esc_attr($data['cta_btn2_text']); ?>" class="widefat" style="border-radius: 6px; padding: 6px 10px;" placeholder="Explore Our Work">
                            </div>
                            <div>
                                <label style="font-size: 12px; color: #64748b;">Button Link / URL</label>
                                <input type="text" id="cta_btn2_link" name="cta_btn2_link" value="<?php echo esc_attr($data['cta_btn2_link']); ?>" class="widefat" style="border-radius: 6px; padding: 6px 10px;" placeholder="/our-work">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SUBMIT BUTTON -->
            <div style="position: sticky; bottom: 20px; background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(8px); padding: 16px 24px; border-radius: 12px; border: 1px solid #cbd5e1; box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1); display: flex; align-items: center; justify-content: space-between; z-index: 100;">
                <span style="color: #64748b; font-size: 13px;">Save changes to update the live website immediately.</span>
                <button type="submit" id="save-btn" class="button button-primary button-hero" style="font-size: 15px; font-weight: 700; padding: 0 28px; height: 46px; line-height: 46px; border-radius: 8px; background: #0284c7; border-color: #0284c7;">
                    Save Impact Summary Page
                </button>
            </div>
        </form>
    </div>

    <script>
    const IMPACT_API_ENDPOINT = "<?php echo esc_url(site_url('/index.php?rest_route=/impact-summary/v1/save')); ?>";
    const IMPACT_WP_NONCE = "<?php echo wp_create_nonce('wp_rest'); ?>";

    let keyAreas = <?php echo json_encode($data['key_areas']); ?>;
    let pathway1Items = <?php echo json_encode($data['pathway1_items']); ?>;
    let pathway2Items = <?php echo json_encode($data['pathway2_items']); ?>;

    function renderKeyAreas() {
        const container = document.getElementById('key_areas_container');
        container.innerHTML = '';

        if (!keyAreas || keyAreas.length === 0) {
            container.innerHTML = '<div style="padding: 16px; background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 8px; text-align: center; color: #64748b;">No impact areas added yet. Click &quot;+ Add Impact Area&quot; above.</div>';
            return;
        }

        keyAreas.forEach((card, index) => {
            const cardEl = document.createElement('div');
            cardEl.style.cssText = 'background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 16px; position: relative; display: flex; flex-direction: column; gap: 10px;';
            cardEl.innerHTML = `
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <strong style="color: #334155; font-size: 13px;">Impact Card #${index + 1}</strong>
                    <button type="button" onclick="removeKeyArea(${index})" style="background: none; border: none; color: #ef4444; font-weight: bold; cursor: pointer; font-size: 13px;">Remove Card ✕</button>
                </div>
                <div style="display: grid; grid-template-columns: 2fr 1fr 1fr; gap: 12px;">
                    <div>
                        <label style="display: block; font-size: 11px; font-weight: 600; color: #64748b; margin-bottom: 4px;">Card Title</label>
                        <input type="text" class="widefat" value="${escapeHtml(card.title || '')}" oninput="updateKeyArea(${index}, 'title', this.value)" placeholder="e.g. Global Research Network" style="border-radius: 6px; padding: 6px 10px;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 11px; font-weight: 600; color: #64748b; margin-bottom: 4px;">Color Theme</label>
                        <select class="widefat" onchange="updateKeyArea(${index}, 'icon_color', this.value)" style="border-radius: 6px; padding: 6px 10px;">
                            <option value="brand" ${card.icon_color === 'brand' ? 'selected' : ''}>Brand (Orange/Accent)</option>
                            <option value="blue" ${card.icon_color === 'blue' ? 'selected' : ''}>Blue</option>
                            <option value="green" ${card.icon_color === 'green' ? 'selected' : ''}>Green</option>
                            <option value="purple" ${card.icon_color === 'purple' ? 'selected' : ''}>Purple</option>
                            <option value="red" ${card.icon_color === 'red' ? 'selected' : ''}>Red</option>
                        </select>
                    </div>
                    <div>
                        <label style="display: block; font-size: 11px; font-weight: 600; color: #64748b; margin-bottom: 4px;">Icon Preset</label>
                        <select class="widefat" onchange="updateKeyArea(${index}, 'icon_name', this.value)" style="border-radius: 6px; padding: 6px 10px;">
                            <option value="globe" ${card.icon_name === 'globe' ? 'selected' : ''}>Globe / Network</option>
                            <option value="beaker" ${card.icon_name === 'beaker' ? 'selected' : ''}>Beaker / Surveillance</option>
                            <option value="flask" ${card.icon_name === 'flask' ? 'selected' : ''}>Flask / Diagnostic</option>
                            <option value="document" ${card.icon_name === 'document' ? 'selected' : ''}>Document / Policy</option>
                            <option value="clipboard" ${card.icon_name === 'clipboard' ? 'selected' : ''}>Clipboard / Priority</option>
                            <option value="shield" ${card.icon_name === 'shield' ? 'selected' : ''}>Shield / Vaccine</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label style="display: block; font-size: 11px; font-weight: 600; color: #64748b; margin-bottom: 4px;">Description</label>
                    <textarea class="widefat" rows="2" oninput="updateKeyArea(${index}, 'description', this.value)" placeholder="Card description text..." style="border-radius: 6px; padding: 6px 10px;">${escapeHtml(card.description || '')}</textarea>
                </div>
            `;
            container.appendChild(cardEl);
        });

        document.getElementById('key_areas_json').value = JSON.stringify(keyAreas);
    }

    function addKeyAreaCard() {
        keyAreas.push({
            id: String(Date.now()),
            title: '',
            description: '',
            icon_color: 'brand',
            bg_color: 'brand',
            icon_name: 'globe'
        });
        renderKeyAreas();
    }

    function updateKeyArea(index, key, value) {
        if (keyAreas[index]) {
            keyAreas[index][key] = value;
            if (key === 'icon_color') {
                keyAreas[index]['bg_color'] = value;
            }
            document.getElementById('key_areas_json').value = JSON.stringify(keyAreas);
        }
    }

    function removeKeyArea(index) {
        if (confirm('Are you sure you want to remove this impact card?')) {
            keyAreas.splice(index, 1);
            renderKeyAreas();
        }
    }

    // Pathway 1 Items (Research to Policy)
    function renderPathway1Items() {
        const container = document.getElementById('pathway1_items_container');
        container.innerHTML = '';

        if (!pathway1Items || pathway1Items.length === 0) {
            container.innerHTML = '<div style="padding: 12px; background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 8px; text-align: center; color: #64748b;">No policy items added. Click &quot;+ Add Policy Item&quot; above.</div>';
            return;
        }

        pathway1Items.forEach((item, index) => {
            const el = document.createElement('div');
            el.style.cssText = 'background: #f8fafc; border: 1px solid #e2e8f0; border-left: 4px solid #16a34a; border-radius: 8px; padding: 14px; position: relative;';
            el.innerHTML = `
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                    <label style="font-size: 11px; font-weight: 700; color: #16a34a; text-transform: uppercase;">Item #${index + 1}</label>
                    <button type="button" onclick="removePathway1Item(${index})" style="background: none; border: none; color: #ef4444; font-weight: bold; cursor: pointer; font-size: 12px;">Remove ✕</button>
                </div>
                <div style="margin-bottom: 8px;">
                    <input type="text" class="widefat" value="${escapeHtml(item.title || '')}" oninput="updatePathway1Item(${index}, 'title', this.value)" placeholder="Initiative Title" style="border-radius: 6px; padding: 6px 10px; font-weight: 600;">
                </div>
                <div>
                    <textarea class="widefat" rows="2" oninput="updatePathway1Item(${index}, 'desc', this.value)" placeholder="Initiative Description" style="border-radius: 6px; padding: 6px 10px;">${escapeHtml(item.desc || '')}</textarea>
                </div>
            `;
            container.appendChild(el);
        });

        document.getElementById('pathway1_items_json').value = JSON.stringify(pathway1Items);
    }

    function addPathway1Item() {
        pathway1Items.push({ title: '', desc: '' });
        renderPathway1Items();
    }

    function updatePathway1Item(index, key, value) {
        if (pathway1Items[index]) {
            pathway1Items[index][key] = value;
            document.getElementById('pathway1_items_json').value = JSON.stringify(pathway1Items);
        }
    }

    function removePathway1Item(index) {
        pathway1Items.splice(index, 1);
        renderPathway1Items();
    }

    // Pathway 2 Items (Research to Practice)
    function renderPathway2Items() {
        const container = document.getElementById('pathway2_items_container');
        container.innerHTML = '';

        if (!pathway2Items || pathway2Items.length === 0) {
            container.innerHTML = '<div style="padding: 12px; background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 8px; text-align: center; color: #64748b;">No practice items added. Click &quot;+ Add Practice Item&quot; above.</div>';
            return;
        }

        pathway2Items.forEach((item, index) => {
            const el = document.createElement('div');
            el.style.cssText = 'background: #f8fafc; border: 1px solid #e2e8f0; border-left: 4px solid #f59e0b; border-radius: 8px; padding: 14px; position: relative;';
            el.innerHTML = `
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                    <label style="font-size: 11px; font-weight: 700; color: #f59e0b; text-transform: uppercase;">Item #${index + 1}</label>
                    <button type="button" onclick="removePathway2Item(${index})" style="background: none; border: none; color: #ef4444; font-weight: bold; cursor: pointer; font-size: 12px;">Remove ✕</button>
                </div>
                <div style="margin-bottom: 8px;">
                    <input type="text" class="widefat" value="${escapeHtml(item.title || '')}" oninput="updatePathway2Item(${index}, 'title', this.value)" placeholder="Tool / Practice Title" style="border-radius: 6px; padding: 6px 10px; font-weight: 600;">
                </div>
                <div>
                    <textarea class="widefat" rows="2" oninput="updatePathway2Item(${index}, 'desc', this.value)" placeholder="Tool / Practice Description" style="border-radius: 6px; padding: 6px 10px;">${escapeHtml(item.desc || '')}</textarea>
                </div>
            `;
            container.appendChild(el);
        });

        document.getElementById('pathway2_items_json').value = JSON.stringify(pathway2Items);
    }

    function addPathway2Item() {
        pathway2Items.push({ title: '', desc: '' });
        renderPathway2Items();
    }

    function updatePathway2Item(index, key, value) {
        if (pathway2Items[index]) {
            pathway2Items[index][key] = value;
            document.getElementById('pathway2_items_json').value = JSON.stringify(pathway2Items);
        }
    }

    function removePathway2Item(index) {
        pathway2Items.splice(index, 1);
        renderPathway2Items();
    }

    function escapeHtml(text) {
        if (!text) return '';
        return String(text)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    // Save handler with AJAX
    function saveImpactSummaryViaAjax(event) {
        event.preventDefault();

        const btn = document.getElementById('save-btn');
        btn.disabled = true;
        btn.innerText = 'Saving Changes...';

        const toast = document.getElementById('ajax-toast');
        toast.style.display = 'none';

        const payload = {
            hero_title: document.getElementById('hero_title').value.trim(),
            hero_description: document.getElementById('hero_description').value.trim(),
            key_areas_title: document.getElementById('key_areas_title').value.trim(),
            key_areas: keyAreas,
            approach_tag: document.getElementById('approach_tag').value.trim(),
            approach_title: document.getElementById('approach_title').value.trim(),
            approach_description: document.getElementById('approach_description').value.trim(),
            pathway1_badge: document.getElementById('pathway1_badge').value.trim(),
            pathway1_title: document.getElementById('pathway1_title').value.trim(),
            pathway1_description: document.getElementById('pathway1_description').value.trim(),
            pathway1_items: pathway1Items,
            pathway2_badge: document.getElementById('pathway2_badge').value.trim(),
            pathway2_title: document.getElementById('pathway2_title').value.trim(),
            pathway2_description: document.getElementById('pathway2_description').value.trim(),
            pathway2_items: pathway2Items,
            cta_title: document.getElementById('cta_title').value.trim(),
            cta_description: document.getElementById('cta_description').value.trim(),
            cta_btn1_text: document.getElementById('cta_btn1_text').value.trim(),
            cta_btn1_link: document.getElementById('cta_btn1_link').value.trim(),
            cta_btn2_text: document.getElementById('cta_btn2_text').value.trim(),
            cta_btn2_link: document.getElementById('cta_btn2_link').value.trim()
        };

        fetch(IMPACT_API_ENDPOINT, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': IMPACT_WP_NONCE
            },
            body: JSON.stringify(payload)
        })
        .then(res => res.json())
        .then(data => {
            btn.disabled = false;
            btn.innerText = 'Save Impact Summary Page';

            toast.style.display = 'block';
            toast.style.background = '#dcfce7';
            toast.style.color = '#166534';
            toast.style.border = '1px solid #86efac';
            toast.innerHTML = '<strong>Success!</strong> Impact Summary page settings have been updated and are live on the website.';

            window.scrollTo({ top: 0, behavior: 'smooth' });
        })
        .catch(err => {
            console.error('Save failed via AJAX, submitting form natively:', err);
            document.getElementById('impact-summary-form').submit();
        });
    }

    document.addEventListener('DOMContentLoaded', () => {
        renderKeyAreas();
        renderPathway1Items();
        renderPathway2Items();
    });
    </script>
    <?php
}
