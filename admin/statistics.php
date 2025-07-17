<?php
require_once '../includes/autoload.php';
session_start();

// Security checks
$securityManager = new SecurityManager($pdo);
$securityManager->secureSession();
$securityManager->checkSessionTimeout();
$csrf_token = $securityManager->generateCSRFToken();

// Only allow access if logged in and user is admin
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// Create statistics manager instance
$statsManager = new StatisticsManager($pdo);

// Get statistics
$systemStats = $statsManager->getSystemStats();
$electionStats = $statsManager->getElectionStats();
$topCandidates = $statsManager->getTopCandidates(5);
$participationRates = $statsManager->getVoterParticipationRates();
$recentActivity = $statsManager->getRecentVotingActivity(5);

// Debug output
error_log("Top Candidates: " . print_r($topCandidates, true));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Vote Statistics</title>
    <link rel="stylesheet" href="../css/admin_header.css">
    <link rel="stylesheet" href="../css/admin_index.css">
    <link rel="stylesheet" href="../css/admin_popup.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="../js/logout.js" defer></script>
    <style>
        .stats-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .stats-section {
            margin-bottom: 30px;
            padding: 20px;
            background-color: #f5f5f5;
            border-radius: 5px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .stats-section h2 {
            color: #333;
            margin-top: 0;
            border-bottom: 1px solid #ddd;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 15px;
        }
        
        .stat-card {
            background-color: #fff;
            padding: 15px;
            border-radius: 5px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .stat-card h3 {
            margin-top: 0;
            color: #555;
            font-size: 14px;
        }
        
        .stat-card p {
            margin-bottom: 0;
            font-size: 24px;
            font-weight: bold;
            color: #333;
        }
        
        .stats-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .stats-table th, .stats-table td {
            text-align: left;
            padding: 10px;
            border-bottom: 1px solid #ddd;
        }
        
        .stats-table th {
            background-color: #eee;
            font-weight: bold;
        }
        
        .stats-table tr:hover {
            background-color: #f9f9f9;
        }
        
        .highlight {
            color: #4285f4;
        }
        
        .date {
            color: #777;
            font-size: 12px;
        }
        
        .participation-rate {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
            color: white;
        }
        
        .rate-high {
            background-color: #4CAF50;
        }
        
        .rate-medium {
            background-color: #FFC107;
        }
        
        .rate-low {
            background-color: #F44336;
        }
    </style>
</head>
<body>
    <?php adminHeader('statistics', $csrf_token ?? ''); ?>
    
    <div class="stats-container">
        <h1>Vote Statistics Dashboard</h1>
        
        <!-- System Overview -->
        <div class="stats-section">
            <h2><i class="fas fa-chart-bar"></i> System Overview</h2>
            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Total Elections</h3>
                    <p><?= number_format($systemStats['total_elections'] ?? 0) ?></p>
                </div>
                <div class="stat-card">
                    <h3>Active Elections</h3>
                    <p><?= number_format($systemStats['active_elections'] ?? 0) ?></p>
                </div>
                <div class="stat-card">
                    <h3>Upcoming Elections</h3>
                    <p><?= number_format($systemStats['upcoming_elections'] ?? 0) ?></p>
                </div>
                <div class="stat-card">
                    <h3>Completed Elections</h3>
                    <p><?= number_format($systemStats['completed_elections'] ?? 0) ?></p>
                </div>
                <div class="stat-card">
                    <h3>Total Registered Voters</h3>
                    <p><?= number_format($systemStats['total_voters'] ?? 0) ?></p>
                </div>
                <div class="stat-card">
                    <h3>Total Votes Cast</h3>
                    <p><?= number_format($systemStats['total_votes'] ?? 0) ?></p>
                </div>
                <div class="stat-card">
                    <h3>Unique Voters Participated</h3>
                    <p><?= number_format($systemStats['unique_voters'] ?? 0) ?></p>
                </div>
                <div class="stat-card">
                    <h3>Total Candidates</h3>
                    <p><?= number_format($systemStats['total_candidates'] ?? 0) ?></p>
                </div>
            </div>
        </div>
        
        <!-- Election Statistics -->
        <div class="stats-section">
            <h2><i class="fas fa-vote-yea"></i> Election Statistics</h2>
            <?php if (empty($electionStats)): ?>
                <p>No election data available.</p>
            <?php else: ?>
                <div style="overflow-x: auto;">
                    <table class="stats-table">
                        <thead>
                            <tr>
                                <th>Election</th>
                                <th>Status</th>
                                <th>Dates</th>
                                <th>Positions</th>
                                <th>Candidates</th>
                                <th>Assigned Voters</th>
                                <th>Participation</th>
                                <th>Total Votes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($electionStats as $election): ?>
                                <tr>
                                    <td><?= htmlspecialchars($election['title']) ?></td>
                                    <td>
                                        <?php
                                        $statusDisplay = $election['status'];
                                        if ($statusDisplay === 'active') {
                                            if (strtotime($election['start_date']) > time()) {
                                                $statusDisplay = 'Upcoming';
                                            } elseif (strtotime($election['end_date']) < time()) {
                                                $statusDisplay = 'Completed';
                                            } else {
                                                $statusDisplay = 'Active';
                                            }
                                        }
                                        echo htmlspecialchars(ucfirst($statusDisplay));
                                        ?>
                                    </td>
                                    <td>
                                        <div><?= date('M d, Y', strtotime($election['start_date'])) ?></div>
                                        <div class="date">to</div>
                                        <div><?= date('M d, Y', strtotime($election['end_date'])) ?></div>
                                    </td>
                                    <td><?= number_format($election['position_count']) ?></td>
                                    <td><?= number_format($election['candidate_count']) ?></td>
                                    <td><?= number_format($election['assigned_voters']) ?></td>
                                    <td>
                                        <?php 
                                        $participation = $election['assigned_voters'] > 0 
                                            ? round(($election['voters_participated'] / $election['assigned_voters']) * 100, 1) 
                                            : 0;
                                        
                                        $rateClass = 'rate-low';
                                        if ($participation >= 70) {
                                            $rateClass = 'rate-high';
                                        } elseif ($participation >= 40) {
                                            $rateClass = 'rate-medium';
                                        }
                                        
                                        if (strtotime($election['end_date']) > time() && $election['status'] !== 'completed') {
                                            echo '<span>' . $election['voters_participated'] . ' of ' . $election['assigned_voters'] . '</span>';
                                        } else {
                                            echo '<span class="participation-rate ' . $rateClass . '">' . $participation . '%</span>';
                                        }
                                        ?>
                                    </td>
                                    <td><?= number_format($election['total_votes']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Top Candidates -->
        <div class="stats-section">
            <h2><i class="fas fa-trophy"></i> Top Candidates</h2>
            <?php if (empty($topCandidates)): ?>
                <p>No candidate data available.</p>
            <?php else: ?>
                <table class="stats-table">
                    <thead>
                        <tr>
                            <th>Candidate</th>
                            <th>Party</th>
                            <th>Position</th>
                            <th>Election</th>
                            <th>Vote Count</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($topCandidates as $candidate): ?>
                            <tr>
                                <td><?= htmlspecialchars($candidate['name']) ?></td>
                                <td><?= htmlspecialchars($candidate['party_name'] ?? 'Independent') ?></td>
                                <td><?= htmlspecialchars($candidate['position_name']) ?></td>
                                <td><?= htmlspecialchars($candidate['election_title']) ?></td>
                                <td><strong><?= number_format($candidate['vote_count']) ?></strong></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        
        <!-- Voter Participation Rates -->
        <div class="stats-section">
            <h2><i class="fas fa-users"></i> Voter Participation Rates (Completed Elections)</h2>
            <?php if (empty($participationRates)): ?>
                <p>No completed elections data available.</p>
            <?php else: ?>
                <table class="stats-table">
                    <thead>
                        <tr>
                            <th>Election</th>
                            <th>Assigned Voters</th>
                            <th>Voters Participated</th>
                            <th>Participation Rate</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($participationRates as $rate): ?>
                            <tr>
                                <td><?= htmlspecialchars($rate['title']) ?></td>
                                <td><?= number_format($rate['assigned_voters']) ?></td>
                                <td><?= number_format($rate['voters_participated']) ?></td>
                                <td>
                                    <?php 
                                    $rateClass = 'rate-low';
                                    if ($rate['participation_rate'] >= 70) {
                                        $rateClass = 'rate-high';
                                    } elseif ($rate['participation_rate'] >= 40) {
                                        $rateClass = 'rate-medium';
                                    }
                                    ?>
                                    <span class="participation-rate <?= $rateClass ?>"><?= $rate['participation_rate'] ?>%</span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        
        <!-- Recent Voting Activity -->
        <div class="stats-section">
            <h2><i class="fas fa-history"></i> Recent Voting Activity</h2>
            <?php if (empty($recentActivity)): ?>
                <p>No recent voting activity.</p>
            <?php else: ?>
                <table class="stats-table">
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>Voter</th>
                            <th>Candidate</th>
                            <th>Position</th>
                            <th>Election</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentActivity as $activity): ?>
                            <tr>
                                <td><?= date('M d, Y H:i:s', strtotime($activity['vote_timestamp'])) ?></td>
                                <td><?= htmlspecialchars($activity['voter_name']) ?></td>
                                <td><?= htmlspecialchars($activity['candidate_name']) ?></td>
                                <td><?= htmlspecialchars($activity['position_name']) ?></td>
                                <td><?= htmlspecialchars($activity['election_title']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>