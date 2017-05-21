<?php

/**
 *      [CodeZm!] Author CodeZm[codezm@163.com].
 *
 *      SSH 处理程序
 *      $Id: host.php 2017-05-21 10:33:29 codezm $
 */

if ( !isset($query) ) $query = $argv[1];

/**
 * mode 0 copy HostName
 * mode 1 copy dbpassword
 * mode 2 copy dbname
 * mode 3 copy comment
 *
 */
if ( !isset($mode) ) $mode = $argv[2];

$query = str_replace('#%', ' ', $query);
$query = explode(',', $query);
echo $query[$mode];
exit;
