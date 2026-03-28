@extends('layouts.user_type.guest')

@section('content')
    <section class="min-vh-100 mb-8">
        <div class="page-header align-items-start min-vh-50 pt-5 pb-11 mx-3 border-radius-lg"
            style="background-image: url('../assets/img/curved-images/curved14.jpg');">
            <span class="mask bg-primary opacity-6"></span>
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-lg-5 text-center mx-auto">
                        <h1 class="text-white mb-2 mt-5">Registrasi</h1>
                        <p class="text-lead text-white">Buat Akun Pribadimu untuk Mengakses Sistem Informasi di Universitas
                            Ibn Khaldun Bogor.</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="container">
            <div class="row mt-lg-n10 mt-md-n11 mt-n10">
                <div class="col-xl-6 col-lg-7 col-md-8 mx-auto">
                    <div class="card z-index-0 pb-5">
                        <div class="card-header text-center pt-4">
                            <h5>Silahkan Pilih:</h5>
                        </div>
                        <div class="row px-xl-5 px-sm-4 px-3" id="tabs">
                            <div class="col-6 me-auto px-1">
                                <a class="btn btn-outline-dark w-100 text-dark tab-link" href="javascript:;"
                                    data-target="#form-dosen">
                                    Dosen
                                </a>
                            </div>
                            <div class="col-6 me-auto px-1">
                                <a class="btn btn-outline-dark w-100 text-dark tab-link" href="javascript:;"
                                    data-target="#form-mahasiswa">
                                    Mahasiswa
                                </a>
                            </div>
                            <div class="col-6 ms-auto px-1">
                                <a class="btn btn-outline-dark w-100 text-dark tab-link" href="javascript:;"
                                    data-target="#form-mahasiswa-pmm">
                                    Mahasiswa PMM
                                </a>
                            </div>
                            <div class="col-6 px-1">
                                <a class="btn btn-outline-dark w-100 text-dark tab-link" href="javascript:;"
                                    data-target="#form-dosen-external">
                                    Dosen External
                                </a>
                            </div>
                            <div class="col-6 me-auto px-1">
                                <a class="btn btn-outline-dark w-100 text-dark tab-link" href="javascript:;"
                                    data-target="#form-pegawai">
                                    Pegawai
                                </a>
                            </div>
                        </div>

                        <div class="card-body tab-content d-none" id="form-dosen">
                            <h5 class="text-dark text-center mb-4">Registrasi Dosen</h5>
                            <form role="form text-left" id="register-dosen" method="POST" action="/register-dosen">
                                @csrf
                                <div class="mb-3">
                                    <label for="user-name" class="form-control-label">{{ __('NIDN/NIP') }}</label>
                                    <input type="text" class="form-control" placeholder="Input NIDN/NIP" name="npm_nidn"
                                        id="npm_nidn" aria-label="npm_nidn" aria-describedby="name"
                                        value="{{ old('npm_nidn') }}" required>
                                </div>
                                <div class="mb-3">
                                    <label for="user-name" class="form-control-label">{{ __('Email') }}</label>
                                    <input type="email" class="form-control" placeholder="Input Email" name="email"
                                        id="email" aria-label="Email" aria-describedby="email-addon"
                                        value="{{ old('email') }}" required>
                                </div>
                                <div class="mb-3">
                                    <label for="user-name" class="form-control-label">{{ __('Password') }}</label>
                                    <input type="password" class="form-control" placeholder="Input Password" name="password"
                                        id="password" aria-label="Password" aria-describedby="password-addon" required>
                                </div>
                                <div class="mb-3">
                                    <label for="user-name" class="form-control-label">{{ __('Konfimasi Password') }}</label>
                                    <input type="password" class="form-control" placeholder="Input Konfimasi Password"
                                        name="password2" id="password2" aria-label="Password"
                                        aria-describedby="password-addon" required>
                                </div>
                                <div class="text-center">
                                    <button type="submit" class="btn bg-gradient-dark w-100 my-4 mb-2"
                                        id="submit-button-dosen">
                                        Simpan
                                        <div class="spinner-border text-light d-none" role="status"
                                            id="submit-spinner-dosen">
                                            <span class="visually-hidden">Loading...</span>
                                        </div>
                                    </button>
                                </div>
                                <p class="text-sm mt-3 mb-0">Sudah punya akun? <a href="/"
                                        class="text-dark font-weight-bolder">login</a></p>
                            </form>
                        </div>
                        <div class="card-body tab-content d-none" id="form-mahasiswa">
                            <h5 class="text-dark text-center mb-4">Registrasi Mahasiswa</h5>
                            <form role="form text-left" id="register-mhs" method="POST" action="/register-mhs">
                                @csrf
                                <div class="mb-3">
                                    <label for="user-name" class="form-control-label">{{ __('NPM') }}</label>
                                    <input type="text" class="form-control" placeholder="Input NPM" name="npm_nidn"
                                        id="mhs_npm_nidn" aria-label="npm_nidn" aria-describedby="name"
                                        value="{{ old('npm_nidn') }}" required>
                                </div>
                                <div class="mb-3">
                                    <label for="user-name" class="form-control-label">{{ __('Email') }}</label>
                                    <input type="email" class="form-control" placeholder="Input Email" name="email"
                                        id="mhs_email" aria-label="Email" aria-describedby="email-addon"
                                        value="{{ old('email') }}" required>
                                </div>
                                <div class="mb-3">
                                    <label for="user-name" class="form-control-label">{{ __('Password') }}</label>
                                    <input type="password" class="form-control" placeholder="Input Password"
                                        name="password" id="mhs_password" aria-label="Password"
                                        aria-describedby="password-addon" required>
                                </div>
                                <div class="mb-3">
                                    <label for="user-name"
                                        class="form-control-label">{{ __('Konfimasi Password') }}</label>
                                    <input type="password" class="form-control" placeholder="Input Konfimasi Password"
                                        name="password2" id="mhs_password2" aria-label="Password"
                                        aria-describedby="password-addon" required>
                                </div>
                                <div class="text-center">
                                    <button type="submit" class="btn bg-gradient-dark w-100 my-4 mb-2"
                                        id="submit-button-mhs">
                                        Simpan
                                        <div class="spinner-border text-light d-none" role="status"
                                            id="submit-spinner-mhs">
                                            <span class="visually-hidden">Loading...</span>
                                        </div>
                                    </button>
                                </div>
                                <p class="text-sm mt-3 mb-0">Sudah punya akun? <a href="/"
                                        class="text-dark font-weight-bolder">login</a></p>
                            </form>
                        </div>
                        <div class="card-body tab-content d-none" id="form-mahasiswa-pmm">
                            <h5 class="text-dark text-center mb-4">Registrasi Mahasiswa PMM</h5>
                            <form role="form text-left" id="register-mhs" method="POST" action="/register-pmm">
                                @csrf
                                <div class="mb-3">
                                    <label for="user-name" class="form-control-label">{{ __('NPM') }}</label>
                                    <input type="text" class="form-control" placeholder="Input NPM" name="npm"
                                        id="mhs_pmm_npm" aria-label="npm" aria-describedby="name"
                                        value="{{ old('npm') }}" required>
                                </div>
                                <div class="mb-3">
                                    <label for="user-name" class="form-control-label">{{ __('Email') }}</label>
                                    <input type="email" class="form-control" placeholder="Input Email" name="email"
                                        id="mhs_pmm_email" aria-label="Email" aria-describedby="email-addon"
                                        value="{{ old('email') }}" required>
                                </div>
                                <div class="mb-3">
                                    <label for="user-name" class="form-control-label">{{ __('Password') }}</label>
                                    <input type="password" class="form-control" placeholder="Input Password"
                                        name="password" id="mhs_pmm_password" aria-label="Password"
                                        aria-describedby="password-addon" required>
                                </div>
                                <div class="mb-3">
                                    <label for="user-name"
                                        class="form-control-label">{{ __('Konfimasi Password') }}</label>
                                    <input type="password" class="form-control" placeholder="Input Konfimasi Password"
                                        name="password2" id="mhs_pmm_password2" aria-label="Password"
                                        aria-describedby="password-addon" required>
                                </div>
                                <div class="text-center">
                                    <button type="submit" class="btn bg-gradient-dark w-100 my-4 mb-2"
                                        id="submit-button-mhs-pmm">
                                        Simpan
                                        <div class="spinner-border text-light d-none" role="status"
                                            id="submit-spinner-mhs-pmm">
                                            <span class="visually-hidden">Loading...</span>
                                        </div>
                                    </button>
                                </div>
                                <p class="text-sm mt-3 mb-0">Sudah punya akun? <a href="/"
                                        class="text-dark font-weight-bolder">login</a></p>
                            </form>
                        </div>
                        <div class="card-body tab-content d-none" id="form-dosen-external">
                            <h5 class="text-dark text-center mb-4">Registrasi Dosen External</h5>
                            <form role="form text-left" id="register-dosen-exr" method="POST" action="/register">
                                @csrf
                                <div class="mb-3">
                                    <label class="form-control-label">{{ __('Nama Lengkap') }}</label>
                                    <input type="text" class="form-control" placeholder="Input Nama Lengkap"
                                        name="nama_lengkap" id="nama_lengkap" aria-label="nama_lengkap"
                                        aria-describedby="nama_lengkap" value="{{ old('nama_lengkap') }}">
                                </div>
                                <div class="mb-3">
                                    <label class="form-control-label">{{ __('NIP/NIK') }}</label>
                                    <input type="number" class="form-control" placeholder="Input NIP/NIK"
                                        name="nip" id="nip" aria-label="nip" value="{{ old('nip') }}">
                                </div>
                                <div class="mb-3">
                                    <label class="form-control-label">{{ __('Instansi') }}</label>
                                    <input type="text" class="form-control" placeholder="Input Instansi Asal"
                                        name="instansi" id="instansi" aria-label="instansi"
                                        value="{{ old('instansi') }}">
                                </div>
                                <div class="mb-3">
                                    <label class="form-control-label">{{ __('Tanggal Lahir') }}</label>
                                    <input type="date" class="form-control" placeholder="Input tanggal lahir"
                                        name="tgl_lahir" id="tgl_lahir" aria-label="tgl_lahir"
                                        value="{{ old('tgl_lahir') }}">
                                </div>
                                <div class="mb-3">
                                    <label class="form-control-label">{{ __('Tempat Lahir') }}</label>
                                    <input type="text" class="form-control" placeholder="Input Tempat Lahir"
                                        name="tempat_lahir" id="tempat_lahir" aria-label="tempat_lahir"
                                        value="{{ old('tempat_lahir') }}">
                                </div>
                                <div class="mb-3">
                                    <label class="form-control-label">{{ __('No. Telp') }}</label>
                                    <input type="number" class="form-control" placeholder="Input No. Telp"
                                        name="no_hp" id="no_hp" aria-label="no_hp" value="{{ old('no_hp') }}">
                                </div>
                                <div class="mb-3">
                                    <label class="form-control-label">{{ __('Agama') }}</label>
                                    <input type="text" class="form-control" placeholder="Input agama" name="agama"
                                        id="agama" aria-label="agama" value="{{ old('agama') }}">
                                </div>
                                <div class="mb-3">
                                    <label class="form-control-label">{{ __('Email') }}</label>
                                    <input type="email" class="form-control" placeholder="Input email" name="email"
                                        id="email" aria-label="email" value="{{ old('email') }}">
                                </div>
                                <div class="mb-3">
                                    <label for="user-name" class="form-control-label">{{ __('Password') }}</label>
                                    <input type="password" class="form-control" placeholder="Input Password"
                                        name="password" id="dosen_ext_password" aria-label="Password"
                                        aria-describedby="password-addon" required>
                                </div>
                                <div class="mb-3">
                                    <label for="user-name"
                                        class="form-control-label">{{ __('Konfimasi Password') }}</label>
                                    <input type="password" class="form-control" placeholder="Input Konfimasi Password"
                                        name="password2" id="dosen_ext_password2" aria-label="Password"
                                        aria-describedby="password-addon" required>
                                </div>
                                <div class="text-center">
                                    <button type="submit" class="btn bg-gradient-dark w-100 my-4 mb-2"
                                        id="submit-button-dosen-ext">
                                        Simpan
                                        <div class="spinner-border text-light d-none" role="status"
                                            id="submit-spinner-dosen-ext">
                                            <span class="visually-hidden">Loading...</span>
                                        </div>
                                    </button>
                                </div>
                                <p class="text-sm mt-3 mb-0">Sudah punya akun? <a href="/"
                                        class="text-dark font-weight-bolder">login</a></p>
                            </form>
                        </div>
                        <div class="card-body tab-content d-none" id="form-pegawai">
                            <h5 class="text-dark text-center mb-4">Registrasi Pegawai</h5>
                            <form role="form text-left" id="register-pegawai" method="POST" action="/register-pegawai">
                                @csrf
                                <div class="mb-3">
                                    <label for="user-name" class="form-control-label">{{ __('NIP') }}</label>
                                    <input type="text" class="form-control" placeholder="Input NIP" name="nip"
                                        id="pegawai_nip" aria-label="nip" aria-describedby="name"
                                        value="{{ old('nip') }}" required>
                                </div>
                                <div class="mb-3">
                                    <label for="user-name" class="form-control-label">{{ __('Email') }}</label>
                                    <input type="email" class="form-control" placeholder="Input Email" name="email"
                                        id="pegawai_email" aria-label="Email" aria-describedby="email-addon"
                                        value="{{ old('email') }}" required>
                                </div>
                                <div class="mb-3">
                                    <label for="user-name" class="form-control-label">{{ __('Password') }}</label>
                                    <input type="password" class="form-control" placeholder="Input Password"
                                        name="pegawai_password" id="password" aria-label="Password"
                                        aria-describedby="password-addon" required>
                                </div>
                                <div class="mb-3">
                                    <label for="user-name"
                                        class="form-control-label">{{ __('Konfimasi Password') }}</label>
                                    <input type="password" class="form-control" placeholder="Input Konfimasi Password"
                                        name="pegawai_password2" id="password2" aria-label="Password"
                                        aria-describedby="password-addon" required>
                                </div>
                                <div class="text-center">
                                    <button type="submit" class="btn bg-gradient-dark w-100 my-4 mb-2"
                                        id="submit-button-pegawai">
                                        Simpan
                                        <div class="spinner-border text-light d-none" role="status"
                                            id="submit-spinner-pegawai">
                                            <span class="visually-hidden">Loading...</span>
                                        </div>
                                    </button>
                                </div>
                                <p class="text-sm mt-3 mb-0">Sudah punya akun? <a href="/"
                                        class="text-dark font-weight-bolder">login</a></p>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@push('script')
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const tabs = document.querySelectorAll('.tab-link');
            const forms = document.querySelectorAll('.tab-content');

            tabs.forEach(tab => {
                tab.addEventListener('click', function() {
                    tabs.forEach(t => {
                        t.classList.remove('active', 'text-white');
                        t.classList.add(
                            'text-dark'); // Tambahkan text-dark ke tab yang tidak aktif
                    });
                    forms.forEach(f => f.classList.add('d-none'));

                    tab.classList.add('active', 'text-white');
                    tab.classList.remove('text-dark'); // Hapus text-dark dari tab yang aktif
                    document.querySelector(tab.getAttribute('data-target')).classList.remove(
                        'd-none');
                });
            });
        });

        $(document).ready(function() {
            $('#register-dosen').on('submit', function(event) {
                event.preventDefault();

                var $submitButton = $('#submit-button-dosen');
                var $submitSpinner = $('#submit-spinner-dosen');
                $submitButton.attr('disabled', true);
                $submitButton.contents().filter(function() {
                    return this.nodeType === 3;
                }).remove();
                $submitSpinner.removeClass('d-none');

                var formData = $(this).serialize();
                $.ajax({
                    url: $(this).attr('action'),
                    method: 'POST',
                    data: formData,
                    success: function(response) {
                        $submitButton.attr('disabled', false);
                        $submitButton.prepend('Simpan');
                        $submitSpinner.addClass('d-none');
                        if (response.code === 201) {
                            Swal.fire({
                                title: 'Success!',
                                text: 'Silahkan cek inbox/spam email anda!',
                                icon: 'success',
                                confirmButtonText: 'OK'
                            });
                            $('#register-dosen')[0].reset();
                        } else if (response.code === 400) {
                            const errorMessage = JSON.parse(response.message);
                            Swal.fire({
                                title: 'Error!',
                                text: errorMessage.message,
                                icon: 'error',
                                confirmButtonText: 'OK'
                            });
                        } else if (response.code === 500) {
                            Swal.fire({
                                title: 'Error!',
                                text: response.message,
                                icon: 'error',
                                confirmButtonText: 'OK'
                            });
                        }
                    },
                    error: function(xhr) {
                        $submitButton.attr('disabled', false);
                        $submitButton.prepend('Simpan');
                        $submitSpinner.addClass('d-none');
                        console.log(xhr);
                    }
                });
            });

            $('#register-mhs').on('submit', function(event) {
                event.preventDefault();

                var $submitButton = $('#submit-button-mhs');
                var $submitSpinner = $('#submit-spinner-mhs');
                $submitButton.attr('disabled', true);
                $submitButton.contents().filter(function() {
                    return this.nodeType === 3;
                }).remove();
                $submitSpinner.removeClass('d-none');

                var formData = $(this).serialize();
                $.ajax({
                    url: $(this).attr('action'),
                    method: 'POST',
                    data: formData,
                    success: function(response) {
                        $submitButton.attr('disabled', false);
                        $submitButton.prepend('Simpan');
                        $submitSpinner.addClass('d-none');
                        console.log(response)
                        if (response.code === 201) {
                            Swal.fire({
                                title: 'Success!',
                                text: 'Silahkan cek inbox/spam email anda!',
                                icon: 'success',
                                confirmButtonText: 'OK'
                            });
                            $('#register-mhs')[0].reset();
                        } else if (response.code === 400) {
                            const errorMessage = JSON.parse(response.message);
                            Swal.fire({
                                title: 'Error!',
                                text: errorMessage.message,
                                icon: 'error',
                                confirmButtonText: 'OK'
                            });
                        } else if (response.code === 500) {
                            Swal.fire({
                                title: 'Error!',
                                text: response.message,
                                icon: 'error',
                                confirmButtonText: 'OK'
                            });
                        }
                    },
                    error: function(xhr) {
                        $submitButton.attr('disabled', false);
                        $submitButton.prepend('Simpan');
                        $submitSpinner.addClass('d-none');
                        console.log(xhr);
                    }
                });
            });

            $('#register-mhs-pmm').on('submit', function(event) {
                event.preventDefault();

                var $submitButton = $('#submit-button-mhs-pmm');
                var $submitSpinner = $('#submit-spinner-mhs-pmm');
                $submitButton.attr('disabled', true);
                $submitButton.contents().filter(function() {
                    return this.nodeType === 3;
                }).remove();
                $submitSpinner.removeClass('d-none');

                var formData = $(this).serialize();
                $.ajax({
                    url: $(this).attr('action'),
                    method: 'POST',
                    data: formData,
                    success: function(response) {
                        $submitButton.attr('disabled', false);
                        $submitButton.prepend('Simpan');
                        $submitSpinner.addClass('d-none');
                        console.log(response)
                        if (response.code === 201) {
                            Swal.fire({
                                title: 'Success!',
                                text: 'Silahkan cek inbox/spam email anda!',
                                icon: 'success',
                                confirmButtonText: 'OK'
                            });
                            $('#register-mhs-pmm')[0].reset();
                        } else if (response.code === 400) {
                            const errorMessage = JSON.parse(response.message);
                            Swal.fire({
                                title: 'Error!',
                                text: errorMessage.message,
                                icon: 'error',
                                confirmButtonText: 'OK'
                            });
                        } else if (response.code === 500) {
                            Swal.fire({
                                title: 'Error!',
                                text: response.message,
                                icon: 'error',
                                confirmButtonText: 'OK'
                            });
                        }
                    },
                    error: function(xhr) {
                        $submitButton.attr('disabled', false);
                        $submitButton.prepend('Simpan');
                        $submitSpinner.addClass('d-none');
                        console.log(xhr);
                    }
                });
            });

            $('#register-dosen-ext').on('submit', function(event) {
                event.preventDefault();

                var $submitButton = $('#submit-button-dosen-ext');
                var $submitSpinner = $('#submit-spinner-dosen-ext');
                $submitButton.attr('disabled', true);
                $submitButton.contents().filter(function() {
                    return this.nodeType === 3;
                }).remove();
                $submitSpinner.removeClass('d-none');

                var formData = $(this).serialize();
                $.ajax({
                    url: $(this).attr('action'),
                    method: 'POST',
                    data: formData,
                    success: function(response) {
                        $submitButton.attr('disabled', false);
                        $submitButton.prepend('Simpan');
                        $submitSpinner.addClass('d-none');
                        console.log(response)
                        if (response.code === 201) {
                            Swal.fire({
                                title: 'Success!',
                                text: 'Silahkan cek inbox/spam email anda!',
                                icon: 'success',
                                confirmButtonText: 'OK'
                            });
                            $('#register-dosen-ext')[0].reset();
                        } else if (response.code === 400) {
                            const errorMessage = JSON.parse(response.message);
                            Swal.fire({
                                title: 'Error!',
                                text: errorMessage.message,
                                icon: 'error',
                                confirmButtonText: 'OK'
                            });
                        } else if (response.code === 500) {
                            Swal.fire({
                                title: 'Error!',
                                text: response.message,
                                icon: 'error',
                                confirmButtonText: 'OK'
                            });
                        }
                    },
                    error: function(xhr) {
                        $submitButton.attr('disabled', false);
                        $submitButton.prepend('Simpan');
                        $submitSpinner.addClass('d-none');
                        console.log(xhr);
                    }
                });
            });

            $('#register-pegawai').on('submit', function(event) {
                event.preventDefault();

                var $submitButton = $('#submit-button-pegawai');
                var $submitSpinner = $('#submit-spinner-pegawai');
                $submitButton.attr('disabled', true);
                $submitButton.contents().filter(function() {
                    return this.nodeType === 3;
                }).remove();
                $submitSpinner.removeClass('d-none');

                var formData = $(this).serialize();
                $.ajax({
                    url: $(this).attr('action'),
                    method: 'POST',
                    data: formData,
                    success: function(response) {
                        $submitButton.attr('disabled', false);
                        $submitButton.prepend('Simpan');
                        $submitSpinner.addClass('d-none');
                        console.log(response)
                        if (response.code === 201) {
                            Swal.fire({
                                title: 'Success!',
                                text: 'Silahkan cek inbox/spam email anda!',
                                icon: 'success',
                                confirmButtonText: 'OK'
                            });
                            $('#register-pegawai')[0].reset();
                        } else if (response.code === 400) {
                            const errorMessage = JSON.parse(response.message);
                            Swal.fire({
                                title: 'Error!',
                                text: errorMessage.message,
                                icon: 'error',
                                confirmButtonText: 'OK'
                            });
                        } else if (response.code === 500) {
                            Swal.fire({
                                title: 'Error!',
                                text: response.message,
                                icon: 'error',
                                confirmButtonText: 'OK'
                            });
                        }
                    },
                    error: function(xhr) {
                        $submitButton.attr('disabled', false);
                        $submitButton.prepend('Simpan');
                        $submitSpinner.addClass('d-none');
                        console.log(xhr);
                    }
                });
            });

        });
    </script>
@endpush
