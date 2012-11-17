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
 * PC 版 mixi アプリのリクエスト署名を定義します。
 * <i>公開鍵の有効期限は 2014 年 1 月 5 日 11 時 12 分 03 秒までとなります。</i>
 * 
 * @link http://developer.mixi.co.jp/appli/spec/pc/validating_signed_requests/ 署名付きリクエストの検証
 * @since 1.9.8
 * @author Naomichi Yamakita <yamakita@dtx.co.jp>
 * @category mars
 * @package net.service.mixi
 */

class Mars_MixiPCSignature extends OAuthSignatureMethod_RSA_SHA1
{
  /**
   * OAuthSignatureMethod_RSA_SHA1::fetch_public_cert()
   * @author Naomichi Yamakita <yamakita@dtx.co.jp>
   */
  protected function fetch_public_cert(&$request) {
  return <<< EOD
-----BEGIN CERTIFICATE-----
MIIDfDCCAmSgAwIBAgIJAJU4Z27Mql6HMA0GCSqGSIb3DQEBBQUAMDIxCzAJBgNV
BAYTAkpQMREwDwYDVQQKEwhtaXhpIEluYzEQMA4GA1UEAxMHbWl4aS5qcDAeFw0x
MjAxMDYwMjEyMDNaFw0xNDAxMDUwMjEyMDNaMDIxCzAJBgNVBAYTAkpQMREwDwYD
VQQKEwhtaXhpIEluYzEQMA4GA1UEAxMHbWl4aS5qcDCCASIwDQYJKoZIhvcNAQEB
BQADggEPADCCAQoCggEBAK7r10TPJ6ANhQ8lK7IvX7E8KlWf4hA7LfbSy4EYbQOr
nTsNu52lnqPDnRrMhemWx1cSgOYSzMzW2b5OufSR8rGM/CVQBKmmKScsG2mG0KOX
CdBJKBJv+vk9SV9zfPCLETiE9gqy86Tz9UxLQUKIN/Vwj2GpQ6wIaJQGxrUtkdo9
kRzVA+PrLz+1mdhcNnvu15IzKoqCYwYQDrT1XysvKt8GPkYPlVweBzawpGOBqrPi
zmju7P8yUfqoaO9eJr4arV+dZVup4yGTQlmrHbe/em9+l4HEboD8kiqiUiGwO5Ap
3Ndtco4yfJDYXu4b6rVJAV+c8YZfyboJZqPIEzGTvoECAwEAAaOBlDCBkTAdBgNV
HQ4EFgQUbwJb5soi8EcvovonJ/GS3Di8VngwYgYDVR0jBFswWYAUbwJb5soi8Ecv
ovonJ/GS3Di8VnihNqQ0MDIxCzAJBgNVBAYTAkpQMREwDwYDVQQKEwhtaXhpIElu
YzEQMA4GA1UEAxMHbWl4aS5qcIIJAJU4Z27Mql6HMAwGA1UdEwQFMAMBAf8wDQYJ
KoZIhvcNAQEFBQADggEBAKmheYWIpvyoSz0eBkcmdeZMq+EgH2lrbdfIIkpZ86N9
TFk7whfSlpSsOPaTyJcjTyWOV7orMdsOHwDTdSeCFtIoatHbVsy/KNYQcO+zAI3f
glJ7MjpmN94fv0fRluwzp9g3OL90cC6b4qbDogtttGg8d+jiZ0Y38lOg0EOjjVQp
jocQn0etv9PxY9zzcqOcxJXr/S9GkQFyc4HqzPJwT+FNS44pf7NM5cG9Yr/IKeQh
vhWPsX3bF9Br9o7nSrcTeJzm5u3JQ7Rt0qSX3RPW4XA6uqd4DjrBQqkzk3tc6S25
L1kBVeo29EXDETUnfGz6UuNcxuA6yfXl6l7ypSVpv90=
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
