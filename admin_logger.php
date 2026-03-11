<?php
class AdminLogger {
    /**
     * 记录管理员操作
     * @param string $username 用户名
     * @param string $action 操作描述
     */
    public function logAction($username, $action) {
        $logDir = 'logs';
        if (!file_exists($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        $logFilePath = $logDir . '/admin_actions.log';
        $time = date('Y-m-d H:i:s');
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        
        $logEntry = "$time|$ip|$username|$action" . PHP_EOL;
        
        file_put_contents($logFilePath, $logEntry, FILE_APPEND);
    }
    
    /**
     * 获取日志记录
     * @param int $limit 每页显示条数
     * @param int $offset 偏移量
     * @return array 日志数组
     */
    public function getLogs($limit = 200, $offset = 0) {
        $logFilePath = 'logs/admin_actions.log';
        $logs = [];
        
        if (!file_exists($logFilePath)) {
            return $logs;
        }
        
        $lines = file($logFilePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        // 反转数组，使最新的日志在前面
        $lines = array_reverse($lines);
        
        // 应用分页
        $lines = array_slice($lines, $offset, $limit);
        
        foreach ($lines as $line) {
            $parts = explode('|', $line, 4);
            if (count($parts) === 4) {
                $logs[] = [
                    'time' => $parts[0],
                    'ip' => $parts[1],
                    'username' => $parts[2],
                    'action' => $parts[3]
                ];
            }
        }
        
        return $logs;
    }
    
    /**
     * 获取日志总条数
     * @return int 日志总条数
     */
    public function getLogCount() {
        $logFilePath = 'logs/admin_actions.log';
        if (!file_exists($logFilePath)) {
            return 0;
        }
        
        $lines = file($logFilePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        return count($lines);
    }
}