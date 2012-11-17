<?php
/**
 * @author Naomichi Yamakita <yamakita@dtx.co.jp>
 * @category mars
 * @package net.service.mixi.helper
 * @version $Id: Mars_MixiMobileAppFormHelper.class.php 3098 2011-10-09 20:34:54Z yamakita $
 * @copyright Copyright (c) 2006-2012 dt corporation
 * @since 1.9.0
 */

/**
 * mixi モバイルアプリのための Form ヘルパです。
 * 
 * @since 1.9.0
 * @author Naomichi Yamakita <yamakita@dtx.co.jp>
 * @category mars
 * @package net.service.mixi.helper
 */
class Mars_MixiMobileAppFormHelper extends Mars_FormHelper
{
  /**
   * コンストラクタ。
   * 次のヘルパ属性は mixi アプリ用に再設定されます。
   *  - errorFieldTag: <div style="color: red">\1</div>
   *  - fieldTag: \1
   * 
   * @author Naomichi Yamakita <yamakita@dtx.co.jp>
   */
  public function __construct()
  {
    $helperConfig = Mars_Config::load(Mars_Config::YAML_GLOBAL_HELPERS, 'form');

    $helperConfig['errorFieldTag'] = '<div style="color: red">\1</div>';
    $helperConfig['fieldTag'] = '\1';

    parent::__construct($helperConfig);
  }

  /**
   * <i>フォームの送信形式は POST 固定です。また、attributes に 'name' 属性を指定することはできません。</i>
   * 
   * @link http://developer.mixi.co.jp/appli/spec/mob/for_partners/pages_api ページ遷移と API アクセス
   * @see Mars_FormHelper::start()
   * @author Naomichi Yamakita <yamakita@dtx.co.jp>
   */
  public function start($path = NULL, $attributes = array())
  {
    // GET は使用不可 (mixi 側の仕様)
    $attributes = parent::constructParameters($attributes);
    $attributes['action'] = Mars_MixiMobileApp::buildForwardActionURI($path);
    $attributes['method'] = 'post';

    $buffer = sprintf("<form%s>\n",
                      parent::buildTagAttribute($attributes, FALSE));

    return $buffer;
  }

  /**
   * startMultipart() メソッドは mixi アプリ上で実行することはできません。
   * 
   * @throws Mars_UnsupportedException メソッドが呼び出された場合に発生。
   * @author Naomichi Yamakita <yamakita@dtx.co.jp>
   */
  public function startMultipart()
  {
    $message = sprintf('startMultipart() method execution is not supported.');
    throw new Mars_UnsupportedException($message);
  }


  /**
   * 基本動作は {@link Mars_FormHelper::containFieldErrors()} メソッドと同じですが、エラーメッセージの文字色が style 属性で指定されている点が異なります。
   * (DoCoMo 2.0 以前の端末では class 指定による CSS を解釈できないため)
   * 
   * @see Mars_FormHelper::containFieldErrors()
   * @author Naomichi Yamakita <yamakita@dtx.co.jp>
   */
  public function containFieldErrors($attributes = array('style' => 'color: red'))
  {
    return parent::containFieldErrors($attributes);
  }

  /**
   * inputImage() メソッドは mixi アプリ上で実行することはできません。
   * 
   * @throws Mars_UnsupportedException メソッドが呼び出された場合に発生。
   * @author Naomichi Yamakita <yamakita@dtx.co.jp>
   */
  public function inputImage()
  {
    $message = sprintf('inputImage() method execution is not supported.');
    throw new Mars_UnsupportedException($message);
  }

  /**
   * @author Naomichi Yamakita <yamakita@dtx.co.jp>
   */
  private function buildLaunchURI($type, $queryData)
  {
    if ($type === Mars_MixiApp::AGENT_TYPE_PC || $type == Mars_MixiApp::AGENT_TYPE_SMARTPHONE) {
      $buffer = NULL;

      if (sizeof($queryData)) {
        $buffer = '&' . http_build_query($queryData, '', '&');
      }

      $uri = sprintf('http://mixi.jp/run_appli.pl?id=%s%s',
                     Mars_MixiMobileApp::getApplicationId(),
                     $buffer);

    } else {
      if (!isset($queryData['action'])) {
        $queryData['action'] = Mars_Config::loadSetup('action.default');
      }

      $uri = Mars_MixiMobileApp::buildForwardActionURI($queryData, TRUE);
    }

    return $uri;
  }

  /**
   * ボイス投稿フォームを生成します。
   * 生成されるフォームにはボイス入力フィールドや投稿ボタンが含まれます。
   * 
   * @param mixed $callback ボイス投稿確認ページ遷移後にコールバックするパス。
   *   指定可能なパスの書式は {@link Mars_RewriteRouter::buildRequestPath()} メソッドを参照。
   * @param mixed $extra オプション属性。'キー=値;' 形式の文字列、または連想配列での指定が可能。
   *   - defaultTweet: テキストフィールドに表示するデフォルトのメッセージ。
   *   - appendURI: TRUE が指定された場合、投稿されたつぶやきにアプリ起動のための URI を追加します。既定値は FALSE。
   *   - query: PC 向けアプリの起動 URI に追加するパラメータを連想配列形式で指定。
   *       未指定の場合はスタート画面が表示される。
   *   - mobileQuery: モバイル向けアプリの起動パス。
   *       指定可能なパスの書式は {@link Mars_RewriteRouter::buildRequestPath()} メソッドを参照。
   *       未指定の場合はスタート画面が表示される。
   *   - touchQuery: スマートフォン向けアプリの起動 URI に追加するパラメータを連想配列形式で指定。
   *       未指定の場合はスタート画面が表示される。
   * @return string 生成したタグを返します。
   * @link http://developer.mixi.co.jp/appli/spec/mob/for_partners/send_voice mixi ボイスの投稿について
   * @author Naomichi Yamakita <yamakita@dtx.co.jp>
   */
  public function voiceForm($callback = NULL, $extra = array())
  {
    $attributes = array();
    $attributes['action'] = Mars_MixiMobileApp::buildCallbackActionPath($callback, 'update:status');
    $attributes['method'] = 'post';

    $extra = parent::constructParameters($extra);
    $hiddenBuffer = NULL;

    // つぶやきにリンクを追加する場合
    if (array_find($extra, 'appendURI', FALSE)) {
      $uri = $this->buildLaunchURI(Mars_MixiApp::AGENT_TYPE_PC, array_find($extra, 'query', array()));
      $mobileURI = $this->buildLaunchURI(Mars_MixiApp::AGENT_TYPE_MOBILE, array_find($extra, 'mobileQuery', array()));
      $touchURI = $this->buildLaunchURI(Mars_MixiApp::AGENT_TYPE_SMARTPHONE, array_find($extra, 'touchQuery', array()));

      $hiddenBuffer = $this->inputHidden('url', array('value' => $uri))
                     .$this->inputHidden('mobileUrl', array('value' => $mobileURI))
                     .$this->inputHidden('touchUrl', array('value' => $touchURI));
    }

    $bodyAttributes = array('value' => array_find($extra, 'defaultTweet'));

    $buffer = sprintf("<form%s>\n%s%s%s%s",
                      parent::buildTagAttribute($attributes, FALSE),
                      $this->inputText('body', $bodyAttributes),
                      $this->inputSubmit('つぶやく'),
                      $hiddenBuffer,
                      $this->close());

    return $buffer;
  }

  /**
   * コミュニケーションフィード投稿フォームを生成します。
   * 生成されるフォームにはフィード投稿ボタンが含まれます。
   * 
   * @param string $title 投稿するアクティビティの本文。
   * @param string $callback フィード投稿確認ページ遷移後にコールバックするパス。
   *   指定可能なパスの書式は {@link Mars_RewriteRouter::buildRequestPath()} メソッドを参照。
   * @param mixed $extra オプション属性。'キー=値;' 形式の文字列、または連想配列での指定が可能。
   * <ul>
   *   <li>appendURI: TRUE が指定された場合、投稿されたフィードにアプリ起動のための URI を追加します。既定値は FALSE。 </li>
   *   <li>
   *     query: PC 向けアプリの起動 URI に追加するパラメータを連想配列形式で指定。
   *       未指定の場合はスタート画面が表示される。
   *   </li>
   *   <li>
   *     mobileQuery: モバイル向けアプリの起動パス。
   *       指定可能なパスの書式は {@link Mars_RewriteRouter::buildRequestPath()} メソッドを参照。
   *       未指定の場合はスタート画面が表示される。
   *   </li>
   *   <li>
   *     mediaItem1: フィードに含める画像。mediaItem2、mediaItem3 も指定可能。
   *     <ul>
   *       <li>第 1 引数: 画像のパス。
   *         <ul>
   *           <li>GIF、JPEG 形式 (40px*40px) が指定可能。</li>
   *           <li>パスの書式は {@link Mars_HTMLHelper::buildAssetPath()} メソッドを参照。</li>
   *           <li>外部 URI のパスは指定不可能。</li>
   *         </ul>
   *       </li>
   *       <li>第 2 引数: 画像の MIME タイプを指定。</li>
   *     </ul>
   *   </li>
   *   <li>recipient1: 宛先を指定する場合のユーザ ID。recipient2 も指定可能。</li>
   *   <li>buttonLabel: 送信ボタンのラベル。</li>
   * </ul>
   * @return string 生成したタグを返します。
   * @link http://developer.mixi.co.jp/appli/spec/mob/for_partners/post_activity アクティビティフィードについて
   * @author Naomichi Yamakita <yamakita@dtx.co.jp>
   */
  public function communicationFeedForm($title, $callback = NULL, $extra = array())
  {
    $attributes = array();
    $attributes['action'] = Mars_MixiMobileApp::buildCallbackActionPath($callback, 'create:activity');
    $attributes['method'] = 'post';

    $extra = parent::constructParameters($extra);
    $hiddenBuffer = NULL;

    // フィードに URI を追加
    if (array_find($extra, 'appendURI')) {
      $uri = $this->buildLaunchURI(Mars_MixiApp::AGENT_TYPE_PC, array_find($extra, 'query', array()));
      $mobileURI = $this->buildLaunchURI(Mars_MixiApp::AGENT_TYPE_MOBILE, array_find($extra, 'mobileQuery', array()));

      $hiddenBuffer = $this->inputHidden('url', array('value' => $uri))
                     .$this->inputHidden('mobileUrl', array('value' => $mobileURI));
    }

    // 画像の添付
    for ($i = 1; $i <= 3; $i++) {
      $target = 'mediaItem' . $i;
      $mediaItem = array_find($extra, $target);

      if (is_array($mediaItem)) {
        $value = sprintf('%s,%s',
          Mars_HTMLHelper::buildAssetPath($mediaItem[0], 'image', array('absolute' => TRUE)),
          $mediaItem[1]);

        $hiddenBuffer .= $this->inputHidden($target, array('value' => $value));
      }
    }

    // 宛先ユーザ ID の指定
    for ($i = 1; $i <= 2; $i++) {
      $target = 'recipient' . $i;
      $recipient = array_find($extra, $target);

      if ($recipient !== NULL) {
        $hiddenBuffer .= $this->inputHidden($target, array('value' => $recipient));
      }
    }

    $buttonLabel = array_find($extra, 'buttonLabel', '送信');

    $buffer = sprintf("<form%s>\n%s%s%s%s",
                      parent::buildTagAttribute($attributes, FALSE),
                      $this->inputHidden('title',  array('value' => $title)),
                      $this->inputSubmit($buttonLabel),
                      $hiddenBuffer,
                      $this->close());

    return $buffer;
  }

  /**
   * メッセージ送信画面に遷移するためのフォームを生成します。
   * 生成されるフォームにはメッセージ送信画面への遷移ボタンが含まれます。 
   * 
   * @param string $recipientId メッセージの送り先ユーザ ID。
   * @param string $title デフォルトの件名。
   * @param string $body メッセージに追加される固定本文。
   * @param string $callback メッセージ送信確認ページ遷移後にコールバックするパス。
   *   指定可能なパスの書式は {@link Mars_RewriteRouter::buildRequestPath()} メソッドを参照。
   * @param mixed $extra オプション属性。'キー=値;' 形式の文字列、または連想配列での指定が可能。
   *   buttonLabel: 送信ボタンのラベル。
   * @return string 生成したタグを返します。
   * @link http://developer.mixi.co.jp/appli/spec/mob/for_partners/sendmessage メッセージ送信機能について
   * @author Naomichi Yamakita <yamakita@dtx.co.jp>
   */
  public function messageForm($recipientId, $title, $body, $callback = NULL, $extra = array())
  {
    $attributes = array();
    $attributes['action'] = Mars_MixiMobileApp::buildCallbackActionPath($callback, 'send:message');
    $attributes['method'] = 'post';

    $extra = parent::constructParameters($extra);
    $buttonLabel = array_find($extra, 'buttonLabel', 'メッセージを送る');

    $buffer = sprintf("<form%s>\n%s%s%s%s%s",
                      parent::buildTagAttribute($attributes, FALSE),
                      $this->inputHidden('recipients', array('value' => $recipientId)),
                      $this->inputHidden('title', array('value' => $title)),
                      $this->inputHidden('body', array('value' => $body)),
                      $this->inputSubmit($buttonLabel),
                      $this->close());

    return $buffer;
  }

  /**
   * マイミクをアプリに招待するためのフォームを生成します。
   * 
   * @param string $callback 招待状送信後にコールバックするパス。
   *   指定可能なパスの書式は {@link Mars_RewriteRouter::buildRequestPath()} メソッドを参照。
   * @param mixed $extra オプション属性。'キー=値;' 形式の文字列、または連想配列での指定が可能。
   *   - recipients: 初期選択状態とするユーザ ID。カンマ区切りの文字列、または配列形式で指定可能。
   *   - message: メッセージ本文。
   *   - image: メッセージに含める画像。{@link communicationFeedForm()} メソッドの 'mediaItem1' 属性を参照。
   *   - filterType: 対象とするユーザの種別。'joined'、'nod_joinded'、'both' のいずれかを指定。既定値は 'joined'。
   *   - appendURI: TRUE が指定された場合、招待状にアプリ起動のための URI を追加します。既定値は FALSE。 
   *   - query: {@link communicationFeedForm()} メソッドの 'query' 属性を参照。
   *       未指定の場合はスタート画面が表示される。
   *   - mobileQuery: {@link communicationFeedForm()} メソッドの 'mobileQuery' 属性を参照。
   *       未指定の場合はスタート画面が表示される。
   *   - targetUsers: 友人の選択画面で表示するユーザのリスト。カンマ区切りの文字列、または配列形式で指定可能。
   *   - description: 友人の選択画面で表示する説明文。
   *   - excludeUsers: 友人の選択画面で非表示にするユーザのリスト。カンマ区切りの文字列、または配列形式で指定可能。
   *   - buttonLabel: 送信ボタンのラベル。
   * @return string 生成したタグを返します。
   * @link http://developer.mixi.co.jp/appli/spec/mob/for_partners/request_api リクエスト API について
   */
  public function inviteForm($callback = NULL, $extra = array())
  {
    $attributes = array();
    $attributes['action'] = Mars_MixiMobileApp::buildCallbackActionPath($callback, 'invite:friends');
    $attributes['method'] = 'post';

    $extra = parent::constructParameters($extra);
    $hiddenBuffer = NULL;

    $recipients = array_find($extra, 'recipients');
    $message = array_find($extra, 'message');
    $image = array_find($extra, 'image');
    $filterType = array_find($extra, 'filterType', 'joined');
    $appendURI = array_find($extra, 'appendURI', FALSE);
    $query = array_find($extra, 'query', array());
    $mobileQuery = array_find($extra, 'mobileQuery', array());
    $targetUsers = array_find($extra, 'targetUsers');
    $description = array_find($extra, 'description');
    $excludeUsers = array_find($extra, 'excludeUsers');
    $buttonLabel = array_find($extra, 'buttonLabel', '送信');

    if ($recipients !== NULL) {
      if (is_array($recipients)) {
        $recipients = implode(',', $recipients);
      }

      $hiddenBuffer .= $this->inputHidden('recipients', array('value' => $recipients));
    }

    if ($message !== NULL) {
      $hiddenBuffer .= $this->inputHidden('message', array('value' => $message));
    }

    if (is_array($image)) {
      $value = sprintf('%s,%s',
        Mars_HTMLHelper::buildAssetPath($image[0], 'image', array('absolute' => TRUE)),
        $image[1]);
      $hiddenBuffer .= $this->inputHidden('image', array('value' => $value));
    }

    if ($appendURI) {
      $uri = $this->buildLaunchURI(Mars_MixiApp::AGENT_TYPE_PC, $query);
      $hiddenBuffer .= $this->inputHidden('url', array('value' => $uri));

      $mobileURI = $this->buildLaunchURI(Mars_MixiApp::AGENT_TYPE_MOBILE, $mobileQuery);
      $hiddenBuffer .= $this->inputHidden('mobile_url', array('value' => $mobileURI));
    }

    if ($targetUsers !== NULL) {
      if (is_array($targetUsers)) {
        $targetUsers = implode(',', $targetUsers);
      }

      $hiddenBuffer .= $this->inputHidden('target_users', array('value' => $targetUsers));
    }

    if ($description !== NULL) {
      $hiddenBuffer .= $this->inputHidden('description', array('value' => $description));
    }

    if ($excludeUsers !== NULL) {
      if (is_array($excludeUsers)) {
        $excludeUsers = implode(',', $excludeUsers);
      }

      $hiddenBuffer .= $this->inputHidden('exclude_users', array('value' => $excludeUsers));
    }

    $buffer = sprintf("<form%s>\n%s%s%s%s",
                      parent::buildTagAttribute($attributes, FALSE),
                      $this->inputHidden('filter_type', array('value' => 'joined')),
                      $hiddenBuffer,
                      $this->inputSubmit($buttonLabel),
                      $this->close());

    return $buffer;
  }
}
