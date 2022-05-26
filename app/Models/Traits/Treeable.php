<?php
namespace App\Models\Traits;

use DB;

trait Treeable
{
    function getRoot( $model )  {
        if( is_numeric($model) )    {
            $model = self::find($model);
        }
        if( !$model->exists() ) return ['errors' => 'Invalid request'];
        $sql = sprintf("SELECT *
        FROM (
        SELECT @id AS _id, (SELECT @id := parent_id FROM `%s` WHERE id = _id)
        FROM (SELECT @id := %d) tmp1
        JOIN `%s` ON @id IS NOT NULL ) tmp2
        JOIN `%s` f ON tmp2._id = f.id
        WHERE f.parent_id IS NULL", $model->table, $model->id, $model->table, $model->table);
        $results = DB::select(DB::raw($sql), [
        ]);
        if( count($results) > 0)    {
            $row = current($results);
            unset($row->{'_id'});
            unset($row->{'(SELECT @id := parent_id FROM `merchants` WHERE id = _id)'});
            return $row;
        }
    }
    function getParent( $model )  {
        if( is_numeric($model) )    {
            $model = self::find($model);
        }
        if( !$model->parent_id ) {
            return $model;
        }
        return $model->find($model->parent_id);
    }
}