<?php

// +----------------------------------------------------------------------
// | Think.Admin
// +----------------------------------------------------------------------
// | 版权所有 2014~2017 广州楚才信息科技有限公司 [ http://www.cuci.cc ]
// +----------------------------------------------------------------------
// | 官方网站: http://think.ctolog.com
// +----------------------------------------------------------------------
// | 开源协议 ( https://mit-license.org )
// +----------------------------------------------------------------------
// | github开源项目：https://github.com/zoujingli/Think.Admin
// +----------------------------------------------------------------------

namespace app\blog\model;

use app\common\service\AuthService;
use app\common\service\ModelService;

/**
 * 博客文章模型
 * Class Article
 * @package app\blog\model
 */
class Article extends ModelService {

    /**
     * 绑定数据表
     * @var string
     */
    protected $table = 'blog_article';

}