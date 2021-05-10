<?php

namespace App\Http\Controllers;

use App\Models\Visitor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Jenssegers\Agent\Agent;

class VisitorController extends Controller
{
    //

    public function createVisitor(Request $request){

        // $ip = \Request::ip();
        // $ip = \Request::getClientIp(true);
        // $ip = request()->ip();
        // $ip = $request()->ip();

        // $ip = $this->getIp();

        $ip = \Request::ip();

        $unique_id = Str::random(20);

        $user_id = "";

        // If user is loggedin save assing user's unique id to user_id
        if(auth()->user()){
            $user_id = auth()->user()->unique_id;
        }

        $visitor = Visitor::where('ip', $ip)
        ->where('user_id', '')
        ->update(['user_id' => $user_id]);


        // If visitor with this IP and user_id is not in db, insert row to db
        $visitor = Visitor::firstOrCreate(
            ['ip' => $ip, 'user_id' => $user_id],
            ['unique_id' => $unique_id]
        );

        Session::put('visitor_id', $visitor->unique_id);


        try {
            return response()->json([
                'response' => 'success',
                'result' => [
                    'visitor' => $visitor,
                    'message' => 'Visitor successfully created',
                ],
            ]);
        } catch (Exception $e) {
            return response()->json([
                'response' => 'error',
                'message' => 'Could not create visitor',
            ]);
        }
        
    }

    public function getDeviceDetails(Request $request){

        $agent = new Agent();

        // Ubuntu, Windows, OS X, ...
        $os = $agent->platform();
        $os_version = $agent->version($os);
        try {
            return response()->json([
                'response' => 'success',
                'result' => [
                    'os' => $os,
                    'os_version' => $os_version,
                    'message' => 'Device fetched',
                ],
            ]);
        } catch (Exception $e) {
            return response()->json([
                'response' => 'error',
                'result' => [
                    'app' => [],
                    'message' => 'Error in fetching device',
                ],
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
        return request()->ip(); // it will return server ip when no client ip found
    }
}
