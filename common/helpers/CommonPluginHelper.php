<?php
// +----------------------------------------------------------------------------------------
// | 原创项目
// +----------------------------------------------------------------------------------------
// | 版权所有 原创脉冲工作室
// +----------------------------------------------------------------------------------------
// |  联系方式：
// |  QQ：123546
// |  skype：123546
// |  Telegram：@123546
// +----------------------------------------------------------------------------------------
// | 开发团队:原创脉冲
// +----------------------------------------------------------------------------------------

namespace common\helpers;


/**
 * 开发常用方法
 * Class CommonPluginHelper
 * @package common\helpers
 * @author "原创脉冲"
 */
class CommonPluginHelper
{
    /**
     * CURL get表单提交
     * @param $url
     * @param $headers
     * @return mixed
     * @author 原创脉冲
     */
    public static function curl_get($url, $headers = "")
    {
        $curl = curl_init();
        if (!empty($headers)) {
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, 500);
        // 为保证第三方服务器与微信服务器之间数据传输的安全性，所有微信接口采用https方式调用，必须使用下面2行代码打开ssl安全校验。
        // 如果在部署过程中代码在此处验证失败，请到 http://curl.haxx.se/ca/cacert.pem 下载新的证书判别文件。
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); //绕过ssl验证
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_URL, $url);
        $res = curl_exec($curl);
        curl_close($curl);
//        $data = json_decode($res, true);
//        return $data;
        return $res;
    }


    /**
     * CURL form表单提交
     * @param $url
     * @param array $data
     * @param array $header
     * @return array|mixed|string
     * @author "原创脉冲"
     */
    public static function curl_post($url, $data = [], $header = [])
    {
        $data = http_build_query($data);
        $curl = curl_init(); // 启动一个CURL会话
        curl_setopt($curl, CURLOPT_URL, $url); // 要访问的地址
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0); // 对认证证书来源的检查
        //curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 1); // 从证书中检查SSL加密算法是否存在
        curl_setopt($curl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']); // 模拟用户使用的浏览器
        curl_setopt($curl, CURLOPT_HTTPHEADER, !empty($header) ? $header : array('Content-Type: application/x-www-form-urlencoded'));
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1); // 使用自动跳转
        curl_setopt($curl, CURLOPT_AUTOREFERER, 1); // 自动设置Referer
        curl_setopt($curl, CURLOPT_POST, 1); // 发送一个常规的Post请求
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data); // Post提交的数据包
        curl_setopt($curl, CURLOPT_TIMEOUT, 30); // 设置超时限制防止死循环
        curl_setopt($curl, CURLOPT_HEADER, 0); // 显示返回的Header区域内容
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); // 获取的信息以文件流的形式返回
        $tmpInfo = curl_exec($curl); // 执行操作
        if (curl_errno($curl)) {
            return 'Errno:' . curl_error($curl);
        }
        curl_close($curl); // 关键CURL会话
//        $data = json_decode($tmpInfo, true);
        return $tmpInfo;
    }


    /**
     * CURL json格式提交
     * @param $url
     * @param array $data
     * @param array $header
     * @return array|mixed|string
     * @author "原创脉冲"
     */
    public static function curl_json($url, $data = [], $header = [])
    {
        $data_string = json_encode($data);
        $curl = curl_init(); // 启动一个CURL会话
        curl_setopt($curl, CURLOPT_URL, $url); // 要访问的地址
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0); // 对认证证书来源的检查
        //curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 1); // 从证书中检查SSL加密算法是否存在
        curl_setopt($curl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']); // 模拟用户使用的浏览器
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1); // 使用自动跳转
        curl_setopt($curl, CURLOPT_AUTOREFERER, 1); // 自动设置Referer
        curl_setopt($curl, CURLOPT_POST, 1); // 发送一个常规的Post请求
        curl_setopt($curl, CURLOPT_TIMEOUT, 30); // 设置超时限制防止死循环
        curl_setopt($curl, CURLOPT_HEADER, 0); // 显示返回的Header区域内容
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, !empty($header) ? $header : array('Content-Type: application/json', 'Content-Length: ' . strlen($data_string)));
        $tmpInfo = curl_exec($curl); // 执行操作
        if (curl_errno($curl)) {
            return 'Errno' . curl_error($curl);
        }
        curl_close($curl); // 关键CURL会话
        $data = json_decode($tmpInfo, true);
        return $data;
    }

    /**
     * 常用计算签名方法
     * @param $token "秘钥"
     * @param array $params "需要排序的数组"
     * @return string
     */
    public static function signature($token, $params = [])
    {
        //var_dump($params);exit;
        ksort($params); //参数数组按键升序排列
        $clear_text = '';    //将参数值按顺序拼接成字符串
        foreach ($params as $key => $value) {
            $clear_text .= $key . '=' . $value . '&';
        }
        $clear_text .= "key=$token";
        $clear_text = trim($clear_text, '&');
        $cipher_text = md5($clear_text); //计算md5 hash
        return strtoupper($cipher_text);
    }

    /**
     * form 提交
     * @param $gateway "提交地址"
     * @param array $params "内容"
     */
    public static function echo_form($gateway, $params = [])
    {
        $html = '<html>
            <head>
                <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
                <title>FORM提交</title>
            </head>
            <body>';
        $html .= '<form action="' . $gateway . '" method="post" id="frmSubmit">';
        foreach ($params as $k1 => $v1) {
            $html .= '<input type="hidden" name="' . $k1 . '" value="' . $v1 . '" />';
        }
        $html .= '</form>
                <script type="text/javascript">
                    document.getElementById("frmSubmit").submit();
                </script>
            </body>
        </html>';
        echo "$html";;
        die;
    }

    /**
     * 创建订单号
     * @param $user_id 6位
     * @return string
     */
    public static function getSn($user_id)
    {
        $user_id = self::confusionUserID($user_id);
        $time = substr(time(), 1);  //取出九位时间戳
        $sn = $time . $user_id; //  拼接时间戳与用户ID
        $need_pad = 19 - strlen($sn);   //  还需要多少位数字补位
        $min = pow(10, $need_pad);
        $max = pow(10, $need_pad + 1) - 1;
        return str_pad($sn, 20, rand($min, $max));
    }

    public static function confusionUserID($user_id)
    {
        $id_list = str_split($user_id);
        for ($i = 0; $i < count($id_list); $i++) {
            switch ($id_list[$i]) {
                case 1:
                    $id_list[$i] = 9;
                    break;
                case 2:
                    $id_list[$i] = 5;
                    break;
                case 3:
                    $id_list[$i] = 4;
                    break;
                case 4:
                    $id_list[$i] = 3;
                    break;
                case 5:
                    $id_list[$i] = 2;
                    break;
                case 6:
                    $id_list[$i] = 7;
                    break;
                case 7:
                    $id_list[$i] = 6;
                    break;
                case 9:
                    $id_list[$i] = 1;
                    break;
                default:
                    break;
            }
        }
        return implode($id_list);
    }

    /**
     * 获取真实IP
     */
    public static function getIP()
    {
        return isset($_SERVER["HTTP_X_FORWARDED_FOR"]) ? explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0]
            : (isset($_SERVER["HTTP_CLIENT_IP"]) ? $_SERVER["HTTP_CLIENT_IP"] : $_SERVER["REMOTE_ADDR"]);
    }

    /**
     * 获取毫秒时间戳
     */
    public static function msectime()
    {
        list($msec, $sec) = explode(' ', microtime());
        $msectime = (float)sprintf('%.0f', (floatval($msec) + floatval($sec)) * 1000);
        return $msectime;
    }

    /**
     * 网页判断当前设备1：安卓；2：IOS；3：微信端；4：PC
     * @return int
     */
    public static function isDevice()
    {
        $agent = strtolower($_SERVER['HTTP_USER_AGENT']);
        if (strpos($agent, 'micromessenger') !== false) {
            return 3;
        } elseif (strpos($agent, 'iphone') || strpos($agent, 'ipad')) {
            return 2;
        } elseif (strpos($agent, 'android') || strpos($agent, 'okhttp') === 0) {
            return 1;
        } else {
            return 4;
        }
    }

    /**
     * 判断是否是手机登录
     * @return bool
     *
     */
    public static function isMobile()
    {
        $ua = strtolower($_SERVER['HTTP_USER_AGENT']);
        $uachar = "/(nokia|sony|ericsson|mot|samsung|sgh|lg|philips|panasonic|alcatel|lenovo|cldc|midp|mobile)/i";
        if (($ua == '' || preg_match($uachar, $ua)) && !strpos(strtolower($_SERVER['REQUEST_URI']), 'wap')) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 获取不为空的数组
     * @param $params
     * @return array
     * @author 原创脉冲
     */
    public static function notNullArray($params)
    {
        foreach ($params as $k => $v) {
            if ($v == "" || $v == null || $v == []) {
                unset($params[$k]);
            }
        }
        return $params;
    }

    /**
     * 转换公钥
     * @param $public_key_str
     * @return string
     * @author 原创脉冲
     */
    public static function getPublicKey($public_key_str)
    {
        $public_key = "-----BEGIN PUBLIC KEY-----\r\n";
        foreach (str_split($public_key_str, 64) as $str) {
            $public_key = $public_key . $str . "\r\n";
        }
        $public_key = $public_key . "-----END PUBLIC KEY-----";
        return $public_key;
    }

    /**
     * 转换私钥
     * @param $private_key_str
     * @return string
     * @author 原创脉冲
     */
    public static function getPrivateKey($private_key_str)
    {
        $private_key = "-----BEGIN RSA PRIVATE KEY-----\r\n";
        foreach (str_split($private_key_str, 64) as $str) {
            $private_key = $private_key . $str . "\r\n";
        }
        $private_key = $private_key . "-----END RSA PRIVATE KEY-----";
        return $private_key;
    }

    /**
     * 随机生成汉字
     * @param $num
     * @return string
     * @author 原创脉冲
     */
    public static function getChar($num)  // $num为生成汉字的数量
    {
        $b = '';
        for ($i = 0; $i < $num; $i++) {
            // 使用chr()函数拼接双字节汉字，前一个chr()为高位字节，后一个为低位字节
            $a = chr(mt_rand(0xB0, 0xD0)) . chr(mt_rand(0xA1, 0xF0));
            // 转码
            $b .= iconv('GB2312', 'UTF-8', $a);
        }
        return $b;
    }
}