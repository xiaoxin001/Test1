<?php
namespace Ejiayou\PHP\Utils\Encrypt\AES\MiniApps;

class Prpcrypt
{
    public $key;

    function __construct( $k )
    {
        $this->key = $k;
    }

    /**
     * 对密文进行解密
     * @param $aesCipher 需要解密的密文
     * @param $aesIV  解密的初始向量
     * @return array 解密得到的明文
     */
    public function decrypt( $aesCipher, $aesIV )
    {
        try {
            $decrypted = openssl_decrypt($aesCipher, 'aes-128-cbc', $this->key, OPENSSL_RAW_DATA, $aesIV);
        } catch (Exception $e) {
            return array(ErrorCode::$IllegalBuffer, null);
        }

        try {
            //去除补位字符
            $pkc_encoder = new PKCS7Encoder;
            $result = $pkc_encoder->decode($decrypted);
        } catch (Exception $e) {
            return array(ErrorCode::$IllegalBuffer, null);
        }
        return array(0, $result);
    }
}
