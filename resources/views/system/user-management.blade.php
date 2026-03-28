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
    </style>
@endpush

@section('content')
    <ul class="nav nav-tabs  mx-4">
        <li class="nav-item">
            <a class="nav-link active" aria-current="page" href="#users" data-bs-toggle="tab">
                Users
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="#admin" data-bs-toggle="tab">
                Admin
            </a>
        </li>
    </ul>



    <div class="tab-content">
        <div class="tab-pane fade show active" id="users">
            <div class="card mb-4 mx-4">
                <div class="card-header pb-0">
                    <div class="d-flex flex-row justify-content-between">
                        <div>
                        </div>
                        <button class="btn bg-gradient-primary btn-sm mb-0" type="button" data-bs-toggle="modal"
                            data-bs-target="#modalUser">+&nbsp; New User</button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive-lg">
                        <table class="table align-items-center" id="userTable" style="width: 100%">
                            <thead>
                                <tr>
                                    <th class="text-uppercase  text-xxs font-weight-bolder px-4">
                                        No
                                        <span id="sortIcon" class="fas fa-sort pointer"></span>
                                    </th>
                                    <th class="text-center text-uppercase  text-xxs font-weight-bolder ">
                                        Username
                                        <span id="sortIcon" class="fas fa-sort pointer"></span>
                                    </th>
                                    <th class="text-center text-uppercase  text-xxs font-weight-bolder ">
                                        Email
                                        <span id="sortIcon" class="fas fa-sort pointer"></span>
                                    </th>
                                    <th class="text-center text-uppercase  text-xxs font-weight-bolder ">
                                        Phone
                                        <span id="sortIcon" class="fas fa-sort pointer"></span>
                                    </th>
                                    <th class="text-center text-uppercase  text-xxs font-weight-bolder ">
                                        Action
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="tab-pane fade" id="admin">
            <div class="card mb-4 mx-4">
                <div class="card-header pb-0">
                    <div class="d-flex flex-row justify-content-between">
                        <div>
                        </div>
                        <button class="btn bg-gradient-primary btn-sm mb-0" type="button" data-bs-toggle="modal"
                            data-bs-target="#modalAdmin">+&nbsp; New Admin</button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive-lg">
                        <table class="table align-items-center" id="adminTable" style="width: 100%">
                            <thead>
                                <tr>
                                    <th class="text-uppercase  text-xxs font-weight-bolder px-4">
                                        No
                                        <span id="sortIcon" class="fas fa-sort pointer"></span>
                                    </th>
                                    <th class="text-center text-uppercase  text-xxs font-weight-bolder ">
                                        Username
                                        <span id="sortIcon" class="fas fa-sort pointer"></span>
                                    </th>
                                    <th class="text-center text-uppercase  text-xxs font-weight-bolder ">
                                        Email
                                        <span id="sortIcon" class="fas fa-sort pointer"></span>
                                    </th>
                                    <th class="text-center text-uppercase  text-xxs font-weight-bolder ">
                                        Phone
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

    {{-- Modal User --}}
    <div class="modal fade" id="modalUser" tabindex="-1" aria-labelledby="UserModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="userModalLabel">Create User</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form role="form text-left" method="POST" id="createUserForm" action="/create-user">
                    <div class="modal-body">
                        @csrf
                        <input type="hidden" class="form-control" name="role_id" id="role_id" aria-label="Name"
                            aria-describedby="name" value="{{ 2 }}">
                        <div class="mb-3">
                            <input type="text" class="form-control" placeholder="Username" name="name" id="name"
                                aria-label="Name" aria-describedby="name" value="{{ old('name') }}">
                            <p id="NameError" class="text-danger text-xs mt-2"></p>
                        </div>
                        <div class="mb-3">
                            <input type="number" class="form-control" placeholder="Phone" name="phone"
                                id="phone" aria-label="Phone" aria-describedby="email-addon"
                                value="{{ old('phone') }}">
                            <p id="PhoneError" class="text-danger text-xs mt-2"></p>
                        </div>
                        <div class="mb-3">
                            <input type="email" class="form-control" placeholder="Email" name="email"
                                id="email" aria-label="Email" aria-describedby="email-addon"
                                value="{{ old('email') }}">
                            <p id="EmailError" class="text-danger text-xs mt-2"></p>
                        </div>
                        <div class="mb-3">
                            <input type="password" class="form-control" placeholder="Password" name="password"
                                id="password" aria-label="Password" aria-describedby="password-addon"
                                autocomplete="new-password">
                            <p id="adminPasswordError" class="text-danger text-xs mt-2"></p>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn bg-gradient-dark">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Admin -->
    <div class="modal fade" id="modalAdmin" tabindex="-1" aria-labelledby="adminModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="adminModalLabel">Create Admin</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form role="form text-left" method="POST" id="createAdminForm" action="/create-user">
                    <div class="modal-body">
                        @csrf
                        <input type="hidden" class="form-control" name="role_id" id="role_id_admin" aria-label="Name"
                            aria-describedby="name" value="{{ 1 }}">
                        <div class="mb-3">
                            <input type="text" class="form-control" placeholder="Username" name="name"
                                id="name_admin" aria-label="Name" aria-describedby="name" value="{{ old('name') }}">
                            <p id="NameErrorAdmin" class="text-danger text-xs mt-2"></p>
                        </div>
                        <div class="mb-3">
                            <input type="number" class="form-control" placeholder="Phone" name="phone"
                                id="phone_admin" aria-label="Phone" aria-describedby="email-addon"
                                value="{{ old('phone') }}">
                            <p id="PhoneErrorAdmin" class="text-danger text-xs mt-2"></p>
                        </div>
                        <div class="mb-3">
                            <input type="email" class="form-control" placeholder="Email" name="email"
                                id="email_admin" aria-label="Email" aria-describedby="email-addon"
                                value="{{ old('email') }}">
                            <p id="EmailErrorAdmin" class="text-danger text-xs mt-2"></p>
                        </div>
                        <div class="mb-3">
                            <input type="password" class="form-control" placeholder="Password" name="password"
                                id="password_admin" aria-label="Password" aria-describedby="password-addon"
                                autocomplete="new-password">
                            <p id="PasswordErrorAdmin" class="text-danger text-xs mt-2"></p>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn bg-gradient-dark">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalEditUser" tabindex="-1" aria-labelledby="adminModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="adminModalLabel">Edit</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form role="form text-left" method="PUT" id="editUserFrom" action="/update-user">
                    <div class="modal-body">
                        @csrf
                        <input type="hidden" name="id" id="user_id" />
                        <div class="mb-3">
                            <input type="text" class="form-control" placeholder="Username" name="name"
                                id="name_input" aria-label="Name" aria-describedby="name" value="{{ old('name') }}">
                            <p id="NameErrorEdit" class="text-danger text-xs mt-2"></p>
                        </div>
                        <div class="mb-3">
                            <input type="number" class="form-control" placeholder="Phone" id="phone_input"
                                name="phone" aria-label="Phone" aria-describedby="email-addon"
                                value="{{ old('phone') }}">
                            <p id="PhoneErrorEdit" class="text-danger text-xs mt-2"></p>
                        </div>
                        <div class="mb-3">
                            <input type="email" class="form-control" placeholder="Email" id="email_input"
                                name="email" aria-label="Email" aria-describedby="email-addon"
                                value="{{ old('email') }}">
                            <p id="EmailErrorEdit" class="text-danger text-xs mt-2"></p>
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
@endsection

@push('script')
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script type="text/javascript">
        $(document).ready(function() {
            const csrfToken = $('meta[name="csrf-token"]').attr('content');

            $('#userTable').DataTable({
                processing: false,
                serverSide: true,
                ajax: {
                    url: "{{ route('user-management') }}",
                    data: function(d) {
                        d.query = $('#searchUser').val();
                    }
                },
                columns: [{
                        data: 'created_at',
                        render: function(data, type, row, meta) {
                            return '<div class="px-4">' + (meta.row + meta.settings._iDisplayStart +
                                1) + '</div>';
                        }
                    },
                    {
                        data: 'name',
                        name: 'name',
                        orderable: true
                    },
                    {
                        data: 'email',
                        name: 'email',
                        orderable: true
                    },
                    {
                        data: 'phone',
                        name: 'phone',
                        orderable: true,
                        render: function(data, type, row) {
                            if (data) {
                                return '+62 ' + data;
                            } else {
                                return data;
                            }
                        }
                    },
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false
                    },

                ],
                columnDefs: [{
                    targets: [2, 3, 4],
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
                    $('.deleteUserBtn').unbind('click').click(function() {
                        var userId = $(this).data(
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
                                    url: "{{ route('delete-user', ['id' => ':userId']) }}"
                                        .replace(':userId', userId),
                                    method: 'DELETE',
                                    data: {
                                        _token: csrfToken
                                    },
                                    success: function(data) {
                                        $('#userTable').DataTable().ajax
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

                    $('.editUserBtn').unbind('click').click(function() {
                        var userId = $(this).data(
                            'id');

                        $.ajax({
                            url: '/show-user/' +
                                userId,
                            method: 'GET',
                            success: function(data) {
                                console.log(data);
                                $('#name_input').val(data.name);
                                $('#phone_input').val(data.phone);
                                $('#email_input').val(data.email);


                                $('#user_id').val(data.id);
                                $('#modalEditUser').modal('show');
                            },
                            error: function(xhr, status, error) {
                                console.error(xhr.responseText);
                            }
                        });

                    });
                }
            });

            $('#adminTable').DataTable({
                processing: false,
                serverSide: true,
                ajax: {
                    url: "{{ route('admin-management') }}",
                    data: function(d) {
                        d.query = $('#searchAdmin').val();
                    }
                },
                columns: [{
                        data: 'created_at',
                        render: function(data, type, row, meta) {
                            return '<div class="px-4">' + (meta.row + meta.settings._iDisplayStart +
                                1) + '</div>';
                        }
                    },
                    {
                        data: 'name',
                        name: 'name',
                        orderable: true
                    },
                    {
                        data: 'email',
                        name: 'email',
                        orderable: true
                    },
                    {
                        data: 'phone',
                        name: 'phone',
                        orderable: true,
                        render: function(data, type, row) {
                            if (data) {
                                return '+62 ' + data;
                            } else {
                                return data;
                            }
                        }
                    },
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false
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
                    $('.deleteAdminBtn').unbind('click').click(function() {
                        var userId = $(this).data(
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
                                    url: "{{ route('delete-user', ['id' => ':userId']) }}"
                                        .replace(':userId', userId),
                                    method: 'DELETE',
                                    data: {
                                        _token: csrfToken
                                    },
                                    success: function(data) {
                                        $('#adminTable').DataTable().ajax
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

                    $('.editAdminBtn').unbind('click').click(function() {
                        var userId = $(this).data(
                            'id');

                        $.ajax({
                            url: '/show-user/' +
                                userId,
                            method: 'GET',
                            success: function(data) {
                                $('#name_input').val(data.name);
                                $('#phone_input').val(data.phone);
                                $('#email_input').val(data.email);

                                $('#user_id').val(data.id);
                                $('#modalEditUser').modal('show');
                            },
                            error: function(xhr, status, error) {
                                console.error(xhr.responseText);
                            }
                        });

                    });
                }
            });

            let ascending = true;
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

            $('#createUserForm').on('submit', function(event) {
                event.preventDefault();

                var formData = $(this).serialize();
                $.ajax({
                    url: $(this).attr('action'),
                    method: 'POST',
                    data: formData,
                    success: function(response) {
                        $('#modalAdmin').modal('hide');
                        $('#modalUser').modal('hide');

                        $('#adminTable').DataTable().ajax
                            .reload();
                        $('#userTable').DataTable().ajax
                            .reload();

                        Swal.fire({
                            title: 'Success!',
                            text: 'Successsfully created data',
                            icon: 'success',
                            confirmButtonText: 'OK'
                        });
                        $('#createUserForm')[0].reset();

                        $('#NameError').text('');
                        $('#PhoneError').text('');
                        $('#EmailError').text('');
                        $('#PasswordError').text('');

                    },
                    error: function(xhr) {
                        var errors = xhr.responseJSON.errors;
                        if (errors.hasOwnProperty('name')) {
                            $('#NameError').text(errors.name[0]);
                        }
                        if (errors.hasOwnProperty('phone')) {
                            $('#PhoneError').text(errors.phone[0]);
                        }
                        if (errors.hasOwnProperty('email')) {
                            $('#EmailError').text(errors.email[0]);
                        }
                        if (errors.hasOwnProperty("password")) {
                            $('#PasswordError').text(errors.password[0]);
                        }

                    }
                });
            });

            $('#createAdminForm').on('submit', function(event) {
                event.preventDefault();

                var formData = $(this).serialize();
                $.ajax({
                    url: $(this).attr('action'),
                    method: 'POST',
                    data: formData,
                    success: function(response) {
                        console.log(response)
                        $('#modalAdmin').modal('hide');
                        $('#adminTable').DataTable().ajax
                            .reload();

                        Swal.fire({
                            title: 'Success!',
                            text: 'Successsfully created data',
                            icon: 'success',
                            confirmButtonText: 'OK'
                        });
                        $('#createAdminForm')[0].reset();

                        $('#NameErrorAdmin').text('');
                        $('#PhoneErrorAdmin').text('');
                        $('#EmailErrorAdmin').text('');
                        $('#PasswordErrorAdmin').text('');
                    },
                    error: function(xhr) {
                        var errors = xhr.responseJSON.errors;

                        if (errors.hasOwnProperty('name')) {
                            $('#NameErrorAdmin').text(errors.name[0]);
                        }
                        if (errors.hasOwnProperty('phone')) {
                            $('#PhoneErrorAdmin').text(errors.phone[0]);
                        }
                        if (errors.hasOwnProperty('email')) {
                            $('#EmailErrorAdmin').text(errors.email[0]);
                        }
                        if (errors.hasOwnProperty("password")) {
                            $('#PasswordErrorAdmin').text(errors.password[0]);
                        }

                    }
                });
            });


            $('#editUserFrom').on('submit', function(event) {
                event.preventDefault();

                var formData = $(this).serialize();
                const userId = $('#user_id').val();
                const url = $(this).attr('action');
                $.ajax({
                    url: `${url}/${userId}`,
                    method: 'POST',
                    data: formData,
                    dataType: 'json',
                    success: function(response) {
                        $('#modalEditUser').modal('hide');
                        $('#userTable').DataTable().ajax.reload();
                        $('#adminTable').DataTable().ajax.reload();
                        Swal.fire({
                            title: 'Success!',
                            text: 'Successfully updated data',
                            icon: 'success',
                            confirmButtonText: 'OK'
                        });
                        $('#editUserFrom')[0].reset();
                        $('#NameError').text('');
                        $('#PhoneError').text('');
                        $('#EmailError').text('');


                        $('#adminPasswordError').text('');
                    },
                    error: function(xhr) {
                        var errors = xhr.responseJSON.errors;
                        if (errors.hasOwnProperty('name')) {
                            $('#NameError').text(errors.name[0]);
                        }
                        if (errors.hasOwnProperty('phone')) {
                            $('#PhoneError').text(errors.phone[0]);
                        }
                        if (errors.hasOwnProperty('email')) {
                            $('#EmailError').text(errors.email[0]);
                        }
                        if (errors.hasOwnProperty('password')) {
                            $('#adminPasswordError').text(errors.password[0]);
                        }
                    }
                });
            });
        });
    </script>
@endpush
