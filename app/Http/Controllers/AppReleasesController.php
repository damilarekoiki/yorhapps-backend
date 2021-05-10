<?php

namespace App\Http\Controllers;

use App\Models\AppReleases;
use App\Models\Apps;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Jenssegers\Agent\Agent;

class AppReleasesController extends Controller
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

    public function downloadRelease(Request $request){

        $app_release_id = $request->app_release_id;

        $app_release = AppReleases::where("unique_id", $app_release_id)->firstOrFail();

        $file_path = $app_release->installer_file_path;

        $file = storage_path() . "/app/public/$file_path";

        $headers = [
            'Content-Type' => 'application/octet-stream',
        ];
        return response()->download($file);
    }

    public function searchAppRelease(Request $request){

        $keyword = $request->keyword;
        
        $app_releases = AppReleases::with('apps')
        ->where('description', 'like', '%'.$keyword.'%')
        ->orWhere('app_version', 'like', '%'.$keyword.'%')
        ->orWhere('os', 'like', '%'.$keyword.'%')
        ->orWhereJsonContains('os_versions',[$keyword])
        ->orWhereJsonContains('bit_types',[$keyword])
        ->orWhereHas('apps', function ($query) use ($keyword) {
            $query->where('name', 'like', '%'.$keyword.'%')
            ->orWhere('category', 'like', '%'.$keyword.'%')
            ->orWhere('description', 'like', '%'.$keyword.'%');

        })
        ->orderBy('id', 'DESC')
        ->paginate(24);

        try {
            return response()->json([
                'response' => 'success',
                'result' => [
                    'app_releases' => $app_releases,
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

    public function addAppRelease(Request $request)
    {
        # code...

        $request_arr = $request->all(); // Create request array that will be used for validation

        $app_id = $request->app_id;
        $app_version = $request->app_version;
        $os = $request->os;
        $bit_types = [];
        if($request->bit_types){
            $bit_types = json_decode($request->bit_types, true); // Convert to array
        }
        
        
        // Create app version slug
        $app_version_slug = strtolower(preg_replace('/[^a-z0-9]/i', '-', $app_version));
        $app_version_slug = preg_replace('/-+/', '-', $app_version_slug);
        // Create app version slug ENDS

        // Add app version slug index to request_arr; To be used in validation
        $request_arr['app_version_slug'] = $app_version_slug;

        // Create os slug
        $os_slug = strtolower(preg_replace('/[^a-z0-9]/i', '-', $os));
        $os_slug = preg_replace('/-+/', '-', $os_slug);
        // Create os ENDS

        // Add os slug index to request_arr; To be used in validation
        $request_arr['os_slug'] = $os_slug;
        

        // Add bit types index to request_arr; To be used in validation
        $request_arr['bit_types'] = $bit_types;

        // Create the validation rules
        $rules = [
            'app_id' => 'required',
            'description' => 'required | min:15',
            'os' => 'required',
            'os_versions' => 'min:1',
            'os_versions.*' => 'required',

            'os_slug' => 'required',
            'os_versions_slug' => 'min:1',
            'os_versions_slug.*' => 'required',
            'app_version_slug' => [
                'required',
                Rule::unique('app_releases')
                ->ignore($request->unique_id, 'unique_id')
                ->where('app_id', $app_id)->where('os', $os)
            ],

            'app_version' => [
                'required',
                /*
                    Check app_releases table for uniqueness
                    A version cannot appear more than once for an app's OS

                */
                Rule::unique('app_releases')
                ->ignore($request->unique_id, 'unique_id')
                ->where('app_id', $app_id)->where('os', $os)
            ],
            'icon_file' => 'required | mimes:jpg,png',
            'bit_types.*' => [
                'required',
                Rule::in(['32', '64']),
            ]
        ]; 

        $os_versions_slug = [];
        $os_versions = [];

        if(!empty($request->os_versions)){
            $os_versions = json_decode($request->os_versions, true); // Convert to array
            // Since os versions is an array, loop through its elements and create slug for each
            for($i=0; $i<count($os_versions); $i++){
                $os_version = $os_versions[$i];
                // Create slug
                $os_version_slug = strtolower(preg_replace('/[^a-z0-9]/i', '-', $os_version));
                $os_version_slug = preg_replace('/-+/', '-', $os_version_slug);
                $os_versions_slug[] = $os_version_slug; // push to os version slug array
                // Create sluge Ends
            }
        }
        
        // Add os versions slug index to request_arr; To be used in validation
        $request_arr['os_versions_slug'] = $os_versions_slug;

        // Default rule for the installation file
        $rules['installation_file'] = 'required';
        if(!empty($request->file('installation_file'))){
            $installer_type = $request->file('installation_file')->getClientMimeType();    
            // Had to do it like this because, I was having issues with validating exe files
            if($installer_type != 'application/x-msdownload'){
                $rules['installation_file'] = 'required | mimes:exe,dll,com,bat,msi,pkg,apk,ipa,deb,bin,sh,lib,tar.gz,tar,gz,zip';
            }
        }   
        
        // Make the validation
        Validator::make($request_arr,
        $rules,
        [
            // ERROR MESSAGES
            'app_id.required' => 'The app field is required.',
            'app_version.unique' => "The app version has already be taken. Choose another app version or OS."

        ])->validate();

        // Create a new object of app release
        $appRelease = new AppReleases();

        $admin = auth()->user(); // The logged in admin

        $app = Apps::firstWhere('unique_id', $app_id);
        $app_name = $app->name;
        
        // Get the files extensions
        $installer_file_extension = $request->file('installation_file')->getClientOriginalExtension();
        $icon_file_extension = $request->file('icon_file')->getClientOriginalExtension();
        // Get the files extensions ENDS

        $time = time();

        $app_name_replaced = strtolower(str_replace(' ','_',$app_name));
        
        // Create file names
        $installer_name = $app_name_replaced.$time.".$installer_file_extension";
        $icon_name = $app_name_replaced.$time.".$icon_file_extension";
        // Create file names

        $installer_file_size = $request->file('installation_file')->getSize();


        // $app_name_replaced = str_replace(' ','_',$app_name);
        // $os_substr = substr($os, 0, 2);
        // $app_name."-v".$app_version."-".$bit_type
        // $app_name."-v".$app_version."-".$bit_type




        // Save the files with their created names
        Storage::putFileAs(
            'public/apps_releases',
            $request->file('installation_file'),
            $installer_name
        );
        $installer_file_path = "apps_releases/$installer_name";

        Storage::putFileAs(
            'public/apps_releases_icons',
            $request->file('icon_file'),
            $icon_name
        );
        $icon_path = "apps_releases_icons/$icon_name";
        // Save the files with their created names ENDS

        $os_versions = json_encode(($os_versions)); // JSON encode for saving in db
        $os_version_slug = json_encode($os_versions_slug); // JSON encode for saving in db
        $bit_types = json_encode($bit_types); // JSON encode for saving in db



        $appRelease->unique_id = Str::random(20);
        $appRelease->app_id = $request->app_id;
        $appRelease->description = $request->description;
        $appRelease->app_version = $request->app_version;
        $appRelease->app_version_slug = $app_version_slug;
        $appRelease->os = $request->os;
        $appRelease->os_slug = $os_slug;
        $appRelease->os_versions = $os_versions;
        $appRelease->os_versions_slug = $os_version_slug;
        $appRelease->bit_types = $bit_types;
        $appRelease->added_by = $admin->unique_id;
        $appRelease->installer_file_path = $installer_file_path;
        $appRelease->installer_file_size = $installer_file_size;
        $appRelease->icon_path = $icon_path;

        $appRelease->save();

        try {
            return response()->json([
                'response' => 'success',
                'result' => [
                    'app_release' => $appRelease,
                    'message' => 'App release successfully added',
                ],
            ]);
        } catch (Exception $e) {
            return response()->json([
                'response' => 'error',
                'message' => 'Could not add app release',
            ]);
        }
    
    }
}
