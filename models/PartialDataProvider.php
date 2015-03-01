<?php
/**
 * Created by PhpStorm.
 * User: Insolita
 * Date: 01.03.15
 * Time: 12:29
 */

namespace insolita\redisman\models;


use yii\data\BaseDataProvider;
use yii\helpers\ArrayHelper;

class PartialDataProvider extends BaseDataProvider{
    /**
     * @var string|callable the column that is used as the key of the data models.
     * This can be either a column name, or a callable that returns the key value of a given data model.
     * If this is not set, the index of the [[models]] array will be used.
     * @see getKeys()
     */
    public $key;

    /**
     * @var array the data that is not paginated or sorted. When pagination is enabled,
     * this property usually contains more elements than [[models]].
     * The array elements must use zero-based integer keys.
     */
    public $allModels;

    public $totalCount;


    /**
     * @inheritdoc
     */
    protected function prepareModels()
    {
        $this->setTotalCount($this->totalCount);
        if (($models = $this->allModels) === null) {
            return [];
        }

        if (($sort = $this->getSort()) !== false) {
            $models = $this->sortModels($models, $sort);
        }

        if (($pagination = $this->getPagination()) !== false) {
            $pagination->totalCount = $this->getTotalCount();

            if ($pagination->getPageSize() > 0) {
                $models = array_slice($models, $pagination->getOffset(), $pagination->getLimit());
            }
        }

        return $models;
    }

    /**
     * @inheritdoc
     */
    protected function prepareKeys($models)
    {
        if ($this->key !== null) {
            $keys = [];
            foreach ($models as $model) {
                if (is_string($this->key)) {
                    $keys[] = $model[$this->key];
                } else {
                    $keys[] = call_user_func($this->key, $model);
                }
            }

            return $keys;
        } else {
            return array_keys($models);
        }
    }

    /**
     * @inheritdoc
     */
    protected function prepareTotalCount()
    {
        return count($this->allModels);
    }

    /**
     * Sorts the data models according to the given sort definition
     * @param array $models the models to be sorted
     * @param Sort $sort the sort definition
     * @return array the sorted data models
     */
    protected function sortModels($models, $sort)
    {
        $orders = $sort->getOrders();
        if (!empty($orders)) {
            ArrayHelper::multisort($models, array_keys($orders), array_values($orders));
        }

        return $models;
    }
} 