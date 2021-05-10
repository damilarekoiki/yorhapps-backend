<?php

namespace App\Http\Controllers;

use App\Models\Visit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;


class VisitController extends Controller
{
    //

    public function createVisit(Request $request){
        
        $visit_id = $request->visit_id;
        $visitor_id = $request->visitor_id;

        Validator::make($request->all(),[
            'visit_id' => 'required'
        ],[
            'visit_id.required' => 'Something went wrong'
        ])->validate();

        try {
            // If visitor with this visit id is not in db, insert row to db
            $visit = Visit::firstOrCreate(
                ['unique_id' => $visit_id],
                ['visitor_id' => $visitor_id]
            );
            return response()->json([
                'response' => 'success',
                'result' => [
                    'visit' => $visit,
                    'message' => 'Visit successfully created',
                ],
            ]);
        } catch (Exception $e) {
            return response()->json([
                'response' => 'error',
                'result' => $e,
                'message' => 'Could not create visit',
            ]);
        }
        
    }

    public function getIp(){
        foreach (array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR') as $key){
            if (array_key_exists($key, $_SERVER) === true){
                foreach (explode(',', $_SERVER[$key]) as $ip){
                    $ip = trim($ip); // just to be safe
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false){
                        return $ip;
                    }
                }
            }
        }
    }
}
