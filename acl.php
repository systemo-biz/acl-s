<?php
/*
Plugin Name: ACL
Plugin URI: http://www.casepress.org/
Description: Access control list for WP
Version: 20130816
Author: CasePress Studio
Author URI: http://www.casepress.org
License: GPL
*/

/*
Подключаем компоненты
*/
//Группы
require_once('includes/groups.php');
//Замещения
require_once('includes/deputies.php');
//Пользовательский интерфейс для указания пользователей и групп у постов
require_once('includes/posts_ui.php');



class ACL {

    function __construct() {
        add_action('admin_enqueue_scripts', array($this, 'load_ss_acl'));
        add_filter('posts_where', array($this, 'acl_filter_where'), 10, 1);
        
        
    }

	/*
	Фильтр постов на основе наличия доступа	
	*/
    function acl_filter_where($where) {
		if (current_user_can('full_access_to_posts') or current_user_can('editor') or current_user_can('administrator')) return $where;
		
		$current_usr_id = get_current_user_id();
		$acl_users[] = $current_usr_id;
		$sub = get_user_meta(get_current_user_id(), 'acl_substitutes');
		$acl_users = array_merge( $sub, $acl_users);
		$acl_users = array_unique($acl_users);

		// получим ид групп в которых есть пользователь
		$acl_groups_id = get_transient('acl_groups_id');
		if (false === $acl_groups_id) {
			$acl_groups_id = get_posts("numberposts=-1&fields=ids&post_type=user_group&meta_key=users&meta_value=".$current_usr_id);
			set_transient('acl_groups_id', $acl_groups_id, 5);
		}
		
		//$acl_groups_id = get_posts("fields=ids&post_type=user_group&meta_key=users&meta_value=".$current_usr_id);

		//Определяем типы постов для контроля доступа
		$pt_array = array( 'report', 'cases', 'post', 'document', 'forum' );
		$pt = "'".implode("','", $pt_array)."'";
		//Определяем статусы постов для контроля доступа
		//добавил Резанов Е.В. 09.07.2014 
		$ps_array = array( 'publish', 'future', 'draft', 'pending', 'private' );
		$ps = "'".implode("','", $ps_array)."'";
		
		$args = array(  
			'fields' => 'ids',
			'post_type' => $pt_array,
			'post_status' =>$ps_array,
			'meta_query' => array(
				'relation' => 'OR',  
				array(  
					'key' => 'acl_users_read',  
					'value' => $acl_users
				)
			),
			'numberposts' => '-1'
		);
		
		//error_log("пользователь: ". $current_usr_id . ", группа: " . print_r($acl_groups_id, true));
		
		// на данном этапе имеем ИД пользователя и ИД групп куда он входит
		// получим ИД постов из таблицы ACL с доступом и переводим их в запятые.
		// код только не красивый
		$ids_usr = ACL_get_post_for_where($current_usr_id, 'user');
		$ids_group = array();
		if(!empty($acl_groups_id)) {
		    foreach ($acl_groups_id as $acl_group_id) {
		        $tmp=array();
			    $tmp=ACL_get_post_for_where($acl_group_id, 'group');
				$ids_group = $ids_group+$tmp;
			}
		}
		$my_ids=array_unique($ids_usr+$ids_group);
		
		
		/*if(!empty($acl_groups_id))
			$args['meta_query'][] = array(  
					'numberposts' => '-1',
					'key' => 'acl_groups_read',
					'value' => $acl_groups_id
				);

		$ids = get_transient('acl_ids');

		if (false === $ids) {
			$ids = get_posts($args);
			set_transient('acl_ids', $ids, 5);
		}*/

		//Получаем ИД постов с доступом и переводим их в запятые.
		//$ids = get_posts($args);
		//print_r('Meta:'.$ids);
		//print_r('Table:'.$my_ids);
		//$ids = implode(",", $ids);
		$ids = implode(",", $my_ids);
		//error_log($ids);
        global $wpdb;
		$where .= " AND (if(".$wpdb->posts.".post_type in (" . $pt . "),if(".$wpdb->posts.".ID IN (" . $ids . "),1,0),1)=1)";
        
		return $where;

        

    }
    
    function load_ss_acl(){
        global $post;
        //select2
        //if (!($post->post_type == "user_group') ) return;
        
        $handle = 'select2';
        
        $src_css = plugin_dir_url(__FILE__).'select2/select2.css';
        wp_enqueue_style( $handle, $src_css );

        $src_js = plugin_dir_url(__FILE__).'select2/select2.min.js';
        wp_enqueue_script( $handle, $src_js );
    }


}

$theACL = new ACL();

/* функция для выборки постов из таблицы
 по ИД пользователя, либо по ИД группы
 возвращает массив ИД постов*/
function ACL_get_post_for_where($subject_id, $subject_type){
        global $wpdb;
		$table_name = $wpdb->prefix . "acl";
		$object_type='post';
		$sql = $wpdb->prepare("SELECT object_id FROM $table_name  WHERE object_type=%s AND subject_type=%s AND subject_id=%d",$object_type, $subject_type, $subject_id);
		$objects_ids = $wpdb->get_results($sql);
		$ids=array();
		if ($objects_ids){
			foreach ($objects_ids as $object_id) {
			    $id=$object_id->object_id;
				$ids[]=$id;
			}
		}
		
		return $ids;
}

// действия при активации\деактивации\удалении плагина
function ACL_Setup_on_activation()
{
	if ( ! current_user_can( 'activate_plugins' ) )
		return;
	$plugin = isset( $_REQUEST['plugin'] ) ? $_REQUEST['plugin'] : '';
	check_admin_referer( "activate-plugin_{$plugin}" );
	acl_create_table();
	// Расcкомментируйте эту строку, чтобы увидеть функцию в действии
	// exit( var_dump( $_GET ) );
}

function ACL_Setup_on_deactivation()
{
	if ( ! current_user_can( 'activate_plugins' ) )
		return;
	$plugin = isset( $_REQUEST['plugin'] ) ? $_REQUEST['plugin'] : '';
	check_admin_referer( "deactivate-plugin_{$plugin}" );

	// Расcкомментируйте эту строку, чтобы увидеть функцию в действии
	//exit( var_dump( $_GET ) );
}

function ACL_Setup_on_uninstall()
{
	if ( ! current_user_can( 'activate_plugins' ) )
		return;
	check_admin_referer( 'bulk-plugins' );

	// Важно: проверим тот ли это файл, который
	// был зарегистрирован в процессе хука удаления.
	if ( __FILE__ != WP_UNINSTALL_PLUGIN )
		return;
    //error_log('// Расcкомментируйте эту строку, чтобы увидеть функцию в действии');
	// Раскомментируйте эту строку, чтобы увидеть функцию в действии
	//exit( var_dump( $_GET ) );
}	

function acl_create_table () {
    global $wpdb;
    $table_name = $wpdb->prefix . "acl";
	error_log($table_name);
    if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        $sql = "CREATE TABLE " . $table_name . " (
	        id mediumint(9) NOT NULL AUTO_INCREMENT,
			subject_type VARCHAR(55) NOT NULL,
			object_type VARCHAR(55) NOT NULL,
			subject_id mediumint(9) NOT NULL,
			object_id mediumint(9) NOT NULL,
	        UNIQUE KEY id (id)
	    );";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}

register_activation_hook(   __FILE__, 'ACL_Setup_on_activation' );
register_deactivation_hook( __FILE__, 'ACL_Setup_on_deactivation' );
register_uninstall_hook(    __FILE__, 'ACL_Setup_on_uninstall' );

// для ДоДо
// перегоним все меты в таблицы
add_shortcode('ACL_add_meta_for_update_table','ACL_add_meta_for_update_table');
function ACL_add_meta_for_update_table() {
    $args = array(
	  'post_type'=>'post',
	  'post_status'=>'any',
	  'numberposts'=>-1,
	);
	$posts=get_posts($args);
	foreach ($posts as $post): setup_postdata($post); 
	    add_post_meta($post->ID, 'add_to_acl_table', 'no', true);
	endforeach;
}
//
	function inst_update_ACL_meta ($subject_type, $object_type, $subject_id, $object_id) {
	    global $wpdb;
		$table_name = $wpdb->prefix . "acl";
		// проверим есть ли такая запись если есть - обновим, если нет, то добавим
		$check_acl_table=inst_check_ACL_meta($subject_type, $object_type, $subject_id, $object_id);
		//error_log('$check_acl_table='.$check_acl_table);
		if (!$check_acl_table){
		    //error_log('нет такой записи, добавляем '.$subject_type.' '.$subject_id.' для :'.$object_id);
		    $data=array('subject_id'=>$subject_id, 'subject_type'=>$subject_type, 'object_type'=>'post', 'object_id'=>$object_id);
		    $format=array('%d','%s', '%s', '%d');
		    $result = $wpdb->insert($table_name, $data, $format);    
		}
		else {
		    //error_log('есть такая запись. обновляем:'.$object_id);
		    $data=array('subject_id'=>$subject_id, 'subject_type'=>$subject_type, 'object_type'=>'post');
		    $format=array('%d','%s', '%s');
			$where=array('object_id'=>$object_id);
			$where_format=array('%d');
		    $result = $wpdb->update($table_name, $data, $format, $where, $where_format);
		}

	    //$wpdb->show_errors();
		//$wpdb->print_error();
		return $result;
	}
	
    function inst_check_ACL_meta ($subject_type, $object_type, $subject_id, $object_id) {
	    // проверяем есть ли уже в таблице такая запись 
		global $wpdb;
		$table_name = $wpdb->prefix . "acl";
		$subjects_ids = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_name  WHERE object_type=%s AND subject_type=%s AND object_id=%d AND subject_id=%s",$object_type, $subject_type, $object_id, $subject_id));
		//$subjects_ids = $wpdb->get_results($sql);
		
		return $subjects_ids;
	}

add_shortcode('ACL_copy_meta_to_table','ACL_copy_meta_to_table');
function ACL_copy_meta_to_table() {
    $args = array(
	    'post_type'=>'post',
		'post_status'=>'any',
		'numberposts'=>-1,
		'meta_key'=>'add_to_acl_table',
		'meta_value'=>'no',
	);
	$posts=get_posts($args);
	foreach ($posts as $post): setup_postdata($post); 
	    $acl_users_read=get_post_meta($post->ID, 'acl_users_read');
		if (isset($acl_users_read)){
		    foreach ($acl_users_read as $acl_user_read){
			    inst_update_ACL_meta ('user', 'post', $acl_user_read, $post->ID);
			}
		}
		$acl_groups_read = get_post_meta($post->ID, 'acl_groups_read');
		if (isset($acl_groups_read)){
		    foreach ($acl_groups_read as $acl_group_read) {
			    inst_update_ACL_meta ('group', 'post', $acl_group_read, $post->ID);
			}
		}
		update_post_meta($post->ID, 'add_to_acl_table', 'yes', true);
	endforeach;
}
?>