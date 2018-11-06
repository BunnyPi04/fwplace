@extends('admin.layout.master')

@section('title', __('Role'))

@section('css')
    <link rel="stylesheet" type="text/css" href="{{ asset('bower_components/metro-asset/vendors/custom/datatables/datatables.bundle.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('bower_components/lib_fwplace/css/toastr.min.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ mix('css/datatable.css') }}">
@endsection

@section('module')

    <h3 class="m-subheader__title m-subheader__title--separator">{{ __('Role') }}</h3>

    <ul class="m-subheader__breadcrumbs m-nav m-nav--inline">
        <li class="m-nav__item m-nav__item--home">
            <a href="{{ route('admin.index') }}" class="m-nav__link m-nav__link--icon">
                <i class="m-nav__link-icon la la-home"></i>
            </a>
        </li>
        <li class="m-nav__separator">-</li>
        <li class="m-nav__item">
            <a class="m-nav__link">
                <span class="m-nav__link-text">{{ __('Employee') }}</span>
            </a>
        </li>
        <li class="m-nav__separator">-</li>
        <li class="m-nav__item">
            <a href="{{ route('users.index') }}" class="m-nav__link">
                <span class="m-nav__link-text">{{ __('Employee List') }}</span>
            </a>
        </li>
        <li class="m-nav__separator">-</li>
        <li class="m-nav__item">
            <a class="m-nav__link">
                <span class="m-nav__link-text">{{ __('Role') }}</span>
            </a>
        </li>
    </ul>

@endsection

@section('content')

    <div class="m-portlet m-portlet--mobile">
        <div class="m-portlet__body">
            <table class="table table-striped- table-bordered table-hover table-checkable" id="roles_table">
                <thead>
                    <tr>
                        <th>{{ __('#') }}</th>
                        <th>{{ __('Role') }}</th>
                        <th>{{ __('Description') }}</th>
                        <th>{{ __('Permission') }}</th>
                        <th>{{ __('Action') }}</th>
                    </tr>
                </thead>
                <tbody>

                    @if (!empty($roles))
                        @foreach ($roles as $key => $role)

                            <tr>
                                <td>{{ $key + 1 }}</td>
                                <td>{{ $role->display_name }}</td>
                                <td>{{ $role->description }}</td>
                                <td>
                                    @if (!empty($role->permissions))
                                        @foreach ($role->permissions as $permission)
                                            <span class="m-badge m-badge--success m-badge--wide">
                                                {{ $permission->display_name }}
                                            </span>
                                        @endforeach
                                    @endif
                                </td>
                                <td class="text-center font-size-18">
                                    <input type="hidden" id="checked-{{ $role->id }}" value="{{ $role->checked }}">

                                    @if ($role->checked == 1)
                                        <i id="action-{{ $role->id }}" class="fa fa-check-circle" onclick="updateRole({{ $user->id }}, {{ $role->id }})" aria-hidden="true" style="cursor: pointer; color: #3598dc; font-size: 20px;"></i>
                                    @else
                                        <i id="action-{{ $role->id }}" class="fa fa-circle-notch" onclick="updateRole({{ $user->id }}, {{ $role->id }})" aria-hidden="true" style="cursor: pointer; color: #3598dc; font-size: 20px;"></i>
                                    @endif
                                </td>
                            </tr>

                        @endforeach
                    @endif

                </tbody>
            </table>
        </div>
    </div>

    {!! Form::hidden('add_success_lang', __('Add success'), ['id' => 'add_success_lang']) !!}
    {!! Form::hidden('delete_success_lang', __('Delete success'), ['id' => 'delete_success_lang']) !!}

@endsection

@section('js')
    <script src="{{ asset('bower_components/metro-asset/vendors/custom/datatables/datatables.bundle.js') }}"></script>
    <script src="{{ asset('bower_components/metro-asset/demo/default/custom/crud/datatables/data-sources/html.js') }}"></script>
    <script src="{{ asset('bower_components/lib_fwplace/js/toastr.min.js') }}"></script>

    <script>
        /*Lang-i18n*/
        var add_success_lang = $('#add_success_lang').val();
        var delete_success_lang = $('#delete_success_lang').val();
        /*---------*/

        /*Thay đổi vai trò*/
        function updateRole(user_id, role_id) {
            var checked = $('#checked-' + role_id).val();

            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            $.ajax({
                type: 'POST',
                url: '{{ route('users.update_role_user') }}',
                data: {
                    user_id: user_id,
                    role_id: role_id,
                    checked: checked,
                },
                success: function(res)
                {
                    if (res.message == 'deleted') {
                        $('#action-' + role_id).removeClass('fa-check-circle').addClass('fa-circle-notch');
                        $('#checked-' + role_id).val(0);
                        toastr.success(delete_success_lang);
                    }

                    if (res.message == 'added') {
                        $('#action-' + role_id).removeClass('fa-circle-notch').addClass('fa-check-circle');
                        $('#checked-' + role_id).val(1);
                        toastr.success(add_success_lang);
                    }
                },
                error: function (xhr, ajaxOptions, thrownError) {
                    //
                }
            });
        }
        /*-------------------*/
    </script>
@endsection
