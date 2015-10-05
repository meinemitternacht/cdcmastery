<?php
/**
 * Created by PhpStorm.
 * User: Claude Bing
 * Date: 10/5/2015
 * Time: 11:09 AM
 */

echo $session->get_active_sessions();
$session->gc();
echo "<br>";
echo $session->get_active_sessions();