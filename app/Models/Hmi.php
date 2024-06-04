<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class Hmi extends Model
{
    protected $table = 'hmi';

    protected $fillable = [
        'hmi_name',
        'hmi_username',
        'hmi_password',
        'hmi_url',
        'hmi_is_test'
    ];

    /**
     * Retrieves a list of HMI configurations with optional pagination.
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
     * Retrieves the count of HMI configurations based on extra arguments.
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
     * Retrieves a specific HMI configuration by its ID.
     *
     * @param int $id
     * @return Hmi|null
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
     * Creates a new HMI configuration.
     *
     * @param array $data
     * @return Hmi
     */
    public static function createHmi(array $data): self
    {
        try {
            return self::create($data);
        } catch (\Exception $e) {
            Log::error("Failed to create: " . $e->getMessage());
            throw new \RuntimeException($e->getMessage(), 500);
        }
    }

    /**
     * Updates an existing HMI configuration by ID.
     *
     * @param int $hmi_id
     * @param array $data
     * @return bool
     */
    public static function updateHmi(int $hmi_id, array $data): bool
    {
        $hmi = self::find($hmi_id);
        if (!$hmi) {
            throw new \RuntimeException('HMI not found', 404);
        }

        try {
            return $hmi->update($data);
        } catch (\Exception $e) {
            Log::error("Failed to update: " . $e->getMessage());
            throw new \RuntimeException($e->getMessage(), 500);
        }
    }

    /**
     * Deletes a specific HMI configuration by ID.
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
