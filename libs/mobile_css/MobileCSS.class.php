<?php
/**
 * CSS ファイルに定義された CSS をインラインスタイルシートに展開します。
 * このクラスは、openpear 上で HTML_CSS_Mobile として公開されていた同パッケージをベースとしていますが、以下の点に差があります。
 *   - HTML_CSS_Mobile を XHTML に対応 (確認済みの不具合を修正)
 *   - フレームワークとの整合性を吸収
 * 尚、このクラスは PEAR、openpear のライブラリに依存しています。詳しくは README を参照して下さい。
 *
 * 現状の問題点
 *   - global_helpers.yml の 'html.cssMinify' 属性が TRUE の場合でも CSS が圧縮されない。(パーサの問題)
 *
 * @package mobile_css
 * @see http://coderepos.org/share/browser/lang/php/HTML_CSS_Mobile
 * @author Daichi Kamemoto(a.k.a yudoufu) <daikame@gmail.com>
 * @author Naomichi Yamakita <yamakita@dtx.co.jp>
 */

require 'HTML/CSS.php';
require 'HTML/CSS/Selector2XPath.php';

class MobileCSS extends Mars_Object
{
  /**
   * @var HTML_CSS
   */
  private $_htmlCSS;

  /**
   * @var DOMXPath
   */
  private $_xpath;

  /**
   * コンストラクタ。
   *
   * @author Naomichi Yamakita <yamakita@dtx.co.jp>
   */
  public function __construct()
  {
    $this->_htmlCSS = new HTML_CSS();
  }

  /**
   * MobileCSS のオブジェクトインスタンスを取得します。
   *
   * @return MobileCSS MobileCSS のオブジェクトインスタンスを返します。
   * @author Naomichi Yamakita <yamakita@dtx.co.jp>
   */
  public static function getInstance()
  {
    static $instance = NULL;

    if ($instance === NULL) {
      $instance = new MobileCSS();
    }

    return $instance;
  }

  /**
   * 外部 CSS、埋め込み CSS を style 属性として XHTML テキストに割り当てます。
   * エンコーディング形式は変換されない点に注意して下さい。
   *
   * @param string $contents 適用対象の XHTML テキスト。
   * @return string style 属性を適用した XHTML テキストを返します。
   * @author Naomichi Yamakita <yamakita@dtx.co.jp>
   */
  public function assign($contents)
  {
    if (null_or_empty($contents)) {
      return $content;
    }

    if (!mb_check_encoding($contents, 'UTF-8')) {
      $contents = mb_convert_encoding($contents, 'UTF-8', 'Shift_JIS');
    }

    $this->response->setContentType('application/xhtml+xml; charset=Shift_JIS');

    // XML 宣言を取り除く (saveXML() コール時に付加されるため)
    if (preg_match('/^<\?xml\s[^>]+?\?>\s*/', $contents, $matches)) {
      $contents = substr($contents, strlen($matches[0]));
    }

    // xmlns 属性を取り除く (saveXML() コール時に付加されるため)
    $contents = preg_replace('/xmlns=["\'][^"\']+["\']/', '', $contents);

    // charset に UTF-8 以外のエンコーディングが指定されている場合、DOMDocument::loadHTML() が解析に失敗する
    $contents = preg_replace('/charset=Shift_JIS/i', 'charset=UTF-8', $contents);

    // 数値文字参照をエスケープ
    $contents = preg_replace('/&(#(?:\d+|x[0-9a-fA-F]+)|[A-Za-z0-9]+);/', 'HTMLCSSINLINERESCAPE%$1%::::::::', $contents);

    try {
      $dom = new DOMDocument();
      $dom->loadXML($contents);
      $dom->formatOutput = TRUE;
      $dom->encoding = 'UTF-8';
      $dom->xmlStandalone = FALSE;

      $this->_xpath = new DOMXPath($dom);
      $this->loadCSS();

      // CSS をインライン化
      $css = $this->_htmlCSS->toArray();
      $styles = array();

      foreach ($css as $selector => $style) {
        // Selector2XPath は疑似要素の解析が不安定のためスルー
        if (strpos($selector, '@') !== FALSE) {
          continue;
        }

        if (strpos($selector, ':') !== FALSE) {
          $inline = NULL;

          foreach ($style as $name => $value) {
            $inline .= sprintf('%s:%s;', $name, $value);
          }

          $styles[] = sprintf('%s{%s}', $selector, $inline);
          continue;
        }

        $xpath = HTML_CSS_Selector2XPath::toXPath($selector);

        try {
          $elements = $this->_xpath->query($xpath);

          if ($elements->length == 0) {
            continue;
          }

          $inline = NULL;

          foreach ($style as $name => $value) {
            $inline .= sprintf('%s:%s;', $name, $value);
          }

          foreach ($elements as $element) {
            if ($attributeStyle = $element->attributes->getNamedItem('style')) {
              $attributeStyle->nodeValue = $inline . $attributeStyle->nodeValue;
            } else {
              $element->setAttribute('style', $inline);
            }
          }

        // 無効なセレクタを無視
        } catch (Exception $e) {}
      }

      // 疑似クラスを <style> タグとして追加する
      if (sizeof($styles)) {
        $style = implode(PHP_EOL, $styles);
        $head = $this->_xpath->query('//head');

        $node = new DOMElement('style', $style);
        $head->item(0)->appendChild($node)->setAttribute('type', 'text/css');
      }

      $result = $dom->saveXML();
      $result = preg_replace('/encoding="UTF-8"/i', 'encoding="Shift_JIS"', $result);
      $result = preg_replace('/charset=UTF-8/i', 'charset=Shift_JIS', $result);
      $result = preg_replace('/HTMLCSSINLINERESCAPE%(#(?:\d+|x[0-9a-fA-F]+)|[A-Za-z0-9]+)%::::::::/', '&$1;', $result);

      return $result;

    } catch (Exception $e) {
      // loadXML() がスローする例外が分かりにくいため、問題が起きたテンプレートのパスをメッセージに追加しておく
      $message = sprintf('Failed to parse template. (hint: %s) [%s]',
        $e->getMessage(),
        $this->renderer->getTemplatePath());

      throw new Mars_ParseException($message);
    }
  }

  /**
   * コンテンツに含まれる外部 CSS、埋め込み CSS を読み込みます。
   *
   * @author Naomichi Yamakita <yamakita@dtx.co.jp>
   */
  private function loadCSS()
  {
    $nodes = $this->_xpath->query('//link[@rel="stylesheet" or @type="text/css"] | //style[@type="text/css"]');
    $isCSSMinify = Mars_Config::loadHelpers('html.cssMinify');

    foreach ($nodes as $node) {
      if ($node->tagName == 'link' && $href = $node->attributes->getNamedItem('href')) {
        $path = $href->nodeValue;

        if (($pos = strpos($path, '?')) !== FALSE) {
          $path = substr($path, 0, $pos);
        }

        if ($isCSSMinify) {
          $path = str_replace('/min', '', $path);
        }

        if (strpos($path, '/common/base') === FALSE) {
          $data = read_file('webroot' . $path);
        } else {
          $data = read_file(MARS_ROOT_DIR . '/webapps/cpanel/webroot' . $path);
        }

      } else if ($node->tagName == 'style') {
        $data = $node->nodeValue;
      }

      $this->parseCSS($data);

      if ($parent = $node->parentNode) {
        $parent->removeChild($node);
      }
    }
  }

  /**
   * CSS の構文を解析します。
   *
   * @param string $data CSS データ。
   * @author Naomichi Yamakita <yamakita@dtx.co.jp>
   */
  private function parseCSS($data)
  {
    $encoding = mb_detect_encoding($data, 'UTF-8, eucjp-win, sjis-win, iso-2022-jp');

    if ($encoding !== 'UTF-8') {
      $data = mb_convert_encoding($data, 'UTF-8', $encoding);
    }

    $this->_htmlCSS->parseString($data);
  }
}
