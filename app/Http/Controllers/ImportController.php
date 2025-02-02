<?php

namespace App\Http\Controllers;

use App\Customer;
use App\Location;
use App\Report;
use PDF;
use Response;
use Session;
use Illuminate\Http\Request;
use App\User;
use Carbon\Carbon;

class ImportController extends Controller
{
	private $customer_id = 0;
	private $location_id = 0;
	private $location_name = '';
	private $report = null;
	private $year = 2015;

	private $street = '';
	private $locname = '';
	private $customername = '';
	private $lines = [];


public function index() {
	ini_set('memory_limit','-1');
	ini_set('max_execution_time', 300);
	//recursive file
	$path = storage_path('/keer5/');
	//$this->listFolderFiles($basepath);
	$objects = new \RecursiveIteratorIterator(
    new \RecursiveDirectoryIterator($path),
    \RecursiveIteratorIterator::SELF_FIRST
	);
	
	foreach ($objects as $file => $object) {
    	$basename = $object->getBasename();
    	if ($basename == '.' or $basename == '..') {
        continue;
    }
    	if ($object->isDir()) {
        continue;
    }
    //$fileData[] = $object->getPathname();
		$this->keer($object->getPathname());

	}
	// echo '<pre>';
	// print_r($fileData);
}

// private function listFolderFiles($dir){
//  $ffs = scandir($dir);
//     echo '<ol>';
//     foreach($ffs as $ff){
//         if($ff != '.' && $ff != '..'){
            
//             if(is_dir($dir.'/'.$ff)) {
//             	$this->listFolderFiles($dir.'/'.$ff);
//             }
//             else {
// 				//got file           	
//             	echo $ff;
//             }
//         }
//     }
//     echo '</ol>';
// }


public function keer($file)
    {
        
    	$row = 1;
		//$file = storage_path('/keer/Cello/Haaren/Locatielijst Bolakker 2 en 4.CSV');
	
		if (($handle = fopen($file, "r")) !== FALSE) {

		    while (($data = fgetcsv($handle, 1000, ";")) !== FALSE) {
		       
		        
		        if ($row == 7) {
		        	$this->lookupKCustomer($data);
		        }
		        
		        if ($row == 8) { //loc name and date
		        	$this->lookupKLocation($data);
		        }

		         if ($row == 9) { //street etc
		        	$this->lookupKStreet($data);
		        }

		        if ($row >= 13) {
		        	$this->parseKLines($data);
		        }
		        
		        $row++;
		        
		    }

	    fclose($handle);
		}


		if (empty($this->customername) && $this->customer_id == -1) {
			//
		}
		else {
			// echo '<pre>';
			$json = json_encode($this->lines);

				if ($this->customer_id == -1) {
					//create customer
					 $customer = Customer::create([
		            'name' => $this->customername,
		            'street' => $this->street  
		        ]);
					 $this->customer_id = $customer->id;
			}

			if ($this->location_id == -1) {

				 $location = Location::create([
				 	'name' => $this->location_name . ' | ' . $this->street,
		            'street' => $this->street,
		            'city' => $this->location_name,
		            'customer_id' => $this->customer_id 
		        ]);
					 $this->location_id = $location->id;

			}


			$report = Report::create(['category'=>'keerkleppen','customer_id'=>$this->customer_id, 'location_id' => $this->location_id,'user_id'=>0,'json'=> $json,'report_ready'=>date('Y-m-d H:i:s'),"created_at"=>'2015-01-01 00:00:00']);
    	}

	}

 	public function blus($file)
    {
        
    	$row = 1;
		//$file = storage_path('/blusconvert/Cello/Haaren/Locatielijst Akkerstraat 16-18.CSV');
	
		if (($handle = fopen($file, "r")) !== FALSE) {

		    while (($data = fgetcsv($handle, 1000, ";")) !== FALSE) {
		       
		        
		        if ($row == 8) {
		        	$this->lookupCustomer($data[1]);
		        }
		        
		        if ($row == 9) { //loc name and date
		        	$this->lookupLocation($data);
		        }

		         if ($row == 10) { //street etc
		        	$this->lookupStreet($data);
		        }

		        if ($row >= 12) {
		        	$this->parseLines($data);
		        }
		        
		        $row++;
		        
		    }

	    fclose($handle);
		}


		// echo '<pre>';
		$json = json_encode($this->lines);

			if ($this->customer_id == -1) {
				//create customer
				 $customer = Customer::create([
	            'name' => $this->customername,
	            'street' => $this->street  
	        ]);
				 $this->customer_id = $customer->id;
		}

		if ($this->location_id == -1) {

			 $location = Location::create([
			 	'name' => $this->location_name . ' | ' . $this->street,
	            'street' => $this->street,
	            'city' => $this->location_name,
	            'customer_id' => $this->customer_id 
	        ]);
				 $this->location_id = $location->id;

		}


		$report = Report::create(['category'=>'blusmiddelen','customer_id'=>$this->customer_id, 'location_id' => $this->location_id,'user_id'=>0,'json'=> $json,'report_ready'=>date('Y-m-d H:i:s'),"created_at"=>'2015-01-01 00:00:00']);
    }

   private function right($string,$chars) 
{ 
    $vright = substr($string, strlen($string)-$chars,$chars); 
    return $vright; 
    
} 

    private function lookupCustomer($customername) {
    	$customer = Customer::where('name','=',$customername)->first();

    	if (empty($customer)) {
    		$this->customer_id = -1;
    		$this->customername = $customername;
    	}
    	else {
    		$this->customer_id = $customer->id;
   		}
    }

	private function lookupLocation($data) {
    	$location = Location::where('name','=',$data[1])->first();

    	if (empty($location)) {
    		$this->location_name = $data[1];
    		$this->location_id = -1;
    	}
    	else {
    		$this->location_id = $location->id;
   		}
    	
    	$this->year = $this->right($data[4],4);
     }

    private function lookupStreet($data) {
    	$location = Location::where('street','=',$data[1])->first();

    	if ($this->location_id == -1) {
	    	if (empty($location)) {
	    		$this->street = $data[1];
	    		$this->location_id = -1;
	    	}
	    	else {
	    		$this->location_id = $location->id;
	   		}
   		}
 
    }

    private function parseLines($data) {
    	 $num = count($data);
    	 if (!(empty($data[0]) || $data[0] == '  Ev. Opmerkingen:')) {
    	// for ($c=0; $c < $num; $c++) {
		   //          echo $data[$c] . " | ";
		   //      }
		   //      echo '<br>';
		    
    	 	$this->lines[$this->generateRowID()] = ["pos"=>$data[0],
    	 	"location"=>htmlspecialchars($data[1],ENT_QUOTES),
    	 	"brand"=>htmlspecialchars($data[2],ENT_QUOTES),
    	 	"type"=>htmlspecialchars($data[4],ENT_QUOTES),
    	 	"material"=>htmlspecialchars($data[3],ENT_QUOTES),
    	 	"fabrication_year"=>htmlspecialchars($data[5],ENT_QUOTES),
    	 	"last_sealed"=>htmlspecialchars($data[6],ENT_QUOTES),
    	 	"debiet_measure"=>str_replace(" l/min","",$data[7]),
    	 	"yes-no" => 'Ja',
    	 	"remarks" => '',
    	 	];
		    }

    }

 private function lookupKCustomer($data) {

 		$namecheck = str_replace("Klant:","", $data[0]);
 		$namecheck = trim($namecheck);
 		if (empty($namecheck)) {
 			$namecheck = $data[2];
 		}

    	$customer = Customer::where('name','=',$namecheck)->first();

    	if (empty($customer)) {
    		$this->customer_id = -1;
    		$this->customername = $namecheck;
    	}
    	else {
    		$this->customer_id = $customer->id;
   		}
    }

	private function lookupKLocation($data) {

		$loccheck = str_replace("Locatie:","", $data[0]);
		$loccheck = trim($loccheck);
 		if (empty($loccheck)) {
 			$loccheck = $data[2];
 		}

    	$location = Location::where('name','=',$loccheck)->first();

    	if (empty($location)) {
    		$this->location_name = $loccheck;
    		$this->location_id = -1;
    	}
    	else {
    		$this->location_id = $location->id;
   		}
    	
    	//$this->year = $this->right($data[4],4);
     }

    private function lookupKStreet($data) {
    	$address = str_replace("Adres:","", $data[0]);
    	$address = trim($address);
    	$location = Location::where('street','=',$address)->first();

    	if ($this->location_id == -1) {
	    	if (empty($location)) {
	    		$this->street =$address;
	    		$this->location_id = -1;
	    	}
	    	else {
	    		$this->location_id = $location->id;
	   		}
   		}
 
    }

    private function parseKLines($data) {
    	
    	 $num = count($data);
    	 if (!(empty($data[0]) || $data[0] == 'Keerklep:')) {
    	// for ($c=0; $c < $num; $c++) {
		   //          echo $data[$c] . " | ";
		   //      }
		   //      echo '<br>';

		    if (isset($data[8])) {
		        
		        $fitting = $data[8];
		        switch (strtolower($fitting)) {
		        	case 'knel':
		        		 $fitting = 'Knel';
		        		break;
		        	case 'schroef':
		        		$fitting = 'Schroef';
		        		break;
		        	case 'cap':
		        		$fitting = 'Capillair';
		        		break;
		        	case 'pers':
		        		$fitting = 'Pers';
		        		break;
		        	
		        }
		       }
		       else {
		       	$fitting = '';
		      }


		      if (isset($data[3])) {
		      	$brand = $data[3];
		      }
		      else {
		      	$brand = '';
		      }

 			if (isset($data[4])) {
		      	$type = $data[4];
		      }
		      else {
		      	$type = '';
		      }

		      if (isset($data[5])) {
		      	$result = $data[5];
		      }
		      else {
		      	$result = '';
		      }

		      if (isset($data[7])) {
		      	$diameter = str_replace("'", "",$data[7]);
		      }
		      else {
		      	$diameter = '';
		      }

    	 	$this->lines[$this->generateRowID()] = [
    	 	"pos"=>htmlspecialchars($data[0],ENT_QUOTES),
    	 	"etage"=>htmlspecialchars($data[1],ENT_QUOTES),
    	 	"location"=>htmlspecialchars($data[2],ENT_QUOTES),
    	 	"brand"=>htmlspecialchars($brand,ENT_QUOTES),
    	 	"type"=>htmlspecialchars($type,ENT_QUOTES),
    	 	"result"=>htmlspecialchars($result,ENT_QUOTES),
    	 	"fabrication_year"=>htmlspecialchars($fitting,ENT_QUOTES),
    	 	"diameter"=>htmlspecialchars(str_replace("Ø", "",$diameter),ENT_QUOTES),
    	 	"drukvast"=>'Ja',
    	 	"yes-no" => 'Ja',
    	 	"remarks" => '',
    	 	];
		    }
		

    }


    private function generateRowID() {
    	return uniqid();
    }

}
