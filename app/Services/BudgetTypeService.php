<?php

namespace App\Services;

use App\Models\BudgetType;

class BudgetTypeService
{
    public function getAllBudgetTypes()
    {
        return BudgetType::all();
    }

    public function getBudgetTypeById($id)
    {
        return BudgetType::findOrFail($id);
    }

    public function createBudgetType(array $data)
    {
		try {
            // Check if the title already exists
            if (BudgetType::where('title', $data['title'])->exists()) {
                throw new \Symfony\Component\HttpKernel\Exception\HttpException(403, 'Title already exists');
            }
            return BudgetType::create([
                'name' =>$data['title'],
                'title' => $data['title'],
            ]);
        } catch (\Exception $e) {
            throw $e;
        }
	}
}
 
