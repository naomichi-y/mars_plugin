<?php
/**
 * @author Naomichi Yamakita <yamakita@dtx.co.jp>
 * @category mars
 * @package net.service
 * @version $Id: Mars_OpenID.class.php 2856 2011-06-14 16:20:23Z yamakita $
 * @copyright Copyright (c) 2006-2012 dt corporation
 * @since 1.9.0
 */

require __DIR__ . '/vendors/lightopenid-lightopenid/openid.php';

/**
 * OpenID によるログイン認証機構を提供します。
 * 
 * @since 1.9.0
 * @author Naomichi Yamakita <yamakita@dtx.co.jp>
 * @category mars
 * @package net.service
 */
class Mars_OpenID extends Mars_Object
{
  /**
   * OpenID プロバイダの通称。
   * @var string
   */
  private $_provider;

  /**
   * LightOpenID オブジェクト。
   * @var LightOpenID
   */
  private $_openId;

  /**
   * {@link Mars_AttributeExchange} オブジェクト。
   * @var Mars_AttributeExchange
   */
  private $_attributeExchange;

  /**
   * エンドユーザの識別子。
   * @var string
   */
  private $_identity = NULL;

  /**
   * コンストラクタ。
   * 
   * @param string $provider OpenID プロバイダの通称。
   * @param string $identifier OP Identifier。
   * @author Naomichi Yamakita <yamakita@dtx.co.jp>
   */
  public function __construct($provider, $identifier)
  {
    $this->_provider = $provider;

    $this->_openId = new LightOpenID();
    $this->_openId->identity = $identifier;
  }

  /**
   * OpenID プロバイダに要求するユーザ属性情報を設定します。
   * 
   * @param Mars_OpenIDAttributeExchange $attributeExchange 要求するユーザ属性情報。
   * @author Naomichi Yamakita <yamakita@dtx.co.jp>
   */
  public function setAttributeExchange($attributeExchange)
  {
    $this->_attributeExchange = $attributeExchange;
  }

  /**
   * OpenID プロバイダから返却されたユーザ属性情報を取得します。
   * 
   * @return Mars_OpenIDAttributeExchange Mars_OpenIDAttributeExchange のオブジェクトインスタンスを返します。
   * @author Naomichi Yamakita <yamakita@dtx.co.jp>
   */
  public function getAttributeExchange()
  {
    return $this->_attributeExchange;
  }

  /**
   * OpenID プロバイダの通称を取得します。
   * 
   * @return string OpenID プロバイダの通称を返します。
   * @author Naomichi Yamakita <yamakita@dtx.co.jp>
   */
  public function getProvider()
  {
    return $this->_provider;
  }

  /**
   * 認証後に遷移するコールバックパスを生成します。
   * コールバックパスには '_openId.provider' パラメータが追加されます。
   *
   * @param string $callback 認証処理後にコールバックするパス。
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

    $path['_openId.provider'] = $this->getProvider();
    $uri = Mars_RewriteRouter::getInstance()->buildRequestPath($path, array(), TRUE);

    return $uri;
  }

  /**
   * 認証用の URI を生成します。
   * 
   * @param string $callback 認証処理後にコールバックするパス。
   *   指定可能なパスの書式は {@link Mars_RewriteRouter::buildRequestPath()} メソッドを参照。
   * @return string 生成した認証用の URI を返します。
   * @author Naomichi Yamakita <yamakita@dtx.co.jp>
   */
  public function buildAuthenticateURI($callback)
  {
    $this->_openId->required = $this->_attributeExchange->getAttributes();
    $this->_openId->returnUrl = $this->buildCallbackURI($callback);

    return $this->_openId->authUrl();
  }

  /**
   * OpenID による認証を行います。
   * クライアントは OpenID プロバイダの認証ページにリダイレクトします。
   * 
   * @param string $actionName 認証処理後にコールバックするパス。
   *   指定可能なパスの書式は {@link Mars_RewriteRouter::buildRequestPath()} メソッドを参照。
   * @author Naomichi Yamakita <yamakita@dtx.co.jp>
   */
  public function authenticate($actionName)
  {
    $uri = $this->buildAuthenticateURI($actionName);
    $this->response->sendRedirect($uri);
  }

  /**
   * OpenID プロバイダから返却されたパラメータを受け取ります。
   * 
   * @return bool パラメータを正しく解析できた場合に TRUE を返します。
   *   リクエストの妥当性をチェックする場合は {@link isAuthenticated()} メソッドを使用して下さい。
   * @author Naomichi Yamakita <yamakita@dtx.co.jp>
   */
  public function receiveData()
  {
    $this->_attributeExchange = new Mars_OpenIDAttributeExchange($this->_openId->getAttributes());

    // レスポンスは GET、または POST で返される
    $data = $this->request->getParameter('openid_mode');

    if (null_or_empty($data)) {
      return FALSE;
    }

    if ($this->request->getQuery('openid_mode') != 'cancel' && $this->_openId->validate()) {
      $this->_identity = $this->request->getParameter('openid_identity');
    }

    return TRUE;
  }

  /**
   * OpenID プロバイダから返された (コールバックされた) 認証情報が正当なものであるかどうか検証します。
   * 
   * @return bool 認証情報が正当なものであれば TRUE、不正であれば FALSE を返します。
   * @author Naomichi Yamakita <yamakita@dtx.co.jp>
   */
  public function isAuthenticated()
  {
    if ($this->_identity) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * エンドユーザの識別子を取得します。
   * 
   * @return string エンドユーザの識別子を返します。
   * @author Naomichi Yamakita <yamakita@dtx.co.jp>
   */
  public function getIdentity()
  {
    return $this->_identity;
  }
}
