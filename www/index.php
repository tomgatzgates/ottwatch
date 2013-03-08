<?php

include_once '../lib/include.php';
include_once 'epiphany/src/Epi.php';
include_once 'controllers/MeetingController.php';
include_once 'controllers/DevelopmentApp.php';

Epi::setPath('base', 'epiphany/src');
Epi::init('route');

getRoute()->get('/', 'dashboard');
getRoute()->get('/about', 'about');
#getRoute()->get('/dashboard', 'dashboard');

getRoute()->get('/lobbyist/search/(.*)', 'lobbyist_search');
getRoute()->get('/lobbyist/([^\/]*)', 'lobbyist');
getRoute()->get('/lobbyist/(.*)/details', 'lobbyistDetails');
getRoute()->get('/lobbyist/(.*)/link', 'lobbyistLink');

getRoute()->get('/devapps', array('DevelopmentAppController','listAll'));
getRoute()->get('/devapps/([^\/]+)', array('DevelopmentAppController','viewDevApp'));

getRoute()->get('/meetings/file/(\d+)', array('MeetingController','getFileCacheUrl'));
getRoute()->get('/meetings', array('MeetingController','dolist')); // meetings
getRoute()->get('/meetings/([^\/]*)', array('MeetingController','dolist')); // meetings/CATEGORY
getRoute()->get('/meetings/meetid/(\d+)', array('MeetingController','meetidForward')); // meetings/CATEGORY/ID
getRoute()->get('/meetings/([^\/]*)/(\d+)', array('MeetingController','meetingDetails')); // meetings/CATEGORY/ID
getRoute()->get('/meetings/([^\/]*)/(\d+)/item/(\d+)', array('MeetingController','meetingDetails')); // meetings/CATEGORY/ID
getRoute()->get('/meetings/([^\/]*)/(\d+)/item/(\d+)/(files|files.json)', array('MeetingController','itemFiles')); // meetings/CATEGORY/ID

getRoute()->get('.*', 'error404');
getRoute()->run();

function ottawaMediaRSS() {
  $url = "http://ottawa.ca/rss/news_en.xml";
  $rss = file_get_contents($url);
  $xml = simplexml_load_string($rss);
  $items = $xml->xpath("//item");
  print "<h4>Media Releases</h4>\n";
  foreach ($items as $item) {
    $title = $item->xpath("title"); $title = $title[0].'';
    $link = $item->xpath("link"); $link = $link[0].'';
    print "<small><a href=\"$link\" target=\"_blank\">$title</a></small><br/>\n";
  }
}

function dashboard() {
  global $OTT_WWW;
  top();
  ?>
  <div class="row-fluid">
  <div class="span4">
  <table class="table table-bordered table-hover table-condensed" style="width: 100%;">
  <tr>
  <td colspan="3">
  <h4>Today's Meetings</h4>
  </td>
  </tr>
  <?php
  $meetings = getDatabase()->all(" select id,meetid,category,date(starttime) starttime from meeting where date(starttime) = date(CURRENT_TIMESTAMP) order by starttime ");
  foreach ($meetings as $m) {
    $mtgurl = htmlspecialchars("http://app05.ottawa.ca/sirepub/mtgviewer.aspx?meetid={$m['meetid']}&doctype");
    ?>
    <tr>
      <td><?php print meeting_category_to_title($m['category']); ?></td>
      <td style="text-align: center; width: 90px;"><?php print $m['starttime']; ?></td>
      <td style="text-align: center;"><a class="btn btn-mini" href="<?php print "$OTT_WWW/meetings/{$m['category']}/{$m['meetid']}"; ?>">Agenda</a></td>
    </tr>
    <?php
  }
  ?>
  <tr>
  <td colspan="3">
  <h4>Upcoming Meetings</h4>
  </td>
  </tr>
  <?php
  $meetings = getDatabase()->all(" select id,category,date(starttime) starttime,meetid from meeting where date(starttime) > date(CURRENT_TIMESTAMP) order by starttime ");
  foreach ($meetings as $m) {
    $mtgurl = htmlspecialchars("http://app05.ottawa.ca/sirepub/mtgviewer.aspx?meetid={$m['meetid']}&doctype");
    ?>
    <tr>
      <td><?php print meeting_category_to_title($m['category']); ?></td>
      <td style="text-align: center; width: 90px;"><?php print $m['starttime']; ?></td>
      <td style="text-align: center;"><a class="btn btn-mini" href="<?php print "$OTT_WWW/meetings/{$m['category']}/{$m['meetid']}"; ?>">Agenda</a></td>
    </tr>
    <?php
  }
  ?>
  <tr>
  <td colspan="3">
  <h4>Previous Meetings</h4>
  </td>
  </tr>
  <?php
  $meetings = getDatabase()->all(" select id,meetid,category,date(starttime) starttime from meeting where date(starttime) < date(CURRENT_TIMESTAMP) order by starttime desc limit 10 ");
  foreach ($meetings as $m) {
    $mtgurl = htmlspecialchars("http://app05.ottawa.ca/sirepub/mtgviewer.aspx?meetid={$m['meetid']}&doctype");
    ?>
    <tr>
      <td><?php print meeting_category_to_title($m['category']); ?></td>
      <td style="text-align: center; width: 90px;"><?php print $m['starttime']; ?></td>
      <td style="text-align: center;"><a class="btn btn-mini" href="<?php print "$OTT_WWW/meetings/{$m['category']}/{$m['meetid']}"; ?>">Agenda</a></td>
    </tr>
    <?php
  }
  ?>
  <tr>
  <td colspan="3">
  <a href="<?php print $OTT_WWW; ?>/meetings/all">See all meetings</a>
  </td>
  </tr>
  </table>
  </div>
  <div class="span4">
  <script>
  function lobbyist_search_form_submit() {
    v = document.getElementById('lobbyist_search_value').value;
    if (v == '') {
      alert('Cannot perform an empty search');
      return;
    }
    document.location.href = 'lobbyist/search/'+v;
  }
  </script>
  <h4>Lobbyist Registry</h4>
  <div class="input-append">
  <input type="text" id="lobbyist_search_value" placeholder="Search by name...">
  <button class="btn" onclick="lobbyist_search_form_submit()"><i class="icon-search"></i> Search</button>
  </div>

  <?php
  ottawaMediaRSS();
  ?>

  <h4>Development Applications</h4>
  <table class="table table-bordered table-hover table-condensed" style="width: 100%;">
  <?php
  $count = getDatabase()->one(" select count(1) c from devapp ");
  $count = $count['c'];
  $apps = getDatabase()->all(" select appid,devid,date(statusdate),apptype statusdate from devapp order by statusdate desc limit 1 ");
  $apps = getDatabase()->all(" select * from devapp order by updated desc limit 5 ");
  foreach ($apps as $a) {
    $url = DevelopmentAppController::getLinkToApp($a['appid']);
    $addr = explode("|",$a['address']);
    $addr = $addr[0];
    ?>
    <tr>
    <td><small><a href="<?php print $url; ?>"><?php print $a['devid']; ?></a></small></td>
    <td><small><?php print $a['apptype']; ?></small></td>
    <td><small><?php print $addr; ?></small></td>
    </tr>
    <?php
    #print "<a href=\"$url\">{$a['devid']}</a> {$a['apptype']}: {$addr}<br/>";
    #pr($a);
  }
  ?>
  <tr>
  <td colspan="3">
  <center>
  <a href="devapps">View all <?php print $count; ?>  development applications</a>
  </center>
  </td>
  </tr>
  </table>

  </div>

  <div class="span4">
  <a class="twitter-timeline" data-dnt="true" href="https://twitter.com/ottwatch" data-widget-id="306310112971210752">Tweets by @ottwatch</a>
  <script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0];if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src="//platform.twitter.com/widgets.js";fjs.parentNode.insertBefore(js,fjs);}}(document,"script","twitter-wjs");</script>
  </div>

  </div>
  <?php
  bottom();
}

function about() {
  top();
  include("about_content.html");
  bottom();
}

function home() {
}

function lobbyist_search($name) {
  top("Lobbyist Search: $name");
  $matches = lobbyistSearch($name);
  $vs = $matches["__vs"]; unset($matches["__vs"]);
  $ev = $matches["__ev"]; unset($matches["__ev"]);

#  if (count($matches) == 1) {
#    reset($matches);
#    $name = key($matches);
#    header("Location: ../$name");
#    return;
#  }
  if (count($matches) == 0) {
    ?>
    <div class="row-fluid">
    <div class="span12">
    <h3>No Matches</h3>
    </div>
    </div>
    <?php
    bottom();
    return;
  }
  ?>
  <div class="row-fluid">
  <h3>Found <?php print count($matches); ?> matches.</h3>
  <table class="table table-hover table-condensed">
  <tr>
  <th>Name</th>
  <th>Link</th>
  </tr>
  </tr>
  <?php
  foreach ($matches as $name => $ctl) {
    ?>
    <tr>
    <td>
    <a href="../<?php print $name; ?>"><?php print $name; ?></a>
    </td>
    <td>
    <a target="_blank" href="../<?php print $name; ?>/link"><i class="icon-edit"></i></a>
    </td>
    </tr>
    <?php
  }
  ?>
  </table>
  </div>
  <?php
  bottom();
}

function lobbyist($name) {
  top("Lobbyist: $name");
  ?>
  <div class="row-fluid">
  <div class="span12">
  <iframe style="border: 0px; width: 100%; height: 1200px;" src="<?php print urlencode($name); ?>/details"></iframe>
  </div>
  </div>
  <?php
  bottom();
}

function lobbyistDetails($name) {
  global $OTT_LOBBY_SEARCH_URL;
  $matches = lobbyistSearch($name);
  $vs = $matches["__vs"]; unset($matches["__vs"]);
  $ev = $matches["__ev"]; unset($matches["__ev"]);

  $m = $matches[$name];
  if (count($m) == 0) {
    print "Not found<br/>";
    return;
  }

  if (count($m) > 1) {
    print "<h4>Multiple Lobbyists found named '$name'</h4>\n";
    $index = 0;
    foreach ($m as $t) {
      $index++;
      ?>
      <form method="post" action="<?php print $OTT_LOBBY_SEARCH_URL; ?>">
      <input type="hidden" name="__VIEWSTATE" value="<?php print $vs; ?>"/>
      <input type="hidden" name="__EVENTVALIDATION" value="<?php print $ev; ?>"/>
      <input type="hidden" name="<?php print $t; ?>" value=""/>
      <input type="submit" value="<?php print ($name.' '.$index); ?>"/>
      </form>
      <?php
    }
    return;
  }

  $fields = array(
    '__VIEWSTATE' => $vs,
    '__EVENTVALIDATION' => $ev,
    $ctl => ''
  );
  $html = sendPost($OTT_LOBBY_SEARCH_URL,$fields);
  $lines = explode("\n",$html);
  $html = array();
  $add = 1;
  foreach ($lines as $line) {
    if ($add) {
      array_push($html,$line);
    }
    if (preg_match("/<body/",$line)) {
      $add = 0;
    }
    if (preg_match("/Header End/",$line)) {
      $add = 1;
    }
    if (preg_match("/Search Lobbyist Design/",$line)) {
      $add = 0;
    }
    if (preg_match("/End Search Lobbyist Design/",$line)) {
      $add = 1;
    }
  }
  $base = "\n<base href=\"https://apps107.ottawa.ca/LobbyistRegistry/search/\" target=\"_blank\"/>\n";
  $html = implode("\n",$html);
  $html = preg_replace("/<head>/","<head>$base",$html);
  print $html;

}

function lobbyistLink($name) {
  global $OTT_LOBBY_SEARCH_URL;

  # get search page
  $html = file_get_contents($OTT_LOBBY_SEARCH_URL);
  $ev = getEventValidation($html);
  $vs = getViewState($html);
	$fields = array(
	  '__VIEWSTATE' => $vs,
	  '__EVENTVALIDATION' => $ev,
    'ctl00$MainContent$btnSearch' => 'Search',
	  'ctl00$MainContent$txtLobbyist' => $name
	);
  $html = sendPost($OTT_LOBBY_SEARCH_URL,$fields);
 
  # find name in search results and forward to first one that is found.
  # TODO: potential defect if two people are registered under same name?
  $lines = explode("\n",$html);
  $ev = getEventValidation($html);
  $vs = getViewState($html);
  $matches = array();
  foreach ($lines as $line) {
    if (preg_match("/gvSearchResults.*LnkLobbyistName/",$line)) {
      $zname = $line;
      $zname = preg_replace("/.*<u>/","",$zname);
      $zname = preg_replace("/<.*/","",$zname);
      $ctl = $line;
      $ctl = preg_replace("/.*;ctl/","ctl",$ctl);
      $ctl = preg_replace("/&.*/","",$ctl);
      if ($zname == $name) {
        # exact match for the one we are looking for.
  			$fields = array(
  			  '__VIEWSTATE' => $vs,
  			  '__EVENTVALIDATION' => $ev,
  		    $ctl => ''
  			);
        autoSubmitForm($OTT_LOBBY_SEARCH_URL,$fields,"Forwarding to $name lobbyist page");
      }
      $matches[$zname] = $ctl;
    }
  }

  if (count($matches) == 1) {
    # not exact match, but only one, so forward anyway
    reset($matches);
    $zname = key($matches);
    $ctl = $matches[$zname];
		$fields = array(
		  '__VIEWSTATE' => $vs,
		  '__EVENTVALIDATION' => $ev,
	    $ctl => ''
		);
    autoSubmitForm($OTT_LOBBY_SEARCH_URL,$fields,"Forwarding to $zname lobbyist page");
    return;
  }

  print "<h3>Exact match not found.</h3>\n";
  print "<ul>\n";
  foreach ($matches as $zname => $ctl) {
    print "<li><a href=\"../$zname/link\">$zname</a></li>\n";
  }
  print "</ul>\n";

  #print "Lobbyist search results for $name<hr/>";
  #print "<hr/>";
  #print $html;

  #header("Location: http://google.ca");
  #print "Will now 302 redirect to Ottawa.ca for lobbyist <b>$name</b> (".time().")\n";
}

function error404() {
  top();
  ?>
  <div class="row-fluid">

  <div class="span4">&nbsp;</div>
  <div class="span4">
  <h1>Error!</h1>
  <h4>Somehow, you've found a page that does not work.</h4>
  <h5>I should put a fail-whale here or something.</h5>
  </div>
  <div class="span4">&nbsp;</div>

  </div>
  <?php
  bottom();
}

function top($title) {
  global $OTT_WWW;
?>
<!DOCTYPE html>
<html>
<!-- <?php print $_SERVER['REQUEST_URI']; ?> -->
<head>
<title><?php print $title; ?></title>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="<?php print $OTT_WWW; ?>/bootstrap/css/bootstrap.min.css" rel="stylesheet" media="screen" type="text/css">
<link href="<?php print $OTT_WWW; ?>/bootstrap/css/bootstrap-responsive.css" rel="stylesheet">
<style type="text/css">
  body {
  padding: 20px;
}
</style>
<script src="http://code.jquery.com/jquery.js" type="text/javascript"></script>
<script src="<?php print $OTT_WWW; ?>/bootstrap/js/bootstrap.min.js"></script>
<script>
function copyToClipboard (text) {
  window.prompt ("Copy to clipboard: Ctrl+C, Enter", text);
}
</script>
</head>
<body>

<div class="row-fluid">
<div class="span12">
<div class="navbar"><div class="navbar-inner">
<ul class="nav">
<li><a href="<?php print $OTT_WWW; ?>">Home</a></li>
<!--<li><a href="<?php print $OTT_WWW; ?>/dashboard">Dashboard</a></li>-->
<li><a href="<?php print $OTT_WWW; ?>/about">About</a></li>
</ul>
</div></div>
</div>
</div>

<?php
	if ($title != '') {
    if (0) {
		?>
		<div style="background: #fcfcfc; padding: 10px; border: #c0c0c0 solid 1px;">
		<div class="row-fluid">
		<div class="lead span6">
		<?php print $title; ?>
		</div>
		</div>
		</div>
		<?php
    }
	}
}

function bottom() {
  global $OTT_WWW;
  ?>
<div style="margin-top: 10px; background: #fcfcfc; padding: 10px; border: #c0c0c0 solid 1px;">
<a href="<?php print $OTT_WWW; ?>"><img alt="Ottawa Watch Logo" style="float: right; padding-left: 5px; width: 50px; height: 50px;" src="<?php print $OTT_WWW; ?>/img/ottwatch.png"/></a>
<i>
Follow <b><a href="http://twitter.com/OttWatch">@OttWatch</a></b> on Twitter too.
Created by <a href="http://kevino.ca"><b>Kevin O'Donnell</b></a> to make it easier to be part of the political conversation in Ottawa.</i>
<div style="clear: both;"></div>
</div>
  <?php
  googleAnalytics();
  ?>

    <script type="text/javascript">
    /* * * CONFIGURATION VARIABLES: EDIT BEFORE PASTING INTO YOUR WEBPAGE * * */
    var disqus_shortname = 'ottwatch'; // required: replace example with your forum shortname

    /* * * DON'T EDIT BELOW THIS LINE * * */
    (function () {
        var s = document.createElement('script'); s.async = true;
        s.type = 'text/javascript';
        s.src = 'http://' + disqus_shortname + '.disqus.com/count.js';
        (document.getElementsByTagName('HEAD')[0] || document.getElementsByTagName('BODY')[0]).appendChild(s);
    }());
    </script>

  </body>
  </html>
  <?php
}

?>



