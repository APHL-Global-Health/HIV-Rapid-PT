<?php
namespace App\Http\Controllers;
set_time_limit(0); //60 seconds = 1 minute
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\County;
use App\SubCounty;
use App\Facility;
use Input;

use Auth;
use DB;
use Jenssegers\Date\Date as Carbon;
use Excel;
use App;
use File;

class FacilityController extends Controller
{

    public function manageFacility()
    {
        return view('facility.index');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $facilitys = Facility::latest()->paginate(5);
        $error = ['error' => 'No results found, please try with different keywords.'];
        $facilitys = Facility::latest()->withTrashed()->paginate(5);
        //  Check user against roles assigned.        
        if(Auth::user()->isCountyCoordinator())
        {
            $facilitys = County::find(Auth::user()->ru()->tier)->facilities()->latest()->withTrashed()->paginate(5);
        }
        else if(Auth::user()->isSubCountyCoordinator())
        {
            $facilitys = SubCounty::find(Auth::user()->ru()->tier)->facilities()->latest()->withTrashed()->paginate(5);
        }
        else if(Auth::user()->isFacilityInCharge())
        {
            $facilitys = Facility::find(Auth::user()->ru()->tier);
        }
        if($request->has('q')) 
        {
            $search = $request->get('q');
            $facilitys = Facility::where('name', 'LIKE', "%{$search}%")->latest()->withTrashed()->paginate(5);
            if(Auth::user()->isCountyCoordinator())
            {
                $facilitys = County::find(Auth::user()->ru()->tier)->facilities()->where('name', 'LIKE', "%{$search}%")->latest()->withTrashed()->paginate(5);
            }
            else if(Auth::user()->isSubCountyCoordinator())
            {
                $facilitys = SubCounty::find(Auth::user()->ru()->tier)->facilities()->where('name', 'LIKE', "%{$search}%")->latest()->withTrashed()->paginate(5);
            }
        }
        foreach($facilitys as $facility)
        {
            $facility->sub = $facility->subCounty->name;
            $facility->sub_id = $facility->subCounty->id;
            $facility->county = $facility->subCounty->county->name;
            $facility->county_id = $facility->subCounty->county->id;
            $facility->subs = $facility->subCounty->county->subCounties;
            foreach($facility->subs as $sbc)
            {
                $sbc->id = $sbc->id;
                $sbc->value = $sbc->name;
            }
        }

        $response = [
            'pagination' => [
                'total' => $facilitys->total(),
                'per_page' => $facilitys->perPage(),
                'current_page' => $facilitys->currentPage(),
                'last_page' => $facilitys->lastPage(),
                'from' => $facilitys->firstItem(),
                'to' => $facilitys->lastItem()
            ],
            'data' => $facilitys
        ];

        return $facilitys->count() > 0 ? response()->json($response) : $error;
    }
    /*
       Search for a facility in the database
    */

   public function search_facility() {
        $term = Input::get('term');
    
        $results = array();
        
        $queries = Facility::where('name', 'LIKE', '%'.$term.'%')
            ->take(5)->get();
        
        foreach ($queries as $query)
        {
            $results[] = [ 'id' => $query->id, 'value' => $query->name];
        }
        if (count($results)>0) {
            # code...
            $results[] = [ 'id' => 0, 'value' => 'No Records found'];
        } 
        return response()->json($results);
       
    }
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'name' => 'required',
            'label' => 'required',
            'description' => 'required',
            'order' => 'required',
            'tag' => 'required',
            'options' => 'required',
        ]);

        $create = Facility::create($request->all());

        return response()->json($create);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $fclty = Facility::find($id);
        $fclty->name = $request->name;
        $fclty->registration_number = $request->registration_number;
        $fclty->mailing_address = $request->mailing_address;
        $fclty->sub_county_id = $request->sub_id;
        $fclty->in_charge = $request->in_charge;
        $fclty->in_charge_phone = $request->in_charge_phone;
        $fclty->in_charge_email = $request->in_charge_email;
        $fclty->save();

        return response()->json($fclty);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        Facility::find($id)->delete();
        return response()->json(['done']);
    }

    /**
     * enable soft deleted record.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function restore($id) 
    {
        $facility = Facility::withTrashed()->find($id)->restore();
        return response()->json(['done']);
    }
    /**
     * Function to return list of counties.
     *
     */
    public function counties()
    {
        $counties = County::orderBy('name')->pluck('name', 'id');
        $categories = [];
        foreach($counties as $key => $value)
        {
            $categories[] = ['id' => $key, 'value' => $value];
        }
        return $categories;
    }
    /**
     * Function to return list of sub-counties.
     *
     */
    public function subs($id)
    {
        $subs = County::find($id)->subCounties->pluck('name', 'id');
        $categories = [];
        foreach($subs as $key => $value)
        {
            $categories[] = ['id' => $key, 'value' => $value];
        }
        return $categories;
    }
    /**
     * Function to return list of facilities.
     *
     */
    public function facilities($id)
    {
        $facilities = SubCounty::find($id)->facilities->orderBy('name')->pluck('name', 'id');
        $categories = [];
        foreach($facilities as $key => $value)
        {
            $categories[] = ['id' => $key, 'value' => $value];
        }
        return $categories;
    }
    /**
     * Function to return list of sub-counties for consignments.
     *
     */
    public function consignment()
    {
        if(Auth::user()->isCountyCoordinator())
        {
            $id = Auth::user()->ru()->tier;
            $subs = County::find($id)->subCounties->orderBy('name', 'ASC')->pluck('name', 'id');
            $categories = [];
            foreach($subs as $key => $value)
            {
                $categories[] = ['id' => $key, 'value' => $value];
            }
            return $categories;
        }
        else
        {
            //  remember to remove this shitty block
            $id = Auth::user()->ru()->tier;
            $subs = SubCounty::orderBy('name', 'ASC')->pluck('name', 'id');
            $categories = [];
            foreach($subs as $key => $value)
            {
                $categories[] = ['id' => $key, 'value' => $value];
            }
            return $categories;
            // return response()->json();
        }
    }

    /**
     * Function to import facilities using excel sheet uploaded
     *
     */
    public function batchImport(Request $request)
    {
        $exploded = explode(',', $request->excel);
        $decoded = base64_decode($exploded[1]);
        if(str_contains($exploded[0], 'sheet'))
            $extension = 'xlsx';
        else
            $extension = 'xls';
        $fileName = uniqid().'.'.$extension;
        $county = County::find(1)->name;    // Remember to change this
        $folder = '/uploads/facilities/';
        if(!is_dir(public_path().$folder))
            File::makeDirectory(public_path().$folder, 0777, true);
        file_put_contents(public_path().$folder.$fileName, $decoded);
        // dd();
        //  Handle the import
        //  Get the results
        //  Import a user provided file
        //  Convert file to csv
        $data = Excel::load('public/uploads/facilities/'.$fileName, function($reader) {})->get();

        if(!empty($data) && $data->count())
        {

            foreach ($data->toArray() as $key => $value) 
            {
                if(!empty($value))
                {
                    $code = NULL;
                    $name = NULL;
                    $sub_county = NULL;
                    $county = NULL;
                    $in_charge = NULL;
                    $in_charge_phone = NULL;
                    $in_charge_email = NULL;
                    foreach ($value as $mike => $ross) 
                    {
                        if(strcmp($mike, "mfl_code") === 0)
                            $code = $ross;
                        if(strcmp($mike, "facility_name") === 0)
                            $name = $ross;
                        if(strcmp($mike, "subcounty") === 0)
                            $sub_county = $ross;
                        if(strcmp($mike, "county") === 0)
                            $county = $ross;
                        if(strcmp($mike, "in_charge") === 0)
                            $in_charge = $ross;
                        if(strcmp($mike, "in_charge_phone") === 0)
                            $in_charge_phone = $ross;
                        if(strcmp($mike, "in_charge_email") === 0)
                            $in_charge_email = $ross;
                    }
                    if(strcmp($county, "MURANGA") === 0)
                        $county = "Murang'a";
                    if(strcmp($county, "HOMABAY") === 0)
                        $county = "Homa Bay";
                    if(strcmp($county, "THARAKA-NITHI") === 0)
                        $county = "Tharaka Nithi";
                    if($code)
                    {
                        $county_id = County::idByName(trim($county));
                        //  Prepare to save facility details
                        $facilityCount = Facility::where('code', trim($code))->count();
                        if($facilityCount == 0)
                        {
                            $facility = new Facility;
                            $facility->code = $code;
                            $facility->name = $name;
                            $facility->in_charge = $in_charge;
                            $facility->in_charge_phone = $in_charge_phone;
                            $facility->in_charge_email = $in_charge_email;
                            //  Get sub-county
                            $sub_county_id = SubCounty::idByName(trim($sub_county));
                            if(!$sub_county_id)
                            {
                                $subCounty = new SubCounty;
                                $subCounty->name = $sub_county;
                                $subCounty->county_id = $county_id;
                                $subCounty->save();
                                $sub_county_id = $subCounty->id;
                            }
                            $facility->sub_county_id = $sub_county_id;
                            $facility->save();
                        }
                    }
                }
            }
        }
    }
    /**
     * Function to return list of mfl codes for facilities in a certain sub-county.
     *
     */
    public function mfls($id)
    {
        $facilities = SubCounty::find($id)->facilities->pluck('code', 'id');
        $categories = [];
        foreach($facilities as $key => $value)
        {
            $categories[] = ['id' => $key, 'value' => $value];
        }
        return $categories;
    }
    /**
     * Function to return facility name
     *
     */
    public function mfl($id)
    {
        $data = ["name" => "", "sub_county" => "", "county" => ""];
        $pk = Facility::idByCode($id);
        if($pk)
        {
            $facility = Facility::find($pk);
            $name = $facility->name;
            $subCounty = $facility->subCounty->name;
            $county = $facility->subCounty->county->name;
            $data = ["name" => $name, "sub_county" => $subCounty, "county" => $county];
        }
        return response()->json($data);
    }
}
$excel = App::make('excel');