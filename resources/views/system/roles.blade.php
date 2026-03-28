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
    <div class="card mb-4 mx-4">
        <div class="card-header pb-0">
            <div class="d-flex flex-row justify-content-between">
                <div>
                </div>
                <button class="btn bg-gradient-primary btn-sm mb-0" type="button" data-bs-toggle="modal"
                    data-bs-target="#modalRole">+&nbsp; New Role</button>

            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive-lg">
                <table class="table align-items-center" id="roleTable" style="width: 100%">
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


    <!-- Modal Create Admin -->
    <div class="modal fade" id="modalRole" tabindex="-1" aria-labelledby="roleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="roleModalLabel">Create Role</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form role="form text-left" method="POST" id="createRoleForm" action="/create-roles">
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

    <div class="modal fade" id="modalEditRole" tabindex="-1" aria-labelledby="roleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="roleModalLabel">Edit Role</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form role="form text-left" method="PUT" id="editRoleFrom" action="/update-roles">
                    <div class="modal-body">
                        @csrf
                        <input type="hidden" name="id" id="role_id" />
                        <div class="mb-3">
                            <input type="text" class="form-control" placeholder="Name" name="name" id="name_input"
                                aria-label="Name" aria-describedby="name">
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
@endsection

@push('script')
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script type="text/javascript">
        $(document).ready(function() {
            const csrfToken = $('meta[name="csrf-token"]').attr('content');
            $('#roleTable').DataTable({
                processing: false,
                serverSide: true,
                ajax: {
                    url: "{{ route('roles') }}",
                    data: function(d) {
                        d.query = $('#searchUser').val();
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
                    $('.deleteRoleBtn').unbind('click').click(function() {
                        var roleId = $(this).data(
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
                                    url: "{{ route('delete-roles', ['id' => ':roleId']) }}"
                                        .replace(':roleId', roleId),
                                    method: 'DELETE',
                                    data: {
                                        _token: csrfToken
                                    },
                                    success: function(data) {
                                        $('#roleTable').DataTable().ajax
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

                    $('.editRoleBtn').unbind('click').click(function() {
                        var roleId = $(this).data(
                            'id');

                        $.ajax({
                            url: '/show-roles/' +
                                roleId,
                            method: 'GET',
                            success: function(data) {
                                $('#name_input').val(data.name);
                                $('#role_id').val(data.id);
                                $('#modalEditRole').modal('show');
                            },
                            error: function(xhr, status, error) {
                                console.error(xhr.responseText);
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
            $('#createRoleForm').on('submit', function(event) {
                event.preventDefault();

                var formData = $(this).serialize();
                $.ajax({
                    url: $(this).attr('action'),
                    method: 'POST',
                    data: formData,
                    success: function(response) {
                        $('#modalRole').modal('hide');
                        $('#roleTable').DataTable().ajax
                            .reload();

                        Swal.fire({
                            title: 'Success!',
                            text: 'Successsfully created data',
                            icon: 'success',
                            confirmButtonText: 'OK'
                        });
                        $('#createRoleForm')[0].reset();
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
            $('#editRoleFrom').on('submit', function(event) {
                event.preventDefault();

                var formData = $(this).serialize();
                const roleId = $('#role_id').val();
                const url = $(this).attr('action');
                $.ajax({
                    url: `${url}/${roleId}`,
                    method: 'POST',
                    data: formData,
                    dataType: 'json',
                    success: function(response) {
                        $('#modalEditRole').modal('hide');
                        $('#roleTable').DataTable().ajax.reload();
                        Swal.fire({
                            title: 'Success!',
                            text: 'Successfully updated data',
                            icon: 'success',
                            confirmButtonText: 'OK'
                        });
                        $('#editRoleFrom')[0].reset();
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

        })
    </script>
@endpush
