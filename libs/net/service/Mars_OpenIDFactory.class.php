<?php
/**
 * @author Naomichi Yamakita <yamakita@dtx.co.jp>
 * @category mars
 * @package net.service
 * @version $Id: Mars_OpenIDFactory.class.php 2803 2011-06-02 16:36:43Z yamakita $
 * @copyright Copyright (c) 2006-2012 dt corporation
 * @since 1.9.0
 */

/**
 * OpenID プロバイダで認証を行うためのコネクタを提供します。
 * 
 * @since 1.9.0
 * @author Naomichi Yamakita <yamakita@dtx.co.jp>
 * @category mars
 * @package net.service
 */

class Mars_OpenIDFactory extends Mars_Object
{
  /**
   * OpenID プロバイダ定数。(Google)
   */
  const PROVIDER_GOOGLE = 'google';

  /**
   * OpenID プロバイダ定数。(Flickr)
   */
  const PROVIDER_FLICKR = 'flickr';

  /**
   * OpenID プロバイダ定数。(Yahoo! JAPAN)
   */
  const PROVIDER_YAHOO_JAPAN = 'yahoo';

  /**
   * OpenID プロバイダ定数。(mixi)
   */
  const PROVIDER_MIXI = 'mixi';

  /**
   * OpenID プロバイダ定数。(livedoor)
   */
  const PROVIDER_LIVEDOOR = 'livedoor';

  /**
   * OpenID プロバイダ定数。(Excite)
   */
  const PROVIDER_EXCITE = 'excite';

  /**
   * OP Identifier のリスト。
   * @var array
   */
  private static $_identifiers = array(
    self::PROVIDER_GOOGLE => 'https://www.google.com/accounts/o8/id',
    self::PROVIDER_FLICKR => 'http://flickr.com',
    self::PROVIDER_YAHOO_JAPAN => 'http://yahoo.co.jp',
    self::PROVIDER_MIXI => 'https://mixi.jp',
    self::PROVIDER_LIVEDOOR => 'http://livedoor.com',
    self::PROVIDER_EXCITE => 'https://openid.excite.co.jp'
  );

  /**
   * コンストラクタ。
   * 
   * @author Naomichi Yamakita <yamakita@dtx.co.jp>
   */
  private function __construct()
  {}

  /**
   * Mars_OpenID のオブジェクトインスタンスを生成します。
   * 
   * @param string $provider 接続先の OpenID プロバイダ。Mars_OpenIDFactory::PROVIDER_* 定数を指定。
   * @return Mars_OpenID Mars_OpenID のオブジェクトインスタンスを返します。
   * @throws Mars_UnsupportedException サポートされていない OpenID プロバイダが指定された場合に発生。
   * @author Naomichi Yamakita <yamakita@dtx.co.jp>
   */
  public static function create($provider)
  {
    if (isset(self::$_identifiers[$provider])) {
      $identifier = self::$_identifiers[$provider];
      $instance = new Mars_OpenID($provider, $identifier);

      return $instance;

    } else {
      $message = sprintf('The specified provider is not supported. [%s]', $provider);
      throw new Mars_UnsupportedException($message);
    }
  }

  /**
   * OpenID プロバイダからコールバックされたパラメータを元 Mars_OpenID のオブジェクトインスタンスを生成します。
   * 
   * @return Mars_OpenID Mars_OpenID のオブジェクトインスタンスを返します。
   *   インスタンスの生成に失敗した (OpenID プロバイダからリクエストされたパラメータが不正、または存在しない) 場合は FALSE を返します。
   * @author Naomichi Yamakita <yamakita@dtx.co.jp>
   */
  public static function createFromRequest()
  {
    $request = Mars_DIContainerFactory::getContainer()->getRequest();
    $provider = $request->getPathInfo('_openId.provider');

    if (null_or_empty($provider)) {
      return FALSE;
    }

    try {
      $instance = self::create($provider);

      if (!$instance->receiveData()) {
        return FALSE;
      }

    } catch (Mars_UnsupportedException $e) {
      return FALSE;
    }

    return $instance;
  }
}
