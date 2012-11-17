<?php
/**
 * @author Naomichi Yamakita <yamakita@dtx.co.jp>
 * @category mars
 * @package net.service.mixi
 * @version $Id: Mars_MixiFileUploadSignature.class.php 2803 2011-06-02 16:36:43Z yamakita $
 * @copyright Copyright (c) 2006-2012 dt corporation
 * @since 1.9.8
 */

/**
 * Touch 版 mixi アプリのリクエスト署名を定義します。
 * 
 * @link http://developer.mixi.co.jp/appli/spec/touch/getting_userid_and_verify_signature/ アクセスユーザの取得と OAuth Signature の検証 
 * @since 1.9.8
 * @author Naomichi Yamakita <yamakita@dtx.co.jp>
 * @category mars
 * @package net.service.mixi
 */

class Mars_MixiTouchSignature extends OAuthSignatureMethod_RSA_SHA1
{
  /**
   * OAuthSignatureMethod_RSA_SHA1::fetch_public_cert()
   * @author Naomichi Yamakita <yamakita@dtx.co.jp>
   */
  protected function fetch_public_cert(&$request) {
  return <<< EOD
-----BEGIN CERTIFICATE-----
MIICdzCCAeCgAwIBAgIJAL1PPQYXg2doMA0GCSqGSIb3DQEBBQUAMDIxCzAJBgNV
BAYTAkpQMREwDwYDVQQKEwhtaXhpIEluYzEQMA4GA1UEAxMHbWl4aS5qcDAeFw0x
MDA2MTQwNzE4MDFaFw0xMjA2MTMwNzE4MDFaMDIxCzAJBgNVBAYTAkpQMREwDwYD
VQQKEwhtaXhpIEluYzEQMA4GA1UEAxMHbWl4aS5qcDCBnzANBgkqhkiG9w0BAQEF
AAOBjQAwgYkCgYEAyLv0jHJHboDJn8yUeAoQhE94HBfu9c1hfjPVkJ6czmD0fW4x
H/TGsExmIIPBG8FS5/dJNl8Fgm63X9drsTZEAPmWVFr0mrPkP2n2pRW7y0marYmH
SNgpFeAD/C0fMFPS2HZ05jjwJJi62+xjnseHfX3V5o3JJ1gOuTUhqFN6njUCAwEA
AaOBlDCBkTAdBgNVHQ4EFgQU7UzxLRDMxmgBPsJvE6HjWJMk4g4wYgYDVR0jBFsw
WYAU7UzxLRDMxmgBPsJvE6HjWJMk4g6hNqQ0MDIxCzAJBgNVBAYTAkpQMREwDwYD
VQQKEwhtaXhpIEluYzEQMA4GA1UEAxMHbWl4aS5qcIIJAL1PPQYXg2doMAwGA1Ud
EwQFMAMBAf8wDQYJKoZIhvcNAQEFBQADgYEAWzi3Co9uJ62iGOtriC4JUpDWNYo3
EkHIIZ4xa95kKEn2MKRUqEHYpyavWeFhdE3bpqfwN0QmFMOQHwuYmh3E8aiMBCyQ
CF/Y1KPB1kxMsJ0HDr7gPx/nE5y1GB8ZxhhRqHNLmQeQkXNKKEr+k8TWruiRrcn3
8fCjc9qX8/yby/U=
-----END CERTIFICATE-----
EOD;
  }

  /**
   * OAuthSignatureMethod_RSA_SHA1::fetch_private_cert()
   * @author Naomichi Yamakita <yamakita@dtx.co.jp>
   */
  protected function fetch_private_cert(&$request)
  {
    return;
  }
}
