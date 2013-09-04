<?php

class ConsultationController {

  // main entry point for crawling public consultations

  public static function crawlConsultations() {
    # start at the stop level consultation listing.
    $html = file_get_contents("http://ottawa.ca/en/city-hall/public-consultations");
    $html = strip_tags($html,"<a>");
    $matches = array();
    preg_match_all("/<a[^h]+href=\"([^\"]+)\"[^>t]+title=\"([^\"]+)\"[^<]+<\/a>/",$html,$matches);
    #print_r($matches);
    for ($x = 0; $x < count($matches[0]); $x++) {
      if (! preg_match('/Learn/',$matches[2][$x])) { continue; }
      $matches[2][$x] = preg_replace('/Learn more about /','',$matches[2][$x]);
      $url = $matches[1][$x];
      $category = $matches[2][$x];
      self::crawlCategory($category,"http://ottawa.ca{$url}");
      #exit;
    }
  }

  // crawl a category for its consultations

  public static function crawlCategory ($category, $url) {
    #print "CATEGORY: $category, URL: $url\n";
    $html = file_get_contents($url);

    $html = preg_replace("/\n/","KEVINO_NEWLINE",$html);
    $html = preg_replace("/<head.*<body/","<body",$html);
    $html = preg_replace("/<script[^<]+<\/script>/"," ",$html);
    $html = preg_replace("/KEVINO_NEWLINE/","\n",$html);
    $html = preg_replace("/ & /"," and ",$html);

    $html = strip_tags($html,"<div><a>");
    $xml = simplexml_load_string($html);
    $div = $xml->xpath('//div[@id="cityott-content"]');
    $div = simplexml_load_string($div[0]->asXML());

    $links = $div->xpath('//a');
    foreach ($links as $a) {
      $url = $a->attributes();
      if (substr($url,0,1) == '/') {
        $url = "http://ottawa.ca{$url}";
      }

      $title = $a[0];
      self::crawlConsultation($category,$title,$url);
    }

  }

  // crawl a specific consultation 

  public static function crawlConsultation ($category, $title, $url) {

    print "---------------------------------------\n";
    print "CATEGORY: $category\n";
    print "TITLE: $title\n";
    print "URL: $url\n";

    #print "$url :: $title\n";
    $html = file_get_contents($url);
    $html = preg_replace("/\n/","KEVINO_NEWLINE",$html);
    $html = preg_replace("/<head.*<body/","<body",$html);
    $html = preg_replace("/<script[^<]+<\/script>/"," ",$html);
    $html = preg_replace("/KEVINO_NEWLINE/","\n",$html);
    $html = preg_replace("/ & /"," and ",$html);
    $html = strip_tags($html,"<div><a>");

    # the view-dom-id CLASS changes randomly, so remove it
    # example: view-dom-id-b477d62d0bdb286acc260d50c820060d
    $html = preg_replace("/view-dom-id-[a-z0-9]+/","",$html);

    $xml = simplexml_load_string($html);
    $div = $xml->xpath('//div[@id="cityott-content"]');
    $contentMD5 = md5($div[0]->asXML());
    print "MD5: $contentMD5\n";
    file_put_contents($contentMD5,$div[0]->asXML());

    $div = simplexml_load_string($div[0]->asXML());
    $links = $div->xpath('//a');
    foreach ($links as $a) {
      #print_r($a);
      $docLink = $a->attributes();
      $docTitle = $a[0];
      print "  DOCUMENT: $docTitle $docLink\n";
      print implode("|",array("CSV",$category,$title,$url,$docTitle,$docLink,"\n"));
    }
  }
  
}

?>
