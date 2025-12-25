<?php
if (!defined('ABSPATH')) exit;

add_action('init', 'spa_register_taxonomy_places');
function spa_register_taxonomy_places() {
    register_taxonomy('spa_place', 'spa_group', [
        'labels' => [
            'name' => 'Miesta',
            'singular_name' => 'Miesto',
            'search_items' => 'Hľadať miesta',
            'all_items' => 'Všetky miesta',
            'edit_item' => 'Upraviť miesto',
            'update_item' => 'Aktualizovať miesto',
            'add_new_item' => 'Pridať miesto',
            'new_item_name' => 'Nové miesto',
            'menu_name' => 'Miesta'
        ],
        'public' => false,
        'show_ui' => true,
        'hierarchical' => false,
        'show_admin_column' => true,
        'show_in_rest' => false,
        'rewrite' => false
    ]);
}

add_action('init', 'spa_register_taxonomy_categories');
function spa_register_taxonomy_categories() {
    register_taxonomy('spa_group_category', 'spa_group', [
        'labels' => [
            'name' => 'Kategórie skupín',
            'singular_name' => 'Kategória skupín',
            'search_items' => 'Hľadať kategórie',
            'all_items' => 'Všetky kategórie',
            'parent_item' => 'Nadradená kategória',
            'parent_item_colon' => 'Nadradená kategória:',
            'edit_item' => 'Upraviť kategóriu',
            'update_item' => 'Aktualizovať kategóriu',
            'add_new_item' => 'Pridať kategóriu',
            'new_item_name' => 'Nová kategória',
            'menu_name' => 'Kategórie skupín'
        ],
        'hierarchical' => true,
        'show_admin_column' => true,
        'show_ui' => true,
        'public' => false,
        'show_in_rest' => false,
        'rewrite' => ['slug' => 'skupiny-kategorie']
    ]);
}

add_action('after_switch_theme', 'spa_create_default_terms');
function spa_create_default_terms() {
    if (get_option('spa_default_terms_created')) return;
    
    foreach (['Malacky', 'Košice'] as $place) {
        if (!term_exists($place, 'spa_place')) {
            wp_insert_term($place, 'spa_place', ['slug' => sanitize_title($place)]);
        }
    }
    
    $categories = [
        'Deti s rodičmi 1,8-3 roky', 'Deti 3-4 roky', 'Deti 5-7 rokov',
        'Deti 8-10 rokov', 'Deti 10+ rokov', 'Dospelí'
    ];
    
    foreach ($categories as $category) {
        if (!term_exists($category, 'spa_group_category')) {
            wp_insert_term($category, 'spa_group_category', ['slug' => sanitize_title($category)]);
        }
    }
    
    update_option('spa_default_terms_created', true);
}

add_action('after_switch_theme', 'spa_create_events_category');
function spa_create_events_category() {
    if (!term_exists('udalosti', 'category')) {
        wp_insert_term('Udalosti', 'category', [
            'slug' => 'udalosti',
            'description' => 'Tábory, akcie, špeciálne podujatia'
        ]);
    }
}

add_action('spa_place_edit_form_fields', 'spa_place_schedule_meta_box', 10, 2);
function spa_place_schedule_meta_box($term) {
    
    global $wpdb;
    
    // Hľadaj programy kde spa_place_id = term_id
    $program_ids = $wpdb->get_col($wpdb->prepare(
        "SELECT post_id FROM {$wpdb->postmeta} 
         WHERE meta_key = 'spa_place_id' AND meta_value = %d",
        $term->term_id
    ));
    
    if (empty($program_ids)) {
        ?>
        <tr class="form-field">
            <th scope="row"><h2>📅 Rozvrh miesta</h2></th>
            <td>
                <p style="color:#999;">Pre toto miesto nie sú priradené žiadne programy.</p>
                <p style="color:#666;font-size:12px;">
                    Term ID: <?php echo $term->term_id; ?><br>
                    Term Name: <?php echo $term->name; ?>
                </p>
            </td>
        </tr>
        <?php
        return;
    }
    
    $programs = get_posts([
        'post_type' => 'spa_group',
        'post__in' => $program_ids,
        'posts_per_page' => -1,
        'orderby' => 'menu_order title',
        'order' => 'ASC'
    ]);
    
    $schedule_by_day = [];
    $days_map = [
        'monday' => 'Pondelok', 'tuesday' => 'Utorok', 'wednesday' => 'Streda',
        'thursday' => 'Štvrtok', 'friday' => 'Piatok', 'saturday' => 'Sobota', 'sunday' => 'Nedeľa'
    ];
    
    foreach ($programs as $program) {
        $schedule = json_decode(get_post_meta($program->ID, 'spa_schedule', true), true);
        if (!empty($schedule)) {
            foreach ($schedule as $item) {
                if (isset($item['day'], $item['time'])) {
                    $schedule_by_day[$item['day']][] = [
                        'time' => $item['time'],
                        'program' => $program->post_title
                    ];
                }
            }
        }
    }
    
    foreach ($schedule_by_day as &$day) {
        usort($day, fn($a, $b) => strcmp($a['time'], $b['time']));
    }
    ?>
    <tr class="form-field">
        <th scope="row" style="vertical-align:top;padding-top:15px;">
            <h2 style="margin:0;">📅 Rozvrh miesta</h2>
            <p style="font-weight:normal;color:#666;margin:5px 0 0;">
                <?php echo count($programs); ?> <?php echo count($programs) == 1 ? 'program' : 'programov'; ?>
            </p>
        </th>
        <td>
            <style>
            .spa-place-schedule{border-collapse:collapse;width:100%;max-width:800px}
            .spa-place-schedule th{background:#f5f5f5;padding:10px;border:1px solid #ddd;font-weight:600}
            .spa-place-schedule td{padding:10px;border:1px solid #ddd;vertical-align:top}
            .spa-schedule-item{margin-bottom:8px;padding:8px;background:#fff;border-left:3px solid #E4002B;border-radius:3px}
            .spa-schedule-time{font-weight:600;color:#E4002B;margin-bottom:3px}
            .spa-schedule-program{color:#666;font-size:13px}
            </style>
            <table class="spa-place-schedule">
                <thead><tr><th style="width:120px">Deň</th><th>Tréningy</th></tr></thead>
                <tbody>
                    <?php foreach ($days_map as $day_key => $day_label) : ?>
                    <tr>
                        <td><strong><?php echo $day_label; ?></strong></td>
                        <td>
                            <?php if (isset($schedule_by_day[$day_key])) : ?>
                                <?php foreach ($schedule_by_day[$day_key] as $item) : ?>
                                <div class="spa-schedule-item">
                                    <div class="spa-schedule-time"><?php echo esc_html($item['time']); ?></div>
                                    <div class="spa-schedule-program"><?php echo esc_html($item['program']); ?></div>
                                </div>
                                <?php endforeach; ?>
                            <?php else : ?>
                                <span style="color:#999;">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </td>
    </tr>
    <?php
}

/* ==========================
   MIGRÁCIA: Starý spa_place CPT → Nová taxonomia
   ========================== */

   /* add_action('admin_init', 'spa_migrate_place_meta_once');

   function spa_migrate_place_meta_once() {
       
       if (get_option('spa_place_meta_migrated')) {
           return; // Už bolo migrované
       }
       
       global $wpdb;
       
       // Mapovanie: starý post_id → nový term_id
       $mapping = [
           678 => 27,  // Hala Basso (post) → Hala Basso (term)
           // Pridaj ďalšie ak existujú
       ];
       
       foreach ($mapping as $old_post_id => $new_term_id) {
           
           // Nájdi všetky programy s starým post_id
           $programs = $wpdb->get_col($wpdb->prepare(
               "SELECT post_id FROM {$wpdb->postmeta}
                WHERE meta_key = 'spa_place_id' AND meta_value = %s",
               $old_post_id
           ));
           
           foreach ($programs as $program_id) {
               
               // Aktualizuj meta na nový term_id
               update_post_meta($program_id, 'spa_place_id', $new_term_id);
               
               // ALEBO vytvor term relationship (ak chceš použiť taxonomiu natvrdo)
               wp_set_object_terms($program_id, [$new_term_id], 'spa_place', false);
           }
       }
       
       update_option('spa_place_meta_migrated', true);
       
       wp_redirect(admin_url('edit-tags.php?taxonomy=spa_place'));
       exit;
   } */