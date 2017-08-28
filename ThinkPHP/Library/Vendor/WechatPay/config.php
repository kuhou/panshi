<?php
/*
 *---------------------------------------------------------------
 *  DESC
 *---------------------------------------------------------------
 *  author:  baoshu
 *  website: kuhou.net
 *  email:   83507315@qq.com
 *  date:    2017/8/28 下午12:16
 */

class we_config
{
    private $_config;
    public function __construct($config)
    {
        $this->_config['appid'] = $config['appid'];
        $this->_config['appsecret'] = $config['appsecret'];
        $this->_config['mchid'] = $config['mchid'];
        $this->_config['paysignkey'] = $config['paysignkey'];

        define('BS_APPID', $this->_config['appid']);
        define('BS_MCHID', $this->_config['mchid']);
        define('BS_KEY', $this->_config['paysignkey']);
        define('BS_APPSECRET', $this->_config['appsecret']);
    }
}