<?php

namespace App\Http\Controllers;

use App\Customer;
use App\Location;
use App\Report;
use App\Log;
use PDF;
use Response;
use Session;
use Illuminate\Http\Request;
use App\User;
use Carbon\Carbon;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');

    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $user = \Auth::user();
        $user->last_loggedin = date('Y-m-d H:i:s');
        $user->save();

        if (!empty(\Auth::user()->customer_id)) {
            return redirect('/klant');
        }

         if (in_array(\Auth::user()->role,[3])) {
            return view('home', array('customers' => Customer::orderBy('name')->where('isNood','=',1)->get()));
        
         }
        if (in_array(\Auth::user()->role,[4])) {
            return view('home', array('customers' => Customer::orderBy('name')->where('isKeerBlus','=',1)->get()));
        
        }


        return view('home', array('customers' => Customer::orderBy('name')->get()));
           
    }

    public function showlog($reportid) {
        $report = Report::withTrashed()->find($reportid);

        $date = new \DateTime("12/01/2017");
        $now = new \DateTime();



if($now > $date) { // overgang . zo behouden we nog history in de eerste twee maanden
        $entries = Log::where('report_id','=',$reportid)->get();
        if ($entries->isEmpty())
        $entries = Log::where('customer_id','=',$report->customer_id)->where('location_id','=',$report->location_id)->orderBy('created_at','desc')->get();
}
else {
        $entries = Log::where('customer_id','=',$report->customer_id)->where('location_id','=',$report->location_id)->orderBy('created_at','desc')->get();

}

        return view('logview', array('entries' => $entries, 'customerid'=>$report->customer_id));
    }

     public function showloglines($entryid) {
        $log = Log::find($entryid);
    
        return view('loglines', array('entry' => $log));
    }

    public function showKlant($locationid=0) {
          if (empty(\Auth::user()->customer_id)) {
            return redirect('/home');
        }

        $user = \Auth::user();
        $user->last_loggedin = date('Y-m-d H:i:s');
        $user->save();

        $customerid = \Auth::user()->customer_id;

        $customer = Customer::find($customerid);
        
        if ($locationid > 0) {

           $reports = Report::where('customer_id','=',$customer->id)
           ->where('location_id','=',$locationid)
           ->orderBy('created_at', 'desc')->get();
        }
        else if ($locationid == -1) {
            $reports = Report::where('customer_id','=',$customer->id)->orderBy('created_at', 'desc')->get();
        }
        else {
            $reports = [];
        }
        
        $locations = Location::where('customer_id','=',$user->customer_id)->get();

        // if (in_array(\Auth::user()->role,[3])) {
        //     $reports = Report::where('customer_id','=',$customer->id)
        //     ->where('category','=','noodverlichting')
        //     ->orderBy('created_at', 'desc')->get();
        // }

        //  if (in_array(\Auth::user()->role,[4])) {
        //     $reports = Report::where('customer_id','=',$customer->id)
        //     ->where('category','!=','noodverlichting')
        //     ->orderBy('created_at', 'desc')->get();
        // }

        return view('customerdetails_forcustomer', array('customer' => $customer,'reports' => $reports,
            'locations'=>$locations));
    }

    public function showcustomer($customerid)
    {

        $customer = Customer::find($customerid);

         if (in_array(\Auth::user()->role,[1,2])) {
            $reports = Report::where('customer_id','=',$customer->id)->orderBy('created_at', 'desc')->get();
        }

        if (in_array(\Auth::user()->role,[3])) {
            $reports = Report::where('customer_id','=',$customer->id)
            ->where('category','=','noodverlichting')
            ->orderBy('created_at', 'desc')->get();
        }

         if (in_array(\Auth::user()->role,[4])) {
            $reports = Report::where('customer_id','=',$customer->id)
            ->where('category','!=','noodverlichting')
            ->orderBy('created_at', 'desc')->get();
        }

        return view('customerdetails', array('customer' => $customer,'reports' => $reports));
    }

 public function showcustomerarchive($customerid)
    {

        $customer = Customer::find($customerid);

         if (in_array(\Auth::user()->role,[1,2])) {
            $reports = Report::where('customer_id','=',$customer->id)
             ->onlyTrashed()
             ->orderBy('created_at', 'desc')->get();
        }

        if (in_array(\Auth::user()->role,[3])) {
            $reports = Report::where('customer_id','=',$customer->id)
            ->where('category','=','noodverlichting')
            ->onlyTrashed()
            ->orderBy('created_at', 'desc')->get();
        }

         if (in_array(\Auth::user()->role,[4])) {
            $reports = Report::where('customer_id','=',$customer->id)
            ->where('category','!=','noodverlichting')
             ->onlyTrashed()
            ->orderBy('created_at', 'desc')->get();
        }

        return view('customerdetails_archive', array('customer' => $customer,'reports' => $reports));
    }

    public function showLatestReports()
    {
        //todo check auth rights

        
        $reports = Report::orderBy('created_at', 'desc')->take(20)->get();
        

        return view('latestreports', array('reports' => $reports));
    }

    public function selectlocation($customerid, $type)
    {

        $customer  = Customer::find($customerid);
        $locations = $customer->locations()->orderBy('name','asc')->get();

        return view('selectlocation', array('customer' => $customer,'locations'=>$locations,'type'=>$type));

    }

      public function locationreport($customerid, $type,$locationid,$reportid = '')
    {

        $customer  = Customer::find($customerid);
        $location = Location::find($locationid);

        //$report = Report::where('location_id','=',$locationid)->first();
        $report = Report::find($reportid);
        
        if ($report === null) {
            $report = Report::create(['category'=>$type,'customer_id'=>$customer->id, 'location_id' => $location->id,'user_id'=>\Auth::user()->id]);
            return redirect('/customer/' . $customerid. '/' . $type .  '/location/' . $locationid . '/' . $report->id);

        }
        
        if ($type == 'blusmiddelen')
        return view('locationreport', array('customer' => $customer,'location'=>$location,'report'=>$report,'type'=>$type));
    
    if ($type == 'keerkleppen')
        return view('locationreport_keerkleppen', array('customer' => $customer,'location'=>$location,'report'=>$report,'type'=>$type));
    
    if ($type == 'noodverlichting')
        return view('locationreport_noodverlichting', array('customer' => $customer,'location'=>$location,'report'=>$report,'type'=>$type));

    if ($type == 'noodverlichting2')
        return view('locationreport_noodverlichtingv2', array('customer' => $customer,'location'=>$location,'report'=>$report,'type'=>$type));
        

    }



       public function locationreportarchive($customerid, $type,$locationid,$reportid = '')
    {

        $customer  = Customer::find($customerid);
        $location = Location::find($locationid);

        //$report = Report::where('location_id','=',$locationid)->first();
        $report = Report::withTrashed()->find($reportid);
        
        
        if ($type == 'blusmiddelen')
        return view('locationreport_archiveview', array('customer' => $customer,'location'=>$location,'report'=>$report,'type'=>$type));
    
    if ($type == 'keerkleppen')
        return view('locationreport_keerkleppen_archiveview', array('customer' => $customer,'location'=>$location,'report'=>$report,'type'=>$type));
    
    

    }

       public function locationreportcheckin($customerid, $type,$locationid,$reportid = '')
    {

           $customer  = Customer::find($customerid);
        $location = Location::find($locationid);

        //$report = Report::where('location_id','=',$locationid)->first();
        $report = Report::find($reportid);
           $report->locked = \Auth::user()->id ;
       $report->save();
        return redirect()->back();

    }

  public function locationreportcheckout($customerid, $type,$locationid,$reportid = '')
    {
           $customer  = Customer::find($customerid);
        $location = Location::find($locationid);

        //$report = Report::where('location_id','=',$locationid)->first();
        $report = Report::find($reportid);
        
       $report->locked = 0;
       $report->save();
         return redirect()->back();

    }

    public function userOverview() {
        return view('auth.overview',['users'=> User::all()]);
    }

     public function userCreate() {
        return view('auth.createuser',['users'=> User::all()]);
    }

      public function customerCreate() {
        return view('customer.createcustomer',['users'=> User::all()]);
    }

      public function locationCreate($id) {
        return view('customer.createlocation',['customer'=> Customer::find($id)]);
    }


  public function locationList($customerid)
    {

        $customer  = Customer::find($customerid);
        $locations = $customer->locations;

        return view('locationlist', array('customer' => $customer,'locations'=>$locations));

    }

    public function customerAdd(Request $request){ 
         
         $request->flash();

         if (empty($request->input('name'))) {
         Session::flash('error', 'Geen Klantnaam ingevuld.');
           return redirect()->back();
           
        }
        else {
        
        if (Customer::where('name','=',$request->input('name'))->exists()) {

         Session::flash('error', 'Deze klant bestaat al.');
        return redirect()->back();
       }
       else {
            $isKeerBlus = 0;
            $isNood = 0;

            if ($request->input('isKeerBlus') == '1') $isKeerBlus = 1;
            if ($request->input('isNood') == '1') $isNood = 1;
            if ($request->input('isAlarm') == '1') $isAlarm = 1; 
            if ($request->input('isBMI') == '1') $isBMI = 1;

        $customer = Customer::create([
            'name' => $request->input('name'),
            'contactperson' => $request->input('contactperson'),
            'email1' => $request->input('email'),
            'phone1' => $request->input('phone1'),
            'phone2' => $request->input('phone2'),
            'street' => $request->input('street'),
            'housenumber' => $request->input('housenumber'),
            'postalcode' => $request->input('postalcode'),
            'city' => $request->input('city'),
            'werknummer' => $request->input('werknummer'),
            'isNood' => $isNood,
            
        
        ]);
        
        

        $customer->save();
       
        return redirect('/customer/' . $customer->id); 
       } 
     }
    }

 public function updateCustomer(Request $request,$id){ 
         
         $request->flash();

         if (empty($request->input('name'))) {
         Session::flash('error', 'Geen Klantnaam ingevuld.');
           return redirect()->back();
           
        }
        else {
            $isKeerBlus = 0;
            $isNood = 0;
            $isAlarm = 0;
            $isBMI = 0;
            $can_login = 0;

            if ($request->input('isKeerBlus') == '1') $isKeerBlus = 1;
            if ($request->input('isNood') == '1') $isNood = 1;
            if ($request->input('isAlarm') == '1') $isAlarm = 1; 
            if ($request->input('isBMI') == '1') $isBMI = 1;
            
          
        $customer = Customer::find($id);      
        $customer->update([
            'name' => $request->input('name'),
            'contactperson' => $request->input('contactperson'),
            'email1' => $request->input('email'),
            'phone1' => $request->input('phone1'),
            'phone2' => $request->input('phone2'),
            'street' => $request->input('street'),
            'housenumber' => $request->input('housenumber'),
            'postalcode' => $request->input('postalcode'),
            'city' => $request->input('city'),
            'werknummer' => $request->input('werknummer'),
            'isKeerBlus' => $isKeerBlus,
            'isNood' => $isNood,
            'isAlarm' => $isAlarm,
            'isBMI' => $isBMI,
        ]);

        if ($can_login == 1 && !empty($request->input('customer_pass')) 
                            && !empty($request->input('email'))
            ) {
            
                $user = \App\User::where('customer_id','=',$customer->id)->first();
                
                if (empty($user)) {
                    
                    //check on user email for non duplicate
                    $usercheck = \App\User::where('email','=',$request->input('email'))->first();
                    
                    if (empty($usercheck)) {
                    $user = \App\User::create([
                    'name' => $request->input('name'),
                    'email' => $request->input('email'),
                    'password' =>bcrypt($request->input('customer_pass')),
                    'role' => '0',
                    'customer_id' => $customer->id,

                    ]);
                    }
                    else {
                        Session::flash('error', 'Er bestaat al een gebruiker met dit email adres, kan geen account aanmaken voor deze klant.');
                    }
                }
                else {
                    if (!empty($request->input('customer_pass'))) {
                 
                        $user->password = bcrypt($request->input('customer_pass'));
                        $user->save();
                    }
                    }
            }
            else {
                if ($can_login == 0) {
                     $user = \App\User::where('customer_id','=',$customer->id)->first();
                     if (!empty($user)) $user->forceDelete();
                     $customer->can_login = 0;
                     $customer->save();
                    //del user and set customer can login to 0

                }
              
            }
      
        $customer->save();

        $destinationPath = public_path() . DIRECTORY_SEPARATOR . 'customerlogos' . DIRECTORY_SEPARATOR . $customer->id;
        if ($request->hasFile('logo')){

        if ($request->file('logo')->isValid()) {
           
            $request->file('logo')->move($destinationPath,$request->file('logo')->getClientOriginalName());
            $customer->logo = $request->file('logo')->getClientOriginalName();
            $customer->save();
        }
       
        }
        
        return redirect('/'); 
       } 
     }



 public function locationAdd(Request $request){ 
         
         $request->flash();

         if (empty($request->input('name'))) {
         Session::flash('error', 'Geen Locatie naam ingevuld.');
           return redirect()->back();
           
        }
        else {
        
        if (Location::where('name','=',$request->input('name'))
            ->where('customer_id','=',$request->input('customer_id'))
            ->exists()) {

            Session::flash('error', 'Deze locatie bestaat al.');
            return redirect()->back();
       }
       else {
         $isKeerBlus = 0;
            $isNood = 0;

            if ($request->input('isKeerBlus') == '1') $isKeerBlus = 1;
            if ($request->input('isNood') == '1') $isNood = 1;
        $location = Location::create([
            'name' => $request->input('name'),
            'contactperson' => $request->input('contactperson'),
            'email1' => $request->input('email'),
            'phone1' => $request->input('phone1'),
            'phone2' => $request->input('phone2'),
            'street' => $request->input('street'),
            'housenumber' => $request->input('housenumber'),
            'postalcode' => $request->input('postalcode'),
            'city' => $request->input('city'),
             'customer_id' => $request->input('customer_id'),
               'isKeerBlus' => $isKeerBlus,
            'isNood' => $isNood,
        
        ]);
        
        $location->save();
        //return redirect()->back();
        return redirect('/customer/' . $request->input('customer_id') . '/location/list'); 
       } 


     }


    }

public function updateLocation(Request $request,$id){ 
         
         $request->flash();

         if (empty($request->input('name'))) {
         Session::flash('error', 'Geen Locatie naam ingevuld.');
           return redirect()->back();
           
        }
        else {
        
        if (Location::where('name','=',$request->input('name'))
            ->where('customer_id','=',$request->input('customer_id'))
            ->where('id','!=',$request->input('location_id'))
            ->exists()) {

            Session::flash('error', 'Deze locatie bestaat al.');
            return redirect()->back();
       }
       else {
         $isKeerBlus = 0;
            $isNood = 0;

            if ($request->input('isKeerBlus') == '1') $isKeerBlus = 1;
            if ($request->input('isNood') == '1') $isNood = 1;
         $location = Location::find($id);      
        $location->update([
            'name' => $request->input('name'),
            'contactperson' => $request->input('contactperson'),
            'email1' => $request->input('email'),
            'phone1' => $request->input('phone1'),
            'phone2' => $request->input('phone2'),
            'street' => $request->input('street'),
            'housenumber' => $request->input('housenumber'),
            'postalcode' => $request->input('postalcode'),
            'city' => $request->input('city'),
             'customer_id' => $request->input('customer_id'),
               'isKeerBlus' => $isKeerBlus,
            'isNood' => $isNood,
        
        ]);
        
        $location->save();
       
        return redirect('/customer/' . $request->input('customer_id') . '/location/list'); 
       } 


     }


    }


    public function saveReport(Request $request) {

        $report_id = $request->input('report_id');
          $customer_id = $request->input('customer_id');
            $location_id = $request->input('location_id');


       if (Report::where('id','=',$report_id)
            ->exists()) {
            //update

        $report = Report::find($report_id);
         $report->update([
             'json' => str_replace('\n', '',$request->input('inputdata')),
         ]);
         return $report_id;
       }
       else {
            //create  
         $report = Report::create([
             'json' => str_replace('\t','',str_replace('\n', '',$request->input('inputdata'))),
             'customer_id' => $customer_id,
             'location_id' => $location_id,
             'user_id' => $request->input('user_id'),
             'category' => $request->input('category'),  
         ]);
        

        $report->save();
       
        return $report->id;
    }



    }

      public function saveready(Request $request) {

        $ready =  $request->input('inputdata');
        $report_id = $request->input('report_id');
          $customer_id = $request->input('customer_id');
            $location_id = $request->input('location_id');

            
       if (Report::where('id','=',$report_id)
            ->exists()) {
            //update
        $currentdate = date('Y-m-d H:i:s');
            if ($ready == 0) {
                $currentdate = null;
            }
         $report = Report::find($report_id);
        
         $report->report_ready = $currentdate;
         $report->save();
        

         return $ready;
       }
       
       return;

    }

     public function reportPdf($id){
ini_set('max_execution_time', 0);
        $report = Report::find($id);
        if (empty($report->json)) {
          $json = null;
        }
        else {
        $json =json_decode($report->json);
      }
      //echo '<pre>';print_r($json);

      //calc gekeurd/afgekeur
      $gecontroleerd = 0;
      $gekeurd = 0;
      $arrjson = array();
      if ($json == null) return;
    
      foreach ($json as $row) {
      
        $gecontroleerd++;

        if ($report->category == 'noodverlichting' || $report->category == 'noodverlichting2') {

              if (!empty($row->ok) && $row->ok == 'Ja') {
                $gekeurd++;
            }
            else if (!empty($row->{'ok'}) && $row->{'ok'} == 'Ja') {
                $gekeurd++;
                        
            }
        }
        else {
            if (!empty($row->yes_no) && $row->yes_no == 'Ja') {
            $gekeurd++;
            }
        else if (!empty($row->{'yes-no'}) && $row->{'yes-no'} == 'Ja') 
            $gekeurd++;
        }
    }
      $new = array();
      $arrjson = json_decode($report->json,false);
      foreach ($arrjson as $row) {
          $new[] = $row;
      }

     //$this->sksort2($new,'pos',true);
            
     usort($new, function($a, $b)
     {
         return strnatcmp($a->pos, $b->pos); // return (0 if ==), (-1 if <), (1 if >)
     });

     $new2 = json_encode($new);
    
        $data = ['report'=>$report,'rows'=>json_decode($new2,true), 'gecontroleerd'=> $gecontroleerd,'afgekeurd' => $gecontroleerd-$gekeurd];

        $pdf = PDF::loadView('PDF.' . $report->category, $data);
        $pdf->setPaper('a4','landscape');
        //$pdf->save('file1.pdf');
          $customer = Customer::find($report->customer_id);
        $location = Location::find($report->location_id);
      
        $basename = 'rapportage_' . trim($report->category) . '_' . trim($customer->name) . '_'. trim($location->name) . '_' . date('Y') . '.pdf';
        
        $filename = str_replace('\xc2\xa0','_',$basename);
        $filename = str_replace(' ','_',$filename);
        $filename = trim(preg_replace('/[\x00-\x1F\x7F\xA0]/u', "", $filename));

       return $pdf->download(str_replace('&nbsp;','_',$filename));
     
    }

    public function reportPdf2($id){
        ini_set('max_execution_time', 0);
                $report = Report::find($id);
                if (empty($report->json)) {
                  $json = null;
                }
                else {
                $json =json_decode($report->json);
              }
              //echo '<pre>';print_r($json);
        
              //calc gekeurd/afgekeur
              $gecontroleerd = 0;
              $gekeurd = 0;
              $arrjson = array();
              if ($json == null) return;
            
              foreach ($json as $row) {
              
                $gecontroleerd++;
        
                if ($report->category == 'noodverlichting' || $report->category == 'noodverlichting2') {
        
                      if (!empty($row->ok) && $row->ok == 'Ja') {
                        $gekeurd++;
                    }
                    else if (!empty($row->{'ok'}) && $row->{'ok'} == 'Ja') {
                        $gekeurd++;
                                
                    }
                }
                else {
                    if (!empty($row->yes_no) && $row->yes_no == 'Ja') {
                    $gekeurd++;
                    }
                else if (!empty($row->{'yes-no'}) && $row->{'yes-no'} == 'Ja') 
                    $gekeurd++;
                }
            }
              $new = array();
              $arrjson = json_decode($report->json,false);
              foreach ($arrjson as $row) {
                  $new[] = $row;
              }
        
             //$this->sksort2($new,'pos',true);
            
             usort($new, function($a, $b)
             {
                 return strnatcmp($a->pos, $b->pos); // return (0 if ==), (-1 if <), (1 if >)
             });

             $new2 = json_encode($new);
            
                $data = ['report'=>$report,'rows'=>json_decode($new2,true), 'gecontroleerd'=> $gecontroleerd,'afgekeurd' => $gecontroleerd-$gekeurd];
        
                $pdf = PDF::loadView('PDF.' . $report->category, $data);
                $pdf->setPaper('a4','landscape');
                //$pdf->save('file1.pdf');
                  $customer = Customer::find($report->customer_id);
                $location = Location::find($report->location_id);
              
                $basename = 'rapportage_' . trim($report->category) . '_' . trim($customer->name) . '_'. trim($location->name) . '_' . date('Y') . '.pdf';
                
                $filename = str_replace('\xc2\xa0','_',$basename);
                $filename = str_replace(' ','_',$filename);
                $filename = trim(preg_replace('/[\x00-\x1F\x7F\xA0]/u', "", $filename));
        
               return $pdf->download(str_replace('&nbsp;','_',$filename));
            
            }
//      $pdf2 = PDF::loadView('PDF.test', $data);
//      $pdf2->setPaper('a4','landscape');
//     $pdf2->save('file2.pdf');
//     // return $pdf->stream("file.pdf", array('Attachment' => false));


// //$pdf = PDF::loadView('PDF.test', $data);
// //return $pdf->download('invoice.pdf');
// //return $pdf->stream('invoice.pdf');
// $path = '';
// $path2 = '';
// $path=public_path() . '\file1.pdf';
// $path2=public_path() . '\file2.pdf';
// $filename = 'm6.pdf'; 
// $pathout = public_path().DIRECTORY_SEPARATOR.$filename;

//  echo $command = '"C:\Program Files (x86)\PDFtk Server\bin\pdftk.exe" '. $path . ' '. $path2 .' cat output ' . $pathout;
//  exec($command);

// //$pdfout = PDF::loadFile($pathout);//->download('test.pdf');
// // $pdftk->send(public_path() . '/file1.pdf');

// return Response::make(file_get_contents($pathout), 200, [
//     'Content-Type' => 'application/pdf',
//     'Content-Disposition' => 'inline; '.$filename,
// ]);

// //route
//     }

function sksort(&$array, $subkey="id", $sort_ascending=false) {
    $temp_array = array();
        if (count((array)$array))
            $temp_array[key($array)] = array_shift($array);
    
        foreach($array as $key => $val){
            $offset = 0;
            $found = false;
            foreach($temp_array as $tmp_key => $tmp_val)
            {
                if(!$found and strtolower($val[$subkey]) > strtolower($tmp_val[$subkey]))
                {
                    $temp_array = array_merge(    (array)array_slice($temp_array,0,$offset),
                                                array($key => $val),
                                                array_slice($temp_array,$offset)
                                              );
                    $found = true;
                }
                $offset++;
            }
            if(!$found) $temp_array = array_merge($temp_array, array($key => $val));
        }
    
        if ($sort_ascending) $array = array_reverse($temp_array);
    
        else $array = $temp_array;
    }


    function sksort2(&$array, $subkey="id", $sort_ascending=false) {
        $temp_array = array();
            if (count((array)$array))
                $temp_array[key($array)] = array_shift($array);
        
            foreach($array as $key => $val){
                $offset = 0;
                $found = false;
                foreach($temp_array as $tmp_key => $tmp_val)
                {
                    if(!$found and strtolower($val[$subkey]) > strtolower($tmp_val[$subkey]))
                    {
                        $temp_array = array_merge(    (array)array_slice($temp_array,0,$offset),
                                                    array($key => $val),
                                                    array_slice($temp_array,$offset)
                                                  );
                        $found = true;
                    }
                    $offset++;
                }
                if(!$found) $temp_array = array_merge($temp_array, array($key => $val));
            }
        
            if ($sort_ascending) $array = array_reverse($temp_array);
        
            else $array = $temp_array;
        }


    public function blusbrand() {

        $brands = \App\Brand::where('category','=','blus')->get();

        return view('lists.blusbrand', array('brands'=>$brands));
        
    }

 public function blusstof($id=0) {

    //first select brand
        $brands = \App\Brand::where('category','=','blus')->get();
        $blusstof = \App\Type::where('category','=','blus')
        ->where('brand_id','=',$id)
        ->get();

        return view('lists.blusstof', array('brands'=>$brands,'blusstoffen'=>$blusstof,'brandid'=>$id));
        
    }

 public function blustype($brandid = 0,$blusstofid = 0) {
//material
        $brands = \App\Brand::where('category','=','blus')->get();
       
        $blusstof = \App\Type::where('category','=','blus')
        ->where('brand_id','=',$brandid)
        ->get();

         $types = \App\Material::where('brand_id','=',$brandid)
        ->where('type_id','=',$blusstofid)
        ->get();


        return view('lists.type', array('brands'=>$brands,'blusstoffen'=>$blusstof,'types'=>$types,'brandid'=>$brandid,'blusstofid'=>$blusstofid));
       
        
    }


 public function getlist($model,$filter) {

        $brands = \App::make('\App\\' . $model)
        ->where('category','=',$filter)
        ->orderBy('name', 'ASC')
        ->get();


        return view('lists.list', array('brands'=>$brands,'model'=>$model,'filter'=>$filter));
        
    }

 public function anyLog(Request $request) {

        $report_id = $request->input('report_id');
        $customer_id = $request->input('customer_id');
         $location_id = $request->input('location_id');

         $log = Log::create([
             'json' => str_replace('\n', '',$request->input('inputdata')),
             'customer_id' => $customer_id,
             'location_id' => $location_id,
              'report_id' => $report_id,
             'user_id' => $request->input('user_id'),
             'category' => $request->input('category'),  
         ]);
        

        $log->save();
       
        return $log->id;
    }


    

}
