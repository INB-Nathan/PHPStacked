CREATE DATABASE IF NOT EXISTS PHPStacked_DB;
USE PHPStacked_DB;

CREATE TABLE elections (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    start_date DATETIME NOT NULL,
    end_date DATETIME NOT NULL,
    status ENUM('upcoming', 'active', 'completed', 'cancelled') DEFAULT 'upcoming',
    max_votes_per_user INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT chk_election_dates CHECK (end_date > start_date)
);

CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    pass_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    user_type ENUM('admin', 'voter') DEFAULT 'voter',
    is_active BOOLEAN DEFAULT TRUE,
    last_login TIMESTAMP NULL,
    session_id VARCHAR(128) NULL,
    session_expires TIMESTAMP NULL,
    ip_address VARCHAR(45) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE parties (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL UNIQUE,
    description VARCHAR(255) DEFAULT NULL
);

CREATE TABLE candidates (
    id INT PRIMARY KEY AUTO_INCREMENT,
    election_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    position VARCHAR(255) NOT NULL,
    party_id INT NULL,
    bio TEXT NULL,
    photo VARCHAR(255) NULL,
    platform TEXT NULL,
    vote_count INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (election_id) REFERENCES elections(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (party_id) REFERENCES parties(id) ON DELETE SET NULL ON UPDATE CASCADE
);

CREATE TABLE votes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    election_id INT NOT NULL,
    candidate_id INT NOT NULL,
    user_id INT NULL,
    voter_name VARCHAR(255) NOT NULL,
    voter_ip VARCHAR(45) NOT NULL,
    user_agent TEXT NULL,
    vote_timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_verified BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (election_id) REFERENCES elections(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (candidate_id) REFERENCES candidates(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE
);

INSERT INTO elections (id, title, description, start_date, end_date, status) VALUES
(1, 'Sample General Election 2025', 'A general election for all positions.', '2025-01-01 08:00:00', '2025-12-31 17:00:00', 'upcoming');

INSERT INTO `users` (`id`, `username`, `email`, `pass_hash`, `full_name`, `user_type`, `is_active`, `last_login`, `session_id`, `session_expires`, `ip_address`, `created_at`, `updated_at`) VALUES (NULL, 'admin', 'admin@account.com', '$2y$10$m82y2FF7itrlvuuHYdIXLec62Bs.BHkBfMSPdAluzlMogSBGj7gT.', 'Admin', 'admin', '1', NULL, NULL, NULL, NULL, '2025-06-27 21:55:52', '2025-06-27 21:56:33');

