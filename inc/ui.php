<?php

// User interface for ACL
class ACL_UI {

function __construct() {

  add_action('admin_enqueue_scripts', array($this, 'load_jquery_plugins'));
  add_action('admin_head', array($this, 'style_for_ui'));
  add_action('post_submitbox_start', array($this, 'add_field_to_submitbox'));
  add_action('save_post', array($this,'save_acl_fields'));
  add_action('wp_ajax_add_acl_users', array($this, 'add_acl_users_callback'));
  add_action('wp_ajax_delete_acl_user', array($this,'delete_acl_user_callback'));
  add_action('wp_ajax_get_users', array($this, 'get_users_for_autocomplete'));

}




function load_jquery_plugins(){

    $post_types = get_post_types_for_acl_s();
    $post = get_post();

    if(empty($post)) return;
    if(!in_array($post->post_type, $post_types)) return;

    //DataTable
    wp_enqueue_style( 'datatable', plugin_dir_url(__FILE__).'datatable/media/css/jquery.dataTables.css' );
    wp_enqueue_script( 'datatable', plugin_dir_url(__FILE__).'datatable/media/js/jquery.dataTables.js' );

    //autocomplete
    wp_enqueue_script('jquery-ui-autocomplete');
}

function style_for_ui(){
  $post_types = get_post_types_for_acl_s();
  $post = get_post();

  if(empty($post)) return;
  if(!in_array($post->post_type, $post_types)) return;

  ?>
  <style>
     .ui-autocomplete{z-index:1000000;}
     .access-options{display:none;}
     #acl_s_true:checked + label + .access-options{display:block;}
  </style>
  <?php
}


function add_field_to_submitbox(){
  $post_types = get_post_types_for_acl_s();
  $post = get_post();

  if(empty($post)) return;
  if(!in_array($post->post_type, $post_types)) return;

     ?>
     <script>
     jQuery(document).ready(function($){
      //autocomplete
      $.ajax({
        data: ({
          action:'get_users',
        }),
        url: "<?php echo admin_url('admin-ajax.php') ?>",
        success: function(data){
    function split( val ) {
      return val.split( /,\s*/ );
    }
    function extractLast( term ) {
      return split( term ).pop();
    }

    $( "#acl_users_s" )
      .bind( "keydown", function( event ) {
        if ( event.keyCode === $.ui.keyCode.TAB &&
            $( this ).autocomplete( "instance" ).menu.active ) {
          event.preventDefault();
        }
      })
      .autocomplete({
        minLength: 0,
        source: function( request, response ) {
          response( $.ui.autocomplete.filter(
            JSON.parse(data), extractLast( request.term ) ) );
        },
        focus: function() {
          return false;
        },
        select: function( event, ui ) {
          var terms = split( this.value );
          terms.pop();
          terms.push( ui.item.value );
          terms.push( "" );
          this.value = terms.join( ", " );
          return false;
        }
      });
        }
      });

      //Обработка удаления пользователя из списка
      $('#users_table tbody').on('click','.delete_acl_user', function(){
        var tr= $(this).closest('tr');
        var userID= tr.find('.user_id').text();
        $.ajax({
        data: ({
            action: 'delete_acl_user',
            post_id: <?php echo $post->ID ?>,
            user_id: userID,
            }),
        url: "<?php echo admin_url('admin-ajax.php') ?>",
        success: function(){
          var row = table.row(tr);
          row.remove().draw();
        },
    });
  });
      //Обработка добавления пользователей в список
      $('.add_users').click(function(){
        add_users();
      });
      $('#acl_users_s').keypress(function(e){
          if(e.keyCode==13){
            add_users();
           }
         });
      function add_users(){
        $.ajax({
          data:({
            action: 'add_acl_users',
            post_id: <?php echo $post->ID ?>,
            user_string: $('#acl_users_s').val()
          }),
          url: "<?php echo admin_url('admin-ajax.php') ?>",
          success: function(data){
              table.rows.add(JSON.parse(data)).draw();
            }

              })
      }
      var table = $('#users_table').DataTable();
      $('#users_table').DataTable();
      $('#users_table2').DataTable();
  });
        </script>
        <div class='misc-pub-section'>
            <input type="checkbox" name="acl_s_true" id="acl_s_true" autocomplete="off" <?php echo get_post_meta($post->ID,'acl_s_true',true)?>>
            <label for="acl_s_true">Доступ по списку</label>
            <div class="access-options">
            <br>
            <span id="acl">Доступ: </span>
            <a href='#TB_inline?width=750&height=700&inlineId=acl_form' class="thickbox" id="options" title="Настройка доступа">Настройка</a>
            </div>
        </div>
        <div id='acl_form' style='display:none;'>
          <div class="field_add_user_to_acl_s">
            <label for="acl_users_s">Пользователи:</label>
            <br/>
            <input id="acl_users_s" name="acl_users_s">
            <input type="button" class=" button add_users" value="Добавить">
          </div>
          <br/><br/>
          <div class="list_manual_for_acl">
            <strong>Список ручного указания доступа к посту</strong><br/><small>Храниться в метаполе: list_users_for_acl_additional</small>

            <table id="users_table">
              <thead>
                  <tr>
                      <th>ID</th>
                      <th>Имя пользователя</th>
                      <th>Действие</th>
                  </tr>
              </thead>

              <tbody>
                  <?php
                      $acl_users_s=get_post_meta($post->ID,'list_users_for_acl_additional');
                      foreach ($acl_users_s as $acl_user):
                          $user_data=get_user_by('id',$acl_user);
                          ?>
                          <tr>
                              <td><span class="user_id"><?php echo $acl_user; ?></span></td>
                              <td><?php echo $user_data->user_nicename; ?></td>
                              <td><a href="#" class="delete_acl_user">удалить</a></td>
                          </tr>
                      <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <br>
          <div class="acl_users_s_wrapper">
            <strong>Список доступа</strong><br/><small>Храниться в метаполе: acl_users_s</small>
            <table id="users_table2">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Имя пользователя</th>
                </tr>
              </thead>

              <tbody>
                <?php
                      $acl_users_s=get_post_meta($post->ID,'acl_users_s');
                      foreach ($acl_users_s as $acl_user) {
                          $user_data=get_user_by('id',$acl_user);
                          ?>
                          <tr>
                              <td><?php echo $acl_user; ?></td>
                              <td><?php echo $user_data->user_nicename; ?></td>
                          </tr><?php
                      }?>
              </tbody>
            </table>
          </div>
        </div>
            <?php
}

function save_acl_fields($post_id){
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (wp_is_post_revision($post_id)) return;

    if(isset($_REQUEST['acl_s_true'])){
        update_post_meta($post_id, 'acl_s_true', 'checked');
    }
    else{
        //Если мета существует, а галочка уже убрана, удаляем мету
        $meta_value=get_post_meta($post_id,'acl_s_true',true);
        if(!empty($meta_value)){
            delete_post_meta($post_id,'acl_s_true');
        }
    }
}

function add_acl_users_callback(){
    $acl_users = get_post_types_for_acl_s();
    $acl_users = array_unique($acl_users, SORT_STRING);
    $old_acl_users = get_post_meta($_REQUEST['post_id'], 'list_users_for_acl_additional');

    foreach ( $acl_users as $user_nicename ) {
      $user_data=get_user_by('slug', $user_nicename);
      $user_id=!empty($user_data)?$user_data->ID:'';
      if (!(in_array($user_id, $old_acl_users)) && !empty($user_nicename)){
        add_post_meta($_REQUEST['post_id'], 'list_users_for_acl_additional', $user_id);
        $data_for_table[]=["$user_id","$user_nicename","<a href='#' class='delete_acl_user'>удалить</a>"];
      };
    };
    echo json_encode($data_for_table);
    exit;
}

function delete_acl_user_callback(){
    delete_post_meta($_REQUEST['post_id'],'list_users_for_acl_additional',$_REQUEST['user_id']);
    exit;
}

function get_users_for_autocomplete(){
  global $wpdb;
    $users = $wpdb->get_results(
  "
  SELECT user_nicename
  FROM $wpdb->users
  ");
  if( $users ) {
    foreach ( $users as $user ) {
      $user_data[]=$user->user_nicename;
    }

    echo json_encode($user_data);
  }

  exit;
}

} $TheACL_UI = new ACL_UI;
