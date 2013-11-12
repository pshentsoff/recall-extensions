/**
 * Javascript source file
 * @file        admin.js
 * @description
 *
 * @package     admin
 * @category
 * @copyright   2013, Vadim Pshentsov. All Rights Reserved.
 * @license     http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 * @author      Vadim Pshentsov <pshentsoff@gmail.com>
 * @link        http://pshentsoff.ru Author's homepage
 * @link        http://blog.pshentsoff.ru Author's blog
 *
 * @created     10.11.13
 */

jQuery(function(){
    /* Вознаграждение за публикацию */
    jQuery('.set_post_fee').live('click',function(){
        var id_attr = jQuery(this).attr('id');
        var post_id = parseInt(id_attr.replace(/\D+/g,''));
        var fee = jQuery('.post-fee-'+post_id).attr('value');
        var data = 'action=re_set_post_fee&post_id='+post_id+'&post_fee='+fee;

        jQuery.ajax({
            type: 'POST',
            data: data,
            dataType: 'json',
            url: '/wp-admin/admin-ajax.php',
            success: function(data){
                if(data['result'] == 'true'){
//                    if(data['error_msg'] != '') alert(data['error_msg']);
                    jQuery('.post-fee-'+data['post_id']).val(data['post_fee']);
//                    alert('fee = '+data['post_fee']);
//                    alert('old_user_count = '+data['old_user_count']);
//                    alert('new_user_count = '+data['new_user_count']);
//                    alert('new_user_count_check = '+data['new_user_count_check']);
                } else {
                    alert(data['error_msg']);
                }
            }
        });
        return false;
    });

    /* Выплата вознаграждения за публикацию */
    jQuery('.pay-request-accept').live('click',function(){
        ajax_pay_request_decision(jQuery(this).attr('id'), 'accept');
        return false;
    });

    /* Отказ в выплате вознаграждения за публикацию */
    jQuery('.pay-request-decline').live('click',function(){
        ajax_pay_request_decision(jQuery(this).attr('id'), 'decline');
        return false;
    });

    ajax_pay_request_decision = function(id_attr, decision) {

        var user_id = parseInt(id_attr.replace(/\D+/g,''));
        var data = 'action=re_pay_request_satisfaction&decision='+decision+'&user_id='+user_id;

        jQuery.ajax({
            type: 'POST',
            data: data,
            dataType: 'json',
            url: '/wp-admin/admin-ajax.php',
            success: function(data){
                if(data['result'] == 'true'){
                    if(data['decision'] == 'accept') {
                        jQuery('.pay-request-'+data['user_id']).html('<span class="pay-request-accepted">'+data['msg']+'</span>');
                    } else {
                        jQuery('.pay-request-'+data['user_id']).html(data['msg']);
                    }
                    jQuery('#pay-request-user-'+data['user_id']+'-accept').remove();
                    jQuery('#pay-request-user-'+data['user_id']+'-decline').remove();
                    jQuery('.balance-'+data['user_id']).html('0');
                } else {
                    alert(data['error_msg']);
                }
            }
        });
    }

    jQuery(document).ready(function() {
        //
    });
});