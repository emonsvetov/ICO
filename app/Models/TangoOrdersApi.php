<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class TangoOrdersApi extends Model
{
    use HasFactory;

    /**
     * The database table associated with the model.
     *
     * @var string
     */
    protected $table = 'tango_orders_api';

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * Retrieves a single test configuration.
     *
     * @return TangoOrdersApi|null The first test configuration or null if not found.
     */
    public static function tango_orders_api_get_test(): ?self
    {
        return self::where('is_test', 1)->first();
    }

    /**
     * Fetches all active configurations, adjusted by the application environment.
     *
     * @return Collection of active configurations.
     */
    public static function getActiveConfigurations(): Collection
    {
        $where = ['status' => 1, 'is_test' => env('APP_ENV') !== 'production' ? 1 : 0];
        return self::where($where)->get();
    }

    /**
     * Lists configurations with pagination support.
     *
     * @param int $limit The number of records to return.
     * @param int $offset The number of records to skip.
     * @return Collection of configurations.
     */
    public static function listConfigurations(int $limit = 0, int $offset = 0): Collection
    {
        return self::all();
    }

    /**
     * Counts the total number of configurations in the database.
     *
     * @return int Total number of configurations.
     */
    public static function countConfigurations(): int
    {
        return self::count();
    }

    /**
     * Creates a new configuration in the database.
     *
     * @param array $data Data for creating the new configuration.
     * @return TangoOrdersApi The newly created configuration model instance.
     */
    public static function createConfiguration(array $data): self
    {
        return self::create($data);
    }

    /**
     * Updates an existing configuration by ID with provided data.
     *
     * @param int $id The ID of the configuration to update.
     * @param array $data Data to update the configuration with.
     * @return bool True if the update was successful, false otherwise.
     */
    public static function updateConfiguration(int $id, array $data): bool
    {
        $configuration = self::find($id);
        return $configuration ? $configuration->update($data) : false;
    }

    /**
     * Retrieves a specific configuration by its ID.
     *
     * @param int $id The ID of the configuration to retrieve.
     * @return TangoOrdersApi|null The configuration if found, or null otherwise.
     */
    public static function viewConfiguration(int $id): ?self
    {
        return self::find($id);
    }

    /**
     * Deletes a configuration from the database by its ID.
     *
     * @param int $id The ID of the configuration to delete.
     * @return bool|null True if the deletion was successful, null on error.
     */
    public static function deleteConfiguration(int $id): ?bool
    {
        return self::destroy($id) > 0;
    }
}
