<?php

namespace backend\widgets\jstree;

use yii\helpers\Json;
use yii\widgets\InputWidget;
use common\helpers\ArrayHelper;
use backend\widgets\jstree\assets\AppAsset;

/**
 * Class JsTree
 *
 * ```php
 *
 * $defaultData = [
 *    [
 *      'id' => 1,
 *      'pid' => 0,
 *      'title' => '测试1',
 *    ],
 *    [
 *      'id' => 2,
 *      'pid' => 0,
 *      'title' => '测试2',
 *    ],
 *    [
 *      'id' => 3,
 *      'pid' => 0,
 *      'title' => '测试3',
 *    ],
 *    [
 *      'id' => 4,
 *      'pid' => 1,
 *      'title' => '测试4',
 *    ],
 * ];
 *
 * $selectIds = [1, 2];
 *
 * ```
 *
 * @package backend\widgets\jstree
 * @author 原创脉冲
 */
class JsTree extends InputWidget
{
    /**
     * ID
     *
     * @var
     */
    public $name;
    /**
     * @var string
     */
    public $theme = 'default';
    /**
     * 默认数据
     *
     * @var array
     */
    public $defaultData = [];
    /**
     *  是否自动展开节点
     *
     * @var bool
     */
    public $autoOpen = false;
    /**
     *  ajax请求url
     *
     * @var string
     */
    public $url = '';
    public $cid = 0;
    /**
     * 选择的ID
     *
     * @var array
     */
    public $selectIds = [];
    /**
     * 过滤掉的ID
     *
     * @var array
     */
    protected $filtrationId = [];

    /**
     * @var bool
     */
    public $equal = false;

    /**
     * @return string
     */
    public function run()
    {
        $this->registerClientScript();

        $defaultData = $this->defaultData;
        $selectIds = $this->selectIds;

        // 获取下级没有全部选择的ID
        $this->filtration(self::itemsMerge($defaultData));
        $filtrationIds = [];
        foreach ($this->filtrationId as $filtrationId) {
           $filtrationIds = array_merge($filtrationIds, array_column(ArrayHelper::getParents($defaultData, $filtrationId), 'id'));
        }

        $jsTreeData = [];
        foreach ($defaultData as $datum) {
            $data = [
                'id' => $datum['id'],
                'parent' => !empty($datum['pid']) ? $datum['pid'] : '#',
                'text' => $datum['title'],
                // 'icon' => 'none'
            ];

            $jsTreeData[] = $data;
        }

        // 过滤选择的ID
        foreach ($selectIds as $key => $selectId) {
            if (in_array($selectId, $filtrationIds)) {
                unset($selectIds[$key]);
            }
        }

        return $this->render($this->theme, [
            'name' => $this->name,
            'selectIds' => Json::encode(array_values($selectIds)),
            'defaultData' => Json::encode($jsTreeData),
            'autoOpen' => $this->autoOpen,
            'url' => $this->url,
            'cid' => $this->cid
        ]);
    }

    /**
     * 过滤
     *
     * @param $data
     */
    public function filtration($data)
    {
        foreach ($data as $datum) {
            if (!empty($datum['-'])) {
                $this->filtration($datum['-']);

                if (in_array($datum['id'], $this->selectIds)) {
                    $ids = array_column($datum['-'], 'id');
                    $selectChildIds = array_intersect($this->selectIds, $ids);

                    if (count($selectChildIds) != count($ids)) {
                        $this->filtrationId[] = $datum['id'];
                    }
                }
            }
        }
    }

    /**
     * 递归
     *
     * @param array $items
     * @param int $pid
     * @param string $idField
     * @param string $pidField
     * @param string $child
     * @return array
     */
    protected static function itemsMerge(array $items, $pid = 0)
    {
        $arr = [];
        foreach ($items as $v) {
            if (is_numeric($pid)) {
                if ($v['pid'] == $pid) {
                    $v['-'] = self::itemsMerge($items, $v['id']);
                    $arr[] = $v;
                }
            }
        }

        return $arr;
    }

    /**
     * 注册资源
     */
    protected function registerClientScript()
    {
        $view = $this->getView();
        AppAsset::register($view);
    }
}