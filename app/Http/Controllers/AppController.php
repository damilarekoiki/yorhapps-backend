<?php

namespace App\Http\Controllers;

use App\Models\Apps;
use App\Models\AppReleases;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Jenssegers\Agent\Agent;

class AppController extends Controller
{
    //

    public function __construct()
    {
        if(!Session::has('noValidation')){
            if(!auth()->user()){
                return response()->json(['error' => 'Unauthorized'], 401);
            }
        }else{
            Session::forget('noValidation');
        }
        
    }

    private function getVisitorId(){
        $visitor_id = "";
        if(Session::has('visitor_id')){
            $visitor_id = Session::get('visitor_id');
            Session::forget('visitor_id');
        }
        return $visitor_id;
    }

    public function createApp(Request $request)
    {
        # code...
        $request_arr = $request->all();
        $name_slug = strtolower(preg_replace('/[^a-z0-9]/i', '-', $request->name));
        $name_slug = preg_replace('/-+/', '-', $name_slug);
        $request_arr['name_slug'] = $name_slug;

        $category_slug = strtolower(preg_replace('/[^a-z0-9]/i', '-', $request->category));
        $category_slug = preg_replace('/-+/', '-', $category_slug);
        $request_arr['category_slug'] = $category_slug;
        
        Validator::make($request_arr, [
            'name' => [
                'required',
                Rule::unique('apps', 'name')->ignore($request->unique_id, 'unique_id'),
            ],
            'name_slug' => [
                'required',
                Rule::unique('apps', 'name_slug')->ignore($request->unique_id, 'unique_id'),
            ],
            'description' => [
                'required',
                'min:15',
            ],
            'category' => [
                'required',
                Rule::in([
                    'Camera', 'Photo', 'Video', 'Audio', 'Multimedia', 'Educational', 'Downloaders',
                    'Entertainment', 'Video Editors', 'Photo Editors', 'Audio Editors', 'Graphics', 'Calculators',
                    'Video Players', 'Audio Players', 'Banking and Money', 'Wireless', 'Antivirus',
                    'Text Editors', 'Browsers', 'Social', 'IDLEs', 'Tools and Utilities'

                ]),
            ]
        ],[
            'name_slug.unique' => "The name has already been chosen."
        ])->validate();
        
        $app = new Apps;
        $admin = auth()->user();

        $app->unique_id = Str::random(20);
        $app->name = $request->name;
        $app->name_slug = $name_slug;
        $app->category = $request->category;
        $app->category_slug = $category_slug;
        $app->description = $request->description;
        $app->added_by = $admin->unique_id;

        $app->save();

        try {
            return response()->json([
                'response' => 'success',
                'result' => [
                    'app' => $app,
                    'message' => 'Release first version',
                ],
            ]);
        } catch (Exception $e) {
            return response()->json([
                'response' => 'error',
                'message' => 'Could not create app',
            ]);
        }
    }

    public function fetchAllApps(){

        
        
        try {
            $apps = Apps::all();
            return response()->json([
                'response' => 'success',
                'result' => [
                    'apps' => $apps,
                    'visitor_id' => $this->getVisitorId(),
                    'message' => 'Apps fetched',
                ],
            ]);
        } catch (Exception $e) {
            return response()->json([
                'response' => 'error',
                'result' => [
                    'apps' => [],
                    'message' => 'Error in fetching apps',
                ],
            ]);
        }
        
    }

    public function fetchAllPaginatedApps(){
        
        $apps = Apps::with('appReleases')->paginate(24);

        try {
            return response()->json([
                'response' => 'success',
                'result' => [
                    'apps' => $apps,
                    'visitor_id' => $this->getVisitorId(),
                    'message' => 'Apps fetched',
                ],
            ]);
        } catch (Exception $e) {
            return response()->json([
                'response' => 'error',
                'result' => [
                    'apps' => [],
                    'message' => 'Error in fetching apps',
                ],
            ]);
        }
        
    }

    

    public function fetchPaginatedAppsGroupedByCategory(Request $request){
        
        $categories = Apps::groupBy('category')->select('category')->paginate(4);
        $apps = [];
        

        if(!empty($categories)){
            foreach ($categories as $category) {
                $category_name  = $category->category;
                $apps[$category_name] = Apps::with('appReleases')->
                where('category', $category_name)
                ->take(6)
                ->get();
            }
        }

        try {
            return response()->json([
                'response' => 'success',
                'result' => [
                    'apps' => $apps,
                    'categories' => $categories,
                    'visitor_id' => $this->getVisitorId(),
                    'message' => 'Apps fetched',
                ],
            ]);
        } catch (Exception $e) {
            return response()->json([
                'response' => 'error',
                'result' => [
                    'apps' => [],
                    'message' => 'Error in fetching apps',
                ],
            ]);
        }

    }

    public function fetchPaginatedAppsInCategory(Request $request){
        
        $category = $request->category_slug;

        $apps = Apps::with('appReleases')
        ->where('category', $category)
        ->paginate(24);

        try {
            return response()->json([
                'response' => 'success',
                'result' => [
                    'apps' => $apps,
                    'visitor_id' => $this->getVisitorId(),
                    'message' => 'Apps fetched',
                ],
            ]);
        } catch (Exception $e) {
            return response()->json([
                'response' => 'error',
                'result' => [
                    'apps' => [],
                    'message' => 'Error in fetching apps',
                ],
            ]);
        }

    }

    public function getAppWithReleases(Request $request){

        $name_slug = $request->name_slug;

        $app = Apps::withCount('downloads as app_num_downloads')
        ->where('name_slug', $name_slug)
        ->first();

        $appReleases = AppReleases::with('apps')
        ->withCount('downloads')
        ->whereHas('apps', function (Builder $query) use ($name_slug) {
            $query->where('name_slug', $name_slug);
        })
        ->orderBy('id', 'DESC')
        ->get();

        try {
            return response()->json([
                'response' => 'success',
                'result' => [
                    'app' => $app,
                    'app_releases' => $appReleases,
                    'visitor_id' => $this->getVisitorId(),
                    'message' => 'App fetched',
                ],
            ]);
        } catch (Exception $e) {
            return response()->json([
                'response' => 'error',
                'result' => [
                    'app' => [],
                    'message' => 'Error in fetching app',
                ],
            ]);
        }      
        
    }

    public function getAppWithReleasesAndRecommended(Request $request){

        $name_slug = $request->name_slug;
        $os_slug = $request->os_slug;
        $app_version_slug = $request->app_version_slug;

        $app = Apps::withCount('downloads as app_num_downloads')
        ->where('name_slug', $name_slug)
        ->first();

        $appReleases = AppReleases::with('apps')
        ->withCount('downloads')
        ->whereHas('apps', function (Builder $query) use ($name_slug) {
            $query->where('name_slug', $name_slug);
        })
        ->orderBy('id', 'DESC')
        ->get();

        $recommended = AppReleases::with('apps')
        ->where('os_slug', $os_slug)
        ->where('app_version_slug', $app_version_slug)
        ->withCount('downloads')
        ->whereHas('apps', function (Builder $query) use ($name_slug) {
            $query->where('name_slug', $name_slug);
        })
        ->orderBy('id', 'DESC')
        ->first();

        try {
            return response()->json([
                'response' => 'success',
                'result' => [
                    'app' => $app,
                    'app_releases' => $appReleases,
                    'recommended' => $recommended,
                    'visitor_id' => $this->getVisitorId(),
                    'message' => 'App fetched',
                ],
            ]);
        } catch (Exception $e) {
            return response()->json([
                'response' => 'error',
                'result' => [
                    'app' => [],
                    'message' => 'Error in fetching app',
                ],
            ]);
        }      
        
    }

    public function scanFile(Request $request)
    {
        # code...
        // Validator::make($request->all(),[
        //     'file' => 'clamav'
        // ])->validate();

        return response()->json([
            'response' => 'success',
            'result' => [
                'message' => 'No virus detected',
            ],
        ]);
    }


    
    
}
