<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Exception;
use RuntimeException;
use Carbon\Carbon;

class ServerIp extends Model
{
    const NOT_DELETED = 0;

    protected $table = 'server_ips';

    protected $fillable = [
        'ip',
        'comment',
        'target',
        'deleted',
        'updated_by',
        'created_at',
        'updated_at',
    ];

    /**
     * Retrieves a list of server IPs with optional pagination.
     *
     * @param int $limit
     * @param int $offset
     * @param bool $all
     * @return \Illuminate\Database\Eloquent\Collection
     * @throws \RuntimeException
     */
    public static function readList(int $limit = 0, int $offset = 0, bool $all = false)
    {
        try {
            $query = self::select('server_ips.*', 'users.first_name', 'users.last_name', 'users.email', 'server_ips_target.name as target_name')
                ->leftJoin('users', 'users.id', '=', 'server_ips.updated_by')
                ->leftJoin('server_ips_target', 'server_ips_target.id', '=', 'server_ips.target')
                ->where('server_ips.deleted', self::NOT_DELETED);

            if (!$all) {
                $query->limit($limit)->offset($offset);
            }

            return $query->get();
        } catch (Exception $e) {
            throw new RuntimeException('Internal query failed, please contact the API administrator', 500);
        }
    }

    /**
     * Retrieves the count of server IPs.
     *
     * @return int
     * @throws \RuntimeException
     */
    public static function countAll()
    {
        try {
            return self::where('deleted', self::NOT_DELETED)->count();
        } catch (Exception $e) {
            throw new RuntimeException('Internal query failed, please contact the API administrator', 500);
        }
    }

    /**
     * Creates a new server IP.
     *
     * @param array $data
     * @return int
     * @throws \RuntimeException
     */
    public static function createIp(array $data)
    {
        try {
            $data['updated_by'] = Auth::id();
            return self::create($data)->id;
        } catch (Exception $e) {
            throw new RuntimeException('Failed to create server IP', 500);
        }
    }

    /**
     * Updates an existing server IP by ID.
     *
     * @param int $id
     * @param array $data
     * @return int
     * @throws \RuntimeException
     */
    public static function updateIp(int $id, array $data)
    {
        try {
            $serverIp = self::findOrFail($id);
            $data['updated_at'] = now();
            $data['updated_by'] = Auth::id();
            $serverIp->update($data);
            return $serverIp->id;
        } catch (Exception $e) {
            throw new RuntimeException('Failed to update server IP', 500);
        }
    }

    /**
     * Retrieves a specific server IP by its ID.
     *
     * @param int $id
     * @return \App\Models\ServerIp
     * @throws \RuntimeException
     */
    public static function readById(int $id)
    {
        try {
            return self::select('server_ips.*', 'users.first_name', 'users.last_name', 'users.email', 'server_ips_target.name as target_name')
                ->leftJoin('users', 'users.id', '=', 'server_ips.updated_by')
                ->leftJoin('server_ips_target', 'server_ips_target.id', '=', 'server_ips.target')
                ->where('server_ips.id', $id)
                ->where('server_ips.deleted', self::NOT_DELETED)
                ->firstOrFail();
        } catch (Exception $e) {
            throw new RuntimeException('Internal query failed, please contact the API administrator', 500);
        }
    }

    /**
     * Deletes a specific server IP by ID.
     *
     * @param int $id
     * @param int $updated_by
     * @return int
     * @throws \RuntimeException
     */
    public static function deleteById(int $id, int $updated_by)
    {
        try {
            $serverIp = self::findOrFail($id);
            $serverIp->deleted = 1;
            $serverIp->updated_by = $updated_by;
            $serverIp->updated_at = now();
            $serverIp->save();
            return $serverIp->id;
        } catch (Exception $e) {
            throw new RuntimeException('Failed to delete server IP', 500);
        }
    }

    /**
     * Accessor to format the created_at attribute.
     *
     * @param $value
     * @return string
     */
    public function getCreatedAtAttribute($value): string
    {
        return Carbon::parse($value)->format('d-m-Y H:i:s');
    }

    /**
     * Accessor to format the updated_at attribute.
     *
     * @param $value
     * @return string
     */
    public function getUpdatedAtAttribute($value): string
    {
        return Carbon::parse($value)->format('d-m-Y H:i:s');
    }
}
