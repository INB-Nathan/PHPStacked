-- Populate PHPStacked_DB with sample data
USE PHPStacked_DB;

-- Clear existing data (optional - comment out if you want to keep existing data)
SET FOREIGN_KEY_CHECKS = 0;
-- Using DELETE instead of TRUNCATE to avoid issues with foreign key constraints
DELETE FROM votes;
DELETE FROM voter_elections;
DELETE FROM candidates;
DELETE FROM positions;
DELETE FROM parties WHERE name != 'Independent';
DELETE FROM elections;
-- Only after clearing dependent tables, you can clear these if needed
-- DELETE FROM users WHERE user_type != 'admin';
SET FOREIGN_KEY_CHECKS = 1;

-- Add sample elections FIRST (since other tables reference these IDs)
INSERT INTO elections (title, description, start_date, end_date, status, max_votes_per_user) VALUES
('Student Council Election 2025', 'Annual election for student council positions', '2025-07-10 08:00:00', '2025-07-15 17:00:00', 'upcoming', 1),
('Department Representatives Election', 'Election for department representatives', '2025-07-01 08:00:00', '2025-07-03 17:00:00', 'completed', 1),
('Campus Improvement Committee', 'Election for campus improvement committee members', '2025-06-15 08:00:00', '2025-06-20 17:00:00', 'completed', 1),
('Student Budget Allocation Vote', 'Vote on allocation of student activity funds', '2025-08-01 08:00:00', '2025-08-05 17:00:00', 'upcoming', 1);

-- Add more parties
INSERT INTO parties (name, description) VALUES
('Student Progress Party', 'Advocating for student welfare and educational improvements'),
('Academic Excellence League', 'Focusing on academic standards and quality education'),
('Campus Unity Coalition', 'Building bridges across diverse student groups'),
('Student Reform Movement', 'Modernizing student policies and campus facilities');

-- Add positions for the elections
INSERT INTO positions (position_name, election_id) VALUES
-- Student Council positions
('President', 1),
('Vice President', 1),
('Secretary', 1),
('Treasurer', 1),
('Public Relations Officer', 1),
-- Department Reps positions
('Science Department Rep', 2),
('Humanities Department Rep', 2),
('Business Department Rep', 2),
('Engineering Department Rep', 2),
-- Campus Improvement Committee positions
('Facilities Member', 3),
('Environmental Member', 3),
('Technology Member', 3),
-- Budget allocation doesn't need positions as it's for fund allocation
('Budget Oversight Chair', 4),
('Budget Secretary', 4);

-- Add more sample users (voters)
INSERT INTO users (username, email, pass_hash, full_name, user_type) VALUES
-- Password for all test accounts is 'pogiako123'
('voter1', 'voter1@example.com', '$2y$10$UWpr62nXiqikvdtJPi/KmOB4QNyw2Cu51D8sd3oCkzsIxCiFAQ7kS', 'John Doe', 'voter'),
('voter2', 'voter2@example.com', '$2y$10$UWpr62nXiqikvdtJPi/KmOB4QNyw2Cu51D8sd3oCkzsIxCiFAQ7kS', 'Jane Smith', 'voter'),
('voter3', 'voter3@example.com', '$2y$10$UWpr62nXiqikvdtJPi/KmOB4QNyw2Cu51D8sd3oCkzsIxCiFAQ7kS', 'Robert Johnson', 'voter'),
('voter4', 'voter4@example.com', '$2y$10$UWpr62nXiqikvdtJPi/KmOB4QNyw2Cu51D8sd3oCkzsIxCiFAQ7kS', 'Sarah Williams', 'voter'),
('voter5', 'voter5@example.com', '$2y$10$UWpr62nXiqikvdtJPi/KmOB4QNyw2Cu51D8sd3oCkzsIxCiFAQ7kS', 'Michael Brown', 'voter'),
('voter6', 'voter6@example.com', '$2y$10$UWpr62nXiqikvdtJPi/KmOB4QNyw2Cu51D8sd3oCkzsIxCiFAQ7kS', 'Emily Davis', 'voter'),
('voter7', 'voter7@example.com', '$2y$10$UWpr62nXiqikvdtJPi/KmOB4QNyw2Cu51D8sd3oCkzsIxCiFAQ7kS', 'David Miller', 'voter'),
('voter8', 'voter8@example.com', '$2y$10$UWpr62nXiqikvdtJPi/KmOB4QNyw2Cu51D8sd3oCkzsIxCiFAQ7kS', 'Maria Garcia', 'voter'),
('voter9', 'voter9@example.com', '$2y$10$UWpr62nXiqikvdtJPi/KmOB4QNyw2Cu51D8sd3oCkzsIxCiFAQ7kS', 'James Wilson', 'voter'),
('voter10', 'voter10@example.com', '$2y$10$UWpr62nXiqikvdtJPi/KmOB4QNyw2Cu51D8sd3oCkzsIxCiFAQ7kS', 'Patricia Moore', 'voter');

-- Add candidates for Student Council Election
INSERT INTO candidates (election_id, name, position_id, party_id, bio, platform, vote_count) VALUES
-- President candidates
(1, 'Alex Chen', 1, 2, 'Senior Computer Science major with leadership experience in multiple student organizations', 'Implementing online learning resources and more study spaces', 0),
(1, 'Jordan Patel', 1, 3, 'Political Science junior with previous experience as class representative', 'Creating a more inclusive campus community and expanding student services', 0),
(1, 'Casey Smith', 1, 1, 'Business Administration senior with internship experience at top companies', 'Bringing real-world business practices to student government', 0),

-- Vice President candidates
(1, 'Taylor Johnson', 2, 2, 'Junior Psychology major and mental health advocate', 'Improving student mental health services and academic support', 0),
(1, 'Morgan Wright', 2, 4, 'Engineering sophomore with technical project management experience', 'Streamlining administrative processes and implementing new technologies', 0),

-- Secretary candidates
(1, 'Riley Adams', 3, 3, 'Communications junior with organizational experience', 'Improving communication between student government and student body', 0),
(1, 'Jamie Garcia', 3, 1, 'Liberal Arts sophomore with previous secretary experience', 'Maintaining transparent records and promoting student involvement', 0),

-- Treasurer candidates
(1, 'Dakota Lee', 4, 4, 'Finance senior with accounting background', 'Responsible budget management and funding for student initiatives', 0),
(1, 'Avery Wilson', 4, 2, 'Economics junior with previous club treasurer experience', 'Equitable distribution of resources across student organizations', 0),

-- PR Officer candidates
(1, 'Cameron Rivera', 5, 3, 'Marketing senior with social media management experience', 'Expanding student government reach through digital platforms', 0),
(1, 'Quinn Hernandez', 5, 1, 'Journalism sophomore with media connections', 'Building stronger relationships with campus and local media', 0);

-- Add candidates for Department Representatives Election (already completed)
INSERT INTO candidates (election_id, name, position_id, party_id, bio, platform, vote_count) VALUES
-- Science Department candidates
(2, 'Alexis Washington', 6, 2, 'Biology senior with research experience', 'Expanding research opportunities for undergraduates', 45),
(2, 'Jamie Fox', 6, 1, 'Chemistry junior with teaching assistant experience', 'Creating more accessible study resources for difficult courses', 38),

-- Humanities Department candidates
(2, 'Jordan Ellis', 7, 3, 'English Literature senior and writing center tutor', 'Promoting interdisciplinary studies and events', 52),
(2, 'Casey Thomas', 7, 4, 'History sophomore with community outreach experience', 'Connecting humanities students with community service opportunities', 41),

-- Business Department candidates
(2, 'Taylor Swift', 8, 1, 'Marketing junior with entrepreneurship experience', 'Creating networking opportunities with local businesses', 63),
(2, 'Morgan Stanley', 8, 2, 'Finance senior with internship connections', 'Bringing industry professionals for career development workshops', 59),

-- Engineering Department candidates
(2, 'Riley Cooper', 9, 4, 'Mechanical Engineering senior with project team leadership', 'Advocating for more hands-on project courses', 48),
(2, 'Avery Zhang', 9, 3, 'Computer Engineering junior with hackathon experience', 'Creating more interdisciplinary tech events and competitions', 55);

-- Add candidates for Campus Improvement Committee (already completed)
INSERT INTO candidates (election_id, name, position_id, party_id, bio, platform, vote_count) VALUES
-- Facilities Member candidates
(3, 'Skyler Rodriguez', 10, 2, 'Architecture senior with campus planning internship', 'Redesigning study spaces for better collaboration', 72),
(3, 'Harper Williams', 10, 3, 'Urban Planning junior with accessibility advocacy experience', 'Making campus more accessible for all students', 68),
(3, 'Jordan Kim', 10, 4, 'Civil Engineering senior with sustainable design background', 'Implementing green infrastructure on campus', 65),

-- Environmental Member candidates
(3, 'River Martinez', 11, 3, 'Environmental Science senior and sustainability club president', 'Expanding recycling programs and reducing campus waste', 82),
(3, 'Sage Thompson', 11, 1, 'Biology junior specializing in ecology', 'Creating more green spaces and native plant gardens', 79),
(3, 'Logan Garcia', 11, 2, 'Sustainability Studies sophomore with community garden experience', 'Implementing a campus composting system', 71),

-- Technology Member candidates
(3, 'Quinn Wilson', 12, 4, 'Computer Science senior with IT work experience', 'Improving campus wifi and technology resources', 88),
(3, 'Dakota Smith', 12, 2, 'Information Systems junior with app development background', 'Creating a comprehensive campus mobile app', 75),
(3, 'Charlie Brown', 12, 1, 'Digital Media senior with UX design portfolio', 'Redesigning digital interfaces for campus services', 68);

-- Add candidates for Budget Allocation Election (upcoming)
INSERT INTO candidates (election_id, name, position_id, party_id, bio, platform, vote_count) VALUES
-- Budget Oversight Chair candidates
(4, 'Phoenix Wright', 13, 3, 'Economics senior with student organization leadership', 'Ensuring transparent and equitable budget decisions', 0),
(4, 'Jordan Taylor', 13, 4, 'Accounting junior with treasury experience', 'Implementing clearer tracking and reporting of student funds', 0),
(4, 'Riley Johnson', 13, 2, 'Finance senior with analytical background', 'Using data-driven approaches to budget allocation', 0),

-- Budget Secretary candidates
(4, 'Cameron Davis', 14, 1, 'Business Administration junior with detail-oriented approach', 'Maintaining accessible records of all budget decisions', 0),
(4, 'Avery Martinez', 14, 3, 'Public Policy sophomore with note-taking skills', 'Providing detailed reports to student body about funding decisions', 0);

-- Assign voters to elections
INSERT INTO voter_elections (voter_id, election_id) VALUES
-- Student Council Election (all voters can participate)
(202500001, 1), (202500002, 1), (202500003, 1), (202500004, 1), (202500005, 1),
(202500006, 1), (202500007, 1), (202500008, 1), (202500009, 1), (202500010, 1),

-- Department Representatives Election (all voters participated)
(202500001, 2), (202500002, 2), (202500003, 2), (202500004, 2), (202500005, 2),
(202500006, 2), (202500007, 2), (202500008, 2), (202500009, 2), (202500010, 2),

-- Campus Improvement Committee (all voters participated)
(202500001, 3), (202500002, 3), (202500003, 3), (202500004, 3), (202500005, 3),
(202500006, 3), (202500007, 3), (202500008, 3), (202500009, 3), (202500010, 3),

-- Budget Allocation (some voters can participate)
(202500001, 4), (202500003, 4), (202500005, 4), (202500007, 4), (202500009, 4);

-- Add sample votes for completed elections
-- Department Representatives Election votes
INSERT INTO votes (election_id, candidate_id, user_id, voter_name, voter_ip, user_agent, is_verified) VALUES
-- Votes for Science Department
(2, 13, 202500001, 'John Doe', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)', TRUE),
(2, 13, 202500003, 'Robert Johnson', '127.0.0.1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7)', TRUE),
(2, 13, 202500005, 'Michael Brown', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)', TRUE),
(2, 13, 202500007, 'David Miller', '127.0.0.1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_7_1)', TRUE),
(2, 13, 202500009, 'James Wilson', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)', TRUE),
(2, 14, 202500002, 'Jane Smith', '127.0.0.1', 'Mozilla/5.0 (iPad; CPU OS 14_7_1)', TRUE),
(2, 14, 202500004, 'Sarah Williams', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)', TRUE),
(2, 14, 202500006, 'Emily Davis', '127.0.0.1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7)', TRUE),
(2, 14, 202500008, 'Maria Garcia', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)', TRUE),
(2, 14, 202500010, 'Patricia Moore', '127.0.0.1', 'Mozilla/5.0 (Linux; Android 11)', TRUE),

-- Votes for Humanities Department
(2, 15, 202500001, 'John Doe', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)', TRUE),
(2, 15, 202500002, 'Jane Smith', '127.0.0.1', 'Mozilla/5.0 (iPad; CPU OS 14_7_1)', TRUE),
(2, 15, 202500003, 'Robert Johnson', '127.0.0.1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7)', TRUE),
(2, 15, 202500005, 'Michael Brown', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)', TRUE),
(2, 15, 202500007, 'David Miller', '127.0.0.1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_7_1)', TRUE),
(2, 15, 202500009, 'James Wilson', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)', TRUE),
(2, 16, 202500004, 'Sarah Williams', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)', TRUE),
(2, 16, 202500006, 'Emily Davis', '127.0.0.1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7)', TRUE),
(2, 16, 202500008, 'Maria Garcia', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)', TRUE),
(2, 16, 202500010, 'Patricia Moore', '127.0.0.1', 'Mozilla/5.0 (Linux; Android 11)', TRUE);

-- Add more votes for Department Representatives Election (Business and Engineering departments)
INSERT INTO votes (election_id, candidate_id, user_id, voter_name, voter_ip, user_agent, is_verified) VALUES
-- Votes for Business Department
(2, 17, 202500001, 'John Doe', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)', TRUE),
(2, 17, 202500002, 'Jane Smith', '127.0.0.1', 'Mozilla/5.0 (iPad; CPU OS 14_7_1)', TRUE),
(2, 17, 202500003, 'Robert Johnson', '127.0.0.1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7)', TRUE),
(2, 17, 202500004, 'Sarah Williams', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)', TRUE),
(2, 17, 202500005, 'Michael Brown', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)', TRUE),
(2, 17, 202500006, 'Emily Davis', '127.0.0.1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7)', TRUE),
(2, 17, 202500007, 'David Miller', '127.0.0.1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_7_1)', TRUE),
(2, 18, 202500008, 'Maria Garcia', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)', TRUE),
(2, 18, 202500009, 'James Wilson', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)', TRUE),
(2, 18, 202500010, 'Patricia Moore', '127.0.0.1', 'Mozilla/5.0 (Linux; Android 11)', TRUE),

-- Votes for Engineering Department
(2, 19, 202500001, 'John Doe', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)', TRUE),
(2, 19, 202500003, 'Robert Johnson', '127.0.0.1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7)', TRUE),
(2, 19, 202500005, 'Michael Brown', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)', TRUE),
(2, 19, 202500007, 'David Miller', '127.0.0.1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_7_1)', TRUE),
(2, 20, 202500002, 'Jane Smith', '127.0.0.1', 'Mozilla/5.0 (iPad; CPU OS 14_7_1)', TRUE),
(2, 20, 202500004, 'Sarah Williams', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)', TRUE),
(2, 20, 202500006, 'Emily Davis', '127.0.0.1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7)', TRUE),
(2, 20, 202500008, 'Maria Garcia', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)', TRUE),
(2, 20, 202500009, 'James Wilson', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)', TRUE),
(2, 20, 202500010, 'Patricia Moore', '127.0.0.1', 'Mozilla/5.0 (Linux; Android 11)', TRUE);

-- Add votes for Campus Improvement Committee
INSERT INTO votes (election_id, candidate_id, user_id, voter_name, voter_ip, user_agent, is_verified) VALUES
-- Votes for Facilities Member candidates
(3, 21, 202500001, 'John Doe', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)', TRUE),
(3, 21, 202500002, 'Jane Smith', '127.0.0.1', 'Mozilla/5.0 (iPad; CPU OS 14_7_1)', TRUE),
(3, 21, 202500003, 'Robert Johnson', '127.0.0.1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7)', TRUE),
(3, 21, 202500004, 'Sarah Williams', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)', TRUE),
(3, 22, 202500005, 'Michael Brown', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)', TRUE),
(3, 22, 202500006, 'Emily Davis', '127.0.0.1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7)', TRUE),
(3, 22, 202500007, 'David Miller', '127.0.0.1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_7_1)', TRUE),
(3, 23, 202500008, 'Maria Garcia', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)', TRUE),
(3, 23, 202500009, 'James Wilson', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)', TRUE),
(3, 23, 202500010, 'Patricia Moore', '127.0.0.1', 'Mozilla/5.0 (Linux; Android 11)', TRUE),

-- Votes for Environmental Member candidates
(3, 24, 202500001, 'John Doe', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)', TRUE),
(3, 24, 202500002, 'Jane Smith', '127.0.0.1', 'Mozilla/5.0 (iPad; CPU OS 14_7_1)', TRUE),
(3, 24, 202500003, 'Robert Johnson', '127.0.0.1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7)', TRUE),
(3, 24, 202500004, 'Sarah Williams', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)', TRUE),
(3, 25, 202500005, 'Michael Brown', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)', TRUE),
(3, 25, 202500006, 'Emily Davis', '127.0.0.1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7)', TRUE),
(3, 25, 202500007, 'David Miller', '127.0.0.1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_7_1)', TRUE),
(3, 25, 202500008, 'Maria Garcia', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)', TRUE),
(3, 26, 202500009, 'James Wilson', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)', TRUE),
(3, 26, 202500010, 'Patricia Moore', '127.0.0.1', 'Mozilla/5.0 (Linux; Android 11)', TRUE),

-- Votes for Technology Member candidates
(3, 27, 202500001, 'John Doe', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)', TRUE),
(3, 27, 202500002, 'Jane Smith', '127.0.0.1', 'Mozilla/5.0 (iPad; CPU OS 14_7_1)', TRUE),
(3, 27, 202500003, 'Robert Johnson', '127.0.0.1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7)', TRUE),
(3, 27, 202500004, 'Sarah Williams', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)', TRUE),
(3, 27, 202500005, 'Michael Brown', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)', TRUE),
(3, 27, 202500006, 'Emily Davis', '127.0.0.1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7)', TRUE),
(3, 28, 202500007, 'David Miller', '127.0.0.1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_7_1)', TRUE),
(3, 28, 202500008, 'Maria Garcia', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)', TRUE),
(3, 28, 202500009, 'James Wilson', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)', TRUE),
(3, 29, 202500010, 'Patricia Moore', '127.0.0.1', 'Mozilla/5.0 (Linux; Android 11)', TRUE);

-- Update vote counts for candidates (this should match the actual votes above)
-- Department Representatives Election
UPDATE candidates SET vote_count = 5 WHERE id = 13; -- Alexis Washington
UPDATE candidates SET vote_count = 5 WHERE id = 14; -- Jamie Fox
UPDATE candidates SET vote_count = 6 WHERE id = 15; -- Jordan Ellis
UPDATE candidates SET vote_count = 4 WHERE id = 16; -- Casey Thomas
UPDATE candidates SET vote_count = 7 WHERE id = 17; -- Taylor Swift
UPDATE candidates SET vote_count = 3 WHERE id = 18; -- Morgan Stanley
UPDATE candidates SET vote_count = 4 WHERE id = 19; -- Riley Cooper
UPDATE candidates SET vote_count = 6 WHERE id = 20; -- Avery Zhang

-- Campus Improvement Committee
UPDATE candidates SET vote_count = 4 WHERE id = 21; -- Skyler Rodriguez
UPDATE candidates SET vote_count = 3 WHERE id = 22; -- Harper Williams
UPDATE candidates SET vote_count = 3 WHERE id = 23; -- Jordan Kim
UPDATE candidates SET vote_count = 4 WHERE id = 24; -- River Martinez
UPDATE candidates SET vote_count = 4 WHERE id = 25; -- Sage Thompson
UPDATE candidates SET vote_count = 2 WHERE id = 26; -- Logan Garcia
UPDATE candidates SET vote_count = 6 WHERE id = 27; -- Quinn Wilson
UPDATE candidates SET vote_count = 3 WHERE id = 28; -- Dakota Smith
UPDATE candidates SET vote_count = 1 WHERE id = 29; -- Charlie Brown

-- Run this query to verify the data has been inserted properly
-- SELECT COUNT(*) FROM users;
-- SELECT COUNT(*) FROM elections;
-- SELECT COUNT(*) FROM positions;
-- SELECT COUNT(*) FROM candidates;
-- SELECT COUNT(*) FROM votes;
-- SELECT COUNT(*) FROM voter_elections;
