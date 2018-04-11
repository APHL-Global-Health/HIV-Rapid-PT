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
use App\Agency;

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


class AgencyController extends Controller
{
	public function manageAgency()
	{
		return view('agency.index');
	}

	public function index()
	{
		$agencies = Agency::orderBy('id', 'ASC')->paginate(10);

        $response = [
            'pagination' => [
                'total' => $agencies->total(),
                'per_page' => $agencies->perPage(),
                'current_page' => $agencies->currentPage(),
                'last_page' => $agencies->lastPage(),
                'from' => $agencies->firstItem(),
                'to' => $agencies->lastItem()
            ],
            'data' => $agencies
        ];

        return $agencies->count() > 0 ? response()->json($response) : $error;
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
		]);
		$agency = new Agency;
		$agency->name = $request->input('name');

		try{
			$agency->save();
			return response()->json($agency);
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
		$agency=Agency::findOrFail($id);
		return response()->json($agency);
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
		]);
		$agency = Agency::find($id);
		$agency->name = $request->input('name');

		try{
			$agency->save();
			return response()->json($agency);
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
		$agency = Agency::find($id)->delete();
		return response()->json(['done']);
	}

	/**
	 * enable soft deleted record.
	 *
	 * @param  int  id
	 * @return \Illuminate\Http\Response
	 */
	public function restore($id){
		$agency = Agency::withTrashed()->find($id)->restore();
		return response()->json(['done']);
	}
}