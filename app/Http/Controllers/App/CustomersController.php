<?php
/*
Project Name: IonicEcommerce
Project URI: http://ionicecommerce.com
Author: VectorCoder Team
Author URI: http://vectorcoder.com/
Version: 2.1
*/
namespace App\Http\Controllers\App;

//validator is builtin class in laravel
use Validator;

use Mail;
use DB;
//for password encryption or hash protected
use Hash;

//for authenitcate login data
use Auth;
use Illuminate\Foundation\Auth\ThrottlesLogins;

//for requesting a value 
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

//for Carbon a value 
use Carbon;

class CustomersController extends Controller
{
	
    /**
     * Create a new controller instance.
     *
     * @return void
     */
   /* public function __construct()
    {
        $this->middleware('auth');
    }*/

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
		

		public function test()
		{
			echo " testing of getcategories";
		}
	//login
	public function processLogin(Request $request){
		
		$customers_email_address = $request->customers_email_address;
		$customers_password      = $request->customers_password;
		
		$existUser = DB::table('customers')->where('customers_email_address', $customers_email_address)->where('customers_password', $customers_password)->where('isActive', '1')->get();
		
		if(count($existUser)>0){
				
			$customers_id = $existUser[0]->customers_id;
			
			//update record of customers_info			
			$existUserInfo = DB::table('customers_info')->where('customers_info_id', $customers_id)->get();
			$customers_info_id 							= $customers_id;
			$customers_info_date_of_last_logon  		= date('Y-m-d H:i:s');
			$customers_info_number_of_logons     		= '1';
			$customers_info_date_account_created 		= date('Y-m-d H:i:s');
			$global_product_notifications 				= '1';
			
			if(count($existUserInfo)>0){
				//update customers_info table
				DB::table('customers_info')->where('customers_info_id', $customers_info_id)->update([
					'customers_info_date_of_last_logon' => $customers_info_date_of_last_logon,
					'global_product_notifications' => $global_product_notifications,
					'customers_info_number_of_logons'=> DB::raw('customers_info_number_of_logons + 1')
				]);
				
			}else{
				//break;
				//insert customers_info table
				$customers_default_address_id = DB::table('customers_info')->insertGetId(
					 ['customers_info_id' => $customers_info_id,
					  'customers_info_date_of_last_logon' => $customers_info_date_of_last_logon,
					  'customers_info_number_of_logons' => $customers_info_number_of_logons,
					  'customers_info_date_account_created' => $customers_info_date_account_created,
					  'global_product_notifications' => $global_product_notifications
					 ]
				);	
				
				DB::table('customers')->where('customers_id', $customers_id)->update([
					'customers_default_address_id' => $customers_default_address_id	
				]);
			}		
			
			//check if already login or not
			$already_login = DB::table('whos_online')->where('customer_id', '=', $customers_id)->get();
									
			if(count($already_login)>0){
				DB::table('whos_online')
					->where('customer_id', $customers_id)
					->update([
							'full_name'  => $existUser[0]->customers_firstname.' '.$existUser[0]->customers_lastname,
							'time_entry'   => date('Y-m-d H:i:s'),							
					]);
			}else{
				DB::table('whos_online')
					->insert([
							'full_name'  => $existUser[0]->customers_firstname.' '.$existUser[0]->customers_lastname,
							'time_entry' => date('Y-m-d H:i:s'),
							'customer_id'    => $customers_id							
					]);
			}			
			
			//get liked products id
			$products = DB::table('liked_products')->select('liked_products_id as products_id')
			->where('liked_customers_id', '=', $customers_id)
			->get();
			
			if(count($products)>0){
				$liked_products = $products;
			}else{
				$liked_products = array();
			}
			
			$existUser[0]->liked_products = $products;
			
			
			$responseData = array('success'=>'1', 'data'=>$existUser, 'message'=>'Data has been returned successfully!');
			$userResponse = json_encode($responseData);
			
		}else{
			$existUser = array();
			$responseData = array('success'=>'0', 'data'=>$existUser, 'message'=>"Invalid email or password");
			$userResponse = json_encode($responseData);
		}			
			print $userResponse;	
	}
		
	//registration 
	public function processRegistration(Request $request){
		
		$customers_firstname            		=   $request->customers_firstname;
		$customers_lastname           			=   $request->customers_lastname;			
		$customers_email_address    		    =   $request->customers_email_address;
		$customers_password          		    =   $request->customers_password;	
		$customers_telephone        		    =   $request->customers_telephone;
		$customers_picture        		   		=   $request->customers_picture;
		$customers_info_date_account_created 	=   date('y-m-d h:i:s');	
		
		
		if(!empty($customers_picture)){
			$image = substr($customers_picture, strpos($customers_picture, ",") + 1);
			$img = base64_decode($image);
			$dir="resources/assets/images/user_profile/";
			if (!file_exists($dir) and !is_dir($dir)) {
				mkdir($dir);
			} 
			$uploadfile = $dir."/pic_".time().".jpg";
			file_put_contents(base_path().'/'.$uploadfile, $img);
			$profile_photo=$uploadfile;
		}else{
			$profile_photo="resources/assets/images/user_profile/default_user.svg";	
		}
		
		//check email existance
		$existUser = DB::table('customers')->where('customers_email_address', $customers_email_address)->get();	
		
		if(count($existUser)=="1"){	
			//response if email already exit	
			$postData = array();
			$responseData = array('success'=>'0', 'data'=>$postData, 'message'=>"Email address is already exist");
			$userResponse = json_encode($responseData);
			print $userResponse;
		}else{
			$customer_data = array(
				'customers_firstname'			 =>  $customers_firstname,
				'customers_lastname'			 =>  $customers_lastname,
				'customers_telephone'			 =>  $customers_telephone,
				'customers_email_address'		 =>  $customers_email_address,
				'customers_password'			 =>  $customers_password,
				'customers_picture'				 =>  $profile_photo,
				'isActive'						 =>  '1',
				'created_at'					 =>	 time()
			);
							
			//insert data into customer
			$customers_id = DB::table('customers')->insertGetId($customer_data);
			/*if(!empty($customers_id)){
								
				$customers_info_data = array(
					'customers_info_date_account_created'   =>   $customers_info_date_account_created,
					'customers_info_id'             		=>   $customers_id,
				);	
				
				DB::table('customers_info')->insertGetId($customers_info_data);				
			}*/
			
			
			$userData = DB::table('customers')->where('customers_id', '=', $customers_id)->get();
			
			$responseData = array('success'=>'1', 'data'=>$userData, 'message'=>"Sign Up successfully!");
			$userResponse = json_encode($responseData);
			print $userResponse;
			
			
			Mail::send('/mail/createAccount', ['userData' => $userData], function($m) use ($userData){
				$m->to($userData[0]->customers_email_address)->subject('Welcome to Ecommerce App"')->getSwiftMessage()
				->getHeaders()
				->addTextHeader('x-mailgun-native-send', 'true');	
			});
		}	
	}
	
	//notify me
	public function notify_me(Request $request){
		
		$device_id 			=  $request->device_id;
		$is_notify 			=  $request->is_notify;
		
		$devices = DB::table('devices')->where('device_id', $device_id)->get();
		if(!empty($devices[0]->customers_id)){
		$customers = DB::table('customers')->where('customers_id', $devices[0]->customers_id)->get();	
		
		if(count($customers>0)){
		
			foreach($customers as $customers_data){
				
				DB::table('devices')->where('customers_id', $customers_data->customers_id)->update([
					'is_notify'   =>   $is_notify,
					]);	
			}
			
		}
		}else{
			
			DB::table('devices')->where('device_id', $device_id)->update([
					'is_notify'   =>   $is_notify,
					]);	
		}
		
		$responseData = array('success'=>'1', 'data'=>'',  'message'=>"Notification setting has been changed successfully!");
		$categoryResponse = json_encode($responseData);
		print $categoryResponse;
	}
	
	//update profile 
	public function updateCustomerInfo(Request $request){
		
		$customers_id            					=   $request->customers_id;
		$customers_firstname            			=   $request->customers_firstname;
		$customers_lastname           				=   $request->customers_lastname;		
		$customers_fax          		   			=   $request->customers_fax;	
		$customers_newsletter          		   		=   $request->customers_newsletter;	
		$customers_telephone          		   		=   $request->customers_telephone;	
		$customers_gender          		   			=   $request->customers_gender;	
		$customers_dob          		   			=   $request->customers_dob;
		$customers_picture        		   			=   $request->customers_picture;
		$customers_old_picture        		   		=   $request->customers_old_picture;		
		
		$customers_info_date_account_last_modified 	=   date('y-m-d h:i:s');	
		
		//customer picture
		if(!empty($customers_picture)){
			$image = substr($customers_picture, strpos($customers_picture, ",") + 1);
			$img = base64_decode($image);
			$dir="resources/assets/images/user_profile/";
			if (!file_exists($dir) and !is_dir($dir)) {
				mkdir($dir);
			} 
			$uploadfile = $dir."/pic_".time().".jpg";
			file_put_contents(base_path().'/'.$uploadfile, $img);
			$customers_picture = $uploadfile;
		}else{
			$customers_picture = $customers_old_picture;
		}
		
		if(!empty($request->customers_password)){
			
			$customer_data = array(
				'customers_firstname'			 =>  $customers_firstname,
				'customers_lastname'			 =>  $customers_lastname,
				'customers_fax'					 =>  $customers_fax,
				'customers_newsletter'			 =>  $customers_newsletter,
				'customers_telephone'			 =>  $customers_telephone,
				'customers_gender'				 =>  $customers_gender,
				'customers_dob'					 =>  $customers_dob,
				'customers_picture'				 =>  $customers_picture,
				'customers_password'  			 =>  $request->customers_password,
			);
			
		}else{
			
			$customer_data = array(
				'customers_firstname'			 =>  $customers_firstname,
				'customers_lastname'			 =>  $customers_lastname,
				'customers_fax'					 =>  $customers_fax,
				'customers_newsletter'			 =>  $customers_newsletter,
				'customers_telephone'			 =>  $customers_telephone,
				'customers_gender'				 =>  $customers_gender,
				'customers_dob'					 =>  $customers_dob,
				'customers_picture'				 =>  $customers_picture,
			);
			
		}
						
		//update into customer
		DB::table('customers')->where('customers_id', $customers_id)->update($customer_data);
				
		DB::table('customers_info')->where('customers_info_id', $customers_id)->update(['customers_info_date_account_last_modified'   =>   $customers_info_date_account_last_modified]);	
		
		$userData = DB::table('customers')->where('customers_id', '=', $customers_id)->get();
		$responseData = array('success'=>'1', 'data'=>$userData, 'message'=>"Customer information has been Updated successfully");
		$userResponse = json_encode($responseData);
		
		print $userResponse;
		
	}
	
	public function updateCustomerPassword(Request $request){
		$customers_id            					=   $request->customers_id;	
		$customers_password							=   $request->customers_password;
		//$customers_email_address    		   		=   $request->customers_email_address;
		$customers_info_date_account_last_modified 	=   date('y-m-d h:i:s');	
		
		$customer_data = array(
			'customers_password'			 =>  $customers_password,
		);
						
		//update into customer
		$userData = DB::table('customers')->where('customers_id', $customers_id)->update($customer_data);
				
		DB::table('customers_info')->where('customers_info_id', $customers_id)->update(['customers_info_date_account_last_modified'   =>   $customers_info_date_account_last_modified]);	
		
		$userData = array();
		$responseData = array('success'=>'1', 'data'=>$userData, 'message'=>"password has been updated successfully");
		$userResponse = json_encode($responseData);
		
		print $userResponse;
		
	}
	
	
	//forgot password
	public function processForgotPassword(Request $request){
		
		$customers_email_address    		  =   $request->customers_email_address;
		$postData = array();
		
		$responseData = array('success'=>'1', 'data'=>$postData, 'message'=>"Your password has been sent to your email address.");
		
		//check email exist
		$existUser = DB::table('customers')->where('customers_email_address', $customers_email_address)->get();
				
		if(count($existUser)>0){
						
			Mail::send('/mail/recoverPassword', ['existUser' => $existUser], function($m) use ($existUser){
				$m->to($existUser[0]->customers_email_address)->subject('Ionic Ecommerce Password Recovery')->getSwiftMessage()
				->getHeaders()
				->addTextHeader('x-mailgun-native-send', 'true');	
			});
			
			$responseData = array('success'=>'1', 'data'=>$postData, 'message'=>"Your password has been sent to your email address.");
			$userResponse = json_encode($responseData);			
			print $userResponse;
		}else{	
			$responseData = array('success'=>'0', 'data'=>$postData, 'message'=>"Email address doesn't exist!");
			$userResponse = json_encode($responseData);			
			print $userResponse;
		}		
	}
	
	//facebookRegistration 
	public function facebookRegistration(Request $request){
		 require_once app_path('vendor/autoload.php');
		 
		 //get function from other controller
		 $myVar = new AppSettingController();
		 $setting = $myVar->getSetting();
		 
		 $customers_password = $this->createRandomPassword();	
		 $access_token = $request->access_token;		 
		 
		 $fb = new \Facebook\Facebook([
		  'app_id' => $setting[0]->facebook_app_id,
		  'app_secret' => $setting[0]->facebook_secret_id,
		    'default_graph_version' => 'v2.2',
		  ]);
		  
		try {
		   $response = $fb->get('/me?fields=id,name,email,first_name,last_name,gender,public_key', $access_token);
		} catch(Facebook\Exceptions\FacebookResponseException $e) {
		   echo 'Graph returned an error: ' . $e->getMessage();
		} catch(Facebook\Exceptions\FacebookSDKException $e) {
		   echo 'Facebook SDK returned an error: ' . $e->getMessage();
		}	
		
		$user = $response->getGraphUser();
		
		$fb_id = $user['id'];		
		$customers_firstname = $user['first_name'];
		$customers_lastname = $user['last_name'];
		$name = $user['name'];
		if($user['gender']=='male'){
			$customers_gender = '0';
		}else{
			$customers_gender = '1';
		}
		if(!empty($user['email'])){
			$customers_email_address = $user['email'];	
		}else{
			$customers_email_address = '';
		}
		//not work on wamp server	
		$img = file_get_contents('https://graph.facebook.com/'.$fb_id.'/picture?type=large&access_token='.$access_token);
		$dir="resources/assets/images/user_profile/";
		if (!file_exists($dir) and !is_dir($dir)) {
			mkdir($dir);
		} 
		$uploadfile = $dir."/pic_".time().".jpg";
		$temp_upload_path = base_path().'/'.$uploadfile;
		file_put_contents($temp_upload_path, $img);
		$profile_photo=$uploadfile;
	
		//user information
		$customer_data = array(
			'customers_firstname' => $customers_firstname,
			'fb_id' => $fb_id,
			'customers_lastname' => $customers_lastname,
			'customers_email_address' => $customers_email_address,
			'customers_password' => $customers_password,
			'isActive' => '1',
			'customers_picture' => $profile_photo,
			'created_at' =>	 time()
		);
		
		$existUser = DB::table('customers')->where('fb_id', '=', $fb_id)->orWhere('customers_email_address', '=', $customers_email_address)->get();
		if(count($existUser)>0){
			
			$customers_id = $existUser[0]->customers_id;
			$success = "2";
			$message = "Customer record has been updated.";
			//update data of customer
			DB::table('customers')->where('customers_id','=',$customers_id)->update($customer_data);
		}else{
			$success = "1";
			$message = "Customer account has been created.";
			//insert data of customer
			$customers_id = DB::table('customers')->insertGetId($customer_data);
		}
		//print $customers_id;
		$userData = DB::table('customers')->where('customers_id', '=', $customers_id)->get();
		
		//update record of customers_info			
		$existUserInfo = DB::table('customers_info')->where('customers_info_id', $customers_id)->get();
		$customers_info_id 							= $customers_id;
		$customers_info_date_of_last_logon  		= date('Y-m-d H:i:s');
		$customers_info_number_of_logons     		= '1';
		$customers_info_date_account_created 		= date('Y-m-d H:i:s');
		$global_product_notifications 				= '1';
		
		if(count($existUserInfo)>0){
			//update customers_info table
			DB::table('customers_info')->where('customers_info_id', $customers_info_id)->update([
				'customers_info_date_of_last_logon' => $customers_info_date_of_last_logon,
				'global_product_notifications' => $global_product_notifications,
				'customers_info_number_of_logons'=> DB::raw('customers_info_number_of_logons + 1')
			]);
			
		}else{
			
			//insert customers_info table
			$customers_default_address_id = DB::table('customers_info')->insertGetId([
					'customers_info_id' => $customers_info_id,
					'customers_info_date_of_last_logon' => $customers_info_date_of_last_logon,
					'customers_info_number_of_logons' =>  $customers_info_number_of_logons,
					'customers_info_date_account_created' => $customers_info_date_account_created,
					'global_product_notifications' => $global_product_notifications
			]);	
			
		}		
		
		//check if already login or not
		$already_login = DB::table('whos_online')->where('customer_id', '=', $customers_id)->get();	
		if(count($already_login)>0){
			DB::table('whos_online')
				->where('customer_id', $customers_id)
				->update([
						'full_name'  => $userData[0]->customers_firstname.' '.$userData[0]->customers_lastname,
						'time_entry'   => date('Y-m-d H:i:s'),							
				]);
		}else{
			DB::table('whos_online')
				->insert([
						'full_name'  => $userData[0]->customers_firstname.' '.$userData[0]->customers_lastname,
						'time_entry' => date('Y-m-d H:i:s'),
						'customer_id'    => $customers_id							
				]);
		}
		
		
		$responseData = array('success'=>$success, 'data'=>$userData, 'message'=>$message);
		$userResponse = json_encode($responseData);
		print $userResponse;
		
		Mail::send('/mail/createAccount', ['userData' => $userData], function($m) use ($userData){
			$m->to($userData[0]->customers_email_address)->subject('Welcome to Ecommerce App"')->getSwiftMessage()
			->getHeaders()
			->addTextHeader('x-mailgun-native-send', 'true');	
		});
		
	}
	
	
	//googleRegistration 
	public function googleRegistration(Request $request){
		
		$customers_password = $this->createRandomPassword();	
		//gmail user information
		$access_token = $request->idToken;
		$google_id = $request->userId;		
		$customers_firstname = $request->givenName;
		$customers_lastname = $request->familyName;
		$picture = $request->imageUrl;
		$customers_email_address = $request->email;
		
		$img = file_get_contents($picture.'?sz=400');
		$dir="resources/assets/images/user_profile/";
		if (!file_exists($dir) and !is_dir($dir)) {
			mkdir($dir);
		} 
		$uploadfile = $dir."/pic_".time().".jpg";
		file_put_contents(base_path().'/'.$uploadfile, $img);
		$customers_picture=$uploadfile;
		
		//user information
		$customer_data = array(
			'google_id' => $google_id,
			'customers_firstname' => $customers_firstname,
			'customers_lastname' => $customers_lastname,
			//'customers_dob' => $customers_dob,
			'customers_email_address' => $customers_email_address,
			'customers_password' => $customers_password,
			'customers_picture' => $customers_picture,
			'isActive' => '1',
			'created_at' =>	 time()
		);
		
		$existUser = DB::table('customers')->where('google_id', '=', $google_id)->orWhere('customers_email_address', '=', $customers_email_address)->get();
		if(count($existUser)>0){
			
			$customers_id = $existUser[0]->customers_id;
			
			DB::table('customers')->where('customers_id', $customers_id)->update($customer_data);
		
		}else{
			
			//insert data into customer
			$customers_id = DB::table('customers')->insertGetId($customer_data);
		}
		
		$userData = DB::table('customers')->where('customers_id', '=', $customers_id)->get();
		
		//update record of customers_info			
		$existUserInfo = DB::table('customers_info')->where('customers_info_id', $customers_id)->get();
		$customers_info_id 							= $customers_id;
		$customers_info_date_of_last_logon  		= date('Y-m-d H:i:s');
		$customers_info_number_of_logons     		= '1';
		$customers_info_date_account_created 		= date('Y-m-d H:i:s');
		$customers_info_date_account_last_modified  = date('Y-m-d H:i:s');
		$global_product_notifications 				= '1';
		
		if(count($existUserInfo)>0){
			$success = '2';
		}else{
			//break;
			//insert customers_info table
			$customers_default_address_id = DB::table('customers_info')->insertGetId(
				[
					'customers_info_id' => $customers_info_id,
					'customers_info_date_of_last_logon' => $customers_info_date_of_last_logon,
					'customers_info_number_of_logons' =>  $customers_info_number_of_logons,
					'customers_info_date_account_created' => $customers_info_date_account_created,
					'global_product_notifications' => $global_product_notifications
				]
			);	
			$success = '1';
		}		
		
		//check if already login or not
		$already_login = DB::table('whos_online')->where('customer_id', '=', $customers_id)->get();
								
		if(count($already_login)>0){
			DB::table('whos_online')
				->where('customer_id', $customers_id)
				->update([
						'full_name'  => $userData[0]->customers_firstname.' '.$userData[0]->customers_lastname,
						'time_entry'   => date('Y-m-d H:i:s'),							
				]);
		}else{
			
			DB::table('whos_online')
				->insert([
						'full_name'  => $userData[0]->customers_firstname.' '.$userData[0]->customers_lastname,
						'time_entry' => date('Y-m-d H:i:s'),
						'customer_id'    => $customers_id							
				]);
		}
				
		//$userData = $request->all();
		$responseData = array('success'=>$success, 'data'=>$userData, 'message'=>"Login successfully");
		$userResponse = json_encode($responseData);
		print $userResponse;
		
		Mail::send('/mail/createAccount', ['userData' => $userData], function($m) use ($userData){
			$m->to($userData[0]->customers_email_address)->subject('Welcome to Ecommerce App"')->getSwiftMessage()
			->getHeaders()
			->addTextHeader('x-mailgun-native-send', 'true');	
		});
		
		}
		
	//generate random password
	function createRandomPassword() { 
		$pass = substr(md5(uniqid(mt_rand(), true)) , 0, 8);	
		return $pass; 
	} 
	
	//generate random password
	function registerDevices(Request $request) {
		
		$device_type = $request->device_type;
		
		if($device_type=='iOS'){ 	/* iphone */
			$type = 1;
		}elseif($device_type=='Android'){	/* android */
			$type = 2;
		}elseif($device_type=='Desktop'){ 	/* other */
			$type = 3;
		}	
		
		
		if(!empty($request->customers_id)){
		
			$device_data = array(
				'device_id' => $request->device_id,
				'device_type' =>  $type,
				'register_date' => time(),
				'update_date' => time(),
				'ram' =>  $request->ram,
				'status' => '1',
				'processor' => $request->processor,
				'device_os' => $request->device_os,
				'location' => $request->location,
				'device_model'=>$request->device_model,
				'customers_id'=>$request->customers_id,
				'manufacturer'=>$request->manufacturer
			);
		
		}else{
			
			$device_data = array(
				'device_id' => $request->device_id,
				'device_type' =>  $type,
				'register_date' => time(),
				'update_date' => time(),
				'status' => '1',
				'ram' =>  $request->ram,
				'processor' => $request->processor,
				'device_os' => $request->device_os,
				'location' => $request->location,
				'device_model'=>$request->device_model,
				'manufacturer'=>$request->manufacturer
			);
						
		}
		
		//check device exist
		$device_id = DB::table('devices')->where('device_id','=', $request->device_id)->get();
	
		if(count($device_id)>0){
			
			DB::table('devices')
				->where('device_id', $request->device_id)
				->update($device_data);
			//print 'exist';
		}
		else{
			$device_id = DB::table('devices')->insertGetId($device_data);	
			//print 'new';
		}
		
		$responseData = array('success'=>'1', 'data'=>array(), 'message'=>"Device is registered.");
		$userResponse = json_encode($responseData);
		print $userResponse;
		
	} 
	
	
	
}