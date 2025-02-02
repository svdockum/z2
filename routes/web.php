<?php

use Illuminate\Support\Facades\Route;



Route::group(['middleware' => ['web']], function () {

	Route::any('/kenmerk', function(){
	
		//Log::debug('An informational message.' . \Request::input());
	
		 $report = \App\Report::find(\Request::input('reportid'));
		 $kenmerk = \Request::input('kenmerk');
	
		 $report->name = $kenmerk;
		 $report->save();
	
		return;	
	});
	

Route::any('/log/{reportid}','HomeController@showlog');
Route::any('/loglines/{reportid}','HomeController@showloglines');

    //
	Route::any('/import','ImportController@index');

	Route::any('/editreportdateuser/{reportid}', function($reportid){

		$date = \Request::input('date');
		$contr = \Request::input('contr');
		
		$report = \App\Report::find($reportid);

  		$d = DateTime::createFromFormat('Y-m-d', $date);
    	
    	if ($d->format('Y-m-d') != $report->created_at->format('Y-m-d')){
    			$report->created_at = $d->format('Y-m-d H:i:s');
		}
		$report->otheruser = $contr;
		$report->save();
		echo $date . 'done' . $report->created_at->format('Y-m-d');
	});

	Route::any('/data/blus/brands', function() {
		$brands = App\Brand::where('category','=','blus')->get();

		$convertArray = [];

		foreach ($brands as $brand) {
			$row = ['id'=> $brand->id, 'text'=>$brand->name];
			$convertArray[] = $row;
		}

		return response()->json($convertArray);
	});

	Route::any('/data/blus/type/{id?}', function($brand_id = null) {


		if (empty($type_id)) {
			
			$types = App\Type::all();
			return response()->json($types);
		}
		else {
			$types = App\Type::where('brand_id','=',$brand_id)->get();
		}
		$convertArray = [];

		foreach ($types as $type) {
			$row = ['id'=> $type->id, 'text'=>$type->name];
			$convertArray[] = $row;
		}

		return response()->json($convertArray);
	});


	Route::any('/data/blus/material/{id?}', function($type_id = null) {

		if (empty($type_id)) {
			
			$materials = App\Material::all();
			return response()->json($materials);
		}
		else {
		$materials = App\Material::where('type_id','=',$type_id)->get();
		}
		$convertArray = [];

		foreach ($materials as $material) {
			$row = ['id'=> $material->id, 'text'=>$material->name];
			$convertArray[] = $row;
		}

		return response()->json($convertArray);
	});
});

Route::group(['middleware' => 'web'], function () {
    

    Route::get('/', 'HomeController@index');
    Route::get('/home', 'HomeController@index');

  
	Route::get('/customer/create', 'HomeController@customerCreate');
	Route::post('/customer/add', 'HomeController@customerAdd');

	Route::post('/customer/delete/{id}', 'HomeController@deleteCustomer');
	Route::post('/customer/update/{id}', 'HomeController@updateCustomer');
	
Route::get('/latest', 'HomeController@showLatestReports');
Route::get('/klant/{locationid?}', 'HomeController@showKlant');

	Route::any('/customer/edit/{id}', function($id) {
		$customer = App\Customer::find($id);
		return view('customer.editcustomer',['customer'=>$customer]);
	});
	Route::any('/customer/{customerid}/location/edit/{id}', function($customerid,$locationid) {
		$customer = App\Customer::find($customerid);
		$location = App\Location::find($locationid);
		return view('customer.editlocation',['location'=>$location,'customer'=>$customer]);
	});

	
	Route::get('/customer/{customerid}/location/create', 'HomeController@locationCreate');

	Route::post('/location/add', 'HomeController@locationAdd');

	
	Route::get('/customer/{customerid}/location/list', 'HomeController@locationList');
	
	Route::post('/location/delete/{id}', 'HomeController@deleteLocation');
	Route::post('/location/update/{id}', 'HomeController@updateLocation');
	

	Route::post('/customer/savereport', 'HomeController@saveReport');
	Route::post('/customer/saveready', 'HomeController@saveready');
	

	Route::get('/report/pdf/{id}', 'HomeController@reportPdf');
	Route::get('/report/pdf2/{id}', 'HomeController@reportPdf2');

	Route::any('/report/delete/{id}',function($id){

		$report = App\Report::find($id);
		$report->delete();
		return redirect()->back();

	});


	Route::any('/customer/delete/{id}',function($id){

		$customer = App\Customer::find($id);
		$customer->delete();
		return redirect('/');

	});

	Route::any('/location/delete/{id}/{customerid}',function($id,$customerid){

		$location = App\Location::find($id);
		$location->delete();
		return redirect('/customer/' . $customerid);

	});

	Route::any('/report/{id}/selectlocation',function($id){
		//show location list for customer, change button to aanpassen en redirect to report
		$report = App\Report::find($id);
		$customer = App\Customer::find($report->customer_id);
		$locations = App\Location::where('customer_id','=',$customer->id)->get();
		return view('setlocation',['report'=> $report, 'customer'=>$customer, 'locations'=>$locations,'type'=> $report->category]);

	});


	Route::any('/setlocation/{id}/location/{locationid}',function($id,$locationid){
		//show location list for customer, change button to aanpassen en redirect to report
		$report = App\Report::find($id);
		$report->location_id = $locationid;
		$report->save();

		return redirect('customer/'.$report->customer_id.'/'.$report->category.'/location/'. $report->location_id.'/'.$report->id);

	});


	Route::any('/report/replicate/{id}',function($id){

		$report = App\Report::find($id);
		$clone = $report->replicate();
		$clone->push();
		
		$report = App\Report::find($clone->id);
		$report->created_at = date('Y-m-d H:i:s');
		$report->updated_at = date('Y-m-d H:i:s');
		$report->user_id = \Auth::user()->id;
		$report->report_ready = null;
		
		$report->save();

		return redirect('/customer/'.$report->customer_id.'/'.$report->category.'/location/'. $report->location_id.'/'. $report->id);
	});

    Route::get('/customer/{customerid}/{type}/location/{locationid}/{reportid?}/edit',
    function($customerid,$type,$locationid,$reportid){
    	return view('reportdateuseredit', ['reportid'=>$reportid]);
    });

    Route::get('/customer/{customerid}/{type}/location/{locationid}/{reportid?}/checkin', 'HomeController@locationreportcheckin');
    Route::get('/customer/{customerid}/{type}/location/{locationid}/{reportid?}/checkout', 'HomeController@locationreportcheckout');
    Route::get('/customer/{customerid}/{type}/location/{locationid}/{reportid?}/archive', 'HomeController@locationreportarchive');

    Route::get('/customer/{customerid}/{type}/location/{locationid}/{reportid?}', 'HomeController@locationreport');


Route::get('/customer/archive/{customerid}', 'HomeController@showcustomerarchive');

	Route::get('/customer/{customerid}/{type}', 'HomeController@selectlocation');
		Route::get('/customer/{customerid}', 'HomeController@showcustomer');


	Route::get('/user/overview', 'HomeController@userOverview');

	Route::get('/user/create', 'HomeController@userCreate');
	Route::post('/user/add', 'Auth\AuthController@createRedirect');
	Route::post('/user/delete/{id}', 'Auth\AuthController@deleteUser');
	Route::post('/user/update/{id}', 'Auth\AuthController@updateUser');
	Route::any('/user/edit/{id}', function($id) {
		$user = App\User::find($id);
		return view('auth.edituser',['user'=>$user]);
	});



	Route::get('/pdf','HomeController@showpdf');


	Route::get('/lists/blusbrand','HomeController@blusbrand');
	Route::get('/lists/blusstof/{id?}','HomeController@blusstof');
	Route::get('/lists/blustype/{brandid?}/{blusstofid?}','HomeController@blustype');


	Route::get('/lists/nood/{model}/{filter}','HomeController@getlist');
	Route::any('report/log', 'HomeController@anyLog');

	///nood/brand/armatuur
/*
	/nood/brand or type/continu
	/nood/brand/nood
	/nood/brand/battery

	/nood/type/picto
*/

	


	Route::post('/lists/nood/{model}/{filter}', function($model,$filter){
		
		if (!empty(\Request::input('name'))) {

			if (\Request::input('id') > 0) {

				//update
				$object = App::make('\App\\' . $model)->find(\Request::input('id'));
				$object->name = \Request::input('name');
				$object->save();
			}
			else {
			
				$object = App::make('\App\\' . $model)::create([
                    'name' => \Request::input('name'),
                    'category' => $filter,
                   ]);
			}
			
		}

		return redirect()->back();

	});


Route::get('/wachtwoord',function() {

	return view('auth.wachtwoord');


});


	Route::post('/wachtwoord', function(){
		$user = \Auth::user();

		if (!empty(\Request::input('pass'))) {

			
				$user->password = bcrypt(\Request::input('pass'));
				$user->save();
			}
			
		return redirect('/');

	});




//---------------
	
	Route::post('/lists/blusbrand', function(){
		
		if (!empty(\Request::input('brandname'))) {

			if (\Request::input('brandid') > 0) {
				//update
				$brand = \App\Brand::find(\Request::input('brandid'));
				$brand->name = \Request::input('brandname');
				$brand->save();
			}
			else {
				$type = \App\Brand::create([
                    'name' => \Request::input('brandname'),
                    'category' => 'blus',
                   ]);
			}
			
		}

		return redirect()->back();

	});


	Route::post('/lists/blusstof', function(){
		
		if (!empty(\Request::input('blusstofname')) && \Request::input('brandid') > 0) {

			if (\Request::input('blusstofid') > 0) {
				//update
				$type = \App\Type::find(\Request::input('blusstofid'));
				$type->name = \Request::input('blusstofname');
				$type->save();
			}
			else {
				$type = \App\Type::create([
                    'name' => \Request::input('blusstofname'),
                    'category' => 'blus',
                    'brand_id' =>\Request::input('brandid'),
            		]);
			}
			
		}

		return redirect()->back();

	});

Route::post('/lists/type', function(){
		
		if (!empty(\Request::input('typename')) && \Request::input('brandid') > 0 && \Request::input('stofid') > 0) {

			if (\Request::input('typeid') > 0) {
				//update
				$type = \App\Material::find(\Request::input('typeid'));
				$type->name = \Request::input('typename');
				$type->save();
			}
			else {
				$type = \App\Material::create([
                    'name' => \Request::input('typename'),
                    'category' => 'blus',
                    'brand_id' =>\Request::input('brandid'),
                    'type_id' =>\Request::input('stofid'),
            		]);
			}
			
		}

		return redirect()->back();

	});


//////
Route::get('pdf2',function(){
  $data = ['no_checked' => 11, 'no_defect'=>4];


     $pdf = PDF::loadView('PDF.test', $data);
     $pdf->setPaper('a4','portrait');
    $pdf->save('file1.pdf');

     $pdf2 = PDF::loadView('PDF.test', $data);
     $pdf2->setPaper('a4','landscape');
    $pdf2->save('file2.pdf');
});

    
});
