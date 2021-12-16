## 部署

1. 安装composer依赖，上传源码及导入数据库。保证目录`data`、`public/uploadfile`拥有写权限
1. 设置网站根目录到`public`
1. 进入`web/config/`目录，将`database.example.php`重命名为`database.php`，并修改文件中数据库连接信息
1. 登入后台，进入`系统 > 网站设置`，配置名称域名等信息
1. 配置完成后，在后台更新缓存

## 本地运行
本地需部署PHP环境及相关依赖，部署好项目源码，在项目目录中运行以下命令进行本地调试：
```bash
php -S [host]:[port] -t public server.php
```
其中`[host]:[port]`替换为相应参数，如 `127.0.0.1:8000`

## 更新说明
### 更新摘要
- 引入composer
- 添加server.php用于本地调试
- 升级Pay模块
- 引入Wechat模块并实现公众号授权登录等功能

### 变更详情
- Pay
  - 控制器中原`payment`方法变更为`initiate`

- Wechat
  - 使用前需将`web/config`目录下的`wechat.example.php`重命名为`wechat.php`，并完善文件中的配置项
  - 如需授权登录，仅需在微信客户端中访问`/?c=wechat&m=oAuth`即可。可以拼入`redirect`参数指定回调跳转的地址，默认跳转到首页
  - 静默授权地址为`/?c=wechat&m=subscribeAuthorize`，授权过程用户无感知，但需要用户关注公众号
  - 如需调用微信公众平台的其它接口(素材、模板消息等)，在控制器中通过`$this->wechat`即可得到WeChatSDK的实例。如果不是`System/Wechat`中的控制器，需先引入`WeChatInstance.php`文件，并在class内部添加`use WeChatInstance;`