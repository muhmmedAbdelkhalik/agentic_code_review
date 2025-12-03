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

   
}
