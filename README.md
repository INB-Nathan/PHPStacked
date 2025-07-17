# PHPStacked

A secure, feature-rich web-based election management and voting system built with PHP and MySQL, running on a XAMPP stack. PHPStacked provides comprehensive election administration tools and a user-friendly voting interface with enterprise-grade security features.

---

## Project Overview

PHPStacked is a modern electronic voting system designed to facilitate secure, transparent, and auditable elections in various contexts including organizational, academic, and governmental settings. The system features a robust dual-interface architecture with separate administrative and voter portals, comprehensive security measures, and real-time election management capabilities.

### Key Highlights
- **Dual Interface**: Separate admin and voter interfaces with role-based access control
- **Enterprise Security**: Multi-layered security with CSRF protection, input validation, and secure session management
- **Real-time Updates**: Automatic election status updates and live result tracking
- **Flexible Voting**: Support for individual candidate voting and party-based voting
- **Audit Trail**: Complete vote tracking with receipt generation and verification
- **Mobile Responsive**: Optimized for desktop, tablet, and mobile devices
- **Timezone Support**: Configurable timezone settings for global deployments

---

## Project Structure

```
PHPStacked/
├── admin/                  # Admin interface
│   ├── candidates.php      # Candidate management with photo uploads
│   ├── database_settings.php # Database configuration and maintenance
│   ├── election.php        # Election creation, editing, and management
│   ├── index.php           # Admin dashboard with statistics overview
│   ├── party_members.php   # Party membership management and assignment
│   ├── party_position.php  # Party and position creation/management
│   ├── statistics.php      # Detailed election analytics and reports
│   └── voters.php          # Voter account management and permissions
├── css/                    # Comprehensive stylesheet collection
│   ├── admin_*.css         # Admin interface styling
│   ├── voter_*.css         # Voter interface styling
│   ├── login.css           # Authentication interface styling
│   ├── main.css            # Core application styles
│   └── party_*.css         # Party-specific interface styling
├── includes/               # Core application backend
│   ├── admin_header.php    # Admin interface navigation and header
│   ├── autoload.php        # Class autoloader with timezone configuration
│   ├── candidate_manager.php # Candidate CRUD operations and photo handling
│   ├── config.php          # Security and application configuration
│   ├── database_manager.php # Database operations and maintenance tools
│   ├── db_connect.php      # Database connection with PDO
│   ├── election_manager.php # Election lifecycle management
│   ├── file_handler.php    # Secure file upload and management
│   ├── functions.php       # Utility functions and helpers
│   ├── input_validator.php # Comprehensive input validation and sanitization
│   ├── party_manager.php   # Political party management operations
│   ├── position_manager.php # Election position management
│   ├── security.php        # SecurityManager class with CSRF protection
│   ├── security_headers.php # HTTP security headers implementation
│   ├── security_helper.php # Security utility functions
│   ├── statistics_manager.php # Election statistics and analytics
│   ├── user_manager.php    # User account management with role control
│   ├── vote_manager.php    # Vote casting, tracking, and receipt generation
│   └── voter_header.php    # Voter interface navigation
├── js/                     # Client-side functionality
│   ├── candidates.js       # Candidate management interactions
│   ├── election_status_updater.js # Real-time election status updates
│   ├── logout.js           # Secure logout functionality
│   └── party_position.js   # Party and position management scripts
├── sql/                    # Database schema and structure
│   └── schema.sql          # Complete database schema with constraints
├── uploads/                # Secure file storage
│   ├── .htaccess          # Upload directory protection
│   └── candidates/         # Candidate photo storage with security
│       └── .htaccess      # Additional candidate photo protection
├── voter/                  # Voter interface
│   ├── available_elections.php # List of accessible elections for voter
│   ├── check_election_statuses.php # Election status verification
│   ├── confirmation.php    # Vote confirmation with receipt generation
│   ├── election_results.php # Real-time and final election results
│   ├── get_party_candidates.php # AJAX endpoint for party candidates
│   ├── index.php           # Voter dashboard with election overview
│   ├── party_vote.php      # Party-based voting interface
│   └── vote.php            # Individual candidate voting interface
├── .htaccess               # Apache security configuration
├── index.php               # Application landing page
├── login.php               # Secure authentication interface
├── logout.php              # Session termination handler
└── README.md               # Project documentation
```

---

## Technologies Used

- **Frontend:** HTML5, CSS3, JavaScript (ES6+), Font Awesome icons
- **Backend:** PHP 8.3+ (Object-Oriented Programming)
- **Database:** MySQL 8.0+ with PDO (Prepared Statements)
- **Server:** Apache 2.4+ (XAMPP/LAMPP stack)
- **Security:** 
  - Content Security Policy (CSP)
  - CSRF token protection
  - Password hashing with bcrypt
  - Input validation and sanitization
  - Secure session management
  - HTTP security headers

---

## Core Features

### Administrative Interface
- **Comprehensive User Management**
  - Create, edit, delete voter and admin accounts
  - Role-based access control (admin/voter)
  - Bulk voter import and management
  - User permission assignment for specific elections

- **Advanced Election Management**
  - Create elections with start/end dates and timezone support
  - Real-time election status management (upcoming, active, completed, cancelled)
  - Maximum votes per user configuration
  - Election-specific voter eligibility settings

- **Candidate & Party Management**
  - Add candidates with photos, biographies, and party affiliations
  - Political party creation and management
  - Position-based candidate organization
  - Bulk candidate operations

- **Real-time Statistics & Analytics**
  - Live vote counting and result tracking
  - Voter turnout analytics
  - Position-wise result breakdown
  - Export capabilities for reports

- **Database Administration**
  - Database health monitoring
  - Backup and maintenance tools
  - System configuration management

### Voter Interface
- **Intuitive Voting Experience**
  - Available elections dashboard with status indicators
  - Individual candidate voting with photos and information
  - Party-based voting option for streamlined experience
  - Mobile-responsive design for all devices

- **Security & Transparency**
  - Secure vote casting with confirmation steps
  - Vote receipt generation with unique tracking codes
  - Vote verification without revealing choices
  - Prevention of duplicate voting

- **Election Information**
  - Detailed candidate profiles and platforms
  - Party information and member listings
  - Real-time election results (when enabled)
  - Election schedule and status updates

### Security Framework
- **Authentication Security**
  - Secure password hashing with bcrypt (cost factor 12)
  - Session timeout with configurable duration (15 minutes default)
  - Session regeneration for security
  - Rate limiting for login attempts

- **Input Protection**
  - Comprehensive input validation with `InputValidator` class
  - SQL injection prevention via PDO prepared statements
  - XSS protection with output encoding
  - File upload security with type verification

- **Session & CSRF Protection**
  - CSRF token validation for all forms
  - Secure session configuration
  - HTTP-only and secure cookie settings
  - Session hijacking prevention

- **HTTP Security Headers**
  - Content Security Policy implementation
  - X-Frame-Options protection
  - X-Content-Type-Options headers
  - Referrer Policy configuration

---

## Security Architecture

### Multi-layered Protection
1. **Network Level**: Apache security configuration via .htaccess
2. **Application Level**: PHP security classes and validation
3. **Database Level**: PDO prepared statements and constraints
4. **Session Level**: Secure session management and timeout
5. **Input Level**: Comprehensive validation and sanitization
6. **Output Level**: XSS prevention and proper encoding

### Data Protection
- All user inputs are validated using the `InputValidator` class
- Database queries use parameterized statements exclusively
- File uploads are restricted and validated for type and size
- Sensitive data is properly hashed and encrypted
- Session data is secured with HTTP-only flags

### Audit & Compliance
- Complete vote audit trail with unique receipt codes
- Detailed logging of administrative actions
- Timestamp tracking for all database operations
- Vote verification without privacy compromise

---

## Configuration & Setup

### Timezone Configuration
The system supports global deployment with configurable timezone settings in `/includes/autoload.php`:

```php
date_default_timezone_set('Asia/Manila'); // Change to your timezone
```

### Security Configuration
Comprehensive security settings in `/includes/config.php`:
- Session timeout and security parameters
- Password complexity requirements
- File upload restrictions
- CSRF protection settings

### Database Configuration
Secure database connection via PDO with prepared statements in `/includes/db_connect.php`

---

## Recent Enhancements

### InputValidator Integration
- Comprehensive input validation throughout the application
- Enhanced security for all form submissions
- Centralized validation logic for maintainability
- Improved error messaging for user experience

### Timezone Support
- Configurable timezone settings for global deployment
- Accurate election scheduling across time zones
- Debug tools for timezone verification

### Enhanced Security
- CSRF protection for all forms
- Improved session management
- Rate limiting for authentication
- Secure file upload handling

---

## System Requirements

- **PHP:** 8.3 or higher
- **MySQL:** 8.0 or higher  
- **Apache:** 2.4 or higher
- **XAMPP/LAMPP:** Latest version recommended
- **Browser:** Modern browser with JavaScript enabled
- **Storage:** Minimum 100MB for application and uploads


