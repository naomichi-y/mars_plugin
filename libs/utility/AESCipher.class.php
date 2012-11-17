<?php
/**
 * 共通鍵暗号化方式 AES を用いた文字列の暗号化・復号化機能を提供します。
 * mars に同梱される Mars_Crypter より複雑な暗号方式をサポートするため、セキュリティを要求するプログラムではこちらの利用を強く推奨します。
 * 
 * このプラグインは {@link Mcrypt http://www.php.net/manual/ja/book.mcrypt.php} モジュールに依存します。
 * 
 * @package utility
 * @author Naomichi Yamakita <yamakita@dtx.co.jp>
 */
class AESCipher extends Mars_Object
{
  private $_secretKey;

  /**
   * コンストラクタ。
   * 
   * @param string $secretKey 暗号化に用いる秘密鍵。
   * @author Naomichi Yamakita <yamakita@dtx.co.jp>
   */
  public function __construct($secretKey)
  {
    $this->_secretKey = $secretKey;
  }

  /**
   * 暗号化されたデータを復号化します。
   *
   * @param string 暗号化されたデータ。(Base64 形式)
   * @return string 復号化された文字列を返します。
   * @author Naomichi Yamakita <yamakita@dtx.co.jp>
   */
  public function decode($value)
  {
    $size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_ECB);
    $iv = mcrypt_create_iv($size, MCRYPT_RAND);
    $binary = pack('H*', bin2hex(base64_decode($value)));

    $decrypted = mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $this->_secretKey, $binary, MCRYPT_MODE_ECB, $iv);

    return rtrim($this->pkcs5Unpadding($decrypted));
  }

  /**
   * 文字列を暗号化します。
   *
   * @param string $value 暗号化対象の文字列。
   * @return string 暗号化された文字列を Base64 でエンコードした値を返します。
   * @author Naomichi Yamakita <yamakita@dtx.co.jp>
   */
  public function encode($value)
  {
    $value = $this->pkcs5Padding($value, 16);
    $size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_ECB);
    $iv = mcrypt_create_iv($size, MCRYPT_RAND);
    $bin = pack('H*', bin2hex($value));
    $encrypted = mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $this->_secretKey, $bin, MCRYPT_MODE_ECB, $iv);

    return base64_encode($encrypted);
  }

  /**
   * @author Naomichi Yamakita <yamakita@dtx.co.jp>
   */
  private function pkcs5Padding($value, $blocksize)
  {
    $padding = $blocksize - (strlen($value) % $blocksize);

    return $value . str_repeat(chr($padding), $padding);
  }

  /**
   * @author Naomichi Yamakita <yamakita@dtx.co.jp>
   */
  private function pkcs5Unpadding($value)
  {
    $padding = ord($value{strlen($value) - 1});

    if ($padding > strlen($value)) {
       return false;
    }

    if (strspn($value, chr($padding), strlen($value) - $padding) != $padding) {
      return false;
    }

    return substr($value, 0, -1 * $padding);
  }
}
