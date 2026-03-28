@extends('layouts.user_type.auth')

@push('style')
    <style>
        .dataTables_info {
            padding-top: 30px;
            padding-left: 30px
        }

        #sortIcon {
            cursor: pointer;
        }

        div.dataTables_wrapper div.dataTables_filter {
            text-align: right;
            padding-right: 10px;
        }

        .select2-container .select2-selection--single {
            height: 34px !important;
        }

        .select2-container--default .select2-selection--single {
            border: 1px solid #ccc !important;
            border-radius: 0px !important;
        }
    </style>
@endpush

@section('content')
    <ul class="nav nav-tabs  mx-4">
        <li class="nav-item">
            <a class="nav-link active" aria-current="page" href="#permissions" data-bs-toggle="tab">
                Permissions
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="#rolePermission" data-bs-toggle="tab">
                Role Permission
            </a>
        </li>
    </ul>

    <div class="tab-content">
        <div class="tab-pane fade show active" id="permissions">
            <div class="card mb-4 mx-4">
                <div class="card-header pb-0">
                    <div class="d-flex flex-row justify-content-between">
                        <div>
                        </div>
                        <button class="btn bg-gradient-primary btn-sm mb-0" type="button" data-bs-toggle="modal"
                            data-bs-target="#modal_permission">+&nbsp; New permissions</button>

                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive-lg">
                        <table class="table align-items-center" id="permisionTable" style="width: 100%">
                            <thead>
                                <tr>
                                    <th class="text-center text-uppercase  text-xxs font-weight-bolder ">
                                        id
                                        <span id="sortIcon" class="fas fa-sort pointer"></span>
                                    </th>
                                    <th class="text-center text-uppercase  text-xxs font-weight-bolder ">
                                        Name
                                        <span id="sortIcon" class="fas fa-sort pointer"></span>
                                    </th>
                                    <th class="text-center text-uppercase  text-xxs font-weight-bolder ">
                                        Action
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Data dari DataTable akan dimasukkan di sini secara dinamis -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="tab-pane fade show" id="rolePermission">
            <div class="card mb-4 mx-4">
                <div class="card-header pb-0">
                    <div class="d-flex flex-row justify-content-between">
                        <div>
                        </div>
                        <button class="btn bg-gradient-primary btn-sm mb-0" id="modal_role_permission_btn" type="button"
                            data-bs-toggle="modal" data-bs-target="#modal_role_permission">+&nbsp; New Role has
                            Permissions</button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive-lg">
                        <table class="table align-items-center" id="rolePermisionTable" style="width: 100%">
                            <thead>
                                <tr>
                                    <th class="text-center text-uppercase  text-xxs font-weight-bolder ">
                                        Permission
                                        <span id="sortIcon" class="fas fa-sort pointer"></span>
                                    </th>
                                    <th class="text-center text-uppercase  text-xxs font-weight-bolder ">
                                        Role
                                        <span id="sortIcon" class="fas fa-sort pointer"></span>
                                    </th>
                                    <th class="text-center text-uppercase  text-xxs font-weight-bolder ">
                                        Action
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Data dari DataTable akan dimasukkan di sini secara dinamis -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modal_permission" tabindex="-1" aria-labelledby="permissionModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="permissionModalLabel">Create Permissions</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form role="form text-left" method="POST" id="createPermission" action="/permissions">
                    <div class="modal-body">
                        @csrf
                        <div class="mb-3">
                            <input type="text" class="form-control" placeholder="Name" name="name" id="name"
                                aria-label="Name" aria-describedby="name" value="{{ old('name') }}">
                            <p id="nameError" class="text-danger text-xs mt-2"></p>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn bg-gradient-dark">Save changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalEditPermission" tabindex="-1" aria-labelledby="permissionModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="permissionModalLabel">Edit Permission</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form role="form text-left" method="PUT" id="editPermisFrom" action="/update-permission">
                    <div class="modal-body">
                        @csrf
                        <input type="hidden" name="id" id="permis_id" />
                        <div class="mb-3">
                            <input type="text" class="form-control" placeholder="Name" name="name"
                                id="name_input" aria-label="Name" aria-describedby="name">
                            <p id="nameErrorEdit" class="text-danger text-xs mt-2"></p>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn bg-gradient-dark">Save changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>


    <div class="modal fade" id="modal_role_permission" tabindex="-1" aria-labelledby="permissionModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="permissionModalLabel">Create Role has Permissions</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form role="form text-left" method="POST" id="createRolePermission" action="/create-role-permission">
                    <div class="modal-body">
                        @csrf
                        <div class="mb-3">
                            <select class="form-select" id="dataRoles" name="roleid">

                            </select>
                        </div>
                        <div class="mb-3">
                            <select class="form-select" id="dataPermissions" name="permissionid">

                            </select>
                        </div>
                        <p id="dataError" class="text-danger text-xs mt-2"></p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn bg-gradient-dark">Save changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection


@push('script')
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script type="text/javascript">
        $(document).ready(function() {
            const csrfToken = $('meta[name="csrf-token"]').attr('content');
            $('#permisionTable').DataTable({
                processing: false,
                serverSide: true,
                ajax: {
                    url: "{{ route('permissions') }}",
                    data: function(d) {
                        d.query = $('#search').val();
                    }
                },
                columns: [{
                    data: 'id',
                    name: 'id',
                    orderable: true,
                    render: function(data, type, row) {
                        return 'ID-' + data;
                    }
                }, {
                    data: 'name',
                    name: 'name',
                    orderable: true
                }, {
                    data: 'action',
                    name: 'action',
                    orderable: true
                }, ],
                columnDefs: [{
                    targets: '_all',
                    className: 'text-center'
                }],
                language: {
                    search: "_INPUT_",
                    searchPlaceholder: "Search...",
                    lengthMenu: "Show Entries _MENU_",
                    paginate: {
                        first: "First",
                        last: "Last",
                        next: "<i class='fas fa-chevron-right'></i>",
                        previous: "<i class='fas fa-chevron-left'></i>"
                    }
                },
                drawCallback: function(settings) {
                    $('.deletePermisBtn').unbind('click').click(function() {
                        var permisId = $(this).data(
                            'id');
                        Swal.fire({
                            title: 'Are you sure?',
                            text: "You won't be able to revert this!",
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonColor: '#3085d6',
                            cancelButtonColor: '#d33',
                            confirmButtonText: 'Yes, delete it!'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                $.ajax({
                                    url: "{{ route('delete-permission', ['id' => ':permisId']) }}"
                                        .replace(':permisId', permisId),
                                    method: 'DELETE',
                                    data: {
                                        _token: csrfToken
                                    },
                                    success: function(data) {
                                        $('#permisionTable').DataTable()
                                            .ajax
                                            .reload();
                                        Swal.fire({
                                            title: 'Success!',
                                            text: `${data.message}`,
                                            icon: 'success',
                                            confirmButtonText: 'OK'
                                        });
                                    },
                                });
                            }
                        });
                    });

                    $('.editPermisBtn').unbind('click').click(function() {
                        var permisId = $(this).data(
                            'id');

                        $.ajax({
                            url: '/show-permission/' +
                                permisId,
                            method: 'GET',
                            success: function(data) {
                                $('#name_input').val(data.name);
                                $('#permis_id').val(data.id);
                                $('#modalEditPermission').modal('show');
                            },
                            error: function(xhr, status, error) {
                                console.error(xhr.responseText);
                            }
                        });

                    });
                }

            });
            $('#rolePermisionTable').DataTable({
                processing: false,
                serverSide: true,
                ajax: {
                    url: "{{ route('role-permissions') }}",
                    data: function(d) {
                        d.query = $('#searchrolepermission').val();
                    }
                },
                columns: [{
                        data: null,
                        name: 'permission',
                        render: function(data) {
                            return `(${data.permission_id}) ${data.permission_name}`;
                        },
                        orderable: true
                    },
                    {
                        data: null,
                        name: 'role',
                        render: function(data) {
                            return `(${data.role_id}) ${data.role_name}`;
                        },
                        orderable: true
                    }, {
                        data: 'action',
                        name: 'action',
                        orderable: true
                    },
                ],
                columnDefs: [{
                    targets: '_all',
                    className: 'text-center'
                }],
                language: {
                    search: "_INPUT_",
                    searchPlaceholder: "Search...",
                    lengthMenu: "Show Entries _MENU_",
                    paginate: {
                        first: "First",
                        last: "Last",
                        next: "<i class='fas fa-chevron-right'></i>",
                        previous: "<i class='fas fa-chevron-left'></i>"
                    }
                },
                drawCallback: function(settings) {
                    $('.deleteRolePermisBtn').unbind('click').click(function() {
                        var roleId = $(this).data('idrole');
                        var permisId = $(this).data('idpermis');
                        Swal.fire({
                            title: 'Are you sure?',
                            text: "You won't be able to revert this!",
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonColor: '#3085d6',
                            cancelButtonColor: '#d33',
                            confirmButtonText: 'Yes, delete it!'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                $.ajax({
                                    url: "{{ route('delete-role-permission', ['roleId' => ':roleId', 'permissionId' => ':permissionId']) }}"
                                        .replace(':roleId', roleId)
                                        .replace(':permissionId', permisId),
                                    method: 'DELETE',
                                    data: {
                                        _token: csrfToken
                                    },
                                    success: function(data) {
                                        $('#rolePermisionTable').DataTable()
                                            .ajax
                                            .reload();
                                        Swal.fire({
                                            title: 'Success!',
                                            text: `${data.message}`,
                                            icon: 'success',
                                            confirmButtonText: 'OK'
                                        });
                                    },
                                });
                            }
                        });
                    });
                }

            });

            $('th').click(function() {
                $('th span').removeClass('fa-sort-up fa-sort-down').addClass('fa-sort');

                let columnIndex = $(this).index();
                let icon = $(this).find('span');

                if (icon.hasClass('fa-sort')) {
                    icon.removeClass('fa-sort').addClass('fa-sort-up');
                } else if (icon.hasClass('fa-sort-up')) {
                    icon.removeClass('fa-sort-up').addClass('fa-sort-down');
                } else {
                    icon.removeClass('fa-sort-down').addClass('fa-sort-up');
                }
            });
        });

        $(document).ready(function() {
            $('#createPermission').on('submit', function(event) {
                event.preventDefault();

                var formData = $(this).serialize();
                $.ajax({
                    url: $(this).attr('action'),
                    method: 'POST',
                    data: formData,
                    success: function(response) {
                        $('#modal_permission').modal('hide');
                        $('#permisionTable').DataTable().ajax
                            .reload();

                        Swal.fire({
                            title: 'Success!',
                            text: 'Successsfully created data',
                            icon: 'success',
                            confirmButtonText: 'OK'
                        });
                        $('#createPermission')[0].reset();
                        $('#nameError').text('');
                    },
                    error: function(xhr) {
                        var errors = xhr.responseJSON.errors;
                        if (errors.hasOwnProperty('name')) {
                            $('#nameError').text(errors.name[0]);
                        }

                    }
                });
            });

            $('#editPermisFrom').on('submit', function(event) {
                event.preventDefault();

                var formData = $(this).serialize();
                const permisId = $('#permis_id').val();
                const url = $(this).attr('action');
                $.ajax({
                    url: `${url}/${permisId}`,
                    method: 'POST',
                    data: formData,
                    dataType: 'json',
                    success: function(response) {
                        $('#modalEditPermission').modal('hide');
                        $('#permisionTable').DataTable().ajax.reload();
                        Swal.fire({
                            title: 'Success!',
                            text: 'Successfully updated data',
                            icon: 'success',
                            confirmButtonText: 'OK'
                        });
                        $('#editPermisFrom')[0].reset();
                        $('#nameErrorEdit').text('');
                    },
                    error: function(xhr) {
                        var errors = xhr.responseJSON.errors;
                        if (errors.hasOwnProperty('name')) {
                            $('#nameErrorEdit').text(errors.name[0]);
                        }
                    }
                });
            });

            $("#modal_role_permission_btn").click(function() {
                $.ajax({
                    url: "{{ route('data-roles-permissions') }}",
                    type: 'GET',
                    dataType: 'json',
                    success: function(data) {
                        const permissions = data.permissions;
                        const roles = data.roles;

                        const dataRolesSelect = $('#dataRoles');
                        dataRolesSelect.empty();
                        $.each(roles, function(index, role) {
                            dataRolesSelect.append($('<option>', {
                                value: role.id,
                                text: role.name
                            }));
                        });

                        const dataPermissionsSelect = $('#dataPermissions');
                        dataPermissionsSelect.empty();
                        $.each(permissions, function(index, permission) {
                            dataPermissionsSelect.append($('<option>', {
                                value: permission.id,
                                text: permission.name
                            }));
                        });
                    },
                    error: function(xhr, status, error) {
                        console.error(xhr.responseText);
                    }
                });
            });

            $('#createRolePermission').on('submit', function(event) {
                event.preventDefault();

                var formData = $(this).serialize();
                $.ajax({
                    url: $(this).attr('action'),
                    method: 'POST',
                    data: formData,
                    success: function(response) {
                        $('#modal_role_permission').modal('hide');
                        $('#rolePermisionTable').DataTable().ajax
                            .reload();

                        Swal.fire({
                            title: 'Success!',
                            text: 'Successsfully created data',
                            icon: 'success',
                            confirmButtonText: 'OK'
                        });
                        $('#createRolePermission')[0].reset();
                        $('#dataError').text('');
                    },
                    error: function(xhr) {

                        $('#dataError').text("Role has Permission already registered.");

                    }
                });
            });
        })
    </script>
@endpush
