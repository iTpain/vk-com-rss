<?php

/*
	Если спрашивает капчу то другой хеадер
*/
//header('Content-Type: text/html; charset=cp1251');
Header('Content-type: text/xml');
require_once 'monica.class.php';

$Monica = new Monica(11);

if($_GET['item'] == NULL) break;

# Captcha
if(isset($_POST['submit_captcha']))
{
	$Monica->vkgroup($_GET['item'],'token',$_POST['captcha_sid'],$_POST['captcha_key']);
}
else
{
	$Monica->vkgroup($_GET['item'],'token');
}

echo("<rss version='2.0'><channel>");
$Monica->build();
echo("</channel></rss>");
?>