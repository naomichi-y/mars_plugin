<?php
/**
 * @author Naomichi Yamakita <yamakita@dtx.co.jp>
 * @category mars
 * @package net.service.mixi
 * @version $Id: Mars_MixiApp.iface.php 2811 2011-06-04 07:06:40Z yamakita $
 * @copyright Copyright (c) 2006-2012 dt corporation
 * @since 1.9.0
 */

/**
 * Mars_MixiApp インタフェースは、mixi アプリのため基本インタフェースを定義します。
 * 
 * @since 1.9.0
 * @author Naomichi Yamakita <yamakita@dtx.co.jp>
 * @category mars
 * @package net.service.mixi
 */

interface Mars_MixiApp
{
  /**
   * エージェントタイプ。(PC)
   */
  const AGENT_TYPE_PC = 1;

  /**
   * エージェントタイプ。(モバイル)
   */
  const AGENT_TYPE_MOBILE = 2;

  /**
   * エージェントタイプ。(スマートフォン)
   */
  const AGENT_TYPE_SMARTPHONE = 4;
}
