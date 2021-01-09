<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use App\Models\User;
use App\Models\Network;
use App\Models\VerifyMail;
use App\Models\Country;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use App\Mail\Mail as SendMail;

class RegisterController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Register Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users as well as their
    | validation and creation. By default this controller uses a trait to
    | provide this functionality without requiring any additional code.
    |
    */

    use RegistersUsers;

    /**
     * Where to redirect users after registration.
     *
     * @var string
     */
    protected $redirectTo = RouteServiceProvider::HOME;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest');
    }

    public function verifyMailSend(Request $request)
    {
        $userModel = new User;
        $networkModel = new Network;
        $verifyMailModel = new VerifyMail;

        // トークンの存在チェック（のちにバリデーションにする）
        if (!$userModel->where('qr_token', $request->qr_token)->exists()) {
            return '存在しないトークンです';
        }

        // トークンからユーザーを取得
        $id = $userModel->where('qr_token', $request->qr_token)->first()->id;

        // トークンからポジションのユーザーを取得
        $p_id = $userModel->where('pr_token', $request->pr_token)->first()->id;

        // 紹介者のネットワーク情報を取得
        $position_structure = $networkModel->where('user_id', $id)->first()->position_structure;

        if (!$networkModel->where('position_structure', 'like', $position_structure.'%')->where('user_id', $p_id)->exists()){
            return '登録できないポジションです';
        }

        // ヴェリファイ用トークンの作成
        $verify_token = sha1(uniqid(bin2hex(random_bytes(20))));

        // すでに存在する場合は削除
        $verifyMailModel->where('email', $request->email)->delete();

        // 一時領域に新しく作成
        $verifyMailModel->create([
            'email' => $request->email,
            'introducer_id' => $id,
            'position_id' => $p_id,
            'verify_token' => $verify_token,
            'expired_at' => date("Y-m-d H:i:s", strtotime("+300 minute")),
        ]);

        // メール送信
        $data = ['verify_url' => Config('app.front_register_url').'?verify_token='.$verify_token,];
        \Mail::to($request->email)->send(new SendMail($data, 'verify_mail'));

        return true;

    }

    public function register(Request $request)
    {
        // 一時領域から紹介者情報を取得
        $verifyMail = VerifyMail::where('verify_token', $request->verify_token)->first();

        // 有効性チェック（のちにバリデーションにする）
        if ($verifyMail == null or $verifyMail->expired_at < date('Y-m-d H:i:s')) {
            return '有効期限が切れています';
        }

        if (User::where('email', $verifyMail->email)->exists()) {
            return 'すでに存在するメールアドレスです';
        }

        // パラメータに追加
        $request->offsetSet('email', $verifyMail->email);
        $request->offsetSet('email_verified_at', date('Y-m-d H:i:s'));
        $request->offsetSet('qr_token', sha1(uniqid(bin2hex(random_bytes(20)))));
        $request->offsetSet('qr_issuedated_at', date('Y-m-d H:i:s'));
        $request->offsetSet('pr_token', uniqid(bin2hex(random_bytes(1))));
        $request->offsetSet('pr_issuedated_at', date('Y-m-d H:i:s'));
        $request->offsetSet('password', bcrypt($request->password));

        // ポジションのユーザー情報を取得
        $positionUser = Network::where('user_id', $verifyMail->position_id)->first();

        // ユーザー作成
        $user = User::create($request->all());
        $this->networkInsert($user, $verifyMail, $positionUser);


        // メール送信
        \Mail::to($user->email)->send(new SendMail([], 'register_complete'));

        return 1;
    }

    private function networkInsert($user, $verifyMail, $positionUser)
    {
        $network = new Network;
        $parent = $network->where('user_id', $verifyMail->introducer_id)->first();

        $data = [
            'user_id' => $user->id,
            'introducer_id' => $verifyMail->introducer_id,
            'position_id' => $verifyMail->position_id,
            'introducer_referral_date' => date('Y-m-d'),
            'position_referral_date' => date('Y-m-d'),
            'introducer_stage' =>  $parent->introducer_stage + 1,
            'position_stage' => $positionUser->position_stage + 1,
            'introducer_structure' => $parent->introducer_structure . '/' . $user->id,
            'position_structure' => $positionUser->position_structure . '/' . $user->id,
        ];

        return $network->create($data);
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array  $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        return Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);
    }

    public function getVerifyMail(Request $request)
    {

        $email = VerifyMail::where('verify_token', $request->verify_token)->first()->email;

        $res = [
            'email' => $email,
            'countries' => Country::where('status', true)->get()->toArray()
        ];
        return $res;
    }
}
