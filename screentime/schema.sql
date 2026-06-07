CREATE TABLE IF NOT EXISTS screen_time_targets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL,
    hostname VARCHAR(255) NOT NULL,
    allowed_until DATETIME NULL,
    last_probe_at DATETIME NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_screen_time_user_host (username, hostname),
    KEY idx_screen_time_last_probe (last_probe_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
