<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2018 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 小夏 < 449134904@qq.com>
// +----------------------------------------------------------------------
namespace app\sms\validate;

use think\Validate;

class GroupSendMessageValidate extends Validate
{
    protected $rule = [
        // 用|分开
        'from'    => 'require',
        'message' => 'require'
    ];

    protected $message = [
        'from.require'    => "The from number is required!",
        'message.require' => 'The message is required!'
    ];


}