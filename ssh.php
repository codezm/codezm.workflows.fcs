<?php

/**
 *      [CodeZm!] Author CodeZm[codezm@163.com].
 *
 *      ~/.ssh/config 解析程序
 *      $Id: host.php 2017-05-21 10:33:29 codezm $
 */

require_once 'workflows.php';
$w = new Workflows();

if ( !isset($query) ) $query = $argv[1];

$ico_png = 'icon.png';
$home = getenv('HOME');
$sshRoot = $home . '/.ssh/';
$config = file($sshRoot . '/config');

$hosts = array();
$cur_host = '';
$cur_hostname = '';
$cur_comment = '';
$password = getenv('ssh_password') ?:'#Password';
$db = getenv('ssh_db') ?:'#DB';
$curHost = $emptyHost = array(
    'host' => '',
    'hostname' => '', 
    'user' => '', 
    'password' => '', 
    'comment' => '', 
    'dbuser' => '', 
    'dbpassword' => '', 
);
foreach ( $config as $i => $configLine ) {
    switch(1) {
        case preg_match('/^\#\s?(.*)\s*$/', $configLine, $m) == 1:
            // The deal with previous Host.
            if($curHost['host']) {
                $hosts[] = $curHost;
                $curHost = $emptyHost;
            }

            $curHost['comment'] = $m[1];
            break;
        case preg_match('/^\s*host\s+(\S*)\s*$/i', $configLine, $m) == 1:
            // The deal with previous Host.
            if($curHost['host']) {
                $hosts[] = $curHost;
                $curHost = $emptyHost;
            }

            $curHost['host'] = $m[1];
            break;
        case preg_match('/^\s*hostname\s+(\S*)\s*$/i', $configLine, $m) == 1 :
            $curHost['hostname'] = $m[1];
            break;
        case preg_match('/^\s*user\s+(\S*)\s*$/i', $configLine, $m) == 1:
            $curHost['user'] = $m[1];
            break;
        case preg_match('/^\s*' . $password . '\s+(\S*)\s*$/i', $configLine, $m) == 1:
            $curHost['password'] = $m[1];
            break;
        case preg_match('/^\s*' . $db . '\s+(.*):(\S*)\s*$/i', $configLine, $m) == 1:
            $curHost['dbuser'] = $m[1];
            $curHost['dbpassword'] = $m[2];
            break;
        default:
            break;
    }
}

// Add the last host data.
if($curHost['host']) {
    $hosts[] = $curHost;
}

// Result data.
$result = array();

if(empty($hosts)) {
    $w->result('config配置文件无数据', '', '未找到任何配置信息', '请您先在 ~/.ssh/config 配置文件中添加服务器信息', $ico_png);
    echo $w->toxml();
    exit;
}

// Output result
$i = 0;
foreach ($hosts as $key => $item) {
    if($i > 8) {
        break;
    }
    if(($isHostname = (strpos($item['hostname'], $query) !== false)) || strpos($item['host'], $query) !== false) {
        $password = array(
            $item['hostname'], 
            $item['dbpassword'], 
            $item['dbuser'], 
            $item['comment'], 
        );
        $password = implode(',', $password);
        $password .= ',ssh ' . ($isHostname ? ($item['user'] . '@' . $item['hostname']) : $item['host']);
        if(!empty($item['password'])) {
            //$password = 'echo "' . $item['password'] . '" | pbcopy && ';
            $password .= ',' . $item['password'];
        }
        $w->result(
            $i, 
            str_replace(' ', '#%', $password), 
            $item['host'], 
            'ssh ' . ($isHostname ? ($item['user'] . '@' . $item['hostname']) : $item['host'])
            . ($isHostname ? '' : (' | ' . $item['hostname']))
            . (!empty($item['comment']) ? (' | ' . $item['comment']) : ''), 
            $ico_png, 
            'yes', 
            '', 
            'codezm'
        );
        $i++;
    }
}

if(empty($w->results())) {
    $w->result('未找到与之相匹配的数据', '', '未找到任何配置信息', '请您先在 ~/.ssh/config 配置文件中添加服务器信息', $ico_png);
    exit;
}
echo $w->toxml();
