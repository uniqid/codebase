<?php
/*************************************************

Codebase - The PHP toolkit
Author: Jacky Yu <jacky325@qq.com>
Copyright (c): 2012-2015 Jacky Yu, All rights reserved
Version: 1.0.0

* This library is free software; you can redistribute it and/or modify it.
* You may contact the author of Picker by e-mail at: jacky325@qq.com

The latest version of Picker can be obtained from:
https://github.com/flyfishsoft/codebase

*************************************************/

$app_path = str_replace("\\", "/", dirname(realpath(__FILE__))) . "/";
require($app_path.'common/cfg/bootstrap.php');
$req_uri  = get_req_uri();
$base_url = BASE_URL . $req_uri;
?>
<!DOCTYPE HTML>
<html>
<head>
    <meta charset="utf-8" />
    <link rel="shortcut icon" type="image/ico" href="favicon.ico">
    <link href="<?php __($base_url) ?>/common/css/base.css" type="text/css" rel="stylesheet">
</head>
<body>
<div class="nav-list">
	<h1>Application List</h1>
	<?php
	foreach(getFiles($app_path, array('common', 'index.php', '.git', 'README.md')) as $app){
		echo '<a target="_blank" href="'.$base_url.'/'.$app.'/index.php">'.$app.'</a>';
	}
	?>
	<div class="nav-foot RoundedCorner">
		<b class="rbottom"><b class="r4"></b><b class="r3"></b><b class="r2"></b><b class="r1"></b></b>
	</div>
</div>
</body>
</html>