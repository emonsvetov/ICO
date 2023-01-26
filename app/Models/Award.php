<?php

namespace App\Models;

use App\Services\LeaderboardService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

use App\Models\JournalEventType;
use App\Models\JournalEvent;
use App\Models\FinanceType;
use App\Models\AccountType;
use App\Models\MediumType;
use App\Models\EventType;
use App\Models\Currency;
use App\Models\Program;
use App\Models\Event;
use App\Models\User;
use mysql_xdevapi\Exception;

class Award extends Model
{
    use HasFactory;

    protected $table = null;
}
