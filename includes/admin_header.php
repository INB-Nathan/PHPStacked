<?php
function adminHeader($active = '') {
    ?>
    <header class="admin-header"> <!-- Header container for navigation -->
        <nav class="admin-nav"> <!-- Navigation bar -->
            <ul>
                <li class="<?php echo $active == 'dashboard' ? 'active' : ''; ?>"> <!-- Dashboard tab -->
                    <a href="index.php">Dashboard</a>
                </li>
                <li class="<?php echo $active == 'voters' ? 'active' : ''; ?>"> <!-- Voters Management tab -->
                    <a href="voters.php">Voters Management</a>
                </li>
                <li class="<?php echo $active == 'party' ? 'active' : ''; ?>"> <!-- Party Management tab -->
                    <a href="party.php">Party Management</a>
                </li>
                <li class="<?php echo $active == 'positions' ? 'active' : ''; ?>"> <!-- Positions Management tab -->
                    <a href="positions.php">Positions Management</a>
                </li>
                <li class="<?php echo $active == 'candidates' ? 'active' : ''; ?>"> <!-- Candidate Management tab -->
                    <a href="candidates.php">Candidate Management</a>
                </li>
                <li class="<?php echo $active == 'statistics' ? 'active' : ''; ?>"> <!-- Vote Statistics tab -->
                    <a href="statistics.php">Vote Statistics</a>
                </li>
            </ul>
        </nav>
    </header>
    <?php
}
?>