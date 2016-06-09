<?php

class SyndicationController {
	public static function publish() {

		# time of last run
    $last = getvar('syndicate.last');
    if ($last == '') { $last = time(); }

		$now = time();
    setvar('syndicate.last',$now);

		# whats new doc?
    $rows = getDatabase()->all(" 
			select
				f.*,
				from_unixtime(:last) last,
				from_unixtime(:now) now
			from 
				feed f
			where 
				f.created >= from_unixtime(:last)
				and f.created < from_unixtime(:now)
			order by
				f.created
    ",array('now'=>$now,'last'=>$last));

    if (count($rows) > 10) {
			$r = array();
			$r['message'] = count($rows)." updates found, so you'll have to read them individually here ";
			$r['url'] = null;
			$r['path'] = '/feed/';
 			self::twitter($r);
      return;
    }

		foreach ($rows as $r) {
			$message = $r['message'];
			$path = $r['path'];
			$url = $r['url'];

      try {
  			self::twitter($r);
      } catch (Exception $e) {
        print $e;
      }
      try {
				# facebook broke b/c I changed my password and I just cant care to fix it, so
  			# self::facebook($r);
      } catch (Exception $e) {
        print $e;
      }
		}
	}

	public static function twitter($r) {
		$message = $r['message'];
		$url = $r['url'];
		if ($url == null) {
			$url = OttWatchConfig::WWW.$r['path'];
		}
		$message = preg_replace('/ Cycling /i',' #ottbike ',$message);
		$message = preg_replace('/ bikeway /i',' #ottbike ',$message);
    $tweet = tweet_txt_and_url($message,$url);

		if ($tweet == 'Lobbying: Jeff Polowin (Coventry Connections Inc.) Taxi issues http://bit.ly/1t1fJQJ') {
			$tweet = 'Lobbying: Jeff Polowin (Coventry Connections Inc.) Taxi issues http://bit.ly/1t1fJQJ http://pic.twitter.com/Nb7TVCEl7v';
		}

    tweet($tweet);
  }

	public static function facebook($r) {
		# POST variables to FB
		$data = array();

		# page post permission
		$row = getDatabase()->one(" select * from variable where name = 'fb_page_access_token' ");
		if (! $row['value']) {
			print "ERROR: no fb_page_access_token\n";
			return;
		}
		$data['access_token'] = $row['value'];

		$data['message'] = $r['message'];
		$data['link'] = $r['url'];
		if ($data['link'] == null) {
			# go with path
			$data['link'] = OttWatchConfig::WWW.$r['path'];
		}

		$url = "https://graph.facebook.com/".OttWatchConfig::FACEBOOK_PAGE_ID."/links";
		$json = sendPost($url,$data);
		$result = json_decode($json);

		if (!isset($result->id)) {
			print "\n!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!\n";
			print "ERROR posting to Facebook\n";
			pr($r);
			print "\n";
			print "----- DATA -----\n";
			pr($data);
			print "\n";
			print "----- JSON -----\n";
			print "$json\n";
			print "----- RESULT -----\n";
			pr($result);
			print "\n";
		}
		
	}
}
