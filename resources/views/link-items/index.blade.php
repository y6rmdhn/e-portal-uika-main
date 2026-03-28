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
                <button class="btn bg-gradient-dark btn-sm mb-0" type="button" data-bs-toggle="modal"
                    data-bs-target="#modalItem">+&nbsp; New Item</button>

            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive-lg">
                <table class="table align-items-center" id="itemsTable" style="width: 100%">
                    <thead>
                        <tr>
                            <th class="text-center text-uppercase  text-xxs font-weight-bolder ">
                                No
                                <span id="sortIcon" class="fas fa-sort pointer"></span>
                            </th>
                            <th class="text-center text-uppercase  text-xxs font-weight-bolder ">
                                Icon
                                <span id="sortIcon" class="fas fa-sort pointer"></span>
                            </th>
                            <th class="text-center text-uppercase  text-xxs font-weight-bolder ">
                                Title
                            </th>
                            <th class="text-center text-uppercase  text-xxs font-weight-bolder ">
                                Link
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
    <div class="modal fade" id="modalItem" tabindex="-1" aria-labelledby="itemModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="itemModalLabel">Create Item</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form role="form text-left" method="POST" id="createEdiForm" action="/create-link-item"
                    enctype="multipart/form-data">
                    <div class="modal-body">
                        @csrf
                        <div class="mb-3">
                            <label for="title" class="form-label">Title</label>
                            <input type="text" class="form-control" placeholder="input title" name="title"
                                id="title" aria-label="Title" aria-describedby="title" value="{{ old('title') }}"
                                required>
                            <p id="titleError" class="text-danger text-xs mt-2"></p>
                        </div>
                        <div class="mb-3">
                            <label for="title" class="form-label">URL</label>
                            <input type="url" class="form-control" placeholder="input url" name="link" id="link"
                                aria-label="Link" aria-describedby="link" value="{{ old('link') }}" required>
                            <p id="linkError" class="text-danger text-xs mt-2"></p>
                        </div>
                        <div class="mb-3">
                            <label for="deskripsi" class="form-label">Deskripsi</label>
                            <textarea class="form-control" id="deskripsi" name="deskripsi" rows="3" required></textarea>
                            <p id="deskripsiError" class="text-danger text-xs mt-2"></p>
                        </div>
                        <div class="mb-3">
                            <label for="icon" class="form-label">Icon</label>
                            <input type="file" class="form-control" placeholder="Icon" name="icon" id="icon"
                                aria-label="Icon" aria-describedby="icon" value="{{ old('icon') }}" required>
                            <p id="iconError" class="text-danger text-xs mt-2"></p>
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

    <div class="modal fade" id="modalEditItem" tabindex="-1" aria-labelledby="itemModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="itemModalLabel">Edit Item</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form role="form text-left" method="PUT" id="editItemFrom" action="/update-link-item"
                    enctype="multipart/form-data">
                    <div class="modal-body">
                        @csrf
                        <input type="hidden" name="id" id="item_id" />
                        <div class="mb-3">
                            <label for="title" class="form-label">Title</label>
                            <input type="text" class="form-control" placeholder="input title" name="title"
                                id="title_input" aria-label="Title" aria-describedby="title">
                            <p id="titleErrorEdit" class="text-danger text-xs mt-2"></p>
                        </div>
                        <div class="mb-3">
                            <label for="deskripsi" class="form-label">URL</label>
                            <input type="url" class="form-control" placeholder="input url" name="link"
                                id="link_input" aria-label="Link" aria-describedby="title">
                            <p id="linkErrorEdit" class="text-danger text-xs mt-2"></p>
                        </div>
                        <div class="mb-3">
                            <label for="deskripsi" class="form-label">Deskripsi</label>
                            <textarea class="form-control" id="deskripsi_input" name="deskripsi" rows="3" required></textarea>
                            <p id="deskripsiErrorEdit" class="text-danger text-xs mt-2"></p>
                        </div>
                        <div class="mb-3">
                            <label for="icon" class="form-label">Icon</label>
                            <input type="file" class="form-control" placeholder="Icon" name="icon"
                                id="icon_input" aria-label="Icon" aria-describedby="icon">
                            <p id="iconErrorEdit" class="text-danger text-xs mt-2"></p>
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
            $('#itemsTable').DataTable({
                processing: false,
                serverSide: true,
                ajax: {
                    url: "{{ route('link-items') }}",
                    data: function(d) {
                        d.query = $('#searchItems').val();
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
                        data: 'file_icon',
                        name: 'file_icon',
                        orderable: false
                    },
                    {
                        data: 'title',
                        name: 'title',
                        orderable: true
                    },
                    {
                        data: 'link',
                        name: 'link',
                        orderable: true
                    },
                    {
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
                    $('.deleteItemBtn').unbind('click').click(function() {
                        var itemId = $(this).data(
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
                                    url: "{{ route('delete-link-item', ['id' => ':itemId']) }}"
                                        .replace(':itemId', itemId),
                                    method: 'DELETE',
                                    data: {
                                        _token: csrfToken
                                    },
                                    success: function(data) {
                                        $('#itemsTable').DataTable().ajax
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

                    $('.editItemBtn').unbind('click').click(function() {
                        var itemId = $(this).data(
                            'id');

                        $.ajax({
                            url: '/show-link-item/' +
                                itemId,
                            method: 'GET',
                            success: function(data) {
                                $('#title_input').val(data.title);
                                $('#link_input').val(data.link);
                                $('#deskripsi_input').val(data.deskripsi);

                                $('#item_id').val(data.id);
                                $('#modalEditItem').modal('show');
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
            $('#createEdiForm').on('submit', function(event) {
                event.preventDefault();

                var formData = new FormData(this);
                $.ajax({
                    url: $(this).attr('action'),
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        $('#modalItem').modal('hide');
                        $('#itemsTable').DataTable().ajax
                            .reload();

                        Swal.fire({
                            title: 'Success!',
                            text: 'Successsfully created data',
                            icon: 'success',
                            confirmButtonText: 'OK'
                        });
                        $('#createEdiForm')[0].reset();
                        $('#titleError').text('');
                        $('#linkError').text('');
                        $('#iconError').text('');
                        $('#deskripsiError').text('');
                    },
                    error: function(xhr) {
                        var errors = xhr.responseJSON.errors;
                        if (errors.hasOwnProperty('title')) {
                            $('#titleError').text(errors.title[0]);
                        } else if (errors.hasOwnProperty('link')) {
                            $('#linkError').text(errors.link[0]);
                        } else if (errors.hasOwnProperty("icon")) {
                            $('#iconError').text(errors.icon[0])
                        } else if (errors.hasOwnProperty("deskripsi")) {
                            $('#deskripsiError').text(errors.deskripsi[0])
                        }

                    }
                });
            });
            $('#editItemFrom').on('submit', function(event) {
                event.preventDefault();

                var formData = new FormData(this);

                const itemId = $('#item_id').val();
                const url = $(this).attr('action');
                $.ajax({
                    url: `${url}/${itemId}`,
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        $('#modalEditItem').modal('hide');
                        $('#itemsTable').DataTable().ajax.reload();
                        Swal.fire({
                            title: 'Success!',
                            text: 'Successfully updated data',
                            icon: 'success',
                            confirmButtonText: 'OK'
                        });
                        $('#editItemFrom')[0].reset();
                        $('#titleErrorEdit').text('');
                        $('#linkErrorEdit').text('');
                        $('#iconErrorEdit').text('');
                        $('#deskripsiErrorEdit').text('');
                    },
                    error: function(xhr) {
                        var errors = xhr.responseJSON.errors;
                        if (errors.hasOwnProperty('title')) {
                            $('#titleErrorEdit').text(errors.title[0]);
                        } else if (errors.hasOwnProperty("link")) {
                            $('#linkErrorEdit').text(errors.link[0]);
                        } else if (errors.hasOwnProperty("icon")) {
                            $('#iconErrorEdit').text(errors.icon[0]);
                        } else if (errors.hasOwnProperty("deskripsi")) {
                            $('#deskripsiErrorEdit').text(errors.deskripsi[0]);
                        }
                    }
                });
            });

        })
    </script>
@endpush
