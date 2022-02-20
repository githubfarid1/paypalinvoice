<?php
require_once('DevCoder/DotEnv.php');
$env = new DotEnv(__DIR__ . '/.env');
$env->load();

?>
<!-- Page Content  -->

<div class="menu-header">
    <button type="button" id="sidebarCollapse" class="btn menu-btn">
        <img src="nav.png" alt="Menu">
    </button>
    <a href="<?=getenv('URL');?>"><span class="menu-text">PayPal Invoice Generator</span></a>

</div>
