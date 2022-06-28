<?php
namespace App\Models\Traits;

use DB;

trait Treeable
{

    public function isRoot()
    {
        return is_null($this->getParentId());
    }

    public function getParentIdName()
    {
        return 'parent_id';
    }

    public function getParentId()
    {
        return $this->getAttributeValue($this->getParentIdName());
    }

    function getRoot($columns = "*")  {
        if( $columns != "*" )   {
            if(is_array($columns))  {
                $columns = implode(',', $columns);
            }
        }
        if( !$this->exists() ) return null;
        $sql = sprintf("SELECT $columns
        FROM (
        SELECT @id AS _id, (SELECT @id := parent_id FROM `%s` WHERE id = _id)
        FROM (SELECT @id := %d) tmp1
        JOIN `%s` ON @id IS NOT NULL ) tmp2
        JOIN `%s` f ON tmp2._id = f.id
        WHERE f.parent_id IS NULL", $this->table, $this->id, $this->table, $this->table);
        $results = DB::select(DB::raw($sql), [
        ]);
        if( count($results) > 0)    {
            $row = current($results);
            unset($row->{'_id'});
            unset($row->{"(SELECT @id := parent_id FROM `{$this->table}` WHERE id = _id)"});
            return $row;
        }
    }
    function getParent()  {
        if( !$this->exists() ) return null;
        return $this->find($this->parent_id);
    }
    function getChildren()  {
        if( !$this->exists() ) return null;
        return $this->children;
    }
    function hasChildren()  {
        if( !$this->exists() ) return null;
        return $this->children === true;
    }
}