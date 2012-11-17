<?php
/**
 * @author Naomichi Yamakita <yamakita@dtx.co.jp>
 * @category mars
 * @package net.service.mixi
 * @version $Id: Mars_MixiMobileApp.class.php 2931 2011-08-09 05:55:58Z yamakita $
 * @copyright Copyright (c) 2006-2012 dt corporation
 * @since 1.9.0
 */

/**
 * mixi モバイルアプリを開発する上で基盤となるライブラリ機能を提供します。
 * mars がサポートする API は 2011 年 2 月現在のものですが、以下に示す API は現状サポートされていません。
 *   - 課金 API
 * 
 * mixi アプリを有効にするには、各種設定ファイルを次のように書き換えておく必要があります。
 * セットアップファイルの設定:
 * <code>
 * session:
 *   autoStart: FALSE
 * </code>
 * 
 * ヘルパファイルの設定:
 * <code>
 * html:
 *   class: Mars_MixiMobileAppHTMLHelper
 * form:
 *   class: Mars_MixiMobileAppFormHelper
 * </code>
 * 
 * プロパティファイルの設定:
 * <code>
 * # mixi アプリの定義 (オプション)
 * mixi:
 *   # コンシューマキー。
 *   consumerKey:
 *   
 *   # コンシューマ秘密鍵。
 *   consumerSecret:
 *   
 *   # コンテンツサーバの基底 URI。{@link Mars_MixiMobileAppHTMLHelper::staticImage()} メソッド等で使用される。
 *   contentsBaseURI:
 * </code>
 * 
 * @since 1.9.0
 * @author Naomichi Yamakita <yamakita@dtx.co.jp>
 * @category mars
 * @package net.service.mixi
 */

class Mars_MixiMobileApp extends Mars_Object implements Mars_MixiApp
{
  /**
   * mixi の基底 URI。
   */
  const BASE_URI = 'http://m.mixi.jp/';

  /**
   * mixi コンテンツ基底 URI。
   */
  const CONTENTS_BASE_URI = 'http://mm.mixi.net/';

  /**
   * mixi アプリ基底 URI。
   */
  const APPLICATION_BASE_URI = 'http://ma.mixi.net/';

  /**
   * ユーザエージェントが DoCoMo かどうか。
   * @var bool
   */
  private static $_isDoCoMo = FALSE;

  /**
   * アプリケーションを mixi モバイルアプリモードで開始します。
   * start() メソッドが行う処理は次の通りです。
   * <ol>
   *   <li>{@link Mars_RewriteRouter::setIgnoreAppendGUID()} メソッドの実行。</li>
   *   <li>{@link Mars_ExceptionStackTraceDelegate::invokeFlush()} メソッドの実行</li>
   * </ol>
   * - mixi モバイルアプリを扱うモジュールでは、必ずフィルタ等を介して本メソッドをコールする必要があります。
   * - アプリケーションのエンコーディング形式は {@link Mars_UserAgentAdapter::getEncoding()} メソッドに依存します。
   * - PC 上で DoCoMo 端末をエミュレートした場合、mixi が出力するタグによっては XHTML パースエラーを引き起こす可能性があります。
   * 
   * @author Naomichi Yamakita <yamakita@dtx.co.jp>
   */
  public static function start()
  {
    $container = Mars_DIContainerFactory::getContainer();
    $userAgent = $container->getRequest()->getUserAgent();

    if ($userAgent->isDoCoMo()) {
      self::$_isDoCoMo = TRUE;
    }

    Mars_RewriteRouter::getInstance()->setIgnoreAppendGUID();

    // 直前までに出力されたタグが残ってると携帯で閲覧時に表示が崩れる可能性がある
    Mars_ExceptionStackTraceDelegate::invokeFlush();
  }

  /**
   * 現在実行しているアプリの ID を取得します。
   * <i>アプリ ID は常に mixi から SAP に送信される値ですが、信頼された ID ではありません。
   * リクエストが正当なものであるかどうかのチェックは {@link Mars_Mixi2LeggedOAuthProvider::isAuthorized()} メソッドを使用して下さい。</i>
   * 
   * @return string アプリ ID を返します。アプリ ID が取得できなかった場合は NULL を返します。
   * @link http://developer.mixi.co.jp/appli/spec/mob/for_partners/transmitted_information SAP サーバに送信される情報
   * @author Naomichi Yamakita <yamakita@dtx.co.jp>
   */
  public static function getApplicationId()
  {
    return Mars_DIContainerFactory::getContainer()->getRequest()->getQuery('opensocial_app_id');
  }

  /**
   * mixi アプリの URI を取得します。
   * 
   * @return string mixi アプリの URI を返します。アプリ ID が取得できなかった場合は NULL を返します。
   * @link http://developer.mixi.co.jp/appli/spec/mob/for_partners/transmitted_information SAP サーバに送信される情報
   * @author Naomichi Yamakita <yamakita@dtx.co.jp>
   */
  public static function getApplicationURI()
  {
    $applicationId = self::getApplicationId();

    if ($applicationId !== NULL) {
      return self::APPLICATION_BASE_URI . $applicationId . '/';
    } else {
      return NULL;
    }
  }

  /** 
   * mixi アプリのコンテンツ URI を取得します。
   * 
   * @return string mixi アプリのコンテンツ URI を返します。
   * @link http://developer.mixi.co.jp/appli/spec/mob/for_partners/images_and_flash 画像や Flash 等の表示について
   * @author Naomichi Yamakita <yamakita@dtx.co.jp>
   */
  public static function getContentsURI()
  {
    return self::CONTENTS_BASE_URI . self::getApplicationId() . '/';
  }

  /**
   * 現在アプリにアクセスしているユーザの ID を取得します。
   * デバッグモードが有効かつ ID が見つからない場合は、global_properties.yml からデバッグ用アプリ ID の取得を試みます。
   * 
   * プロパティ設定例:
   * <code>
   * mixi:
   *   debug:
   *     ownerId: ...
   * </code>
   * <i>{@link getApplicationId()} メソッドの項も参照。</i>
   * 
   * @link http://developer.mixi.co.jp/appli/spec/mob/for_partners/transmitted_information SAP サーバに送信される情報
   * @return string 現在のアプリ ID を返します。
   * @see Mars_MixiMobileApp::getApplicationId()
   * @author Naomichi Yamakita <yamakita@dtx.co.jp>
   */
  public static function getOwnerId()
  {
    $ownerId = Mars_DIContainerFactory::getContainer()->getRequest()->getQuery('opensocial_owner_id');

    if ($ownerId === NULL && is_output_debug()) {
      $ownerId = Mars_Config::loadProperties('mixi.debug.ownerId');
    }

    return $ownerId;
  }

  /**
   * userId のプロフィール URI を取得します。
   * 
   * @param string $userId 対象とするユーザ ID。urn:guid 、または guid 形式で指定可能。
   * @return string userId のプロフィール URI を返します。
   * @author Naomichi Yamakita <yamakita@dtx.co.jp>
   */
  public static function getProfileURI($userId)
  {
    return self::BASE_URI . 'show_friend.pl?id=' . Mars_Mixi2LeggedOAuthProvider::getId($userId);
  }

  /**
   * mixi アプリの内部用リンクパスを生成します。
   * 
   * @param mixed $path リンク対象のアクション名。
   *   指定可能な形式は {@link Mars_RewriteRouter::buildRequestPath()} メソッドを参照。
   * @param bool $absolute 生成したパスを絶対パスに変換する場合は TRUE を指定。
   * @return string 生成したパスを返します。
   * @author Naomichi Yamakita <yamakita@dtx.co.jp>
   */
  public static function buildForwardActionURI($path, $absolute = FALSE)
  {
    $queryData = array();
    $queryData['url'] = Mars_RewriteRouter::getInstance()->buildRequestPath($path, array(), TRUE);

    $uri = self::buildQueryString($queryData);

    if ($absolute) {
      $uri = self::getApplicationURI() . $uri;
    }

    return $uri;
  }

  /**
   * mixi サーバ上で特定の処理を行った後に遷移するコールバック用のパスを生成します。
   * 
   * @param mixed $callback 処理実行後にコールバックするアクション名。
   *   指定可能な形式は {@link Mars_RewriteRouter::buildRequestPath()} メソッドを参照。
   * @param string $path mixi アプリ上で実行する処理。('update:status' など)
   * @return string 生成したパスを返します。
   * @author Naomichi Yamakita <yamakita@dtx.co.jp>
   */
  public static function buildCallbackActionPath($callback, $path, $queryData = array())
  {
    $queryData['callback'] = Mars_RewriteRouter::getInstance()->buildRequestPath($callback, array(), TRUE);
    $path = $path . self::buildQueryString($queryData);

    return $path;
  }

  /**
   * 画像や Flash データへのリダイレクトを行います。
   * 
   * @param string $path リダイレクト先のコンテンツファイル。'webroot' からの相対パスを指定します。
   * @param bool $signed TRUE を指定した場合、リクエストの妥当性を検証するための OAuth Signature が追加されます。 
   * @author Naomichi Yamakita <yamakita@dtx.co.jp>
   * @link http://developer.mixi.co.jp/appli/spec/mob/for_partners/images_and_flash 画像や Flash 等の表示について
   */
  public static function sendRedirectMedia($path, $signed = FALSE)
  {
    if (substr($path, 0, 1) !== '/') {
      $path = '/'. $path;
    }

    $queryData = array();

    if ($signed) {
      $queryData['signed'] = 1;
    }

    $extra = array();
    $extra['query'] = $queryData;
    $extra['absolute'] = TRUE;

    $path = Mars_HTMLHelper::buildAssetPath($path, 'image', $extra);

    $queryData = array();
    $queryData['url'] = $path;

    $uri = self::getContentsURI() . self::buildQueryString($queryData);
    Mars_DIContainerFactory::getContainer()->getResponse()->sendRedirect($uri);
  }

  /**
   * URI に追加するクエリ文字列を生成します。
   * ユーザエージェントが DoCoMo の場合は 'guid=ON' クエリが追加されます。
   * 
   * @param array $queryData URI に追加するパラメータのリスト。
   * @return string 生成したクエリ文字列を返します。
   * @author Naomichi Yamakita <yamakita@dtx.co.jp>
   */
  private static function buildQueryString($queryData)
  {
    if (self::$_isDoCoMo) {
      $queryData['guid'] = 'ON';
    }

    $query = '?' . http_build_query($queryData, '', '&');

    return $query;
  }
}

