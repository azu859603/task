<?php


namespace common\helpers;

use yii\captcha\CaptchaAction;

class CaptchaBuilder extends CaptchaAction
{
    private $verifycode;
    private $base64;

    public function __construct()
    {
        $this->init();
        // 更多api请访问yii\captcha\CaptchaAction类文档
        // 这里可以初始化默认样式
        $this->maxLength = 4;            // 最大显示个数
        $this->minLength = 4;            // 最少显示个数
//        $this->backColor = 0x000000;     // 背景颜色
//        $this->foreColor = 0x00ff00;     // 字体颜色
        $this->width = 80;               // 宽度
        $this->height = 45;              // 高度


        //$this->imageLibrary = "gd";//or $this->imageLibrary = "imagick";
    }

    /**
     * @return string verfiycode image base64
     */
    public function base64()
    {
        if ($this->base64) {
            return $this->base64;
        } else {
            return $this->base64 = "data:image/png;base64," . base64_encode($this->renderImage($this->getPhrase()));
        }
    }

    /**
     * @return string new generated verifycode
     */
    public function getNewPhrase()
    {
        $this->base64 = null;
        return $this->verifycode = $this->generateVerifyCode();
    }

    /**
     * @return string generated verifycode
     */
    public function getPhrase()
    {
        if ($this->verifycode) {
            return $this->verifycode;
        } else {
            return $this->verifycode = $this->generateVerifyCode();
        }
    }
}