<?php
/**
 * @file        class-re-users-list-table.php
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
 * @created     12.11.13
 */

class RE_Users_List_Table extends WP_Users_List_Table {

    function get_columns() {

        $columns = parent::get_columns();
        unset($columns['cb']);
        $columns['pay_request_date'] = __('Дата запроса');
        $columns['balance_user_recall'] = __('Сумма на балансе');
        $columns['pay_request_accept'] = __('Удовлетворить');
        $columns['pay_request_decline'] = __('Отклонить');

        return $columns;
    }

}