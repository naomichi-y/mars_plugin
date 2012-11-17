<?php
/**
 * @author Naomichi Yamakita <yamakita@dtx.co.jp>
 * @category mars
 * @package net.service.mixi.helper
 * @version $Id: Mars_MixiMobileAppHTMLHelper.class.php 3098 2011-10-09 20:34:54Z yamakita $
 * @copyright Copyright (c) 2006-2012 dt corporation
 * @since 1.9.0
 */

/**
 * mixi モバイルアプリのための HTML ヘルパです。
 * 
 * @since 1.9.0
 * @author Naomichi Yamakita <yamakita@dtx.co.jp>
 * @category mars
 * @package net.service.mixi.helper
 */
class Mars_MixiMobileAppHTMLHelper extends Mars_HTMLHelper
{
  /**
   * 静的コンテンツの基底パス。
   * @var string
   */
  private $_contentsBaseURI;

  /**
   * コンストラクタ。
   *
   * @author Naomichi Yamakita <yamakita@dtx.co.jp>
   */
  public function __construct()
  {
    $helperConfig = Mars_Config::load(Mars_Config::YAML_GLOBAL_HELPERS, 'html');
    $this->_contentsBaseURI = Mars_Config::loadProperties('mixi.contentsBaseURI');

    parent::__construct($helperConfig);
  }

  /**
   * @return bool 現在のところ、このメソッドは必ず TRUE を返します。
   * @see Mars_HTMLHelper::isAbsolutePath()
   * @author Naomichi Yamakita <yamakita@dtx.co.jp>
   */
  public function isAbsolutePath()
  {
    return TRUE;
  }

  /**
   * 基本動作は {@link Mars_HTMLHelper::messages()} メソッドと同じですが、メッセージの文字色が style 属性で指定されている点が異なります。
   * (DoCoMo 2.0 以前の端末では class 指定による CSS を解釈できないため)
   * @see Mars_HTMLHelper::messages()
   * @author Naomichi Yamakita <yamakita@dtx.co.jp>
   */
  public function messages($attributes = array('style' => 'color: #093'))
  {
    return parent::messages($attributes);
  }

  /**
   * 基本動作は {@link Mars_HTMLHelper::errors()} メソッドと同じですが、エラーメッセージの文字色が style 属性で指定されている点が異なります。
   * (DoCoMo 2.0 以前の端末では class 指定による CSS を解釈できないため)
   * @see Mars_HTMLHelper::errors()
   * @author Naomichi Yamakita <yamakita@dtx.co.jp>
   */
  public function errors($fieldError = TRUE, $attributes = array('style' => 'color: red'))
  {
    return parent::errors($fieldError, $attributes);
  }

  /**
   * queryData に signed 属性を指定することで、リクエストの妥当性を検証するための OAuth Signature が追加されます。
   * <code>
   * $html->image('hello.jpg', array(), array('queryData' => array('signed' => 1)));
   * </code>
   * 
   * @link http://developer.mixi.co.jp/appli/spec/mob/for_partners/images_and_flash 画像や Flash 等の表示について
   * @see Mars_HTMLHelper::image()
   * @author Naomichi Yamakita <yamakita@dtx.co.jp>
   */
  public function image($filePath, $attributes = array(), $extra = array())
  {
    $extra = parent::constructParameters($extra);
    $extra['absolute'] = TRUE;

    return parent::image($filePath, $attributes, $extra);
  }

  /**
   * コンテンツサーバから取得したファイルを元にイメージタグを生成します。
   * コンテンツサーバの基底 URI はプロパティファイルで設定して下さい。({@link Mars_MixiMobileApp} クラスの API を参照)
   * 
   * @param string $filePath 出力する画像のパス。'contentsBaseURI' からの相対パスを指定。
   * @param mixed $attributes タグに追加する属性。{@link Mars_HTMLHelper::link()} メソッドを参照。
   * @return string 生成したイメージタグを返します。
   * @author Naomichi Yamakita <yamakita@dtx.co.jp>
   */
  public function staticImage($filePath, $attributes = array())
  {
    $defaults = array();
    $defaults['src'] = $this->_contentsBaseURI . '/' . $filePath;

    $attributes = parent::constructParameters($attributes, $defaults);
    $buffer = parent::buildTagAttribute($attributes);

    return sprintf('<img%s>', $buffer);
  }

  /**
   * @link http://developer.mixi.co.jp/appli/spec/mob/for_partners/pages_api ページ遷移と API アクセス
   * @see Mars_HTMLHelper::link()
   * @author Naomichi Yamakita <yamakita@dtx.co.jp>
   */
  public function link($label = NULL, $path = NULL, $attributes = array())
  {
    $isConvert = FALSE;

    // リクエストパスを mixi サーバ上の URI に変換
    // (http://m.mixi.jp/show_friend.pl 等が指定された場合は変換を行わない)
    if (is_array($path) || substr($path, 0, 4) !== 'http') {
      $isConvert = TRUE;

    } else {
      $parse = parse_url($path);

      // 絶対パスが SAP 自身を指す場合は変換を許可する
      if ($this->request->getHost() === $parse['host']) {
        $isConvert = TRUE;
      }
    }

    if ($isConvert) {
      $path = Mars_MixiMobileApp::buildForwardActionURI($path, FALSE);
    }

    $buffer = $this->baseLink($label, $path, $attributes, array());

    return $buffer;
  }

  /**
   * ユーザの位置情報を取得するためのリンクタグを生成します。
   * 
   * @param string $label {@link Mars_HTMLHelper::link()} メソッドを参照。
   * @param mixed $path {@link Mars_HTMLHelper::link()} メソッドを参照。
   * @param bool $gps 位置情報の取得方法を設定。
   *   TRUE 指定時は携帯電話の GPS から、FALSE 指定時は基地局情報から大まかな場所を取得する。
   *   <i>SoftBank 端末に関しては正確な位置情報を取得できない (S! GPS 非対応) 端末があるため、gps を TRUE に指定した際の動作は FALSE と同等になる。</i>
   * @param mixed $attributes タグに追加する属性。{@link Mars_HTMLHelper::link()} メソッドを参照。
   * @return string 生成したリンクタグを返します。
   * @link http://developer.mixi.co.jp/appli/spec/mob/for_partners/get_gps_info 位置情報取得について
   * @author Naomichi Yamakita <yamakita@dtx.co.jp>
   */
  public function location($label, $path, $gps = FALSE, $attributes = array())
  {
    if ($gps && !$this->request->getUserAgent()->isSoftBank()) {
      $path = 'location:gps' . Mars_MixiMobileApp::buildForwardActionURI($path);
    } else {
      $path = 'location:cell' . Mars_MixiMobileApp::buildForwardActionURI($path);
    }

    $buffer = $this->baseLink($label, $path, $attributes, array());

    return $buffer;
  }

  /**
   * マイミク招待フォームへのリンクタグを生成します。
   * 
   * @param string $label {@link Mars_HTMLHelper::link()} メソッドを参照。
   * @param string $callback 招待状送信後にコールバックするパス。
   *   指定可能なパスの書式は {@link Mars_RewriteRouter::buildRequestPath()} メソッドを参照。
   * @param mixed $attributes タグに追加する属性。{@link Mars_HTMLHelper::link()} メソッドを参照。
   * @return string 生成したリンクタグを返します。
   * @link http://developer.mixi.co.jp/appli/spec/mob/for_partners/about_invite マイミク招待機能について
   * @since 1.9.0-p1
   * @author Naomichi Yamakita <yamakita@dtx.co.jp>
   */
  public function invite($label, $callback, $attributes = array())
  {
    $path = Mars_MixiMobileApp::buildCallbackActionPath($callback, 'invite:friends');
    $buffer = $this->baseLink($label, $path, $attributes, array());

    return $buffer;
  }

  /**
   * 指定したファイルを mixi フォトアルバムにアップロードするためのリンクタグを生成します。
   * ユーザがアンカーを押下した後、mixi から SAP 側 Web サーバにファイルの取得要求が送信されますが、この時 SAP 側では必ず署名の検証を行う必要があります。
   * 署名の検証は {@link Mars_Mixi2LeggedOAuthProvider::isAuthorized() Mars_Mixi2LeggedOAuthProvider::isAuthorized(Mars_Mixi2LeggedOAuthProvider::SIGNATURE_RSA_PHOTO_UPLOAD)} メソッドを使用して下さい。
   * 
   * @param string $path 投稿対象のファイルパス。PNG、JPEG 形式が指定可能。
   *   指定可能なパスの書式は {@link Mars_HTMLHelper::buildAssetPath()} メソッドを参照。
   * @param string $label {@link Mars_HTMLHelper::link()} メソッドを参照。
   * @param string $callback フォトアップロード後にコールバックするパス。 
   *   指定可能なパスの書式は {@link Mars_RewriteRouter::buildRequestPath()} メソッドを参照。
   * @param mixed $attributes タグに追加する属性。{@link Mars_HTMLHelper::link()} メソッドを参照。
   * @return string 生成したリンクタグを返します。
   * @link http://developer.mixi.co.jp/appli/spec/mob/for_partners/photo_upload_api アプリからフォトアップロード機能について
   * @since 1.9.0-p1
   * @author Naomichi Yamakita <yamakita@dtx.co.jp>
   */
  public function upload($path, $label, $callback, $attributes = array())
  {
    $queryData = array();
    $queryData['img_url'] = Mars_HTMLHelper::buildAssetPath($path, 'image', array('absolute' => TRUE));

    $path = Mars_MixiMobileApp::buildCallbackActionPath($callback, 'upload:photo', $queryData);
    $buffer = $this->baseLink($label, $path, $attributes, array());

    return $buffer;
  }
}
