<?php

class MeetingController {

  static public function getDocumentUrl ($meetid,$doctype) {
    return "http://app05.ottawa.ca/sirepub/agview.aspx?agviewmeetid={$meetid}&agviewdoctype={$doctype}";
  }

  static public function getItemUrl ($itemid) {
    return "http://app05.ottawa.ca/sirepub/agdocs.aspx?doctype=agenda&itemid=".$itemid;
  }

  static public function getMeetingUrl ($id) {
    global $OTT_WWW;
    $row = getDatabase()->one(" select id,category from meeting where id = :id ",array("id" => $id));
    if (!$row['id']) {
      return "";
    }
    return "$OTT_WWW/meetings/{$row['category']}/{$row['id']}";
  }

  static public function meetidForward ($meetid) {
    $m = getDatabase()->one(" select * from meeting where meetid = :meetid ",array("meetid" => $meetid));
    if (!$m['id']) {
      error404();
      return;
    }
    header("Location: ../{$m['category']}/{$m['id']}");
  }

  static public function itemFiles ($category,$id,$itemid,$format) {
    $files = getDatabase()->all(" select * from ifile where itemid = :id order by id ",array('id' => $itemid));
    if ($format == 'files.json') {
      print json_encode($files);
      return;
    }
    foreach ($files as $f) {
      $url = "http://app05.ottawa.ca/sirepub/view.aspx?cabinet=published_meetings&fileid={$f['fileid']}";
      print "<i class=\"icon-file\"></i> <a target=\"_blank\" href=\"{$url}\">{$f['title']}</a><br/>\n";
    }
  }

  static public function meetingDetails ($category,$id,$itemid) {
    $m = getDatabase()->one(" select * from meeting where id = :id ",array("id" => $id));
    if (!$m['id']) {
      self::doList($category);
      return;
    }
    $agendaUrl = self::getDocumentUrl($m['meetid'],'AGENDA');
    $title = meeting_category_to_title($m['category']);
    $item = getDatabase()->one(" select * from item where id = :id ",array("id" => $itemid));
    if ($item['itemid']) {
      # page title, and agenda url can be updated early
      $agendaUrl .= '#Item'.$item['itemid'];
      $title = $item['title'];
    }
    $zoomingTo = "Zooming to: $title";

    $items = getDatabase()->all(" select * from item where meetingid = :id order by id ",array("id" => $id));
    top($title);
    ?>

    <script>
    <!-- <a name="Item168199"></a> -->
    function highlightItem(id,itemid) {

      // move the agenda iFrame to the <a name=""/> for the chosen item
      $('#agendaFrame').attr('src','<?php print self::getDocumentUrl($m['meetid'],'AGENDA'); ?>#Item' + itemid);

      // build a REST link back to OttWatch for the item, and create
      // a Tweet button for it.
      owItemUrl = '<?php print self::getMeetingUrl($id); ?>/item/' + id;
      itemTitle = $('#itemAnchor'+id).html();
      tweetText = 'Reading "' + itemTitle + '" ';
      while ((tweetText.length + owItemUrl.length) > 139) {
        itemTitle = itemTitle.substring(0,itemTitle.length-1);
        tweetText = 'Reading "' + itemTitle + '"... ';
      }
      shareUrl = 'https://twitter.com/share?url='+escape(owItemUrl)+'&text=' + escape(tweetText);

      itemTitle = $('#itemAnchor'+id).html();
      newHtml = 
        ' <a target="_blank" href="'+shareUrl+'"><img alt="Tweet" src=\"<?php print OttWatchConfig::WWW; ?>/img/twitter-share.png\"/></a> ' +
        ' <a target="_blank" href="https://www.facebook.com/sharer/sharer.php?u='+escape(owItemUrl)+'">' + 
        ' <img style="height: 20px; width: 58px;" alt="Tweet" src="<?php print OttWatchConfig::WWW; ?>/img/facebook-share.png"/></a>' +
        ' <a href="javascript:copyToClipboard(\'' + owItemUrl + '\');" class="btn btn-mini">Clipboard <i class="icon-share"></i></a> ' +
        '';
      $('#itemDetailsTitle').html(itemTitle);
      $('#itemDetailsShare').html(newHtml);

      // load file data
      $.get(owItemUrl + '/files', function(data) {
        $('#itemDetailsFiles').html(data);
        $(this).scrollTop(0);
      });


    }
    </script>

    <div class="row-fluid">
    <div class="span4 visible-desktop">
    <div style="overflow:scroll; height: 600px; padding-right: 5px; padding-left: 5px;">
    <ol>
    <?php
    foreach ($items as $i) {
      ?>
      <li><a id="itemAnchor<?php print $i['id']; ?>" href="javascript:highlightItem(<?php print $i['id'].','.$i['itemid']; ?>);"><?php print $i['title']; ?></a></li>
      <?php
    }
    ?>
    <ol>
    </div>
    </div>

    <?php 
    $ttu = OttWatchConfig::WWW."/meetings/{$m['category']}/{$m['id']}";
    ?>
    <div class="span8">

    <div style="padding: 5px; margin-bottom: 5px; ">
    <div id="itemDetailsTitle" style="font-weight: bold;"></div>
    <div id="itemDetailsFiles"></div>
    
    <div id="itemDetailsShare" class="pull-right">
    <a target="_blank" 
      href="https://twitter.com/share?url=<?php 
      print urlencode($ttu); 
      ?>&text=<?php 
      print urlencode("Reading an agenda for $title"); 
      ?>"><img alt="Tweet" src="<?php print OttWatchConfig::WWW; ?>/img/twitter-share.png"/></a>
    <a target="_blank" href="https://www.facebook.com/sharer/sharer.php?u=<?php print urlencode($ttu); ?>"><img style="height: 20px; width: 58px;" alt="Tweet" src="<?php print OttWatchConfig::WWW; ?>/img/facebook-share.png"/></a>
    <a href="javascript:copyToClipboard('<?php print urlencode($ttu); ?>');" class="btn btn-mini">Clipboard <i class="icon-share"></i></a>
    </div>

    </div><!-- item details -->

    <iframe id="agendaFrame" src="<?php print $agendaUrl; ?>" style="width: 100%; height: 600px; border: 1px solid #000000;"></iframe>

    </div><!-- span8 -->

    </div><!-- row -->

    <?php
    if ($item['itemid']) {
      # cleaner to call javascript to update the UI, rather than try and hack the correct state everywhere in advance.
      ?>
      <script>javascript:highlightItem(<?php print $item['id'].','.$item['itemid']; ?>);</script>
      <?php
    } else {
      ?>
      <script>$('#itemDetailsTitle').html('<?php print $title; ?>');</script>
      <?php
    }

    bottom();
  }

  static public function doList ($category) {
    global $OTT_WWW;
    top();
    if ($category == 'all' || $category == '') { 
      $category = ''; 
      $title = 'All Categories';
    } else {
      $title = meeting_category_to_title($category);
    }
    ?>

<div id="navbar-example" class="navbar navbar-static">
<div class="navbar-inner">
<div class="container" style="width: auto;">

<ul class="nav" role="navigation">
<a class="brand" href="#">Filter</a>
<li class="dropdown">
  <a id="drop1" href="#" class="dropdown-toggle" data-toggle="dropdown"><?php print $title; ?> <b class="caret"></b></a>
  <ul class="dropdown-menu" role="menu" aria-labelledby="drop1">
    <?php
    $rows = getDatabase()->all(" select category,title from category order by title ");
    foreach ($rows as $r) { 
      ?>
      <li role="menuitem"><a href="<?php print urlencode($r['category']); ?>"><?php print $r['title']; ?></a></li>
      <?php
    }
    ?>
    <li role="menuitem" class="divider"></li>
    <li role="menuitem"><a href="all">All Meetings</a></li>
  </ul>
</li>
<!--
<li class="dropdown">
  <a id="drop2" href="#" class="dropdown-toggle" data-toggle="dropdown">Date <b class="caret"></b></a>
  <ul class="dropdown-menu" role="menu" aria-labelledby="drop1">
    <li role="menuitem" class="divider"></li>
    <li role="menuitem"><a href="all">All Dates</a></li>
  </ul>
</li>
-->
</ul>

</div>
</div>
</div> 

    <table class="table table-bordered table-hover table-condensed" style="width: 100%;">
    <?php
    $rows = getDatabase()->all(" 
      select m.id,m.category,id,meetid,date(starttime) starttime
      from meeting m 
        join category c on c.category = m.category 
      ".
      ($category == '' ? '' : ' where c.category = :category ')
      ."
      order by starttime desc ",
      array('category' => $category));
    foreach ($rows as $r) { 
      $mtgurl = htmlspecialchars("http://app05.ottawa.ca/sirepub/mtgviewer.aspx?meetid={$r['meetid']}&doctype");
      $myurl = htmlspecialchars($OTT_WWW."/meetings/{$r['category']}/{$r['id']}");
      ?>
	    <tr>
	      <td style="width: 90px; text-align: center;"><?php print $r['starttime']; ?></td>
	      <td style="width: 90px; text-align: center;"><a class="btn btn-mini" href="<?php print $myurl; ?>">Agenda</a></td>
	      <td>
        <a href="<?php print $myurl; ?>"><?php print meeting_category_to_title($r['category']); ?></a>
        </td>
	      <td>
        <?php
        $count = getDatabase()->one(" select count(1) c from item where meetingid = :id ",array('id'=>$r['id']));
        print "{$count['c']} items";
        ?>
        </td>
	    </tr>
      <?php
    }
    ?>
    </table>
    <?php
    bottom();
  }

  /*
   */
  static public function downloadAndParseMeeting ($id) {

    $m = getDatabase()->one(" select * from meeting where id = :id ",array('id'=>$id));
    if (!$m['id']) { 
      print "downloadAndParseMeeting for $id :: NOT FOUDN\n";
      return; 
    }

    print "downloadAndParseMeeting for meeting:$id\n";

    # get agenda HTML
    $agenda = file_get_contents(self::getDocumentUrl($m['meetid'],'AGENDA')); 

    # charset issues
    $agenda = mb_convert_encoding($agenda,"ascii");

    # XML issues
    $agenda = preg_replace("/&nbsp;/"," ",$agenda);

    # rebuild item rows
    getDatabase()->execute(" delete from item where meetingid = :id ",array('id'=>$id));

    # scrape out item IDs, and titles.
	  $lines = explode("\n",$agenda);
    $add = 0;
    $spool = array();
	  foreach ($lines as $line) {
      $line = preg_replace("/\r/","",$line);
	    if (preg_match("/itemid=/",$line)) {
        $add = 1;
	      $itemid = $line;
	      $itemid = preg_replace("/.*itemid=/","",$itemid);
	      $itemid = preg_replace('/".*/',"",$itemid);
	      $items[] = $itemid;
        array_push($spool,$line);
        continue;
	    }
	    if ($add && preg_match("/a>/",$line)) {
        $add = 0;
        array_push($spool,$line);
        $snippet = "<a ".implode($spool)."\n";
        $snippet = preg_replace("/<\/a>.*/","</a>",$snippet);
        $snippet = preg_replace("/target=pubright/",'',$snippet);
        $snippet = preg_replace("/lang=[^>]*/",'',$snippet);
        $snippet = preg_replace("/\n/",' ',$snippet);
        $snippet = preg_replace("/\r/",' ',$snippet);
        $xml = simplexml_load_string($snippet);
				if (!is_object($xml)) {
					print "WARNING, bad snippet\n";
					print ">> $snippet <<\n";
					$title = '<i class="icon-warning-sign"></i> Doh! title autodection failed';
				} else {
	        $title = $xml->xpath("//span"); $title = $title[0].'';
	        if ($title == '') {
	          $title = $xml->xpath("//a"); $title = $title[0].'';
	        }
	
	        # charset problems
	        $title = preg_replace("/ \? /"," - ",$title);
	        $title = preg_replace("/\?/","'",$title);
	
	        # fix open/close brace, and spaces next to braces
	        $title = preg_replace("/^\(/","",$title);
	        $title = preg_replace("/\)\s*$/","",$title);
	        $title = preg_replace("/  +/"," ",$title);
	        $title = preg_replace("/\( +/","(",$title);
	        $title = preg_replace("/ +\)/",")",$title);
				}
	  	  $dbitemid = getDatabase()->execute('insert into item (meetingid,itemid,title) values (:meetingid,:itemid,:title) ', array(
	  	    'meetingid' => $id,
	  	    'itemid' => $itemid,
	  	    'title' => $title,
	  	  ));
        $spool = array();
      }
      if ($add) {
        array_push($spool,$line);
      }
	  }

    # purge existing files; not needed as delete ITEM cascades
    # getDatabase()->execute(" delete from ifile where itemid in (select id from item where meetingid = :id) ",array('id'=>$id));

    # go back to the database to build up the "items" in this meeting, and then go grab
    # all the files too.
    $items = getDatabase()->all(' select * from item where meetingid = :id ', array('id' => $id));

	  foreach ($items as $item) {
      print "  item:{$item['id']} title: {$item['title']}\n";
	    $html = file_get_contents(self::getItemUrl($item['itemid']));
		  $lines = explode("\n",$html);
	    $files = array();
		  foreach ($lines as $line) {
		    if (preg_match("/fileid=/",$line)) {
          $line = preg_replace("/\n/","",$line);
          $line = preg_replace("/\r/","",$line);

          $title = $line;
          $title = preg_replace("/.*&nbsp;/","",$title);
          $title = preg_replace("/<.*/","",$title);

		      $fileid = $line;
		      $fileid = preg_replace("/.*fileid=/","",$fileid);
		      $fileid = preg_replace('/".*/',"",$fileid);

          print "    file: $title\n";
		  	  getDatabase()->execute('insert into ifile (itemid,fileid,title,created,updated) values (:itemid,:fileid,:title,CURRENT_TIMESTAMP,CURRENT_TIMESTAMP) ', array(
		  	    'itemid' => $item['id'],
		  	    'fileid' => $fileid,
		  	    'title' => $title,
		  	  ));
		    }
		  }
	  }

  }

  
}

?>
