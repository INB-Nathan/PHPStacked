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

CREATE INDEX idx_username ON users(username);
CREATE INDEX idx_email ON users(email);
CREATE INDEX idx_session_id ON users(session_id);
CREATE INDEX idx_user_type ON users(user_type);

CREATE TABLE candidates (
    id INT PRIMARY KEY AUTO_INCREMENT,
    election_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    position VARCHAR(255) NOT NULL,
    party_name VARCHAR(255) NULL,
    bio TEXT NULL,
    photo VARCHAR(255) NULL,
    platform TEXT NULL,
    vote_count INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT chk_vote_count CHECK (vote_count >= 0),
    FOREIGN KEY (election_id) REFERENCES elections(id) ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE INDEX idx_election_id ON candidates(election_id);
CREATE INDEX idx_position ON candidates(position);
CREATE INDEX idx_party_name ON candidates(party_name);

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

CREATE INDEX idx_election_id_votes ON votes(election_id);
CREATE INDEX idx_candidate_id_votes ON votes(candidate_id);
CREATE INDEX idx_user_id_votes ON votes(user_id);
CREATE INDEX idx_voter_ip ON votes(voter_ip);
CREATE INDEX idx_vote_timestamp ON votes(vote_timestamp);

ALTER TABLE votes ADD CONSTRAINT unique_user_vote UNIQUE (election_id, user_id, candidate_id);
ALTER TABLE votes ADD CONSTRAINT unique_anonymous_vote UNIQUE (election_id, voter_name, voter_ip, candidate_id);

DELIMITER //

CREATE TRIGGER update_vote_count_after_insert
AFTER INSERT ON votes
FOR EACH ROW
BEGIN
    UPDATE candidates 
    SET vote_count = vote_count + 1 
    WHERE id = NEW.candidate_id;
END//

CREATE TRIGGER update_vote_count_after_delete
AFTER DELETE ON votes
FOR EACH ROW
BEGIN
    UPDATE candidates 
    SET vote_count = vote_count - 1 
    WHERE id = OLD.candidate_id AND vote_count > 0;
END//

DELIMITER ;

CREATE VIEW active_elections AS
SELECT * FROM elections 
WHERE status = 'active' 
AND NOW() BETWEEN start_date AND end_date;

CREATE VIEW election_results AS
SELECT 
    e.id as election_id,
    e.title as election_title,
    c.id as candidate_id,
    c.name as candidate_name,
    c.position,
    c.party_name,
    c.vote_count,
    ROUND((c.vote_count * 100.0 / NULLIF(total_votes.total, 0)), 2) as percentage
FROM candidates c
JOIN elections e ON c.election_id = e.id
LEFT JOIN (
    SELECT election_id, SUM(vote_count) as total
    FROM candidates
    GROUP BY election_id
) total_votes ON c.election_id = total_votes.election_id
ORDER BY c.election_id, c.position, c.vote_count DESC;