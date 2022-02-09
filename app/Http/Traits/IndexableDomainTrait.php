<?php
namespace App\Http\Traits;
use App\Http\Traits\IndexableTrait;
use DB;

trait IndexableDomainTrait {

    use IndexableTrait;

    public function indexable_domain() {
        // $this->field_name = 'othername';
        $results = $this->indexable();
        return $results;
    }
}