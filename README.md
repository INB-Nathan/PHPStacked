# PHPStacked

A modular web-based application for secure election management and voting systems, built with PHP and MySQL, running on a XAMPP stack.

---

## Project Overview

PHPStacked is a comprehensive electronic voting system designed to facilitate secure, transparent elections in various contexts (organizational, academic, governmental). The system provides separate interfaces for administrators and voters, with robust security features to ensure vote integrity and user privacy.

---

## Project Structure

```
PHPStacked/
├── admin/                  # Admin interface
│   ├── candidates.php      # Candidate management
│   ├── database_settings.php # Database configuration
│   ├── election.php        # Election management
│   ├── index.php           # Admin dashboard
│   ├── party_members.php   # Party membership management
│   ├── party_position.php  # Party and position management
│   ├── statistics.php      # Election statistics and analytics
│   └── voters.php          # Voter management
├── css/                    # Stylesheet files
│   ├── admin_*.css         # Admin interface styles
│   ├── voter_*.css         # Voter interface styles
│   └── main.css            # Shared styles
├── includes/               # Core application files
│   ├── autoload.php        # Class autoloader
│   ├── candidate_manager.php # Candidate operations
│   ├── config.php          # Application configuration
│   ├── database_manager.php # Database operations
│   ├── db_connect.php      # Database connection
│   ├── election_manager.php # Election operations
│   ├── file_handler.php    # File upload handling
│   ├── input_validator.php # Input validation and sanitization
│   ├── party_manager.php   # Party operations
│   ├── position_manager.php # Position operations
│   ├── security.php        # Security management
│   ├── security_headers.php # HTTP security headers
│   ├── security_helper.php # Security utility functions
│   ├── statistics_manager.php # Statistical operations
│   ├── user_manager.php    # User management
│   ├── vote_manager.php    # Vote operations
│   └── voter_header.php    # Voter interface header
├── js/                     # JavaScript files
│   ├── candidates.js       # Candidate management scripts
│   ├── logout.js           # Logout functionality
│   └── party_position.js   # Party & position scripts
├── sql/                    # Database schema
│   └── schema.sql          # Complete database schema
├── uploads/                # File upload storage
│   └── candidates/         # Candidate photos/images
├── voter/                  # Voter interface
│   ├── available_elections.php # List available elections
│   ├── confirmation.php    # Vote confirmation
│   ├── election_results.php # View election results
│   ├── get_party_candidates.php # Get candidates by party
│   ├── index.php           # Voter dashboard
│   ├── party_vote.php      # Vote by party
│   ├── view_election.php   # View election details
│   └── vote.php            # Vote for candidates
├── index.php               # Main entry point
├── login.php               # Login interface
├── logout.php              # Logout handler
├── .htaccess               # Apache configuration
└── security_report.md      # Security documentation
```

---

## Technologies Used

- **Frontend:** HTML5, CSS3, JavaScript
- **Backend:** PHP 8.3+
- **Database:** MySQL (via PDO)
- **Server:** Apache (XAMPP)
- **Security:** PDO prepared statements, Content Security Policy, password hashing, CSRF protection

---

## Features

### Admin Interface
- **User Management:** Create, edit, and manage voter accounts
- **Election Setup:** Configure elections with start/end dates and voter eligibility
- **Candidate Management:** Add and manage candidates with photos and platforms
- **Party & Position Management:** Define political parties and positions
- **Statistics & Reports:** View real-time election statistics and results
- **Database Management:** Perform database operations and maintenance

### Voter Interface
- **Available Elections:** View active and upcoming elections
- **Voting System:** Cast votes for individual candidates or by party
- **Election Results:** View results of completed elections
- **Secure Authentication:** Protect voter identity and votes
- **Mobile-Responsive Design:** Compatible with various devices

### Security Features
- **Input Validation:** Comprehensive validation of all user inputs
- **SQL Injection Prevention:** Prepared statements and parameterized queries
- **Authentication Security:** Secure session management, password hashing
- **XSS Prevention:** Output encoding and Content Security Policy
- **CSRF Protection:** Token-based cross-site request forgery protection
- **File Upload Security:** File type verification and secure storage
- **Rate Limiting:** Protection against brute force attacks
- **Security Headers:** Implementation of modern HTTP security headers

---

## Security Considerations

- Parameterized SQL queries to prevent injection attacks
- Password hashing using bcrypt
- Session security with timeout and anti-hijacking measures
- CSRF token protection for all forms
- Content Security Policy and other security headers
- Input validation and sanitization
- Secure file upload handling
