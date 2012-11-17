<?php
/**
 * @author Naomichi Yamakita <yamakita@dtx.co.jp>
 * @category mars
 * @package net.service.facebook
 * @version $Id: Mars_FacebookOAuthProvider.class.php 3112 2011-10-16 19:00:30Z yamakita $
 * @copyright Copyright (c) 2006-2012 dt corporation
 * @since 1.9.0
 */

/**
 * facebook 上で OAuth 認可を行なうための機構を提供します。
 * 
 * @link http://developers.facebook.com/docs/authentication/ Authentication
 * @since 1.9.0
 * @author Naomichi Yamakita <yamakita@dtx.co.jp>
 * @category mars
 * @package net.service.facebook
 */

class Mars_FacebookOAuthProvider extends Mars_3LeggedOAuthProvider
{
  /**
   * エンドポイントベース URI。
   */
  const ENDPOINT_BASE_URI = 'https://graph.facebook.com';

  /**
   * コンストラクタ。
   * コンシューマキー、及びコンシューマ秘密鍵はプロパティファイルに定義することも可能です。
   *
   * config/global_properties.yml の設定例:
   * <code>
   * facebook:
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
    $config = Mars_Config::loadProperties('facebook');

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
   * @see Mars_OAuthProvider::getProvider()
   * @author Naomichi Yamakita <yamakita@dtx.co.jp>
   */
  public function getProvider()
  {
    return Mars_OAuthFactory::PROVIDER_FACEBOOK;
  }

  /**
   * @param string $callback {@link Mars_3LeggedOAuthProvider::buildCallbackURI()} メソッドを参照。
   * @param array $scope 認可する追加のパーミッション。
   *   詳細は {@link http://developers.facebook.com/docs/authentication/permissions/ Permissions} を参照。
   *   スコープが未指定の場合はユーザの基本情報にアクセス可能です。
   * <code>
   * $scope = array('email', 'read_stream');
   * $oauth->buildAuthorizeURI('OAuthCallback', $scope);
   * </code>
   * @see Mars_3LeggedOAuthProvider::buildAuthorizeURI()
   * @link http://developers.facebook.com/docs/authentication/ Authentication
   * @author Naomichi Yamakita <yamakita@dtx.co.jp>
   */
  public function buildAuthorizeURI($callback = NULL, $scope = array())
  {
    $queryData = array();
    $queryData['client_id'] = $this->_consumerKey;
    $queryData['redirect_uri'] = $this->buildCallbackURI($callback);
    $queryData['scope'] = implode(',', $scope);

    $query = http_build_query($queryData, '', '&');
    $uri = self::ENDPOINT_BASE_URI . '/oauth/authorize?' . $query;

    return $uri;
  }

  /**
   * @param string $callback {@link Mars_3LeggedOAuthProvider::buildCallbackURI()} メソッドを参照。
   * @param array $scope {@link Mars_FacebookOAuthProvider::buildAuthorizeURI()} メソッドを参照。
   * @see Mars_3LeggedOAuthProvider::buildAuthorizeURI()
   * @link http://developers.facebook.com/docs/authentication/ Authentication
   * @author Naomichi Yamakita <yamakita@dtx.co.jp>
   */
  public function authorize($callback = NULL, $scope = array())
  {
    $this->response->sendRedirect($this->buildAuthorizeURI($callback, $scope));
  }

  /**
   * @see Mars_3LeggedOAuthProvider::parseAccessToken()
   * @author Naomichi Yamakita <yamakita@dtx.co.jp>
   */
  public function parseAccessToken()
  {
    // 認証コードの取得
    $queryData = array();
    $queryData['client_id'] = $this->_consumerKey;
    $queryData['redirect_uri'] = $this->request->getURL(FALSE);
    $queryData['client_secret'] = $this->_consumerSecret;
    $queryData['code'] = $this->request->getQuery('code');

    $query = http_build_query($queryData, '', '&');
    $uri = self::ENDPOINT_BASE_URI . '/oauth/access_token?' . $query;

    $sender = new Mars_HttpRequestSender($uri);
    $parser = $sender->send();

    if ($parser->getStatus() == 200) {
      parse_str($parser->getContents(), $parameters);
      $this->_accessToken = $parameters;

    } else {
      $result = json_decode($parser->getContents());
      throw new Mars_RequestException($result->error->message);
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
   * @since 1.10.0
   * @author Naomichi Yamakita <yamakita@dtx.co.jp>
   */
  public function send($uri, $requestMethod = Mars_HttpRequest::HTTP_GET, $parameters = array(), $files = array())
  {
    if (!$this->isAuthorized()) {
      throw new Mars_RequestException('Access token is not set.');
    }

    $uri = $this->buildEndpointURI($uri);

    $sender = new Mars_HttpRequestSender($uri);
    $sender->setRequestMethod($requestMethod);
    $sender->addParameter('access_token', $this->_accessToken['oauth_token']);
    $sender->addParameters($parameters);

    if (sizeof($files)) {
      foreach ($files as $name => $path) {
        $sender->addUploadFile($name, $path);
      }
    }

    return $sender->send();
  }

  /**
   * ユーザのプロフィール情報を取得します。
   * 
   * @return stdClass ユーザ情報を格納した stdClass のオブジェクトインスタンスを返します。
   * @throws Mars_RequestException アクセストークンが不正な場合に発生。
   * @author Naomichi Yamakita <yamakita@dtx.co.jp>
   */
  public function getProfile()
  {
    if (!$this->isAuthorized()) {
      throw new Mars_RequestException('Access token is not set.');
    }

    $uri = self::ENDPOINT_BASE_URI . '/me?access_token=' . $this->_accessToken['access_token'];

    $sender = new Mars_HttpRequestSender($uri);
    $parser = $sender->send();
    $result = json_decode($parser->getContents());

    if ($parser->getStatus() == 200) {
      return $result;

    } else {
      throw new Mars_RequestException($result->error->message);
    }
  }
}
