<?php
  //bTw0[Uat6t8F
  //wp
  //db_99bbf8_w351_1 
  //99bbf8_w351_1
  //_16D51H607e8Qz4q
  //php /home/soujirou/public_html/fisherman/app/ottoservice.php type=followSomeone
  error_reporting(E_ALL);
  ini_set("display_errors",1);
  
  //header("Content-Type: application/json; charset=UTF-8");
  //$access_token = $_SESSION['access_token'];
  $postdata = json_decode(file_get_contents("php://input"));
  if(!isset($postdata)) $postdata = (object)$_POST;
  if(!isset($postdata->type)) $postdata = (object)$_GET;
  require_once('../twitteroauth/twitteroauth.php');
  require_once('login/config.php');
  
  //var_dump($postdata);
  
  $type = $postdata->type;
  //$type = $_GET['type'];
  //echo 'start';
  //echo 'type= '.$type.'<br/>';

  class OTTOFishermanAPI {
    private $db;
    private $data;    
    private $access_token;
    private $tapi;
    private $count = 100;
    // Constructor - open DB connection
    function __construct($postdata) {
    	$this->data = $postdata;    	
        $this->db = new mysqli('localhost', DBUSERNAME, DBUSERPASS, 'soujirou_aunupe');
        $this->db->autocommit(FALSE);
    }
 
    // Destructor - close DB connection
    function __destruct() {
        $this->db->close();
    }
    
	function followSomeone() {
		//get id
		//f/lookup
		//true? deq 
		//else fl + t + deq + enq(iq)
		//$v = ;
		if(rand(0,10)>3) {
			$messages = array("Can you opt to follow us please.. :) thanks.", "cordially follow please :) thanks.", "could you genially follow please :) thanks..", "Hope it gladdens you to follow please :) thanks..", "hope you are obliged to follow :) thanks.", "Hope you could follow us please :) thanks.", "Hope you could lend credence by following please :) thanks..", "hope you find it high-minded to kindly follow please.. :) thanks..", "Hope you find it venerable to kindly follow please :) thanks..", "it is our longing that you see it fit to follow please.. :) thanks.", "it would be eminent if you could kindly follow please :) thanks", "it would be estimable if you could kindly follow please.. :) thanks.", "it would be estimable if you could kindly follow please.. :) thanks. ", "it would be illustrious of you, if you could kindly follow please :) thanks..", "it would be revered, if you could follow please :) thanks..", "This bespeaks you could kindly follow, please :) thanks!", "We anticipate it delights you to follow please :) thanks.", "we crave your indulgence to follow us please :) thanks.", "we hope you could join our audience by following please :) thanks..", "we hope you find it virtuous to be on our follower list please. :) thanks.", "We implore you to follow, please :) thanks.", "we look forward to having you as a follower :) please & thank you.. ", "would be winsome, if you could follow please :) thanks. ", "you are beckoned to follow please :) thanks. ", "Your follow is kindly solicited please.. :) thanks.");
			for($i=0;$i<2;$i++) {
				$sql = $this->db->prepare("SELECT a.uid, token, secret, handle_id FROM fisherman_follow_view a 
				LEFT JOIN fisherman_users b ON a.uid = b.uid
				where a.uid = 1323434076
				LIMIT 1 ");
				$sql->execute();
				$sql->bind_result($uid,$token,$secret,$handle_id);
				$sql->fetch();
				//var_dump($sql);
				
				if(isset($uid,$token,$secret,$handle_id)) {
					$tapi = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET, $token, $secret);
					$parameters = array('target_id' => $handle_id);
					//echo $uid . " " . $token . " " . $secret . " " . $handle_id;
					$graph = $tapi->get('friendships/show',$parameters);
					/*print_r($graph);
					echo "<br/><br/><br/>";
					var_dump($graph);
					echo "<br/><br/><br/>";*/
					echo(json_encode($graph));
					echo "<br/><br/><br/>";
					
					echo $graph->relationship->source->following ." ". $graph->relationship->target->following;
					
					if(!($graph->relationship->source->following || $graph->relationship->target->following)) {
						$parameters = array('user_id'=>$handle_id,'follow'=>true);
						$status = $tapi->post('friendships/create',$parameters); //$handle_id
						var_dump($status);
						echo "<br/><br/>";
						if(!isset($status->errors)) {
							$parameters = array('status' => $messages[array_rand($messages)].' @'.$graph->relationship->target->screen_name);
							$status = $tapi->post('statuses/update',$parameters);
							var_dump($status);
						}
						else echo "couldn't friend :(";
						
						if(!isset($status->errors)) {
							$sql->close();
							echo '<br/>--<br/>'.$status->id_str.'<br/>--<br/>';
							$sql = $this->db->prepare("call follow_dequeue(?,?,?,?)"); //increment tweets sent
							$sql->bind_param("ssss",$uid,$handle_id,$status->id_str,time());
							$sql->execute();
						}
						else if($status->errors[0]->code == 159) {
							//suspended account	
							$sql->close();
							echo "suspended account? ".$uid." ".$handle_id;
							$sql = $this->db->prepare("DELETE FROM fisherman_follow_queue WHERE uid = ? AND handle_id = ?");
							var_dump($sql);
							$sql->bind_param("ss",$uid,$handle_id);
							$sql->execute();
						}
						else echo "couldn't send :(";
					}
					else {
						$sql->close();
						echo "already friends, shouldn't have gotten here ".$uid." ".$handle_id;
		//				$sql = $this->db->prepare("delete from fisherman_follow_queue where uid = ? and handle_id = ?");
						$sql = $this->db->prepare("DELETE FROM fisherman_follow_queue WHERE uid = ? AND handle_id = ?");
						var_dump($sql);
						$sql->bind_param("ss",$uid,$handle_id);
						$sql->execute();				
					}
				}
				else echo 'no one to follow :(';
				$sql->close();
			}
		}
	}
	
	function unfollowSomeone() {
		$days = 3;
		$lag = $days*60*60;
		$sql = $this->db->prepare("SELECT a.uid, token, secret, handle_id, beg_id FROM fisherman_inquire_queue a 
		LEFT JOIN fisherman_users b ON a.uid = b.uid
		where ? - a.adate > ?
		LIMIT 1 ");
		$sql->bind_param("ss",time(),$lag);
		$sql->execute();
		$sql->bind_result($uid,$token,$secret,$handle_id,$beg_id);
		$sql->fetch();
		var_dump($sql);
		echo $uid . " : " . $token . " : " . $secret . " : " . $handle_id . " : " . $beg_id. " : ".time()." : ".$lag."<br/><br/>";
		if(isset($uid,$token,$secret,$handle_id)) {
			$tapi = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET, $token, $secret);
			$parameters = array('target_id' => $handle_id);
			//echo $uid . " " . $token . " " . $secret . " " . $handle_id;
			$graph = $tapi->get('friendships/show',$parameters);
			/*print_r($graph);
			echo "<br/><br/><br/>";
			var_dump($graph);
			echo "<br/><br/><br/>";*/
			echo(json_encode($graph));
			echo "<br/><br/><br/>";
			
			echo $graph->relationship->source->following ." ". $graph->relationship->target->following;
			if(!$graph->relationship->target->following) {
				//increment no follow
				$sql->close();
				$sql = $this->db->prepare("update fisherman_users set unfollowed = unfollowed + 1 where uid = ".$uid);
				$sql->execute();
				$parameters = array('user_id'=>$handle_id);
				$status = $tapi->post('friendships/destroy',$parameters); //$handle_id
				var_dump($status);
				echo "<br/><br/>";
				
				$status = $tapi->post('statuses/destroy/'.$beg_id);
				var_dump($status);
				echo "<br/><br/>";
			}
			else {
				//increment followed
				$sql->close();
				$sql = $this->db->prepare("update fisherman_users set followed = followed + 1 where uid = ".$uid);
				$sql->execute();
			}
			
			if(!isset($status->errors)) {
				$sql->close();
				$sql = $this->db->prepare("delete from fisherman_inquire_queue where uid = ? and handle_id = ?");
				var_dump($sql);
				$sql->bind_param("ss",$uid,$handle_id);
				var_dump($sql);
				$sql->execute();
				var_dump($sql);
			}
			else echo "couldn't unfriend :(";
		}
		else echo 'no one to unfollow :)';
		$sql->close();
	}
}

//echo $type;
//var_dump($access_token);
$api = new OTTOFishermanAPI($postdata);
if($type == "followSomeone") $api->followSomeone();
else if($type == "unfollowSomeone") $api->unfollowSomeone();
else if($type == "getUserInfo") $api->getUserInfo();
else if($type == "getNextList") $api->getNextList();
else if($type == "addToQueue") $api->addToQueue();
?>