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
 * @link        http://blog.pshentsoff.ru Author'slog
 *
 * @created     10.11.13
 */

jQuery(function(){
    /* Вознаграждение за публикацию */
    jQuery('.set_post_fee').live('click',function(){
        var id_attr = jQuery(this).attr('id');
        var post_id = parseInt(id_attr.replace(/\D+/g,''));
        var fee = jQuery('.post-fee-'+post_id).attr('value');
        var dataString_count = 'action=re_set_post_fee&post_id='+post_id+'&fee='+fee;

        jQuery.ajax({
            type: 'POST',
            data: dataString_count,
            dataType: 'json',
            url: '/wp-admin/admin-ajax.php',
            success: function(data){
                if(data['result'] == 'true'){
                    jQuery('.post-fee-'+data['post_id']).val(data['post_fee']);
                } else {
                    alert(data['error_msg']);
                }
            }
        });
        return false;
    });

});