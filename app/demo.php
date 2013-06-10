<?php
  session_start();
  require_once('../twitteroauth/twitteroauth.php');
  require_once('login/config.php');
  require_once('../dbconnect/db_con.php');
  
  $access_token = $_SESSION['access_token'];
  $connection = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET,
				$access_token['oauth_token'], $access_token['oauth_token_secret']);
  $content = $connection->get('account/rate_limit_status');
  echo "Current API hits remaining: {$content->remaining_hits}.";

?>