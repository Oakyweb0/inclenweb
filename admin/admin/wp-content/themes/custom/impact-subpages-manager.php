<?php
/**
 * Impact Sub-Pages Manager (Key Research Findings, Policy Influence, Transforming Lives)
 */

add_action('admin_menu', function () {
    // Key Research Findings
    add_submenu_page(
        'group-our-impact',
        'Key Research Findings',
        'Key Research Findings',
        'manage_options',
        'key-research-findings',
        'key_research_findings_page'
    );

    // Policy Influence
    add_submenu_page(
        'group-our-impact',
        'Policy Influence',
        'Policy Influence',
        'manage_options',
        'policy-influence',
        'policy_influence_page'
    );

    // Transforming Lives
    add_submenu_page(
        'group-our-impact',
        'Transforming Lives',
        'Transforming Lives',
        'manage_options',
        'transforming-lives',
        'transforming_lives_page'
    );
});

// REST API setup
add_action('rest_api_init', function () {
    register_rest_route('impact-sub/v1', '/all', [
        'methods'             => 'GET',
        'callback'            => function() {
            return rest_ensure_response([
                'findings'     => get_option('key_research_findings_data', []),
                'policy'       => get_option('policy_influence_data', []),
                'transforming' => get_option('transforming_lives_data', [])
            ]);
        },
        'permission_callback' => '__return_true',
    ]);

    register_rest_route('impact-sub/v1', '/save-(?P<section>[a-zA-Z0-9_-]+)', [
        'methods'             => 'POST',
        'callback'            => function($request) {
            $section = $request->get_param('section');
            $body = $request->get_json_params();
            if ($section === 'findings') {
                update_option('key_research_findings_data', $body);
            } elseif ($section === 'policy') {
                update_option('policy_influence_data', $body);
            } elseif ($section === 'transforming') {
                update_option('transforming_lives_data', $body);
            }
            return rest_ensure_response(['success' => true, 'message' => 'Saved successfully']);
        },
        'permission_callback' => '__return_true',
    ]);
});

function key_research_findings_page() {
    $data = get_option('key_research_findings_data', [
        'title' => 'Key Research Findings & Discoveries',
        'description' => 'Disseminating evidence-based outcomes, publication summaries, and epidemiological insights.',
        'highlights' => 'Published 150+ peer-reviewed studies across leading global health journals.'
    ]);
    ?>
    <div class="wrap" style="max-width: 1000px;">
        <div style="background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); color: #fff; padding: 22px 28px; border-radius: 12px; margin-bottom: 25px;">
            <h1 style="color: #fff; margin: 0 0 6px 0; font-size: 24px;">🔬 Key Research Findings Manager</h1>
            <p style="margin: 0; color: #94a3b8; font-size: 14px;">Manage research findings, publication breakthroughs, and evidence highlights for <code>/key-research-findings</code></p>
        </div>
        <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 25px;">
            <div style="display: flex; flex-direction: column; gap: 16px;">
                <div>
                    <label style="display: block; font-weight: 700; color: #334155; margin-bottom: 6px;">Section Title</label>
                    <input type="text" id="findings_title" value="<?php echo esc_attr($data['title'] ?? ''); ?>" style="width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px;">
                </div>
                <div>
                    <label style="display: block; font-weight: 700; color: #334155; margin-bottom: 6px;">Overview / Description</label>
                    <textarea id="findings_desc" rows="3" style="width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px;"><?php echo esc_textarea($data['description'] ?? ''); ?></textarea>
                </div>
                <div>
                    <label style="display: block; font-weight: 700; color: #334155; margin-bottom: 6px;">Key Highlights</label>
                    <textarea id="findings_highlights" rows="4" style="width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px;"><?php echo esc_textarea($data['highlights'] ?? ''); ?></textarea>
                </div>
                <button type="button" id="save-findings-btn" style="background: #ea580c; color: #fff; border: none; padding: 12px 24px; border-radius: 8px; font-weight: 700; cursor: pointer; align-self: flex-start;">
                    💾 Save Key Findings
                </button>
                <div id="findings-msg" style="display:none; padding: 10px; border-radius: 6px; font-weight: 600;"></div>
            </div>
        </div>
    </div>
    <script>
    jQuery(document).ready(function($) {
        $('#save-findings-btn').on('click', function() {
            var btn = $(this);
            btn.text('Saving...').prop('disabled', true);
            var payload = {
                title: $('#findings_title').val(),
                description: $('#findings_desc').val(),
                highlights: $('#findings_highlights').val()
            };
            fetch('<?php echo esc_url_raw(rest_url('impact-sub/v1/save-findings')); ?>', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            }).then(function() {
                btn.text('💾 Save Key Findings').prop('disabled', false);
                $('#findings-msg').css({ display: 'block', background: '#dcfce7', color: '#15803d' }).text('✓ Saved successfully!').fadeIn();
                setTimeout(function() { $('#findings-msg').fadeOut(); }, 3500);
            });
        });
    });
    </script>
    <?php
}

function policy_influence_page() {
    $data = get_option('policy_influence_data', [
        'title' => 'Translating Evidence into National Health Guidelines',
        'description' => 'Collaborating directly with Ministry of Health, ICMR, NITI Aayog, and WHO to shape clinical standards.',
        'policies' => "1. National Immunization Guidelines & Surveillance\n2. Child Survival & Neonatal Care Frameworks\n3. Anti-Microbial Resistance (AMR) Stewardship"
    ]);
    ?>
    <div class="wrap" style="max-width: 1000px;">
        <div style="background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); color: #fff; padding: 22px 28px; border-radius: 12px; margin-bottom: 25px;">
            <h1 style="color: #fff; margin: 0 0 6px 0; font-size: 24px;">🏛️ Policy Influence Manager</h1>
            <p style="margin: 0; color: #94a3b8; font-size: 14px;">Manage health policy contributions, advisory roles, and regulatory impact for <code>/policy-influence</code></p>
        </div>
        <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 25px;">
            <div style="display: flex; flex-direction: column; gap: 16px;">
                <div>
                    <label style="display: block; font-weight: 700; color: #334155; margin-bottom: 6px;">Heading</label>
                    <input type="text" id="policy_title" value="<?php echo esc_attr($data['title'] ?? ''); ?>" style="width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px;">
                </div>
                <div>
                    <label style="display: block; font-weight: 700; color: #334155; margin-bottom: 6px;">Policy Impact Summary</label>
                    <textarea id="policy_desc" rows="3" style="width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px;"><?php echo esc_textarea($data['description'] ?? ''); ?></textarea>
                </div>
                <div>
                    <label style="display: block; font-weight: 700; color: #334155; margin-bottom: 6px;">Key National Policies Influenced</label>
                    <textarea id="policy_list" rows="5" style="width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px;"><?php echo esc_textarea($data['policies'] ?? ''); ?></textarea>
                </div>
                <button type="button" id="save-policy-btn" style="background: #ea580c; color: #fff; border: none; padding: 12px 24px; border-radius: 8px; font-weight: 700; cursor: pointer; align-self: flex-start;">
                    💾 Save Policy Data
                </button>
                <div id="policy-msg" style="display:none; padding: 10px; border-radius: 6px; font-weight: 600;"></div>
            </div>
        </div>
    </div>
    <script>
    jQuery(document).ready(function($) {
        $('#save-policy-btn').on('click', function() {
            var btn = $(this);
            btn.text('Saving...').prop('disabled', true);
            var payload = {
                title: $('#policy_title').val(),
                description: $('#policy_desc').val(),
                policies: $('#policy_list').val()
            };
            fetch('<?php echo esc_url_raw(rest_url('impact-sub/v1/save-policy')); ?>', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            }).then(function() {
                btn.text('💾 Save Policy Data').prop('disabled', false);
                $('#policy-msg').css({ display: 'block', background: '#dcfce7', color: '#15803d' }).text('✓ Policy data updated!').fadeIn();
                setTimeout(function() { $('#policy-msg').fadeOut(); }, 3500);
            });
        });
    });
    </script>
    <?php
}

function transforming_lives_page() {
    $data = get_option('transforming_lives_data', [
        'title' => 'Transforming Lives Across India',
        'subtitle' => 'See how our research translates into real-world health solutions.',
        'cta_label' => 'Explore Impact',
        'cta_link' => '/our-impact'
    ]);
    ?>
    <div class="wrap" style="max-width: 1000px;">
        <div style="background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); color: #fff; padding: 22px 28px; border-radius: 12px; margin-bottom: 25px;">
            <h1 style="color: #fff; margin: 0 0 6px 0; font-size: 24px;">✨ Transforming Lives Banner Manager</h1>
            <p style="margin: 0; color: #94a3b8; font-size: 14px;">Manage promotional card and spotlight stories for Our Impact dropdown & pages</p>
        </div>
        <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 25px;">
            <div style="display: flex; flex-direction: column; gap: 16px;">
                <div>
                    <label style="display: block; font-weight: 700; color: #334155; margin-bottom: 6px;">Title</label>
                    <input type="text" id="trans_title" value="<?php echo esc_attr($data['title'] ?? ''); ?>" style="width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px;">
                </div>
                <div>
                    <label style="display: block; font-weight: 700; color: #334155; margin-bottom: 6px;">Subtitle / Tagline</label>
                    <textarea id="trans_sub" rows="3" style="width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px;"><?php echo esc_textarea($data['subtitle'] ?? ''); ?></textarea>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                    <div>
                        <label style="display: block; font-weight: 700; color: #334155; margin-bottom: 6px;">Button Label</label>
                        <input type="text" id="trans_btn" value="<?php echo esc_attr($data['cta_label'] ?? ''); ?>" style="width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px;">
                    </div>
                    <div>
                        <label style="display: block; font-weight: 700; color: #334155; margin-bottom: 6px;">Button Link</label>
                        <input type="text" id="trans_link" value="<?php echo esc_attr($data['cta_link'] ?? ''); ?>" style="width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px;">
                    </div>
                </div>
                <button type="button" id="save-trans-btn" style="background: #ea580c; color: #fff; border: none; padding: 12px 24px; border-radius: 8px; font-weight: 700; cursor: pointer; align-self: flex-start;">
                    💾 Save Transforming Lives Banner
                </button>
                <div id="trans-msg" style="display:none; padding: 10px; border-radius: 6px; font-weight: 600;"></div>
            </div>
        </div>
    </div>
    <script>
    jQuery(document).ready(function($) {
        $('#save-trans-btn').on('click', function() {
            var btn = $(this);
            btn.text('Saving...').prop('disabled', true);
            var payload = {
                title: $('#trans_title').val(),
                subtitle: $('#trans_sub').val(),
                cta_label: $('#trans_btn').val(),
                cta_link: $('#trans_link').val()
            };
            fetch('<?php echo esc_url_raw(rest_url('impact-sub/v1/save-transforming')); ?>', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            }).then(function() {
                btn.text('💾 Save Transforming Lives Banner').prop('disabled', false);
                $('#trans-msg').css({ display: 'block', background: '#dcfce7', color: '#15803d' }).text('✓ Updated successfully!').fadeIn();
                setTimeout(function() { $('#trans-msg').fadeOut(); }, 3500);
            });
        });
    });
    </script>
    <?php
}
