<?php

namespace App\Http\Controllers;

use Recaptcha;
use Carbon\Carbon;
use App\Models\User;
use App\Models\cards;
use App\Models\materials;
use App\Models\payments;
use App\Models\models;
use App\Models\companies;
use App\Models\companies_user;
use App\Models\Remark;
use App\Models\Machine;
use App\Models\staff;
use App\Models\Media;
use App\Models\order;
use App\Models\Store;
use App\Models\Album;
use App\Models\AlbumDetail;
use App\Models\Project;
use App\Models\Product;
use App\Models\Payment;
use App\Models\price_menu;
use App\Models\Notification;
use App\Models\Plan_solution;
use App\Models\Plan_solution_order;
use App\Models\Product_solution;
use App\Models\Product_solution_order;
use App\Models\Key;

// use App\Models\TimesOrder;
use App\Events\PicUploaded;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Events\PicUploadFailed;
use App\Repository\OrderRepository;
use App\Events\CompleteTransformPic;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\UploadedFile;
use App\Events\CompleteTransformVideo;
use App\Events\AIBoxRefresh;
use App\Notifications\ConfirmUserCode;
use App\Notifications\ClickTimesRemind;
use App\Notifications\NoTimesRemind;
use App\Notifications\SolutionExpiredNotify;
use App\Notifications\ResetPasswordLink;
use App\Http\Resources\MediaCollection;
use App\Http\Resources\OrderCollection;
use App\Http\Resources\AlbumCollection;
use App\Http\Resources\StoreCollection;
use App\Http\Resources\ProductCollection;
use App\Http\Resources\ProductSolutionCollection;
use App\Jobs\AutoDeleteGuestMedia;
use App\Jobs\ProductUnsubscribe;
use App\Jobs\ResetPasswordTokenExpired;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Encryption\Encrypter;

use Illuminate\Support\Facades\Validator;
// use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Password;
use Intervention\Image\Facades\Image;

use ZipArchive;

class ApiController extends Controller
{
    public function get_cpu_usage() {
        exec('top -b -n 1 | grep "Cpu(s)"', $output);
        $cpuInfo = explode(",", $output[0]);
        $cpuUsage = trim(str_replace("Cpu(s):", "", $cpuInfo[0]));

        return $cpuUsage;
    }

    /**
     * login
     */
    public function login(Request $request) {
        
        $validator = Validator::make($request->all(),[
            'account' => 'required',
            'password' => 'required'
        ]);
        
        
        if ($validator->fails()) {
            return [
                'success' => false,
                'message' => __('auth.failed'),
                'errors' => $validator->errors()->toArray()
            ];
        }

        $credentials = $request->only('account', 'password');

        if ($result = Auth::attempt($credentials)) {
            $auth = Auth::user();
            $token = $auth->createToken($request->account)->plainTextToken;

            $user = User::Where('id', $auth->id)->first();
            $user->remember_token = $token;
            $user->save();

            return [
                'success' => true,
                'message' => [
                    'id' =>  $auth->id,
                    'name' =>  $auth->name,
                    'email' =>  $auth->email,
                    'token'=>  $token
                ]
            ];
        } else {
            return [
                'success' => false,
                'message' => __('auth.failed'),
                'errors' => $result
            ];
        }
    }

    public function company_login(Request $request) {
        
        $validator = Validator::make($request->all(),[
            'username' => 'required',
            'password' => 'required'
        ]);
        
        
        if ($validator->fails()) {
            return [
                'success' => false,
                'message' => __('auth.failed'),
                'errors' => $validator->errors()->toArray()
            ];
        }

        $credentials = $request->only('account', 'password');
        $company = companies::where('account', $request->username)->first();
            
        if(!$company)
        {
            return [
                'success' => false,
                'message' => __('auth.failed'),
                'errors' => $validator->errors()->toArray()
            ];
        }

        $token = Str::random(60);

        while (companies::where('token', $token)->exists()) {
            $token = Str::random(60);
        }

        $company->token = $token;
        $company->save();

        if ($company) {
            if($company->password != $request->password)
            {
                return [
                    'success' => false,
                    'message' => __('auth.failed'),
                    'errors' => $validator->errors()->toArray()
                ];
            }
            return [
                'success' => true,
                'message' => [
                    'id' =>  $company->id,
                    'name' =>  $company->name,
                    'account' =>  $company->account,
                    'token' =>  $company->token,
                ]
            ];
        } else {
            return [
                'success' => false,
                'message' => __('auth.failed'),
                'errors' => $company
            ];
        }
    }

    public function staff_login(Request $request) {
        
        $validator = Validator::make($request->all(),[
            'account' => 'required',
            'password' => 'required'
        ]);
        
        
        if ($validator->fails()) {
            return [
                'success' => false,
                'message' => __('auth.failed'),
                'errors' => $validator->errors()->toArray()
            ];
        }

        $credentials = $request->only('account', 'password');
        $staff = Staff::where('account', $request->account)->first();
            
        if(!$staff)
        {
            return [
                'success' => false,
                'message' => __('auth.failed'),
                'errors' => $validator->errors()->toArray()
            ];
        }


        if ($staff) {
            if($staff->password != $request->password)
            {
                return [
                    'success' => false,
                    'message' => __('auth.failed'),
                    'errors' => $validator->errors()->toArray()
                ];
            }
            return [
                'success' => true,
                'message' => [
                    'id' =>  $staff->id,
                    'account' =>  $staff->account,
                ]
            ];
        } else {
            return [
                'success' => false,
                'message' => __('auth.failed'),
                'errors' => $staff
            ];
        }
    }

    public function companies_UpdatePassword(Request $request) {

        $validator = Validator::make($request->all(), [
            'token' => 'required',
            'Password' =>'required',
            'newPassword' => 'required',
        ]);

        if (!$request->token)
            abort(415);

        if ($validator->fails()) {
            return [
                'success' => false,
                'message' => __('register.failed'),
                'errors' => $validator->errors()->toArray()
            ];
        }

        $resetPasswordToken = $request->token;
        $password = $request->Passowrd;
        $companies = companies::where('token', $resetPasswordToken)->first();

        if ($companies) {
            if($companies->passowrd != $password)
            {
                return [
                    'success' => false,
                    'message' => "passowrd Error"
                ];
            }

            //$companies->password = Hash::make($request->newPassword);
            $companies->password = $request->newPassword;
            $companies->save();

            return [
                'success' => true,
                'message' => "ChangeSuccess"
            ];   

        }
        else {
            abort(415);
            return [
                'success' => false,
                'message' => "UserNotFound"
            ];
        }

        
    }

  
    /**
     *  api
     */
    public function addOrder(Request $request){
        $validator = Validator::make($request->all(),[
            'token' => 'required',
            'machine_name' => 'required',
            'method' => 'required',
            'account' => 'required'
        ]);
        
        if (!$request->token)
            abort(415);

        if($validator->fails()){
            return [
                'success' => false,
                'message' => __('register.failed'),
                'errors'=> $validator->errors()->toArray()
            ];
        }

        $company = companies::where("token",$request->token)->first();
        $machine = Machine::where('id',$request->machine_name)->first();


        $order = new order();
        $length = 16;
        do {
            $number = '';
            for ($i = 0; $i < $length; $i++) {
                $number .= mt_rand(0, 9);
            }
        } while (order::where('id', $number)->exists()); // 确保唯一
        $order->id = $number;
        $order->company_id = $company->id;
        $order->machine_id = $machine->id;
        $order->method = $request->method;
        $order->account = $request->account;
        $order->times = 5;
        $order->save();

        return [
            'success' => true,
            'message' => $order->id
        ];
    }

    public function checkCamera(Request $request)
    {
        // 驗證：MID 必填且存在於 machines.id；camera 必須是非負整數
        $validator = Validator::make($request->all(), [
            'MID'    => 'required',
            'camera' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => __('validation.failed'),
                'errors'  => $validator->errors()->toArray(),
            ], 422);
        }

        $mid = (int) $request->input('MID');
        $currentCamera = (int) $request->input('camera');

        // 取得機器資料
        $machine = Machine::find($mid);
        // 假設 machines.camera 欄位存的是「規定相機數量」（整數）
        $requiredCamera = (int) ($machine->camera ?? 0);

        // 不相同就把 status 改成 2
        if ($currentCamera !== $requiredCamera) {
            // 只有在不是 2 的情況下才更新，避免一直寫 DB
            if ((int)$machine->status !== 2) {
                $machine->status = 2;
                $machine->save();
            }
        }

        return [
            'success' => true,
            'message' => [
                'status'           => (int) $machine->status,   // 目前狀態
                'required_camera'  => $requiredCamera,          // 資料表規定相機數量
            ]
        ];
    }
    

    public function reduceTimes(Request $request)
    {
        $validator = Validator::make($request->all(),[
            'orderID' => 'required',
        ]);

        $orderID = $request->input('orderID');
        $order = order::where('id', $orderID)->first();
        if(!$order)
        {
            return [
                'success' => false,
                'message' => "查無此訂單"
            ];
        }

        if($order->times <= 0)
        {
            return [
                'success' => false,
                // 'message' => "該名片以達到使用上限，你也想要擁有3D名片嗎? 請洽 : <a href=''>法鬥文創</a>"
                'message' => "使用次數已用完"
            ];
        }

        if($order->times >0)
        {
            $order->times -= 1;
        }
        
        $order->save();

        return [
            'success' => true,
            'message' => [
                'times' => $order->times
            ]
        ];
    }

    public function paymentSuccessful(Request $request){
        $validator = Validator::make($request->all(),[
            'orderID' => 'required'
        ]);
        
        if($validator->fails()){
            return [
                'success' => false,
                'message' => __('register.failed'),
                'errors'=> $validator->errors()->toArray()
            ];
        }

        $order = order::where('id', $request->orderID)->first();
        $order->status = 1;
        $order->save();
    }

    public function methodSuccessful(Request $request){
        $validator = Validator::make($request->all(),[
            'UID' => 'required',
            'MID' => 'required'
        ]);

        if($validator->fails()){
            return [
                'success' => false,
                'message' => "資料缺失",
                'errors'=> $validator->errors()->toArray()
            ];
        }

        $machine = machine::where('id', $request->MID)->first();

        if(!$machine)
        {
            return [
                'success' => false,
                'message' => "機器不存在",
            ];
        }

        if($machine->status == 1)
        {
            return [
                'success' => false,
                'message' => "機器正在使用，無法開啟",
            ];
        }

        $machine->status = 1;
        $machine->user = $request->UID;
        $machine->save();

        return [
            'success' => true
        ];
    }

    public function getMachineStatus(Request $request){
        $validator = Validator::make($request->all(),[
            'MID' => 'required'
        ]);

        if($validator->fails()){
            return [
                'success' => false,
                'message' => __('register.failed'),
                'errors'=> $validator->errors()->toArray()
            ];
        }

        $machine = machine::where('id', $request->MID)->first();

        if (!$machine) {
            return [
                'success' => false,
                'message' => 'Machine not found'
            ];
        }
        
        return [
            'success' => true,
            'message' => [       
                'user' => $machine->user,
                'status' => $machine->status
            ]
        ];
    }

    public function setMachineStatus(Request $request){
        $validator = Validator::make($request->all(),[
            'MID' => 'required',
            'status' => 'required'
        ]);

        if($validator->fails()){
            return [
                'success' => false,
                'message' => __('register.failed'),
                'errors'=> $validator->errors()->toArray()
            ];
        }

        $machine = machine::where('id', $request->MID)->first();

        if (!$machine) {
            return [
                'success' => false,
                'message' => 'Machine not found'
            ];
        }
        
        $machine->status = 0;
        $machine->save();

        return [
            'success' => true,
            'message' => [       
                'user' => $machine->user,
                'status' => $machine->status
            ]
        ];
    }

    public function addmachine(Request $request){
        $validator = Validator::make($request->all(),[
            'name' => 'required'
        ]);
        
        if (!$request->token)
            abort(415);

        if($validator->fails()){
            return [
                'success' => false,
                'message' => __('register.failed'),
                'errors'=> $validator->errors()->toArray()
            ];
        }

        $machine = new Machine();
        $machine->name = $request->name;
        $remark->save();

        return [
            'success' => true,
            'message' => $machine->name
        ];
    }

    public function getModelRemark(Request $request) {
        
        $validator = Validator::make($request->all(),[
            'username' => 'required',
            'modelId' => 'required'
        ]);

        if($validator->fails()){
            return [
                'success' => false,
                'message' => __('register.failed'),
                'errors'=> $validator->errors()->toArray()
            ];
        }
        $staff = Staff::where('account',$request->username)->first();
        $company = companies::where('id', $staff->company_id)->first();

        if(!$company)
        {
            abort(415);
            return [
                'success' => false,
                'message' => "請重新登入"
            ];
        }

        $remarks = Remark::where('staff_id', $staff->id)
                         ->where('order_id', $request->modelId) // 新增条件
                         ->get();

        $remarks_list= [];
        foreach ($remarks as $remark) {  
           
            $remarks_data = [
                'remark' => $remark->remark,
                'id' =>  $remark->id
            ];

            array_push($remarks_list, $remarks_data);  
        }

        return [
            'success' => true,
            'message' => $remarks_list
        ];
    }

    public function addRemark(Request $request) {
        
        $validator = Validator::make($request->all(),[
            'account' => 'required',
            'orderId' => 'required',
            'note' => 'required'
        ]);
        
        // if (!$request->token)
        //     abort(415);

        if($validator->fails()){
            return [
                'success' => false,
                'message' => __('register.failed'),
                'errors'=> $validator->errors()->toArray()
            ];
        }

        $staff = staff::where('account', $request->account)->first();
        // $company = companies::where('token', $request->token)->first();

        if(!$staff)
        {
            abort(415);
            return [
                'success' => false,
                'message' => "請重新登入"
            ];
        }

        $order = order::where('id', $request->orderId)->first();

        if(!$order)
        {
            return [
                'success' => false,
                'message' => "查無此訂單"
            ];
        }

        $remark = new Remark();
        $remark->staff_id = $staff->id;
        $remark->order_id = $order->id;
        $remark->remark = $request->note;
        $remark->save();

        return [
            'success' => true,
            'message' => $remark->remark
        ];
    }

    public function removeRemark(Request $request) {
        
        $validator = Validator::make($request->all(),[
            'account' => 'required',
            'noteId' => 'required'
        ]);

        if($validator->fails()){
            return [
                'success' => false,
                'message' => __('register.failed'),
                'errors'=> $validator->errors()->toArray()
            ];
        }
        $staff = staff::where('account', $request->account)->first();

        // $company = companies::where('token', $request->token)->first();

        if(!$staff)
        {
            abort(415);
            return [
                'success' => false,
                'message' => "請重新登入"
            ];
        }

        $remark = Remark::where('id', $request->noteId)->first();

        if(!$remark)
        {
            return [
                'success' => false,
                'message' => "查無此備註"
            ];
        }

        $remark->delete();

        return [
            'success' => true,
            'message' => '刪除成功'
        ];
    }

    public function get_orderList(Request $request) {
        
        $validator = Validator::make($request->all(),[
            'account' => 'required'
        ]);

        if($validator->fails()){
            return [
                'success' => false,
                'message' => __('register.failed'),
                'errors'=> $validator->errors()->toArray()
            ];
        }

        $staff = Staff::where('account',$request->account)->first();
        $company = companies::where('id', $staff->company_id)->first();

        if(!$company)
        {
            abort(415);
            return [
                'success' => false,
                'message' => "請重新登入"
            ];
        }

        $models = models::where('company_id', $company->id)->get();
        $order_list= [];
        foreach ($models as $model) {             
            $order_data = [
                'id' => $model->id,
                'email' => $model->account,
                // 'remark' => $model->remark,
                'createdAt' => $model->created_at->format('Y-m-d H:i:s')
            ];

            array_push($order_list, $order_data);  
        }

        return [
            'success' => true,
            'message' => $order_list
        ];
    }

    public function getUserData(Request $request){
        
        $validator = Validator::make($request->all(),[
            'email' => 'required'
        ]);

        if($validator->fails()){
            return [
                'success' => false,
                'message' => __('register.failed'),
                'errors'=> $validator->errors()->toArray()
            ];
        }

        $user = User::where('email',$request->email);

        if($user->exists())
        {
            $user = $user->first();
            return [
                'success' => true,
                'message' => [       
                    'name' => $user->name,       
                    'email' => $user->email,
                    'download_time' => $user->download_time,
                    'bonus_times' => $user->bonus_times                      
                ]
            ];
        }else{
            return [
                'success' => false,
                'message' => "找不到此帳號"
            ];
        }
    }

    public function uploadModel(Request $request)
    {
        $validator = Validator::make($request->all(),[
            'order' => 'required',
            'account' => 'required',
            'texture' => 'required',
            'model' => 'required'
        ]);
        if($validator->fails()){
            return [
                'success' => false,
                'message' => __('editModels.failed'),
                'errors'=> $validator->errors()->toArray()
            ];
        }

        $staff = Staff::where('account',$request->account)->first();
        if(!$staff)
        {
            abort(415);
            return [
                'success' => false,
                'message' => "帳號錯誤，請重新登入"
            ];
        }
        $company = companies::where('id', $staff->company_id)->first();

        if(!$company)
        {
            abort(415);
            return [
                'success' => false,
                'message' => "請重新登入"
            ];
        }

        $model = models::where('id',$request->order)->first();
        
        if(!$model)
        {
            return[
                'success' => false,
                'message' => "找不到此訂單"
            ];
        }

        $order = order::where('id', $model->order_id)->first();
        if(!$order)
        {
            return[
                'success' => false,
                'message' => "找不到此訂單"
            ];
        }
        $s3_model_dir = env('APP_ENV') . "/".$model->company_id."/".$order->id."/"; // 修改这里的设定
 
        $s3_texture_dir = $s3_model_dir."texture";
        $s3_mesh_dir = $s3_model_dir."mesh";

        $textureFile = $request->file('texture');
        $meshFile = $request->file('model');

        $s3_texture_fileName = $request->order . '.' . $textureFile->getClientOriginalExtension();
        $s3_mesh_fileName = $request->order . '.' . $meshFile->getClientOriginalExtension();
        
        if (!($textureFile->isValid() && $meshFile->isValid()))
        {
            return [
                'success' => false,
                'message' => "檔案上傳失敗，請確認您上傳的檔案是否可用",
            ];
        }

        $isAllUploaded = true;
        
        if (!$textureFile->storeAs($s3_texture_dir, $s3_texture_fileName, 's3'))
            $isAllUploaded = false;
        if (!$meshFile->storeAs($s3_mesh_dir, $s3_mesh_fileName, 's3'))
            $isAllUploaded = false;

        if (!$isAllUploaded)
        {
            // 刪掉該 $s3_model_dir 資料夾
            return [
                'success' => false,
                'message' => "檔案上傳失敗，請確認您上傳的檔案是否可用",
            ];
        }

        $model->texture_url = $s3_texture_dir.'/'.$s3_texture_fileName;
        $model->mesh_url = $s3_mesh_dir.'/'.$s3_mesh_fileName;

        $model->save();

        return[
            $request->order
        ];
    }

    public function company_CheckLevel(Request $request){
        $validator = Validator::make($request->all(),[
            'token' => 'required'
        ]);

        if (!$request->token)
            abort(415);

        if($validator->fails()){
            return [
                'success' => false,
                'message' => __('register.failed'),
                'errors'=> $validator->errors()->toArray()
            ];
        }

        $company = companies::where('token', $request->token);
        
        if(!$company->exists())
        {
            abort(415);
            return [
                'success' => false,
                'message' => "請重新登入"
            ];
        }

        $company = $company->first();

        if($company->level == 1)
        {
            return [
                'success' => true,
                'message' => true
            ];
        }else{
            return [
                'success' => true,
                'message' => false
            ];  
        }
    }

    public function getCompanyUser(Request $request){
        $validator = Validator::make($request->all(),[
            'token' => 'required'
        ]);

        if (!$request->token)
            abort(415);

        if($validator->fails()){
            return [
                'success' => false,
                'message' => __('register.failed'),
                'errors'=> $validator->errors()->toArray()
            ];
        }

        $getCompanyUser_Result =[
            'success' => true,
            'hasRemainingTimes'=>[],
            'hasntRemainingTimes'=>[]
        ];

        $company = companies::where('token',$request->token)->first();
        
        if(!$company)
        {
            abort(415);
            return [
                'success' => false,
                'message' => "請重新登入"
            ];
        }

        if ($company->level == 0) {
            $company_users = companies_user::orderBy('created_at', 'desc')->get();
        } else {
            $company_users = companies_user::where('company_id', $company->id)
                                           ->orderBy('created_at', 'desc')
                                           ->get();
        }
        // if($company->level == 0)
        // {
        //     $company_users = companies_user::orderBy('user_id', 'desc')->get();
        // }else
        // {
        //     $company_users = companies_user::where('company_id', $company->id)
        //                         ->orderBy('user_id', 'desc')
        //                         ->get();
        // }

        foreach ($company_users as $company_user) {
            
            $user = User::where('id',$company_user->user_id)->first();
            if (!$user->remember_token)
            {
                $token = $user->createToken($user->account)->plainTextToken;
                $user->remember_token = $token;
                $user->save();
            }
            $card = cards::where('user_id',$user->id);
            $card_amount = $card->count();

            $company_name = "";
            $company_token = "";

            $card = $card->first();
            if ($card) {
                $company_name = companies::where('id', $card->company_id)->select("name")->first()->name;
                $company_token = companies::where('id', $card->company_id)->select("token")->first()->token;
            }

            $user_data = [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'company' => $company_name,
                'company_token' => $company_token,
                'company_id' => $card->company_id,
                'card_amount' => $card_amount,
                'remainingTimes' => $user->download_time + $user->bonus_times,
                'token' => $user->remember_token
            ];

            if($user->download_time == 0 && $user->bonus_times == 0)
            {
                array_push($getCompanyUser_Result['hasntRemainingTimes'], $user_data);
            }else
            {
                array_push($getCompanyUser_Result['hasRemainingTimes'], $user_data);
            }
        }
        
        return[
            'success' => true,
            'message' => $getCompanyUser_Result
        ];
    }

    public function addCompanyUser(Request $request){
        $validator = Validator::make($request->all(),[
            'token' => 'required',
            'user_id' => 'required'

        ]);

        if (!$request->token)
            abort(415);

        if($validator->fails()){
            return [
                'success' => false,
                'message' => __('register.failed'),
                'errors'=> $validator->errors()->toArray()
            ];
        }
        
        $company = companies::where('token',$request->token)->first();
        if(!$company)
        {
            abort(415);
            return [
                'success' => false,
                'message' => "請重新登入"
            ];
        }

        $user = User::where('id',$request->user_id)->first();

        $companies_user = companies_user::where('company_id',$company->id)->where('user_id',$user->id);
        if($companies_user->exists())
        {
            return [
                'success' => false,
                'message' => "帳號已存在，無法新建"
            ];
        }
        $companies_user = $companies_user->get();

        $company_user = new companies_user();
        $company_user->user_id = $user->id;
        $company_user->company_id = $company->id;
        $company_user->save();
        return [
            'success' => true,
            'message' => "success"
        ];
    }

    public function removeCompanyUser(Request $request){
        $validator = Validator::make($request->all(),[
            'token' => 'required',
            'user_id' => 'required'
        ]);

        if (!$request->token)
            abort(415);

        if($validator->fails()){
            return [
                'success' => false,
                'message' => __('register.failed'),
                'errors'=> $validator->errors()->toArray()
            ];
        }
        
        $company = companies::where('token', $request->token)->first();
        if(!$company)
        {
            abort(415);
            return [
                'success' => false,
                'message' => "請重新登入"
            ];
        }

        $user = User::where('id',$request->user_id)->first();
        
        $companies_user = companies_user::where('company_id', $company->id)->where('user_id', $user->id)->first();
        if(!$companies_user)
        {
            return [
                'success' => false,
                'message' => "找不到此會員"
            ];
        }

        $companies_user->delete();
        return [
            'success' => true,
            'message' => "刪除成功"
        ];
    }

    public function getUserInfo(Request $request){
        $user = Auth::user();
        return[
            'success' =>true,
            'message'=>auth::user()
        ];
    }

    public function getCompanyToken(Request $request){
        $validator = Validator::make($request->all(),[
            'MID' => 'required',
        ]);

        if($validator->fails()){
            return [
                'success' => false,
                'message' => __('register.failed'),
                'errors'=> $validator->errors()->toArray()
            ];
        }
        
        $machine = Machine::where('id', $request->MID)->first();

        if(!$machine)
        {
            return [
                'success' => false,
                'message' => '找不到此機器'
            ];
        }

        $company = Companies::where('id',$machine->company_id)->first();
        
        if(!$company)
        {
            return [
                'success' => false,
                'message' => '此機器無公司'
            ];
        }

        return [
            'success' => true,
            'message' => $company->token
        ];
    }

    public function addModels(Request $request){
        $validator = Validator::make($request->all(),[
            'token' => 'required',
            'user_account' => 'required',
            'texture_url' => 'required',
            'mesh_url' => 'required',
            'cover_url' => 'required',
            'cover_half_url' => 'required',
        ]);

        if (!$request->token)
            abort(415);

        if($validator->fails()){
            return [
                'success' => false,
                'message' => __('register.failed'),
                'errors'=> $validator->errors()->toArray()
            ];
        }
  
        $company = companies::where('token',$request->token)->first();
        if(!$company)
        {
            abort(415);
            return [
                'success' => false,
                'message' => "請重新登入"
            ];
        }

        $user = User::where('account', $request->user_account)->first();
        if (!$user) {
            return [
                'success' => false,
                'message' => "查無此帳號，請確認帳號是否輸入正確",
            ];
        }

        $id = uniqid();
        $s3_model_dir = env('APP_ENV') . "/".$user->id."/"."model"."/".$id."/"; // 修改这里的设定

        $s3_texture_dir = $s3_model_dir."texture";
        $s3_mesh_dir = $s3_model_dir."mesh";
        $s3_cover_dir = $s3_model_dir."cover";
        $s3_coverHalf_dir = $s3_model_dir."cover_half";

        $textureFile = $request->file('texture_url');
        $meshFile = $request->file('mesh_url');
        $coverFile = $request->file('cover_url');
        $coverHalfFile = $request->file('cover_half_url');

        $s3_texture_fileName = $id . '.' . $textureFile->getClientOriginalExtension();
        $s3_mesh_fileName = $id . '.' . $meshFile->getClientOriginalExtension();
        $s3_cover_fileName = $id . '.' . $coverFile->getClientOriginalExtension();
        $s3_coverHalf_fileName = $id . '.' . $coverHalfFile->getClientOriginalExtension();

        if (!($textureFile->isValid() && $meshFile->isValid() && $coverFile->isValid() && $coverHalfFile->isValid()))
        {
            return [
                'success' => false,
                'message' => "檔案上傳失敗，請確認您上傳的檔案是否可用",
            ];
        }

        $isAllUploaded = true;
        
        if (!$textureFile->storeAs($s3_texture_dir, $s3_texture_fileName, 's3'))
            $isAllUploaded = false;
        if (!$meshFile->storeAs($s3_mesh_dir, $s3_mesh_fileName, 's3'))
            $isAllUploaded = false;
        if (!$coverFile->storeAs($s3_cover_dir, $s3_cover_fileName, 's3'))
            $isAllUploaded = false;
        if (!$coverHalfFile->storeAs($s3_coverHalf_dir, $s3_coverHalf_fileName, 's3'))
            $isAllUploaded = false; 

        if (!$isAllUploaded)
        {
            // 刪掉該 $s3_model_dir 資料夾
            return [
                'success' => false,
                'message' => "檔案上傳失敗，請確認您上傳的檔案是否可用",
            ];
        }

        $model = new models();
        $model->user_id = $user->id;
        $model->texture_url = $s3_texture_dir.'/'.$s3_texture_fileName;
        $model->mesh_url = $s3_mesh_dir.'/'.$s3_mesh_fileName;
        $model->cover_url = $s3_cover_dir.'/'.$s3_cover_fileName;
        $model->cover_half_url = $s3_coverHalf_dir.'/'.$s3_coverHalf_fileName;
        $model->company_id = $company->id;

        $model->save();

        return [
            'success' => true,
            'message' => [
                'user_id' => $model->user_id,
                'mesh_url' => $model->mesh_url,
                'texture_url' => $model->texture_url,
                'cover_url' => $model->cover_url,
                'id' => $model->id
            ],
        ];
    }

    public function editModels(Request $request){
        $validator = Validator::make($request->all(),[
            'BC_id' => 'required',
            'token' => 'required',
            'id' => 'required',
            'texture_url' => 'required',
            'mesh_url' => 'required',
            'cover_url' => 'required',
            'cover_half_url' => 'required',
        ]);

        if (!$request->token)
            abort(415);

        if($validator->fails()){
            return [
                'success' => false,
                'message' => __('editModels.failed'),
                'errors'=> $validator->errors()->toArray()
            ];
        }

        $company = companies::where('token',$request->token)->first();
        if(!$company)
        {
            abort(415);
            return [
                'success' => false,
                'message' => "請重新登入"
            ];
        }

        $model = models::where('id',$request->id)->first();
        
        if(!$model)
        {
            return[
                'success' => false,
                'message' => "找不到編輯的模型"
            ];
        }

        $card = cards::where('id',$request->BC_id)->first();
        if($card)
        {
            $card->version = $card->version + 1;
            $card->save();
        }

        // 取得卡片圖並儲存到 S3

        $fullPath_mesh = $model->mesh_url;
        $pathInfo = pathinfo($fullPath_mesh);
        $s3_mesh_dir = $pathInfo['dirname']; 
        $s3_mesh_fileName = $pathInfo['basename']; 
        $meshFile = $request->file('mesh_url');

        $fullPath_texture = $model->texture_url;
        $pathInfo = pathinfo($fullPath_texture);
        $s3_texture_dir = $pathInfo['dirname']; 
        $s3_texture_fileName = $pathInfo['basename']; 
        $textureFile = $request->file('texture_url');

        $fullPath_cover = $model->cover_url;
        $pathInfo = pathinfo($fullPath_cover);
        $s3_cover_dir = $pathInfo['dirname']; 
        $s3_cover_fileName = $pathInfo['basename']; 
        $coverFile = $request->file('cover_url');

        $fullPath_cover_half = $model->cover_half_url;
        $pathInfo = pathinfo($fullPath_cover_half);
        $s3_coverHalf_dir = $pathInfo['dirname']; 
        $s3_coverHalf_fileName = $pathInfo['basename']; 
        $coverHalfFile = $request->file('cover_half_url');

        if(!($textureFile->isValid() && $meshFile->isValid() && $coverFile->isValid() && $coverHalfFile->isValid())) {
            $error = "";
            if (!$textureFile->isValid())
                $error = $error."貼圖 ";
            
            if (!$meshFile->isValid())
                $error = $error."模型 ";

            if (!$coverFile->isValid())
                $error = $error."全身圖 ";

            if (!$coverHalfFile->isValid())
                $error = $error."半身圖 ";

            return [
                'success' => false,
                'message' => "找不到".$error
            ];
        }

        $textureFile->storeAs($s3_texture_dir, $s3_texture_fileName, 's3'); 
        $meshFile->storeAs($s3_mesh_dir, $s3_mesh_fileName, 's3'); 
        $coverFile->storeAs($s3_cover_dir, $s3_cover_fileName, 's3'); 
        $coverHalfFile->storeAs($s3_coverHalf_dir, $s3_coverHalf_fileName, 's3'); 
            // $file = new \Illuminate\Http\UploadedFile($tmpFilePath, $s3_fileName, 'image/png', null, true);
            // $file->storeAs($s3_dir, $s3_fileName, 's3'); // 修改这里的存储方式

        return [
            'success' => true,
            'message' => [
                'user_id' => $model->user_id,
                'mesh_url' => $model->mesh_url,
                'texture_url' => $model->texture_url,
                'cover_url' => $model->cover_url,
                'id' => $model->id
            ],
        ];
        
    }

    public function resetPassword(Request $request) {

        $resetPasswordToken = $request->query('resetPasswordToken');
        $email = $request->query('email');

        if ($user = User::where('email', $email)->first()) {
            if ($user->resetPasswordToken == $resetPasswordToken)
            {
                return '<script>window.location = "http://192.168.0.112:5173/ForgetPassword/?resetPasswordToken='.$user->resetPasswordToken.'&email='.$email.'";</script>';
                // return '<script>window.location = "https://4dbox.lightmatrix3d.com/?resetPasswordToken='.$user->resetPasswordToken.'&email='.$email.'";</script>';
            }
            else
            {                
                return '<script>window.location = "http://192.168.0.112:5173/ForgetPassword/";</script>';
                // token 失效
            }
        }
    }

    /**
     * confirm mail
     */
    public function registerMember($code) {

        if ($user = User::where('confirm_code', $code)->first()) {

            if (now() > $user->confirm_code_expired_at) {
                return '<script>window.location = "http://192.168.0.112:5173/?memberRegistResult=expiredVerificationCode";</script>';
                // return '<script>window.location = "https://4dbox.lightmatrix3d.com/?memberRegistResult=expiredVerificationCode";</script>';
                return [
                    'success' => false,
                    'message' => 'expiredVerificationCode'
                ];
            }

            $user->confirm_code = null;
            $user->confirm_code_expired_at = null;
            $user->email_auth = true;
            $user->email_verified_at = now();

            $user->save();

            return '<script>window.location = "http://192.168.0.112:5173/?memberRegistResult=registerSuccess";</script>';
            // return '<script>window.location = "https://4dbox.lightmatrix3d.com/?memberRegistResult=success";</script>';
            return [
                'success' => true,
                'message' => '註冊成功！'
            ];
        };

        return '<script>window.location = "http://192.168.0.112:5173/?memberRegistResult=incorrectVerificationCode";</script>';
        // return '<script>window.location = "https://4dbox.lightmatrix3d.com/?memberRegistResult=incorrectVerificationCode";</script>';
        return [
            'success' => false,
            'message' => 'incorrectVerificationCode'
        ];
    }

    /**
     * update user
     */
    public function userUpdate(Request $request) {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'phone' => 'nullable|regex:/^09\d{2}-?\d{3}-?\d{3}$/', //手機號碼
            'password' => ['nullable', 'confirmed', 'min:8'],
            'old_password' => 'nullable|required_with:password|current_password'
        ]);

        if ($validator->fails()) {
            return [
                'success' => false,
                'message' => 'updateUserDataFailed',
                'errors' => $validator->errors()->toArray()
            ];
        }

        $user = Auth::user();
        $user->name = $request->name;

        if ($request->phone) {
            $user->phone = $request->phone;
        }

        if ($request->password) {
            $user->password = Hash::make($request->password);
        }

        $user->save();

        return [
            'success' => true,
            'message' => 'update user success!',
        ];
    }

    /**
     * update member name
     */
    public function updateMemberName(Request $request) {

        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
        ]);

        if ($validator->fails()) {
            return [
                'success' => false,
                'message' => 'updateUsernameFailed',
                'errors' => $validator->errors()->toArray()
            ];
        }

        $user = Auth::user();
        $user->name = $request->name;
        $user->save();

        return [
            'success' => true,
            'message' => 'update member name success!'
        ];
    }

    /**
     * update password
     */
    public function updatePassword(Request $request) {

        $validator = Validator::make($request->all(), [
            'password' => 'required|password|confirmed',
            'old_password' => 'required|current_password'
        ]);

        if ($validator->fails()) {
            return [
                'success' => false,
                'message' => 'updatePasswordFailed',
                'errors' => $validator->errors()->toArray()
            ];
        }

        $user = Auth::user();
        $user->password = Hash::make($request->password);
        $user->save();

        return [
            'success' => true,
            'message' => 'update password success!'
        ];
    }

    public function changePassword(Request $request) {

        $validator = Validator::make($request->all(), [
            'token' => 'required',
            'email' => 'required|email',
            'newPassword' => 'required',
        ]);

        if (!$request->token)
            abort(415);
        
        if ($validator->fails()) {
            return [
                'success' => false,
                'message' => __('register.failed'),
                'errors' => $validator->errors()->toArray()
            ];
        }

        $resetPasswordToken = $request->token;
        $email = $request->email;
        $user = User::where('email', $email)->first();

        if ($user) {
            if ($user->resetPasswordToken == $resetPasswordToken)
                $user->password = Hash::make($request->newPassword);
            else
            {
                return [
                    'success' => false,
                    'message' => "TokenExpired"
                ];
            }

            $user->save();
        }
        else {
            return [
                'success' => false,
                'message' => "UserNotFound"
            ];
        }

        return [
            'success' => true,
            'message' => "ChangeSuccess"
        ];
    }

    /**
     * query order list
     */
    public function queryOrderList() {
        if (Auth::id() == 1)
        {
            $query = Order::where(function ($query) {
                $query
                ->where('user_id', Auth::id())
                ->Where('type', 0);
            })
            ->orWhere(function ($query) {
                $query
                ->where('user_id', Auth::id())
                ->Where('type', 1)
                ->whereHas('product_solution_order', function($orderQuery) {
                    $orderQuery->where('is_activated', 1);
                });
            });
        }
        else
        {
            $query = Order::where(function ($query) {
                $query
                ->where('user_id', Auth::id())
                ->WhereDoesntHave('product_solution_order');
            })
            ->orWhere(function ($query) {
                $query
                ->where('user_id', Auth::id())
                ->whereHas('product_solution_order', function($orderQuery) {
                    $orderQuery->where('is_activated', 1);
                });
            });
        }


        return [
            'success' => true,
            'message' => new OrderCollection($query->latest()->paginate(999999)),
        ];
    }

    /**
     * update video name
     */
    public function updateVideoName(Request $request,$id) {

        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'id' => 'required|exists:media,id'
        ]);

        if ($video = Media::find($id)) {
            $video->name = $request->name;
            $video->save();
        };

        return [
            'success' => true,
            'message' => 'update video name success!'
        ];
    }

    /**
     * upload video
     */
    public function uploadVideo(Request $request) {
        try{
            $validator = Validator::make($request->all(), [
                'video' => 'required|mimes:mp4,mov,ogg,qt|max:1048576', // aaaa
            ]);

            if ($validator->fails()) {
                return [
                    'success' => false,
                    'message' => 'uploadVideoFailed',
                    'errors' => $validator->errors()->toArray()
                ];
            }

            $cpuUsage = $this->get_cpu_usage();

            if ($cpuUsage>7) {
                return[
                    'success' => false,
                    'message' => 'systemBusy',
                    'cpu' => $cpuUsage,
                ];
            }

            //create new order, media and store file to storage
            $repository = new OrderRepository();
            $repository->userUploadVideo($request);

            $media = $repository->getMedia();

            event(new PicUploaded($media));

            return [
                'success' => true,
                'message' => 'upload video success!',
                'cpu' => $cpuUsage,
            ];
        }
        catch(e) {
            return [
                'success' => false,
                'message' => e.message,
            ];
        }
    }

    public function getModel(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'orderID' => 'required',
        ]);

        $model = models::where('order_id', $request->orderID)->first();
        if(!$model)
        {
            return [
                'success' => false,
                'message' => "查不到此模型",
            ];
        }

        $order = order::where('id', $request->orderID)->first();
        if(!$order)
        {
            return [
                'success' => false,
                'message' => "查不到此訂單",
            ];
        }

        if($order->times <= 0)
        {
            return [
                'success' => false,
                'message' => "使用次數已無"
            ];
        }

        $model_Result = [
            'success' => true,
            'message' => [
                'model' =>[
                    'texture' => $model->texture_url,
                    'mesh' => $model->mesh_url,
                    'version' => $model->version
                ]
            ],
        ];

        if ($model_Result['message']["model"]["texture"] != '')
            $model_Result['message']["model"]["texture"] = Storage::disk('s3')->temporaryUrl($model_Result['message']["model"]["texture"], now()->addHour());
        if ($model_Result['message']["model"]["mesh"] != '')
            $model_Result['message']["model"]["mesh"] = Storage::disk('s3')->temporaryUrl($model_Result['message']["model"]["mesh"], now()->addHour());

        return $model_Result;
    }

    /**
     * upload picture
     */
    public function uploadPicture(Request $request) {
        try{
            $totalStart = microtime(true);
            $validator = Validator::make($request->all(), [
                'pic' => 'required',
                'order' => 'required',
                'token' => 'required',
                'machine' => 'required'
            ]);

            if ($validator->fails()) {
                return [
                    'success' => false,
                    'message' => 'uploadImageFailed',
                    'errors' => $validator->errors()->toArray()
                ];
            }

            $company = companies::where('token',$request->token)->first();

            if(!$company)
            {
                return [
                    'success' => false,
                    'message' => "查不到此公司",
                ];
            }
            $s3_pic_dir = env('APP_ENV') . "/".$company->id."/".$request->order."/"."picture"; // 修改这里的设定
            
            $t1 = microtime(true);
            $picFile = $request->file('pic');
            $t2 = microtime(true);
    
            $s3_picture_fileName = $request->order . '.' . $picFile->getClientOriginalExtension();
            
            if (!($picFile->isValid()))
            {
                return [
                    'success' => false,
                    'message' => "檔案上傳失敗，請確認您上傳的檔案是否可用",
                ];
            }
    
            $isAllUploaded = true;
            
            $storeStart = microtime(true);
            if (!$picFile->storeAs($s3_pic_dir, $s3_picture_fileName, 's3'))
                $isAllUploaded = false;
            $storeEnd = microtime(true);
    
                        
            Log::info("receive spend: " . ($t2 - $t1));
            Log::info("storeAs spend: " . ($storeEnd - $storeStart));

            if (!$isAllUploaded)
            {
                // 刪掉該 $s3_model_dir 資料夾
                return [
                    'success' => false,
                    'message' => "檔案上傳失敗，請確認您上傳的檔案是否可用",
                ];
            }
            
            $order = order::where('id', $request->order)->first();
            if (!$order)
            {
                return [
                    'success' => false,
                    'message' => "查不到此訂單",
                ];
            }

            $machine = Machine::where('id', $request->machine)->first();
            if (!$machine)
            {
                return [
                    'success' => false,
                    'message' => "查不到此機台",
                ];
            }

            if($order->method == "mail")
            {
                Log::info("mail send");
                $order->notify(new ClickTimesRemind());
            }

            $machine->status = 0;
            $machine->save();

            $order->status = 1;
            $order->save();

            $model = new models();
            $model->order_id = $order->id;
            $model->company_id = $company->id;
            $model->status = 0;
            $model->pic_url = $s3_pic_dir . '/' . $s3_picture_fileName;
            $model->save();
            $totalEnd = microtime(true);
            Log::info("total spend: " . ($totalEnd - $totalStart));
            return [
                'success' => true,
                'message' => 
                    [
                        'method' => $order->method,
                        'account' => $order->account    
                    ]
            ];
        }
        catch(e) {
            return [
                'success' => false,
                'message' => e.message,
            ];
        }
    }

    public function downloadPicture(Request $request)
    {
        try{
            $validator = Validator::make($request->all(), [
                'account' => 'required',
                'model' => 'required',
            ]);
            
            if ($validator->fails()) {
                return [
                    'success' => false,
                    'message' => 'uploadImageFailed',
                    'errors' => $validator->errors()->toArray()
                ];
            }

            $staff = staff::where('account', $request->account)->first();

            $model = models::where('id', $request->model)->first();

            if(!$model)
            {
                return [
                    'success' => false,
                    'message' => "查不到此訂單",
                ];
            }

            return [
                'success' => true,
                'message' => [
                    "picture" => Storage::disk('s3')->temporaryUrl($model->pic_url, now()->addHour())
                ]
            ];
            

        }
        catch(e) {
            return [
                'success' => false,
                'message' => e.message,
            ];
        }
    }

    public function downloadModel(Request $request)
    {
        try{
            $validator = Validator::make($request->all(), [
                'account' => 'required',
                'model' => 'required',
            ]);
            
            if ($validator->fails()) {
                return [
                    'success' => false,
                    'message' => 'uploadImageFailed',
                    'errors' => $validator->errors()->toArray()
                ];
            }

            $staff = staff::where('account', $request->account)->first();

            $model = models::where('id', $request->model)->first();

            if(!$model)
            {
                return [
                    'success' => false,
                    'message' => "查不到此訂單",
                ];
            }

            return [
                'success' => true,
                'message' => [
                    "files" => [Storage::disk('s3')->temporaryUrl($model->mesh_url, now()->addHour()),
                                Storage::disk('s3')->temporaryUrl($model->texture_url, now()->addHour())]
                ]
            ];
            

        }
        catch(e) {
            return [
                'success' => false,
                'message' => e.message,
            ];
        }
    }

    /**
     * upload canvas picture
     */
    public function uploadCanvas(Request $request) {
        try{        
            // $validator = Validator::make($request->all(), [
            //     'pic' => 'required', // |string
            // ]);
            $validator = Validator::make($request->all(), [
                'pic' => 'required_without:picFile',
                'picFile' => 'required_without:pic',
            ]);

            if ($validator->fails()) {
                return [
                    'success' => false,
                    'message' => 'uploadImageFailed',
                    'errors' => $validator->errors()->toArray()
                ];
            }

            $cpuUsage = $this->get_cpu_usage();

            if ($cpuUsage > 50) {
                return[
                    'success' => false,
                    'message' => 'systemBusy',
                    'cpu' => $cpuUsage,
                ];
            }

            // =====

            //create new order, media and store file to storage
            $repository = new OrderRepository();
            // $repository->userUploadMediaFromCanvas($request);

            if ($request->picFile)
                $repository->userUploadMediaFromFile($request);
            else
                $repository->userUploadMediaFromCanvas($request);

            $media = $repository->getMedia();
            
            $user = Auth::user();

            $target = 'points';
            if ($request->to) {
                $target = $request->to;
            }
            
            //create new order, media and store file to storage
            if ($user->$target<-(int)$request->value) {
                $repository->userAddValueFailed($request);

                return [
                    'success' => false,
                    'message' => [
                        'type' => 'not enough points. Please add value !',
                    ]
                ];
            }

            // if succ, then add value
            $user->$target += (int)$request->value;
            $user->save();

            if ($media->user_id == 598)
            {
                // || $media->user_id == 1
                AutoDeleteGuestMedia::dispatch($media->id)->delay(now()->addMinutes(10));
                // AutoDeleteGuestMedia::dispatch($media->id)->delay(now()->addSeconds(30));
            }

            event(new PicUploaded($media));

            return [
                'success' => true,
                'message' => 'upload picture success! media id: '.$media->id,
                'cpu' => $cpuUsage
            ];
        }
        catch(e) {
            return [
                'success' => false,
                'message' => e.message,
            ];
        }
    }

    /**
     * get user videos
     */
    public function videos(Request $request) {

        $validator = Validator::make($request->all(),[
            'page' => 'nullable|integer',
            'type' => ['nullable', 'regex:/^([0-9]+|all)$/'],
        ]);

        if ($validator->fails()) {
            return [
                'success' => false,
                'message' => $validator->messages()->first(),
                'errors' => $validator->errors()->toArray()
            ];
        }

        $query = Media::where('user_id', Auth::id());

        $type_check = $request->type || $request->type === 0 || $request->type === '0';
        if ($type_check && $request->type != 'all') {
            $query->whereHas('order', function($orderQuery) use ($request) {
                $orderQuery->where('type', $request->type);
            });
        }

        return [
            'success' => true,
            'message' => new MediaCollection($query->paginate(10)),
          /*   'sql' => $query->toSql(),
            'bindings' => $query->getBindings() */
        ];
    }

    /**
     * get user orders
     */
    public function orders(Request $request) {

        $validator = Validator::make($request->all(), [
            'page' => 'nullable|integer',
            'type' => ['nullable', 'regex:/^([0-9]+|all)$/'],
        ]);

        if ($validator->fails()) {
            return [
                'success' => false,
                'message' => $validator->messages()->first(),
                'errors' => $validator->errors()->toArray()
            ];
        }

        $query = Order::where('user_id', Auth::id())->orderBy('created_at', 'desc');

        if ($request->dt_condition) 
        {
            $dt_condition = Carbon::now()->subDays($request->dt_condition)->toDateString();
            $query->whereDate('created_at', '>=', $dt_condition);
        }
        
        $type_check = $request->order_type || $request->order_type === 0 || $request->order_type === '0';
        if ($type_check && $request->order_type != 'all')
        {
            $query->where('type', $request->order_type); 
        }


        if ($request->activeFilter)
        {
            if ($request->order_type === "1" || $request->order_type === 1) 
            {
                $query->whereHas('product_solution_order', function($orderQuery) {
                    $orderQuery->where('is_activated', 1);
                });
            }

            if ($request->order_type == 'all') 
            {
                $query->where(function ($orderQuery) {
                    $orderQuery->whereHas('product_solution_order', function ($pQuery) {
                        $pQuery
                        ->where('type', 1)
                        ->where('is_activated', 1);
                    })->orWhere(function ($pQuery) {
                        $pQuery
                        ->where('type', 0);
                    });
                });
            }
        }

        if ($request->hasImg) 
        {
            $query->where(function ($tmp) {
                $tmp
                ->where('type', 0)
                ->whereHas('media', function ($m_query) {
                    // $m_query->where('status', "!=", 3);
                    // ->where('status', '!=', 2);
                    $m_query->whereNotIn('status', [2, 3]);
                })
                ->orWhere('type', 1);
            });
            
            // $filteredResults = $results->filter(function ($item) {
            //     // 假设 `media` 是加载了的关系，并且 `cover` 是存储在 S3 上的文件路径
            //     if ($item->media && Storage::disk('s3')->exists($item->media->cover)) {
            //         return true;
            //     }
            //     return false;
            // });
        }
        
        return [
            'success' => true,
            'message' => new OrderCollection($query->paginate(30)),
            'request_type' => $request->order_type
/*             'sql' => $query->toSql(),
            'bindings' => $query->getBindings(),
            'condition' =>($request->order_type || $request->order_type === 0 || $request->order_type==='0') && $request->order_type != 'all' */
        ];
    }


    public function notifications(Request $request) {
        $dt_condition = Carbon::now();
        
        $query = Notification::where(function ($query) use ($dt_condition) {
            $query
            ->where('user_id', Auth::id())
            ->where('release_at', '<=', $dt_condition); 
        })
        ->orWhere(function ($query) use ($dt_condition) {
            $query
            ->whereNull('user_id')
            ->where('release_at', '<=', $dt_condition); 
        });

        $query = $query->where('is_activated', 1)->get();

        return [
            'success' => true,
            'message' => $query
        ];
    }

    /**
     * get projects
     */
    public function projects(Request $request) {
        return [
            'success' => true,
            'message' => Project::get(),
        ];
    }

     /**
     * get products
     */
    public function products(Request $request) {

        $validator = Validator::make($request->all(), [
            'page' => 'nullable|integer',
            'type' => ['nullable', 'regex:/^([0-9]+|all)$/'],
        ]);

        if ($validator->fails()) {
            return [
                'success' => false,
                'message' => $validator->messages()->first(),
                'errors' => $validator->errors()->toArray()
            ];
        }

        
        $query = Product_solution::select('product_id')->where('is_activated', 1)
        ->groupBy('product_id')
        ->with('product', function ($child_query) {
            $child_query
            ->where('type', 0)
            ->orWhere('type', 1)->has('album.albumDetail');
        })->get()->filter(function ($item) {
            return $item->product !== null;
        })->pluck('product'); 
        
        // $query = Product::get();
        //'message' => new OrderCollection ($query->paginate(10)),
        return [
            'success' => true,
            'message' => new ProductCollection($query),
/*             'sql' => $query->toSql(),
            'bindings' => $query->getBindings(),
            'condition' =>($request->order_type || $request->order_type === 0 || $request->order_type==='0') && $request->order_type != 'all' */
        ];
    }

     /**
     * get stores
     */
    public function stores(Request $request) {

        $validator = Validator::make($request->all(), [
            'page' => 'nullable|integer',
            'type' => ['nullable', 'regex:/^([0-9]+|all)$/'],
        ]);

        if ($validator->fails()) {
            return [
                'success' => false,
                'message' => $validator->messages()->first(),
                'errors' => $validator->errors()->toArray()
            ];
        }

        $query = Store::get();
        //'message' => new OrderCollection ($query->paginate(10)),
        return [
            'success' => true,
            'message' => new StoreCollection($query), // new ProductCollection ($query)
/*             'sql' => $query->toSql(),
            'bindings' => $query->getBindings(),
            'condition' =>($request->order_type || $request->order_type === 0 || $request->order_type==='0') && $request->order_type != 'all' */
        ];
    }

    /**
     * get albums
     */
    public function albums(Request $request) {

        $validator = Validator::make($request->all(), [
            'page' => 'nullable|integer',
            'type' => ['nullable', 'regex:/^([0-9]+|all)$/'],
        ]);

        if ($validator->fails()) {
            return [
                'success' => false,
                'message' => $validator->messages()->first(),
                'errors' => $validator->errors()->toArray()
            ];
        }

        $query = Album::where('user_id', Auth::id())->get();

        return [
            'success' => true,
            'message' => new AlbumCollection($query)
            
        ];
    }

    public function product_solutions(Request $request) {

        $validator = Validator::make($request->all(), [
            'page' => 'nullable|integer',
            'type' => ['nullable', 'regex:/^([0-9]+|all)$/'],
        ]);

        if ($validator->fails()) {
            return [
                'success' => false,
                'message' => $validator->messages()->first(),
                'errors' => $validator->errors()->toArray()
            ];
        }

        $query = Product_solution::where('product_id', $request->productID)->where('is_activated', 1);

        return [
            'success' => true,
            'message' => new ProductSolutionCollection($query->paginate(10)),
        ];
    }

    public function plan_solutions(Request $request) {
        $query = Plan_solution::where('is_activated', 1);
        return [
            'success' => true,
            'message' => $query->get(),
        ];
    }

    public function get2Dpics() {
        define("TYPE_PIC", 1);
        define("STATUS_PROCESSING", 0);

        $videos = Media::where('type', 1)->where('status', 0)->whereNotNull('original')->where('is_staff_uploaded',0)->get();
        $pics = [];
        foreach($videos as $video) {
            $pics[] = (object)['id' => $video->id,
            'name' => $video->name,
            'obj' =>Storage::disk('s3')->temporaryUrl($video->original??$video->obj, now()->addHour()),
            'path' => (new OrderRepository($video->order))->getPath($video->id)];
        }

        // crop 版本
        // $videos = Media::where('type', TYPE_PIC)->where('status', STATUS_PROCESSING)->whereNotNull('crop')->where('is_staff_uploaded', 0)->get();        
        // $pics = [];
        // foreach ($videos as $video) {
        //     $pics[] = (object)['id' => $video->id,
        //     'name' => $video->name,
        //     'obj' =>Storage::disk('s3')->temporaryUrl($video->crop??$video->obj, now()->addHour()),
        //     'path' => (new OrderRepository($video->order))->getPath($video->id)];
        // }


        return [
            'success' => true,
            'message' => $pics,
        ];
    }

    public function mediaChangeStatus($media) {
        define("TYPE_VID", 0);
        define("TYPE_PIC", 1);

        $repo = new OrderRepository($media->order);
        $media->status = 1;
        
        if ($media->type == TYPE_PIC) {
            $media->obj = $repo->getPath($media->id);
        }

        if ($media->type == TYPE_VID) {
            $media->obj = $repo->getVideoPath($media->id);

            //if media is created by staff, add cover
            if ($media->is_staff_uploaded == 1) {
                $media->cover = $repo->getVideoCoverPath($media->id);
            }
        }

        $media->finish_time = now();
        $media->save();

        if ($media->type == TYPE_VID) {
            event(new CompleteTransformVideo($media));
        }

        if ($media->type == TYPE_PIC) {
            event(new CompleteTransformPic($media));
        }
    }

    public function set2DpicFinish(Request $request) {

        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:media,id',
        ]);

        if ($validator->fails()) {
            return [
                'success' => false,
                'message' => 'mediaNotFound',
                'errors' => $validator->errors()->toArray()
            ];
        }

        $media = Media::where('id', $request->id)->first();
        if ($media) {
            $this->mediaChangeStatus($media);
        }

        return [
            'success' => true
        ];
    }

    public function deleteVideo(Request $request, Media $media) {
        define("STATUS_DELETED", 3);

        if ($request->user()->cannot('update', $media)) {
            abort(403);
        }

        $media->status = STATUS_DELETED;

        //delete original file
        if ($media->original && Storage::disk('s3')->exists($media->original)) {
            Storage::disk('s3')->delete($media->original);
        }

        //delete obj file
        if ($media->obj && Storage::disk('s3')->exists($media->obj)) {
            Storage::disk('s3')->delete($media->obj);
        }

        //delete cover file
        if ($media->cover && Storage::disk('s3')->exists($media->cover)) {
            Storage::disk('s3')->delete($media->cover);
        }

        $media->original = null;
        $media->obj = null;
        $media->cover = null;

        $media->save();

        return [
            'success' => true
        ];
    }

    public function videoFailed(Request $request, Media $media) {
        define("STATUS_FAILED", 2);

        if ($request->user()->cannot('update', $media)) {
            abort(403);
        }

        $media->status = STATUS_FAILED;
        $media->save();

        event(new PicUploadFailed($media));

        return [
            'success' => true
        ];
    }

    public function getVideos() {
        $media = Media::where('type', 0)->where('status', 0)->whereNotNull('original')->where('is_staff_uploaded',0)->get();
        $videos = [];
        foreach ($media as $medium) {
            $videos[] = (object)['id' => $medium->id,
            'name' => $medium->name,
            'original' => Storage::disk('s3')->temporaryUrl($medium->original, now()->addHour()),
            'path' => (new OrderRepository($medium->order))->getVideoPath($medium->id)];
        }
        return [
            'success' => true,
            'message' => $videos,
        ];
    }

    public function setVideoFinish(Request $request) {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:media,id',
        ]);

        if ($validator->fails()) {
            return [
                'success' => false,
                'message' => 'mediaNotFound',
                'errors' => $validator->errors()->toArray()
            ];
        }

        $media = Media::where('id', $request->id)->first();
        if ($media) {
            $this->mediaChangeStatus($media);
        }
        return [
            'success' => true
        ];
    }

    public function video(Request $request, Media $media) {
        try{
            $userOrders = Order::where('user_id', $request->user()->id)->where('type', '1')->get();

            $media_arr = [];
            $result_arr = [];
            $albumCollection = null;
            $tmp_media = null;

            $can_view = false;
            
            if ($request->user()->cannot('view', $media)) {
                foreach ($userOrders as $order) {
                    if ($order->product_solution_order == null) {
                        continue;
                    }

                    
                    if ($order->product_solution_order->product_solution->product->type == 0) {
                        $tmp_media = [$order->product_solution_order->product_solution->product->media];
                    }
                    else {
                        $album = collect([$order->product_solution_order->product_solution->product->album]);
                        $albumCollection = new AlbumCollection($album);
                        $tmp_media = $albumCollection->first()->media;
                    }
                    
                    array_push($media_arr, $tmp_media);
                }
                
                foreach ($media_arr as $temp_arr) {
                    foreach ($temp_arr as $temp) {
                        if ($temp->id == $media->id) {
                            $can_view = true;
                        }
                    }
                }

                if (!$can_view) {
                    abort(403);
                }
            }

            return [
                'success' => true,
                'message' => [
                    'cover' => Storage::disk('s3')->temporaryUrl($media->cover, now()->addHour()),
                    'obj' => Storage::disk('s3')->temporaryUrl($media->obj, now()->addHour()),
                ],
            ];
        }
        catch(Exception $e) {
            return var_dump($e);
        }
    }

    public function test(Request $request) {
        // Log::info('Test log');
        // Log::error('Test log');
        return [
            'success' => true,
            // 'message' =>Auth::id(),
        ];

        $query = Order::where(function ($query) use ($request) {
            $query
            ->where('user_id', Auth::id())
            ->WhereDoesntHave('product_solution_order');
        })
        ->orWhere(function ($query) use ($request) {
            $query
            ->where('user_id', Auth::id())
            ->whereHas('product_solution_order', function($orderQuery) {
                $orderQuery->where('is_activated', 1);
            });
        });
        $res = clone $query;
        
        return [
            'success' => true,
            'message' => $res->toSql()
        ];
        // $dt_condition = Carbon::now()->toDateString();

        // $query = Notification::where(function ($query) use ($dt_condition) {
        //     $query->where('user_id', Auth::id())
        //           ->whereDate('release_at', '<=', $dt_condition);
        // })
        // ->orWhere(function ($query) use ($dt_condition) {
        //     $query->whereNull('user_id')
        //           ->whereDate('release_at', '<=', $dt_condition);
        // });

        // return [
        //     'success' => true,
        //     'message' => $query->get(),
        // ];
    }

    /**
     * get projects
     */
    public function checkPaymentFlow(Request $request) {
        // check programming error
        /* $validator = Validator::make($request->all(),[
            'value' => 'nullable', //|integer
            'type' => ['nullable', 'regex:/^([0-9]+|all)$/'],
        ]);

        if ($validator->fails()) {
            return [
                'success' => false,
                'message' => $validator->messages()->first(),
                'errors' => $validator->errors()->toArray()
            ];
        } */

        $user = Auth::user();

        $target = 'points';
       
        if ($request->to) {
            $target = $request->to;
        }

        // check action type
        switch ($request->type) {
            // check 2to3 available
            case 0:
                //create new order, media and store file to storage
                if ($user->$target<-(int)$request->value) {
                    return [
                        'success' => false,
                        'message' => [
                            'type' => 'not enough points. Please add value !',
                        ]
                    ];
                }
            
            // check buy items
            case 1:
                $repository = new OrderRepository();
                $repository->createOrder($request);

                if ($user->$target<-(int)$request->value) {
                    return [
                        'success' => false,
                        'message' => [
                            'type' => 'not enough points to buy. Please add value !',
                        ]
                    ];
                }

                // after trasaction succ
                $repository->userAddValueSucc($request);
                break;

            // check Paypal payment success 
            case 2:
                $repository = new OrderRepository();
                $repository->createOrder($request);
                // ====== check paypal trasaction status =======
                
                // ====================

                // after trasaction succ
                $repository->userAddValueSucc($request);

                if (!$request->from) {
                    break;
                }

                if ($request->from == 'ads') {
                    $user->ads_times += 1;
                }
                
                break;
            
            case 3:
                

                
                break;
            
            default:
                return [
                    'success' => false,
                    'message' => [
                        'type' => 'ain\'t regular type',
                    ]
                ];
        }

        // if succ, then add value
        $user->$target+=(int)$request->value;
        $user->save();

        return [
            'success' => true,
            'message' => [
                'points' => $user->points,
                'free_points' => $user->free_points,
            ]
/*           'sql' => $query->toSql(),
            'bindings' => $query->getBindings(),
            'condition' =>($request->order_type || $request->order_type === 0 || $request->order_type==='0') && $request->order_type != 'all' */
        ];
    }


    /**
     * create album
     */
    public function albumCreate(Request $request) {

        $validator = Validator::make($request->all(), [
            'name' => 'required',
        ]);

        if ($validator->fails()) {
            return [
                'success' => false,
                'message' => __('register.failed'),
                'errors' => $validator->errors()->toArray()
            ];
        }

        $user = Auth::user();

        $album = Album::create([
            'name' => $request->name,
        ]);

        $album->user()->associate($user);
        $album->save();

        return [
            'success' => true,
            'message' => [
                'id' =>  $album->id,
                'name' =>  $album->name,
            ]
        ];
    }

    public function deleteAlbum(Request $request) {
        $albumID = $request->album['id'];
        $productsWithoutAlbum = !Product::whereHas('album', function ($query) use ($albumID) {
            $query->where('album_id', $albumID);
        })->exists();

        if (!$productsWithoutAlbum) {
            return [
                'success' => false,
                'message' => 'albumInProduct'
            ];
        }

        $album = Album::where('id', $request->album['id'])->first();
        $album->delete();

        // $album->save();
        
        return [
            'success' => true,
            'message' => [
                'id' =>  ''
            ]
        ];
    }

    /**
     * add media to album
     */
    public function editToAlbum(Request $request) {

        /* $validator = Validator::make($request->all(),[
            'name' => 'required',
        ]);

        if ($validator->fails()) {
            return [
                'success' => false,
                'message' => __('register.failed'),
                'errors' => $validator->errors()->toArray()
            ];
        } */

        $albumID = $request->album['id'];
        $productsWithoutAlbum = !Product::whereHas('album', function ($query) use ($albumID) {
            $query->where('album_id', $albumID);
        })->exists();

        if (!$productsWithoutAlbum) {
            return [
                'success' => false,
                'message' => 'albumInProduct'
            ];
        }

        $album = Album::where('id', $request->album['id'])->first();
        $request_media = $request->chosenMedia;

        if ($request->editType == 'add') {
            foreach ($request->chosenMedia as $key => $media) {
                $albumDetail = AlbumDetail::where('album_id', $request->album['id'])->where('media_id', $media['id']);
                if (!$albumDetail->exists())
                {
                    AlbumDetail::create([
                        'album_id' => $request->album['id'],
                        'media_id' => $media['id']
                    ]);
                }
                // $media = Media::where('id', $media['id'])->first();
                // $media->album()->associate($album);
                // $media->save();
            }    
        }
        else if ($request->editType == 'delete') {
            foreach ($request->chosenMedia as $key => $media) {
                $albumDetail = AlbumDetail::where('album_id', $request->album['id'])->where('media_id', $media['id']);
                if ($albumDetail->exists())
                    $albumDetail->delete();
                // $media = Media::where('id', $value['id'])->first();
                // $media->album_id = null;
                // $media->save();
            }    
        }
        
        return [
            'success' => true,
            'message' => [
                'id' =>  $media,
            ]
        ];
    }

    /**
     * subscribe a product
     */
    public function product_subscribe(Request $request) {
        $validator = Validator::make($request->all(), [
            'product_solution' => 'nullable', // |integer
            'type' => ['nullable', 'regex:/^([0-9]+|all)$/'],
        ]);

        if ($validator->fails()) {
            return [
                'success' => false,
                'message' => $validator->messages()->first(),
                'errors' => $validator->errors()->toArray()
            ];
        }

        $user = Auth::user();
                
        if ($request->product_solution["isFree"])
        {
            // 找該 plan 內的0元方案
            $FreePlan = Product_solution::where('product_id', $request->product_solution["id"])->Where('costs', 0)->first();
            
            $copy = clone $FreePlan;
            foreach ($copy as $key => $value) {
                $request->request->add([$key => $value]);
            }

            $product_solution = $FreePlan;
            $product = $product_solution->product;
            $product_solution_orders = $product_solution->product_solution_order;
        }
        else
        {
            // $have_order = Product_solution_order::where('email', 'example@example.com')->exists();
            $product_solution = Product_solution::where('id', $request->product_solution['id'])->first();
            $product = $product_solution->product;
            $product_solution_orders = $product_solution->product_solution_order;
        }

        // Prevent subscribe self.
        if ($product->type == 0) {
            $media_list = [$product->media];
        }
        else{
            $album = collect([$product->album]);
            $albumCollection = new AlbumCollection($album);
            $media_list = $albumCollection->first()->media;
        }

        foreach($media_list as $media) {
            if ($media->order->user->id == $user->id) {
                return [
                    'success' => false,
                    'message' => 'yourPhoto',
                ];
            }
        }

        // Prevent subscribe same product.
        foreach($product_solution_orders as $product_solution_order) {
            if ($product_solution_order->order->user->id == $user->id && $product_solution_order->status == 0 && $product_solution_order->is_activated != 0) {
                return [
                    'success' => false,
                    'message' => 'alreadySubscribed',
                ];
            }
        }

        // if succ, then make an order and make a solution order
        $repository = new OrderRepository();
        if ($request->product_solution["isFree"])
            $order_detail = $repository->subscribeProductFree($request, $product_solution);
        else 
        {
            $order_detail = $repository->subscribeProduct($request);

            $payment = Payment::where('transaction_id', $request->resource_id)->first();
            $payment->order_id = $order_detail->solution_order->order_id;
            $payment->save();
        }

        // $media = $repository->getMedia();
        // foreach($media_list as $media) {
        //     event(new PicUploaded($media));
        // }

        event(new AIBoxRefresh($user->id));
        // if ($user->id == 1) {
            // Log::info('ID: '.$order_detail->solution_order->order_id);
            // ProductUnsubscribe::dispatch($order_detail->solution_order->order_id)->delay(now()->addMinutes(1));
            // ProductUnsubscribe::dispatch($order_detail->solution_order->order_id)->delay(now()->addSeconds(20));
        // }
        // ====== check paypal trasaction status =======

        if ($request->product_solution["isFree"]){
            return [
                'success' => true,
                'message' => [
                    'order' => $order_detail
                ]
            ];
        }
        else {
            return [
                'success' => true,
                'message' => [
                    'order' => $order_detail,
                    'payment' => $request->resource_id,
                ]
            ];
        }
    }

    /**
     * subscribe a product
     */
    public function plan_subscribe(Request $request) {
        // check programming error
        $validator = Validator::make($request->all(), [
            'product_solution' => 'nullable', // |integer
            'type' => ['nullable', 'regex:/^([0-9]+|all)$/'],
        ]);

        if ($validator->fails()) {
            return [
                'success' => false,
                'message' => $validator->messages()->first(),
                'errors' => $validator->errors()->toArray()
            ];
        }

        $user = Auth::user();
        
        // if succ, then make an order and make a solution order
        $repository = new OrderRepository();
        $order_detail = $repository->subscribePlan($request);

        $payment = Payment::where('transaction_id', $request->resource_id)->first();
        $payment->order_id = $order_detail->solution_order->order_id;
        $payment->save();
        
        // ====== check paypal trasaction status =======

        return [
            'success' => true,
            'message' => [
                'order' => $order_detail,
                'payment' => $request->resource_id,
            ]
        ];
    }
    
    /**
     * unsubscribe a product
     */
    public function product_unsubscribe(Request $request) {
        // check programming error
        $validator = Validator::make($request->all(),[
            'order' => 'nullable', // |integer
            'type' => ['nullable', 'regex:/^([0-9]+|all)$/'],
        ]);

        if ($validator->fails()) {
            return [
                'success' => false,
                'message' => $validator->messages()->first(),
                'errors' => $validator->errors()->toArray()
            ];
        }

        $user = Auth::user();

        // $have_order = Product_solution_order::where('email', 'example@example.com')->exists();

        // Cancel mark when order variable is an Order object
        /* $order = Order::where('id', $request->order['id']=)->first(); */

        $order = Order::where('id', $request->order)->first();
        $product_solution_order = $order->product_solution_order;

        if (!$order->product_solution_order) {
            return [
                'success' => false,
                'message' => 'noOrder',
            ];
        }

        $product_solution_order->is_activated = 0;
        $product_solution_order->save();

        event(new AIBoxRefresh($order->user_id));
        
        return [
            'success' => true,
            'message' => 'unsubscribeSuccess'
        ];
    }

    /**
     * create payment_detail to table
     */
    public function checkout_order_approved(Request $request) {
        $userid = Auth::user()->id;
        $details = $request->input('details');
        $transactionId = $request->input('resourceId');
        
        $order_data = Payment::create([
            'user_id' => $userid,
            // 'product_solution_id' => $request->input('projectId'),
            'order_id' => null,
            'payment_method' => "paypal",
            'event_type' => "CHECKOUT.ORDER.APPROVED",
            'payment_amount' => $request->input('payment_amount'),
            'payment_currency' => $request->input('payment_currency'),
            'transaction_id' => $transactionId,
            'status' => $request->input('capture_status'),
            'summary' => ""
        ]);      
        
        $jsonData = json_encode($details, JSON_PRETTY_PRINT);
        $SavePath = storage_path('PamentDetails');
        if (!file_exists($SavePath)) {
            mkdir($SavePath, 0775, true);
        }

        $filePath = $SavePath . '/' .$transactionId . '-CHECKOUT_ORDER.json';
        file_put_contents($filePath, $jsonData);
        
        return [
            'success' => true,/* 
            'message' => [
                'id' => "asdfsd"
            ] */
        ];
        // return response()->json('success');
    }

    public function getUserUsage(Request $request) {
        $user_id = Auth::id();

        // 0 vid
        $vid_usage = Media::where('user_id', $user_id)->where('type', 0)->count();
        // 1 pic
        $pic_usage = Media::where('user_id', $user_id)->where('type', 1)->count();
        
        return [
            'success' => true,
            'message' => [
                'vid_usage' => $vid_usage,
                'pic_usage' => $pic_usage
            ]
        ];
    }

    public function getCurrentPlan(Request $request) {
        $user_id = Auth::id();

        $user_solution_order = Order::where('user_id', Auth::id())->where('type', 4)->whereHas('plan_solution_order', function($orderQuery) use ($request) {
            $orderQuery->where('is_activated', 1);
        })->get();

        $last_solution = Plan_solution_order::where('is_activated', 0)->whereHas('order', function ($query) {
            $query->where('user_id', Auth::id())->where('type', 4);
        });

        if ($last_solution->exists()) 
            $last_solution = $last_solution->orderBy('expired_at', 'desc')->first();
        else
            $last_solution = null;

        $user_solution_list = array();
        if (count($user_solution_order) < 1)
        {
            array_push($user_solution_list, [
                'plan' =>Plan_solution::where('costs', 0)->first(),
                'order' => null,
                'last_plan' => $last_solution,
            ]);
            return [
                'success' => true,
                'message' => $user_solution_list
            ];
        }
        else {
            array_push($user_solution_list, [
                'plan' => $user_solution_order[0]->plan_solution_order->plan_solution,
                'order' => $user_solution_order[0]->plan_solution_order,
                'last_plan' => $last_solution
            ]);
        }
        
        return [
            'success' => true,
            'message' => $user_solution_list
        ];
    }

    /**
     * check if subscribed 
     */
    public function check_product_subscribe(Request $request) {
        // check programming error
        $validator = Validator::make($request->all(), [
            'product' => 'nullable', // |integer
            'type' => ['nullable', 'regex:/^([0-9]+|all)$/'],
        ]);

        if ($validator->fails()) {
            return [
                'success' => false,
                'message' => $validator->messages()->first(),
                'errors' => $validator->errors()->toArray()
            ];
        }

        $user = Auth::user();
        
        // $have_order = Product_solution_order::where('email', 'example@example.com')->exists();
        $product = Product::where('id', $request->product['id'])->first();

        $product_solution = $product->product_solution;

        // Prevent subscribe self.
        if ($product->type == 0) {
            $media_list = [$product->media];
        }
        else {
            $album = collect([$product->album]);
            $albumCollection = new AlbumCollection($album);
            $media_list = $albumCollection->first()->media;
        }

        foreach ($media_list as $media) {
            if ($media->order->user->id == $user->id) {
                return [
                    'success' => false,
                    'message' => 'yourPhoto',
                ];
            }
        }

        // Prevent subscribe same product.
        foreach ($product_solution as $solution) {
            $product_solution_orders = $solution->product_solution_order;

            foreach ($product_solution_orders as $product_solution_order) {
                if ($product_solution_order->order->user->id == $user->id && $product_solution_order->status == 0) {
                    return [
                        'success' => false,
                        'message' => 'alreadySubscribed',
                    ];
                }
            }
        }

        return [
            'success' => true,
            'message' => "可以訂閱"
        ];
    }

    // public function throttleTest(Request $request) {
    //     return "AA";
    // }
}
