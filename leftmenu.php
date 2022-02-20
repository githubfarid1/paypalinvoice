<!-- Sidebar  -->
<?php
require_once('DevCoder/DotEnv.php');
$env = new DotEnv(__DIR__ . '/.env');
$env->load();

?>
<nav id="sidebar">
    <div class="sidebar-header" style="background: white;">
        <h3 style="color: black;"><?= getenv('APP_ENV') == 'live' ? 'Live' : 'Sandbox';?></h3>
        <!-- <img src="logo1.png" alt="logo"> -->
    </div>

    <ul class="list-unstyled components">
        <li>
            <a href="createnew.php">Create New Invoice</a>
        </li>
        <li>
            <a href="generate.php">Generate New Token</a>
        </li>
    </ul>
</nav>
