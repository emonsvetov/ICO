<?php

namespace App\Services\reports;

use App\Models\AccountType;
use App\Models\JournalEventType;
use App\Models\MediumInfo;
use App\Models\Merchant;
use App\Models\OptimalValue;
use App\Models\Posting;
use App\Models\Program;
use App\Models\User;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use PhpParser\Node\Expr\Cast\Object_;
use PhpParser\Node\Expr\PostInc;
use stdClass;

class ReportParticipantLoginService extends ReportServiceAbstract
{

    protected function calc(): array
    {
        $this->table = [];

        $program = Program::where('account_holder_id', $this->params[self::PROGRAM_ID])->first();
        $programId = $program ? $program->id : 0;

        $query =  User::whereHas('roles', function (\Illuminate\Database\Eloquent\Builder $query) use ($programId) {
            $query->where('name', 'LIKE', config('roles.participant'))
                ->where('model_has_roles.program_id', $programId);
        });
        $query->whereBetween('users.last_login', [$this->params[self::DATE_FROM], $this->params[self::DATE_TO]]);
        $users = $query->count();
        $this->table['data'] = $users;
        return $this->table;
    }

}


