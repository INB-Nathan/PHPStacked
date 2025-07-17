<?php
function voterHeader($active = '')
{
    ?>
    <header class="admin-header">
        <nav class="admin-nav">
            <ul class="nav-menu">
                <li class="<?php echo $active == 'dashboard' ? 'active' : ''; ?>">
                    <a href="index.php">Dashboard</a>
                </li>
                <li class="<?php echo $active == 'elections' ? 'active' : ''; ?>">
                    <a href="available_elections.php">Available Elections</a>
                </li>
                <li class="<?php echo $active == 'results' ? 'active' : ''; ?>">
                    <a href="election_results.php">Election Results</a>
                </li>
            </ul>
            <div class="nav-logout">
                <a href="#" id="logoutNavBtn">
                    <i class="fa-solid fa-right-from-bracket"></i> Log Out
                </a>
            </div>
        </nav>
    </header>
    <?php
}
?>