<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Repositories\PositionRepository;
use App\Repositories\ProgramRepository;
use App\Repositories\UserRepository;
use App\Repositories\WorkspaceRepository;
use Illuminate\Http\Request;

class UserDisableController extends Controller
{
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
        $this->middleware('checkTrainer')->except('index', 'create', 'store', 'edit', 'update');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $programs = $this->programRepository->pluckProgram()->prepend('', config('site.prepend'));
        $positions = $this->positionRepository->pluckPosition()->prepend('', config('site.prepend'));
        $workspaces = $this->workspaceRepository->pluckWorkspace()->prepend('', config('site.prepend'));
        $users = $this->userRepository->newQuery();
        if ($request->has('name')) {
            $users->getListName($request->name);
        }

        if ($request->has('program_id') && $request->program_id != config('site.prepend')) {
            $users->getList('program_id', $request->program_id);
        }

        if ($request->has('workspace_id') && $request->workspace_id != config('site.prepend')) {
            $users->getList('workspace_id', $request->workspace_id);
        }

        if ($request->has('position_id') && $request->position_id != config('site.prepend')) {
            $users->getList('position_id', $request->position_id);
        }
        $users = $users->orderBy('created_at', 'DESC')->where('status', '=', config('site.disable'));
        $users = $users->paginate(config('site.paginate_user'));

        return view('admin.user.disable', compact('users', 'programs', 'positions', 'workspaces'));
    }
}
