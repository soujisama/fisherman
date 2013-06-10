<?php
/**
 * @file
 * Take the user when they return from Twitter. Get access tokens.
 * Verify credentials and redirect to based on response from Twitter.
 */

/* Start session and load lib */
session_start();
require_once('../../twitteroauth/twitteroauth.php');
require_once('config.php');
require_once('../../dbconnect/db_con.php');

/* If the oauth_token is old redirect to the connect page. */
if (isset($_REQUEST['oauth_token']) && $_SESSION['oauth_token'] !== $_REQUEST['oauth_token']) {
  $_SESSION['oauth_status'] = 'oldtoken';
  header('Location: ./clearsessions.php');
}

/* Create TwitteroAuth object with app key/secret and token key/secret from default phase */
$connection = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET, $_SESSION['oauth_token'], $_SESSION['oauth_token_secret']);

/* Request access tokens from twitter */
$access_token = $connection->getAccessToken($_REQUEST['oauth_verifier']);

/* Save the access tokens. Normally these would be saved in a database for future use. */
$_SESSION['access_token'] = $access_token;

/* Remove no longer needed request tokens */
unset($_SESSION['oauth_token']);
unset($_SESSION['oauth_token_secret']);

/* If HTTP response is 200 continue otherwise send to connect page to retry */
if (200 == $connection->http_code) {
  /* The user has been verified and the access tokens can be saved for future use */
  $_SESSION['status'] = 'verified';
  
  $fields = array('type' => 'registerUser', 'uid'=> $access_token['user_id'], 'token' => $access_token['oauth_token'],
                  'secret' => $access_token['oauth_token_secret'] , 'username' => $access_token['screen_name'], 'date' => time());
                  
  $headers = array('Content-Type: application/json');
						
  $ch = curl_init();
  curl_setopt($ch,CURLOPT_URL,'http://fisherman.soujirou.biz/app/service.php');
  curl_setopt($ch,CURLOPT_POST,true);
  curl_setopt($ch,CURLOPT_HTTPHEADER,$headers);
  curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
  curl_setopt($ch,CURLOPT_POSTFIELDS,json_encode($fields));
		
  $result = json_decode(curl_exec($ch));
  $err = curl_errno($ch);
  $errmsg  = curl_error($ch);
  $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

		//$header  = curl_getinfo( $ch );
		
		//if (!$errmsg =='') {die($err.':'.$errmsg);} 
		
		/*$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		if(curl_errno($ch)) { 
			echo 'Curl error: ' . curl_error($ch); 
		}*/
		
  curl_close($ch);
  //echo json_encode($fields);
  //echo '<br/>-----------------------------------<br/>';
  //var_dump($result, $err, $errmsg); 
  header('Location: ../index.php?httpcode='.$httpcode.'&mess='.$result);
  //var_dump($access_token);
} else {
  /* Save HTTP status for error dialog on connnect page.*/
  header('Location: ./clearsessions.php');
}
