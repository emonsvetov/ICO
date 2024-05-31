<?php
namespace App\Models\Traits;

use App\Models\Traits\GetModelByMixed;

trait Filterable
{
    use GetModelByMixed;
    
    static $DEFAULT_PARAMS = [
        'status' => '',
        'keyword' => '',
        'sortby' => 'id',
        'direction' => 'asc',
        'minimal' => false, // will return id and name with full list
        'paginate' => true,
        'tree' => true, //whether to return data in tree format
        'flatlist' => false, //whether to return data in tree format
        'except' => [], //array of primary keys
    ];

    static $PARAMS = [];
    static $query = null;

    private function _makeParams( $override = [] ) {
        // pr($override);
        $params = [];
        $status = !empty($override['status']) ? $override['status'] : request()->get('status', '');
        $keyword = !empty($override['keyword']) ? $override['keyword'] : request()->get('keyword', '');
        $sortby = !empty($override['sortby']) ? $override['sortby'] : request()->get('sortby', 'id');
        $direction = !empty($override['direction']) ? $override['direction'] : request()->get('direction', 'asc');
        $tree = isset($override['tree']) ? $override['tree'] : request()->get('tree', true);
        $minimal = !empty($override['minimal']) ? $override['minimal'] : request()->get('minimal', false);
        $flatlist = !empty($override['flatlist']) ? $override['flatlist'] : request()->get('flatlist', false);
        $except = !empty($override['except']) ? $override['except'] : request()->get('except', '');
        $limit = !empty($override['limit']) ? $override['limit'] : request()->get('limit', 10);
        $paginate = !empty($override['paginate']) ? $override['paginate'] : request()->get('paginate', true);
        $programs = !empty($override['programs']) ? $override['programs'] : request()->get('programs', []);
        $params['status'] = $status;
        $params['keyword'] = $keyword;
        $params['sortby'] = $sortby;
        $params['direction'] = $direction;
        $params['tree'] = $tree;
        $params['minimal'] = $minimal;
        $params['limit'] = $limit;
        $params['paginate'] = $paginate;
        $params['flatlist'] = $flatlist;
        $params['except'] = $except;
        $params['programs'] = !is_null($programs) && !empty($programs) ? explode(",", $programs) : [];
        // pr($params);
        self::$PARAMS = array_merge(self::$DEFAULT_PARAMS, $params);
        return self::$PARAMS;
    }

    public function filterable($model, $return = true)  {
        if( !self::$query && $model ) {
            self::$query = $model::query();
        }
        self::_makeParams();
        if( method_exists(__CLASS__, 'applyFilters') )  {
            self::applyFilters();
        }
        if( $return ) {
            return self::$query;
        }
    }
}