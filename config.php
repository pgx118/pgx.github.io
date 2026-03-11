<?php
// 默认配置
$defaultConfig = [
    'security' => [
        'login_attempts' => 5,
        'session_timeout' => 30,
        'ip_restriction' => 'none',
        'allowed_file_types' => 'jpg,jpeg,png,gif,pdf,doc,docx,xls,xlsx,ppt,pptx,txt,zip,rar',
        'enable_2fa' => false
    ],
    'performance' => [
        'cache_enabled' => 'file',
        'cache_lifetime' => 3600,
        'max_upload_size' => 100,
        'concurrent_uploads' => 5,
        'enable_gzip' => true
    ],
    'storage' => [
        'storage_type' => 'local',
        'storage_path' => 'uploads/',
        'storage_quota' => 0,
        'auto_cleanup' => false,
        'cleanup_days' => 30
    ],
    'maintenance' => [
        'maintenance_mode' => 'disabled',
        'maintenance_message' => '系统正在维护中，请稍后再试。',
        'backup_schedule' => 'disabled',
        'backup_location' => 'backups/',
        'enable_logging' => true
    ]
];

// 配置文件路径
$configFile = 'config.json';

// 加载或创建配置文件
if (file_exists($configFile)) {
    $config = json_decode(file_get_contents($configFile), true);
    if (!is_array($config)) {
        $config = $defaultConfig;
        file_put_contents($configFile, json_encode($defaultConfig, JSON_PRETTY_PRINT));
    }
} else {
    $config = $defaultConfig;
    file_put_contents($configFile, json_encode($defaultConfig, JSON_PRETTY_PRINT));
}

// 合并默认配置和用户配置
$config = array_replace_recursive($defaultConfig, $config);
?>