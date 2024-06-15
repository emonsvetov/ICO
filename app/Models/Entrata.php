<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class Entrata extends Model
{
    protected $table = 'entrata';

    protected $fillable = [
        'program_id',
        'entrata_property_id',
        'url',
        'username',
        'password',
        'user_type'
    ];

    /**
     * Retrieves a list of Entrata configurations with optional pagination.
     *
     * @param array $extra_args
     * @param int $limit
     * @param int $offset
     * @return Collection
     */
    public static function readList(array $extra_args = [], int $limit = 0, int $offset = 0): Collection
    {
        try {
            return self::when($extra_args, function ($query, $extra_args) {
                foreach ($extra_args as $key => $value) {
                    $query->where($key, $value);
                }
            })
                ->limit($limit)
                ->offset($offset)
                ->get();
        } catch (\Exception $e) {
            Log::error("Failed to read list: " . $e->getMessage());
            throw new \RuntimeException($e->getMessage(), 500);
        }
    }

    /**
     * Retrieves the count of Entrata configurations based on extra arguments.
     *
     * @param array $extra_args
     * @return int
     */
    public static function readListCount(array $extra_args = []): int
    {
        try {
            return self::when($extra_args, function ($query, $extra_args) {
                foreach ($extra_args as $key => $value) {
                    $query->where($key, $value);
                }
            })->count();
        } catch (\Exception $e) {
            Log::error("Failed to read list count: " . $e->getMessage());
            throw new \RuntimeException($e->getMessage(), 500);
        }
    }

    /**
     * Retrieves a specific Entrata configuration by its ID.
     *
     * @param int $id
     * @return Entrata|null
     */
    public static function view(int $id): ?self
    {
        try {
            return self::find($id);
        } catch (\Exception $e) {
            Log::error("Failed to view: " . $e->getMessage());
            throw new \RuntimeException($e->getMessage(), 500);
        }
    }

    /**
     * Creates a new Entrata configuration.
     *
     * @param array $data
     * @return Entrata
     */
    public static function createEntrata(array $data): self
    {
        try {
            return self::create($data);
        } catch (\Exception $e) {
            Log::error("Failed to create: " . $e->getMessage());
            throw new \RuntimeException($e->getMessage(), 500);
        }
    }

    /**
     * Updates an existing Entrata configuration by ID.
     *
     * @param int $entrata_id
     * @param array $data
     * @return bool
     */
    public static function updateEntrata(int $entrata_id, array $data): bool
    {
        $entrata = self::find($entrata_id);
        if (!$entrata) {
            throw new \RuntimeException('Entrata not found', 404);
        }

        try {
            return $entrata->update($data);
        } catch (\Exception $e) {
            Log::error("Failed to update: " . $e->getMessage());
            throw new \RuntimeException($e->getMessage(), 500);
        }
    }

    /**
     * Deletes a specific Entrata configuration by ID.
     *
     * @param int $id
     * @return bool|null
     */
    public static function deleteConfiguration(int $id): ?bool
    {
        try {
            return self::destroy($id) > 0;
        } catch (\Exception $e) {
            Log::error("Failed to delete: " . $e->getMessage());
            throw new \RuntimeException($e->getMessage(), 500);
        }
    }
}
