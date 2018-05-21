<?php
namespace app\common\logic;

//zwx 数据库 条件显示
class ConditionShow
{

    public function __construct(){
    }

    /** 
     * zwx
     * @param string    $a 键
     * @param string     $b 方法 $c 数组
     * @return 
     */
    public function ConditionShowDefault($a='',$b,$c=''){
        $data = $this->$b();
        if($a !== ''){
            return $data[$c][$a];
        }else if($a == '' && $c != ''){
            return $data[$c];
        }else{
            return $data;
        }
    }

    //显示配置
    public function show_set_config(){
        return [
            'current_location'=>[
                'index/index' => '首页',
                'goods/index' => '珠宝成品',
                'goods/custom' => '商品定制',
                'goods/custom_xdt' => '精品定制',
                'goods/diamond' => '钻石',
                'store/index' => '门店预约',
                'article/index' => '文章列表',
                'user/index' => '账号设置',
                'user/userorder' => '我的订单',
                'user/useraddress' => '地址管理',
                'user/userordergoodsevallist' => '评价管理',
                'user/usercollection' => '我的收藏',
                'user/usergoodsview' => '浏览记录',
            ],
        ];
    }

    public function goods(){
        return [
            'product_status'=>[
                0 => '未上架',
                1 => '已上架',
            ],
            'stock_status'=>[
                2 => '订货',
                1 => '现货',
            ],
            'type'=>[
                0 => '白钻',
                1 => '彩钻',
                2 => '散货',
                3 => '成品',
                4 => '定制',
            ],
        ];
    }

    public function goods_diamond(){
        return [
            'type'=>[
                0 => '白钻',
                1 => '彩钻',
                2 => '散货',
            ],
            'inventory_type'=>[
                1 => '现货',
                2 => '订货',
            ],
            'certificate_type'=>[
                'GIA' => 1,
                'IGI' => 2,
                'HRD' => 3
            ],
            'shape_name'=>[
                'ROUND'=>'圆形',
                'OVAL'=>'椭圆',
                'MARQUISE'=>'马眼',
                'HEART'=>'心形',
                'PEAR'=>'水滴',
                'PRINCESS'=>'方形',
                'EMERALD'=>'祖母绿',
                'CUSHION'=>'枕形',
                'RADIANT'=>'雷蒂恩',
                'BAGUETTE'=>'梯方',
                'SQUARE EMERALD'=>'方形祖母绿',
            ],
            'shape'=>[
                '圆形'=>'ROUND',
                '椭圆'=>'OVAL',
                '马眼'=>'MARQUISE',
                '心形'=>'HEART',
                '水滴'=>'PEAR',
                '方形'=>'PRINCESS',
                '祖母绿'=>'EMERALD',
                '枕形'=>'CUSHION',
                '雷蒂恩'=>'RADIANT',
                '梯方'=>'BAGUETTE',
                '方形祖母绿'=>'SQUARE EMERALD',
            ],
        ];
    }

    public function order(){
        return [
            'order_status'=>[
                0 => '待支付',
                3 => '待发货',
                4 => '待收货',
                5 => '已完成',
                7 => '已取消',
            ],
            'order_status_a'=>[
                '待支付'=>'0',
                '待发货'=>'3',
                '待收货'=>'4',
                '已完成'=>'5',
                '已取消'=>'7',
            ],
            'pay_status'=>[
                0 => '未支付',
                1 => '已支付',
            ],
            'pay_type'=>[
                1 => '线下转账',
                2 => '在线支付',
            ],
            'pay_code'=>[
                '1000' => '线下转账',
                '1001' => '支付宝',
                '1002' => '微信',
                '1003' => '网银支付', //环迅
                '1004' => '微信扫码', //环迅
                '1005' => '支付宝扫码', //环迅
            ],
            'shipping_type'=>[
                1 => '门店自取',
                2 => '第三方物流',
            ],
        ];
    }

    public function order_goods(){
        return [
            'order_type'=>[
                1 => '裸钻',
                2 => '成品',
                3 => '定制',
            ],
        ];
    }

    public function cart(){
        return [
            'cart_type'=>[
                1 => '裸钻',
                2 => '成品',
                3 => '定制',
            ],
            'goods_cart_type'=>[  //商品类型对应购物车类型
                0 => 1,
                1 => 1,
                2 => 1,
                3 => 2,
                4 => 3,
            ],
            'goods_cart_type_zpt'=>[  //商品类型对应购物车类型 钻配托
                0 => 3,
                1 => 3,
                2 => 3,
                3 => 3,
                4 => 3,
            ],
        ];
    }

    public function article_default(){
        return [
            'type'=>[
                1 => '用户指南',
                2 => '关于配送',
                3 => '售后服务',
                4 => '常见问题',
            ],
        ];
    }

    public function report_certificate(){
        return [
            'ztype'=>[
                1 => 'GIA',
                2 => 'IGI',
                3 => 'HRD',
            ],
            'ztype_a'=>[
                'GIA' => 1,
                'IGI' => 2,
                'HRD' => 3,
            ],
        ];
        
    }
}