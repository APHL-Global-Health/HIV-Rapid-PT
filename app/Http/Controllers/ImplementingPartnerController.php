<?php

namespace App\Http\Controllers;
set_time_limit(0);
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Notifications\Notification;
use App\Http\Controllers\Controller;
use App\User;
use App\Role;
use App\Facility;
use App\County;
use App\Program;
use App\Round;
use App\SmsHandler;

use DB;
use Hash;
use Auth;
use Mail;
use App\Libraries\AfricasTalkingGateway as Bulk;
use Jenssegers\Date\Date as Carbon;
use Excel;
use App;
use File;

//  Notification
use App\Notifications\WelcomeNote;
use App\Notifications\RegretNote;

use App\ImplementingPartner;

class ImplementingPartnerController extends Controller
{
	public function manageImplementingPartner()
	{
		return view('implementingpartner.index');
	}

	public function index()
	{

		$implementingPartners = ImplementingPartner::with('agency','counties')->orderBy('id', 'ASC')->paginate(20);
        $response = [
            'pagination' => [
                'total' => $implementingPartners->total(),
                'per_page' => $implementingPartners->perPage(),
                'current_page' => $implementingPartners->currentPage(),
                'last_page' => $implementingPartners->lastPage(),
                'from' => $implementingPartners->firstItem(),
                'to' => $implementingPartners->lastItem()
            ],
            'data' => $implementingPartners
        ];

        return $implementingPartners->count() > 0 ? response()->json($response) : $error;
	}

	/**
	 * Store a newly created resource in storage.
	 *
	 * @param  \Illuminate\Http\Request
	 * @return \Illuminate\Http\Response
	 */
	public function store(Request $request)
	{
		$this->validate($request, [
			"name" => 'required',
			"agency_id" => 'required',
			"county_id" => 'required',
		]);
		$implementingPartner = new ImplementingPartner;
		$implementingPartner->name = $request->input('name');
		$implementingPartner->agency_id = $request->input('agency_id');

		try{
			$implementingPartner->save();

			foreach ($request->input('county_id') as $county_id) {
				$implementingPartner->counties()->attach($county_id);
			}
			return response()->json($implementingPartner);
		}
		catch (\Illuminate\Database\QueryException $e){
			return response()->json(array('status' => 'error', 'message' => $e->getMessage()));
		}
	}

	/**
	 * Display the specified resource.
	 *
	 * @param  int  id
	 * @return \Illuminate\Http\Response
	 */
	public function show($id){
		$implementingPartner=ImplementingPartner::findOrFail($id);
		return response()->json($implementingPartner);
	}

	/**
	 * Update the specified resource in storage.
	 *
	 * @param  \Illuminate\Http\Request  request
	 * @param  int  id
	 * @return \Illuminate\Http\Response
	 */
	public function update(Request $request, $id)
	{
		$this->validate($request, [
			"name" => 'required',
			"agency_id" => 'required',
		]);
		$implementingPartner = ImplementingPartner::find($id);
		$implementingPartner->name = $request->input('name');
		$implementingPartner->agency_id = $request->input('agency_id');

		try{
			$implementingPartner->save();
			$implementingPartner->counties()->detach();

			foreach ($request->input('county_id') as $county_id) {
				$implementingPartner->counties()->attach($county_id);
			}

			return response()->json($implementingPartner);
		}
		catch (\Illuminate\Database\QueryException $e){
			return response()->json(array('status' => 'error', 'message' => $e->getMessage()));
		}
	}

	/**
	 * Remove the specified resource from storage.
	 *
	 * @param  int  id
	 * @return \Illuminate\Http\Response
	 */
	public function destroy($id){
		$implementingPartner = ImplementingPartner::find($id)->delete();
		return response()->json(['done']);
	}

	/**
	 * enable soft deleted record.
	 *
	 * @param  int  id
	 * @return \Illuminate\Http\Response
	 */
	public function restore($id){
		$implementingPartner = ImplementingPartner::withTrashed()->find($id)->restore();
		return response()->json(['done']);
	}
}