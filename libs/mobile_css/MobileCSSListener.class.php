<?php
/**
 * MobileCSS をフレームワークから利用するためのリスナークラスです。
 * MobileCSS はユーザエージェントが DoCoMo の場合に起動します。
 *
 * @package mobile_css
 * @author Naomichi Yamakita <yamakita@dtx.co.jp>
 */
class MobileCSSListener extends Mars_Object implements Mars_ControllerListener
{
  /**
   * @see Mars_ControllerListener::getListenerPoints()
   * @author Naomichi Yamakita <yamakita@dtx.co.jp>
   */
  public function getListenerPoints()
  {
    return array('outputBuffer');
  }

  /**
   * @see Mars_FrontControllerDelegate::outputBuffer()
   * @author Naomichi Yamakita <yamakita@dtx.co.jp>
   */
  public function outputBuffer(&$contents)
  {
    if ($this->request->getUserAgent()->isDoCoMo()) {
      $contents = MobileCSS::getInstance()->assign($contents);
    }
  }
}
