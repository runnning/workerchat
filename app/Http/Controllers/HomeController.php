<?php

namespace App\Http\Controllers;
use App\Models\Message;
use App\Models\User;
use GatewayClient\Gateway;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');

        //设置GatewayWorker服务的Register服务ip和端口
        Gateway::$registerAddress='127.0.0.1:1238';
    }


    /**
     * 聊天首页
    */
    public function index(Request $request)
    {
        $room_id=$request->room_id?:1;
        //会话存room_id
        session(['room_id'=>$room_id]);
        return view('home');
    }

    /**
     * 初始化
     * @throws \Exception
     */
    public function init(Request $request): void
    {
        //绑定用户
        $this->bind($request);

        //在线用户
        $this->users();

        //历史记录
        $this->history();

        //进入聊天室
        $this->login();
    }

    /**
     * 绑定
    */
    private function bind(Request $request): void
    {
        $id=Auth::id();
        //客户端ID
        $client_id=$request->client_id;
        //client_id与uid绑定
        Gateway::bindUid($client_id,$id);
        //加入组
        Gateway::joinGroup($client_id,session('room_id'));
        //设置gateway session
        Gateway::setSession($client_id,[
            'id'=>$id,
            'avatar'=>Auth::user()?->gravatar(),
            'name'=>Auth::user()?->name
        ]);
    }

    /**
     * 登录
     * @throws \Exception
     */
    private function login(): void
    {
        $user=Auth::user();
        $data=[
            'type'=>'say',
            'data'=>[
                'avatar'=>$user?->gravatar(),
                'name'=>$user?->name,
                'content' => '进入了聊天室',
                'time'=>date('Y-m-d H:i:s')
            ]
        ];
        Gateway::sendToGroup(session('room_id'), json_encode($data, JSON_THROW_ON_ERROR));
    }

    /**
     * 发送消息
     * @throws \Exception
     */
    public function say(Request $request): void
    {
        $user=Auth::user();
        $content=$request->input('content');
        $data=[
            'type'=>'say',
            'data'=>[
                'avatar'=>$user?->gravatar(),
                'name'=>$user?->name,
                'content'=>$content,
                'time'=>date('Y-m-d H:i:s')
            ]
        ];

        //私聊
        if($request->user_id){
            $data['data']['name']=Auth::user()->name.'对'.User::find($request->user_id)->name.'说';
            Gateway::sendToUid($request->user_id, json_encode($data, JSON_THROW_ON_ERROR));
            Gateway::sendToUid(Auth::id(), json_encode($data, JSON_THROW_ON_ERROR));

            //私聊信息,只发给对应用户,不存数据库
            return;
        }
        //对应的room
        Gateway::sendToGroup(session('room_id'),json_encode($data, JSON_THROW_ON_ERROR));
        //存入数据库,以后方便聊天记录
        Message::create([
            'user_id' => $user?->id,
            'room_id' => session('room_id'),
            'content' => $content
        ]);
    }

    /**
     * @throws \JsonException
     */
    private function history(): void
    {
        $data=['type'=>'history'];
        $messages=Message::with('user')
            ->where('room_id',session('room_id'))
            ->orderBy('id','desc')
            ->limit(5)
            ->get();
        $data['data']=$messages->map(static function ($item){
            return [
                'avatar'=>$item->user->gravatar(),
                'name'=>$item->user->name,
                'content'=>$item->content,
                'time'=>$item->created_at->format('Y-m-d H:i:s')
            ];
        });
        //发送给指定用户
        Gateway::sendToUid(Auth::id(), json_encode($data, JSON_THROW_ON_ERROR));
    }

    /**
     * @throws \Exception
     */
    private function users(): void
    {
        $data=[
            'type'=>'users',
            'data'=>Gateway::getAllClientSessions()
        ];
        Gateway::sendToGroup(session('room_id'),json_encode($data, JSON_THROW_ON_ERROR));
    }
}
