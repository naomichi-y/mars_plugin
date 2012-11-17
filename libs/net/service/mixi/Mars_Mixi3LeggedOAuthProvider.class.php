<?php
/**
 * @author Naomichi Yamakita <yamakita@dtx.co.jp>
 * @category mars
 * @package net.service.mixi
 * @version $Id: Mars_Mixi2LeggedOAuthProvider.class.php 3108 2011-10-16 15:21:11Z yamakita $
 * @copyright Copyright (c) 2006-2012 dt corporation
 * @since 1.10.5
 */

/**
 * mixi Graph API にアクセスする機能を提供します。
 * 
 * @link http://developer.mixi.co.jp/connect/mixi_graph_api/ mixi Graph API
 * @since 1.10.5
 * @author Naomichi Yamakita <yamakita@dtx.co.jp>
 * @category mars
 * @package net.service.mixi
 */

class Mars_Mixi3LeggedOAuthProvider extends Mars_3LeggedOAuthProvider
{
  /**
   * 認可 URI。(PC、スマートフォン)
   */
  const ENDPOINT_AUTH_URI = 'https://mixi.jp/connect_authorize.pl';

  /**
   * 認可 URI。(モバイル)
   */
  const ENDPOINT_AUTH_URI_MOBILE = 'http://m.mixi.jp/connect_authorize.pl';

  /**
   * アクセストークンを取得する URI。
   */
  const ENDPOINT_TOKEN_ISSUANCE_URI = 'https://secure.mixi-platform.com/2/token';

  /**
   * エンドポイントベース URI。
   */
  const ENDPOINT_BASE_URI = 'http://api.mixi-platform.com';

  /**
   * @var object Mars_UserAgent
   */
  private $_userAgent;

  /**
   * コンストラクタ。
   * コンシューマキー、及びコンシューマ秘密鍵はプロパティファイルに定義することも可能です。
   * 
   * config/global_properties.yml の設定例:
   * <code>
   * mixiGraph:
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
    $config = Mars_Config::loadProperties('mixiGraph');

    if ($consumerKey === NULL) {
      $consumerKey = array_find($config, 'consumerKey');
    }

    if ($consumerSecret === NULL) {
      $consumerSecret = array_find($config, 'consumerSecret');
    }

    $this->_userAgent = $this->request->getUserAgent();

    parent::__construct($consumerKey, $consumerSecret);
  }

  /**
   * @param array $scope 認可するパーミッション。
   *   詳細は {@link http://developer.mixi.co.jp/connect/mixi_graph_api/mixi_io_spec_top/ 技術仕様} を参照。
   * <code>
   * $scope = array('r_profile', 'w_share');
   * $oauth->buildAuthorizeURI($scope);
   * </code>
   * @see Mars_3LeggedOAuthProvider::buildAuthorizeURI()
   * @link http://developer.mixi.co.jp/connect/mixi_graph_api/api_auth/ 認証認可手順
   * @author Naomichi Yamakita <yamakita@dtx.co.jp>
   */
  public function buildAuthorizeURI($scope = array())
  {
    $queryData = array();
    $queryData['client_id'] = $this->_consumerKey; 
    $queryData['response_type'] = 'code';
    $queryData['scope'] = implode(' ', $scope);
    $queryData['state'] = Mars_OAuthFactory::PROVIDER_MIXI_3LEGGED;

    if ($this->_userAgent->isSmartphone()) {
      $queryData['display'] = 'touch';

    } else if ($this->_userAgent->isMobile()) {
      if ($this->_userAgent->isDoCoMo()) {
        $queryData['guid'] = 'ON';
      }

    } else {
      $queryData['display'] = 'pc';
    }

    if ($this->_userAgent->isMobile()) {
      $baseURI = self::ENDPOINT_AUTH_URI_MOBILE;
    } else {
      $baseURI = self::ENDPOINT_AUTH_URI;
    }

    $uri = $baseURI . '?' . http_build_query($queryData, '', '&');

    return $uri;
  }

  /**
   * @param array $scope {@link Mars_Mixi3LeggedOAuthProvider::buildAuthorizeURI()} メソッドを参照。
   * @see Mars_3LeggedOAuthProvider::buildAuthorizeURI()
   * @link http://developer.mixi.co.jp/connect/mixi_graph_api/api_auth/ 認証認可手順
   * @author Naomichi Yamakita <yamakita@dtx.co.jp>
   */
  public function authorize($scope = array())
  {
    $this->response->sendRedirect($this->buildAuthorizeURI($scope));
  }

  /**
   * @see Mars_3LeggedOAuthProvider::parseAccessToken()
   * @author Naomichi Yamakita <yamakita@dtx.co.jp>
   */
  public function parseAccessToken()
  {
    $queryData = array();
    $queryData['grant_type'] = 'authorization_code';
    $queryData['client_id'] = $this->_consumerKey;
    $queryData['client_secret'] = $this->_consumerSecret;
    $queryData['code'] = $this->request->getQuery('code');
    $queryData['redirect_uri'] = $this->request->getURL(FALSE);

    $sender = new Mars_HttpRequestSender(self::ENDPOINT_TOKEN_ISSUANCE_URI);
    $sender->setRequestMethod(Mars_HttpRequest::HTTP_POST);
    $sender->addParameters($queryData);
    $parser = $sender->send();

    $data = $parser->getJSONData(TRUE);

    if (isset($data['error'])) {
      $message = sprintf('Failed to obtain an access token. [%s]', $data['error']);
      throw new Mars_RequestException($message);
    }

    $this->_accessToken = $data;
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
   * アクセストークンを再発行します。
   * トークンの再発行に成功した場合、{@link Mars_Mixi3LeggedOAuthProvider::getAccessToken()} は新しいトークンを返すようになります。
   * 
   * @param string $refreshToken {@link Mars_Mixi3LeggedOAuthProvider::getAccessToken()} で取得したリフレッシュトークン値。
   * @return array 再発行したアクセストークンを返します。
   * @throws Mars_RequestException アクセストークンの発行に失敗した場合に発生。
   * @link http://developer.mixi.co.jp/connect/mixi_graph_api/api_auth/#toc-2 アクセストークンの再発行。
   * @author Naomichi Yamakita <yamakita@dtx.co.jp>
   */
  public function reissueAccessToken($refreshToken)
  {
    $queryData = array();
    $queryData['grant_type'] = 'refresh_token';
    $queryData['client_id'] = $this->_consumerKey;
    $queryData['client_secret'] = $this->_consumerSecret;
    $queryData['refresh_token'] = $refreshToken;

    $sender = new Mars_HttpRequestSender(self::ENDPOINT_TOKEN_ISSUANCE_URI);
    $sender->setRequestMethod(Mars_HttpRequest::HTTP_POST);
    $sender->addParameters($queryData);
    $parser = $sender->send();

    $data = $parser->getJSONData(TRUE);

    if (isset($data['error'])) {
      $message = sprintf('Failed to re-issue of the access token. [%s]', $data['error']);
      throw new Mars_RequestException($message);
    }

    $this->_accessToken = $data;

    return $data;
  }

  /**
   * @see Mars_OAuthProvider::getEndpointBaseURI()
   * @author Naomichi Yamakita <yamakita@dtx.co.jp>
   */
  public function getEndpointBaseURI()
  {
    if ($this->_userAgent->isMobile()) {
      return self::ENDPOINT_BASE_URI_MOBILE;
    }

    return self::ENDPOINT_BASE_URI;
  }

  /**
   * @see Mars_OAuthProvider::getProvider()
   * @author Naomichi Yamakita <yamakita@dtx.co.jp>
   */
  public function getProvider()
  {
    return Mars_OAuthFactory::PROVIDER_MIXI_3LEGGED;
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
   * @see Mars_3LeggedOAuthProvider::send()
   * @since 1.10.0
   * @author Naomichi Yamakita <yamakita@dtx.co.jp>
   */
  public function send($uri, $requestMethod = Mars_HttpRequest::HTTP_GET, $parameters = array(), $files = array())
  {
    $uri = $this->buildEndpointURI($uri);
    $accessToken = $this->getAccessToken();

    $sender = new Mars_HttpRequestSender($uri);
    $sender->setRequestMethod($requestMethod);
    $sender->addHeader('Authorization', 'OAuth ' . $accessToken['access_token']);
    $sender->addParameters($parameters);
    $sender->setPostFormat(Mars_HttpRequestSender::FORMAT_JSON);
    $parser = $sender->send();

    if ($parser->getStatus() != 200) {
      if (stripos($parser->getContentType(), 'application/json') !== FALSE) {
        $data = $parser->getJSONData(TRUE);
        $error = sprintf('%s (%s)', $data['error'], $data['error_description']);

      } else {
        $error = $parser->getHeader('WWW-Authenticate');
      }

      $message = sprintf('Failed to send API. [%s]', $error);
      throw new Mars_RequestException($message);
    }

    return $parser;
  }
}
