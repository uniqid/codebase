<?php
/*************************************************

Codebase - The PHP toolkit
Author: Jacky Yu <jacky325@qq.com>
Copyright (c): 2012-2015 Jacky Yu, All rights reserved
Version: 1.0.0

* This library is free software; you can redistribute it and/or modify it.
* You may contact the author of Codebase by e-mail at: jacky325@qq.com

The latest version of Codebase can be obtained from:
https://github.com/uniqid/codebase

*************************************************/

if(!defined('IN_CODEBASE')) {
	exit('Access Denied');
}

class Rsa {
    private $_rsa_path = '';

    public function __construct($rsa_path = '') {
        if($rsa_path === ''){
            $this->_rsa_path = dirname(dirname(__FILE__)).'/cfg/';
        } else {
            $this->_rsa_path = $rsa_path;
        }
    }

    /**
     * generate pb.key
     * openssl rsa -in pk.pem -pubout -out pb.key
     */
    public function encode($str) {
        $publicstr = file_get_contents($this->_rsa_path . 'pb.key');
        $publickey = openssl_pkey_get_public($publicstr);
        $r = openssl_public_encrypt($str, $encrypted, $publickey);
        if ($r) {
            return base64_encode($encrypted);
        }
        return false;
    }

    /**
     * generate pk.pem
     * 1. openssl genrsa -des3 -out pk.pem 2048
     * 2. openssl rsa -in pk.pem -out pk.pem
     */
    public function decode($str) {
        $str = base64_decode($str);
        $privstr = file_get_contents($this->_rsa_path . 'pk.pem');
        $privkey = openssl_pkey_get_private($privstr);
        $r = openssl_private_decrypt($str, $decrypted, $privkey, OPENSSL_PKCS1_PADDING);
        if ($r) {
            return $decrypted;
        }
        return false;
    }
}
