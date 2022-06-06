<template>
    <div class="container">
        <a href="?room_id=1" class="btn btn-danger">吃货人生</a>
        <a href="?room_id=2" class="btn btn-primary">技术探讨</a>
        <hr class="divider">
        <div class="row">
            <div class="col-md-8">
                <div class="panel panel-default">
                    <div class="panel-heading">聊天室</div>
                    <div class="panel-body">

                        <div class="messages">
                            <div class="media" v-for="message in messages">
                                <div class="media-left">
                                    <a href="#">
                                        <img class="media-object img-circle" :src="message.avatar" alt="头像">
                                    </a>
                                </div>
                                <div class="media-body">
                                    <p class="time">{{message.time}}</p>
                                    <h4 class="media-heading">{{message.name}}</h4>
                                    {{message.content}}
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="panel panel-default">
                    <div class="panel-heading">在线用户</div>

                    <div class="panel-body">
                        <ul class="list-group">
                            <li class="list-group-item" v-for="user in users">
                                <img :src="user.avatar"
                                     class="img-circle" width="50">
                                {{user.name}}
                            </li>
                        </ul>
                    </div>

                </div>
            </div>
        </div>

        <form @submit.prevent="onsubmit">
            <div class="form-group">
                <label for="user_id">私聊</label>

                <select class="form-control" id="user_id" v-model="user_id">
                    <option>所有人</option>
                    <option :value="user.id" v-for="user in users">{{user.name}}</option>
                </select>
            </div>

            <div class="form-group">
                <label for="content">内容</label>
                <textarea class="form-control" rows="3" id="content" v-model="content"></textarea>
            </div>

            <button type="submit" class="btn btn-default">提交</button>
        </form>
    </div>
</template>

<script>
let ws = new WebSocket('ws://192.168.229.131:8282')
export default {
    data() {
        return {
            messages: [],
            users:[],
            content:'',
            user_id:'',
        }
    },
    created() {
        ws.onmessage = (e) => {
            //字符串转json
            let data = JSON.parse(e.data);

            //如果没有类型,就为空
            let type = data.type || '';

            switch (type) {
                //心跳检测
                case "ping":
                    ws.send('pong')
                    break
                //初始化
                case 'init':
                    axios({
                        method: 'post',
                        url: '/init',
                        data: {
                            client_id: data.client_id
                        }
                    });
                    break;
                 //发送消息
                case 'say':
                    this.messages.push(data.data);
                    break;
                //在线用户
                case 'users':
                    this.users=data.data;
                    break;
                //聊天记录
                case 'history':
                    this.messages=data.data;
                    break;
                //退出
                case 'logout':
                    this.$delete(this.users,data.client_id)
                    break;
                default:
                    console.log(data)
            }
        }
    },
    methods: {
        onsubmit:function () {
            axios({
                method: 'post',
                url: '/say',
                data: {
                    content:this.content,
                    user_id:this.user_id,
                }
            });
            this.content='';
        }
    }
}
</script>
