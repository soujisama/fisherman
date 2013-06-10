<?php
  error_reporting(E_ALL);
  ini_set("display_errors",1);

if(!isset($_SESSION)) session_start();

if (empty($_SESSION['access_token']) || empty($_SESSION['access_token']['oauth_token']) || empty($_SESSION['access_token']['oauth_token_secret'])) {
    header('Location: login/clearsessions.php');
}

//require_once('../twitteroauth/twitteroauth.php');
//require_once('login/config.php');
//require_once('../dbconnect/db_con.php');

$access_token = $_SESSION['access_token'];
?>

<!DOCTYPE html>
<html class="no-js" lang="en">
<head>
    <meta charset="utf-8" />
    <!--meta name="viewport" content="width=device-width, initial-scale=1.0" /-->
    <title>Hello @<?php echo $access_token['screen_name'] ?>, where are we fishing today?</title>
    <link href="../bootstrap/css/bootstrap.min.css" type="text/css" rel="stylesheet" />
    <link href="../bootstrap/css/bootstrap-responsive.min.css" type="text/css" rel="stylesheet" />
    <link href="styles/style.css" type="text/css" rel="stylesheet" />
    <script src="../jquery/jquery-1.8.2.js" type="text/javascript"></script>
    <script src="../bootstrap/js/bootstrap.min.js" type="text/javascript"></script>
    <script src="../jquery/jquery-ui-1.9.2.min.js" type="text/javascript"></script>
    <script src="../jsextensions/jquery.imagesloaded.js" type="text/javascript"></script>
    <script src="../jsextensions/slimbox2.js" type="text/javascript"></script>
    <script src="../knockout/knockout-2.2.0.debug.js" type="text/javascript"></script>
    <script src="../knockout/knockout_mapping.js" type="text/javascript"></script>
    <script src="../modernizr/modernizr-2.6.2.js" type="text/javascript"></script>
    <script src="scripts/script.js" type="text/javascript"></script>
    <script type="text/javascript">
        $(function() {
            $.ajaxSetup({
                cache: false
            });
        });

        function demo() {
            $.get("demo.php", function(data) {
                alert(data);
            });
        }
    </script>
</head>
<body>
    <input type="hidden" id="httpcode" value="<?php echo $_GET['httpcode']; ?>" />
    <input type="hidden" id="mess" value="<?php echo $_GET['mess']; ?>" />
    <div class="container-fluid" style="height:100%">
        <section id="searchPane" data-bind="slideVisible: doingSearch, with: searchResult" class="container-fluid" style="height:90%; display:none;">
            <div style="background: url('images/image.jpg') center right no-repeat; height: 15%">
                <div style="background: rgba(255,255,255,0.7)">
                    <h1 data-bind="text: handle"></h1>
                    <p>
                        is following <span data-bind="text: followingCount"></span> people and is being followed by <span data-bind="text: followerCount"></span> people.
                    </p>
                </div>
            </div>
            <div class="row-fluid" style="height: 85%;">
                <div class="span12" style="height: 100%;">
                    <div class="row-fluid" style="height: 100%;">
                        <div class="span6" style="height: 100%;">
                            <!--ul data-bind="foreach: followerList" class="thumbnails">
                                <li class="span4" style="background: url('images/image.jpg') center center, rgba(255,255,255,0.1)">
                                    <div class="thumbnail"  style="background: rgba(255,255,255,0.7)">
                                        <label>@<span data-bind="text: name" class="lead"></span></label>
                                        <label>Followers: <span data-bind="text: followerCount"></span></label>
                                        <label>Following: <span data-bind="text: followingCount"></span></label>
                                    </div>
                                </li>
                            </ul-->
                            <div class="search-header">
                                Followers
                                <div class="pull-right">
                                    <button class="btn btn-info btn-mini">
                                    	ADD <span data-bind="text: $root.tempList().length"></span> TO FOLLOW QUEUE
                                    </button>
                                </div>
                            </div>
                            <div class="search-body">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th><input type="checkbox" class="checkbox" /></th>
                                            <th>handle</th>
                                            <th>followers</th>
                                            <th>following</th>
                                            <th># tweets</th>
                                            <th>last tweet</th>
                                        </tr>
                                    </thead>
                                    <tbody data-bind="foreach: followerList">
                                        <tr>
                                            <td><input type="checkbox" data-bind="checked: selected, click: $root.listChecked" class="checkbox" /></td>
                                            <td data-bind="text: handle"></td>
                                            <td data-bind="text: followerCount"></td>
                                            <td data-bind="text: followingCount"></td>
                                            <td data-bind="text: tweetCount"></td>
                                            <td data-bind="text: ldate"></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="span6" style="height: 100%;">
                            <!--ul data-bind="foreach: followingList" class="thumbnails">
                                <li class="span4" style="background: url('images/image.jpg') center center, rgba(255,255,255,0.1)">
                                    <div class="thumbnail"  style="background: rgba(255,255,255,0.7)">
                                        <label>@<span data-bind="text: name" class="lead"></span></label>
                                        <label>Followers: <span data-bind="text: followerCount"></span></label>
                                        <label>Following: <span data-bind="text: followingCount"></span></label>
                                    </div>
                                </li>
                            </ul-->
                            <div class="search-header">
                                Following
                                <div class="pull-right">
                                    <button class="btn btn-info btn-mini">
                                    	ADD <span data-bind="text: $root.selectedFollowingCount"></span> TO FOLLOW QUEUE
                                    </button>
                                </div>
                            </div>
                            <div class="search-body">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th><input type="checkbox" class="checkbox" /></th>
                                            <th>handle</th>
                                            <th>followers</th>
                                            <th>following</th>
                                            <th># tweets</th>
                                            <th>last tweet</th>
                                        </tr>
                                    </thead>
                                    <tbody data-bind="foreach: followingList">
                                        <tr>
                                            <td><input type="checkbox" data-bind="checked: selected, click: $root.listChecked" class="checkbox" /></td>
                                            <td data-bind="text: handle"></td>
                                            <td data-bind="text: followerCount"></td>
                                            <td data-bind="text: followingCount"></td>
                                            <td data-bind="text: tweetCount"></td>
                                            <td data-bind="text: ldate"></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <header><br /> <!--data-bind="text: status().message, css: status().type"-->
            <div id="statusBar" class="info" data-type="<?php if(isset($_GET['httpcode'])) echo $_GET['httpcode']; ?>"><?php if(isset($_GET['mess'])) echo $_GET['mess']; ?></div>
            <div class="pull-right">
                <form data-bind="submit: doSearch" class="form-inline" style="margin-bottom:0px; display:inline-block">
                    <span class="input-prepend" style="margin-bottom:0px">
                        <span class="add-on">@</span>
                        <input type="text" data-bind="value: searchString, valueUpdate: ['afterkeydown', 'propertychange', 'input']" placeholder="search by handle" />
                    </span>
                </form>
                <button class="btn" data-bind="click: showSettings">
                    <span class="icon-wrench"></span>
                </button>
            </div>
            <nav>
                <ul class="nav nav-tabs inline">
                    <li class="disabled"><a>fisherman</a></li>
                    <li class="disabled visible-wide"><a>Hello, @<?php echo $access_token['screen_name'] ?>! You are</a></li>
                    <li class="hidden-phone"><a href='#'>following <span data-bind="text: fisherman().followingCount"></span></a></li>
                    <li class="hidden-phone"><a href='#'>followed by <span data-bind="text: fisherman().followerCount"></span></a></li>
                    <li class="disabled visible-wide"><a>... and these are your queues</a></li>
                    <li class="active"><a href='#' data-bind="click: getFollowQueue">follow queue <span class="badge badge-info" data-bind="    text: followQueue().length">0</span></a></li>
                    <li class=""><a href='#'>inquiry queue <span class="badge badge-info" data-bind="text: inquiryQueue().length">0</span></a></li>
                </ul>
            </nav>
        </header>

        <section id="content" class="row-fluid" style="padding-top:20px">
            <div id="followQueue" class="span12">
                <div class="span3">
                    <h5>Follow queue information</h5>
                    <p>lol wuT!</p>
                </div>
                <div class="span9">
                    <table class="table text-center table-hover">
                        <thead>
                            <tr>
                                <th><input type="button" class="btn btn-danger btn-mini" value="delete selected" /></th>
                                <th>handle</th>
                                <th>followers</th>
                                <th>following</th>
                                <th>number of tweets</th>
                                <th>last tweet date</th>
                                <th class="hidden-phone hidden-tablet">date added</th>
                            </tr>
                        </thead>
                        <tbody data-bind="foreach: followQueue">
                            <tr>
                                <td><input type="checkbox" data-bind="checked: selected" class="checkbox" /></td>
                                <td data-bind="text: handle"></td>
                                <td data-bind="text: followers"></td>
                                <td data-bind="text: following"></td>
                                <td data-bind="text: tweetCount"></td>
                                <td data-bind="text: ldate"></td>
                                <td data-bind="text: adate" class="hidden-phone hidden-tablet"></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <!--div class="span2" style="display:none">
                    <button class="btn btn-danger">delete selected</button>
                </div-->
            </div>
        </section>
        <div id="search" style="position: absolute; left: 0px; top: -100%; height: 100%">
            SEARCH!
        </div>
        <div id="settings" style="position: absolute; left:100%; top: 0px; text-align: left; width: 100%; display: none">
            <h2>Settings!</h2>
        </div>
    </div>
    <div data-bind="text: ko.toJSON($root)"></div>
    <div id="loader">
    	<div id="icon"></div>
    </div>
</body>
</html>
