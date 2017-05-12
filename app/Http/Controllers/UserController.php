<?php

namespace App\Http\Controllers;
use Auth;
use Hash;
use Session;
use View;
use DB;
use Cookie;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Cart;
use App\Models\CartDetail;
use URL;
use DateTime;
use DateTimeZone;
use Illuminate\Support\Facades\Request;
/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
class UserController extends Controller {
     
    // User Login
public function postLogin() 
{    
      $email    = Request::get('email');
      $password = md5(Request::get('password'));

      $login_data = array(
                      'email'    => $email,
                      'password' => $password
                      );          

            $result = User::login_info($email,$password);

            if($result)
            {
                $user_type = $result->user_type;
   
                if($user_type == 'ADMIN')
                  {
                    Session::put('user_id',$result->user_id);
                    Session::put('user_type',$result->user_type);
                    $response['result'] = 1;
                  }

                  else if($user_type == 'USER')
                  {
                    
                    Session::put('session_user_id',$result->user_id);
                    Session::put('user_name',$result->first_name);
                    Session::put('user_type',$result->user_type);                   
                    $response['user_id'] = Session::get('session_user_id');

                    $menu_id_array = Cart::read_menu_cookies();

                      if(!empty($menu_id_array))
                      {
                          $item_id_array            = Cart::read_item_cookies();
                          $combination_id_array     = Cart::read_combination_cookies();
                          $quantity_array           = Cart::read_quantity_cookies();
                          $price_array              = Cart::read_price_cookies();

                          $date            = new DateTime('now', new DateTimeZone('Asia/Kolkata'));
                          $created_date    = $date->format('Y-m-d H:i:s');

                            $cart_data = array(
                              'user_id'    => Session::get('session_user_id'), 
                              'status'     => 'OPEN', 
                              'created_at' => $created_date
                              );

                                foreach($item_id_array as $key=>$value)
                                {
                                    $cart_id     = Cart::add_to_cart($cart_data);

                                    $cart_detail = array(
                                                'cart_id'             => $cart_id, 
                                                'user_id'             => Session::get('session_user_id'), 
                                                'menu_id'             => $menu_id_array[$key], 
                                                'item_id'             => $value,
                                                'item_combination_id' => $combination_id_array[$key],
                                                'quantity'            => $quantity_array[$key],
                                                'status'              => 'OPEN', 
                                                'created_at'          => $created_date
                                                );

                                    $result = CartDetail::add_cart_details($cart_detail);  

                                    if($result)
                                    {
                                        Cookie::queue(Cookie::forget('cookie_menu_ids'));
                                        Cookie::queue(Cookie::forget('cookie_item_ids'));
                                        Cookie::queue(Cookie::forget('cookie_combination_ids'));
                                        Cookie::queue(Cookie::forget('cookie_quantity'));
                                        Cookie::queue(Cookie::forget('cookie_price'));
                                    }        
                                }
                      }

                    $response['result'] = 2;
                  }

            }else
              {                    
                  $response['result'] = 0;
              }  
    
    return response()->json($response);         
}


    
public function postAddUser()
  {        
    $username = Request::get('username');
    $email    = Request::get('email');
    $password = Request::get('password');
   
    $date1        = new DateTime('now', new DateTimeZone('Asia/Kolkata'));
    $created_date = $date1->format('Y-m-d H:i:s');
    $updated_date = $date1->format('Y-m-d H:i:s');

    $user_data = array(
                        'username'     => $username,
                        'email'        => $email,
                        'password'     => $password,
                        'enity_type'=>'DOCTOR',
                        'created_at'   => $created_date,
                        'updated_at'   => $updated_date
                        );
                
    if($user_data) 
    {
        $response= User::add_user($user_data);
    }

      return response()->json($response);

    }
   
  public function postUserDetail()
  {        
    $user_id  = Request::get('user_id');

    $response = User::user_id($user_id);

    return response()->json($response);
  }

  //add users
  public function postAddUsers() {

      $email = Request::get('email');

      $result = DB::table('users')
                  ->where('users.email',$email)
                  ->first();

      if($result)
      {
          $response=0;

      }else{

           $first_name = Request::get('first_name');
           $last_name  = Request::get('last_name');
           $mobile     = Request::get('mobile');
           $email      = Request::get('email');
           $password   = Request::get('password');

           date_default_timezone_set("Asia/Kolkata");
           $created_at        = date('Y-m-d H:i:s');

          $admin_data = array(

                  'user_type'  => 'USER',
                  'first_name' => $first_name,
                  'last_name'  => $last_name,
                  'mobile'     => $mobile,
                  'email'      => $email,
                  'password'   => md5($password),
                  'created_at' => $created_at                 
              );
 
          $user_id = User::add_users($admin_data);

          $user_address = array(

                  'user_id'       => $user_id,
                  'address'       => '',
                  'pincode'       => '',
                  'area_id_fk'    => '',
                  'city_id_fk'    => '',
                  'state_id'      => '',
                  'country'       => '',
                  'created_at'    => $created_at                
              );

          $result=DB::table('user_address')->insert($user_address);


          $encrypted_user_id=md5($user_id);
          DB::table('users')->where('users.user_id',$user_id)->update(array('encrypted_user_id'=>$encrypted_user_id));

           $response=1;          
      }

      return response()->json($response);
  }

  //add users
  public function postRegistration() 
  {
      $email = Request::get('email');

      $result = DB::table('users')
                  ->where('users.email',$email)
                  ->first();

      if($result)
      {
          $response = 2;

      }else{

           $first_name = Request::get('first_name');
           $last_name  = Request::get('last_name');
           $mobile     = Request::get('mobile');
           $email      = Request::get('email');
           $password   = Request::get('password');

           date_default_timezone_set("Asia/Kolkata");
           $created_at        = date('Y-m-d H:i:s');

          $admin_data = array(

                  'user_type'  => 'USER',
                  'first_name' => $first_name,
                  'last_name'  => $last_name,
                  'mobile'     => $mobile,
                  'email'      => $email,
                  'password'   => md5($password),
                  'created_at' => $created_at                 
              );
 
          $user_id = User::add_users($admin_data);

          $user_address = array(

                  'user_id'       => $user_id,
                  'address'       => '',
                  'pincode'       => '',
                  'area_id_fk'    => '',
                  'city_id_fk'    => '',
                  'state_id'      => '',
                  'country'       => '',
                  'created_at'    => $created_at                
              );

          $result=DB::table('user_address')->insert($user_address);

          $encrypted_user_id=md5($user_id);

          DB::table('users')->where('users.user_id',$user_id)->update(array('encrypted_user_id'=>$encrypted_user_id));

          Session::put('session_user_id',$user_id);
          Session::put('user_name',$first_name);
          Session::put('user_type','USER'); 
                
          $response['user_id'] = Session::get('session_user_id');

          $response['result']  = 1;          
      }

      return response()->json($response);
  }

//fetching all users from the db
    public function getDisplayAllUsers() {

        $response['users_details'] = User::get_all_users();
        
        return response()->json($response); 
    }


/*Delete particular Slider*/
    public function postDeleteUsers() 
    {
        $response['error']   = 'true';
        $response['message'] = 'Error in Deleting';
        
        $user_id       = Request::get('user_id');
        $delete_status = array('delete_status' => 'INACTIVE');
        
        if($user_id) 
        {
            $result = User::delete_users($user_id,$delete_status);
        }
                
        if($result == 1)
        {
            $response['error']   = 'false';
            $response['message'] = 'Deleted Successfully';
        }
        return response()->json($response);
    }



//displaying single user details
    public function postDisplaySingleUser() {

        $user_id             = Request::get('user_id');
      
        $response['details'] = User::user_id($user_id);

        return response()->json($response); 
    }

//add user-address
    // public function postAddUserAddress() {

    //          $user_id   = Request::get('user_id');
    //          $address   = Request::get('address');
    //          $pincode   = Request::get('pincode');
    //          $area      = Request::get('area');
    //          $city      = Request::get('city');
    //          $state     = Request::get('state');
    //          $country   = Request::get('country');

    //          date_default_timezone_set("Asia/Kolkata");
    //          $created_at        = date('Y-m-d H:i:s');

    //         $admin_data = array(

    //                 'user_id'       => $user_id,
    //                 'address'       => $address,
    //                 'pincode'       => $pincode,
    //                 'area_id_fk'    => $area,
    //                 'city_id_fk'    => $city,
    //                 'state_id'      => $state,
    //                 'country'       => $country,
    //                 'created_at'    => $created_at                 
    //             );
    //        if($admin_data){
    //          $response = User::add_user_address_data($admin_data);
    //         $response = 1;
    //        }else{
    //          $response = 0;
    //        }
             

    //     return response()->json($response);
    // }

//add user-address
    public function postUpdateUserProfile() {

             $user_id      = Request::get('user_id');
             $first_name   = Request::get('first_name');
             $last_name    = Request::get('last_name');
             $mobile       = Request::get('mobile');
             $email        = Request::get('email');
             $address      = Request::get('address');
             $pincode      = Request::get('pincode');
             $area         = Request::get('area');
             $city         = Request::get('city');
             $state        = Request::get('state');
             $country      = Request::get('country');

             $date = new DateTime('now', new DateTimeZone('Asia/Kolkata'));
             $created_at = $date->format('Y-m-d H:i:s');
             $response['result']= 0;

            $user_table = array(

                    'first_name' => $first_name,
                    'last_name'  => $last_name,
                    'mobile'     => $mobile,
                    'email'      => $email,
                    'updated_at' => $created_at                 
                );

            $user_address_table = array(

                    'user_id'    => $user_id,
                    'address'    => $address,
                    'pincode'    => $pincode,
                    'area_id_fk' => $area,
                    'city_id_fk' => $city,
                    'state_id'   => $state,
                    'country'    => $country,
                    'updated_at' => $created_at                 
                );

           if($user_id){

             $user_result = User::user_table_update($user_table,$user_id);

             // $address_check=DB::table('user_address')
             //                 ->where('user_id',$user_id)
             //                 ->where('delete_status','ACTIVE')
             //                 ->first();

              if($user_address_table)  {
                $result = User::user_address_update($user_address_table,$user_id);
                  if($result){
                    $response['result'] = 1;
                  }
              } 
              // else{
              //   $response = User::user_address_insert($user_address_table);
              // }
           }
          return response()->json($response);
    } 

  public function postUserAddressDetails()
  {
    $user_id = Session::get('session_user_id');

    $result  = User::user_details($user_id);

    $response['result'] = 0;

    if($result)
    {
      $response['result']       = 1;
      $response['user_address'] = $result;
    }

    return response()->json($response);
  }

  public function postAddMoreUserAddress()
  {
    $user_id    = Session::get('session_user_id');

    $address    = Request::get('address');

    $date       = new DateTime('now', new DateTimeZone('Asia/Kolkata'));

    $created_at = $date->format('Y-m-d H:i:s');

    $user_address = array(
                    'user_id'       => $user_id,
                    'address'       => $address,
                    'pincode'       => '',
                    'area_id_fk'    => '',
                    'city_id_fk'    => '',
                    'state_id'      => '',
                    'country'       => '',
                    'created_at'    => $created_at                
                );

    $result=DB::table('user_address')->insert($user_address);

    $response['result'] = 0;

    if($result)
    {
      $response['result']       = 1;
    }

    return response()->json($response);
  }

  public function postDeleteUserAddress(){

    $user_id    = Session::get('session_user_id');

    $address_id = Request::get('address_id');

    $result     = DB::table('user_address')
                      ->where('address_id',$address_id)
                      ->update(array('delete_status'=>'INACTIVE'));

    $response['result'] = 0;

    if($result)
    {
      $response['result'] = 1;
    }

    return response()->json($response);

  }
    
}
