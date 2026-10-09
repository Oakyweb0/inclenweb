<?php
/**
 * Setup and Update Database Tables for INCLEN Managers
 */

function custom_setup_database_tables() {
    global $wpdb;
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    $charset_collate = $wpdb->get_charset_collate();

    // 1. INCLEN Tools Table
    $table_inclen = $wpdb->prefix . 'inclen_tools';
    $sql_inclen = "CREATE TABLE $table_inclen (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        project_name varchar(255) NOT NULL,
        tool_name varchar(255) DEFAULT '',
        year varchar(50) DEFAULT '',
        modules text DEFAULT '[]',
        cover_image varchar(255) DEFAULT '',
        pdfs text DEFAULT '[]',
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    dbDelta($sql_inclen);

    // 2. Research Projects (Original)
    $table_research = $wpdb->prefix . 'research_projects';
    $sql_research = "CREATE TABLE $table_research (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        title varchar(255) NOT NULL,
        year varchar(50) DEFAULT '',
        principal_investigator varchar(255) DEFAULT '',
        funder varchar(255) DEFAULT '',
        study_sites varchar(255) DEFAULT '',
        image_url varchar(255) DEFAULT '',
        pdf_url varchar(255) DEFAULT '',
        summary text DEFAULT '',
        sort_order int(11) DEFAULT 0,
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    dbDelta($sql_research);

    // Make sure sort_order column exists
    $row = $wpdb->get_results("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '$table_research' AND COLUMN_NAME = 'sort_order'");
    if(empty($row)) {
        $wpdb->query("ALTER TABLE $table_research ADD COLUMN sort_order int(11) DEFAULT 0");
    }

    // 3. Completed Projects (New)
    $table_completed = $wpdb->prefix . 'completed_projects';
    $sql_completed = "CREATE TABLE $table_completed (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        title varchar(255) NOT NULL,
        year varchar(50) DEFAULT '',
        principal_investigator varchar(255) DEFAULT '',
        co_investigator varchar(255) DEFAULT '',
        funder varchar(255) DEFAULT '',
        study_sites varchar(255) DEFAULT '',
        image_url varchar(255) DEFAULT '',
        pdf_url varchar(255) DEFAULT '',
        summary text DEFAULT '',
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    dbDelta($sql_completed);

    // 4. Research Priority Settings (New)
    $table_priority = $wpdb->prefix . 'priority_settings';
    $sql_priority = "CREATE TABLE $table_priority (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        name varchar(255) NOT NULL,
        category varchar(100) DEFAULT '',
        duration varchar(100) DEFAULT '',
        file_url varchar(255) DEFAULT '',
        file_size varchar(50) DEFAULT '',
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    dbDelta($sql_priority);

    // 5. Document Library (Original)
    $table_docs = $wpdb->prefix . 'document_library';
    $sql_docs = "CREATE TABLE $table_docs (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        name varchar(255) NOT NULL,
        category varchar(100) DEFAULT '',
        duration varchar(100) DEFAULT '',
        file_url varchar(255) DEFAULT '',
        file_size varchar(50) DEFAULT '',
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    dbDelta($sql_docs);

    // 6. Download Requests Table
    $table_requests = $wpdb->prefix . 'download_requests';
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_requests'") != $table_requests) {
        $sql_requests = "CREATE TABLE $table_requests (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            full_name varchar(255) NOT NULL,
            email varchar(100) NOT NULL,
            location varchar(255) DEFAULT '',
            speciality varchar(255) DEFAULT '',
            project_title varchar(255) DEFAULT '',
            pdf_url varchar(255) DEFAULT '',
            timestamp datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";
        dbDelta($sql_requests);
    }

    // 7. Partners Table
    $table_partners = $wpdb->prefix . 'partners';
    $sql_partners = "CREATE TABLE $table_partners (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        company_name varchar(255) NOT NULL,
        company_logo varchar(255) DEFAULT '',
        about text DEFAULT '',
        description longtext DEFAULT '',
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    dbDelta($sql_partners);

    // 8. Industry Partnerships Table
    $table_industry = $wpdb->prefix . 'industry_partnerships';
    $sql_industry = "CREATE TABLE $table_industry (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        company_name varchar(255) NOT NULL,
        company_logo varchar(255) DEFAULT '',
        para longtext DEFAULT '',
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    dbDelta($sql_industry);

    // 9. Research Partnerships Table
    $table_research_part = $wpdb->prefix . 'research_partnerships';
    $sql_research_part = "CREATE TABLE $table_research_part (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        company_name varchar(255) NOT NULL,
        company_logo varchar(255) DEFAULT '',
        para longtext DEFAULT '',
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    dbDelta($sql_research_part);

    // 10. Annual Reports Table
    $table_annual_reports = $wpdb->prefix . 'annual_reports';
    $sql_annual_reports = "CREATE TABLE $table_annual_reports (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        cover_image varchar(255) DEFAULT '',
        heading varchar(255) NOT NULL,
        highlights longtext DEFAULT '',
        pdf_url varchar(255) DEFAULT '',
        pdf_size varchar(50) DEFAULT '',
        year varchar(50) DEFAULT '',
        is_featured tinyint(1) DEFAULT 0,
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    dbDelta($sql_annual_reports);

    // 11. Newsletters Table
    $table_newsletters = $wpdb->prefix . 'newsletters';
    $sql_newsletters = "CREATE TABLE $table_newsletters (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        cover_image varchar(255) DEFAULT '',
        heading varchar(255) NOT NULL,
        highlights longtext DEFAULT '',
        pdf_url varchar(255) DEFAULT '',
        pdf_size varchar(50) DEFAULT '',
        year varchar(50) DEFAULT '',
        is_featured tinyint(1) DEFAULT 0,
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    dbDelta($sql_newsletters);

    // 12. Device Products Table
    $table_device_products = $wpdb->prefix . 'device_products';
    $sql_device_products = "CREATE TABLE $table_device_products (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        cover_image varchar(255) DEFAULT '',
        tag varchar(255) DEFAULT '',
        heading varchar(255) NOT NULL,
        short_description text DEFAULT '',
        para longtext DEFAULT '',
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    dbDelta($sql_device_products);

    // 13. FCRA & Registration Table
    $table_fcra = $wpdb->prefix . 'fcra_registration';
    $sql_fcra = "CREATE TABLE $table_fcra (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        tag varchar(255) DEFAULT '',
        heading varchar(255) NOT NULL,
        declared_year varchar(50) DEFAULT '',
        pdf_url varchar(255) DEFAULT '',
        pdf_size varchar(50) DEFAULT '',
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    dbDelta($sql_fcra);

    // 14. Training Materials Table
    $table_training = $wpdb->prefix . 'training_materials';
    $sql_training = "CREATE TABLE $table_training (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        heading varchar(255) NOT NULL,
        tag varchar(255) DEFAULT '',
        module varchar(255) DEFAULT '',
        level varchar(255) DEFAULT '',
        duration varchar(255) DEFAULT '',
        pdf_url varchar(255) DEFAULT '',
        pdf_size varchar(50) DEFAULT '',
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    dbDelta($sql_training);

    // 15. Data Repository Table
    $table_data_repo = $wpdb->prefix . 'data_repository';
    $sql_data_repo = "CREATE TABLE $table_data_repo (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        category varchar(255) DEFAULT '',
        title varchar(255) NOT NULL,
        pdf_url varchar(255) DEFAULT '',
        pdf_size varchar(50) DEFAULT '',
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    dbDelta($sql_data_repo);

    // 16. Home Page Hero Section Table
    $table_home_hero = $wpdb->prefix . 'home_hero';
    $sql_home_hero = "CREATE TABLE $table_home_hero (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        images longtext,
        heading varchar(255) NOT NULL,
        paragraph longtext DEFAULT '',
        latest_updates longtext,
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    dbDelta($sql_home_hero);

    // 17. Home Page About Section Table
    $table_home_about = $wpdb->prefix . 'home_about';
    $sql_home_about = "CREATE TABLE $table_home_about (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        tag_text varchar(255) DEFAULT '',
        heading varchar(255) NOT NULL,
        description longtext DEFAULT '',
        main_image varchar(255) DEFAULT '',
        quote_text varchar(255) DEFAULT '',
        quote_author varchar(255) DEFAULT '',
        stat_number varchar(255) DEFAULT '',
        stat_text varchar(255) DEFAULT '',
        feature_blocks longtext,
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    dbDelta($sql_home_about);

    // 18. Home Page Presence Section Table
    $table_home_presence = $wpdb->prefix . 'home_presence';
    $sql_home_presence = "CREATE TABLE $table_home_presence (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        heading varchar(255) NOT NULL,
        subheading text DEFAULT '',
        stats_data longtext,
        countries_data longtext,
        institutions_data longtext,
        networks_data longtext,
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    dbDelta($sql_home_presence);

    // 19. Home Page Collaborators Section Table
    $table_home_collaborators = $wpdb->prefix . 'home_collaborators';
    $sql_home_collaborators = "CREATE TABLE $table_home_collaborators (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        heading varchar(255) NOT NULL,
        subheading text DEFAULT '',
        logos longtext,
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    dbDelta($sql_home_collaborators);

    // Seed Home Collaborators if empty
    $count_collab = $wpdb->get_var("SELECT COUNT(*) FROM $table_home_collaborators");
    if (!$count_collab || $count_collab == 0) {
        $wpdb->insert($table_home_collaborators, [
            'heading'    => 'Strategic Collaborators',
            'subheading' => 'Empowering global healthcare through multi-disciplinary research and high-impact partnerships.',
            'logos'      => wp_json_encode([
                '/images/collabortor_logo/who.webp',
                '/images/collabortor_logo/icmr_logo_new.webp',
                '/images/collabortor_logo/phfi.webp',
                '/images/collabortor_logo/bill-melinda-gates-foundation-logo.webp',
                '/images/collabortor_logo/World_Bank-Logo.wine.webp',
                '/images/collabortor_logo/UNICEF-Logo.wine.webp'
            ])
        ]);
    }

    // 20. PDF Download Leads Table
    $table_download_leads = $wpdb->prefix . 'download_leads';
    $sql_download_leads = "CREATE TABLE $table_download_leads (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        full_name varchar(255) NOT NULL,
        email varchar(255) NOT NULL,
        location varchar(255) NOT NULL,
        speciality varchar(255) NOT NULL,
        pdf_url varchar(1000) NOT NULL,
        source_section varchar(255) DEFAULT '',
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    dbDelta($sql_download_leads);

    // 21. Home Page Impact Statistics Table
    $table_home_impact = $wpdb->prefix . 'home_impact';
    $sql_home_impact = "CREATE TABLE $table_home_impact (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        stats longtext,
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    dbDelta($sql_home_impact);

    // 22. Home Page Key Research Areas Table
    $table_home_research_areas = $wpdb->prefix . 'home_research_areas';
    $sql_home_research_areas = "CREATE TABLE $table_home_research_areas (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        areas longtext,
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    dbDelta($sql_home_research_areas);

    // 23. About Page Who We Are Table
    $table_about_who_we_are = $wpdb->prefix . 'about_who_we_are';
    $sql_about_who_we_are = "CREATE TABLE $table_about_who_we_are (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        tag_text varchar(255) DEFAULT '',
        heading varchar(255) DEFAULT '',
        description longtext,
        image_url varchar(1000) DEFAULT '',
        quote_text text,
        quote_subtext varchar(255) DEFAULT '',
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    dbDelta($sql_about_who_we_are);

    // 24. About Page Our Journey Table
    $table_about_our_journey = $wpdb->prefix . 'about_our_journey';
    $sql_about_our_journey = "CREATE TABLE $table_about_our_journey (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        tag_text varchar(255) DEFAULT '',
        heading varchar(255) DEFAULT '',
        subheading text DEFAULT '',
        milestones longtext DEFAULT '',
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    dbDelta($sql_about_our_journey);

    // 25. Website Navigation Menu Visibility Settings
    $table_site_navigation = $wpdb->prefix . 'site_navigation';
    $sql_site_navigation = "CREATE TABLE $table_site_navigation (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        menu_structure longtext NOT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    dbDelta($sql_site_navigation);
    // 26. About Page Mission Table
    $table_about_mission = $wpdb->prefix . 'about_mission';
    $sql_about_mission = "CREATE TABLE $table_about_mission (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        tag_text varchar(255) DEFAULT '',
        heading varchar(255) DEFAULT '',
        description text DEFAULT '',
        cards longtext DEFAULT '',
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    dbDelta($sql_about_mission);

    // 27. Blogs Table
    $table_blogs = $wpdb->prefix . 'blogs';
    $sql_blogs = "CREATE TABLE $table_blogs (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        title varchar(255) NOT NULL,
        slug varchar(255) DEFAULT '',
        content longtext DEFAULT '',
        author varchar(255) DEFAULT '',
        image varchar(255) DEFAULT '',
        banner_image varchar(255) DEFAULT '',
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    dbDelta($sql_blogs);

    // 28. News Table
    $table_news = $wpdb->prefix . 'news';
    $sql_news = "CREATE TABLE $table_news (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        title varchar(255) NOT NULL,
        slug varchar(255) DEFAULT '',
        content longtext DEFAULT '',
        author varchar(255) DEFAULT '',
        image varchar(255) DEFAULT '',
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    dbDelta($sql_news);

    // 29. Fellowships Table
    $table_fellowships = $wpdb->prefix . 'fellowships';
    $sql_fellowships = "CREATE TABLE $table_fellowships (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        title varchar(255) NOT NULL,
        short_description text DEFAULT '',
        subtitle varchar(255) DEFAULT '',
        description longtext DEFAULT '',
        duration varchar(100) DEFAULT '',
        who_apply varchar(255) DEFAULT '',
        image varchar(500) DEFAULT '',
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    dbDelta($sql_fellowships);

    // 30. Academic Association Table
    $table_academic_assoc = $wpdb->prefix . 'academic_association';
    $sql_academic_assoc = "CREATE TABLE $table_academic_assoc (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        program_type varchar(100) NOT NULL DEFAULT 'phd',
        title varchar(255) NOT NULL,
        tag varchar(255) DEFAULT '',
        description longtext DEFAULT '',
        eligibility longtext DEFAULT '',
        duration varchar(100) DEFAULT '',
        pdf_url varchar(500) DEFAULT '',
        image_url varchar(500) DEFAULT '',
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    dbDelta($sql_academic_assoc);

    // 31. Partner Institutes Table
    $table_partner_inst = $wpdb->prefix . 'partner_institutes';
    $sql_partner_inst = "CREATE TABLE $table_partner_inst (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        institute_name varchar(255) NOT NULL,
        institute_logo varchar(500) DEFAULT '',
        website_url varchar(500) DEFAULT '',
        sort_order int(11) DEFAULT 0,
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    dbDelta($sql_partner_inst);

    // 32. Contact & Site Information Table
    $table_contact = $wpdb->prefix . 'contact_info';
    $sql_contact = "CREATE TABLE $table_contact (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        office_title varchar(255) DEFAULT 'Executive Office',
        address_line1 text DEFAULT '',
        address_line2 text DEFAULT '',
        phone varchar(100) DEFAULT '',
        phone_footer varchar(100) DEFAULT '',
        email varchar(150) DEFAULT '',
        map_query text DEFAULT '',
        map_embed_url text DEFAULT '',
        hero_title varchar(255) DEFAULT '',
        hero_subtitle text DEFAULT '',
        research_title varchar(255) DEFAULT '',
        research_description text DEFAULT '',
        stat1_value varchar(100) DEFAULT '',
        stat1_label varchar(255) DEFAULT '',
        stat2_value varchar(100) DEFAULT '',
        stat2_label varchar(255) DEFAULT '',
        stat3_value varchar(100) DEFAULT '',
        stat3_label varchar(255) DEFAULT '',
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    dbDelta($sql_contact);

    // Seed default if empty
    $contact_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_contact");
    if (!$contact_count || $contact_count == 0) {
        $wpdb->insert($table_contact, [
            'office_title'         => 'Executive Office',
            'address_line1'        => 'A-157–158, 3rd Floor, DDA Shed, Okhla Phase-II',
            'address_line2'        => 'New Delhi – 110020',
            'phone'                => '+91-11-47730000',
            'phone_footer'         => '+91 11 47730000 - 99',
            'email'                => 'ieodelhi@inclentrust.org',
            'map_query'            => 'A-157 DDA Shed, Okhla Phase-II, New Delhi 110020',
            'map_embed_url'        => 'https://maps.google.com/maps?q=A-157%20DDA%20Shed%2C%20Okhla%20Phase-II%2C%20New%20Delhi%20110020&t=&z=16&ie=UTF8&iwloc=&output=embed',
            'hero_title'           => "Let's start a Conversation",
            'hero_subtitle'        => "Connect with the INCLEN Executive Office. Whether it's data access, institutional partnership, or global research inquiries.",
            'research_title'       => 'A Global Research Infrastructure',
            'research_description' => 'With 89 Clinical Epidemiology Units across 34 countries, our network provides a unique platform for high-impact multicentric studies.',
            'stat1_value'          => '34',
            'stat1_label'          => 'Countries Connected',
            'stat2_value'          => '89',
            'stat2_label'          => 'Partner Institutes',
            'stat3_value'          => '400k+',
            'stat3_label'          => 'Population Monitored'
        ]);
    }

    // 30. Impact Summary Table
    $table_impact_summary = $wpdb->prefix . 'impact_summary';
    $sql_impact_summary = "CREATE TABLE $table_impact_summary (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        hero_title varchar(255) DEFAULT 'Our Impact',
        hero_description longtext DEFAULT '',
        key_areas_title varchar(255) DEFAULT 'Key Impact Areas',
        key_areas longtext DEFAULT '[]',
        approach_tag varchar(255) DEFAULT 'Our Approach',
        approach_title varchar(255) DEFAULT 'Bridging Evidence & Action',
        approach_description longtext DEFAULT '',
        pathway1_badge varchar(50) DEFAULT '01',
        pathway1_title varchar(255) DEFAULT 'Research to Policy & Program',
        pathway1_description longtext DEFAULT '',
        pathway1_items longtext DEFAULT '[]',
        pathway2_badge varchar(50) DEFAULT '02',
        pathway2_title varchar(255) DEFAULT 'Research to Practice',
        pathway2_description longtext DEFAULT '',
        pathway2_items longtext DEFAULT '[]',
        cta_title varchar(255) DEFAULT 'Join Us in Making a Difference',
        cta_description longtext DEFAULT '',
        cta_btn1_text varchar(255) DEFAULT 'Partner With Us',
        cta_btn1_link varchar(255) DEFAULT '/contact',
        cta_btn2_text varchar(255) DEFAULT 'Explore Our Work',
        cta_btn2_link varchar(255) DEFAULT '/our-work',
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    dbDelta($sql_impact_summary);

    // Seed default if empty
    $impact_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_impact_summary");
    if (!$impact_count || $impact_count == 0) {
        $default_key_areas = [
            [
                'id' => '1',
                'title' => 'Global Research Network',
                'description' => 'Operates across 34 countries with 89 academic institutions and over 1,800 members, creating a massive platform for interdisciplinary public health research.',
                'icon_color' => 'brand',
                'bg_color' => 'brand',
                'icon_name' => 'globe'
            ],
            [
                'id' => '2',
                'title' => 'SOMAARTH Surveillance',
                'description' => 'Established one of the world\'s largest surveillance sites in Palwal, Haryana, covering 51 villages and a population exceeding 200,000 to monitor environmental and health transitions.',
                'icon_color' => 'blue',
                'bg_color' => 'green',
                'icon_name' => 'beaker'
            ],
            [
                'id' => '3',
                'title' => 'Diagnostic Innovation',
                'description' => 'Developed the widely used INCLEN diagnostic tool for neurodevelopmental disorders, validated and modified by AIIMS for nationwide child health assessments.',
                'icon_color' => 'blue',
                'bg_color' => 'blue',
                'icon_name' => 'flask'
            ],
            [
                'id' => '4',
                'title' => 'Policy & Program Evaluation',
                'description' => 'Conducted critical evaluations of India\'s Universal Immunization Program (UIP) and implemented research on managing neonatal sepsis and pneumonia to guide national health policy.',
                'icon_color' => 'green',
                'bg_color' => 'green',
                'icon_name' => 'document'
            ],
            [
                'id' => '5',
                'title' => 'National Priority Setting',
                'description' => 'Led a massive \'crowd-sourced\' initiative involving over 2,000 experts and 250+ institutions to define public health research priorities for the Government of India.',
                'icon_color' => 'purple',
                'bg_color' => 'purple',
                'icon_name' => 'clipboard'
            ],
            [
                'id' => '6',
                'title' => 'Vaccine & COVID-19',
                'description' => 'Actively managed clinical trials for COVID-19 vaccines (including heterologous prime-boost combinations) and sero-surveillance for Dengue and Chikungunya.',
                'icon_color' => 'red',
                'bg_color' => 'red',
                'icon_name' => 'shield'
            ]
        ];

        $default_pathway1_items = [
            [
                'title' => 'National Research Priority Setting (RPS)',
                'desc' => 'Collaborated with the Indian Council of Medical Research (ICMR) to lead a nationwide crowd-sourced exercise involving 2,000+ experts to define research priorities for maternal and child health through 2025.'
            ],
            [
                'title' => 'Immunization Policy',
                'desc' => 'Evaluated the Universal Immunization Program (UIP) and provided the evidence base for the rollout of the Rotavirus Vaccine and the Intensified Mission Indradhanush (IMI).'
            ],
            [
                'title' => 'National Health Programs',
                'desc' => 'Actively assists the government in adopting strategies for the National Program for Prevention & Control of Cancer, Diabetes, Cardiovascular Diseases and Stroke (NPCDCD).'
            ],
            [
                'title' => 'Childhood Pneumonia & Sepsis',
                'desc' => 'Generated evidence to assist national governments in adopting context-sensitive strategies to reduce under-five mortality from pneumonia.'
            ]
        ];

        $default_pathway2_items = [
            [
                'title' => 'INCLEN Diagnostic Tools (INDT)',
                'desc' => 'Developed validated diagnostic instruments for neurodevelopmental disorders (e.g., ADHD, Neuromotor Impairments) that primary care physicians can use with minimal training.'
            ],
            [
                'title' => 'Community Interventions',
                'desc' => 'Translates research into practice at the SOMAARTH site by engaging ASHA workers and Panchayati officers in dialogues about high-risk pregnancies and heart attack symptoms.'
            ],
            [
                'title' => 'Knowledge Translation Units',
                'desc' => 'Established the International Institute of Global Health (IIGH) which houses a \'Policy Unit\' dedicated to turning network-generated evidence into clinical care tools and practice guidelines.'
            ],
            [
                'title' => 'Diagnostic Validation',
                'desc' => 'Partnered with AIIMS to modify and validate INCLEN tools for a wider age range (1 month to 18 years), ensuring they are practical for diverse clinical settings.'
            ]
        ];

        $wpdb->insert($table_impact_summary, [
            'hero_title' => 'Our Impact',
            'hero_description' => "The INCLEN Trust measures its impact through large-scale research surveillance, policy translation, and the development of diagnostic tools that influence national health programs.\n\nKey Impact Areas: Global Research Network, SOMAARTH Surveillance Site, Diagnostic Innovation, Policy & Program Evaluation, National Research Priority Setting, Vaccine Research & COVID-19 Response.",
            'key_areas_title' => 'Key Impact Areas',
            'key_areas' => json_encode($default_key_areas),
            'approach_tag' => 'Our Approach',
            'approach_title' => 'Bridging Evidence & Action',
            'approach_description' => 'INCLEN Trust bridges the gap between scientific evidence and public health action through two primary pathways: translating research into national policies and programs, and converting findings into clinical practice.',
            'pathway1_badge' => '01',
            'pathway1_title' => 'Research to Policy & Program',
            'pathway1_description' => 'INCLEN acts as a strategic technical partner to the Government of India, ensuring that data drives national health strategies.',
            'pathway1_items' => json_encode($default_pathway1_items),
            'pathway2_badge' => '02',
            'pathway2_title' => 'Research to Practice',
            'pathway2_description' => 'The organization develops "actionable tools" that empower frontline health workers and primary care physicians to apply complex research in everyday settings.',
            'pathway2_items' => json_encode($default_pathway2_items),
            'cta_title' => 'Join Us in Making a Difference',
            'cta_description' => 'Partner with INCLEN to drive global health innovation and policy change. Together, we can build a healthier future.',
            'cta_btn1_text' => 'Partner With Us',
            'cta_btn1_link' => '/contact',
            'cta_btn2_text' => 'Explore Our Work',
            'cta_btn2_link' => '/our-work'
        ]);
    }

    // 31. Capacity Building Table
    $table_capacity = $wpdb->prefix . 'capacity_building';
    $sql_capacity = "CREATE TABLE $table_capacity (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        hero_badge varchar(255) DEFAULT 'Est. 2012 · Global Reach',
        hero_title varchar(255) DEFAULT 'Capacity',
        hero_highlight varchar(255) DEFAULT 'Building',
        hero_description longtext DEFAULT '',
        hero_btn_text varchar(255) DEFAULT 'Explore Programs',
        hero_btn_link varchar(255) DEFAULT '#programs',
        focus_tag varchar(255) DEFAULT 'Core Training Programs',
        focus_heading varchar(255) DEFAULT 'Capacity Building Focus',
        focus_description longtext DEFAULT '',
        focus_topics longtext DEFAULT '[]',
        alumni_tag varchar(255) DEFAULT 'Social Proof',
        alumni_heading varchar(255) DEFAULT 'Alumni & Past Participants',
        alumni_description longtext DEFAULT '',
        alumni_items longtext DEFAULT '[]',
        cta_tag varchar(255) DEFAULT 'Advance Your Health Career',
        cta_heading varchar(255) DEFAULT 'Get Involved Today',
        cta_description longtext DEFAULT '',
        cta_btn1_text varchar(255) DEFAULT 'Apply to LAMP ↗',
        cta_btn1_link varchar(255) DEFAULT '#',
        cta_btn2_text varchar(255) DEFAULT 'Join as Institution',
        cta_btn2_link varchar(255) DEFAULT '#',
        cta_btn3_text varchar(255) DEFAULT 'Explore Internships',
        cta_btn3_link varchar(255) DEFAULT '#',
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    dbDelta($sql_capacity);

    $default_focus_topics = [
        [
            'key' => 'lamp',
            'title' => 'Leadership & Management Program (LAMP)',
            'tag' => 'Leadership & Strategy',
            'overview' => 'INCLEN\'s flagship leadership development initiative designed to equip mid-career healthcare professionals, project managers, and researchers with strategic leadership, communication, and management capabilities to drive health policy changes.',
            'curriculum' => [
                'Leadership concepts & styles',
                'Team management & conflict resolution',
                'Strategic planning & management',
                'Research proposal & grant writing',
                'Financial management & budgeting',
                'Evidence-informed policymaking'
            ],
            'audience' => 'Mid-career researchers, clinical fellows, project managers, and health administrators.',
            'duration' => '15-day intensive residential program'
        ],
        [
            'key' => 'public-health',
            'title' => 'Public Health Research',
            'tag' => 'Epidemiology & Design',
            'overview' => 'Fundamental and advanced training in public health research methodologies, clinical epidemiology, and study designs to produce high-impact peer-reviewed literature and evidence-based interventions.',
            'curriculum' => [
                'Clinical epidemiology principles',
                'Observational & experimental study designs',
                'Ethical guidelines & IRB approval processes',
                'Literature reviews & meta-analyses',
                'Scientific writing & manuscript preparation',
                'Grant proposal development'
            ],
            'audience' => 'Doctoral candidates, academic health faculty, clinical investigators, and medical students.',
            'duration' => 'Modular formats (online & offline sessions)'
        ],
        [
            'key' => 'data-science',
            'title' => 'Data Science & Biostatistics',
            'tag' => 'Analytics & Systems',
            'overview' => 'End-to-end training in clinical data management, database design, biostatistical analysis, and data modeling using modern computing environments and analytical programming.',
            'curriculum' => [
                'Biostatistics fundamentals & hypothesis testing',
                'Clinical data quality assurance & entry control',
                'Statistical programming (R, Stata, Python)',
                'Database design & data schema planning',
                'Large-scale health registry analysis',
                'Data visualization & reporting techniques'
            ],
            'audience' => 'Biostatisticians, data managers, registry handlers, and research analysts.',
            'duration' => 'Self-paced modules with hands-on labs'
        ],
        [
            'key' => 'gis',
            'title' => 'GIS Training & Health Mapping',
            'tag' => 'Spatial Analysis',
            'overview' => 'Training in geographic information systems (GIS) applied to public health, enabling researchers to map disease distributions, model environmental risks, and analyze spatial access to healthcare resources.',
            'curriculum' => [
                'GIS fundamentals & mapping software',
                'Geocoding & spatial database management',
                'Disease mapping & spatial hotspot detection',
                'Environmental health exposure modeling',
                'Spatial accessibility analysis for health sites',
                'Geospatial statistics & spatial clustering'
            ],
            'audience' => 'Epidemiologists, environmental health researchers, and urban health planners.',
            'duration' => '5-day intensive practical workshop'
        ],
        [
            'key' => 'implementation',
            'title' => 'Implementation Research',
            'tag' => 'Health Systems Translation',
            'overview' => 'Training focused on translating clinical trial efficacy into real-world effectiveness within diverse health systems, understanding barriers to scale, and optimizing operational health programs.',
            'curriculum' => [
                'Implementation research frameworks',
                'Barrier & facilitator identification (CFIR)',
                'Hybrid design clinical trials',
                'Qualitative & mixed-methods integration',
                'Health system integration & scalability',
                'Programmatic monitoring & evaluation'
            ],
            'audience' => 'Health program managers, operational researchers, and policy advisors.',
            'duration' => 'Interactive seminar series & case workshops'
        ],
        [
            'key' => 'onground',
            'title' => 'Research Onground & Field Operations',
            'tag' => 'Field Logistics & Cohorts',
            'overview' => 'Practical operational training for executing large-scale community surveys, managing demographic and environmental surveillance cohorts, and coordinating field logistics ethically and efficiently.',
            'curriculum' => [
                'Field operations management & supervisor logistics',
                'Demographic surveillance site operations',
                'Household survey sampling & questionnaire execution',
                'Community stakeholder engagement & consent systems',
                'Ethical practices in remote & rural field sites',
                'Real-time electronic data capture & field QA'
            ],
            'audience' => 'Field supervisors, research coordinators, cohort managers, and surveyors.',
            'duration' => 'Hands-on field attachments & simulation drills'
        ]
    ];

    $default_alumni = [
        ['name' => 'Lorem Ipsum 1', 'institution' => 'Lorem Ipsum Institution', 'location' => 'Lorem Ipsum Location', 'batch' => '2015', 'theme' => 'Lorem Ipsum Theme Details'],
        ['name' => 'Lorem Ipsum 2', 'institution' => 'Lorem Ipsum Institution', 'location' => 'Lorem Ipsum Location', 'batch' => '2015', 'theme' => 'Lorem Ipsum Theme Details'],
        ['name' => 'Lorem Ipsum 3', 'institution' => 'Lorem Ipsum Institution', 'location' => 'Lorem Ipsum Location', 'batch' => '2014', 'theme' => 'Lorem Ipsum Theme Details'],
        ['name' => 'Lorem Ipsum 4', 'institution' => 'Lorem Ipsum Institution', 'location' => 'Lorem Ipsum Location', 'batch' => '2014', 'theme' => 'Lorem Ipsum Theme Details'],
        ['name' => 'Lorem Ipsum 5', 'institution' => 'Lorem Ipsum Institution', 'location' => 'Lorem Ipsum Location', 'batch' => '2013', 'theme' => 'Lorem Ipsum Theme Details'],
        ['name' => 'Lorem Ipsum 6', 'institution' => 'Lorem Ipsum Institution', 'location' => 'Lorem Ipsum Location', 'batch' => '2013', 'theme' => 'Lorem Ipsum Theme Details']
    ];

    $capacity_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_capacity");
    if (!$capacity_count || $capacity_count == 0) {
        $wpdb->insert($table_capacity, [
            'hero_badge'        => 'Est. 2012 · Global Reach',
            'hero_title'        => 'Capacity',
            'hero_highlight'    => 'Building',
            'hero_description'  => 'Building the next generation of global health leaders through rigorous academic training, clinical epidemiology, and management programs.',
            'hero_btn_text'     => 'Explore Programs',
            'hero_btn_link'     => '#programs',
            'focus_tag'         => 'Core Training Programs',
            'focus_heading'     => 'Capacity Building Focus',
            'focus_description' => 'We offer specialized training tracks designed to cultivate next-generation leaders in global health, research methods, and data systems.',
            'focus_topics'      => json_encode($default_focus_topics),
            'alumni_tag'        => 'Social Proof',
            'alumni_heading'    => 'Alumni & Past Participants',
            'alumni_description'=> 'Meet our past LAMP program participants and batches leading public health operations globally.',
            'alumni_items'      => json_encode($default_alumni),
            'cta_tag'           => 'Advance Your Health Career',
            'cta_heading'       => 'Get Involved Today',
            'cta_description'   => 'Join our global network of Clinical Epidemiology Units, apply to the next cohort of LAMP, or join as an individual member or intern.',
            'cta_btn1_text'     => 'Apply to LAMP ↗',
            'cta_btn1_link'     => '#',
            'cta_btn2_text'     => 'Join as Institution',
            'cta_btn2_link'     => '#',
            'cta_btn3_text'     => 'Explore Internships',
            'cta_btn3_link'     => '#'
        ]);
    } else {
        // Ensure columns exist and are populated if empty
        $existing = $wpdb->get_row("SELECT * FROM $table_capacity LIMIT 1", ARRAY_A);
        $update_data = [];
        if (empty($existing['focus_topics']) || $existing['focus_topics'] === '[]') {
            $update_data['focus_topics'] = json_encode($default_focus_topics);
        }
        if (empty($existing['alumni_items']) || $existing['alumni_items'] === '[]') {
            $update_data['alumni_items'] = json_encode($default_alumni);
        }
        if (!empty($update_data)) {
            $wpdb->update($table_capacity, $update_data, ['id' => $existing['id']]);
        }
    }

    // 32. Engagement & Advocacy Table
    $table_engagement = $wpdb->prefix . 'engagement_advocacy';
    $sql_engagement = "CREATE TABLE $table_engagement (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        hero_badge varchar(255) DEFAULT 'Social Mobilization & Action',
        hero_title varchar(255) DEFAULT 'Engagement &',
        hero_highlight varchar(255) DEFAULT 'Advocacy',
        hero_description longtext DEFAULT '',
        belief_tag varchar(255) DEFAULT 'Our Core Belief',
        belief_heading varchar(255) DEFAULT 'Sustainable health improvements are achieved when we work together.',
        belief_para1 longtext DEFAULT '',
        belief_para2 longtext DEFAULT '',
        belief_para3 longtext DEFAULT '',
        approach_tag varchar(255) DEFAULT 'Methodology',
        approach_heading varchar(255) DEFAULT 'Our Approach',
        approach_description longtext DEFAULT '',
        approach_items longtext DEFAULT '[]',
        pillars_tag varchar(255) DEFAULT 'Strategic Focus',
        pillars_heading varchar(255) DEFAULT 'Strategic Pillars',
        pillars_description longtext DEFAULT '',
        pillars_items longtext DEFAULT '[]',
        case_study_tag varchar(255) DEFAULT 'Real-World Results',
        case_study_heading varchar(255) DEFAULT 'Impact Stories',
        case_study_badge varchar(255) DEFAULT 'Lorem Ipsum Case Study',
        case_study_title varchar(255) DEFAULT 'Lorem Ipsum Dolor Sit Amet Consectetur Adipiscing Elit',
        case_study_description longtext DEFAULT '',
        case_study_image varchar(1000) DEFAULT '',
        resources_tag varchar(255) DEFAULT 'Resources',
        resources_heading varchar(255) DEFAULT 'Publications & Policy Briefs',
        resources_items longtext DEFAULT '[]',
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    dbDelta($sql_engagement);

    $engagement_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_engagement");
    if (!$engagement_count || $engagement_count == 0) {
        $default_approaches = [
            ['title' => 'Active Partners', 'description' => 'Engage communities as active partners throughout the study and implementation lifecycle.'],
            ['title' => 'Stakeholder Collaboration', 'description' => 'Strengthen stakeholder collaboration across agencies, civil societies, and researchers.'],
            ['title' => 'Actionable Evidence', 'description' => 'Generate actionable scientific evidence suitable for direct public health translation.'],
            ['title' => 'Evidence-Informed Policy', 'description' => 'Support evidence-informed policymaking by presenting research directly to government units.'],
            ['title' => 'Leadership Building', 'description' => 'Promote local capacity building, clinical research leadership, and institutional capability.'],
            ['title' => 'Equitable Solutions', 'description' => 'Advocate for equitable and sustainable health solutions targeting marginalized groups.']
        ];

        $default_pillars = [
            ['title' => 'Stakeholder Engagement', 'description' => 'Bringing together clinical researchers, doctors, civil society actors, and public agencies on a shared collaborative platform.', 'icon_name' => 'users'],
            ['title' => 'Community Partnerships', 'description' => 'Co-designing local health interventions and programs with rural demographic study cohorts to ensure cultural relevance.', 'icon_name' => 'globe'],
            ['title' => 'Policy Advocacy', 'description' => 'Presenting scientific data to regional ministries and international health entities to influence long-term policy adjustments.', 'icon_name' => 'document'],
            ['title' => 'Knowledge Translation', 'description' => 'Creating visual summaries, training guides, and simplified policy briefs to translate technical laboratory clinical data for the public.', 'icon_name' => 'book']
        ];

        $default_resources = [
            ['title' => 'Lorem Ipsum Dolor Sit Amet Policy Brief', 'meta' => 'Published: Lorem Ipsum • PDF (000 KB)', 'pdf_url' => '#', 'button_text' => 'Download Brief'],
            ['title' => 'Consectetur Adipiscing Elit Handbook', 'meta' => 'Published: Lorem Ipsum • PDF (0.0 MB)', 'pdf_url' => '#', 'button_text' => 'Download Handbook']
        ];

        $wpdb->insert($table_engagement, [
            'hero_badge'            => 'Social Mobilization & Action',
            'hero_title'            => 'Engagement &',
            'hero_highlight'        => 'Advocacy',
            'hero_description'      => 'Translating robust research into public health action, policy frameworks, and community-led health improvements.',
            'belief_tag'            => 'Our Core Belief',
            'belief_heading'        => 'Sustainable health improvements are achieved when we work together.',
            'belief_para1'          => 'At INCLEN, we believe that sustainable health improvements are achieved when communities, researchers, healthcare providers, and policymakers work together. Our engagement approach promotes meaningful participation of stakeholders throughout the research and implementation cycle, ensuring that solutions are contextually relevant, culturally appropriate, and scalable.',
            'belief_para2'          => 'Through partnerships with communities, academic institutions, civil society organizations, and government agencies, we generate evidence that informs public health action and supports equitable health outcomes.',
            'belief_para3'          => 'Our advocacy efforts focus on translating research evidence into policies, programs, and practices that strengthen health systems and improve the lives of vulnerable populations.',
            'approach_tag'          => 'Methodology',
            'approach_heading'      => 'Our Approach',
            'approach_description'  => 'We deploy participatory, research-led, and policy-focused methodologies to advocate for sustainable health developments.',
            'approach_items'        => json_encode($default_approaches),
            'pillars_tag'           => 'Strategic Focus',
            'pillars_heading'       => 'Strategic Pillars',
            'pillars_description'   => 'Four core areas that drive our public health outreach, translation networks, and advocacy campaigns.',
            'pillars_items'         => json_encode($default_pillars),
            'case_study_tag'        => 'Real-World Results',
            'case_study_heading'    => 'Impact Stories',
            'case_study_badge'      => 'Lorem Ipsum Case Study',
            'case_study_title'      => 'Lorem Ipsum Dolor Sit Amet Consectetur Adipiscing Elit',
            'case_study_description'=> 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.',
            'case_study_image'      => 'https://images.unsplash.com/photo-1509062522246-3755977927d7?auto=format&fit=crop&q=80&w=600',
            'resources_tag'         => 'Resources',
            'resources_heading'     => 'Publications & Policy Briefs',
            'resources_items'       => json_encode($default_resources)
        ]);
    }

    // 33. Community Activities Table
    $table_community = $wpdb->prefix . 'community_activities';
    $sql_community = "CREATE TABLE $table_community (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        hero_badge varchar(255) DEFAULT 'Outreach • Education • Prevention',
        hero_heading varchar(255) DEFAULT 'Community Activities',
        hero_description longtext DEFAULT '',
        hero_subdescription longtext DEFAULT '',
        intro_heading longtext DEFAULT '',
        intro_description longtext DEFAULT '',
        initiatives longtext DEFAULT '[]',
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    dbDelta($sql_community);

    $community_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_community");
    if (!$community_count || $community_count == 0) {
        $default_initiatives = [
            [
                'id'              => 'clinics',
                'tag'             => 'Clinical Outreach',
                'tag_style'       => 'blue',
                'headline_prefix' => 'Accessible Care via',
                'highlight'       => 'INCLEN Clinics',
                'highlight_color' => 'blue',
                'description'     => 'INCLEN operates clinics across multiple research sites such as in Palwal, Bareilly, and Mawphlang, providing accessible healthcare and supporting community-based clinical research.',
                'image_url'       => '/images/community/1.png',
                'image_alt'       => 'INCLEN Clinics',
                'image_left'      => false,
                'accent_type'     => 'stats',
                'accent_title'    => 'Global',
                'accent_subtitle' => 'Standard Care',
                'accent_text'     => 'Providing essential healthcare services at the heart of the community.'
            ],
            [
                'id'              => 'nikshay',
                'tag'             => 'Social Support',
                'tag_style'       => 'amber',
                'headline_prefix' => 'Healing Communities:',
                'highlight'       => 'Nikshay Mitra',
                'highlight_color' => 'amber',
                'description'     => 'INCLEN is a Nikshay Mitra and provides nutritional support to TB patients at its research sites in Palwal, Bareilly, and Mawphlang, contributing to improved treatment adherence and patient well-being.',
                'image_url'       => '/images/community/2.png',
                'image_alt'       => 'Nikshay Mitra',
                'image_left'      => true,
                'accent_type'     => 'dark_card',
                'accent_title'    => 'Nutritional Aid',
                'accent_subtitle' => 'Supporting TB Patients with Vital Food & Medical Adherence',
                'accent_text'     => ''
            ],
            [
                'id'              => 'jansamvad',
                'tag'             => 'Community Dialogue',
                'tag_style'       => 'blue',
                'headline_prefix' => 'Empowering People:',
                'highlight'       => 'Jan Samvad',
                'highlight_color' => 'blue',
                'description'     => 'INCLEN conducts community awareness programs to support the implementation of key national health initiatives such as immunization, RBSK (Rashtriya Bal Swasthya Karyakram), and tuberculosis control, empowering communities with knowledge and access.',
                'image_url'       => '/images/community/3.png',
                'image_alt'       => 'Jan Samvad',
                'image_left'      => false,
                'accent_type'     => 'grid',
                'accent_title'    => 'Knowledge',
                'accent_subtitle' => 'Impact Goal',
                'accent_text'     => 'National Focus Area'
            ],
            [
                'id'              => 'blindness',
                'tag'             => 'Vision Care',
                'tag_style'       => 'amber',
                'headline_prefix' => 'Restoring Sight:',
                'highlight'       => 'Blindness Control',
                'highlight_color' => 'amber',
                'description'     => "Under the Government of India's National Programme for Control of Blindness, INCLEN organizes screening camps and facilitates the distribution of spectacles to elderly individuals in need, improving vision and quality of life.",
                'image_url'       => '/images/community/4.png',
                'image_alt'       => 'Blindness Control Programme',
                'image_left'      => true,
                'accent_type'     => 'badges',
                'accent_title'    => 'Vision',
                'accent_subtitle' => 'Focused Care',
                'accent_text'     => 'Restored Sight'
            ]
        ];

        $wpdb->insert($table_community, [
            'hero_badge'          => 'Outreach • Education • Prevention',
            'hero_heading'        => 'Community Activities',
            'hero_description'    => 'Strengthening Communities Through Care and Awareness',
            'hero_subdescription' => 'Driving Impact via Outreach, Education, and Preventive Healthcare.',
            'intro_heading'       => 'INCLEN is committed to improving public health at the grassroots level through meaningful community engagement.',
            'intro_description'   => 'By integrating research with outreach, we support national health priorities while ensuring essential healthcare services reach underserved populations.',
            'initiatives'         => json_encode($default_initiatives)
        ]);
    }

    // 34. Key Research Findings Table
    $table_findings = $wpdb->prefix . 'key_research_findings';
    $sql_findings = "CREATE TABLE $table_findings (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        hero_badge varchar(255) DEFAULT 'Research • Evidence • Impact',
        hero_heading varchar(255) DEFAULT 'Key Research Findings',
        hero_description longtext DEFAULT '',
        hero_subdescription longtext DEFAULT '',
        intro_heading longtext DEFAULT '',
        intro_highlight varchar(255) DEFAULT '',
        intro_subtext longtext DEFAULT '',
        findings longtext DEFAULT '[]',
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    dbDelta($sql_findings);

    $findings_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_findings");
    if (!$findings_count || $findings_count == 0) {
        $default_findings = [
            [
                'id'                   => 'neuro',
                'category_tag'         => 'Child Health Research',
                'title'                => 'High Burden of',
                'title_highlight'      => 'Neurodevelopmental Disorders',
                'description'          => 'Approximately 12% of children under 12 years of age are affected by neurodevelopmental disorders, highlighting the critical need for early screening and intervention strategies.',
                'accent_color'         => 'accent',
                'layout_position'      => 'chart_right',
                'stat_type'            => 'single_stat',
                'stat_value'           => '12%',
                'stat_label'           => 'Incidence Rate',
                'stat_secondary_value' => '',
                'stat_secondary_label' => '',
                'stat_note'            => 'Underlining the need for scaled screening infrastructure.',
                'tags'                 => '',
                'chart_type'           => 'pie',
                'chart_labels'         => 'Impacted, Others',
                'chart_data'           => '12, 88',
                'chart_data_secondary' => '',
                'chart_dataset_label1' => 'Incidence %',
                'chart_dataset_label2' => ''
            ],
            [
                'id'                   => 'injection',
                'category_tag'         => 'Infection Control',
                'title'                => 'Unsafe',
                'title_highlight'      => 'Injection Practices',
                'description'          => 'Nearly 63% of injections were found to be unsafe, significantly contributing to the transmission of blood-borne infections such as Hepatitis B and HIV.',
                'accent_color'         => 'brand',
                'layout_position'      => 'chart_left',
                'stat_type'            => 'dual_stat',
                'stat_value'           => '63%',
                'stat_label'           => 'Unsafe Rate',
                'stat_secondary_value' => 'Critical',
                'stat_secondary_label' => 'Public Health Alert',
                'stat_note'            => '',
                'tags'                 => '',
                'chart_type'           => 'line',
                'chart_labels'         => 'Public, Private, Outreach, Overall',
                'chart_data'           => '45, 78, 52, 63',
                'chart_data_secondary' => '',
                'chart_dataset_label1' => 'Unsafe Practices %',
                'chart_dataset_label2' => ''
            ],
            [
                'id'                   => 'zoonotic',
                'category_tag'         => 'Emerging Infections',
                'title'                => 'Zoonotic',
                'title_highlight'      => 'Causes of Fever',
                'description'          => 'Around 20% of acute undifferentiated fever cases are attributed to infections such as leptospirosis and scrub typhus, which can lead to serious complications like encephalopathy.',
                'accent_color'         => 'accent',
                'layout_position'      => 'chart_right',
                'stat_type'            => 'tag_list',
                'stat_value'           => '20%',
                'stat_label'           => 'Acute Fever Cases',
                'stat_secondary_value' => '',
                'stat_secondary_label' => '',
                'stat_note'            => '',
                'tags'                 => 'Leptospirosis, Scrub Typhus',
                'chart_type'           => 'bar',
                'chart_labels'         => 'Leptospirosis, Scrub Typhus, Other Zoonotic, Non-Zoonotic',
                'chart_data'           => '8, 7, 5, 80',
                'chart_data_secondary' => '',
                'chart_dataset_label1' => 'Prevalence %',
                'chart_dataset_label2' => ''
            ],
            [
                'id'                   => 'polio',
                'category_tag'         => 'Immunization Strategy',
                'title'                => 'Gaps in',
                'title_highlight'      => 'Polio Coverage',
                'description'          => 'Approximately 24% of children were missed during Pulse Polio campaigns, revealing critical gaps that informed targeted immunization interventions.',
                'accent_color'         => 'brand',
                'layout_position'      => 'chart_left',
                'stat_type'            => 'banner_stat',
                'stat_value'           => '24%',
                'stat_label'           => 'Children Missed during Pulse Polio Campaigns',
                'stat_secondary_value' => '',
                'stat_secondary_label' => '',
                'stat_note'            => '',
                'tags'                 => '',
                'chart_type'           => 'stacked_bar',
                'chart_labels'         => 'North, Central, South, East, West',
                'chart_data'           => '72, 78, 85, 68, 77',
                'chart_data_secondary' => '28, 22, 15, 32, 23',
                'chart_dataset_label1' => 'Reached',
                'chart_dataset_label2' => 'Missed'
            ]
        ];

        $wpdb->insert($table_findings, [
            'hero_badge'          => 'Research • Evidence • Impact',
            'hero_heading'        => 'Key Research Findings',
            'hero_description'    => 'Evidence that Drives Change',
            'hero_subdescription' => 'Data-Led Insights for Better Health Outcomes.',
            'intro_heading'       => 'Our research translates complex public health challenges into actionable, evidence-based solutions.',
            'intro_highlight'     => 'public health challenges',
            'intro_subtext'       => 'We bridge the gap between scientific investigation and implementation to ensure national health priorities are met with data-driven precision.',
            'findings'            => json_encode($default_findings)
        ]);
    }

    // Policy Influence Table
    $table_policy = $wpdb->prefix . 'policy_influence';
    $sql_policy = "CREATE TABLE $table_policy (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        hero_badge varchar(255) DEFAULT 'EVIDENCE TO POLICY',
        hero_title_prefix varchar(255) DEFAULT 'Policy',
        hero_title_highlight varchar(255) DEFAULT 'Influence',
        hero_description text DEFAULT '',
        stats text DEFAULT '[]',
        approach_tag varchar(255) DEFAULT 'How we make an impact',
        approach_heading varchar(255) DEFAULT 'Our Approach',
        approach_description text DEFAULT '',
        pillars text DEFAULT '[]',
        thematic_tag varchar(255) DEFAULT 'Research Streams',
        thematic_heading varchar(255) DEFAULT 'Thematic Focus',
        thematic_description text DEFAULT '',
        thematic_cards text DEFAULT '[]',
        timeline_tag varchar(255) DEFAULT 'Chronology of Impact',
        timeline_heading varchar(255) DEFAULT 'Track Record',
        timeline_description text DEFAULT '',
        timeline_items text DEFAULT '[]',
        partners_tag varchar(255) DEFAULT 'Global Translation Network',
        partners_heading varchar(255) DEFAULT 'Partners & Collaborators',
        partners_description text DEFAULT '',
        collaborators text DEFAULT '[]',
        alliances_tag varchar(255) DEFAULT 'Regional Alliances',
        alliances_text varchar(255) DEFAULT 'IndiaCLEN · ChinaCLEN · LatinCLEN · INCLEN Africa',
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    dbDelta($sql_policy);

    // Seed default Policy Influence data if empty
    $count_policy = $wpdb->get_var("SELECT COUNT(*) FROM $table_policy");
    if ($count_policy == 0) {
        $default_stats = [
            ['id' => 's1', 'value' => '89', 'label' => 'Academic Institutions'],
            ['id' => 's2', 'value' => '34', 'label' => 'Countries'],
            ['id' => 's3', 'value' => '218+', 'label' => 'Partners in India'],
            ['id' => 's4', 'value' => '200K+', 'label' => 'Population under surveillance']
        ];

        $default_pillars = [
            [
                'id' => 'p1',
                'title' => 'Evidence Generation',
                'description' => 'Multi-site, collaborative research producing nationally representative data on high-priority health issues.',
                'icon' => 'evidence'
            ],
            [
                'id' => 'p2',
                'title' => 'Capacity Building',
                'description' => 'Training future leaders in clinical epidemiology and health research who carry evidence into policy roles.',
                'icon' => 'capacity'
            ],
            [
                'id' => 'p3',
                'title' => 'Government Engagement',
                'description' => 'Direct partnerships with MoHFW, ICMR, and state governments to align research with national health priorities.',
                'icon' => 'government'
            ],
            [
                'id' => 'p4',
                'title' => 'Surveillance & Monitoring',
                'description' => 'SOMAARTH-DDESS provides continuous demographic and health data from over 200,000 people to track policy outcomes.',
                'icon' => 'surveillance'
            ]
        ];

        $default_thematic_cards = [
            [
                'id' => 't1',
                'title' => 'Child Health',
                'description' => 'Neurodevelopmental disabilities, low birth weight, pneumonia treatment, and vaccine-preventable disease.',
                'color' => 'purple'
            ],
            [
                'id' => 't2',
                'title' => 'Maternal & Reproductive Health',
                'description' => 'Evidence for improving maternal care quality, neonatal outcomes, and reproductive health access.',
                'color' => 'rose'
            ],
            [
                'id' => 't3',
                'title' => 'Nutrition & NCDs',
                'description' => 'Social determinants of undernutrition, childhood obesity, and metabolic syndrome across LMICs.',
                'color' => 'emerald'
            ],
            [
                'id' => 't4',
                'title' => 'Mental Health',
                'description' => 'Digital and primary-care-based screening and management for depression, anxiety, and alcohol use.',
                'color' => 'sky'
            ],
            [
                'id' => 't5',
                'title' => 'Injuries & Violence',
                'description' => 'Multi-country data on childhood injuries to support prevention policies and health system responses.',
                'color' => 'orange'
            ]
        ];

        $default_timeline_items = [
            [
                'id' => 'tl1',
                'year' => '2019',
                'tag' => 'Neonatal Health',
                'title' => 'Management of Possible Serious Bacterial Infection (PSBI)',
                'description' => 'Implementation research in Palwal, Haryana on managing newborn sepsis where hospital referral is not feasible — directly informing frontline health worker protocols.'
            ],
            [
                'id' => 'tl2',
                'year' => '2018',
                'tag' => 'Vaccines',
                'title' => 'Rollout of Rotavirus Vaccine & Active AEFI Surveillance',
                'description' => 'Multi-centre surveillance of adverse events following immunisation (MAASS-India) and post-introduction evaluation of rotavirus vaccine to support national immunisation policy.'
            ],
            [
                'id' => 'tl3',
                'year' => '2016',
                'tag' => 'Child Health',
                'title' => 'Task Force on Childhood Obesity',
                'description' => 'Nationally coordinated evidence synthesis used to develop India\'s policy framework for prevention and management of childhood and adolescent obesity.'
            ],
            [
                'id' => 'tl4',
                'year' => '2014',
                'tag' => 'Neurodevelopment',
                'title' => 'Neurodevelopmental Disabilities study (NDD-India)',
                'description' => 'Landmark multi-site study establishing prevalence of neurodevelopmental disabilities among Indian children — a critical evidence base for national disability policy.'
            ],
            [
                'id' => 'tl5',
                'year' => '2009',
                'tag' => 'Immunisation',
                'title' => 'Evaluation of Integrated Management of Neonatal & Childhood Illness (IMNCI)',
                'description' => 'Comprehensive programme evaluation informing India\'s IMNCI scale-up strategy and influencing WHO guidelines for LMIC settings.'
            ],
            [
                'id' => 'tl6',
                'year' => '2005',
                'tag' => 'Universal Immunisation',
                'title' => 'Evaluation of Universal Immunization Program (UIP)',
                'description' => 'Multi-site evaluation of India\'s UIP, alongside repeated pulse polio programme evaluations from 1998 onwards, directly shaping national immunisation strategy.'
            ]
        ];

        $default_collaborators = [
            'Ministry of Health & Family Welfare',
            'Indian Council of Medical Research (ICMR)',
            'Government of Haryana',
            'World Health Organization (WHO)',
            'Bill & Melinda Gates Foundation',
            'UNICEF'
        ];

        $wpdb->insert($table_policy, [
            'hero_badge'            => 'EVIDENCE TO POLICY',
            'hero_title_prefix'     => 'Policy',
            'hero_title_highlight'  => 'Influence',
            'hero_description'      => 'INCLEN bridges the gap between rigorous evidence and real-world policy, working alongside governments, international agencies, and health ministries to shape decisions that improve health outcomes for underserved populations.',
            'stats'                 => json_encode($default_stats),
            'approach_tag'          => 'How we make an impact',
            'approach_heading'      => 'Our Approach',
            'approach_description'  => 'INCLEN\'s policy influence rests on four pillars — generating credible multi-site evidence, building a network of trained researchers, engaging directly with decision-makers, and sustaining long-term surveillance to track impact.',
            'pillars'               => json_encode($default_pillars),
            'thematic_tag'          => 'Research Streams',
            'thematic_heading'      => 'Thematic Focus',
            'thematic_description'  => 'INCLEN\'s five thematic groups generate evidence that directly informs national programmes and international guidelines.',
            'thematic_cards'        => json_encode($default_thematic_cards),
            'timeline_tag'          => 'Chronology of Impact',
            'timeline_heading'      => 'Track Record',
            'timeline_description'  => 'Selected research studies that have directly informed national programmes and government decision-making.',
            'timeline_items'        => json_encode($default_timeline_items),
            'partners_tag'          => 'Global Translation Network',
            'partners_heading'      => 'Partners & Collaborators',
            'partners_description'  => 'INCLEN maintains active strategic relationships with government bodies and international agencies to ensure research findings reach those who make policy.',
            'collaborators'         => json_encode($default_collaborators),
            'alliances_tag'         => 'Regional Alliances',
            'alliances_text'        => 'IndiaCLEN · ChinaCLEN · LatinCLEN · INCLEN Africa'
        ]);
    }

    // 40. Academic Association Table
    $table_academic_assoc = $wpdb->prefix . 'academic_association';
    $sql_academic_assoc = "CREATE TABLE $table_academic_assoc (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        program_type varchar(100) NOT NULL DEFAULT 'phd',
        title varchar(255) NOT NULL,
        tag varchar(255) DEFAULT '',
        duration varchar(100) DEFAULT '',
        description longtext DEFAULT '',
        eligibility longtext DEFAULT '',
        pdf_url varchar(500) DEFAULT '',
        image_url varchar(500) DEFAULT '',
        sort_order int(11) DEFAULT 0,
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    dbDelta($sql_academic_assoc);

    if (function_exists('custom_seed_academic_association_data')) {
        $count = $wpdb->get_var("SELECT COUNT(*) FROM $table_academic_assoc");
        if ($count == 0) {
            custom_seed_academic_association_data();
        }
    }

    // 41. Site Navigation (Navbar Control) Table
    $table_nav = $wpdb->prefix . 'site_navigation';
    $sql_nav = "CREATE TABLE $table_nav (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        menu_structure longtext NOT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    dbDelta($sql_nav);

    if (function_exists('get_default_menu_structure')) {
        $count_nav = $wpdb->get_var("SELECT COUNT(*) FROM $table_nav");
        if ($count_nav == 0) {
            $wpdb->insert($table_nav, [
                'menu_structure' => wp_json_encode(get_default_menu_structure())
            ]);
        }
    }
    // 42. Footer Settings Table
    $table_footer = $wpdb->prefix . 'footer_settings';
    $sql_footer = "CREATE TABLE $table_footer (
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
    dbDelta($sql_footer);

    $count_footer = $wpdb->get_var("SELECT COUNT(*) FROM $table_footer");
    if (!$count_footer || $count_footer == 0) {
        $default_explore = [
            ['id' => '1', 'title' => 'Our Mission', 'url' => '/about#what-we-do', 'target' => '_self', 'is_active' => true],
            ['id' => '2', 'title' => 'Research Projects', 'url' => '/research', 'target' => '_self', 'is_active' => true],
            ['id' => '3', 'title' => 'Partner Institutes', 'url' => '/partners', 'target' => '_self', 'is_active' => true],
            ['id' => '4', 'title' => 'Publications', 'url' => '/publications', 'target' => '_self', 'is_active' => true],
            ['id' => '5', 'title' => 'Careers', 'url' => '/careers', 'target' => '_self', 'is_active' => true],
            ['id' => '6', 'title' => 'FCRA & Registration', 'url' => '/fcra', 'target' => '_self', 'is_active' => true],
        ];

        $wpdb->insert($table_footer, [
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

// Hook to run during theme load or admin init
add_action('after_switch_theme', 'custom_setup_database_tables');
add_action('admin_init', 'custom_setup_database_tables');

