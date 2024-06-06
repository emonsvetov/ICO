<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ServerIpsTarget extends Model
{
    protected $table = 'server_ips_target';

    protected $fillable = ['name'];

    /**
     * Retrieves a list of server IP targets with optional pagination.
     *
     * @param int $limit
     * @param int $offset
     * @param bool $all
     * @return \Illuminate\Database\Eloquent\Collection
     * @throws \RuntimeException
     */
    public static function readList($limit = 0, $offset = 0, $all = false)
    {
        try {
            $query = self::query();
            if ($all === false) {
                $query->limit($limit)->offset($offset);
            }
            return $query->get();
        } catch (\Exception $e) {
            throw new \RuntimeException('Internal query failed, please contact the API administrator', 500);
        }
    }

    /**
     * Retrieves the count of server IP targets.
     *
     * @return int
     * @throws \RuntimeException
     */
    public static function countAll()
    {
        try {
            return self::count();
        } catch (\Exception $e) {
            throw new \RuntimeException('Internal query failed, please contact the API administrator', 500);
        }
    }

    /**
     * Creates a new server IP target.
     *
     * @param array $data
     * @return int
     * @throws \Exception
     */
    public static function createTarget(array $data)
    {
        try {
            return self::create($data)->id;
        } catch (\Exception $e) {
            throw new \Exception('Failed to create server IP target', 500);
        }
    }

    /**
     * Updates an existing server IP target by ID.
     *
     * @param int $id
     * @param array $data
     * @return bool
     * @throws \Exception
     */
    public static function updateTarget($id, array $data)
    {
        try {
            $target = self::findOrFail($id);
            return $target->update($data);
        } catch (\Exception $e) {
            throw new \Exception('Failed to update server IP target', 500);
        }
    }

    /**
     * Retrieves a specific server IP target by its ID.
     *
     * @param int $id
     * @return \App\Models\ServerIpsTarget
     * @throws \RuntimeException
     */
    public static function readById($id)
    {
        try {
            return self::findOrFail($id);
        } catch (\Exception $e) {
            throw new \RuntimeException('Internal query failed, please contact the API administrator', 500);
        }
    }

    /**
     * Deletes a specific server IP target by ID.
     *
     * @param int $id
     * @return bool
     * @throws \Exception
     */
    public static function deleteById($id)
    {
        try {
            $target = self::findOrFail($id);
            return $target->delete();
        } catch (\Exception $e) {
            throw new \Exception('Failed to delete server IP target', 500);
        }
    }
}
