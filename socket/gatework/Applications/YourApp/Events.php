<?php
/**
 * This file is part of workerman.
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the MIT-LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @author walkor<walkor@workerman.net>
 * @copyright walkor<walkor@workerman.net>
 * @link http://www.workerman.net/
 * @license http://www.opensource.org/licenses/mit-license.php MIT License
 */

/**
 * 用于检测业务代码死循环或者长时间阻塞等问题
 * 如果发现业务卡死，可以将下面declare打开（去掉//注释），并执行php start.php reload
 * 然后观察一段时间workerman.log看是否有process_timeout异常
 */

//declare(ticks=1);

use \GatewayWorker\Lib\Gateway;

/**
 * 主逻辑
 * 主要是处理 onConnect onMessage onClose 三个方法
 * onConnect 和 onClose 如果不需要可以不用实现并删除
 */
class Events
{
    /**
     * 当有客户端连接时，将client_id返回，让mvc框架判断当前uid并执行绑定
     * @throws JsonException
     */
    public static function onConnect($client_id): void
    {
        Gateway::sendToClient($client_id, json_encode([
            'type' => 'init',
            'client_id' => $client_id
        ], JSON_THROW_ON_ERROR));
    }


    public static function onMessage($client_id, $message)
    {

    }

    /**
     * 当用户断开连接时触发
     *
     * @throws Exception
     */
    public static function onClose($client_id): void
    {
        // 向所有人发送
        Gateway::sendToAll(json_encode([
            'type' => 'logout',
            'client_id' => $client_id
        ], JSON_THROW_ON_ERROR));
    }
}
