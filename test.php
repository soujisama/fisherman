<?php
$then = strtotime('Fri Sep 02 11:00:37 +0000 2011');
$lag = 24 * 60 * 60 * 3;
$per = $then + lag;
$now = time();
echo $per . ' : ' . $now;
echo '<br/>';
if ($per > $now) echo 'selected';
?>