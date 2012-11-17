<?php
/**
 * @author Naomichi Yamakita <yamakita@dtx.co.jp>
 * @category mars
 * @package net.service.mixi
 * @version $Id: Mars_Mixi2LeggedOAuthProvider.class.php 3108 2011-10-16 15:21:11Z yamakita $
 * @copyright Copyright (c) 2006-2012 dt corporation
 * @since 1.9.0
 */

require dirname(__DIR__) . '/twitter/vendors/twitteroauth/OAuth.php';

/**
 * mixi モバイルアプリにおいて、2-legged OAuth を用いた RESTful API へのアクセス機能を提供します。
 * 
 * @link http://developer.mixi.co.jp/appli/spec/mob/2-legged-oauth 2-legged OAuth による API アクセス
 * @since 1.9.0
 * @author Naomichi Yamakita <yamakita@dtx.co.jp>
 * @category mars
 * @package net.service.mixi
 */

class Mars_Mixi2LeggedOAuthProvider extends Mars_OAuthProvider
{
  /**
   * mixi から送信されるリクエストの署名形式。(HMAC-SHA1)
   */
  const SIGNATURE_HMAC = 1;

  /**
   * mixi から送信される PC 版 mixi アプリの署名形式。(RSA-SHA1)
   */
  const SIGNATURE_RSA_PC = 2;

  /**
   * mixi から送信される touch 版 mixi アプリの署名形式。(RSA-SHA1)
   */
  const SIGNATURE_RSA_TOUCH = 3;

  /**
   * mixi から送信されるフォトアップロードの署名形式。(RSA-SHA1)
   */
  const SIGNATURE_RSA_PHOTO_UPLOAD = 4;

  /**
   * mixi から送信されるライフサイクルイベントの署名形式。(RSA-SHA1)
   */
  const SIGNATURE_RSA_LIFECYCLE_EVENT = 5;

  /**
   * RESTful API のエンドポイント基底 URI。
   */
  const ENDPOINT_BASE_URI = 'http://api.mixi-platform.com/os/0.8';

  /**
   * OAuthConsumer オブジェクト。
   * @var OAuthConsumer
   */
  private $_consumer;

  /**
   * zlib モジュールがインストールされているか。
   * @var bool
   */
  private $_hasZlib = FALSE;

  /**
   * コンストラクタ。
   * コンシューマキー、及びコンシューマ秘密鍵はコンストラクタの引数、またはプロパティファイルに定義することが可能です。
   * (プロパティファイルの設定例は {@link Mars_MixiMobileApp} クラスの API を参照)
   * 
   * @param string $consumerKey コンシューマキー。
   * @param string $consumerSecret コンシューマ秘密鍵。
   * @author Naomichi Yamakita <yamakita@dtx.co.jp>
   */
  protected function __construct($consumerKey = NULL, $consumerSecret = NULL)
  {
    $config = Mars_Config::loadProperties('mixi');

    if ($consumerKey === NULL) {
      $consumerKey = array_find($config, 'consumerKey');
    }

    if ($consumerSecret === NULL) {
      $consumerSecret = array_find($config, 'consumerSecret');
    }

    parent::__construct($consumerKey, $consumerSecret);

    $this->_consumer = new OAuthConsumer($consumerKey, $consumerSecret);
    $this->_hasZlib = extension_loaded('zlib');
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
    return Mars_OAuthFactory::PROVIDER_MIXI;
  }

  /**
   * HTTP リクエストが mixi から送信された正当なものであるかどうかを検証します。
   * mixi モバイルアプリを実装する上で、このメソッドはリクエスト毎に必ず実行して下さい。
   * 
   * @param int $type 署名方式。Mars_OAuthProvider::SIGNATURE_* 定数を指定。
   * @throws Mars_UnsupportedException サポートされていない署名形式が指定された場合に発生。
   * @link http://developer.mixi.co.jp/appli/spec/mob/validate-oauth-signature OAuth Signature の検証方法について
   * @link http://developer.mixi.co.jp/appli/spec/mob/for_partners/photo_upload_api アプリからフォトアップロード機能について
   * @link http://developer.mixi.co.jp/appli/spec/mob/for_partners/lifecycle_event ライフサイクルイベントについて
   * @see Mars_OAuthProvider::isAuthorizaed()
   * @author Naomichi Yamakita <yamakita@dtx.co.jp>
   */
  public function isAuthorized($type = self::SIGNATURE_HMAC)
  {
    $result = FALSE;

    switch ($type) {
      case self::SIGNATURE_HMAC:
        $authorization = $this->request->getHeader('Authorization');

        // Authorization ヘッダに含まれるパラメータを連想配列に変換
        preg_match_all('/([a-z_]+)="([^"]+)"/', $authorization, $matches);

        if (sizeof($matches[0])) {
          $attributes = array_combine($matches[1], $matches[2]);

          $parameters = array();
          $parameters['oauth_nonce'] = $attributes['oauth_nonce'];
          $parameters['oauth_signature_method'] = $attributes['oauth_signature_method'];
          $parameters['oauth_timestamp'] = $attributes['oauth_timestamp'];
          $parameters['oauth_version'] = $attributes['oauth_version'];
          $parameters['opensocial_app_id'] = Mars_MixiMobileApp::getApplicationId();
          $parameters['opensocial_owner_id'] = Mars_MixiMobileApp::getOwnerId();
          $parameters += $this->request->getQuery();

          $method = $this->request->getRequestMethod();
          $uri = $this->request->getURL(FALSE);

          $request = OAuthRequest::from_consumer_and_token($this->_consumer,
                                                           NULL,
                                                           $method,
                                                           $uri,
                                                           $parameters);
          $request->sign_request(new OAuthSignatureMethod_HMAC_SHA1(), $this->_consumer, NULL);

          $buildSignature = @$request->get_parameter('oauth_signature');
          $requestSignature = urldecode($attributes['oauth_signature']);

          if ($buildSignature === $requestSignature) {
            $result = TRUE;
          }
        }

        break;

      case self::SIGNATURE_RSA_PC:
        $request = OAuthRequest::from_request(NULL, NULL, array_merge($_GET, $_POST));

        // 不正なリクエスト時に 'Undefined index: oauth_signature' エラーが起こる不具合 (r525 で確認済み) があるため、エラー制御演算子を付けておく
        $signature = @$request->get_parameter('oauth_signature');

        if (!is_null($signature)) {
          $signatureMethod = new Mars_MixiPCSignature();
          $result = $signatureMethod->check_signature($request, NULL, NULL, $signature);
        }

        break;

      case self::SIGNATURE_RSA_TOUCH:
        $request = OAuthRequest::from_request(NULL, NULL, array_merge($_GET, $_POST));
        $signature = @$request->get_parameter('oauth_signature');

        if (!is_null($signature)) {
          $signatureMethod = new Mars_MixiTouchSignature();
          $result = $signatureMethod->check_signature($request, NULL, NULL, $signature);
        }

        break;

      case self::SIGNATURE_RSA_PHOTO_UPLOAD:
        $request = OAuthRequest::from_request();
        $signature = @$request->get_parameter('oauth_signature');

        if (!is_null($signature)) {
          $signatureMethod = new Mars_MixiFileUploadSignature();
          $result = $signatureMethod->check_signature($request, NULL, NULL, $signature);
        }

        break;

      case self::SIGNATURE_RSA_LIFECYCLE_EVENT:
        if ($this->request->getParameter('opensocial_owner_id') !== NULL) {
          break;
        }

        if ($this->request->getParameter('opensocial_viewer_id') !== NULL) {
          break;
        }

        // ライフサイクルイベントは mixi から POST リクエストが送信される
        // (OAuth の仕様上は POST データを署名生成のアルゴリズムに使用することが規定されているが、mixi アプリが仕様に準拠していないため QueryString のみを使用する)
        $requestHeaders = OAuthUtil::get_headers();
        $parameters = OAuthUtil::parse_parameters($this->request->getEnvironment('QUERY_STRING'));

        if (isset($requestHeaders['Authorization']) && substr($requestHeaders['Authorization'], 0, 6) == 'OAuth ') {
          $headerParameters = OAuthUtil::split_header($requestHeaders['Authorization'], FALSE);
          $parameters = array_merge($parameters, $headerParameters);

          $request = OAuthRequest::from_request(NULL, NULL, $parameters);
          $signature = $request->get_parameter('oauth_signature');

          if (!is_null($signature)) {
            $signatureMethod = new Mars_MixiLifecycleEventSignature();
            $result = $signatureMethod->check_signature($request, NULL, NULL, $signature);
          }
        }

        break;

      default:
        $message = sprintf('Signature format is not supported. [%s]', $type);
        throw new Mars_UnsupportedException($message);
        break;
    }

    return $result;
  }

  /**
   * OpenSocial ID 文字列 (urn:guid) から guid を取得します。
   * 
   * @param string $id OpenSocial ID 文字列。
   * @return string guid を返します。guid が見つからない場合は id 自体を返します。
   * @author Naomichi Yamakita <yamakita@dtx.co.jp>
   */
  public function getId($id)
  {
    $pos = strpos($id, ':');

    if ($pos !== FALSE) {
      return substr($id, $pos + 1);
    }

    return $id;
  }

  /**
   * RESTful API に対してリクエストを送信します。
   * パフォーマンス上の観点から、PHP の zlib モジュールが有効な場合はデータを gzip 圧縮転送します。
   * 
   * @param array $parameters 送信するパラメータのリスト。
   *   <code>
   *   // 対象フィールドを foo、bar に指定
   *   $parameters = array('fields' => 'foo,bar');
   *   
   *   // フィルタリングの指定
   *   $parameters = array('filterBy' => 'hasApp');
   *   </code>
   * @param string $requestMethod {@link Mars_OAuthProvider::send()} メソッドを参照。
   * @return Mars_HttpResponseParser {@link Mars_OAuthProvider::send()} メソッドを参照。
   * @throws Mars_RequestException mixi からエラーが返された場合に発生。
   *   - {@link Mars_RequestException::getCode()} メソッドでレスポンスコードが取得可能。(コードの意味は mixi のマニュアルを参照)
   *   - OAuth の署名検証などでエラーが起きた場合は {@link http://oauth.pbworks.com/ProblemReporting 具体的な理由} が返される。
   *   - {@link Mars_RequestException::getAttribute() Mars_RequestException::getAttribute('error')} メソッドで返却されたレスポンスページを取得可能。
   * @see Mars_OAuthProvider::send()
   * @link http://developer.mixi.co.jp/appli/spec/mob/for_partners/mobile_api_detail RESTful API 仕様
   * @link http://developer.mixi.co.jp/appli/spec/mob/2-legged-oauth 2-legged OAuth による API アクセス
   */
  public function send($uri, $requestMethod = Mars_HttpRequest::HTTP_GET, $parameters = array(), $files = array())
  {
    $baseFeed = $this->buildEndpointURI($uri);
    $queryData = array();

    if ($requestMethod == Mars_HttpRequest::HTTP_GET || $requestMethod == Mars_HttpRequest::HTTP_DELETE) {
      $queryData = $parameters;
    }

    $queryData['xoauth_requestor_id'] = Mars_MixiMobileApp::getOwnerId();

    $request = OAuthRequest::from_consumer_and_token($this->_consumer,
                                                     NULL,
                                                     $requestMethod,
                                                     $baseFeed,
                                                     $queryData);
    $request->sign_request(new OAuthSignatureMethod_HMAC_SHA1(), $this->_consumer, NULL);

    // to_header() から返されるヘッダ名 ('Authorization: ') の部分を取り除く
    $authorization = substr($request->to_header(), 15);
    $uri = $baseFeed . '?' . http_build_query($queryData, '', '&');

    if ($requestMethod === Mars_HttpRequest::HTTP_POST) {
      $sender = new Mars_HttpRequestSender($uri);
      $sender->addParameters($parameters);
      $sender->setPostFormat(Mars_HttpRequestSender::FORMAT_JSON);

    } else {
      $sender = new Mars_HttpRequestSender($uri);
    }

    $sender->setRequestMethod($requestMethod);
    $sender->addHeader('Authorization', $authorization);

    // 圧縮転送を有効にする
    if ($this->_hasZlib) {
      $sender->addHeader('Accept-Encoding', 'gzip');
    }

    $parser = $sender->send();
    $status = $parser->getStatus();

    if ($status == 200) {
      return $parser;

    } else {
      $authenticate = $parser->getHeader('WWW-Authenticate');

      // OAuth の承認でエラーが起きた場合は oauth_problem に原因が格納される
      if (preg_match('/oauth_problem="([^"]+)"/', $authenticate, $matches)) {
        $message = sprintf('OAuth authenticate error. [%s]', $matches[1]);
      } else {
        $message = sprintf('mixi returned an error code. [%s]', $status);
      }

      $e = new Mars_RequestException($message, $status);
      $e->setAttribute('error', $parser->getContents());

      throw $e;
    }
  }
}
