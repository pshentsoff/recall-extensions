/**
 * Javascript source file
 * @file        recall-extensions.js
 * @description
 *
 * @package     recall-extensions
 * @category
 * @copyright   2013, Vadim Pshentsov. All Rights Reserved.
 * @license     http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 * @author      Vadim Pshentsov <pshentsoff@gmail.com>
 * @link        http://pshentsoff.ru Author's homepage
 * @link        http://blog.pshentsoff.ru Author's blog
 *
 * @created     11.11.13
 */

jQuery(document).ready(function() {
    /* Добавляем кнопку заказа выплаты в виджет личного счета */
    if(jQuery('.usercount')) {
        jQuery('.usercount').append('&nbsp;<input type="button" id="re_pay_request" value="Заказать выплату"/>');

        jQuery('#re_pay_request').click(function(){

            var data = 'action=re_pay_request';

            jQuery.ajax({
                type: 'POST',
                data: data,
                dataType: 'json',
                url: '/wp-admin/admin-ajax.php',
                success: function(data){
                    if(data['result'] == 'true'){
                        alert(data['msg']);
                    } else {
                        alert(data['error_msg']);
                    }
                }
            });

        });
    }

    if(jQuery('.single')) {
        jQuery('.single').html('Профиль пользователя');
    }

});
