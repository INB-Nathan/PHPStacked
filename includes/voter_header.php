<?php
function voterHeader($active = '')
{
?>
<header class="admin-header">
    <nav class="admin-nav">
        <ul>
            <li class="<?php echo $active == 'dashboard' ? 'active' : ''; ?>">
                <a href="index.php">Dashboard</a>
            </li>
            <li class="<?php echo $active == 'view_election' ? 'active' : ''; ?>">
                <a href="view_election.php">View Election</a>
            </li>
            <li class="<?php echo $active == 'vote' ? 'active' : ''; ?>">
                <a href="vote.php">Vote</a>
            </li>
        </ul>
    </nav>
</header>
<?php
}
?>