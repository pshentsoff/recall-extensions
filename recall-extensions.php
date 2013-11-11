<?php
/*
Plugin Name: Recall Extensions
Plugin URI:
Description: Plugin extends some WP-Recall, Recall-Magazine functionality.
Version: 0.2.9
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

define('RE_PAY_REQUEST_DECLINED', -1);
define('RE_PAY_REQUEST_SET', 1);
define('RE_PAY_REQUEST_SUSPENDED', 2);
define('RE_PAY_REQUEST_ACCEPTED', 3);

if(is_admin()) {
    /**
     * Вешаем админские хуки
     */
    // js
    add_action('admin_head', 're_js_enqueue_admin');
    // Дополнительные метабоксы
    add_action('add_meta_boxes', 're_metaboxes_init');

    //Добавляем колонку баланса в админке к строчкам публикаций в группах wp-recall
    add_filter( 'manage_edit-post-group_columns', 're_user_balance_post_group_columns' );
    add_filter( 'manage_posts_custom_column', 're_user_balance_posts_custom_column', 15, 2 );

    // Добавляем колонки к списку пользователей
    add_filter( 'manage_users_columns', 're_users_admin_columns' );
    add_filter( 'manage_users_custom_column', 're_users_admin_columns_content', 15, 3 );
}

/**
 * Подключаем скрипты к админке
 */
function re_js_enqueue_admin() {
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
    $post_fee_html .= '<input type="button" class="set_post_fee" id="set-post-fee-'.$post->ID.'" value="'.__('Назначить').'">';

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

/**
 * Добавляем колонки к списку пользователей
 * @param $columns
 * @return array
 */
function re_users_admin_columns($columns) {

    $result = array_merge( $columns,
        array( 'pay_requests' => "Запросы на выплаты" )
    );

    return $result;
}

/**
 * @param $custom_column
 * @param $column_name
 * @param $user_id
 * @return string
 */
function re_users_admin_columns_content( $custom_column, $column_name, $user_id ){

    $column_content = $custom_column;

    switch( $column_name ){
        case 'pay_requests':
            $pay_request = get_user_meta($user_id, '_pay_request', true);
            if($pay_request == RE_PAY_REQUEST_SET) {
                $column_content = '<span class="pay-request-'.$user_id.'">'.__('Заявка на выплату').'</span><br />';
                $column_content .= '<input type="button" class="pay-request-accept" id="pay-request-user-'.$user_id.'-accept" value="'.__('Удовлетворить').'">' ;
                $column_content .= '&nbsp;<input type="button" class="pay-request-decline" id="pay-request-user-'.$user_id.'-decline" value="'.__('Отклонить').'">' ;
            } elseif($pay_request == RE_PAY_REQUEST_ACCEPTED) {
                $column_content = '<span class="pay-request-'.$user_id.'">'.__('Заявка удовлетворена').'</span><br />';
            } elseif($pay_request == RE_PAY_REQUEST_DECLINED) {
                $column_content = '<span class="pay-request-'.$user_id.'">'.__('Заявка отклонена').'</span><br />';
            }
            break;
    }

    return $column_content;
}

/**
 * Обработка AJAX-запроса на назначение вознаграждения
 */
function re_ajax_set_post_fee() {

    global $wpdb;

    $post_id = isset($_POST['post_id']) ? $_POST['post_id'] : false;
    $post_fee = isset($_POST['post_fee']) ? $_POST['post_fee'] : 0;

    $answer = array(
        'result' => 'false',
        'error_msg' => __(''),
        'post_id' => $post_id,
        'post_fee' => $post_fee,
//        'set_post_fee_nonce' => $_POST['set_post_fee_nonce'],
//        '_wp_http_referer' => $_POST['_wp_http_referer'],
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
            $answer['error_msg'] = __('Не найдено публикации с указанным ID.');
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
            }
        } else {
            $answer['result'] = 'false';
            $answer['error_msg'] = __('Значение вознаграждения не изменилось.');
            echo json_encode($answer);
            exit;
        }

        // Корректируем личный счет
        $post = get_post($post_id);
        $user_count = $wpdb->get_var("SELECT count FROM ".RMAG_PREF ."user_count WHERE user = '$post->post_author'");
        $answer['old_user_count'] = $user_count;
        if($prev_post_fee) {
            $user_count -= $prev_post_fee;
        }
        $user_count += $post_fee;
        $answer['new_user_count'] = $user_count;

        $wpdb->update(RMAG_PREF.'user_count', array('count' => $user_count), array('user' => $post->post_author));

        $user_count = $wpdb->get_var("SELECT count FROM ".RMAG_PREF ."user_count WHERE user = '$post->post_author'");
        $answer['new_user_count_check'] = $user_count;

        $answer['result'] = 'true';
    } else {
        $answer['error_msg'] = __('Не указан ID публикации.');
        echo json_encode($answer);
        exit;
    }

    echo json_encode($answer);
    exit;
}
add_action('wp_ajax_re_set_post_fee', 're_ajax_set_post_fee');
//add_action('wp_ajax_nopriv_re_set_post_fee', 're_ajax_set_post_fee');

function re_ajax_pay_request_satisfaction() {

    $answer = array(
        'result' => 'false',
        'error_msg' => '',
        'msg' => '',
        'decision' => $_POST['decision'],
        'user_id' => $_POST['user_id'],
    );

    if($answer['decision'] == 'accept') {
        //@todo Собственно выплаты через функции mag-recall-modul
        update_user_meta($answer['user_id'], '_pay_request', RE_PAY_REQUEST_ACCEPTED);
        $answer['msg'] = __('Заявка удовлетворена.');
        $answer['result'] = 'true';
    } else {
        update_user_meta($answer['user_id'], '_pay_request', RE_PAY_REQUEST_DECLINED);
        $answer['msg'] = __('Заявка отклонена.');
        $answer['result'] = 'true';
    }

    echo json_encode($answer);
    exit;
}
add_action('wp_ajax_re_pay_request_satisfaction', 're_ajax_pay_request_satisfaction');
//remove debug function and hook
/**
 * Выводим в консоль для определения нужных параметров
 */
/*add_action('in_admin_header', 'my_get_current_screen');
function my_get_current_screen(){
    $screen_info = get_current_screen();

    fb('$screen_info = '.print_r($screen_info, true));
}*/

/**
 * Подключаем скрипты
 */
function re_js_enqueue() {
    wp_enqueue_script( 'jquery' );
    wp_enqueue_script( 'recall-extensions', plugins_url('js/recall-extensions.js', __FILE__) );
}
add_action('wp_enqueue_scripts', 're_js_enqueue');

/**
 * Обработка AJAX/JSON запроса на выплату
 */
function re_ajax_pay_request() {

    global $user_ID;

    $answer = array(
        'result' => 'false',
        'error_msg' => '',
        'msg' => '',
    );

    if(update_user_meta($user_ID, '_pay_request', RE_PAY_REQUEST_SET)) {
        $answer['result'] = 'true';
        $answer['msg'] = __('Заявка принята к рассмотрению');
    }

    echo json_encode($answer);
    exit;
}
add_action('wp_ajax_re_pay_request', 're_ajax_pay_request');

