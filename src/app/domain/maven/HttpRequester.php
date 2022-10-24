<?php

namespace app\domain\maven;
use GuzzleHttp\Client;

/* caching on request scope */
class HttpRequester
{
  private static $cache = [];

  static function request($url)
  {
    // prevent metadata requests to CDN (maven.axonivy.com) - cache last too long.
    $url = str_replace("https://maven.axonivy.com/", "https://nexus-mirror.axonivy.com/repository/maven/", $url);
    if (!isset(self::$cache[$url])) {
      $client = new Client();
      $options = ['http_errors' => false];
      $res = $client->request('GET', $url, $options);
      $content = '';
      if ('200' == $res->getStatusCode()) {
        $content = $res->getBody();
      }
      self::$cache[$url] = $content;
    }
    return self::$cache[$url];
  }
}
