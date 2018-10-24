<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserFormRequest;
use App\Models\Position;
use App\Models\Program;
use App\Models\Workspace;
use App\Repositories\PositionRepository;
use App\Repositories\ProgramRepository;
use App\Repositories\UserRepository;
use App\Repositories\WorkspaceRepository;
use App\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use RealRashid\SweetAlert\Facades\Alert;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    protected $userRepository;
    protected $programRepository;
    protected $positionRepository;
    protected $workspaceRepository;

    public function __construct(
        UserRepository $userRepository,
        ProgramRepository $programRepository,
        PositionRepository $positionRepository,
        WorkspaceRepository $workspaceRepository
    ) {
        $this->userRepository = $userRepository;
        $this->programRepository = $programRepository;
        $this->positionRepository = $positionRepository;
        $this->workspaceRepository = $workspaceRepository;
    }

    public function index()
    {
        $programs = $this->programRepository->pluckProgram()->prepend(__('Programs'), '0');
        $positions = $this->positionRepository->pluckPosition()->prepend(__('Positions'), '0');
        $workspaces = $this->workspaceRepository->pluckWorkspace()->prepend(__('Workspaces'), 0);

        return view('auth.register', compact('programs', 'positions', 'workspaces'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(UserFormRequest $request)
    {
        $data = $request->all();
        $data['password'] = bcrypt($request->password);
        $data['status'] = 0;
        $this->userRepository->create($data);
        Alert::success(trans('Register Member Successfully'), trans('Please Wait Active'));

        return redirect('/login');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $programs = $this->programRepository->listprogramArray();
        $positions = $this->positionRepository->listpositionArray();
        $workspaces = $this->workspaceRepository->listWorkspaceArray();
        $user = $this->userRepository->findOrFail($id);
        $trainers = $this->userRepository->getSelectTrainer($user->program_id);

        return view('users.edit', compact('positions', 'programs', 'workspaces', 'user', 'trainers'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(UserFormRequest $request, $id)
    {
        $user = $this->userRepository->findOrFail($id);
        $data = $request->all();
        if ($request->hasFile('avatar')) {
            Storage::delete(config('site.user.image') . $user->avatar);
            $request->avatar->store(config('site.user.image'));
            $data['avatar'] = $request->avatar->hashName();
        }
        $this->userRepository->update($data, $id);
        alert()->success(__('Edit User'), __('Successfully!!!'));
        
        return redirect('/');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    public function selectTrainer(Request $request)
    {
        if (!$request->has('program_id')) {
            return null;
        }
        $trainers = $this->userRepository->getSelectTrainer($request->program_id);

        return json_encode($trainers);
    }
}
