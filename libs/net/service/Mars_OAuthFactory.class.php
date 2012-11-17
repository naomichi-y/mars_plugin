<?php
/**
 * @author Naomichi Yamakita <yamakita@dtx.co.jp>
 * @category mars
 * @package net.service
 * @version $Id: Mars_OAuthFactory.class.php 2803 2011-06-02 16:36:43Z yamakita $
 * @copyright Copyright (c) 2006-2012 dt corporation
 * @since 1.9.0
 */

/**
 * OAuth プロバイダで認可を行うためのコネクタを提供します。
 * 
 * @since 1.9.0
 * @author Naomichi Yamakita <yamakita@dtx.co.jp>
 * @category mars
 * @package net.service
 */
class Mars_OAuthFactory extends Mars_Object
{
  /**
   * OAuth プロバイダ定数。(Twitter)
   */
  const PROVIDER_TWITTER = 'twitter';

  /**
   * OAuth プロバイダ定数。(Facebook)
   */
  const PROVIDER_FACEBOOK = 'facebook';

  /**
   * OAuth プロバイダ定数。(mixi 2-Legged)
   */
  const PROVIDER_MIXI_2LEGGED = 'mixi2-legged';

  /**
   * OAuth プロバイダ定数。(mixi 3-Legged)
   */
  const PROVIDER_MIXI_3LEGGED = 'mixi3-legged';

  /**
   * コンストラクタ。
   * 
   * @author Naomichi Yamakita <yamakita@dtx.co.jp>
   */
  private function __construct()
  {}

  /**
   * {@link Mars_OAuthProvider} を実装したクラスのオブジェクトインスタンスを生成します。
   * 
   * @param string $provider 接続先の OAuth プロバイダ。Mars_OAuthProvider::PROVIDER_* 定数を指定。
   * @return Mars_OAuthProvider Mars_OAuthProvider を実装したクラスのオブジェクトインスタンスを返します。
   * @throws Mars_UnsupportedException サポートされていない OpenID プロバイダが指定された場合に発生。
   * @author Naomichi Yamakita <yamakita@dtx.co.jp>
   */
  public static function create($provider)
  {
    $instance = NULL;

    switch ($provider) {
      case self::PROVIDER_TWITTER:
        $instance = Mars_TwitterOAuthProvider::getInstance();
        break;

      case self::PROVIDER_FACEBOOK:
        $instance = Mars_FacebookOAuthProvider::getInstance();
        break;

      case self::PROVIDER_MIXI_2LEGGED:
        $instance = Mars_Mixi2LeggedOAuthProvider::getInstance();
        break;

      case self::PROVIDER_MIXI_3LEGGED:
        $instance = Mars_Mixi3LeggedOAuthProvider::getInstance();
        break;

      default:
        $message = sprintf('The specified provider is not supported. [%s]', $provider);
        throw new Mars_UnsupportedException($message);
    }

    return $instance;
  }

  /**
   * 3-legged OAuth プロバイダからコールバックされたパラメータを元に {@link Mars_OAuthProvider} を実装したクラスのオブジェクトインスタンスを生成します。
   * (2-legged OAuth プロバイダで当メソッドを使用することはできません)
   *
   * @return Mars_OAuthProvider {@link Mars_OAuthProvider} を実装したクラスのオブジェクトインスタンスを返します。
   *   インスタンスの生成に失敗した (OAuth プロバイダからリクエストされたパラメータが不正、または存在しない) 場合は FALSE を返します。
   * @throws Mars_RequestException パラメータが不正な場合に発生。
   * @author Naomichi Yamakita <yamakita@dtx.co.jp>
   */
  public static function createFromRequest()
  {
    // Twitter、facebook は PATH_INFO 形式、mixi は GET 形式で 'state' パラメータを返す
    // (mixi は 'state' パラメータ固定)
    $request = Mars_DIContainerFactory::getContainer()->getRequest();
    $provider = $request->getParameter('state');

    if (null_or_empty($provider)) {
      return FALSE;
    }

    try {
      $instance = self::create($provider);
      $instance->parseAccessToken();

    } catch (Mars_UnsupportedException $e) {
      return FALSE;
    }

    return $instance;
  }
}

