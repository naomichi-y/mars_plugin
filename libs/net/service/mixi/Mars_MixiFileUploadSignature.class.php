<?php
/**
 * @author Naomichi Yamakita <yamakita@dtx.co.jp>
 * @category mars
 * @package net.service.mixi
 * @version $Id: Mars_MixiFileUploadSignature.class.php 2803 2011-06-02 16:36:43Z yamakita $
 * @copyright Copyright (c) 2006-2012 dt corporation
 * @since 1.9.0
 */

/**
 * フォトアップロード機能のリクエスト署名を定義します。
 * 
 * @link http://developer.mixi.co.jp/appli/spec/mob/photo_upload_apihttp://developer.mixi.co.jp/appli/spec/mob/photo_upload_api アプリからフォトアップロード機能について
 * @since 1.9.0
 * @author Naomichi Yamakita <yamakita@dtx.co.jp>
 * @category mars
 * @package net.service.mixi
 */

class Mars_MixiFileUploadSignature extends OAuthSignatureMethod_RSA_SHA1
{
  /**
   * OAuthSignatureMethod_RSA_SHA1::fetch_public_cert()
   * @author Naomichi Yamakita <yamakita@dtx.co.jp>
   */
  protected function fetch_public_cert(&$request) {
  return <<< EOD
-----BEGIN CERTIFICATE-----
MIICdzCCAeCgAwIBAgIJAK6oiSkLW/goMA0GCSqGSIb3DQEBBQUAMDIxCzAJBgNV
BAYTAkpQMREwDwYDVQQKEwhtaXhpIEluYzEQMA4GA1UEAxMHbWl4aS5qcDAeFw0x
MDA3MjEwODI0MDFaFw0xMjA3MjAwODI0MDFaMDIxCzAJBgNVBAYTAkpQMREwDwYD
VQQKEwhtaXhpIEluYzEQMA4GA1UEAxMHbWl4aS5qcDCBnzANBgkqhkiG9w0BAQEF
AAOBjQAwgYkCgYEAzSWXar2xZ1+2kdJKW6FzJBB8/RtDOWY46sQN3q93UQP6RQi/
AGeyhd0UNcx8uw+N7ulz/dNDdy1EbwrXMdN0jfK0SRHF61HIfyLfBNrWNUqhlwbj
j0duZcdLeHkWDmoZdB9bekOvFfLKIF9Qey/njQSUdglfTL9P2XwaYjFXjqkCAwEA
AaOBlDCBkTAdBgNVHQ4EFgQUsqTgGT8ThFGs/6EcCNDEH/QivKswYgYDVR0jBFsw
WYAUsqTgGT8ThFGs/6EcCNDEH/QivKuhNqQ0MDIxCzAJBgNVBAYTAkpQMREwDwYD
VQQKEwhtaXhpIEluYzEQMA4GA1UEAxMHbWl4aS5qcIIJAK6oiSkLW/goMAwGA1Ud
EwQFMAMBAf8wDQYJKoZIhvcNAQEFBQADgYEAe2KWtJV2tSVAqZ988NuwXym73yPy
PphRet/GzFnA4kWJzJ47AXbpSW2hwx/zbnV57bJ1/+nRau4T6E+FkaBnYgVQB1AH
1RPhIEXlaueur1Zd2cTe2c09IHSfiiv6Vx3rc+oqTtjmKys6OqV1U+rMZg2wO7qN
0n8x+NIZc268bzY=
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
