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
    public function encode($data) {
        $public_key = file_get_contents($this->_rsa_path . 'pb.key');
        $public_key = openssl_pkey_get_public($public_key);
        $r = openssl_public_encrypt($data, $encrypted, $public_key);
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
    public function decode($data) {
        $data = base64_decode($data);
        $private_key = file_get_contents($this->_rsa_path . 'pk.pem');
        $private_key = openssl_pkey_get_private($private_key);
        $r = openssl_private_decrypt($data, $decrypted, $private_key, OPENSSL_PKCS1_PADDING);
        if ($r) {
            return $decrypted;
        }
        return false;
    }

    /**
     * 签名
     */
    public function sign($data){
        $private_key = file_get_contents($this->_rsa_path . 'pk.pem');
        $signature   = '';
        openssl_sign($data, $signature, $private_key);
        return base64_encode($signature);
    }

    /**
     * 验证签名
     */
    public function verify($data, $signature){
        $public_key = file_get_contents($this->_rsa_path . 'pb.key');
        $r = openssl_verify($data, base64_decode($signature), $public_key);
        if ($r == 1) {
            return true;
        }
        return false;
    }
}
