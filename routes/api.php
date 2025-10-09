<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ApiController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\UserController;
use App\Jobs\GitPull;
use Illuminate\Support\Facades\Log;
use OpenSpout\Common\Entity\Row;
use App\Http\Controllers\LineWebhookController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

//login
Route::post('/login',[ApiController::class, 'login']);
Route::post('/company_login',[ApiController::class, 'company_login']);
Route::post('/staff_login',[ApiController::class, 'staff_login']);

//staff login
Route::post('/staffLogin',[StaffController::class, 'login']);

Route::post('/testBC',[ApiController::class, 'testBC']);

// api func
Route::post('/getBC_admin',[ApiController::class, 'getBC_admin']);
Route::post('/getBC',[ApiController::class, 'getBC']);
Route::post('/getBC_info',[ApiController::class, 'getBC_info']);
Route::post('/addModels',[ApiController::class, 'addModels']);
Route::post('/addPrice',[ApiController::class, 'addPrice']);
Route::post('/getPrice',[ApiController::class, 'getPrice']);
Route::post('/removePrice',[ApiController::class, 'removePrice']);
Route::post('/editPrice',[ApiController::class, 'editPrice']);
Route::post('/addCompanyUser',[ApiController::class, 'addCompanyUser']);
Route::post('/getCompanyUser',[ApiController::class, 'getCompanyUser']);
Route::post('/removeCompanyUser',[ApiController::class, 'removeCompanyUser']);
Route::post('/companies_UpdatePassword',[ApiController::class, 'companies_UpdatePassword']);
Route::post('/sendTimesMail',[ApiController::class, 'sendTimesMail']);
Route::post('/userAddTimes',[ApiController::class, 'userAddTimes']);
Route::post('/getUserData',[ApiController::class, 'getUserData']);
Route::post('/company_CheckLevel',[ApiController::class, 'company_CheckLevel']);
Route::post('/editMaterials',[ApiController::class, 'editMaterials']);
Route::post('/editModels',[ApiController::class, 'editModels']);
Route::post('/getModelRemark',[ApiController::class, 'getModelRemark']);
Route::post('/addRemark',[ApiController::class, 'addRemark']);
Route::post('/removeRemark',[ApiController::class, 'removeRemark']);
Route::post('/uploadModel',[ApiController::class, 'uploadModel']);
Route::post('/addOrder',[ApiController::class, 'addOrder']);
Route::post('/getMachineStatus',[ApiController::class, 'getMachineStatus']);
Route::post('/uploadPicture',[ApiController::class, 'uploadPicture']);
Route::post('/methodSuccessful',[ApiController::class, 'methodSuccessful']);
Route::post('/getCompanyToken',[ApiController::class, 'getCompanyToken']);
Route::post('/get_orderList',[ApiController::class, 'get_orderList']);
Route::post('/downloadPicture',[ApiController::class, 'downloadPicture']);
Route::post('/downloadModel',[ApiController::class, 'downloadModel']);
Route::post('/getModel',[ApiController::class, 'getModel']);
Route::post('/setMachineStatus',[ApiController::class, 'setMachineStatus']);
Route::post('/line/register-and-push', [LineWebhookController::class, 'registerAndPush']);


//===NEW===
Route::post('/get_modelList',[ApiController::class, 'get_modelList']);



// Route::post('/encAllfile',[ApiController::class, 'encAllfile']);
Route::post('/getAllUser',[ApiController::class, 'getAllUser']);
// Route::post('/getTimesOrder',[ApiController::class, 'getTimesOrder']);
Route::post('/reduceTimes',[ApiController::class, 'reduceTimes']);

//company
Route::post('/company_register',[ApiController::class, 'company_register']);


//linepay func
Route::get('/confirm.php',[ApiController::class, 'confirm']);
Route::post('/send_LinePay',[ApiController::class,'send_LinePay']);


Route::get('/getMMMM',[ApiController::class, 'getMMMM']);
//register
Route::post('/register',[ApiController::class, 'register']);

//forget password
Route::post('/forgetPassword',[ApiController::class, 'forgetPassword']);

//forget password
Route::any('/resetPassword',[ApiController::class, 'resetPassword'])->name('resetPassword');

//forget password
Route::post('/changePassword',[ApiController::class, 'changePassword'])->name('changePassword');

//register member
Route::any('/registerMember/{code}',[ApiController::class, 'registerMember'])->name('registerMember');

Route::post('/get2Dpics',[ApiController::class, 'get2Dpics']);
Route::post('/set2DpicFinish',[ApiController::class, 'set2DpicFinish']);

// get videos
Route::post('/getVideos',[ApiController::class, 'getVideos']);
Route::post('/setVideoFinish',[ApiController::class, 'setVideoFinish']);


Route::post('/guestLogin',[UserController::class, 'guestLogin']);

Route::post('/test',[ApiController::class, 'test']);

// Route::middleware('throttle:10,1')->group(function () {

//     Route::get('/throttleTest',[ApiController::class, 'throttleTest']);
//     // 這裡放置需要限制的路由
// });

Route::group(['middleware' => ['auth:sanctum']], function () {
    
    Route::post('/addBC',[ApiController::class, 'addBC']);
    Route::post('/editBC',[ApiController::class, 'editBC']);
    Route::post('/getMaterial',[ApiController::class, 'getMaterial']);
    Route::post('/getAllBC',[ApiController::class, 'getAllBC']);
    Route::post('/clickCard',[ApiController::class, 'clickCard']);
    Route::post('/getUserInfo',[ApiController::class, 'getUserInfo']);
    Route::post('/removeBC',[ApiController::class, 'removeBC']);
    Route::post('/rollback_card',[ApiController::class, 'rollback_card']);
    Route::post('/rollback_times',[ApiController::class, 'rollback_times']);
    Route::post('/rollback_material',[ApiController::class, 'rollback_material']);
    Route::post('/rollback_model',[ApiController::class, 'rollback_model']);
    Route::post('/addMaterials',[ApiController::class, 'addMaterials']);

    Route::get('/user',[UserController::class, 'profile']);

    // Route::get('/getPoints',[UserController::class, 'getPoints']);

    //get user points 
    Route::post('/getPoints',[UserController::class, 'getPoints']);

    //set user VIP 
    Route::post('/setVIP',[UserController::class, 'setVIP']);

    //staff only routes
    Route::group(['middleware'=>['admin']],function(){
        //addOrderList
        Route::post('/addOrderList',[StaffController::class, 'addOrderList']);

        //add video
        Route::post('/addVideo',[StaffController::class, 'addVideo']);

         //video failed
        Route::post('/videoFailed/{media}',[ApiController::class, 'videoFailed']);

        // finish media
        Route::post('/finishMedia',[ApiController::class, 'set2DpicFinish']);

        //query all orders
        Route::post('/queryAllOrderList',[StaffController::class, 'queryAllOrderList']);

        Route::post('/uploadVideo/{media}',[StaffController::class, 'uploadVideo']);
    });


     //updateMemberName
     Route::post('/updateMemberName',[ApiController::class, 'updateMemberName']);

     //updatePassword
     Route::post('/updatePassword',[ApiController::class, 'updatePassword']);

     //userUpdate
     Route::put('/userUpdate',[ApiController::class, 'userUpdate']);

     //queryOrderList
     Route::post('/queryOrderList',[ApiController::class, 'queryOrderList']);

     //updateVideoName
     Route::post('/updateVideoName/{id}',[ApiController::class, 'updateVideoName']);

     //resend confirmation mail
    Route::post('/sendConfirmEmail',[ApiController::class, 'sendConfirmEmail']);


    //upload cropped pics from frontend
    Route::post('/uploadCanvas',[ApiController::class, 'uploadCanvas']);

    //upload video from frontend
    Route::post('/uploadVideo',[ApiController::class, 'uploadVideo']);

    //get user orders
    Route::get('/orders',[ApiController::class, 'orders']);

    //get user orders
    Route::get('/notifications',[ApiController::class, 'notifications']);

    //get user videos
    Route::get('/videos',[ApiController::class, 'videos']);

    //get projects
    Route::get('/projects',[ApiController::class, 'projects']);

    //get products
    Route::get('/products',[ApiController::class, 'products']);

    //get stores
    Route::get('/stores',[ApiController::class, 'stores']);

    //get stores
    Route::get('/albums',[ApiController::class, 'albums']);

    //get stores
    Route::get('/product_solutions',[ApiController::class, 'product_solutions']);

    //get single video
    Route::post('/media/{media}',[ApiController::class, 'video']);

    //delete user video
    Route::delete('/deleteVideo/{media}',[ApiController::class, 'deleteVideo']);

    //add user to device
    Route::post('/addDevice',[UserController::class, 'addDevice']);

    //check user payment
    Route::post('/checkPaymentFlow',[ApiController::class, 'checkPaymentFlow']);

    //create a album
    Route::post('/albumCreate',[ApiController::class, 'albumCreate']);

    //add media to album
    Route::post('/editToAlbum',[ApiController::class, 'editToAlbum']);

    //delete album
    Route::post('/deleteAlbum',[ApiController::class, 'deleteAlbum']);

    //subscribe a product
    Route::post('/product_subscribe',[ApiController::class, 'product_subscribe']);

    //subscribe a product
    Route::post('/plan_subscribe',[ApiController::class, 'plan_subscribe']);
    
    //unsubscribe a product
    Route::post('/product_unsubscribe',[ApiController::class, 'product_unsubscribe']);
    
    //check subscribe available
    Route::post('/check_product_subscribe',[ApiController::class, 'check_product_subscribe']);

    Route::post('/checkout_order', [ApiController::class, 'checkout_order_approved']);

    //get plan solutions
    Route::get('/plan_solutions',[ApiController::class, 'plan_solutions']);
    
    Route::get('/getUserUsage',[ApiController::class, 'getUserUsage']);

    Route::post('/getCurrentPlan', [ApiController::class, 'getCurrentPlan']);

    Route::get('/getTest',[ApiController::class, 'test']);
});




//webhook
Route::post('/github',function(){

    Log::info('webhook triggered');
    GitPull::dispatch();
});