<?php
/**
 * @author Naomichi Yamakita <yamakita@dtx.co.jp>
 * @category mars
 * @package net.service.mixi
 * @version $Id: Mars_MixiLifecycleEventSignature.class.php 2803 2011-06-02 16:36:43Z yamakita $
 * @copyright Copyright (c) 2006-2012 dt corporation
 * @since 1.9.0
 */

/**
 * ライフサイクルイベントのリクエスト署名を定義します。
 * <i>公開鍵の有効期限は 2014 年 1 月 5 日 13 時 42 分 34 秒までとなります。</i>
 * 
 * @link http://developer.mixi.co.jp/appli/spec/mob/lifecycle_event ライフサイクルイベントについて
 * @since 1.9.0
 * @author Naomichi Yamakita <yamakita@dtx.co.jp>
 * @category mars
 * @package net.service.mixi
 */

class Mars_MixiLifecycleEventSignature extends OAuthSignatureMethod_RSA_SHA1
{
  /**
   * OAuthSignatureMethod_RSA_SHA1::fetch_public_cert()
   * @author Naomichi Yamakita <yamakita@dtx.co.jp>
   */
  protected function fetch_public_cert(&$request) {
  return <<< EOD
-----BEGIN CERTIFICATE-----
MIIDfDCCAmSgAwIBAgIJAPgNPMrtHFQCMA0GCSqGSIb3DQEBBQUAMDIxCzAJBgNV
BAYTAkpQMREwDwYDVQQKEwhtaXhpIEluYzEQMA4GA1UEAxMHbWl4aS5qcDAeFw0x
MjAxMDYwNDQyMzRaFw0xNDAxMDUwNDQyMzRaMDIxCzAJBgNVBAYTAkpQMREwDwYD
VQQKEwhtaXhpIEluYzEQMA4GA1UEAxMHbWl4aS5qcDCCASIwDQYJKoZIhvcNAQEB
BQADggEPADCCAQoCggEBAMP3x3SToLiP0wWgMkAQGYNcf5VhLYouTzp66HVhTtJY
Q2yRkzY1QPZ+7psqN/IG9rVYyx8AFxzcCNjdGvVN0+XGV+yh5HZOFUA1P0WuGrom
mgEnbQeBvZSm176abp6Pg4nPtRdjNCWEajsIDA6u+0E+DcBNN5lU++cq5jeaEVPv
xvfR6VhIwLwsWrlgyfaWBQj7ngXtsA+vjUMSgSo9bPpy6ReIUV+lcHGnFYgnahUO
XZ34FPjKAlDk82S8IwqeY0RVk2msNZOYQtOcJJ4SHToRsD7B5//vYl+kzfytjZuj
GEsGY4EzIZXslphEE8BACvj7LVHw2QFlVcUZB7V7l3sCAwEAAaOBlDCBkTAdBgNV
HQ4EFgQU1hPJWSRz6ueV8GvU1Cw8jmC4ps4wYgYDVR0jBFswWYAU1hPJWSRz6ueV
8GvU1Cw8jmC4ps6hNqQ0MDIxCzAJBgNVBAYTAkpQMREwDwYDVQQKEwhtaXhpIElu
YzEQMA4GA1UEAxMHbWl4aS5qcIIJAPgNPMrtHFQCMAwGA1UdEwQFMAMBAf8wDQYJ
KoZIhvcNAQEFBQADggEBAHWdnw7YEY3IuHDqpVJ5IOukaMNPIcLREQstN8mvdD+W
juN8zS74zj7BiZwAMDxRuFv9Ev8HGpIfKXLEEdBA/wZ5I6lzFqCxVbrp/HrCHoMV
o9SwKMQJzvmgsg7JvcJKZ1AjgkUHuqbBC1Ikfe0rp7/1uZao5jRCr9wdKmJwt73W
0deFW/OTFz0SMK5zvbUBWqPSXHolTrCrAQDHF/t5AsX2DNWsqH8rTtLHHkxIiFZI
/zldHT49NZWV/WGmKOzoXAIqWDxEsc1/2agDR7oOfx4thFZgxj+Lcrk3dDi3x4g7
alvt9KoyJE1kNcHAbqxC+tfwNQvxwo85Cg7XN69225U=
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
