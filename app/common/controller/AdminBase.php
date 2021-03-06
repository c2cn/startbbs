<?php
namespace app\common\controller;

use org\Auth;
use think\Loader;
use Cache;
use think\Controller;
use think\Db;
use Session;
use Env;
/**
 * 后台公用基础控制器
 * Class AdminBase
 * @package app\common\controller
 */
class AdminBase extends Controller
{
    protected function initialize()
    {
        parent::initialize();

        $this->checkAuth();
        $auth     = new Auth();
         //var_dump($auth->getGroups(1));
         //var_dump($auth->getAuthList(1, 1));
        // var_dump($auth->getUserInfo(1));

        $this->getMenu();
        define('CACHE_PATH',Env::get('runtime_path') . 'cache/');
        define('TEMP_PATH',Env::get('runtime_path'). 'temp/');

        // 输出当前请求控制器（配合后台侧边菜单选中状态）
        $this->assign('controller', Loader::parseName($this->request->controller()));

		//输出$root
        //$baseUrl = str_replace('\\','/',dirname($_SERVER['SCRIPT_NAME'])).'/';
    	$root='http://'.$_SERVER['HTTP_HOST'];
    	$this->assign('root',$root);
    }

    /**
     * 权限检查
     * @return bool
     */
    protected function checkAuth()
    {

        if (Session::get('group_id')!=1) {
            $this->error('管理员未登录或权限不足','admin/login/index');
        }

        $module     = $this->request->module();
        $controller = $this->request->controller();
        $action     = $this->request->action();

        // 排除权限
        $not_check = ['admin/Index/index', 'admin/AuthGroup/getjson', 'admin/System/clear'];

        if (!in_array($module . '/' . $controller . '/' . $action, $not_check)) {
            $auth     = new Auth();
            $admin_id = Session::get('user_id');

            if (!$auth->check($module . '/' . $controller . '/' . $action, $admin_id) && $admin_id != 1) {
                $this->error('没有权限');
            }
        }
    }

    /**
     * 获取侧边栏菜单
     */
    protected function getMenu()
    {
        $menu     = [];
        $admin_id = Session::get('user_id');
        $auth     = new Auth();

        $auth_rule_list = Db::name('auth_rule')->where('status', 1)->order(['sort' => 'DESC', 'id' => 'ASC'])->select();
        //var_export($auth_rule_list);

        foreach ($auth_rule_list as $value) {
            if ($auth->check($value['name'], $admin_id) || $admin_id == 1) {
                $menu[] = $value;
            }
        }
        $menu = !empty($menu) ? array2tree($menu) : [];

        $this->assign('menu', $menu);
    }
}