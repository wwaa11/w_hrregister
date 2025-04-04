<?php
namespace App\Http\Controllers;

use App\Http\Controllers\WebController;
use App\Models\Item;
use App\Models\Project;
use App\Models\Slot;
use App\Models\Transaction;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;

class WebController extends Controller
{
    public function loginPage()
    {
        return view('login');
    }
    public function loginRequest(Request $req)
    {
        $userid = $req->userid;
        $password = $req->password;
        $data = [
            'status' => 'failed',
            'message' => null,
        ];

        $response = Http::withHeaders([
            'token' => env('API_KEY'),
        ])
            ->post('http://172.20.1.12/dbstaff/api/getuser', [
                'userid' => $req->userid,
            ])
            ->json();

        $data['message'] = 'ไม่พบรหัสพนักงานนี้ กรุณาติดต่อแผนก HR';

        if ($response['status'] == 1) {
            $userData = User::where('userid', $req->userid)->first();

            if (!$userData) {
                $newUser = new User();
                $newUser->userid = $userid;
                $newUser->password = Hash::make($userid);
                $newUser->name = $response['user']['name'];
                $newUser->position = $response['user']['position'];
                $newUser->department = $response['user']['department'];
                $newUser->division = $response['user']['division'];
                $newUser->last_update = date('Y-m-d H:i:s');
                $newUser->save();
            } else {
                $userData->name = $response['user']['name'];
                $userData->position = $response['user']['position'];
                $userData->department = $response['user']['department'];
                $userData->division = $response['user']['division'];
                $userData->last_update = date('Y-m-d H:i:s');
                $userData->save();
            }

            $data['message'] = 'รหัสพนักงาน หรือ รหัสผ่านผิด';

            if (Auth::attempt(['userid' => $userid, 'password' => $password])) {
                session([
                    'name' => $response['user']['name'],
                    'position' => $response['user']['position'],
                    'department' => $response['user']['department'],
                    'division' => $response['user']['division'],
                    'email' => $response['user']['email'],
                ]);

                $data['status'] = 'success';
                $data['message'] = 'เข้าสู่ระบบสำเร็จ';
            }
        }

        return response()->json($data, 200);
    }
    public function logoutRequest(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
    public function changePassword(Request $request)
    {
        $password = $request->password;

        $user = Auth::user();
        $user->password = Hash::make($password);
        $user->password_changed = true;
        $user->save();

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
    public function index()
    {
        $user = Auth::user();
        $projects = Project::where('project_delete', false)->get();
        $myItem = Transaction::where('user', Auth::user()->userid)
            ->where('transaction_active', true)
            ->orderBy('date', 'asc')
            ->get();

        return view('Project.index')->with(compact('user', 'myItem', 'projects'));
    }
    public function ProjectIndex($project_id)
    {
        $project = Project::find($project_id);
        $transaction = Transaction::where('project_id', $project_id)
            ->where('user', Auth::user()->userid)
            ->where('transaction_active', true)
            ->first();
        $isRegister = $transaction == null ? false : true;

        return view('Project.project')->with(compact('isRegister', 'transaction', 'project'));
    }
    public function TransactionSave(Request $req)
    {
        $response = [
            'status' => 'failed',
            'message' => 'รอบที่เลือกเต็มแล้ว!',
        ];
        $item = Item::find($req->item_id);
        if ($item->item_available > 0) {
            $item->item_available -= 1;
            $item->save();

            $new = new Transaction();
            $new->project_id = $req->project_id;
            $new->item_id = $req->item_id;
            $new->user = Auth::user()->userid;
            $new->date = $item->slot->slot_date;
            $new->save();

            $response = [
                'status' => 'success',
                'message' => 'ทำการลงทำเบียนสำเร็จ!',
            ];
        }

        return response()->json($response, 200);
    }
    public function TransactionDelete(Request $req)
    {
        $transaction = Transaction::where('project_id', $req->project_id)
            ->where('user', Auth::user()->userid)
            ->where('transaction_active', true)
            ->first();

        $transaction->transaction_active = false;
        $transaction->save();

        $item = Item::where('id', $transaction->item_id)->first();
        $item->item_available += 1;
        $item->save();

        $response = [
            'status' => 'success',
            'message' => 'ทำการเปลี่ยนรอบการลงทะเบียนสำเร็จ!',
        ];

        return response()->json($response, 200);
    }
    public function TransactionSign(Request $req)
    {
        $transaction = Transaction::find($req->transaction_id);
        $transaction->checkin = true;
        $transaction->checkin_datetime = date('Y-m-d H:i');
        $transaction->save();

        $response = [
            'status' => 'success',
            'message' => 'ลงชื่อสำเร็จ!',
        ];

        return response()->json($response, 200);
    }
    public function adminIndex()
    {
        $projects = Project::where('project_delete', false)->get();

        return view('admin.index')->with(compact('projects'));
    }
    public function adminCreateProject()
    {

        return view('admin.Project_create');
    }
    public function FulldateTH($date)
    {
        $dateTime = strtotime($date);

        $day = date('d', $dateTime);
        $month = date('m', $dateTime);
        $year = date('Y', $dateTime);

        switch ($month) {
            case '01':
                $fullmonth = 'มกราคม';
                break;
            case '02':
                $fullmonth = 'กุมภาพันธ์';
                break;
            case '03':
                $fullmonth = 'มีนาคม';
                break;
            case '04':
                $fullmonth = 'เมษายน';
                break;
            case '05':
                $fullmonth = 'พฤษภาคม';
                break;
            case '06':
                $fullmonth = 'มิถุนายน';
                break;
            case '07':
                $fullmonth = 'กรกฎาคม';
                break;
            case '08':
                $fullmonth = 'สิงหาคม';
                break;
            case '09':
                $fullmonth = 'กันยายน';
                break;
            case '10':
                $fullmonth = 'ตุลาคม';
                break;
            case '11':
                $fullmonth = 'พฤศจิกายน';
                break;
            case '12':
                $fullmonth = 'ธันวาคม';
                break;
        }
        $year = $year + 543;

        $birthDate = date_create($date);
        $nowDate = date_create(date('Y-m-d'));
        $diff = $birthDate->diff($nowDate);

        $data = $day . ' ' . $fullmonth . ' ' . $year;

        return $data;
    }
    public function adminCreateProject_AddDate(Request $req)
    {
        $dates = [];
        $start = $req->start;
        $end = $req->end;

        $startDate = new \DateTime($start);
        $endDate = new \DateTime($end . ' +1 Days');

        $interval = new \DateInterval('P1D'); // 1 day interval
        $dateRange = new \DatePeriod($startDate, $interval, $endDate);

        foreach ($dateRange as $date) {
            $dates[] = [
                'date' => $date->format('Y-m-d'),
                'title' => $this->FulldateTH($date->format('Y-m-d')),
            ];
        }

        return response()->json(['status' => 'success', 'dates' => $dates]);
    }
    public function adminStoreProject(Request $req)
    {
        $validate = false;
        if ($req->project_name !== null && $req->slot !== null && $req->item['list']) {
            $validate = true;
        } else {
            return back()->with('message', 'ข้อมูลไม่ถูกต้อง!');
        }
        if ($validate) {
            $project = new Project();
            $project->project_name = $req->project_name;
            $project->project_detail = $req->project_detail;
            $project->save();
            $slotindex = 0;
            foreach ($req->slot as $sl) {
                $slotindex += 1;
                $slot = new Slot();
                $slot->project_id = $project->id;
                $slot->slot_index = $slotindex;
                $slot->slot_date = $sl['date'];
                $slot->slot_name = $sl['title'];
                $slot->save();

                $listIndex = 0;
                foreach ($req->item['list'] as $list) {
                    $listIndex += 1;
                    $li = new Item();
                    $li->slot_id = $slot->id;
                    $li->item_index = $listIndex;
                    $li->item_name = $list['name'];
                    $li->item_detail = $list['detail'];
                    if ($req->item['item_note_1_title'] !== null) {
                        $li->item_note_1_active = true;
                        $li->item_note_1_title = $req->item['item_note_1_title'];
                        $li->item_note_1_value = $list['note_1_value'];
                    }
                    if ($req->item['item_note_2_title'] !== null) {
                        $li->item_note_2_active = true;
                        $li->item_note_2_title = $req->item['item_note_2_title'];
                        $li->item_note_2_value = $list['note_2_value'];
                    }
                    if ($req->item['item_note_3_title'] !== null) {
                        $li->item_note_3_active = true;
                        $li->item_note_3_title = $req->item['item_note_3_title'];
                        $li->item_note_3_value = $list['note_3_value'];
                    }
                    $li->item_available = $list['avabile'];
                    $li->save();
                }
            }
        }

        return redirect(env('APP_URL') . '/admin/project/' . $project->id);
    }
    public function adminViewProject($id)
    {
        $project = Project::find($id);

        return view('admin.Project_view')->with(compact('project'));
    }
    public function Project_allTransactions($id)
    {
        $project = Project::find($id);

        return view('admin.Project_allTransactions')->with(compact('project'));
    }
    public function adminExcelProjectDate($item_id)
    {
        $item = Item::find($item_id);
        $pdf = Pdf::loadView('admin.Project_export', compact('item'));

        return $pdf->stream('test.pdf');
    }
    function adminUser() 
    {
        $users = User::orderBy('admin', 'desc')->orderBy('userid', 'asc')->get();

        return view('admin.Users_Management')->with(compact('users'));
    }
    function adminUserResetPassword(Request $req)
    {
        $user = User::where('userid', $req->userid)->first();
        $user->password = Hash::make($req->userid);
        $user->password_changed = false;
        $user->save();

        $data = [
            'status' => 'success',
            'message' => 'รีเซ็ตรหัสผ่านสำเร็จ',
        ];

        return response()->json($data, 200);
    }
    function admincheckinProject($project_id)
    {
        $project = Project::find($project_id);
        $transactions = Transaction::where('project_id', $project_id)->where('transaction_active', true)->where('checkin', true)->where('hr_approve', false)->get();

        return view('admin.Project_checkin')->with(compact('project','transactions'));
    }
    function admincheckinProjectApprove(Request $req)
    {
        $transaction = Transaction::find($req->id);
        $transaction->hr_approve = true;
        $transaction->hr_approve_datetime = date('Y-m-d H:i:s');
        $transaction->save();

        $data = [
            'status' => 'success',
            'message' => 'Approve สำเร็จ',
        ];

        return response()->json($data, 200);
    }
}
