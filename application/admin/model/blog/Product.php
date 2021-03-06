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

namespace app\admin\model\blog;


use app\common\service\ModelService;

/**
 * 产品模型数据
 * Class Product
 * @package app\admin_blog\model
 */
class Product extends ModelService {

    /**
     * 绑定数据表
     * @var string
     */
    protected $table = 'blog_product';

    /**
     * 关联会员表
     * @return \think\model\relation\HasOne
     */
    public function memberInfo() {
        return $this->hasOne("Member", "id", "member_id")->joinType('left');
    }

    /**
     * 关联产品类型
     * @return \think\model\relation\HasOne
     */
    public function categoryInfo() {
        // hasone('model名称','id','category_id')
        return $this->hasOne("proCategory", "id", "category_id")->joinType('left');
    }

    /**
     * 新增产品
     * @param $insert
     * @return \think\response\Json
     * @throws \think\exception\PDOException
     */
    public static function add($post) {
        self::startTrans();
        try {
            $insert = $post;
            unset($insert['tag_list']);
            $product_id = self::insertGetId($insert);
            self::__buildAddTag($product_id, $post);
            self::commit();
        } catch (\Exception $e) {
            self::rollback();
            return __error($e->getMessage());
        }
        return __success('产品添加成功！');
    }

    /**
     * 修改文章
     * @param $update
     * @return \think\response\Json
     * @throws \think\exception\PDOException
     */
    public static function edit($update) {
        $update_article = $update;
        unset($update_article['product_id']);
        unset($update_article['tag_list']);
        self::where(['id' => $update['product_id']])->update($update_article);
        \app\admin\model\blog\ProductTag::where(['product_id' => $update['product_id']])->delete();
        self::__buildAddTag($update['product_id'], $update);
        return __success('产品修改成功！');
    }

    /**
     * 前置数据
     * @param $data
     */
    protected static function __buildAddTag($product_id, $insert) {
        if (isset($insert['tag_list']) && !empty($insert['tag_list']) && !empty($product_id)) {
            list($tag_list, $save_all) = [explode(',', $insert['tag_list']), []];
            foreach ($tag_list as $vo) {
                $tag_id = \app\admin\model\blog\ProTag::where(['tag_title' => $vo])->value('id');
                if (empty($tag_id)) {
                    $tag_id = \app\admin\model\blog\ProTag::insertGetId(['tag_title' => $vo]);
                }
                $save_all[] = [
                    'product_id' => $product_id,
                    'tag_id'     => $tag_id,
                ];
            }
            \app\admin\model\blog\ProductTag::insertAll($save_all);
        }
    }

    /**
     * 删除
     * @param $id
     * @return \think\response\Json
     * @throws \think\exception\PDOException
     */
    public static function del($id) {
        is_array($id) ? $del = self::whereIn('id', $id) : $del = self::where('id', $id);
        self::startTrans();
        try {
            $del = $del->delete($id);
            self::commit();
        } catch (\Exception $e) {
            self::rollback();
            return __error($e->getMessage());
        }
        return __success('删除成功');
    }

    /**
     * 获取文章列表数据
     * @param int   $page
     * @param int   $limit
     * @param array $search
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function getList($page = 1, $limit = 10, $search = []) {
        $where = [];
        //搜索条件
        foreach ($search as $key => $value) {
            if (!empty($value)) {                
                switch ($key) {
                    case 'username':
                        $member_id = model('member')->where([['username', 'LIKE', "%{$value}%"]])->column('id');
                        $where[] = ['member_id', 'IN', $member_id];
                        break;
                    case 'nickname':
                        $member_id = model('member')->where([['nickname', 'LIKE', "%{$value}%"]])->column('id');
                        $where[] = ['member_id', 'IN', $member_id];
                        break;
                    case 'create_at':
                        $value_list = explode(" - ", $value);
                        $where[] = [$key, 'BETWEEN', ["{$value_list[0]} 00:00:00", "{$value_list[1]} 23:59:59"]];
                        break;
                    default:
                        !empty($value) && $where[] = [$key, 'LIKE', '%' . $value . '%'];
                }
            }

        }
        $count = self::where($where)->count();//float类型

        $order = ['sort' => 'asc'];
        $data = self::where($where)->field('id,category_id,member_id,title,cover_img,describe,recommend,praise,clicks,sort,remark,status,is_open,create_at')
            ->page($page, $limit)->order($order)->select()
            ->each(function ($item, $key) {
                $memberInfo = $item->memberInfo;
                $categoryInfo = $item->categoryInfo;
                if ($item['member_id'] == 0) {
                    $item['username'] = 'admin';
                    $item['nickname'] = '管理员';
                    $item['head_img'] = '/static/image/blog/face_default.jpg';
                } else {
                    $item['username'] = $memberInfo['username'];
                    $item['nickname'] = $memberInfo['nickname'];
                    $item['head_img'] = $memberInfo['head_img'];
                }
                $item['category_title'] = $categoryInfo['title'];

                // $item['commont_total'] = model('blog.commont')->where(['product_id' => $item['id']])->count();
            });
        empty($data) ? $msg = '暂无数据！' : $msg = '查询成功！';
        $info = [
            'limit'        => $limit,
            'page_current' => $page,
            'page_sum'     => ceil($count / $limit),
        ];
        $list = [
            'code'  => 0,
            'msg'   => $msg,
            'count' => $count,
            'info'  => $info,
            'data'  => $data,
        ];
        return $list;
    }

    /**
     * 统计分类文章
     * @param $category_id
     * @return float|string
     */
    public static function articleCount($category_id) {
        return self::where(['category_id' => $category_id, 'status' => 0, 'is_deleted' => 0])->count('id');
    }

}
