<?php
/**
 * @author Naomichi Yamakita <yamakita@dtx.co.jp>
 * @category mars
 * @package net.service.twitter
 * @version $Id: Mars_TwitterOAuthProvider.class.php 3108 2011-10-16 15:21:11Z yamakita $
 * @copyright Copyright (c) 2006-2012 dt corporation
 * @since 1.9.0
 */

require __DIR__ . '/vendors/twitteroauth/twitteroauth.php';

/**
 * Twitter 上で OAuth 認可を行なうための機構を提供します。
 * 
 * @link http://dev.twitter.com/pages/auth Authenticating Requests with OAuth
 * @since 1.9.0
 * @author Naomichi Yamakita <yamakita@dtx.co.jp>
 * @category mars
 * @package net.service.twitter
 */

class Mars_TwitterOAuthProvider extends Mars_3LeggedOAuthProvider
{
  /**
   * エンドポイントベース URI。
   */
  const ENDPOINT_BASE_URI = 'https://api.twitter.com';

  /**
   * コンストラクタ。
   * コンシューマキー、及びコンシューマ秘密鍵はプロパティファイルに定義することも可能です。
   * 
   * config/global_properties.yml の設定例:
   * <code>
   * twitter:
   *   # コンシューマキー。
   *   consumerKey:
   *   
   *   # コンシューマ秘密鍵。
   *   consumerSecret:
   * </code>
   * @see Mars_OAuthProvider::__construct()
   * @author Naomichi Yamakita <yamakita@dtx.co.jp>
   */
  protected function __construct($consumerKey = NULL, $consumerSecret = NULL)
  {
    $config = Mars_Config::loadProperties('twitter');

    if ($consumerKey === NULL) {
      $consumerKey = array_find($config, 'consumerKey');
    }

    if ($consumerSecret === NULL) {
      $consumerSecret = array_find($config, 'consumerSecret');
    }

    parent::__construct($consumerKey, $consumerSecret);
  }

  /**
   * @see Mars_OAuthProvider::getEndpointBaseURI()
   * @author Naomichi Yamakita <yamakita@dtx.co.jp>
   */
  public function getEndpointBaseURI()
  {
    return self::ENDPOINT_BASE_URI;
  }

  /**
   * OAuth プロバイダの通称を取得します。
   * 
   * @return string OAuth プロバイダの通称を返します。
   * @author Naomichi Yamakita <yamakita@dtx.co.jp>
   */
  public function getProvider()
  {
    return Mars_OAuthFactory::PROVIDER_TWITTER;
  }

  /**
   * @param string $callback {@link Mars_3LeggedOAuthProvider::buildCallbackURI()} メソッドを参照。
   * @see Mars_3LeggedOAuthProvider::buildAuthorizeURI()
   * @author Naomichi Yamakita <yamakita@dtx.co.jp>
   */
  public function buildAuthorizeURI($callback = NULL)
  {
    $uri = $this->buildCallbackURI($callback);

    // 未承認のリクエストトークンを取得
    try {
      // コンシューマーキー (あるいは秘密鍵) が不正な場合、getRequestToken() 実行時に 'Undefined index' エラーが発生する
      $twitter = new Mars_TwitterOAuth($this->_consumerKey, $this->_consumerSecret);
      $requestToken = $twitter->getRequestToken($uri);

    } catch (ErrorException $e) {
      $message = 'Consumer key (or secret) is invalid.';
      throw new RuntimeException($message); 
    }

    $this->user->setAttribute('_twitterRequestToken', $requestToken['oauth_token']);
    $this->user->setAttribute('_twitterRequestTokenSecret', $requestToken['oauth_token_secret']);

    return $twitter->getAuthorizeUrl($requestToken);
  }

  /**
   * @param string $callback {@link Mars_3LeggedOAuthProvider::buildCallbackURI()} メソッドを参照。
   * @see Mars_3LeggedOAuthProvider::authorize()
   * @author Naomichi Yamakita <yamakita@dtx.co.jp>
   */
  public function authorize($callback = NULL)
  {
    $this->response->sendRedirect($this->buildAuthorizeURI($callback));
  }

  /**
   * @see Mars_3LeggedOAuthProvider::parseAccessToken()
   * @author Naomichi Yamakita <yamakita@dtx.co.jp>
   */
  public function parseAccessToken()
  {
    $requestToken = $this->user->getAttribute('_twitterRequestToken');
    $requestTokenSecret = $this->user->getAttribute('_twitterRequestTokenSecret');

    // アクセストークンの取得
    $twitter = new Mars_TwitterOAuth($this->_consumerKey,
      $this->_consumerSecret,
      $requestToken,
      $requestTokenSecret);
    $verifier = $this->request->getQuery('oauth_verifier');

    $this->user->removeAttribute('_twitterRequestToken');
    $this->user->removeAttribute('_twitterRequestTokenSecret');

    try {
      $this->_accessToken = $twitter->getAccessToken($verifier);

    } catch (ErrorException $e) {
      // 401 Unauthorized
      throw new Mars_RequestException('Couldn\'t get access token.');
    }
  }

  /**
   * @see Mars_OAuthProvider::isAuthorized()
   * @author Naomichi Yamakita <yamakita@dtx.co.jp>
   */
  public function isAuthorized()
  {
    if ($this->_accessToken) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * @see Mars_3LeggedOAuthProvider::getAccessToken()
   * @author Naomichi Yamakita <yamakita@dtx.co.jp>
   */
  public function getAccessToken()
  {
    return $this->_accessToken;
  }

  /**
   * @see Mars_3LeggedOAuthProvider::send() 
   * @author Naomichi Yamakita <yamakita@dtx.co.jp>
   */
  public function send($uri, $requestMethod = Mars_HttpRequest::HTTP_GET, $parameters = array(), $files = array())
  {
    $uri = $this->buildEndpointURI($uri);
    $accessToken = $this->getAccessToken();

    if (!isset($accessToken['oauth_token'])) {
      throw new Mars_RequestException('Access token is not set.');
    }

    if (!isset($accessToken['oauth_token_secret'])) {
      throw new Mars_RequestException('Access token secret is not set.');
    }

    $twitter = new Mars_TwitterOAuth($this->_consumerKey,
      $this->_consumerSecret,
      $accessToken['oauth_token'],
      $accessToken['oauth_token_secret']);

    $response = $twitter->oAuthRequest($uri, $requestMethod, $parameters);
    $parser = new Mars_HttpResponseParser($twitter->http_info, $response);

    return $parser;
  }
}

