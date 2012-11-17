<?php
/**
 * @author Naomichi Yamakita <yamakita@dtx.co.jp>
 * @category mars
 * @package net.service.twitter
 * @version $Id: Mars_TwitterOAuth.class.php 3106 2011-10-13 08:14:46Z yamakita $
 * @copyright Copyright (c) 2006-2012 dt corporation
 * @since 1.9.0
 */

/**
 * TwitterOAuth を拡張した Twitter OAuth ライブラリです。
 * TwitterOAuth との唯一の違いは、HTTP 通信時に CURL モジュールに依存しない点が挙げられます。
 * 
 * @link https://github.com/abraham/twitteroauth twitteroauth
 * @since 1.9.0
 * @author Naomichi Yamakita <yamakita@dtx.co.jp>
 * @category mars
 * @package net.service.twitter
 */

class Mars_TwitterOAuth extends TwitterOAuth
{
  /**
   * @see TwitterOAuth::http()
   * @author Naomichi Yamakita <yamakita@dtx.co.jp>
   */
  public function http($uri, $method, $postData = NULL)
  {
    // TwitterOAuth::http() はレスポンスヘッダを書き換えてるため使用しない
    // (Mars_ResponseParser の動作に影響するため)
    $sender = new Mars_HttpRequestSender($uri);
    $sender->setUserAgent($this->useragent);
    $sender->setReadTimeout($this->connecttimeout);
    $sender->addHeader('Expect', '');

    if (is_string($postData)) {
      parse_str($postData, $postData);
    }

    if ($method === 'POST') {
      $sender->setRequestMethod(Mars_HttpRequest::HTTP_POST);
      $sender->addParameters($postData);

    } else if ($method === 'DELETE') {
      $sender->setRequestMethod(Mars_HttpRequest::HTTP_DELETE);

      if (sizeof($postData)) {
        $uri = $uri . '?' . OAuthUtil::build_http_query($postData);
      }
    }

    $sender->setBaseURI($uri);
    $parser = $sender->send();

    $this->http_code = $parser->getStatus();
    $this->http_info = $parser->getRawHeader();
    $this->url = $uri;

    return $parser->getContents();
  }
}
