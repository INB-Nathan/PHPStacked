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

CREATE TABLE positions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    position_name VARCHAR(100) NOT NULL,
    election_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (election_id) REFERENCES elections(id) ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    pass_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    user_type ENUM('admin', 'voter') DEFAULT 'voter',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
ALTER TABLE users AUTO_INCREMENT= 202500000;

CREATE TABLE parties (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL UNIQUE,
    description VARCHAR(255) DEFAULT NULL,
    election_id INT NULL,
    FOREIGN KEY (election_id) REFERENCES elections(id) ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE candidates (
    id INT PRIMARY KEY AUTO_INCREMENT,
    election_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    position_id INT NOT NULL,
    party_id INT NULL,
    bio TEXT NULL,
    photo VARCHAR(255) NULL,
    platform TEXT NULL,
    vote_count INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (election_id) REFERENCES elections(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (party_id) REFERENCES parties(id) ON DELETE SET NULL ON UPDATE CASCADE,
    FOREIGN KEY (position_id) REFERENCES positions(id) ON DELETE CASCADE ON UPDATE CASCADE
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

CREATE TABLE voter_elections (
    voter_id INT NOT NULL,
    election_id INT NOT NULL,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (voter_id, election_id),
    FOREIGN KEY (voter_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (election_id) REFERENCES elections(id) ON DELETE CASCADE ON UPDATE CASCADE
);

-- Seed data
INSERT INTO parties (name, description)
VALUES ('Independent', 'Independent Candidate');

-- Test admin user admin pogiako123
INSERT INTO users (id,username, email, pass_hash, full_name, user_type)
VALUES (1,'admin', 'admin@gmail.com', '$2y$10$hc.hkHLCeO91bZIp7BB4RuFhSvBjJNLd2AI7rngONlJVQDvdQLhOK', 'adminako', 'admin');