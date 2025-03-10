<?php

namespace App\Http\Controllers;

use App\Jobs\SyncStudent;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class SyncController extends Controller
{
    public function stjob()
    {
        $users = User::query()
            ->whereNull('token')
            ->where(function ($query) {
                $query->whereNull('try_at');
            })
            ->orderBy('school_id')
            ->orderBy('grade')
            ->orderBy('class_name')
            ->get();

        $total = 0;
        $error = [];
        foreach ($users as $user) {
            try {
                dispatch(new SyncStudent($user));
                $total++;
            } catch (\Exception $e) {
                $error[] = [
                    'citizen_id' => $user->citizen_id,
                    'school_id' => $user->school_id,
                    'message' => $e->getMessage(),
                ];
            }
        }

        return [
            'total' => $total,
            'error' => $error,
        ];
    }

    public function pisa()
    {
        $users = User::select('citizen_id')->pluck('citizen_id')->toArray();

        $sts = DB::connection('moncon')
            ->table('STUDENT as s')
            ->selectRaw('s."OBJECTID" as citizen_id')
            ->leftJoin('GRADE as g', 'g.OBJECTID', '=', 's.GRADEID')
            ->where(function ($query) {
                $query->where('s.STUDENTPIN', 'ilike', '_____2009%')
                    ->orWhere(function ($q) {
                        $q->where('s.BIRTHDATE', '>=', '2009-01-01')
                            ->where('s.BIRTHDATE', '<', '2010-01-01');
                    });
            })
            ->where('g.GRADENUMBER', '>=', '6')
            ->where('g.GRADENUMBER', '<=', '11')
            ->whereNotNull('s.SCHOOLID')
            ->whereNotNull('s.GRADEID')
            ->pluck('citizen_id')
            ->toArray();

        $diff = array_diff($sts, $users);

        $sts = DB::connection('moncon')
            ->table('STUDENT as s')
            ->selectRaw('s."OBJECTID" as citizen_id, s."STUDENTNAME" as first_name, s."STUDENTSURNAME" as last_name,
                                   sc."OKPONUMBER" as school_id, g."GRADENUMBER" as grade, g."GRADELETTER" as class_name')
            ->join('SCHOOL as sc', 'sc.OBJECTID', '=', 's.SCHOOLID')
            ->leftJoin('GRADE as g', 'g.OBJECTID', '=', 's.GRADEID')
            ->whereIn('s.OBJECTID', $diff)
            ->get();

        $total = 0;
        $error = [];
        foreach ($sts as $st) {
            try {
                $grade = $st->grade < 7 ? 7 : (min($st->grade, 10));
                $user = User::create([
                    'citizen_id' => mb_trim($st->citizen_id),
                    'first_name' => mb_trim($st->first_name),
                    'last_name' => mb_trim($st->last_name),
                    'grade' => $grade,
                    'class_name' => $this->letter($st->class_name),
                    'school_id' => mb_trim($st->school_id),
                ]);

                $total++;

                dispatch(new SyncStudent($user));
            } catch (\Exception $e) {
                $error[] = [
                    'citizen_id' => $st->citizen_id,
                    'school_id' => $st->school_id,
                    'message' => $e->getMessage(),
                ];
            }
        }

        return [
            'total' => $total,
            'error' => $error,
        ];

    }

    private function letter($l)
    {
        //$letters = 'АБВГДЕЖЗИЙКЛМНОПРСТУФХЦЧШЩЪЫЬЭЮЯ';

        $l = mb_strtoupper(mb_trim($l));

//        if (str_contains($letters, $l)) {
//            return $l;
//        }

        return $l;
    }
}
