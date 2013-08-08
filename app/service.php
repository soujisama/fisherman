<?php
  error_reporting(E_ALL);
  ini_set("display_errors",1);
  if(!isset($_SESSION)) {
    session_start();
  }
  header("Content-Type: application/json; charset=UTF-8");
  $access_token = $_SESSION['access_token'];
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
  
  class User {
    public $handle_id;
    public $handle;
    public $followerCount;
    public $followingCount;
    public $tweetCount;
    public $ldate;
	public $adate;
    public $selected = false;
    
    function __construct($id,$n,$frC,$fgC,$tC,$ld) {
    	$this->handle_id = $id;
    	$this->handle = $n;
    	$this->followerCount = $frC;
    	$this->followingCount = $fgC;
    	$this->tweetCount = $tC;
    	$this->ldate = $ld;
    }
  }
  
  class Search {
    public $handle_id;
    public $handle;
    public $followerCount;
    public $followingCount;
    public $followerList;
    public $followingList;
	public $followerIndex = 100;
	public $followingIndex = 100;
    public $status;
    
    function __construct($id,$n,$frC,$fgC,$frL,$fgL) {
    	$this->handle_id = $id;
    	$this->handle = $n;
    	$this->followerCount = $frC;
    	$this->followingCount = $fgC;
    	$this->followerList = $frL;
    	$this->followingList = $fgL;
    }
  }

  class FishermanAPI {
    private $db;
    private $data;    
    private $access_token;
    private $tapi;
    private $count = 100;
    // Constructor - open DB connection
    function __construct($postdata,$access_token) {
    	$this->data = $postdata;    	
    	$this->access_token = $access_token;
        $this->db = new mysqli('localhost', DBUSERNAME, DBUSERPASS, 'soujirou_aunupe');
        $this->db->autocommit(FALSE);
        $this->tapi = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET, $access_token['oauth_token'], $access_token['oauth_token_secret']);
		//echo CONSUMER_KEY, CONSUMER_SECRET;
		//var_dump($access_token);
    }
 
    // Destructor - close DB connection
    function __destruct() {
        $this->db->close();
    }
    
    function getIds($method,$handle) {    
		$ids = array();
		$cur = -1;
		do {
			$param = array('user_id' => $handle, 'cursor' => $cur);
			$ret = $this->tapi -> get($method, $param);
			//echo ($method);
			//var_dump($param);
			//var_dump($ret);
			$ids = array_merge($ids,$ret->ids);
			$cur = $ret->next_cursor_str;
		}
		while($cur!=0);		
		
		return $ids;
    }
    
    function cacheIds() {
    
    }
    
    function saveIds($ids,$table) {
    	$sql = $this->db->prepare("delete from $table where uid = ?");
    	$sql->bind_param('s',$this->access_token['user_id']);
    	$sql->execute();
    	$this->db->commit();
    	//$sql->close;
    	$sql = $this->db -> prepare('insert into '.$table.' (uid,handle_id) select ?,? from (select 1) t where not exists 
    								(select ignore_id from fisherman_ignore_lists where uid = ? and ignore_id = ?)');    						
    	//var_dump($sql);
    	foreach($ids as $id) {
    		$sql->bind_param('ssss',$this->access_token['user_id'],$id,$this->access_token['user_id'],$id);
    		$sql->execute();
    	}
    	$this->db->commit();

    }
    
    function fetch($table,$cursor) {
    	$sql = $this->db -> prepare('select handle_id,handle,followers,following,tweets,ldate from '.$table.' where uid = ? and handle_id not in (select handle_id from fisherman_follow_queue where uid = ?) limit ?, ?');
    	$sql->bind_param('ssss',$this->access_token['user_id'],$this->access_token['user_id'],$cursor,$this->count);
    	$sql->execute();
		$sql->bind_result($handle_id,$handle,$followers,$following,$tweets,$ldate);

		while($sql->fetch()) {    	
			//echo $table.' ';
			if($handle == "") {
				$ids = array($handle_id);
				while($sql->fetch()) array_push($ids,$handle_id);
				return $this->setFetch($ids,$table,$cursor);
			}
			else {
				$list = array();
				$userObject = new User($handle_id,$handle,$followers,$following,$tweets,$ldate);
				array_push($list,$userObject);
				while($sql->fetch()) {
					if($tweets == 0) continue;
					$userObject = new User($handle_id,$handle,$followers,$following,$tweets,$ldate);
					$lag = 3 * 24 * 60 * 60;
					if($userObject->tweetCount > 0 && $following/$followers > 0.7 && (strtotime($ldate) + $lag) > time()) $userObject->selected = true;
					array_push($list,$userObject);
				}
				return $list;	
			}
		}
    }
    
    function setFetch($ids,$table,$cursor) {
		$string = implode(",",$ids);
		$params = array('user_id' => $string);
    	$newIds = $this->tapi -> get("/users/lookup",$params);
		
		$sql = $this->db->prepare("update $table set handle = ?, followers = ?, following = ?, tweets = ?, ldate = ? where handle_id = ?");
		$list = array();
		foreach($newIds as $user) {
			//var_dump($user);
			$days = 7;
			$lag = $days * 24 * 60 * 60;
			if($user->statuses_count == 0 || !isset($user->status)) continue;
			$userObject = new User($user->id_str,$user->screen_name,$user->followers_count,$user->friends_count,
									$user->statuses_count,$user->status->created_at);
			if($userObject->tweetCount > 0 && $userObject->followingCount/$userObject->followerCount > 0.7 && (strtotime($userObject->ldate) + $lag) > time()) $userObject->selected = true;
			array_push($list,$userObject);
			$sql->bind_param("ssssss",$user->screen_name,$user->followers_count,$user->friends_count,
									$user->statuses_count,$user->status->created_at,$user->id_str);
			$sql->execute();
			$this->db->commit();
		}
		return $list;
    }
 
	
	function registerUser() {
		//echo '<br/>function registerUser begin<br/>';
		$uid = $this->data->uid;
		$token = $this->data->token;
		$secret = $this->data->secret;
		$username = $this->data->username;
		$registered = $this->data->date;
		$updates = 1;
		
		//echo 'registering user<br />';
		//var_dump($this->data);
		//echo $username." | ".$uid." | ".$token." | ".$secret." | ".$date;
		
		$sql = $this->db->prepare('insert into fisherman_users (uid,token,secret,username,registered) values (?,?,?,?,?) on duplicate key update token = ?, secret = ?, username = ?');
		//echo $sql->error;
		$sql->bind_param("ssssisss",$uid,$token,$secret,$username,$registered,$token,$secret,$username);
		$sql->execute();
		$this->db->commit();
		$ret = $sql->insert_id;	
		$err = $sql->error_list;
		
		if (count($err) == 0) {
			if($ret > 0) echo json_encode('successfully registered on server');
		}
		else {
			echo json_encode('error registering on the server. background service may be unavailable. ');
			//var_dump($err);
		}
		//echo "$ret -> registering device";
		
		//$this->registerDevice($ret);
	}
	
    function getOwnerInfo() {
        //echo 'function getUserInfo begin';
        //var_dump($this->tapi);
        $user = $this->tapi -> get('account/verify_credentials');
        //var_dump($user);
		$sql = $this->db -> prepare("select tweeted_at,followed,unfollowed,count(*) from fisherman_users a join fisherman_follow_queue b where a.uid = b.uid and a.uid = ?");
		//var_dump($sql);
		$sql->bind_param('s',$user->id_str);
		//var_dump($sql);
		$sql->execute();
		$sql->bind_result($t_at,$fd,$uf,$ct);
		$string = "";
		if($sql->fetch()) {
			$string = "You have tweeted at $t_at people<br/>And been followed by $fd people<br/><br/><br/>$uf people didn't follow back<br/>They have been unfollowed<br/><br/>You have $ct people in your follow queue<br/>";
		}
        $ret = (object) array(
        			"uid" => $user->id_str,
        			"name" => $user->screen_name,
        			"followerCount" => $user->followers_count,
        			"followingCount" => $user->friends_count,
					"information" => $string,
        			"status" => (object) array('type' => 'success', 'message' => 'What are we fishing for today?')
        );
	    $sql->close();
	    $ignore_list = $this -> getIds('followers/ids',$user->screen_name);
	    $ignore_list = array_merge($ignore_list,$this -> getIds('friends/ids',$user->screen_name));
	    $sql = $this->db -> prepare('insert into fisherman_ignore_lists (uid,ignore_id) values (?,?) on duplicate key update ignore_id = ignore_id');
	    //var_dump($sql);
	    foreach($ignore_list as $id) {
        	$sql->bind_param("ss",$user->id_str,$id);
        	$sql->execute();
        }
        $this->db->commit();
        
        echo json_encode($ret);
    }

	function getUserInfo() {
		$handle = $this->data->handle;
		
		//echo '<br /><br />now registering device<br/>';
		//echo $user." | ".$imei." | ".$info." | ".$registration." | ".$date."<br /><br />";
		
		$user = $this->tapi -> get('users/show/'.$handle);
		
		//var_dump($this->data);
		if(!isset($user->id)) {
			$status = array('type' => 'error', 'message' => 'bad handle');
			$ret = new Search(null,null,null,null,null,null);
			$ret->status = (object)$status;
			
			echo json_encode($ret);
		}
		else {		
			$ids = $this -> getIds('followers/ids', $user->id_str);
			$this->saveIds($ids, 'fisherman_follower_lists');
			//var_dump($ids);
			
			$ids = $this -> getIds('friends/ids', $user->id_str);		
			$this->saveIds($ids, 'fisherman_following_lists');
			//var_dump($ids);
			
			unset($ids);
			$followerList = $this -> fetch('fisherman_follower_lists',0);
			$followingList = $this -> fetch('fisherman_following_lists',0);
			$ret = new Search($user->id_str,$user->screen_name,$user->followers_count,$user->friends_count,$followerList,$followingList);
			$ret->status = (object)array('type' => 'success', 'message' => 'user grabbed, dattebayo!');
			
			echo json_encode($ret);		
		}
		
	}
	
	function getNextList() {
		$list = array();
		$tries = 0;
		do {
			if($this->data->list == 0) $list = $this -> fetch('fisherman_follower_lists',$this->data->cursor);
			else $list = $this -> fetch('fisherman_following_lists',$this->data->cursor);
			if(count($list) < 1) $this->data->cursor = 0;
			$tries++;
		}
		while(count($list < 1) && $tries < 3);
		$status = (object)array('type' => 'success', 'message' => 'list grabbed, dattebayo!');
		$ret = (object) array('list' => $list, 'cursor' => $this->data->cursor + 100, 'status' => $status);
		echo json_encode($ret);
	}
	
	function addToQueue() {
		$list = $this->data->list;
		$date = time();
		//$list = explode(',',$this->data->list);
		//echo $list;
		//echo '<br /><br />';
		//echo json_encode($list);
		
		//echo $name." ".$user." ".$destination." ".$lat." ".$lng." ".$date;
		
		$sql = $this->db->prepare("call follow_enqueue(?, ?, ?)");
		foreach($list as $id) {
			$sql->bind_param("sss",$this->access_token['user_id'],$id,$date);
			$sql->execute();
		}
		$this->db->commit();
		$ret = $sql->insert_id;
		$sql->close();
		//sendResponse(200,$ret);
		echo json_encode($ret);
	}
		
	function getQueueItems() {
		$queue = $this->data->queue;
		$cursor = $this->data->cursor;
		//echo $queue.' '.$cursor;
		$list = array();
		
		if($queue == 0) //follow
		$sql = $this->db->prepare("select handle_id, handle, followers, following, tweets, ldate, adate from fisherman_follow_queue where uid = ? order by adate limit ?, 30");
		else if($queue == 1) //inquire
		$sql = $this->db->prepare("select handle_id, handle, followers, following, tweets, ldate, adate from fisherman_inquire_queue where uid = ? order by adate limit ?, 30");
		$sql->bind_param("ss",$this->access_token['user_id'],$cursor);
		
		$sql->execute();
		$sql->bind_result($handle_id,$handle,$followers,$following,$tweets,$ldate,$adate);
		
		while($sql->fetch()) {   
			$userObject = new User($handle_id,$handle,$followers,$following,$tweets,$ldate);
			$since = time()-$adate;
			if($since > 24 * 60 * 60) $since = substr($since/(24*60*60),0,4) ."d ago";
			else if($since > 60 * 60) $since = substr($since/(60*60),0,4) ."h ago";
			else if($since > 60) $since = substr($since/(60),0,4) ."m ago";
			else $since = $since . "s ago";
			$userObject->adate = $since;
			array_push($list,$userObject);
		}
		$status = 'successfully loaded follower queue';
		$ret = (object)array('queue' => $list, 'status' => $status);
		echo json_encode($ret);
				
		$sql->close();
	}
}

//echo $type;
//var_dump($access_token);
$api = new FishermanAPI($postdata,$access_token);
if($type == "registerUser") $api->registerUser();
else if($type == "getOwnerInfo") $api->getOwnerInfo();
else if($type == "getUserInfo") $api->getUserInfo();
else if($type == "getNextList") $api->getNextList();
else if($type == "addToQueue") $api->addToQueue();
else if($type == "getQueueItems") $api->getQueueItems();
?>