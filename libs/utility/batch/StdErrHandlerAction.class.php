<?php
/**
 * 標準エラーを取得し、mars のロガーにメッセージを送信します。
 * 
 * 標準エラーの形式について:<br />
 * このクラスは現在のところ、JSON 形式の STDERR をサポートします。
 * 使用方法は {@link http://code.dtx-mars.com/?p=3025 コードスニペット} を参照して下さい。
 * 
 * オプション引数:
 *   <ul>
 *     <li>
 *       --type: 標準エラーの形式。
 *       <ul>
 *         <li>json: JSON 形式。データ形式に制限はなく、全てのデータがロギングされる。</li>
 *       </ul>
 *     </li>
 *     <li>
 *       --level: ロガーに通知するログレベル。
 *       <ul>
 *         <li>trace</li>
 *         <li>debug</li>
 *         <li>info</li>
 *         <li>warning</li>
 *         <li>error</li>
 *         <li>fatal</li>
 *       </ul>
 *     </li>
 *   </ul>
 *
 * @package utility.batch
 * @author Naomichi Yamakita <yamakita@dtx.co.jp>
 */
class StdErrHandlerAction extends Mars_Action
{
  public function execute()
  {
    $arguments = $this->controller->getArguments();
    $type = array_find($arguments, 'type', 'json');
    $level = array_find($arguments, 'level', 'error');

    $levels = array('trace', 'debug', 'info', 'warning', 'error', 'fatal'); 

    if (!in_array($level, $levels)) {
      fputs(STDERR, sprintf('Invalid parameter [--level=%s]', $level));
      exit;
    }

    $fp = fopen('php://stderr', 'r');
    $buffer = NULL;

    $data = fread($fp, 4096);

    if ($type === 'json') {
      $separate = str_repeat('=', 74);
      $message = sprintf("%s\nSummary:\n%s\n%s\n"
       ."%s\n\$_SERVER:\n%s\n%s\n",
        $separate,
        $separate,
        json_string_format($data),
        $separate,
        $separate,
        var_export($_SERVER, TRUE));
    }

    $array = json_decode($data, TRUE);

    $logger = Mars_Logger::getLogger(basename($array['path']));
    $logger->$level($message);
  }
}
