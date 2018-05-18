<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2017 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 老猫 <catman@thinkcmf.com>
// +----------------------------------------------------------------------

namespace app\sms\model;

use think\Model;

class SmsSessionModel extends Model
{
    // 是否需要自动写入时间戳 如果设置为字符串 则表示时间字段的类型
    protected $autoWriteTimestamp = true;

    public function lastMessage()
    {
        return $this->hasOne('SmsMessageModel','session_id')->order('sent_time DESC');
    }

}