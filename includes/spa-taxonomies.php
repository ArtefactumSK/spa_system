<?php
/**
 * SPA Taxonomies
 * 
 * @package Samuel Piasecký ACADEMY
 * @version 1.0.0

 * SPA Taxonomies - DEPRECATED
 * Taxonómia 'spa_place' je zastaralá - používame teraz CPT 'spa_place'
 * Ponecháme pre spätnú kompatibilitu so starými programami
 * NOVÉ MIESTA: Pridávaj cez CPT 'spa_place' (admin → Miesta)
 */

if (!defined('ABSPATH')) {
    exit;
}

/* ==========================
   TAXONÓMIA: Miesta (Malacky, Košice)
   ========================== */

add_action('init', 'spa_register_taxonomy_places');

function spa_register_taxonomy_places() {
    
    $labels = [
        'name' => 'Miesta',
        'singular_name' => 'Miesto',
        'search_items' => 'Hľadať miesta',
        'all_items' => 'Všetky miesta',
        'edit_item' => 'Upraviť miesto',
        'update_item' => 'Aktualizovať miesto',
        'add_new_item' => 'Pridať miesto',
        'new_item_name' => 'Nové miesto',
        'menu_name' => 'Miesta'
    ];

    register_taxonomy('spa_place', 'spa_group', [
        'labels' => $labels,
        'public' => false,
        'show_ui' => true,
        'hierarchical' => false,
        'show_admin_column' => true,
        'show_in_rest' => false,
        'rewrite' => false
    ]);
}

/* ==========================
   TAXONÓMIA: Kategórie skupín (vekové)
   ========================== */

add_action('init', 'spa_register_taxonomy_categories');

function spa_register_taxonomy_categories() {
    
    $labels = [
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
    ];

    register_taxonomy('spa_group_category', 'spa_group', [
        'labels' => $labels,
        'hierarchical' => true,
        'show_admin_column' => true,
        'show_ui' => true,
        'public' => false,
        'show_in_rest' => false,
        'rewrite' => ['slug' => 'skupiny-kategorie']
    ]);
}

/* ==========================
   AUTOMATICKÉ VYTVORENIE ZÁKLADNÝCH TERMOV
   ========================== */

add_action('after_switch_theme', 'spa_create_default_terms');

function spa_create_default_terms() {
    
    // Kontrola či už boli vytvorené
    if (get_option('spa_default_terms_created')) {
        return;
    }
    
    // MIESTA
    $places = ['Malacky', 'Košice'];
    
    foreach ($places as $place) {
        if (!term_exists($place, 'spa_place')) {
            wp_insert_term($place, 'spa_place', [
                'slug' => sanitize_title($place)
            ]);
        }
    }
    
    // KATEGÓRIE
    $categories = [
        'Deti s rodičmi 1,8-3 roky',
        'Deti 3-4 roky',
        'Deti 5-7 rokov',
        'Deti 8-10 rokov',
        'Deti 10+ rokov',
        'Dospelí'
    ];
    
    foreach ($categories as $category) {
        if (!term_exists($category, 'spa_group_category')) {
            wp_insert_term($category, 'spa_group_category', [
                'slug' => sanitize_title($category)
            ]);
        }
    }
    
    // Označ že boli vytvorené
    update_option('spa_default_terms_created', true);
}

/* ==========================
   KATEGÓRIA PRE ČLÁNKY: Udalosti
   ========================== */

add_action('after_switch_theme', 'spa_create_events_category');

function spa_create_events_category() {
    
    if (!term_exists('udalosti', 'category')) {
        wp_insert_term('Udalosti', 'category', [
            'slug' => 'udalosti',
            'description' => 'Tábory, akcie, špeciálne podujatia'
        ]);
    }
}

/* ==========================
   META BOX: Rozvrh miesta (automatický)
   ========================== */

add_action('spa_place_edit_form', 'spa_place_schedule_meta_box', 10, 2);

function spa_place_schedule_meta_box($term, $taxonomy) {
    
    // Získaj všetky programy pre toto miesto
    $programs = get_posts([
        'post_type' => 'spa_group',
        'posts_per_page' => -1,
        'tax_query' => [[
            'taxonomy' => 'spa_place',
            'field' => 'term_id',
            'terms' => $term->term_id
        ]]
    ]);
    
    if (empty($programs)) {
        echo '<tr class="form-field"><th scope="row"><h2>📅 Rozvrh miesta</h2></th><td>';
        echo '<p style="color:#999;">Pre toto miesto nie sú priradené žiadne programy.</p>';
        echo '</td></tr>';
        return;
    }
    
    // Zozbieraj rozvrh z programov
    $schedule_by_day = [];
    $days_map = [
        'monday' => 'Pondelok',
        'tuesday' => 'Utorok',
        'wednesday' => 'Streda',
        'thursday' => 'Štvrtok',
        'friday' => 'Piatok',
        'saturday' => 'Sobota',
        'sunday' => 'Nedeľa'
    ];
    
    foreach ($programs as $program) {
        $schedule_json = get_post_meta($program->ID, 'spa_schedule', true);
        $schedule = $schedule_json ? json_decode($schedule_json, true) : [];
        
        if (!empty($schedule)) {
            foreach ($schedule as $item) {
                $day = $item['day'];
                $time = $item['time'];
                
                if (!isset($schedule_by_day[$day])) {
                    $schedule_by_day[$day] = [];
                }
                
                $schedule_by_day[$day][] = [
                    'time' => $time,
                    'program' => $program->post_title
                ];
            }
        }
    }
    
    // Zoraď podľa času
    foreach ($schedule_by_day as &$day_schedule) {
        usort($day_schedule, function($a, $b) {
            return strcmp($a['time'], $b['time']);
        });
    }
    
    ?>
    <tr class="form-field">
        <th scope="row">
            <h2 style="margin:0;">📅 Rozvrh miesta</h2>
            <p style="font-weight:normal;color:#666;margin:5px 0 0 0;">Automaticky generovaný z programov</p>
        </th>
        <td>
            <style>
            .spa-place-schedule { border-collapse: collapse; width: 100%; max-width: 800px; }
            .spa-place-schedule th { background: #f5f5f5; padding: 10px; text-align: left; border: 1px solid #ddd; font-weight: 600; }
            .spa-place-schedule td { padding: 10px; border: 1px solid #ddd; vertical-align: top; }
            .spa-schedule-item { margin-bottom: 8px; padding: 8px; background: #fff; border-left: 3px solid var(--theme-palette-color-3); }
            .spa-schedule-time { font-weight: 600; color: var(--theme-palette-color-3); }
            .spa-schedule-program { color: #666; font-size: 13px; }
            </style>
            
            <table class="spa-place-schedule">
                <thead>
                    <tr>
                        <th>Deň</th>
                        <th>Tréningy</th>
                    </tr>
                </thead>
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
            
            <p style="margin-top:15px;padding:10px;background:#e7f3ff;border-left:3px solid #0073aa;">
                ℹ️ <strong>Poznámka:</strong> Rozvrh sa automaticky aktualizuje podľa pridelených programov.
            </p>
        </td>
    </tr>
    <?php
}

/* ==========================
   META BOX: Rozvrh miesta (automatický)
   ========================== */

   add_action('spa_place_edit_form_fields', 'spa_place_schedule_meta_box', 10, 2);

   function spa_place_schedule_meta_box($term) {
       
       // Získaj všetky programy pre toto miesto
       $programs = get_posts([
           'post_type' => 'spa_group',
           'posts_per_page' => -1,
           'tax_query' => [[
               'taxonomy' => 'spa_place',
               'field' => 'term_id',
               'terms' => $term->term_id
           ]],
           'orderby' => 'menu_order title',
           'order' => 'ASC'
       ]);
       
       if (empty($programs)) {
           ?>
           <tr class="form-field">
               <th scope="row"><h2>📅 Rozvrh miesta</h2></th>
               <td><p style="color:#999;">Pre toto miesto nie sú priradené žiadne programy.</p></td>
           </tr>
           <?php
           return;
       }
       
       // Zozbieraj rozvrh z programov
       $schedule_by_day = [];
       $days_map = [
           'monday' => 'Pondelok',
           'tuesday' => 'Utorok',
           'wednesday' => 'Streda',
           'thursday' => 'Štvrtok',
           'friday' => 'Piatok',
           'saturday' => 'Sobota',
           'sunday' => 'Nedeľa'
       ];
       
       foreach ($programs as $program) {
           $schedule_json = get_post_meta($program->ID, 'spa_schedule', true);
           $schedule = $schedule_json ? json_decode($schedule_json, true) : [];
           
           if (!empty($schedule) && is_array($schedule)) {
               foreach ($schedule as $item) {
                   if (isset($item['day']) && isset($item['time'])) {
                       $day = $item['day'];
                       $time = $item['time'];
                       
                       if (!isset($schedule_by_day[$day])) {
                           $schedule_by_day[$day] = [];
                       }
                       
                       $schedule_by_day[$day][] = [
                           'time' => $time,
                           'program' => $program->post_title
                       ];
                   }
               }
           }
       }
       
       // Zoraď podľa času
       foreach ($schedule_by_day as &$day_schedule) {
           usort($day_schedule, function($a, $b) {
               return strcmp($a['time'], $b['time']);
           });
       }
       
       ?>
       <tr class="form-field">
           <th scope="row" style="vertical-align:top; padding-top:15px;">
               <h2 style="margin:0;">📅 Rozvrh miesta</h2>
               <p style="font-weight:normal;color:#666;margin:5px 0 0 0;">Automaticky generovaný z programov</p>
           </th>
           <td>
               <style>
               .spa-place-schedule { border-collapse: collapse; width: 100%; max-width: 800px; margin-top: 5px; }
               .spa-place-schedule th { background: #f5f5f5; padding: 10px; text-align: left; border: 1px solid #ddd; font-weight: 600; }
               .spa-place-schedule td { padding: 10px; border: 1px solid #ddd; vertical-align: top; }
               .spa-schedule-item { margin-bottom: 8px; padding: 8px; background: #fff; border-left: 3px solid var(--theme-palette-color-3, #E4002B); border-radius: 3px; }
               .spa-schedule-time { font-weight: 600; color: var(--theme-palette-color-3, #E4002B); margin-bottom: 3px; }
               .spa-schedule-program { color: #666; font-size: 13px; }
               </style>
               
               <table class="spa-place-schedule">
                   <thead>
                       <tr>
                           <th style="width: 120px;">Deň</th>
                           <th>Tréningy</th>
                       </tr>
                   </thead>
                   <tbody>
                       <?php foreach ($days_map as $day_key => $day_label) : ?>
                           <tr>
                               <td><strong><?php echo esc_html($day_label); ?></strong></td>
                               <td>
                                   <?php if (isset($schedule_by_day[$day_key]) && !empty($schedule_by_day[$day_key])) : ?>
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
               
               <p style="margin-top:15px;padding:10px;background:#e7f3ff;border-left:3px solid #0073aa;border-radius:3px;">
                   ℹ️ <strong>Poznámka:</strong> Rozvrh sa automaticky aktualizuje podľa pridelených programov k tomuto miestu.
               </p>
           </td>
       </tr>
       <?php
   }