<?php
/**********************************************************************************
								LM Framework v3
								
	A simple PHP based application framework.
	
	Contact: pozniak.lukasz@gmail.com
	
	Copyright (c) 2005-2013, ?ukasz Po?niak
	All rights reserved.

	Redistribution and use in source and binary forms, with or without modification,
	are permitted provided that the following conditions are met:
	
	Redistributions of source code must retain the above copyright notice,
	this list of conditions and the following disclaimer.
	Redistributions in binary form must reproduce the above copyright notice,
	this list of conditions and the following disclaimer in the documentation
	and/or other materials provided with the distribution.
	THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
	AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO,
	THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
	ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS
	BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY,
	OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT
	OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS;
	OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY,
	WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
	ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED
	OF THE POSSIBILITY OF SUCH DAMAGE.

**********************************************************************************/

function getID() {
    if (!isset($_GET['id'])) {
        $id=getprefs();
        $id=$id['defaultPage'];
    } else {
        $id=$_GET['id'];
    }
    
    return $id;
}

function template_contents() {
    $id = getID();
    ob_start();
    showTabContents($id);
    $ret = ob_get_contents();
    ob_end_clean();
    return $ret;
}

function template_main($contents,$title,$meta) {
	global $LM_APP_NAME, $lmver, $LANG, $LM_READONLY;
        //<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
	?>
	<!DOCTYPE html> 
	<html prefix="og: http://ogp.me/ns#" lang="en" class="no-js">
	<head>
	
        <?=$meta?>
	<link rel="alternate" type="application/rss+xml" title="RSS" href="../Site_Core/rss.php">

	<?php
	applycss(getcss());
        applycss("css/print.css");
        applycss("css/grid.css");
        applycss("css/mg.css");
	?>
        <!--<link rel="stylesheet" href="jquery-ui/css/ui-darkness/jquery-ui-1.10.3.custom.min.css" />-->
	<link rel="icon" href="../Graphics/favicon.ico" type="image/ico">
        <script type="text/javascript" src="<?=getUrl()?>jquery-ui/js/jquery-1.9.1.js"></script>
        <script type="text/javascript" src="<?=getUrl()?>jquery-ui/js/jquery-ui-1.10.3.custom.min.js"></script>
        <script type="text/javascript" src="<?=getUrl()?>chart.js/Chart.min.js"></script>
        <script type="text/javascript" src="<?=getUrl()?>ccpwgl/external/glMatrix-0.9.5.min.js"></script>
        <script type="text/javascript" src="<?=getUrl()?>ccpwgl/ccpwgl_int.js"></script>
        <script type="text/javascript" src="<?=getUrl()?>ccpwgl/test/TestCamera2.js"></script>
        <script type="text/javascript" src="<?=getUrl()?>ccpwgl/ccpwgl.js"></script>
        <script type="text/javascript" src="<?=getUrl()?>ajax.js"></script>
        <script type="text/javascript" src="<?=getUrl()?>mg.js"></script>
        <script type="text/javascript" src="<?=getUrl()?>skrypty.js"></script>
        <script type="text/javascript" src="<?=getUrl()?>webgl.js"></script>
	<script type="text/javascript" src="<?=getUrl()?>skin-icon.js"></script>
	<script type="text/javascript">
		function logoff() {
		logout=window.open("index.php?logoff=1","logoff_window","toolbar=0,location=0,scrollbars=0,resizable=0,height=64,width=64,top=100,left=100");
		logout.close();
		}</script>
	</head>
	<body text="#000000" bgcolor="#FFFFFF">
	<center>
        <canvas id="testCanvas" width="1" height="1" style="width: 1px; height: 1px; display: none;"></canvas>
	<table class="tab-container">
	<tr><td width="100%" class="tab-horizbar">
		<table border="0" cellspacing="0" cellpadding="0" width="100%">
		<tr><td width="33%" align="left"><div class="top">Logged in as:<b> <?php
			echo(getusername());
		?></b><br></div></td>
                <td width="34%"><div id="evetime" title="Current EVE Time" style="margin-left: auto; margin-right: auto; width: 36px;" class="top">--:--</div>
                <script type="text/javascript">
                    window.setInterval(function(){ showEvetime('evetime'); }, 5000);
                    showEvetime('evetime');
                </script>
                </td>
		<td width="33%"><div class="top2"><a href="../Graphics/favicon.ico"><img src="<?=getUrl()?>img/settings.gif" alt="Settings" style="vertical-align: middle;"></a><a href="../wwwroot/index.php?logoff=1"><img src="<?=getUrl()?>img/log.gif" alt="Log off" style="vertical-align: middle;"> <b>Log off</b></a>
		<br></div></div></td></tr>
		</table>
	</td></tr>
	<tr><td width="100%" class="tab-logo">
	<img src="<?=getUrl()?>img/LMeve.png" alt="Logo">
	<?php //draw messages notify
	include("msgchk.php");
	include("../Modules/Notifications/notifications.php");
	if ($LM_READONLY==1) echo($LANG['READONLY']);
	?>
	</td></tr>
	<tr><td class="tab-menu" width="100%" style="padding: 0;">
		<table border="0" cellspacing="0" cellpadding="0" width="100%">
		<tr>
	
	<?php //draw menu
	$id = getID();
	menu($id);
	?>
		</tr>
		</table>
	</td></tr>
	<tr><td width="100%" class="tab-horizbar">
	<br>
	</td></tr>
	<tr><td width="100%" style="padding: 0;">
		<table border="0" cellspacing="0" cellpadding="0" width="100%">
		<tr><td class="tab-links" id="tab-links" style="width: 20%; vertical-align: top; padding: 5px;">
	<?php //draw links from db
        $sidebar=getConfigItem('leftSidebar','Administrators can freely edit this sidebar.');
        
            echo(stripslashes(htmlspecialchars_decode($sidebar)));
            
	if (checkrights("Administrator")) { ?>
	<div style="text-align: center;"><hr><form method="get" action="">
	<input type="hidden" name="id" value="5">
	<input type="hidden" name="id2" value="6">
	<input type="hidden" name="nr" value="new">
	<input type="submit" value="Edit">
	</form></div>
	<?php } ?>
		</td>
		<td width="80%" class="tab-main" id="tab-main" valign="top">
			<?=$contents?>
		</td>
		</tr>
		</table>
	
	</td></tr>
	<tr><td width="100%" class="tab-horizbar">
	<a href="../wwwroot/index.php?id=254">About</a><br>
	</td></tr>
	</table>
	<?php
	include("copyright.php");
	?>
	<script type="text/javascript" src="<?=getUrl()?>resizer.js"></script>
	</center>
	</body>
	</html>
	<?php
}

function template_locked($meta, $msg=null) {
	global $LM_APP_NAME,$LM_DEFAULT_CSS,$LANG;
        if (is_null($msg)) $msg=$LANG['MAINTENANCE'];
	?>
	<!DOCTYPE html> 
	<html prefix="og: http://ogp.me/ns#" lang="en" class="no-js">
	<head>
            
	<?=$meta?>
	<link rel="alternate" type="application/rss+xml" title="RSS" href="../Site_Core/rss.php">

	<?php
	applycss($LM_DEFAULT_CSS);
	?>
	<link rel="icon" href="../Graphics/favicon.png" type="image/png">
	</head>
	<body text="#000000" bgcolor="#FFFFFF">
	<center>
	<br>
	<table border="0" cellspacing="0" cellpadding="0" width="250" class="login">
	
	
	<tr><td><br><div class="tcen"><?php echo($msg); ?>
	</div></td></tr>
	<tr><td><div class="tcen"><br>
		 <form method="get" action="">
			<input type="submit" value="Try again">
		 </form>
		 </div>
	</td></tr>
	</table>
	<?php
	include("copyright.php");
	?>
	</center>
	</body>
	</html>
	<?php
}

function template_login($meta) {
	global $LM_APP_NAME,$LM_DEFAULT_CSS,$LANG,$SSOENABLED;
	?>
	<!DOCTYPE html> 
	<html prefix="og: http://ogp.me/ns#" lang="en" class="no-js">
	<head>
            
	<?=$meta?>
	<link rel="alternate" type="application/rss+xml" title="RSS" href="../wwwroot/rss.php">

	<?php
	applycss($LM_DEFAULT_CSS);
	?>
	</head>
	<body text="#000000" bgcolor="#FFFFFF">
	<center>
            
	<br>
	<form name="loginform" id="loginform" method="post" action="">
	<img src="../Graphics/LMeve.png" alt='LMeve' />
	<table border="0" cellspacing="0" cellpadding="0" width="300" class="login">
	
	
	<tr><td align="left">&nbsp;<!--<br><label for="user_login">User:</label>--></td></tr>
	<tr><td><div class="tcen">
			<input name="login" placeholder="Login" id="user_login" size=40 type="text" value="" style="width: 190px" autocapitalize="off">
		</div>
	</td></tr>
	<tr><td align="left">&nbsp;<!--<label for="user_pass">Password:</label>--></td></tr>
	<tr><td><div class="tcen">
                        <input name="password" placeholder="Password" id="user_pass" size="40" type="password" style="width: 190px" autocapitalize="off">
		</div>
	</td></tr>
        <tr><td align="left">&nbsp;<!--<label for="user_pass">Password:</label>--></td></tr>
	<tr><td><div class="tcen">
			<input name="logon" type="submit" value="Log in"><br/>
			<?php if ($SSOENABLED) { ?> <hr style="opacity: 0.2;" />
			<a href="../wwwroot/ssologin.php"><img src="<?=getUrl()?>img/EVE_SSO_Login_Buttons_Small_White.png"></a>
			<?php } ?>
                        
                        <?php if (getConfigItem('publicKillboard')=='enabled') { ?> <hr style="opacity: 0.2;" />
			<a href="../Modules/Killboard/killboard.php"><img src="<?=getUrl()?>img/lmeve-killboard.png"></a>
			<?php } ?>
                        <hr style="opacity: 0.2;" />
                        <em>Administrator of <?=$LM_APP_NAME?> is processing EVE Online information owned by <a href="https://www.ccpgames.com/company">CCP hf</a> and shared under <a href="https://developers.eveonline.com/resource/license-agreement">Developer License Agreement</a>.<br/> <?=$LM_APP_NAME?> is using cookies so it can keep you logged in.</em>
		 </div>
	</td></tr>
	</table>
	<?php
	include('copyright.php');
	?>
	</form>
	</center>
	</body>
	</html>
	<?php
}

function template_badlogon() {
	global $LM_APP_NAME,$LM_DEFAULT_CSS,$LANG;	
	?>
	<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
	<html>
	<head>
	<META http-equiv="Content-Type" content="text/html; charset=iso-8859-2">
	<META HTTP-EQUIV="Pragma" CONTENT="no-cache">
	<title><?php echo($LM_APP_NAME); ?> - Please log in</title>
	<?php
	applycss($LM_DEFAULT_CSS);
	?>
	<link rel="icon" href="../Graphics/favicon.ico" type="image/ico">
	</head>
	<body text="#000000" bgcolor="#FFFFFF">
	<center>
	<br>
	<table border="0" cellspacing="0" cellpadding="0" width="250" class="login">
	<tr><td><div class="tcen"><br>Wrong username<br>or password.<br>&nbsp;</div></td></tr>
	<tr><td align="center"><div class="tcen"><br>
		 <form method="get" action="">
			<input type="submit" value="Back">
		 </form></div>
	</td></tr>
	</table>
	</center>
	<?php
	 include('copyright.php');
	?>
	</body>
	</html>
	<?php
}

function template_logout($msg='Logged out.') {
	global $LM_APP_NAME,$LM_DEFAULT_CSS,$LANG;
	?>
	<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
	<html>
	<head>
	<META http-equiv="Content-Type" content="text/html; charset=iso-8859-2">
	<META HTTP-EQUIV="Pragma" CONTENT="no-cache">
	<META HTTP-EQUIV="Refresh" CONTENT="3; URL=../wwwroot/index.php">
	<title><?php echo($LM_APP_NAME); ?> - Logged out</title>
	<?php
	applycss($LM_DEFAULT_CSS);
	?>
	<link rel="icon" href="../Graphics/favicon.ico" type="image/ico">
	</head>
	<body text="#000000" bgcolor="#FFFFFF">
	<center>
	<br>
	<table border="0" cellspacing="0" cellpadding="0" width="250" class="login">
	
	
	<tr><td><br><div class="tcen"><?=$msg?></div></td></tr>
	<tr><td><div class="tcen"><br>
		 <form method="get" action="">
			<input type="submit" value="Login again">
		 </form>
		 </div>
	</td></tr>
	</table>
	<?php
	include("copyright.php");
	?>
	</center>
	</body>
	</html>
	<?php
}

?>