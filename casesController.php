<?php

namespace App\Http\Controllers\Admin;

use Alkoumi\LaravelHijriDate\Hijri;
use App\Http\Controllers\Controller;
use App\Mail\AdminCorrespondencesEmail;
use App\Model\Appointment;
use App\Model\CaseDocuments;
use App\Model\CaseHearing;
use App\Model\CaseJudgment;
use App\Model\CasesOpponents;
use App\Model\Opponents;
use App\Model\CaseProcedures;
use App\Model\CaseQuestionnaires;
use App\Model\Cases;
use App\Model\CaseSession;
use App\Model\CasesPhoneCode;
use App\Model\CaseUser;
use App\Model\CaseVeto;
use App\Model\Category;
use App\Model\HrNotice;
use App\Model\Payment;
use App\Model\Questionnaire;
use App\Model\Service;
use App\Model\Task;
use App\Model\User;
use Carbon\Carbon;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Kreait\Firebase\Auth;
use Yajra\DataTables\DataTables;

class CasesController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('can:show-cases', ['only' => ['index', 'load']]);
        $this->middleware('can:create-cases', ['only' => ['create', 'store']]);
        $this->middleware('can:edit-cases', ['only' => ['edit', 'update']]);
        $this->middleware('can:delete-cases', ['only' => ['destroy']]);
        $this->middleware('can:cases-status', ['only' => ['activate', 'disable']]);
    }

    /**
     * Show the application dashboard.
     *
     * @return Renderable
     */
    public function index()
    {
        $case_status = Category::query()->where('type', 'case_status')->get();
        $case_type = 'case';
        return view('admin.pages.cases.index', compact('case_status', 'case_type'));
    }

    public function appeal()
    {
        $case_status = Category::query()->where('type', 'case_status')->get();
        $case_type = 'appeal';
        return view('admin.pages.cases.index', compact('case_status', 'case_type'));
    }

    public function higher()
    {
        $case_status = Category::query()->where('type', 'case_status')->get();
        $case_type = 'higher';
        return view('admin.pages.cases.index', compact('case_status', 'case_type'));
    }

    public function load(Request $request)
    {

        $cases = Cases::query();
        //check if request->start_date is set and end_date is set
        // REMOVED: Expensive update query that was running on every load
        // Cases::query()->update(['case_located_at' => 'court'],);
        if ($request->start_date && $request->end_date) {
            $cases->whereBetween('created_at', [$request->start_date, $request->end_date]);
        }
        //chaeck if request->case_status_id is not empty
        if ($request->case_status_id) {
            $cases->where('case_status_id', $request->case_status_id);
        }

        if ($request->sub_category_id) {
            $cases->where('sub_category_id', $request->sub_category_id);
        }

        //check if request->court_id is not empty
        if ($request->court_id) {
            $cases->where('court_id', $request->court_id);
        }
        //check if request->circle_id is not empty
        if ($request->circle_id) {
            $cases->where('circle_id', $request->circle_id);
        }
        //check if request->user_id is not empty
        if ($request->user_id) {
            $caseUser = CaseUser::query()->where('user_id', $request->user_id)->get()->pluck('case_id')->toArray();
            $cases->whereIn('id', $caseUser);
        }
        if ($request->filled('customer_type')) {
            $customerType = $request->input('customer_type');

            $cases->whereHas('customer', function ($query) use ($customerType) {
                $query->where('role_id', $customerType);
            });
        }
        if ($request->opponent_id) {
            $caseUser = CasesOpponents::query()->where('opponent_id', $request->opponent_id)->get()->pluck('case_id')->toArray();
            $cases->whereIn('id', $caseUser);
        }

        if ($request->client_characteristic_id) {
            $caseUser = CaseUser::query()->where('user_characteristic_id', $request->client_characteristic_id)->get()->pluck('case_id')->toArray();
            $cases->whereIn('id', $caseUser);
        }
        if ($request->filled('opponent_type')) {
            $opponentType = $request->input('opponent_type');

            $cases->whereHas('opponent', function ($query) use ($opponentType) {
                $query->where('type', $opponentType);
            });
        }
        $cases->where('case_level', $request->case_type);
        if (in_array(auth()->user()->role_id, [3, 4])) {
            $cases->whereHas('users', function ($query) {
                $query->where('user_id', auth()->user()->id)->where('user_department_id', NUll);
            });
        }
        $cases->with([
            'users.characteristic',
            'clients.characteristic',
            'case_status',
            'court',
            'case_category',
            'addedName',
            'caseOpponents.opponents'
        ]);
        $search = $request->get('search', false);
        if ($search) {
            $cases = $cases->where(function ($query) use ($search) {
                $query->where('case_number', 'like', '%' . $search . '%')
                    ->orWhere('date', 'like', '%' . $search . '%')
                    //                    ->orWhere('ar_case_title', 'like', '%' . $search . '%')
                    //                    ->orWhere('en_case_title', 'like', '%' . $search . '%')
                    ->orWhere('id', 'like', '%' . $search . '%')->orwhereHas('court', function ($query) use ($search) {
                        $query->where('ar_name', 'like', '%' . $search . '%');
                    })->orwhereHas('case_status', function ($query) use ($search) {
                        $query->where('en_name', 'like', '%' . $search . '%')
                            ->orWhere('ar_name', 'like', '%' . $search . '%');
                    });
            });
        }
        return DataTables::make($cases)
            ->escapeColumns([])
            ->addColumn('created_at', function ($case) {
                return Carbon::parse($case->created_at)->toDateString();
            })
            ->addColumn('case_file_number', function ($case) {
                $number = $case->id;
                return (string)$number;
            })
            ->addColumn('case_status', function ($case) {
                return optional($case->case_status)->name ?? '-';
            })
            ->addColumn('date', function ($case) {
                //if year is 0000 then return empty

                return $case->date . '  ' . hijri($case->date, 'short');
            })
            ->addColumn('case_status_style', function ($case) {
                return optional($case->case_status)->style;
            })
            ->addColumn('clients', function ($case) {
                $name = "";
                $clients = $case->clients->where('user_department_id', NULL);
                if (count($clients) > 1) {
                    $name .= '<span class=" mx-1">' . $clients[0]->user->full_name . ' ..وآخرون</span>';
                } else {
                    foreach ($clients as $user) {
                        $name .= '<span class=" mx-1">' . $user->user->full_name . '</span>';
                    }
                }
                return $name;
            })
            ->addColumn('client_characteristics', function ($case) {
                $characteristics = "";
                $clients = $case->clients->where('user_department_id', NULL);

                if (count($clients) > 1) {
                    // Get all unique characteristics
                    $uniqueCharacteristics = $clients->pluck('characteristic.name')->filter()->unique()->values();

                    if ($uniqueCharacteristics->count() == 1) {
                        // All clients have the same characteristic - show it once
                        $characteristics .= '<span class=" mx-1">' . $uniqueCharacteristics->first() . '</span>';
                    } else {
                        // Different characteristics - show first one + "..وآخرون"
                        $firstClient = $clients->first();
                        if ($firstClient && $firstClient->characteristic) {
                            $characteristics .= '<span class=" mx-1">' . $firstClient->characteristic->name . ' ..وآخرون</span>';
                        } else {
                            $characteristics .= '<span class=" mx-1">- ..وآخرون</span>';
                        }
                    }
                } else {
                    // Show single client's characteristic
                    foreach ($clients as $client) {
                        if ($client->characteristic) {
                            $characteristics .= '<span class=" mx-1">' . $client->characteristic->name . '</span>';
                        } else {
                            $characteristics .= '<span class=" mx-1">-</span>';
                        }
                    }
                }

                return $characteristics ?: '-';
            })
            ->addColumn('opponents', function ($case) {
                $name = "";
                $Opponents = $case->caseOpponents;
                if (count($Opponents) > 1) {
                    $name .= '<span class=" mx-1">' . optional($Opponents[0]->opponents)->full_name . ' ..وآخرون</span>';
                } else {
                    foreach ($Opponents as $Opponent) {
                        $name .= '<span class=" mx-1">' . optional($Opponent->opponents)->full_name . '</span>';
                    }
                }
                return $name ?: '-';
            })
            ->addColumn('court_name', function ($case) {
                return optional($case->court)->name ?? '';
            })
            ->addColumn('case_title', function ($case) {
                return optional($case->case_category)->name;
            })
            ->addColumn('actions', function ($case) {
                return null;
            })
            ->addColumn('related', function ($case) {
                return null;
            })
            ->addColumn('addedName', function ($case) {
                return $case->addedName->fullname ?? '';
            })
            ->make();
    }

    public function storeCaseSession(Request $request)
    {
        $request->validate([
            'case_id' => 'required',
        ]);
        $data = $request->only([
            'case_id',
            'task_id',
            'session_details',
            'court_decision',
            'next_session_tasks'
        ]);
        CaseSession::query()->create($data);
        return $request->all();
    }

    public function loadCaseSession(Request $request)
    {
        $case_id = $request->case_id;

        return CaseSession::query()->where('case_id', $case_id)->with('task')->get();
    }

    public function deleteJudgmentCase(Request $request)
    {
        $id = $request->id;
        $case = CaseJudgment::query()->findOrFail($id);
        $case->forceDelete();
        return $request->all();
    }

    public function deletecaseObjections(Request $request)
    {
        $id = $request->id;
        $case = CaseVeto::query()->findOrFail($id);
        $case->forceDelete();
        return $request->all();
    }

    public function storeJudgmentCase(Request $request)
    {
        $request->validate([
            'case_id' => 'required',
        ]);
        $data = $request->only([
            'case_id',
            'judgment_number',
            'date',
            'court_id',
            'notify_duration'
        ]);
        //upload document file
        if ($request->has('document')) {
            $Document = $request->file('document');
            $data['document'] = uploadFile($Document, 'Cases/Documents');
        }
        $item = CaseJudgment::query()->create($data);
        $employee_ids = $item->case->users->pluck('user_id')->toArray();
        $employee_ids[] = 26;
        HrNotice::query()->create([
            'ar_subject' => 'حكم جديد',
            'en_subject' => 'New judgment',
            'ar_description' => 'تم اضافة حكم جديد للقضية رقم ' . $item->case_id,
            'en_description' => 'New judgment added to case number ' . $item->case_id,
            'type' => 'employee',
            'date' => $item->notify_duration_at,
            'employee_ids' => $employee_ids,
        ]);
        return $item;
    }

    public function storecaseObjections(Request $request)
    {
        $request->validate([
            'case_id' => 'required',
            'objection_type' => 'required|in:appeal,cassation,reconsideration',
            'request_number' => 'required|string',
        ]);
        $data = $request->only([
            'case_id',
            'objection_type',
            'request_number',
            'ar_reason',
            'en_reason',
            'date',
            'ar_result',
            'en_result'
        ]);
        if ($request->has('document')) {
            $Document = $request->file('document');
            $data['document'] = uploadFile($Document, 'Cases/Objections');
        }
        $item = CaseVeto::query()->create($data);

        // Send WhatsApp notification to clients
        if ($item) {
            $case = $item->case;
            $case_number = $case->case_number ?? $case->id;
            $clients = $case->clients->where('user_department_id', NULL);

            foreach ($clients as $client) {
                $user = $client->user;
                $mobile = $user->mobile;
                $additional_mobile = $user->additional_mobile;

                if ($mobile && $user->send_notification == 'active') {
                    if ($additional_mobile) {
                        $mobile = $mobile . ',' . $additional_mobile;
                    }

                    $objection_type_ar = admin($item->objection_type, [], 'ar');
                    $message = "موكلنا العزيز\nنفيدكم بأنه تم تقديم طلب ({$objection_type_ar}) رقم الطلب ({$item->request_number}) في القضية رقم ({$case_number}) وإرساله بنجاح وسيتم إشعاركم بما يستجد.\nوالله يحفظكم ويرعاكم.";

                    $this->sendWhatsApp($mobile, $message);

                    // Send email notification
                    $email = $user->email;
                    if (!empty($email)) {
                        Mail::to($email)->send(new AdminCorrespondencesEmail($message, $user->main_language));
                    }
                }
            }
        }

        return $item;
    }

    public function loadJudgmentCase(Request $request)
    {
        $case_id = $request->case_id;

        return CaseJudgment::query()->where('case_id', $case_id)->with('court')->get();
    }

    public function loadcaseObjections(Request $request)
    {
        $objections = CaseVeto::query();
        $objections->orderBy('date', 'DESC');
        if ($request->get('case_id')) {
            $objections->where('case_id', $request->get('case_id'));
        }
        return DataTables::make($objections)
            ->escapeColumns([])
            ->addColumn('created_at', function ($objection) {
                return Carbon::parse($objection->created_at)->toDateString() . ' ' . Carbon::parse($objection->created_at)->toTimeString();
            })
            ->addColumn('objection_type', function ($objection) {
                return $objection->objection_type ? admin($objection->objection_type) : '';
            })
            ->addColumn('request_number', function ($objection) {
                return $objection->request_number ?? '';
            })
            ->addColumn('actions', function ($case) {
                return null;
            })
            ->make();
    }

    public function editObjections($id, Request $request)
    {
        $data = CaseVeto::query()->findOrFail($id);
        return view('admin.pages.cases.objections.edit', ['data' => $data, 'id' => $id]);
    }

    public function updateObjections(Request $request)
    {
        $request->validate([
            'objection_type' => 'required|in:appeal,cassation,reconsideration',
            'request_number' => 'required|string',
            'document' => 'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,txt,zip,rar,7z,jpg,jpeg,png,gif,bmp,svg'
        ], [
            'objection_type.required' => admin('Objection Type Is Required'),
            'request_number.required' => admin('Request Number Is Required'),
            'document.image' => admin('Document Must Be Image'),
            'document.mimes' => admin('Document Must Be Pdf,Doc,Docx,Xls,Xlsx,Ppt,Pptx,txt,Zip,Rar,7z,Jpg,Jpeg,Png,Gif,Bmp,Svg'),
        ]);
        $data = $request->only([
            'objection_type',
            'request_number',
        ]);

        if ($request->has('document')) {
            $ID_photo = $request->file('document');
            $data['document'] = uploadFile($ID_photo, 'Cases/Objections');
        }
        $item = CaseVeto::query()->where('id', $request['id'])->update($data);

        if ($item) {
            $return = ["result" => "ok", "message" => admin("Add Operation Successfully")];
        } else {
            $return = ["result" => "error", "message" => admin("Add Operation Failed")];
        }
        return $return;
    }

    public function loadUsers(Request $request)
    {
        //load cases users
        $id = $request->get('case_id');
        if ($request->get('type') == 'lawyer') {
            $cases = CaseUser::query()->where('case_id', $id)
                ->where('user_department_id', 1);
        } else if ($request->get('type') == 'advisor') {
            $cases = CaseUser::query()->where('case_id', $id)
                ->where('user_department_id', 2);
        } else {

            $cases = CaseUser::query()->where('case_id', $id)
                ->where('user_department_id', null);
        }
        return DataTables::make($cases->with('user'))
            ->escapeColumns([])
            ->addColumn('role', function ($user) {
                return $user->user?->role?->name ?? '';
            })
            ->addColumn('user_full_name', function ($user) {
                $nickname = optional($user->user->details)->nick_name . ' ' ?? '';
                $name = optional($user->user)->full_name ?? '';
                $fullname = $nickname . $name;
                return $fullname;
            })
            ->addColumn('user_email', function ($user) {
                return optional($user->user)->email ?? '';
            })
            ->addColumn('characteristic', function ($user) {
                return $user->characteristic->name ?? '';
            })
            ->addColumn('user_mobile', function ($user) {
                return optional($user->user)->mobile ?? '';
            })
            ->addColumn('actions', function ($user) {
                return null;
            })->orderColumn('full_name', function ($query, $order) {
                $query->orderBy('first_name', $order);
            })->orderColumn('email', function ($query, $order) {
                $query->orderBy('email', $order);
            })->orderColumn('mobile', function ($query, $order) {
                $query->orderBy('mobile', $order);
            })
            ->make();
    }

    public function changeStatus(Request $request)
    {
        $status = $request->get('status_id');
        $id = $request->get('id');
        $case = Cases::query()->find($id);
        $case->case_status_id = $status;
        $case->save();
        return response()->json(['status' => 'success', 'message' => 'تم تغيير الحالة بنجاح']);
    }

    public function changeLevel(Request $request)
    {
        $level = $request->get('level');
        $id = $request->get('case_id');
        $case = Cases::query()->find($id);
        $case->case_level = $level;
        $case->save();
        return response()->json(['status' => 'success', 'message' => 'تم تغيير الحالة بنجاح']);
    }

    public function loadQuestionnaire($id)
    {
        //load all casesQuestionnaire
        $questions = Questionnaire::query()->where('sub_category_id', $id)->get();

        return $questions;
    }

    /**
     * Show the application dashboard.
     *
     * @return Renderable
     */
    public function addClient(Request $request)
    {
        //add new record to CaseUser
        $opponents_name = [];
        $caseUsersName = [];
        $case_id = $request->get('case_id');
        $client_id = $request->get('client_id');
        $data['user_id'] = $client_id;
        $data['case_id'] = $case_id;
        $data['user_characteristic_id'] = $request->get('user_characteristic_id');
        $case = Cases::find($case_id);
        $case_number = $case->case_number ?? $case->id;
        $client = User::find($client_id);
        $lang = $client->main_language;
        $mobile = $client->mobile;
        $email = $client->email;
        $additional_mobile = $client->additional_mobile;
        $n_lang = $lang . '_name';
        $category = $case ? $case->case_category->$n_lang : "";
        app()->setlocale($lang);
        $Opponents = CasesOpponents::query()->with('opponents')->where('case_id', $case->id)->get();
        foreach ($Opponents as $opponent) {
            $opponents_name[] = $opponent->opponents->full_name;
        }
        $caseUsers = $case->clients->where('user_department_id', NULL);
        foreach ($caseUsers as $name) {
            $caseUsersName[] = $name->user->full_name;
        }
        $opponents_name = trim(implode(",", array_filter($opponents_name)));
        $caseUsersName = trim(implode(",", array_filter($caseUsersName)));
        if ($request->get('type') == 'lawyer') {
            $data['user_department_id'] = 1;
            $CaseUsers = CaseUser::query()->create($data);
            if ($lang == 'en') {
                $message = 'His Excellency ' . $client->details->en_nickname . ' ' . $client->full_name . ' A new case number (' . $case_number . ') and type (' . $category . ') has been referred to the client ' . $caseUsersName . ' with the opponent ' . $opponents_name . '. Please do the necessary regarding the case.';
            } else {
                $message = 'سعادة ' . $client->details->ar_nickname . ' ' . $client->full_name . ' تم إحالة قضية جديدة رقم (' . $case_number . ') ونوعها (' . $category . ') للموكل ' . $caseUsersName . ' مع الخصم ' . $opponents_name . '، يرجى عمل اللازم حيال القضية.';
            }
        } else if ($request->get('type') == 'advisor') {
            $data['user_department_id'] = 2;
            $CaseUsers = CaseUser::query()->create($data);
            if ($lang == 'en') {
                $message = 'His Excellency ' . $client->details->en_nickname . ' ' . $client->full_name . ' A new case number (' . $case_number . ') and type (' . $category . ') has been referred to the client ' . $caseUsersName . ' with the opponent ' . $opponents_name . '. Please do the necessary regarding the case.';
            } else {
                $message = 'سعادة ' . $client->details->ar_nickname . ' ' . $client->full_name . ' تم إحالة قضية جديدة رقم (' . $case_number . ') ونوعها (' . $category . ') للموكل ' . $caseUsersName . ' مع الخصم ' . $opponents_name . '، يرجى عمل اللازم حيال القضية.';
            }
        } else {
            $CaseUsers = CaseUser::query()->create($data);
            if ($lang == 'en') {
                $message = "Dear Client, " . $client->details->en_fullName . "  we would like to inform you that a new case number (" . $case_number . ") and its type (" . $category . ")  has been added with an opponent (" . $opponents_name . ").";
            } else {
                $message = "موكلنا العزيز " . $client->details->ar_fullName . " نفيدكم بأنه تم إضافة قضية جديدة رقم (" . $case_number . ") ونوعها (" . $category . ") مع الخصم (" . $opponents_name . ").";
            }
        }
        if ($CaseUsers) {
            if ($mobile && $client->send_notification == 'active') {
                if ($additional_mobile) {
                    $mobile = $mobile . ',' . $additional_mobile;
                }
                $this->sendWhatsApp($mobile, $message);
            }

            if (!empty($email) && !empty($message) && $client->send_notification == 'active') {
                Mail::to($email)->send(new AdminCorrespondencesEmail($message, $lang));
            }
            $return = ["result" => "ok", "message" => admin("Add Operation Successfully")];
        } else {
            $return = ["result" => "error", "message" => admin("Add Operation Failed")];
        }
        return $return;
    }

    public function create()
    {
        return view('admin.pages.cases.add', ['edit' => true]);
    }

    public function checkIfCaseCategoryHasQuestionnaires($case_category_id)
    {
        $category = Questionnaire::query()->where('category_id', $case_category_id)->first();
        if ($category) {
            return 'true';
        }
        return 'false';
    }

    public function store(Request $request)
    {
        $request->validate(
            [
                //            'ar_case_title'=>'required',
                //            'case_number'=>'required',
                //            'date'=>'required',
            ],
            [
                //            'ar_case_title.required'=>admin('Arabic Case Title Is Title'),
                //            'sub_category_id'=>admin('Case Category Is Required'),
                //            'case_status_id'=>admin('Case Status Is Required'),
                //            'case_number'=>admin('Case Number Is Required'),
                //            'date'=>admin('Case Date Is Required'),
            ]
        );
        //check if request is valid
        $data = $request->only([
            //            'ar_case_title',
            'case_number',
            'case_status_id',
            'department',
            'sub_category_id',
            'case_located_at',
            'city_id',
            'date',
            'court_id',
            'authority',
            'ar_reason',
            'en_reason',
            'status',
            'characterize_id',
            'circle_id',
            'date_hijri'
        ]);
        //        $data['en_case_title'] = $data['ar_case_title'];

        $data['case_located_at'] = 'court';
        $data['sub_category_id'] = $request->get('sub_category_id');
        if ($request->get('case_id') && $request->get('case_id') != 1000000000) {
            $id = $request->get('case_id');
            $data['id'] = $id;
            Cases::query()->findOrFail($id)->update($data);
            $case_id = $request->get('case_id');
            $item = Cases::query()->findOrFail($case_id);
        } else {

            $data['added_by'] = auth()->user()->id;
            $item = Cases::query()->create($data);
            $case_id = $item->id;
        }
        if (is_array($request->opponents) && count($request->opponents) > 0) {
            CasesOpponents::where('case_id', $case_id)->delete();
            foreach ($request->opponents as $opponent) {
                CasesOpponents::create([
                    'case_id' => $case_id,
                    'opponent_id' => $opponent,
                ]);
            }
        }
        $questions = $item->case_category->questionnaires;
        $questionsIds = $questions->pluck('id')->toArray();
        CaseQuestionnaires::query()->where('case_id', $case_id)->whereNotIn('questionnaire_id', $questionsIds)->forceDelete();
        foreach ($questions as $question) {
            $caseQuestionnaire = CaseQuestionnaires::query()->where('questionnaire_id', $question->id)->where('case_id', $case_id)->first();
            if (!$caseQuestionnaire) {
                $questionnaire = new CaseQuestionnaires();
                $questionnaire->questionnaire_id = $question->id;
                $questionnaire->case_id = $case_id;
                $questionnaire->type = $question->type;
                $questionnaire->save();
            }
        }
        $case_questionnaire = $item->questionnaires;
        if ($item) {
            if ($request->get('case_id') && $request->get('case_id') != 1000000000) {
                $ar_subject = 'تحديث القضية';
                $en_subject = 'Case update';
                $ar_description = 'تم تحديث قضية رقم ' . $item->id;
                $en_description = 'Case No. ' . $item->id . ' has been updated';
                $return = ["result" => "ok", "message" => admin("Case Updated Successfully"), 'id' => $case_id, 'case' => $case_questionnaire];
            } else {
                $ar_subject = 'قضية جديد';
                $en_subject = 'New Case';
                $ar_description = 'تم اضافة قضية رقم ' . $item->id;
                $en_description = 'Case No. ' . $item->id . ' has been added';
                $return = ["result" => "ok", "message" => admin("Add Operation Successfully"), 'id' => $item->id, 'case' => $case_questionnaire];
            }
            HrNotice::query()->create([
                'ar_subject' => $ar_subject,
                'en_subject' => $en_subject,
                'ar_description' => $ar_description,
                'en_description' => $en_description,
                'type' => '',
                'date' => $item->date,
                'employee_ids' => [26],
            ]);
        } else {
            $return = ["result" => "error", "message" => admin("Add Operation Failed")];
        }
        return $return;
    }

    public function storeQuestionnaire(Request $request)
    {
        $questionnaire_ids = $request->get('questionnaire_id');
        $questionnaire_idx = $request->get('questionnaire_idx');
        $replies = $request->get('replay');
        $questions = Cases::query()->where('id', $request->get('case_id'))->first();
        Cases::where('id', $request->get('case_id'))->update([
            'detailed_topic_ar' => $request->detailed_topic_ar,
            'detailed_topic_en' => $request->detailed_topic_en,
            'requests_ar' => $request->requests_ar,
            'requests_en' => $request->requests_en,
            'supports_ar' => $request->supports_ar,
            'supports_en' => $request->supports_en,
        ]);
        if ($questions && isset($questions->questionnaires)) {
            $questions = $questions->questionnaires;
            $other_questions = $questions->where('type', 'multi_select');
            $questions = $questions->where('type', '!=', 'multi_select');
            if ($questions) {
                foreach ($questions as $key => $que) {
                    $questionnaire = $que;
                    if ($questionnaire) {

                        if ($questionnaire->questionnaires->type == 'long_text') {
                            $questionnaire->long_reply = $replies[$key];
                            $questionnaire->save();
                        } elseif ($questionnaire->questionnaires->type == 'small_text') {
                            $questionnaire->small_reply = $replies[$key];
                            $questionnaire->save();
                        } elseif ($questionnaire->questionnaires->type == 'date') {
                            $questionnaire->data_reply = $replies[$key];
                            $questionnaire->save();
                        } else if ($questionnaire->questionnaires->type == 'select') {
                            $questionnaire->options = $replies[$key];
                            $questionnaire->save();
                        }
                    }
                }
            }
            if ($other_questions) {
                foreach ($other_questions as $key => $que) {
                    $questionnaire = $que;
                    $key = $key - 1;
                    if ($questionnaire) {
                        $questionnaire->multi_options = $request->multi_replay;
                        $questionnaire->save();
                    }
                }
            }
        }
        return ['result' => 'ok', 'message' => admin('Questionnaire Updated Successfully')];
    }

    public function show($id)
    {
        $data = Cases::query()->findOrFail($id);
        $appointments = Appointment::query()->where('case_id', $id)->get();
        $case_hearings = CaseHearing::query()->where('case_id', $id)->get();
        return view('admin.pages.cases.details', ['data' => $data, 'appointments' => $appointments, 'case_hearings' => $case_hearings]);
    }

    public function print($id)
    {
        $data = Cases::query()->findOrFail($id);
        $appointments = Appointment::query()->where('case_id', $id)->get();
        $case_hearings = CaseHearing::query()->where('case_id', $id)->get();
        return view('admin.pages.cases.print', ['data' => $data, 'appointments' => $appointments, 'case_hearings' => $case_hearings]);
    }

    public function edit($id, Request $request)
    {
        $edit = $request->get('edit', false);
        $data = Cases::query()->findOrFail($id);
        $data->opponents = CasesOpponents::where('case_id', $id)->with('opponents')->get();
        return view('admin.pages.cases.add', ['data' => $data, 'edit' => $edit]);
    }

    public function update(Request $request)
    {

        $request->validate(
            [
                'en_name' => 'min: 2|required',
                'ar_name' => 'min: 2|required',
                'native_name' => ['required'],
                'continent_id' => ['required'],
                'code' => ['required'],
            ],
            [
                'en_name.required' => admin('English Name is required'),
                'en_name.min' => admin('English Name at least must be 2 digits'),
                'ar_name.required' => admin('Arabic Name is required'),
                'ar_name.min' => admin('Arabic Name at least must be 2 digits'),
            ]
        );
        $data = $request->only([
            'en_name',
            'ar_name',
            'native_name',
            'code',
            'continent_id',
            'department',
            'characterize_id',
            'circle_id'
        ]);
        $data['status'] = $request['status'] == "on" ? 'active' : 'inactive';

        $item = Cases::query()->findOrFail($request->get('id'))->update($data);
        if ($item) {
            $return = ["result" => "ok", "message" => admin("Edit Operation Successfully")];
        } else {
            $return = ["result" => "error", "message" => admin("Edit Operation Failed")];
        }
        return $return;
    }

    public function destroy(Request $request)
    {
        $id = $request->get('id');
        $data = Cases::query()->findOrFail($request->get('id'));
        if (!$data) return response(["msg" => "not found"], 404);
        $data->deleted_by = auth()->user()->id;
        $data->save();
        if ($data->delete()) {
            CaseVeto::where('case_id', $id)->delete();
            Appointment::where('case_id', $id)->delete();
            CaseJudgment::where('case_id', $id)->delete();
            CaseDocuments::where('case_id', $id)->delete();
            CaseProcedures::where('case_id', $id)->delete();
            return response(["msg" => "success"], 200);
        } else {
            return response(["msg" => "error"], 400);
        }
    }

    public function deleteClient(Request $request)
    {
        $data = CaseUser::query()->findOrFail($request->get('id'));
        if (!$data) return response(["msg" => "not found"], 404);
        if ($data->delete()) {
            return response(["msg" => "success"], 200);
        } else {
            return response(["msg" => "error"], 400);
        }
    }

    public function disable(Request $request)
    {
        $data = Cases::query()->findOrFail($request->get('id'));
        if (!$data) return response(["msg" => "not found"], 404);
        $data->status = 'inactive';
        if ($data->save()) {
            return response(["msg" => "success"], 200);
        } else {
            return response(["msg" => "error"], 400);
        }
    }

    public function activate(Request $request)
    {
        $data = Cases::query()->findOrFail($request->get('id'));
        if (!$data) return response(["msg" => "not found"], 404);
        $data->status = 'active';
        if ($data->save()) {
            return response(["msg" => "success"], 200);
        } else {
            return response(["msg" => "error"], 400);
        }
    }

    //case document functions

    public function documents($id)
    {
        $caseDocuments = CaseDocuments::query()->where('case_id', $id)->get();
        return view('admin.pages.cases.documents.index', ['id' => $id, 'caseDocuments' => $caseDocuments]);
    }

    public function loadDocuments($id, Request $request)
    {
        $caseDocuments = CaseDocuments::query()->where('case_id', $request->case_id);
        $search = $request->get('search', false);
        if ($search) {
            $caseDocuments = $caseDocuments->where(function ($query) use ($search) {
                $query->where('ar_subject', 'like', '%' . $search . '%')
                    ->orWhere('en_subject', 'like', '%' . $search . '%')
                    ->orWhere('status', 'like', '%' . $search . '%')
                    ->orWhere('id', 'like', '%' . $search . '%');
                if (strpos('فعال', $search) !== false) {
                    $query->orwhere('status', 'active');
                }
                if (strpos('معطل', $search) !== false) {
                    $query->orwhere('status', 'like', '%' . 'inactive' . '%');
                }
            });
        }
        return DataTables::make($caseDocuments)
            ->escapeColumns([])
            ->addColumn('created_at', function ($case) {
                return Carbon::parse($case->created_at)->toDateString();
            })
            ->addColumn('name', function ($case) {
                return $case->name;
            })
            ->addColumn('actions', function ($case) {
                return null;
            })->orderColumn('name', function ($query, $order) {
                $query->orderBy('ar_name', $order);
            })
            ->make();
    }

    public function storeDocument(Request $request, $id)
    {

        $request->validate([
            'ar_subject' => 'required',
            'document' => 'required|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,txt,zip,rar,7z,jpg,jpeg,png,gif,bmp,svg|max:30720'
        ], [
            'ar_subject.required' => admin('Arabic Subject Is Required'),
            'en_subject.required' => admin('English Subject Is Required'),
            'document.mimes' => admin('Document Must Be Pdf,Doc,Docx,Xls,Xlsx,Ppt,Pptx,Txt,Zip,Rar,7z,Jpg,Jpeg,Png,Gif,Bmp,Svg'),
            'document.max' => admin('Document size must not exceed 30MB'),
        ]);
        $data = $request->only([
            'ar_subject',
        ]);
        $data['case_id'] = $id;
        $data['status'] = $request['status'] == "on" ? 'active' : 'inactive';
        //official_description

        if ($request->has('document')) {
            $ID_photo = $request->file('document');
            // Optimize file upload with better error handling
            $uploadResult = uploadFile($ID_photo, 'Cases/Documents');
            if ($uploadResult === false) {
                return ["result" => "error", "message" => admin("File upload failed. Please try again.")];
            }
            $data['document'] = $uploadResult;
        }

        $item = CaseDocuments::query()->create($data);

        if ($item) {
            $return = ["result" => "ok", "message" => admin("Add Operation Successfully")];
        } else {
            $return = ["result" => "error", "message" => admin("Add Operation Failed")];
        }
        return $return;
    }

    public function createDocument($id)
    {
        return view('admin.pages.cases.documents.add', ['id' => $id]);
    }

    public function editDocument($id)
    {
        $data = CaseDocuments::query()->findOrFail($id);
        return view('admin.pages.cases.documents.edit', ['data' => $data, 'id' => $id]);
    }

    public function updateDocument(Request $request)
    {
        $request->validate([
            'ar_subject' => 'required',
            'document' => 'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,txt,zip,rar,7z,jpg,jpeg,png,gif,bmp,svg|max:30720'
        ], [
            'ar_subject.required' => admin('Arabic Subject Is Required'),
            'en_subject.required' => admin('English Subject Is Required'),
            'document.image' => admin('Document Must Be Image'),
            'document.mimes' => admin('Document Must Be Pdf,Doc,Docx,Xls,Xlsx,Ppt,Pptx,Txt,Zip,Rar,7z,Jpg,Jpeg,Png,Gif,Bmp,Svg'),
            'document.max' => admin('Document size must not exceed 30MB'),
        ]);
        $data = $request->only([
            'ar_subject',
        ]);
        $data['status'] = $request['status'] == "on" ? 'active' : 'inactive';
        //official_description

        if ($request->has('document')) {
            $ID_photo = $request->file('document');
            // Optimize file upload with better error handling
            $uploadResult = uploadFile($ID_photo, 'Cases/Documents');
            if ($uploadResult === false) {
                return ["result" => "error", "message" => admin("File upload failed. Please try again.")];
            }
            $data['document'] = $uploadResult;
        }

        $item = CaseDocuments::query()->where('id', $request['id'])->update($data);

        if ($item) {
            $return = ["result" => "ok", "message" => admin("Add Operation Successfully")];
        } else {
            $return = ["result" => "error", "message" => admin("Add Operation Failed")];
        }
        return $return;
    }

    public function destroyDocument(Request $request)
    {
        $data = CaseDocuments::query()->findOrFail($request->get('id'));
        if (!$data) return response(["msg" => "not found"], 404);
        if ($data->delete()) {
            return response(["msg" => "success"], 200);
        } else {
            return response(["msg" => "error"], 400);
        }
    }

    public function disableDocument(Request $request)
    {
        $data = CaseDocuments::query()->findOrFail($request->get('id'));
        if (!$data) return response(["msg" => "not found"], 404);
        $data->status = 'inactive';
        $data->save();
        return response(["msg" => "success"], 200);
    }

    public function activateDocument(Request $request)
    {
        $data = CaseDocuments::query()->findOrFail($request->get('id'));
        if (!$data) return response(["msg" => "not found"], 404);
        $data->status = 'active';
        $data->save();
        return response(["msg" => "success"], 200);
    }

    //case procedures functions

    public function procedures($id)
    {
        $CaseProcedures = CaseProcedures::query()->where('case_id', $id)->get();
        return view('admin.pages.cases.procedures.index', ['id' => $id, 'CaseProcedures' => $CaseProcedures]);
    }

    public function loadProcedures($id, Request $request)
    {
        $CaseProcedures = CaseProcedures::query()->where('case_id', $request->case_id);
        $search = $request->get('search', false);
        if ($search) {
            $CaseProcedures = $CaseProcedures->where(function ($query) use ($search) {
                $query->where('name', 'like', '%' . $search . '%')
                    ->orWhere('id', 'like', '%' . $search . '%');
                if (strpos('فعال', $search) !== false) {
                    $query->orwhere('status', 'active');
                }
                if (strpos('معطل', $search) !== false) {
                    $query->orwhere('status', 'like', '%' . 'inactive' . '%');
                }
            });
        }
        return DataTables::make($CaseProcedures)
            ->escapeColumns([])
            ->addColumn('created_at', function ($case) {
                return Carbon::parse($case->created_at)->toDateString() . ' ' . Carbon::parse($case->created_at)->toTimeString();
            })
            ->addColumn('name', function ($case) {
                return $case->name;
            })
            ->addColumn('actions', function ($case) {
                return null;
            })
            ->make();
    }

    public function storeProcedures(Request $request, $id)
    {

        $request->validate([
            'name' => 'required',
        ], [
            '' => admin('Name Is Required'),
        ]);
        $data = $request->only([
            'name',
            'name_en',
            'description',
        ]);
        $data['case_id'] = $id;
        //official_description


        $item = CaseProcedures::query()->create($data);

        if ($item) {
            $case = $item->case;
            $case_number = $case->case_number ?? $case->id;
            $users = $item->case->clients;
            foreach ($users as $user) {
                $lang = $user->user->main_language;
                $mobile = $user->user->mobile;
                $additional_mobile = $user->user->additional_mobile;
                if ($lang == 'en') {
                    $message = 'Dear Client, we would like to inform you that action has been taken for case no. (' . $case_number . '), which includes the following: (' . $request->name_en . ') and you will be notified of the latest.';
                } else {
                    $message = 'موكلنا العزيز نفيدكم بأنه تم اتخاذ إجراء للقضية رقم (' . $case_number . ') والمتضمن الآتي: (' . $request->name . ') وسيتم إشعاركم لاحقاً بما يستجد.';
                }
                if ($mobile && $user->user->send_notification == 'active') {
                    if ($additional_mobile) {
                        $mobile = $mobile . ',' . $additional_mobile;
                    }
                    $this->sendWhatsApp($mobile, $message);
                }
            }

            $return = ["result" => "ok", "message" => admin("Add Operation Successfully")];
        } else {
            $return = ["result" => "error", "message" => admin("Add Operation Failed")];
        }
        return $return;
    }

    public function createProcedures($id)
    {
        return view('admin.pages.cases.procedures.add', ['id' => $id]);
    }

    public function editProcedures($id)
    {
        $data = CaseProcedures::query()->findOrFail($id);
        return view('admin.pages.cases.procedures.edit', ['data' => $data, 'id' => $id]);
    }

    public function updateProcedures(Request $request)
    {

        $request->validate([
            'name' => 'required',
        ], [
            '' => admin('Name Is Required'),
        ]);
        $data = $request->only([
            'name',
            'name_en',
            'description',
        ]);

        $item = CaseProcedures::query()->where('id', $request['id'])->update($data);

        if ($item) {
            $return = ["result" => "ok", "message" => admin("Add Operation Successfully")];
        } else {
            $return = ["result" => "error", "message" => admin("Add Operation Failed")];
        }
        return $return;
    }

    public function destroyProcedures(Request $request)
    {
        $data = CaseProcedures::query()->findOrFail($request->get('id'));
        if (!$data) return response(["msg" => "not found"], 404);
        if ($data->delete()) {
            return response(["msg" => "success"], 200);
        } else {
            return response(["msg" => "error"], 400);
        }
    }

    public function disableProcedures(Request $request)
    {
        $data = CaseProcedures::query()->findOrFail($request->get('id'));
        if (!$data) return response(["msg" => "not found"], 404);
        $data->status = 'inactive';
        $data->save();
        return response(["msg" => "success"], 200);
    }

    public function activateProcedures(Request $request)
    {
        $data = CaseProcedures::query()->findOrFail($request->get('id'));
        if (!$data) return response(["msg" => "not found"], 404);
        $data->status = 'active';
        $data->save();
        return response(["msg" => "success"], 200);
    }

    //opponents

    public function opponents($id)
    {
        return view('admin.pages.cases.opponents.index', ['id' => $id]);
    }

    public function loadOpponents(Request $request)
    {
        //        $registered  = CasesOpponents::query()->where('case_id',$request->get('case_id'))->pluck('opponent_id')->toArray();
        $Opponents = CasesOpponents::query()->with('opponents')->where('case_id', $request->get('case_id'));
        $search = $request->get('search', false);
        if ($search) {
            $Opponents = $Opponents->where(function ($query) use ($search) {
                $query->where('name', 'like', '%' . $search . '%')
                    ->orWhere('mobile', 'like', '%' . $search . '%')
                    ->orWhere('address', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%')
                    ->orWhere('lawyer_name', 'like', '%' . $search . '%')
                    ->orWhere('lawyer_number', 'like', '%' . $search . '%')
                    ->orWhere('lawyer_office', 'like', '%' . $search . '%')
                    ->orWhere('lawyer_mobile', 'like', '%' . $search . '%')
                    ->orWhere('lawyer_email', 'like', '%' . $search . '%')
                    ->orWhere('lawyer_address', 'like', '%' . $search . '%')
                    ->orWhere('status', 'like', '%' . $search . '%')
                    ->orWhere('id', 'like', '%' . $search . '%');
                if (strpos('فعال', $search) !== false) {
                    $query->orwhere('status', 'active');
                }
                if (strpos('معطل', $search) !== false) {
                    $query->orwhere('status', 'like', '%' . 'inactive' . '%');
                }
            });
        }
        return DataTables::make($Opponents)
            ->escapeColumns([])
            ->addColumn('created_at', function ($case) {
                return Carbon::parse($case->created_at)->toDateString();
            })
            ->addColumn('name', function ($case) {
                return $case->opponents->full_name;
            })
            ->addColumn('email', function ($case) {
                return $case->opponents->email;
            })
            ->addColumn('mobile', function ($case) {
                return $case->opponents->mobile;
            })
            ->addColumn('mobile', function ($case) {
                return $case->opponents->mobile;
            })
            ->addColumn('characteristic', function ($case) {
                return optional($case->case->characterizeD)->name ?? '';
            })
            ->addColumn('actions', function ($case) {
                return null;
            })->orderColumn('name', function ($query, $order) {
                $query->orderBy('ar_name', $order);
            })
            ->make();
    }

    public function destroyOpponent(Request $request)
    {
        $data = CasesOpponents::query()->findOrFail($request->get('id'));
        if (!$data) return response(["msg" => "not found"], 404);
        if ($data->delete()) {
            return response(["msg" => "success"], 200);
        } else {
            return response(["msg" => "error"], 400);
        }
    }

    public function disableOpponent(Request $request)
    {
        $data = Opponents::query()->findOrFail($request->get('id'));
        if (!$data) return response(["msg" => "not found"], 404);
        $data->status = 'inactive';
        $data->save();
        return response(["msg" => "success"], 200);
    }

    public function activateOpponent(Request $request)
    {
        $data = Opponents::query()->findOrFail($request->get('id'));
        if (!$data) return response(["msg" => "not found"], 404);
        $data->status = 'active';
        $data->save();
        return response(["msg" => "success"], 200);
    }

    public function createOpponent($id)
    {
        return view('admin.pages.cases.opponents.add', ['id' => $id]);
    }

    public function storeOpponent(Request $request)
    {

        $request->validate([
            'opponent_id' => 'required',
            'case_id' => 'required',
        ]);
        $data = $request->only([
            'opponent_id',
            'case_id',
        ]);

        $item = CasesOpponents::query()->create($data);

        if ($item) {
            $return = ["result" => "ok", "message" => admin("Add Operation Successfully")];
        } else {
            $return = ["result" => "error", "message" => admin("Add Operation Failed")];
        }
        return $return;
    }

    public function editOpponent($id)
    {
        $data = Opponents::query()->findOrFail($id);
        return view('admin.pages.cases.opponents.edit', ['data' => $data, 'id' => $id]);
    }

    public function updateOpponent(Request $request)
    {

        $request->validate([
            'name' => 'required',
            'mobile' => 'required|numeric',
        ], [
            'name.required' => admin('Opponent Name Is Required'),
            'mobile.required' => admin('Opponent Mobile Is Required'),
            'mobile.numeric' => admin('Opponent Mobile Must Be Numeric'),
            'address.required' => admin('Opponent Address Is Required'),
            'email.required' => admin('Opponent Email Is Required'),
            'email.email' => admin('Opponent Email Is Not Valid'),
            'lawyer_name.required' => admin('Lawyer Name Is Required'),
            'lawyer_number.required' => admin('Lawyer Number Is Required'),
            'lawyer_number.numeric' => admin('Lawyer Number Must Be Numeric'),
            'lawyer_office.required' => admin('Lawyer Office Is Required'),
            'lawyer_mobile.required' => admin('Lawyer Mobile Is Required'),
            'lawyer_mobile.numeric' => admin('Lawyer Mobile Must Be Numeric'),
            'lawyer_email.required' => admin('Lawyer Email Is Required'),
            'lawyer_email.email' => admin('Lawyer Email Is Not Valid'),
            'lawyer_address.required' => admin('Lawyer Address Is Required'),
        ]);
        $data = $request->only([
            'name',
            'mobile',
            'address',
            'email',
            'lawyer_name',
            'lawyer_number',
            'lawyer_office',
            'lawyer_mobile',
            'lawyer_email',
            'lawyer_address',
        ]);
        //official_description

        $item = Opponents::query()->where('id', $request['id'])->update($data);

        if ($item) {
            $return = ["result" => "ok", "message" => admin("Add Operation Successfully")];
        } else {
            $return = ["result" => "error", "message" => admin("Add Operation Failed")];
        }
        return $return;
    }

    public function sessions($id)
    {
        $data = CaseSession::query()->where('case_id', $id)->get();
        return view('admin.pages.cases.sessions.index', ['data' => $data, 'id' => $id]);
    }

    public function loadSessions($id, Request $request)
    {
        $sessions = CaseSession::query()->where('case_id', $id);
        $search = $request->get('search', false);
        if ($search) {
            $sessions = $sessions->where(function ($query) use ($search) {
                $query->where('name', 'like', '%' . $search . '%')
                    ->orWhere('mobile', 'like', '%' . $search . '%')
                    ->orWhere('id', 'like', '%' . $search . '%');
                if (strpos('فعال', $search) !== false) {
                    $query->orwhere('status', 'active');
                }
                if (strpos('معطل', $search) !== false) {
                    $query->orwhere('status', 'like', '%' . 'inactive' . '%');
                }
            });
        }
        return DataTables::make($sessions)
            ->escapeColumns([])
            ->addColumn('created_at', function ($session) {
                return Carbon::parse($session->created_at)->toDateString();
            })
            ->addColumn('name', function ($session) {
                return $session->name;
            })
            ->addColumn('actions', function ($case) {
                return null;
            })->orderColumn('name', function ($query, $order) {
                $query->orderBy('ar_name', $order);
            })
            ->make();
    }

    public function destroySession(Request $request)
    {
        $data = CaseSession::query()->findOrFail($request->get('id'));
        if (!$data) return response(["msg" => "not found"], 404);
        if ($data->delete()) {
            return response(["msg" => "success"], 200);
        } else {
            return response(["msg" => "error"], 400);
        }
    }

    public function disableSession(Request $request)
    {
        $data = CaseSession::query()->findOrFail($request->get('id'));
        if (!$data) return response(["msg" => "not found"], 404);
        $data->status = 'inactive';
        $data->save();
        return response(["msg" => "success"], 200);
    }

    public function activateSession(Request $request)
    {
        $data = CaseSession::query()->findOrFail($request->get('id'));
        if (!$data) return response(["msg" => "not found"], 404);
        $data->status = 'active';
        $data->save();
        return response(["msg" => "success"], 200);
    }

    public function storeSession(Request $request, $id)
    {

        $request->validate([
            'document' => 'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,txt,zip,rar,7z,jpg,jpeg,png,gif,bmp,svg'
        ], [
            'document.mimes' => admin('Document Must Be Pdf,Doc,Docx,Xls,Xlsx,Ppt,Pptx,Txt,Zip,Rar,7z,Jpg,Jpeg,Png,Gif,Bmp,Svg'),
        ]);
        $data = $request->only([
            'court_decision',
            'session_details'
        ]);
        $data['case_id'] = $id;
        //official_description

        if ($request->has('document')) {
            $ID_photo = $request->file('document');
            $data['document'] = uploadFile($ID_photo, 'Cases/Documents');
        }

        $item = CaseSession::query()->create($data);

        if ($item) {
            $return = ["result" => "ok", "message" => admin("Add Operation Successfully")];
        } else {
            $return = ["result" => "error", "message" => admin("Add Operation Failed")];
        }
        return $return;
    }

    public function createSession($id)
    {
        return view('admin.pages.cases.sessions.add', ['id' => $id]);
    }

    public function editSession($id)
    {
        $data = CaseSession::query()->findOrFail($id);
        return view('admin.pages.cases.sessions.edit', ['data' => $data]);
    }

    public function updateSession(Request $request)
    {
        $request->validate([
            'document' => 'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,txt,zip,rar,7z,jpg,jpeg,png,gif,bmp,svg'
        ], [
            'document.mimes' => admin('Document Must Be Pdf,Doc,Docx,Xls,Xlsx,Ppt,Pptx,Txt,Zip,Rar,7z,Jpg,Jpeg,Png,Gif,Bmp,Svg'),
        ]);
        $data = $request->only([
            'court_decision',
            'session_details',
            'next_session_tasks'
        ]);
        $id = $request['id'];
        //official_description
        if ($request->has('document')) {
            $ID_photo = $request->file('document');
            $data['document'] = uploadFile($ID_photo, 'Cases/Documents');
        }
        $item = CaseSession::query()->findOrFail($id);
        $item->update($data);
        if ($item) {
            $return = ["result" => "ok", "message" => admin("Add Operation Successfully")];
        } else {
            $return = ["result" => "error", "message" => admin("Add Operation Failed")];
        }
        return $return;
    }

    public function printAll()
    {
        $title = admin('cases');
        $headers = [
            '#',
            admin('File No'),
            admin('Case No'),
            admin('Case Category'),
            admin('Court'),
            admin('opponents'),
            admin('Clients'),
            admin('Added By Name'),
            admin('Status'),
        ];
        $cases = Cases::orderBy('id', 'DESC')->get();
        $data = [];
        $i = 1;
        foreach ($cases as $case) {
            $clients_name = '';
            $opponents_name = '';
            $clients = $case->clients->where('user_department_id', NULL);
            if (count($clients) > 1) {
                $clients_name .= $clients[0]->user->full_name . ' ' . admin('andOthers');
            } else {
                foreach ($clients as $user) {
                    $clients_name .= $user->user->full_name;
                }
            }
            $opponents = CasesOpponents::query()->with('opponents')->where('case_id', $case->id)->get();
            if (count($opponents) > 1) {
                $opponents_name .= $opponents[0]->opponents->full_name . ' ' . admin('andOthers');
            } else {
                foreach ($opponents as $opponent) {
                    $opponents_name .= $opponent->opponents->full_name;
                }
            }
            $data[] = [
                $i++,
                $case->id,
                $case->case_number,
                optional($case->case_category)->name ?? '',
                optional($case->court)->name ?? '',
                $opponents_name ?? '',
                $clients_name ?? '',
                $case->addedName->fullname ?? '',
                optional($case->case_status)->name ?? ''
            ];
        }
        return view('admin.layout.print_all', compact('title', 'headers', 'data'));
    }

    public function appointmentsPrint($id)
    {
        $data = Cases::query()->findOrFail($id);
        $appointments = Appointment::query()->where('case_id', $id)->get();
        return view('admin.pages.cases.appointmentsPrint', ['data' => $data, 'appointments' => $appointments]);
    }
}
