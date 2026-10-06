<?php
/**
 * Community Activities Manager - WordPress Admin & REST API
 */

add_action('admin_menu', function () {
    add_submenu_page(
        'group-our-work',
        'Community Activities',
        'Community Activities',
        'manage_options',
        'community-activities-manager',
        'community_activities_manager_page'
    );
});

add_action('rest_api_init', function () {
    register_rest_route('community-activities/v1', '/all', [
        'methods'             => 'GET',
        'callback'            => 'get_community_activities_data',
        'permission_callback' => '__return_true',
    ]);

    register_rest_route('community-activities/v1', '/save', [
        'methods'             => 'POST',
        'callback'            => 'save_community_activities_data',
        'permission_callback' => '__return_true',
    ]);
});

function get_community_activities_data() {
    global $wpdb;
    $table = $wpdb->prefix . 'community_activities';
    $row = $wpdb->get_row("SELECT * FROM $table ORDER BY id ASC LIMIT 1", ARRAY_A);
    if (!$row) {
        return new WP_REST_Response(['status' => 'not_found', 'data' => null], 404);
    }

    if (!empty($row['initiatives']) && is_string($row['initiatives'])) {
        $row['initiatives'] = json_decode($row['initiatives'], true);
    }

    return new WP_REST_Response(['status' => 'success', 'data' => $row], 200);
}

function save_community_activities_data($request) {
    global $wpdb;
    $table = $wpdb->prefix . 'community_activities';
    if (function_exists('custom_setup_database_tables')) {
        custom_setup_database_tables();
    }

    $params = $request->get_json_params();
    if (empty($params)) {
        $params = json_decode($request->get_body(), true);
    }

    if (empty($params)) {
        return new WP_REST_Response(['status' => 'error', 'message' => 'Invalid parameters'], 400);
    }

    $initiatives = isset($params['initiatives']) ? (is_array($params['initiatives']) ? json_encode($params['initiatives']) : $params['initiatives']) : '[]';

    $data = [
        'hero_badge'          => sanitize_text_field($params['hero_badge'] ?? ''),
        'hero_heading'        => sanitize_text_field($params['hero_heading'] ?? ''),
        'hero_description'    => sanitize_textarea_field($params['hero_description'] ?? ''),
        'hero_subdescription' => sanitize_textarea_field($params['hero_subdescription'] ?? ''),
        'intro_heading'       => sanitize_textarea_field($params['intro_heading'] ?? ''),
        'intro_description'   => sanitize_textarea_field($params['intro_description'] ?? ''),
        'initiatives'         => $initiatives,
    ];

    $existing = $wpdb->get_var("SELECT id FROM $table LIMIT 1");
    if ($existing) {
        $updated = $wpdb->update($table, $data, ['id' => $existing]);
        return new WP_REST_Response(['status' => 'success', 'message' => 'Community Activities updated successfully!'], 200);
    } else {
        $inserted = $wpdb->insert($table, $data);
        return new WP_REST_Response(['status' => 'success', 'message' => 'Community Activities saved successfully!'], 200);
    }
}

function community_activities_manager_page() {
    global $wpdb;
    $table = $wpdb->prefix . 'community_activities';
    if (function_exists('custom_setup_database_tables')) {
        custom_setup_database_tables();
    }
    $data = $wpdb->get_row("SELECT * FROM $table LIMIT 1", ARRAY_A);

    $initiatives = !empty($data['initiatives']) ? json_decode($data['initiatives'], true) : [];
    if (!is_array($initiatives)) $initiatives = [];
    ?>
    <div class="wrap" style="max-width: 1200px; margin: 20px auto; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 25px; border-bottom: 2px solid #e2e8f0; padding-bottom: 15px;">
            <div>
                <h1 style="font-size: 28px; font-weight: 700; color: #0f172a; margin: 0;">Community Activities Manager</h1>
                <p style="color: #64748b; margin: 5px 0 0 0; font-size: 14px;">Manage hero, grassroots mission statement, and outreach initiatives cards for <code>/community-activities</code></p>
            </div>
            <div>
                <button type="button" id="save-ca-btn-top" class="button button-primary" style="background:#0284c7; border-color:#0284c7; padding: 6px 20px; font-size: 15px; height:auto; border-radius:6px; font-weight:600;">💾 Save Changes</button>
            </div>
        </div>

        <div id="ca-notice" style="display:none; padding:15px; border-radius:8px; margin-bottom:20px; font-weight:600; font-size:14px;"></div>

        <form id="ca-form" onsubmit="event.preventDefault(); saveCaData();">
            <!-- HERO SECTION -->
            <div style="background:#fff; border:1px solid #cbd5e1; border-radius:12px; padding:25px; margin-bottom:25px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                <h3 style="font-size:18px; color:#0f172a; margin-top:0; border-bottom:1px solid #e2e8f0; padding-bottom:10px;">1. Hero Banner</h3>
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:15px; margin-top:15px;">
                    <div>
                        <label style="display:block; font-weight:600; margin-bottom:5px; color:#334155;">Hero Top Tag / Badge</label>
                        <input type="text" id="ca_hero_badge" value="<?php echo esc_attr($data['hero_badge'] ?? 'Outreach • Education • Prevention'); ?>" style="width:100%; padding:8px 12px; border:1px solid #cbd5e1; border-radius:6px;">
                    </div>
                    <div>
                        <label style="display:block; font-weight:600; margin-bottom:5px; color:#334155;">Hero Heading</label>
                        <input type="text" id="ca_hero_heading" value="<?php echo esc_attr($data['hero_heading'] ?? 'Community Activities'); ?>" style="width:100%; padding:8px 12px; border:1px solid #cbd5e1; border-radius:6px;">
                    </div>
                    <div>
                        <label style="display:block; font-weight:600; margin-bottom:5px; color:#334155;">Hero Main Description</label>
                        <textarea id="ca_hero_description" rows="2" style="width:100%; padding:8px 12px; border:1px solid #cbd5e1; border-radius:6px;"><?php echo esc_textarea($data['hero_description'] ?? ''); ?></textarea>
                    </div>
                    <div>
                        <label style="display:block; font-weight:600; margin-bottom:5px; color:#334155;">Hero Sub Description</label>
                        <textarea id="ca_hero_subdescription" rows="2" style="width:100%; padding:8px 12px; border:1px solid #cbd5e1; border-radius:6px;"><?php echo esc_textarea($data['hero_subdescription'] ?? ''); ?></textarea>
                    </div>
                </div>
            </div>

            <!-- INTRO MISSION SECTION -->
            <div style="background:#fff; border:1px solid #cbd5e1; border-radius:12px; padding:25px; margin-bottom:25px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                <h3 style="font-size:18px; color:#0f172a; margin-top:0; border-bottom:1px solid #e2e8f0; padding-bottom:10px;">2. Grassroots Public Health Statement (Intro Section)</h3>
                <div style="margin-top:15px;">
                    <label style="display:block; font-weight:600; margin-bottom:5px; color:#334155;">Main Statement Heading</label>
                    <textarea id="ca_intro_heading" rows="2" style="width:100%; padding:8px 12px; border:1px solid #cbd5e1; border-radius:6px;"><?php echo esc_textarea($data['intro_heading'] ?? ''); ?></textarea>
                </div>
                <div style="margin-top:15px;">
                    <label style="display:block; font-weight:600; margin-bottom:5px; color:#334155;">Supporting Description</label>
                    <textarea id="ca_intro_description" rows="2" style="width:100%; padding:8px 12px; border:1px solid #cbd5e1; border-radius:6px;"><?php echo esc_textarea($data['intro_description'] ?? ''); ?></textarea>
                </div>
            </div>

            <!-- INITIATIVES CARDS REPEATER -->
            <div style="background:#fff; border:1px solid #cbd5e1; border-radius:12px; padding:25px; margin-bottom:25px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid #e2e8f0; padding-bottom:10px; margin-bottom:15px;">
                    <div>
                        <h3 style="font-size:18px; color:#0f172a; margin:0;">3. Community Initiatives Cards (<span id="initiatives-count"><?php echo count($initiatives); ?></span>)</h3>
                        <p style="color:#64748b; font-size:13px; margin:4px 0 0 0;">Add, reorder, or edit alternating initiative cards with images, tags, and accent badges.</p>
                    </div>
                    <button type="button" onclick="addInitiativeCard()" class="button button-primary" style="background:#0284c7; border:none; padding:5px 15px; border-radius:6px; font-weight:600;">+ Add Initiative Card</button>
                </div>

                <div id="initiatives-container" style="display:flex; flex-direction:column; gap:20px;"></div>
            </div>

            <div style="margin-top:20px; padding:15px 0; border-top:1px solid #e2e8f0; display:flex; justify-content:flex-end;">
                <button type="submit" id="save-ca-btn" class="button button-primary" style="background:#0284c7; border-color:#0284c7; padding: 8px 30px; font-size: 16px; height:auto; border-radius:6px; font-weight:700;">💾 Save All Changes</button>
            </div>
        </form>
    </div>

    <script>
    let initiativesData = <?php echo json_encode($initiatives); ?>;

    function renderInitiatives() {
        const container = document.getElementById('initiatives-container');
        container.innerHTML = '';
        document.getElementById('initiatives-count').innerText = initiativesData.length;

        initiativesData.forEach((item, index) => {
            const card = document.createElement('div');
            card.style.cssText = 'background:#f8fafc; border:1px solid #cbd5e1; border-radius:10px; padding:20px; box-shadow:0 1px 3px rgba(0,0,0,0.04);';
            card.innerHTML = `
                <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid #e2e8f0; padding-bottom:8px; margin-bottom:12px;">
                    <strong style="font-size:16px; color:#0f172a;">Card #${index + 1}: ${item.highlight || item.tag || 'Initiative'}</strong>
                    <button type="button" onclick="removeInitiative(${index})" style="background:#ef4444; color:#fff; border:none; padding:4px 10px; border-radius:4px; font-size:12px; cursor:pointer;">Delete Card</button>
                </div>
                <div style="display:grid; grid-template-columns: 1fr 1fr 1fr; gap:12px; margin-bottom:12px;">
                    <div>
                        <label style="font-size:12px; font-weight:600; color:#334155;">Tag / Pill Label</label>
                        <input type="text" value="${item.tag || ''}" onchange="initiativesData[${index}].tag = this.value" placeholder="e.g. Clinical Outreach" style="width:100%; padding:6px 10px; border:1px solid #cbd5e1; border-radius:4px;">
                    </div>
                    <div>
                        <label style="font-size:12px; font-weight:600; color:#334155;">Tag Style Color</label>
                        <select onchange="initiativesData[${index}].tag_style = this.value" style="width:100%; padding:6px 10px; border:1px solid #cbd5e1; border-radius:4px;">
                            <option value="blue" ${item.tag_style === 'blue' ? 'selected' : ''}>Blue</option>
                            <option value="amber" ${item.tag_style === 'amber' ? 'selected' : ''}>Amber / Orange</option>
                        </select>
                    </div>
                    <div>
                        <label style="font-size:12px; font-weight:600; color:#334155;">Image Position</label>
                        <select onchange="initiativesData[${index}].image_left = (this.value === 'true')" style="width:100%; padding:6px 10px; border:1px solid #cbd5e1; border-radius:4px;">
                            <option value="false" ${!item.image_left ? 'selected' : ''}>Right Side</option>
                            <option value="true" ${item.image_left ? 'selected' : ''}>Left Side</option>
                        </select>
                    </div>
                </div>

                <div style="display:grid; grid-template-columns: 1fr 1fr 1fr; gap:12px; margin-bottom:12px;">
                    <div>
                        <label style="font-size:12px; font-weight:600; color:#334155;">Headline Prefix</label>
                        <input type="text" value="${item.headline_prefix || ''}" onchange="initiativesData[${index}].headline_prefix = this.value" placeholder="e.g. Accessible Care via" style="width:100%; padding:6px 10px; border:1px solid #cbd5e1; border-radius:4px;">
                    </div>
                    <div>
                        <label style="font-size:12px; font-weight:600; color:#334155;">Highlight Word</label>
                        <input type="text" value="${item.highlight || ''}" onchange="initiativesData[${index}].highlight = this.value" placeholder="e.g. INCLEN Clinics" style="width:100%; padding:6px 10px; border:1px solid #cbd5e1; border-radius:4px;">
                    </div>
                    <div>
                        <label style="font-size:12px; font-weight:600; color:#334155;">Highlight Color</label>
                        <select onchange="initiativesData[${index}].highlight_color = this.value" style="width:100%; padding:6px 10px; border:1px solid #cbd5e1; border-radius:4px;">
                            <option value="blue" ${item.highlight_color === 'blue' ? 'selected' : ''}>Blue</option>
                            <option value="amber" ${item.highlight_color === 'amber' ? 'selected' : ''}>Amber / Orange</option>
                        </select>
                    </div>
                </div>

                <div style="margin-bottom:12px;">
                    <label style="font-size:12px; font-weight:600; color:#334155;">Card Description</label>
                    <textarea rows="2" onchange="initiativesData[${index}].description = this.value" style="width:100%; padding:6px 10px; border:1px solid #cbd5e1; border-radius:4px;">${item.description || ''}</textarea>
                </div>

                <div style="display:grid; grid-template-columns: 1.5fr 1fr; gap:12px; margin-bottom:12px;">
                    <div>
                        <label style="font-size:12px; font-weight:600; color:#334155;">Image URL</label>
                        <input type="text" value="${item.image_url || ''}" onchange="initiativesData[${index}].image_url = this.value" placeholder="/images/community/1.png" style="width:100%; padding:6px 10px; border:1px solid #cbd5e1; border-radius:4px;">
                    </div>
                    <div>
                        <label style="font-size:12px; font-weight:600; color:#334155;">Image Alt Text</label>
                        <input type="text" value="${item.image_alt || ''}" onchange="initiativesData[${index}].image_alt = this.value" placeholder="INCLEN Clinics" style="width:100%; padding:6px 10px; border:1px solid #cbd5e1; border-radius:4px;">
                    </div>
                </div>

                <!-- ACCENT CARD DETAILS -->
                <div style="background:#ffffff; border:1px solid #e2e8f0; border-radius:8px; padding:12px;">
                    <strong style="display:block; font-size:12px; color:#475569; margin-bottom:8px;">🎨 Accent Element Details</strong>
                    <div style="display:grid; grid-template-columns: 1fr 1fr 1.5fr; gap:10px;">
                        <div>
                            <label style="font-size:11px; font-weight:600; color:#64748b;">Accent Title / Stat</label>
                            <input type="text" value="${item.accent_title || ''}" onchange="initiativesData[${index}].accent_title = this.value" placeholder="e.g. Global" style="width:100%; padding:5px 8px; border:1px solid #cbd5e1; border-radius:4px; font-size:12px;">
                        </div>
                        <div>
                            <label style="font-size:11px; font-weight:600; color:#64748b;">Accent Subtitle</label>
                            <input type="text" value="${item.accent_subtitle || ''}" onchange="initiativesData[${index}].accent_subtitle = this.value" placeholder="e.g. Standard Care" style="width:100%; padding:5px 8px; border:1px solid #cbd5e1; border-radius:4px; font-size:12px;">
                        </div>
                        <div>
                            <label style="font-size:11px; font-weight:600; color:#64748b;">Accent Text / Note</label>
                            <input type="text" value="${item.accent_text || ''}" onchange="initiativesData[${index}].accent_text = this.value" placeholder="e.g. Providing essential healthcare" style="width:100%; padding:5px 8px; border:1px solid #cbd5e1; border-radius:4px; font-size:12px;">
                        </div>
                    </div>
                </div>
            `;
            container.appendChild(card);
        });
    }

    function addInitiativeCard() {
        initiativesData.push({
            id: 'init-' + Date.now(),
            tag: 'New Outreach',
            tag_style: 'blue',
            headline_prefix: 'Empowering Communities:',
            highlight: 'New Initiative',
            highlight_color: 'blue',
            description: 'Description of the new community initiative.',
            image_url: '/images/community/1.png',
            image_alt: 'New Initiative',
            image_left: false,
            accent_type: 'stats',
            accent_title: '100+',
            accent_subtitle: 'Sites Covered',
            accent_text: 'Active community engagement programs.'
        });
        renderInitiatives();
    }

    function removeInitiative(index) {
        if (confirm('Are you sure you want to remove this card?')) {
            initiativesData.splice(index, 1);
            renderInitiatives();
        }
    }

    function saveCaData() {
        const notice = document.getElementById('ca-notice');
        notice.style.display = 'block';
        notice.style.background = '#e0f2fe';
        notice.style.color = '#0369a1';
        notice.innerText = '⏳ Saving Community Activities details...';

        const payload = {
            hero_badge: document.getElementById('ca_hero_badge').value,
            hero_heading: document.getElementById('ca_hero_heading').value,
            hero_description: document.getElementById('ca_hero_description').value,
            hero_subdescription: document.getElementById('ca_hero_subdescription').value,
            intro_heading: document.getElementById('ca_intro_heading').value,
            intro_description: document.getElementById('ca_intro_description').value,
            initiatives: initiativesData,
        };

        fetch('<?php echo esc_url_raw(rest_url('community-activities/v1/save')); ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': '<?php echo wp_create_nonce('wp_rest'); ?>'
            },
            body: JSON.stringify(payload)
        })
        .then(res => res.json())
        .then(res => {
            if (res.status === 'success') {
                notice.style.background = '#dcfce7';
                notice.style.color = '#15803d';
                notice.innerText = '✅ Saved successfully! All changes are live.';
            } else {
                notice.style.background = '#fee2e2';
                notice.style.color = '#b91c1c';
                notice.innerText = '❌ Error saving: ' + (res.message || 'Unknown error');
            }
            setTimeout(() => { notice.style.display = 'none'; }, 4000);
        })
        .catch(err => {
            notice.style.background = '#fee2e2';
            notice.style.color = '#b91c1c';
            notice.innerText = '❌ Request failed: ' + err.message;
        });
    }

    document.getElementById('save-ca-btn-top').addEventListener('click', saveCaData);

    // Initial render
    renderInitiatives();
    </script>
    <?php
}
