<?php
/**
 * Created by PhpStorm.
 * User: shimabukuroyuuta
 * Date: 2018/03/08
 * Time: 16:44
 */
if (!function_exists('e')) {
    function e(string $s): string {
        return htmlspecialchars($s, ENT_QUOTES, 'UTF-8', false);
    }
}
