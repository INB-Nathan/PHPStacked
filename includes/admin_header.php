<?php
function adminHeader($active = '') {
?>
<header class="admin-header">
  <nav class="admin-nav">
    <div class="nav-logo">
      <a href="index.php" class="logo-link">Election System Admin</a>
    </div>
    <ul class="nav-menu">
      <li class="<?php echo ($active === 'dashboard')   ? 'active' : ''; ?>">
        <a href="index.php">Dashboard</a>
      </li>
      <li class="<?php echo ($active === 'voters')      ? 'active' : ''; ?>">
        <a href="voters.php">Voters Management</a>
      </li>
      <li class="<?php echo ($active === 'party')       ? 'active' : ''; ?>">
        <a href="party_position.php">Party & Position</a>
      </li>
      <li class="<?php echo ($active === 'candidates')  ? 'active' : ''; ?>">
        <a href="candidates.php">Candidate Management</a>
      </li>
      <li class="<?php echo ($active === 'statistics')  ? 'active' : ''; ?>">
        <a href="statistics.php">Vote Statistics</a>
      </li>
      <li class="<?php echo ($active === 'election')  ? 'active' : ''; ?>">
        <a href="election.php">Election Management</a>
      </li>
      <li class="<?php echo ($active === 'database')  ? 'active' : ''; ?>">
        <a href="database_settings.php">Database Settings</a>
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