<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class SyncController extends Controller
{
    public function pisa()
    {
        $users = User::select('citizen_id')->pluck('citizen_id')->toArray();

        $sts = DB::connection('moncon')
            ->table('STUDENT as s')
            ->selectRaw('s."OBJECTID" as citizen_id')
            ->join('SCHOOL as sc', 'sc.OBJECTID', '=', 's.SCHOOLID')
            ->leftJoin('GRADE as g', 'g.OBJECTID', '=', 's.GRADEID')
            ->where('s.BIRTHDATE', '>=', '2009-01-01')
            ->where('s.BIRTHDATE', '<', '2010-01-01')
            ->where('g.GRADENUMBER', '>', '6')
            ->where('g.GRADENUMBER', '<', '11')
            ->pluck('citizen_id')
            ->toArray();

        $diff = array_diff($sts, $users);

        $sts = DB::connection('moncon')
            ->table('STUDENT as s')
            ->selectRaw('s."OBJECTID" as citizen_id, s."STUDENTNAME" as first_name, s."STUDENTSURNAME" as last_name,
                                   sc."OKPONUMBER" as school_id, g."GRADENUMBER" as grade, g."GRADELETTER" as class_name')
            ->whereIn('s.OBJECTID', $diff)
            ->get();

        $total = 0;
        $error = [];
        foreach ($sts as $st) {
            try {
                User::create([
                    'citizen_id' => mb_trim($st->citizen_id),
                    'first_name' => mb_trim($st->first_name),
                    'last_name' => mb_trim($st->last_name),
                    'grade' => mb_trim($st->grade),
                    'class_name' => $this->letter($st->class_name),
                    'school_id' => mb_trim($st->school_id),
                ]);

                $total++;
            } catch (\Throwable $th) {
                $error[] = [
                    'citizen_id' => $st->citizen_id,
                    'school_id' => $st->school_id,
                    'message' => $th->getMessage(),
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
