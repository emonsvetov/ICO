<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class ServerIpsTarget extends Model
{
    use HasFactory;

    protected $table = 'server_ips_target';

    protected $fillable = ['name'];

    /**
     * Retrieves a list of server IP targets with optional pagination.
     *
     * @param int $limit
     * @param int $offset
     * @return Collection
     * @throws RuntimeException
     */
    public static function readList(int $limit = 0, int $offset = 0): Collection
    {
        try {
            $query = self::query();
            if ($limit > 0) {
                $query->limit($limit)->offset($offset);
            }
            return $query->get();
        } catch (\Exception $e) {
            Log::error('Failed to retrieve server IP targets list: ' . $e->getMessage());
            throw new RuntimeException('Internal query failed, please contact the API administrator', 500);
        }
    }

    /**
     * Retrieves the count of server IP targets.
     *
     * @return int
     * @throws RuntimeException
     */
    public static function countAll(): int
    {
        try {
            return self::count();
        } catch (\Exception $e) {
            Log::error('Failed to count server IP targets: ' . $e->getMessage());
            throw new RuntimeException('Internal query failed, please contact the API administrator', 500);
        }
    }

    /**
     * Creates a new server IP target.
     *
     * @param array $data
     * @return int
     * @throws RuntimeException
     */
    public static function createTarget(array $data)
    {
        try {
            return self::create($data)->id;
        } catch (\Exception $e) {
            Log::error('Failed to create server IP target: ' . $e->getMessage());
            throw new RuntimeException('Failed to create server IP target', 500);
        }
    }

    /**
     * Updates an existing server IP target by ID.
     *
     * @param int $id
     * @param array $data
     * @return bool
     * @throws RuntimeException
     */
    public static function updateTarget(int $id, array $data): bool
    {
        try {
            $target = self::findOrFail($id);
            return $target->update($data);
        } catch (\Exception $e) {
            Log::error('Failed to update server IP target: ' . $e->getMessage());
            throw new RuntimeException('Failed to update server IP target', 500);
        }
    }

    /**
     * Retrieves a specific server IP target by its ID.
     *
     * @param int $id
     * @return \App\Models\ServerIpsTarget
     * @throws RuntimeException
     */
    public static function readById(int $id): self
    {
        try {
            return self::findOrFail($id);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve server IP target by ID: ' . $e->getMessage());
            throw new RuntimeException('Internal query failed, please contact the API administrator', 500);
        }
    }

    /**
     * Deletes a specific server IP target by ID.
     *
     * @param int $id
     * @return bool
     * @throws RuntimeException
     */
    public static function deleteById(int $id): bool
    {
        try {
            $target = self::findOrFail($id);
            return $target->delete();
        } catch (\Exception $e) {
            Log::error('Failed to delete server IP target: ' . $e->getMessage());
            throw new RuntimeException('Failed to delete server IP target', 500);
        }
    }
}
