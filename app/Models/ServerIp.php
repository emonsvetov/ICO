<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Exception;
use RuntimeException;


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

    public static function readList($limit = 0, $offset = 0, $all = false)
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

    public static function countAll()
    {
        try {
            return self::where('deleted', self::NOT_DELETED)->count();
        } catch (Exception $e) {
            throw new RuntimeException('Internal query failed, please contact the API administrator', 500);
        }
    }

    public static function createIp($data)
    {
        try {
            return self::create($data)->id;
        } catch (Exception $e) {
            throw new RuntimeException('Failed to create server IP', 500);
        }
    }

    public static function updateIp($id, $data)
    {
        try {
            $serverIp = self::findOrFail($id);
            $data['updated_at'] = now();
            $serverIp->update($data);
            return $serverIp->id;
        } catch (Exception $e) {
            throw new RuntimeException('Failed to update server IP', 500);
        }
    }

    public static function readById($id)
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

    public static function deleteById($id, $updated_by)
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
}
