<?php

/**
 *      [CodeZm!] Author CodeZm[codezm@163.com].
 *
 *      ~/.ssh/config 解析程序
 *      $Id: host.php 2017-05-21 10:33:29 codezm $
 */

require_once 'workflows.php';
$w = new Workflows();
// Get query parameter.
if (!isset($query)) {
    $query = $argv[1];
}

$ico_png = 'icon.png';
$sshConfigFile = getenv('HOME') . '/.ssh/config';
$sshConfigModifyTime = filemtime($sshConfigFile);
if(!$sshConfigModifyTime) {
    $w->result('not find ~/.ssh/config file.', '', 'Not find "~/.ssh/config" file', 'Error: Not Find Configurate File.', $ico_png);
    echo $w->toxml();
    exit;
}

// Clean up ssh cache.
if (strtolower($query) == 'clean') {
    $w->write(array(), 'ssh');
    $w->result('', '', '已清除配置缓存', 'Clean up cache over for ssh config.', $ico_png);
    echo $w->toxml();
    exit;
}

// Storage ssh cache.
$cacheData = $w->read('ssh');
if (!$cacheData || $sshConfigModifyTime != $cacheData['mtime']) {
    $cacheData = array(
        'hosts' => array(
        ), 
        'mtime' => $sshConfigModifyTime
    );
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
    $config = file($sshConfigFile);
    foreach ( $config as $i => $configLine ) {
        switch(1) {
            case preg_match('/^\#\s?(.*)\s*$/', $configLine, $m) == 1:
                // The deal with previous Host.
                if($curHost['host']) {
                    $cacheData['hosts'][] = $curHost;
                    $curHost = $emptyHost;
                }

                $curHost['comment'] = $m[1];
                break;
            case preg_match('/^\s*host\s+(\S*)\s*$/i', $configLine, $m) == 1:
                // The deal with previous Host.
                if($curHost['host']) {
                    $cacheData['hosts'][] = $curHost;
                    $curHost = $emptyHost;
                }

                $curHost['host'] = $m[1];
                break;
            case preg_match('/^\s*IdentityFile\s+(\S*)\s*$/i', $configLine, $m) == 1 :
                $curHost['IdentityFile'] = $m[1];
                break;
            case preg_match('/^\s*Port\s+(\S*)\s*$/i', $configLine, $m) == 1 :
                $curHost['port'] = $m[1];
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
        $cacheData['hosts'][] = $curHost;
    }

    // Verify host data.
    if(empty($cacheData['hosts'])) {
        $w->result('config配置文件无数据', '', '未找到任何配置信息', '请您先在 ~/.ssh/config 配置文件中添加服务器信息', $ico_png);
        echo $w->toxml();
        exit;
    }

    $w->write($cacheData, 'ssh');
}

// Search query and Output query result.
$i = 0;
foreach ($cacheData['hosts'] as $key => $item) {
    if($i > 8) {
        break;
    }
    if(($isHostname = (strpos($item['hostname'], $query) !== false)) || strpos($item['host'], $query) !== false) {
        $sshCommandLine = 'ssh ' . ($isHostname ? 
            (
                (isset($item['IdentityFile']) ? ('-i ' . $item['IdentityFile'] . ' ') : '') 
                . (isset($item['port']) ? ('-p ' . $item['port'] . ' ') : '')
                . $item['user'] . '@' . $item['hostname']
            )
            : $item['host']);

        $transmitData = array(
            $item['hostname'], 
            $item['dbpassword'], 
            $item['dbuser'], 
            $item['comment'], 
            $sshCommandLine, 
            $item['password']
        );
        $transmitData = implode(',', $transmitData);
        $w->result(
            $i, 
            str_replace(' ', '#%', $transmitData), 
            $item['host'], 
            $sshCommandLine
            . ($isHostname ? '' : (' | ' . $item['hostname']))
            . (isset($item['comment']) ? (' | ' . $item['comment']) : ''), 
            $ico_png, 
            'yes', 
            '', 
            'codezm'
        );
        $i++;
    }
}
echo $w->toxml();
