<?php
function adminHeader($active = '') {
    ?>
    <header class="admin-header" style="position:relative;">
        <nav class="admin-nav">
            <ul>
                <li class="<?php echo $active == 'dashboard' ? 'active' : ''; ?>">
                    <a href="index.php">Dashboard</a>
                </li>
                <li class="<?php echo $active == 'voters' ? 'active' : ''; ?>">
                    <a href="voters.php">Voters Management</a>
                </li>
                <li class="<?php echo $active == 'party' ? 'active' : ''; ?>">
                    <a href="party.php">Party Management</a>
                </li>
                <li class="<?php echo $active == 'positions' ? 'active' : ''; ?>">
                    <a href="positions.php">Positions Management</a>
                </li>
                <li class="<?php echo $active == 'candidates' ? 'active' : ''; ?>">
                    <a href="candidates.php">Candidate Management</a>
                </li>
                <li class="<?php echo $active == 'statistics' ? 'active' : ''; ?>">
                    <a href="statistics.php">Vote Statistics</a>
                </li>
                <li>
                    <a href="#" id="logoutNavBtn">
                        <i class="fa-solid fa-right-from-bracket"></i> Log Out
                    </a>
                </li>
            </ul>
        </nav>
    </header>
    <?php
}
?>