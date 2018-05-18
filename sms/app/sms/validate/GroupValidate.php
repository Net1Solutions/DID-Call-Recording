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

class GroupValidate extends Validate
{
    protected $rule = [
        // 用|分开
        'name' => 'require',
    ];

    protected $message = [
        'name.require' => "The group name is required!",
    ];


}