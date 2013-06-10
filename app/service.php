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
	public $followerIndex = 0;
	public $followingIndex = 0;
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
			$param = array('cursor' => $cur);
			$ret = $this->tapi -> get($method.$handle, $param);
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
    	$sql = $this->db -> prepare('select handle_id,handle,followers,following,tweets,ldate from '.$table.' where uid = ? limit ?, ?');
    	$sql->bind_param('sss',$this->access_token['user_id'],$cursor,$this->count);
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
					$userObject = new User($handle_id,$handle,$followers,$following,$tweets,$ldate);
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
			if($user->statuses_count == 0 || !isset($user->status)) continue;
			$userObject = new User($user->id_str,$user->screen_name,$user->followers_count,$user->friends_count,
									$user->statuses_count,$user->status->created_at,$user->id_str);
			if($userObject->tweetCount > 0 && $userObject->followingCount/$userObject->followerCount > 0.7) $userObject->selected = true;
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
        $ret = (object) array(
        			"uid" => $user->id_str,
        			"name" => $user->screen_name,
        			"followerCount" => $user->followers_count,
        			"followingCount" => $user->friends_count,
        			"status" => (object) array('type' => 'success', 'message' => 'What are we fishing for today?')
        );
	    
	    $ignore_list = $this -> getIds('followers/ids/',$user->screen_name);
	    $ignore_list = array_merge($ignore_list,$this -> getIds('friends/ids/',$user->screen_name));
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
			$ids = $this -> getIds('followers/ids/', $user->id_str);
			$this->saveIds($ids, 'fisherman_follower_lists');
			//var_dump($ids);
			
			$ids = $this -> getIds('friends/ids/', $user->id_str);		
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
	
	function getDestinations() {
		$sql = $this->db->prepare('select name, destination from comment_marker');
		$sql->execute();
		$sql->bind_result($name,$destination);
		$prev = "";
		$map = array();
		$array = array();
		while($sql->fetch()) {
			if($prev == "") $prev = $name;
			if($prev != $name) {
				$map[$prev] = $array;
				$prev = $name;
				$array = array();
			}
			$array[] = $destination;
		}
		$sql->close();
		$map[$prev] = $array;
		sendJSONResponse(200,$map);
	}
	
	function uploadCM() {
		$name = $_GET['name'];
		$user = $_GET['user'];
		$destination = $_GET['destination'];
		$lat = $_GET['lat'];
		$lng = $_GET['lng'];
		$init = $_GET['init'];
		$date = $_GET['date'];
		
		//echo $name." ".$user." ".$destination." ".$lat." ".$lng." ".$date;
		
		$sql = $this->db->prepare("insert into comment_marker (name,user,destination,lat,lng,initial,date) values (?, ?, ?, ?, ?, ?, ?)");
		$sql->bind_param("sssssis",$name,$user,$destination,$lat,$lng,$init,$date);
		$sql->execute();
		$this->db->commit();
		$ret = $sql->insert_id;
		
		if($ret > 0) {
			$sender = new SenderAPI();
			$message = array("message"=>"New Comment Marker","type"=>0,"cm_id"=>$ret);
			$sender->send($message);
		}
		//if insert_id > 0 : send gcm(NEW CM) to ALL // only create notification for those in the vicinity
		
		$sql->close();
		sendResponse(200,$ret);
		//echo $ret;
	}
	
	function uploadC() {
		$cm = $_GET['cm'];
		$user = $_GET['user'];
		$text = $_GET['text'];
		$condition = $_GET['condition'];
		$date = $_GET['date'];
		
		//echo $name.$destination.$lat.$lng.$date;
		
		$sql = $this->db->prepare("insert into comment (comment_marker,user,comment_text,status,date) values (?, ?, ?, ?, ?)");
		$sql->bind_param("issis",$cm,$user,$text,$condition,$date);
		$sql->execute();
		$this->db->commit();
		$ret = $sql->insert_id;
		
		if($ret > 0) {
			$sender = new SenderAPI();
			$message = array("message"=>"New Comment","type"=>0,"cm_id"=>$cm);
			$sender->send($message);
		}
		
		//if insert_id > 0 : send gcm(NEW COMMENT) to comment_marker.user -> get REGISTERED_ID;
		$sql->close();
		sendResponse(200,$ret);
		//echo $ret;
	}
	
	function uploadR() {
		$comment = $_GET['comment'];
		$user = $_GET['user'];
		$vote = $_GET['vote'];
		$date = $_GET['date'];
		
		
		//echo $comment.$user.$vote.$date;
		
		$sql = $this->db->prepare("insert into rating (user,vote,comment,date) values (?,?,?,?) on duplicate key update vote = ?, date = ?");
		$sql->bind_param("sissis",$user,$vote,$comment,$date,$vote,$date);
		$sql->execute();
		$this->db->commit();
		$ret = $sql->insert_id;
		
		if(isset($_GET['changed'])) {
			$sender = new SenderAPI();
			$message = array("message"=>"Status Changed","type"=>0,"cm_id"=>$_GET['changed']);
			$sender->send($message);
		}
		
		$sql->close();
		sendResponse(200);
	}
}

//echo $type;
//var_dump($access_token);
$api = new FishermanAPI($postdata,$access_token);
if($type == "registerUser") $api->registerUser();
else if($type == "getOwnerInfo") $api->getOwnerInfo();
else if($type == "getUserInfo") $api->getUserInfo();
else if($type == "uploadR") $api->uploadR();
else if($type == "getDestinations") $api->getDestinations();
?>