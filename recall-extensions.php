<?php
/*
Plugin Name: Recall Extensions
Plugin URI:
Description: Plugin extends some WP-Recall, Recall-Magazine functionality.
Version: 0.1.2
Author: Vadim Pshentsov
Author URI: http://pshentsoff.ru
License: Apache License, Version 2.0
Wordpress version supported: 3.6 and above
Text Domain: comments-extensions
Domain Path: /languages
*/
/**
 * @file        recall-extensions.php
 * @description
 *
 * PHP Version  5.3.13
 *
 * @package 
 * @category
 * @plugin URI
 * @copyright   2013, Vadim Pshentsov. All Rights Reserved.
 * @license     http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 * @author      Vadim Pshentsov <pshentsoff@gmail.com> 
 * @link        http://pshentsoff.ru Author's homepage
 * @link        http://blog.pshentsoff.ru Author's blog
 *
 * @created     10.11.13
 */

/**
 * Вешаем админские хуки
 */
if(is_admin()) {
    // js
    add_action('admin_head', 're_js_enqueue');
    // Дополнительные метабоксы
    add_action('add_meta_boxes', 're_metaboxes_init');

    //Добавляем колонку баланса в админке к строчкам публикаций в группах wp-recall
    add_filter( 'manage_edit-post-group_columns', 're_user_balance_post_group_columns' );
    add_filter( 'manage_posts_custom_column', 're_user_balance_posts_custom_column', 5, 2 );

}

/**
 * Подключаем скрипты к админке
 */
function re_js_enqueue() {
    wp_enqueue_script( 'jquery' );
    wp_enqueue_script( 're_admin_ajax', plugins_url('js/admin.js', __FILE__) );
}

/**
 * Добавляем метабоксы
 */
function re_metaboxes_init() {
    // Метабокс вознаграждений за публикацию
    add_meta_box('post_fee', __('Вознаграждение за публикацию'), 're_post_fee_show', 'post-group', 'side', 'high');
}

/**
 * Выводим метабокс вознаграждений за публикацию
 * @param $post
 * @param null $box
 */
function re_post_fee_show($post, $box = null) {

    $post_fee = get_post_meta($post->ID, '_post_fee', true);
    $post_fee = !empty($post_fee) ? $post_fee : 0;

    $post_fee_html = '<input type="text" class="post-fee-'.$post->ID.'" name="post-fee-'.$post->ID.'" size="4" value="'.$post_fee.'">';
    $post_fee_html .= '<input type="button" class="set_post_fee" id="set-post-fee-'.$post->ID.'" value="'.__('Назначить').'">' ;

    echo $post_fee_html;
}

/**
 * Добавляем колонки для списка публикаций
 * @param $columns
 * @return array
 */
function re_user_balance_post_group_columns($columns) {

    return array_merge( $columns,
        array(
            'post_author'       => __('Автор'),
            'post_fee'    => __('Вознаграждение'),
        )
    );

}

/**
 * Вывод данных по добавленным колонкам
 * @param $column_name
 * @param $post_id
 */
function re_user_balance_posts_custom_column( $column_name, $post_id) {

    global $wpdb;

    switch ($column_name) {
        case 'post_fee':

            $post = get_post($post_id);
            re_post_fee_show($post);

            break;
        case 'post_author':

            $post = get_post($post_id);
            $user_id = $post->post_author;

            $user = get_userdata($user_id);

            echo $user->user_login;

            break;
    }
}

function re_set_post_fee() {

    global $wpdb;

    $post_id = isset($_POST['post_id']) ? $_POST['post_id'] : false;
    $post_fee = isset($_POST['post_fee']) ? $_POST['post_fee'] : 0;

    $answer = array(
        'result' => 'false',
        'error_msg' => __('Неизвестная ошибка.'),
        'post_id' => $post_id,
        'post_fee' => $post_fee,
    );

    if($post_id) {
        // Проверяем параметры
        $post_id = is_numeric($post_id) ? (int)$post_id : false;
        if(!$post_id) {
            $answer['error_msg'] = __('Не верный формат ID публикации.');
            echo json_encode($answer);
            exit;
        }
        if(!get_post($post_id)) {
            $answer['error_msg'] = __('Не найжено публикации с указанным ID.');
            echo json_encode($answer);
            exit;
        }
        if(!is_numeric($post_fee)) {
            $answer['error_msg'] = __('Не верный формат вознаграждения.');
            echo json_encode($answer);
            exit;
        }

        // Запоминаем значение вознаграждения
        $prev_post_fee = get_post_meta($post_id, '_post_fee', true);
        if($prev_post_fee != $post_fee) {
            if(!update_post_meta($post_id, '_post_fee', $post_fee)) {
                $answer['error_msg'] = __('Ошибка при изменении значения вознаграждения.');
                echo json_encode($answer);
                exit;
            } else {
                $answer['result'] = 'true';
                $answer['error_msg'] = __('Значение вознаграждения не изменилось.');
                echo json_encode($answer);
                exit;
            }
        }

        // Корректируем личный счет
        $post = get_post($post_id);
        $user_count = $wpdb->get_var("SELECT count FROM ".RMAG_PREF ."user_count WHERE user = '$post->post_author'");
        if($prev_post_fee) {
            $user_count -= $prev_post_fee;
        }
        $user_count += $post_fee;

        $wpdb->update(RMAG_PREF.'user_count', array('count' => $user_count), array('user' => $post->post_author));

        $answer['result'] = 'true';
    } else {
        $answer['error_msg'] = __('Не указан ID публикации.');
        echo json_encode($answer);
        exit;
    }

    echo json_encode($answer);
    exit;
}
add_action('wp_ajax_re_set_post_fee', 're_set_post_fee');
add_action('wp_ajax_nopriv_re_set_post_fee', 're_set_post_fee');

//remove debug function and hook
/**
 * Выводим в консоль для определения нужных параметров
 */
/*add_action('in_admin_header', 'my_get_current_screen');
function my_get_current_screen(){
    $screen_info = get_current_screen();

    fb('$screen_info = '.print_r($screen_info, true));
}*/