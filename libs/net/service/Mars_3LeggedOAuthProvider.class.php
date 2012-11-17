<?php
/**
 * @author Naomichi Yamakita <yamakita@dtx.co.jp>
 * @category mars
 * @package net.service.twitter
 * @version $Id: Mars_3LeggedOAuthProvider.class.php 3112 2011-10-16 19:00:30Z yamakita $
 * @copyright Copyright (c) 2006-2012 dt corporation
 * @since 1.9.0
 */

/**
 * Mars_3LeggedOAuthProvider クラスは、3-legged OAuth のための抽象クラスです。
 * 3-legged OAuth プロバイダはこのクラスを実装する必要があります。
 * 
 * @since 1.9.0
 * @author Naomichi Yamakita <yamakita@dtx.co.jp>
 * @category mars
 * @package net.service
 */

abstract class Mars_3LeggedOAuthProvider extends Mars_OAuthProvider
{
  /**
   * アクセストークン。
   * @var array
   */
  protected $_accessToken = array();

  /**
   * 認可用の URI を生成します。
   * 
   * @throws RuntimeException コンシューマキー (あるいは秘密鍵) が不正な場合に発生。
   * @return string 生成した認可用の URI を返します。
   * @author Naomichi Yamakita <yamakita@dtx.co.jp>
   */
  abstract public function buildAuthorizeURI();

  /**
   * 認可を行います。
   * クライアントは OAuth プロバイダの認可ページにリダイレクトされます。
   * 
   * @author Naomichi Yamakita <yamakita@dtx.co.jp>
   */
  abstract public function authorize();

  /**
   * 認可後に遷移するコールバックパスを生成します。
   * コールバックパスには 'oauth.provider' パラメータが追加されます。
   * 
   * @param string $callback 認可処理後にコールバックするパス。  
   *   指定可能なパスの書式は {@link Mars_RewriteRouter::buildRequestPath()} メソッドを参照。
   * @return string 生成したコールバックパスを返します。
   * @author Naomichi Yamakita <yamakita@dtx.co.jp>
   */
  protected function buildCallbackURI($callback)
  {
    if (is_array($callback)) {
      $path = $callback;

    } else {
      $path = array();
      $path['action'] = $callback;
    }

    $path['state'] = $this->getProvider();
    $uri = Mars_RewriteRouter::getInstance()->buildRequestPath($path, array(), TRUE);

    return $uri;
  }

  /**
   * OAuth プロバイダから返された (コールバックされた) パラメータからアクセストークンを解析します。
   * このメソッドは {@link Mars_OAuthFactory::createFromRequest()} メソッドでインスタンスを生成した際に自動的にコールされます。
   * 
   * @throws Mars_RequestException アクセストークンを取得できない場合に発生。
   * @author Naomichi Yamakita <yamakita@dtx.co.jp>
   */
  abstract public function parseAccessToken();

  /**
   * アクセストークンを設定します。
   * 
   * @param string $tokenId アクセストークン ID。
   * @since 1.10.0
   * @author Naomichi Yamakita <yamakita@dtx.co.jp>
   */
  public function setAccessToken($tokenId)
  {
    $this->_accessToken['oauth_token'] = $tokenId;
  }

  /**
   * アクセストークンシークレットを設定します。
   * 
   * @param string $tokenSecret アクセストークンシークレット。
   * @since 1.10.0
   * @author Naomichi Yamakita <yamakita@dtx.co.jp>
   */
  public function setAccessTokenSecret($tokenSecret)
  {
    $this->_accessToken['oauth_token_secret'] = $tokenSecret;
  }

  /**
   * アクセストークンを取得します。
   * 
   * @return array アクセストークンを返します。
   *   例えば、array('oauth_token' => '...', 'oauth_token_secret' => '...') のような配列を返します。
   * @author Naomichi Yamakita <yamakita@dtx.co.jp>
   */
  abstract public function getAccessToken();
}

