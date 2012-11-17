<?php
/**
 * @author Naomichi Yamakita <yamakita@dtx.co.jp>
 * @category mars
 * @package net.service
 * @version $Id: Mars_OAuthProvider.class.php 3112 2011-10-16 19:00:30Z yamakita $
 * @copyright Copyright (c) 2006-2012 dt corporation
 * @since 1.9.0
 */

/**
 * OAuth を実装するプロバイダのための抽象クラスです。
 * 
 * @since 1.9.0
 * @author Naomichi Yamakita <yamakita@dtx.co.jp>
 * @category mars
 * @package net.service
 */

abstract class Mars_OAuthProvider extends Mars_Object
{
  /**
   * コンシューマキー。
   * @var string
   */
  protected $_consumerKey;

  /**
   * コンシューマ秘密鍵。
   * @var string
   */
  protected $_consumerSecret;

  /**
   * コンストラクタ。
   * 
   * @param string $consumerKey コンシューマキー。
   * @param string $consumerSecret コンシューマ秘密鍵。
   * @throws RuntimeException コンシューマキー、またはコンシューマ秘密鍵が未定義の場合に発生。
   * @author Naomichi Yamakita <yamakita@dtx.co.jp>
   */
  protected function __construct($consumerKey = NULL, $consumerSecret = NULL)
  {
    if ($consumerKey === NULL) {
      $message = 'Consumer key is undefined.';
      throw new RuntimeException($message);
    }

    if ($consumerSecret === NULL) {
      $message = 'Consumer secret is undefined.';
      throw new RuntimeException($message);
    }

    $this->_consumerKey = $consumerKey;
    $this->_consumerSecret = $consumerSecret;
  }

  /**
   * コンシューマーキーを設定します。 
   *
   * @param string $consumerKey コンシューマキー。
   * @author Naomichi Yamakita <yamakita@dtx.co.jp>
   */
  public function setConsumerKey($consumerKey)
  {
    $this->_consumerKey = $consumerKey;
  }

  /**
   * コンシューマ秘密鍵を設定します。
   *
   * @param string $consumerSecretKey コンシューマ秘密鍵。
   * @author Naomichi Yamakita <yamakita@dtx.co.jp>
   */
  public function setConsumerSecretKey($consumerSecretKey)
  {
    $this->_consumerSecretKey = $consumerSecretKey;
  }

  /**
   * Mars_OAuthProvider を実装したクラスのオブジェクトインスタンスを取得します。
   * 
   * @return Mars_OAuthProvider Mars_OAuthProvider を実装したクラスのオブジェクトインスタンスを返します。
   * @author Naomichi Yamakita <yamakita@dtx.co.jp>
   */
  public static function getInstance()
  {
    static $instance = NULL;

    if ($instance === NULL) {
      $className = get_called_class();
      $instance = new $className;
    }

    return $instance;
  }

  /**
   * エンドポイントの基底となる URI を取得します。
   * 
   * @return string エンドポイントの基底となる URI を返します。
   * @since 1.10.0
   * @author Naomichi Yamakita <yamakita@dtx.co.jp>
   */
  abstract public function getEndpointBaseURI();

  /**
   * API をリクエストする URI を構築します。
   * 
   * @param string $path '/' から始まる API のリクエストパス。
   * @return string path を絶対 URI に変換した結果を返します。
   *   path 自体が絶対 URI の場合、変換の処理は行われません。
   * @since 1.10.0
   * @author Naomichi Yamakita <yamakita@dtx.co.jp>
   */
  protected function buildEndpointURI($path)
  {
    load_function_library('URI');

    if (!is_absolute_uri($path)) {
      if (substr($path, 0, 1) !== '/') {
        $path = '/' . $path;
      }

      $uri = $this->getEndpointBaseURI() . $path;

    } else {
      $uri = $path;
    }

    return $uri;
  }

  /**
   * プロバイダ名を取得します。
   * 
   * @return string プロバイダ名を返します。
   * @author Naomichi Yamakita <yamakita@dtx.co.jp>
   */
  abstract public function getProvider();

  /**
   * OAuth プロバイダから返された (コールバックされた) 認可情報が正当なものであるかどうか検証します。
   *
   * @return bool 認可情報が正当なものであれば TRUE、不正であれば FALSE を返します。
   * @author Naomichi Yamakita <yamakita@dtx.co.jp>
   */
  abstract public function isAuthorized();

  /**
   * RESTful API に対してリクエストを送信します。
   * 
   * @param string $api リクエスト URI。基底エンドポイントからの相対パス、または絶対パスによる指定が有効。
   * @param string $requestMethod リクエストの送信形式。Mars_HttpRequest::HTTP_* 定数を指定。
   * @param array $parameters 送信するパラメータのリスト。
   *   array('パラメータ名' => 'パラメータ値') 形式で複数指定可能。
   * @param array $files 送信するファイルのリスト。
   *   array('パラメータ名' => 'ファイルのパス (絶対パス、または APP_ROOT_DIR からの相対パスが有効)') 形式で複数指定可能。
   * @return Mars_HttpResponseParser レスポンス情報を格納した Mars_HttpResponseParser オブジェクトインスタンスを返します。
   * @throws Mars_RequestException アクセストークンが不正な場合に発生。
   * @author Naomichi Yamakita <yamakita@dtx.co.jp>
   */
  abstract public function send($uri, $requestMethod = Mars_HttpRequest::HTTP_GET, $parameters = array(), $files = array());
}
