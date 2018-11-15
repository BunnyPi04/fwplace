<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Repositories\LocationRepository;
use App\Repositories\SeatRepository;
use App\Repositories\WorkspaceRepository;
use App\Repositories\ProgramInterface;
use App\Repositories\UserRepository;
use App\Repositories\PositionRepository;
use App\Repositories\DesignDiagramRepository;
use APP\Repositories\WorkingScheduleRepository;
use Illuminate\Http\Request;
use App\Http\Requests\DesignDiagramRequests;
use DB;
use Validator;
use Illuminate\Support\Facades\Storage;
use RealRashid\SweetAlert\Facades\Alert;

class DiagramController extends Controller
{
    protected $userRepository;
    protected $programRepository;
    protected $positionRepository;
    protected $locationRepository;
    protected $designDiagramRepository;
    protected $workingScheduleRepository;

    public function __construct(
        LocationRepository $locationRepository,
        WorkspaceRepository $workspaceRepository,
        SeatRepository $seatRepository,
        UserRepository $userRepository,
        ProgramInterface $programRepository,
        PositionRepository $positionRepository,
        DesignDiagramRepository $designDiagramRepository
    ) {
        $this->locationRepository = $locationRepository;
        $this->workspace = $workspaceRepository;
        $this->seat = $seatRepository;
        $this->userRepository = $userRepository;
        $this->programRepository = $programRepository;
        $this->positionRepository = $positionRepository;
        $this->designDiagramRepository = $designDiagramRepository;
    }

    public function typeWorkspaceInformation()
    {
        return view('test.workspace.create');
    }

    public function saveWorkspace(Request $request)
    {
        $data = $request->all();
        if ($request->image) {
            $request->image->store(config('site.workspace.image'));
            $data['image'] = $request->image->hashName();
        }
        $workspace = $this->workspace->create($data);

        return redirect()->route('generate', ['id' => $workspace->id]);
    }

    public function generateDiagram(Request $request, $idWorkspace)
    {
        $workspace = $this->workspace->findOrFail($idWorkspace);
        $totalSeat = $workspace->total_seat;
        $seatPerRow = $workspace->seat_per_row;
        $remainderSeat = $totalSeat % $seatPerRow; // Lấy số ghế dư ra

        if ($remainderSeat > 0) {
            $totalRow = floor($totalSeat / $seatPerRow) + 1; // Lấy số hàng, nếu có dư thì +1 hàng để lưu số dư
        } else {
            $totalRow = floor($totalSeat / $seatPerRow);
        }
        $alphabet = range('A', 'Z'); // Tạo ra bảng chữ cái để đặt tên cho cột
        $columnName = $alphabet;
        if ($seatPerRow > count($alphabet)) {
            // Nếu như số ghế/hàng > bảng chữ cái thì sẽ kết hợp thêm 1 bảng chữ cái nữa: AA, AB, AC,...
            foreach ($alphabet as $char) {
                foreach ($alphabet as $additionalChar) {
                    $columnName[] = $char . $additionalChar;
                }
            }
        }

        $columnList = array_slice($columnName, 0, $seatPerRow); // Lấy danh sách tên các hàng
        $rowList = range(1, $totalRow); // Lấy danh sách tên các cột
        $renderSeat = [];
        $counting = 0; // Đếm số ghế được tạo ra
        foreach ($rowList as $key => $row) {
            foreach ($columnList as $column) {
                $counting++;
                if ($counting <= $totalSeat) {
                    // Chưa max thì sẽ thêm
                    $renderSeat[$row][] = $column . $row;
                } else {
                    // Nếu max thì thêm để tạo đủ ghế
                    $renderSeat[$row][] = null;
                }
            }
        }
        $locations = $workspace->locations;
        $colorLocation = [];

        foreach ($locations as $key => $location) {
            foreach ($location->seats as $id => $seat) {
                $userId = $this->userRepository->get();
                $listUserId = unserialize($seat->user_id);
                $colorLocation[$key][$id]['location'] = $location->name;
                $colorLocation[$key][$id]['seat_id'] = $seat->id;
                if ($seat->user_id != null) {
                    $checkName = [];
                    $checkAvatar = [];
                    $checkUserId = [];
                    foreach ($userId as $value) {
                        if (in_array($value->id, $listUserId)) {
                            $checkAvatar[] = $value->avatar;
                            $checkUserId[] =  $value->id;
                            $checkName[] = $value->name;
                            $checkProgram = $value->program_id;
                            $position= $this->userRepository->findOrFail($value->id)->position->id;
                        }
                    }
                    $colorLocation[$key][$id]['user_name'] = $checkName;
                    $colorLocation[$key][$id]['avatar'] = $checkAvatar;
                    $colorLocation[$key][$id]['user_id'] =  $checkUserId;
                    $colorLocation[$key][$id]['position'] = $position;
                    $colorLocation[$key][$id]['program'] = $checkProgram;
                } else {
                    $colorLocation[$key][$id]['user_name'] = $seat->name;
                    $colorLocation[$key][$id]['avatar'] = null;
                    $colorLocation[$key][$id]['user_id'] = null;
                    $colorLocation[$key][$id]['program'] = null;
                    $colorLocation[$key][$id]['position'] = null;
                }

                $colorLocation[$key][$id]['name'] = $seat->name;
                $colorLocation[$key][$id]['color'] = $location->color;
                $colorLocation[$key][$id]['workspace_id'] = $location->workspace_id;
            }
        }

        $colorLocation = json_encode($colorLocation);
        $listProgram = $this->programRepository->listProgramArray();
        $listPosition = $this->positionRepository->listpositionArray();
        $listUser = $this->userRepository->getList('program_id', 1)->pluck('name', 'id');

        return view('test.workspace.generate', compact(
            'renderSeat',
            'idWorkspace',
            'colorLocation',
            'listProgram',
            'listPosition',
            'listUser'
        ));
    }

    public function saveLocation(Request $request, $id)
    {
        $this->validate($request, [
        'seats' => 'required',
        'name' => 'required',
        ]);

        $this->workspace->findOrFail($id);
        $seats = explode(',', $request->seats);
        $location = $this->locationRepository->create([
        'name' => $request->name,
        'workspace_id' => $id,
        'color' => $request->color,
        ]);

        foreach ($seats as $value) {
            $this->seat->create([
            'name' => $value,
            'location_id' => $location->id,
            ]);
        }

        return redirect()->back();
    }

    public function saveAjaxLocation(Request $request)
    {
        $data = $this->locationRepository->create([
        'name' => $request->name,
        'workspace_id' => $request->id,
        'color' => $request->color,
        ]);

        $seats = $request->seat;
        foreach ($seats as $value) {
            $this->seat->create([
            'name' => $value,
            'location_id' => $data->id,
            ]);
        }

        return response()->json($data);
    }

    public function list()
    {
        $workspaces = $this->workspace->get();

        return view('test.workspace.index', compact('workspaces'));
    }

    public function detail($id)
    {
        $workspace = $this->workspace->findOrFail($id);
        $totalSeat = $workspace->total_seat;
        $seatPerRow = $workspace->seat_per_row;
        $remainderSeat = $totalSeat % $seatPerRow; // Lấy số ghế dư ra

        if ($remainderSeat > 0) {
            $totalRow = floor($totalSeat / $seatPerRow) + 1; // Lấy số hàng, nếu có dư thì +1 hàng để lưu số dư
        } else {
            $totalRow = floor($totalSeat / $seatPerRow);
        }

        $alphabet = range('A', 'Z'); // Tạo ra bảng chữ cái để đặt tên cho cột
        $columnName = $alphabet;
        if ($seatPerRow > count($alphabet)) {
            // Nếu như số ghế/hàng > bảng chữ cái thì sẽ kết hợp thêm 1 bảng chữ cái nữa: AA, AB, AC,...
            foreach ($alphabet as $char) {
                foreach ($alphabet as $additionalChar) {
                    $columnName[] = $char . $additionalChar;
                }
            }
        }

        $columnList = array_slice($columnName, 0, $seatPerRow); // Lấy danh sách tên các hàng
        $rowList = range(1, $totalRow); // Lấy danh sách tên các cột
        $renderSeat = [];
        $counting = 0; // Đếm số ghế được tạo ra
        foreach ($rowList as $key => $row) {
            foreach ($columnList as $column) {
                $counting++;
                if ($counting <= $totalSeat) {
                    // Chưa max thì sẽ thêm
                    $renderSeat[$row][] = $column . $row;
                } else {
                    // Nếu max thì thêm để tạo đủ ghế
                    $renderSeat[$row][] = null;
                }
            }
        }

        $locations = $workspace->locations;
        $colorLocation = [];
        foreach ($locations as $key => $location) {
            foreach ($location->seats as $id => $seat) {
                $colorLocation[$key][$id]['location'] = $location->name;
                $colorLocation[$key][$id]['name'] = $seat->name;
                $colorLocation[$key][$id]['color'] = $location->color;
            }
        }
        $colorLocation = json_encode($colorLocation);

        return view('test.workspace.detail', compact('workspace', 'renderSeat', 'colorLocation', 'locations'));
    }

    public function saveInfoLocation(Request $request)
    {
        $seatUserId = $this->seat->findOrFail($request->seat_id);
        $checkUserId = unserialize($seatUserId->user_id);
        if (is_array($checkUserId) && !empty($checkUserId)) {
            $request->merge([ 'user_id' => serialize(array_merge($checkUserId, $request->user_id)) ]);
        } else {
            $request->merge([ 'user_id' => serialize($request->user_id) ]);
        }

        $data = $request->only('user_id', 'seat_id');


        $this->seat->update($data, $request->seat_id);

        Alert::success(trans('Edit Program'), trans('Successfully!!!'));

        return redirect()->back();
    }

    public function editInfoLocation(Request $request)
    {
        $data = $this->userRepository->where('id', $request->user_id)->first();

        return response()->json($data);
    }

    public function imageMap()
    {
        $workspaces = $this->workspace->get();

        return view('test.workspace.image_map', compact('workspaces'));
    }

    public function saveDesignDiagram(DesignDiagramRequests $request)
    {
        $data = $request->only('name', 'diagram', 'content');
        DB::beginTransaction();
        try {
            if ($request->diagram) {
                $request->diagram->store(config('site.diagram.image'));
                $data['diagram'] = $request->diagram->hashName();
            }
            $this->designDiagramRepository->create($data);
            DB::commit();
            Alert::success(trans('Add success'), trans('Successfully!!!'));

            return redirect()->route('list_diagram');
        } catch (Exception $e) {
            DB::rollback();

            Alert::error(trans('Add error'), __('Required'));
        }
    }

    public function listDiagram()
    {
        $listDiagram = $this->designDiagramRepository->getListDiagram();

        return view('test.workspace.diagram_list', compact('listDiagram'));
    }

    public function diagramDetail($id)
    {
        $diagramDetail = $this->designDiagramRepository->findOrFail($id);

        return view('test.workspace.diagram_detail', compact('diagramDetail'));
    }

    public function avatarInfo(Request $request, $id)
    {
        $data = $this->userRepository->findOrFail($id);

        return response()->json($data);
    }

    public function editInfoUser(Request $request)
    {
        DB::beginTransaction();
        try {
            $checkUser = $this->seat->findOrFail($request->seat_id);
            $checkUserId = unserialize($checkUser->user_id);
            foreach ($checkUserId as $key => $val) {
                if ($request->user_id == $val) {
                    $arr = array_replace(
                        $checkUserId,
                        array_fill_keys(
                            array_keys($checkUserId, $val),
                            $request->edit_userId
                        )
                    );
                }
            }
            $request->merge(['user_id' => serialize($arr)]);
            $data = $request->only('user_id', 'seat_id');
            $this->seat->update($data, $request->seat_id);
            DB::commit();

            Alert::success(trans('Edit Program'), trans('Successfully!!!'));

            return redirect()->back();
        } catch (Exception $e) {
            DB::rollback();

            Alert::error(trans('Add error'), __('Required'));
        }
    }
}
