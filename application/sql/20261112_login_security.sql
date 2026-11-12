CREATE TABLE login_attempt (
    ip_address VARCHAR(45) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    window_start DATETIME NOT NULL,
    attempts INT UNSIGNED NOT NULL,
    PRIMARY KEY (ip_address),
    KEY idx_login_attempt_window (window_start)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
