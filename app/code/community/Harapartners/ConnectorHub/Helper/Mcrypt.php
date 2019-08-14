<?php
/*
 * NOTICE OF LICENSE
 *
 * This source file is subject to the End User Software Agreement (EULA).
 * It is also available through the world-wide-web at this URL:
 * http://www.harapartners.com/license [^]
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to eula@harapartners.com so we can send you a copy immediately.
 *
 */
class Harapartners_ConnectorHub_Helper_Mcrypt extends Harapartners_ConnectorHub_Helper_Object {

    public function init($key)
    {
        if (!$this->getCipher()) {
            $this->setCipher(MCRYPT_BLOWFISH);
        }

        if (!$this->getMode()) {
            $this->setMode(MCRYPT_MODE_ECB);
        }

        $this->setHandler(mcrypt_module_open($this->getCipher(), '', $this->getMode(), ''));

        if (!$this->getInitVector()) {
            if (MCRYPT_MODE_CBC == $this->getMode()) {
                $this->setInitVector(substr(
                    md5(mcrypt_create_iv (mcrypt_enc_get_iv_size($this->getHandler()), MCRYPT_RAND)),
                    - mcrypt_enc_get_iv_size($this->getHandler())
                ));
            } else {
                $this->setInitVector(mcrypt_create_iv (mcrypt_enc_get_iv_size($this->getHandler()), MCRYPT_RAND));
            }
        }

        $maxKeySize = mcrypt_enc_get_key_size($this->getHandler());

        if (strlen($key) > $maxKeySize) { // strlen() intentionally, to count bytes, rather than characters
            $this->setHandler(null);
            throw new Exception('Maximum key size must be smaller '.$maxKeySize);
        }

        mcrypt_generic_init($this->getHandler(), $key, $this->getInitVector());

        return $this;
    }

    public function encrypt($data)
    {
        if (!$this->getHandler()) {
            throw new Exception('Crypt module is not initialized.');
        }
        if (strlen($data) == 0) {
            return $data;
        }
        return mcrypt_generic($this->getHandler(), $data);
    }

    public function decrypt($data)
    {
        if (!$this->getHandler()) {
            throw new Exception('Crypt module is not initialized.');
        }
        if (strlen($data) == 0) {
            return $data;
        }
        //Encrypt-Decrypt may append "\0", such padding characters must be removed
        return rtrim(mdecrypt_generic($this->getHandler(), $data), "\0");
    }


    public function __destruct()
    {
        if ($this->getHandler()) {
            $this->_reset();
        }
    }

    protected function _reset()
    {
        mcrypt_generic_deinit($this->getHandler());
        mcrypt_module_close($this->getHandler());
    }
    
}
