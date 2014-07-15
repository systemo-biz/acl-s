<?php/*Добавляем страницу настроек WordPress*/add_action('admin_menu', 'cp_gdeslon_settings_page_add');function cp_gdeslon_settings_page_add(){add_options_page(	$page_title = 'ГдеСлон Купоны', 	$menu_title='ГдеСлон Купоны', 	$capability='manage_options', 	$menu_slug='cp_gdeslon_settings_page', 	$function='cp_gdeslon_settings_page_function');}function cp_gdeslon_settings_page_function(){?>    <div class="wrap">        <h1>Настройки ГдеСлон</h1>        <form action="options.php" method="POST">            <?php settings_fields( 'cp_gdeslon_settings_page' ); ?>            <?php do_settings_sections( 'cp_gdeslon_settings_page' ); ?>            <?php submit_button(); ?>        </form>    </div><?php}/*Регистрируем опции, секции и поля*/add_action('admin_init', 'cp_gdeslon_init_options');function cp_gdeslon_init_options(){	    register_setting( 'cp_gdeslon_settings_page', 'cp_gdeslon_url_csv' );    register_setting( 'cp_gdeslon_settings_page', 'cp_gdeslon_period_cron' );    register_setting( 'cp_gdeslon_settings_page', 'cp_gdeslon_post_types_select' );	/*	Добавляем секцию на страницу настроек	*/	add_settings_section( 		$id = 'cp_gdeslon_settings_sections', 		$title = '', 		$callback = 'cp_settings_pages_section_callback', 		$page = 'cp_gdeslon_settings_page'	);	/*	Добавляем поля к секции настроек	*/	add_settings_field(		$id = 'cp_gdeslon_options_field_url_csv', 		$title = 'URL для загрузки CSV', 		$callback = 'cp_gdeslon_options_field_url_csv_callback', 		$page = "cp_gdeslon_settings_page", 		$section = "cp_gdeslon_settings_sections" 		);	add_settings_field(		$id = 'cp_gdeslon_options_field_period_cron', 		$title = 'Расписание', 		$callback = 'cp_gdeslon_options_field_period_cron_callback', 		$page = "cp_gdeslon_settings_page", 		$section = "cp_gdeslon_settings_sections" 		);	add_settings_field(		$id = 'cp_gdeslon_options_field_post_types_select', 		$title = 'Выбор типа поста', 		$callback = 'cp_gdeslon_options_field_post_types_select_callback', 		$page = "cp_gdeslon_settings_page", 		$section = "cp_gdeslon_settings_sections" 		);}function cp_settings_pages_section_callback(){?><p>Данные для настроек можно взять в личном кабинете ГдеСлон (<a href="http://www.gdeslon.ru/">www.gdeslon.ru</a>)</p><p>Также убедитесь что wp_cron работает для автоматической загрузки (<a href="http://codex.wordpress.org/Function_Reference/wp_cron">wp_cron()</a>)</p><?php}function cp_gdeslon_options_field_url_csv_callback(){	$setting_name = 'cp_gdeslon_url_csv';	$setting_value = esc_attr( get_option( $setting_name ) );?><div id="<?php echo $setting_name; ?>">	<input type="url" size="55" name="<?php echo $setting_name; ?>" value="<?php echo $setting_value; ?>" /></div><?php}function cp_gdeslon_options_field_period_cron_callback(){	$setting_name = 'cp_gdeslon_period_cron';	$setting_value = esc_attr( get_option( $setting_name ) );	$schedules = wp_get_schedules();	$schedules_keys = array_keys($schedules);	echo '<select name="'. $setting_name .'" id="'.$setting_name.'">';	echo '<option value="none"', ($schedule['interval'] == $setting_value ? ' selected="selected"' : ''), '>В ручном режиме</option>';	$i = 0;	foreach ($schedules as $schedule) {		echo '<option value="', $schedules_keys[$i], '"', ($schedules_keys[$i] == $setting_value ? ' selected="selected"' : ''), '>', $schedule['display'], '</option>';		$i++;	}	echo '</select>';}function cp_gdeslon_options_field_post_types_select_callback() {	$setting_name = 'cp_gdeslon_post_types_select';	$setting_value = esc_attr( get_option( $setting_name ) );	$post_types = get_post_types();	//echo $setting_value;	echo '<select name="'. $setting_name .'" id="'.$setting_name.'">';	foreach ($post_types as $post_type) {		echo '<option value="', $post_type, '"', ($post_type == $setting_value ? ' selected="selected"' : ''), '>', $post_type, '</option>';	}	echo '</select>';}