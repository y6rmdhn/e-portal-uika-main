@extends('new-theme.app')

@section('content')
    <section
        class="flex items-center p-6 w-full bg-right-bottom h-screen bg-no-repeat bg-cover bg-[url(https://gpm.ppsuika.ac.id/wp-content/uploads/2023/06/uika-bogor.jpg)]">
        <div class="absolute inset-0 w-full h-full bg-black/60"></div>
        <!-- end backdrop -->

        <div class="container">
            <div class="backdrop-blur-2xl bg-black/40 rounded-lg overflow-hidden max-w-5xl mx-auto">
                <div class="grid lg:grid-cols-2">
                    <div class="flex flex-col h-full p-10">
                        <div class="pb-2">
                            <a href="{{ url('/') }}" class="flex justify-center">
                                <img src="{{ asset('assets/img/favicon.png') }}" alt="dark logo" class="h-20">
                            </a>
                        </div>
                        <div class="pb-6 my-auto text-center">
                            <h4 class="text-2xl font-bold text-white mb-3">E-PORTAL</h4>
                            <!-- <h4 class="text-2xl font-bold text-white mb-3">Sign In</h4> -->
                            <p class="text-gray-300 mb-5 max-w-sm mx-auto"><b>Sign In</b><br>Portal UIKA sistem informasi
                                dari Universitas Ibn Khaldun Bogor.</p>

                            <!-- form -->
                            <form class="form text-start" method="post" enctype="multipart/form-data">
                                @csrf
                                <div class="mb-4">
                                    <label for="emailaddress"
                                        class="block text-base/normal font-semibold text-gray-200 mb-2">Email
                                        address</label>
                                    <input
                                        class="block w-full rounded py-1.5 px-3 bg-transparent border-white/10 border-gray-200 text-white/80 focus:border-white/25 focus:ring-transparent"
                                        name="email" type="email" id="emailaddress" required=""
                                        placeholder="Enter your email">
                                </div>
                                <!-- end email input -->
                                <div class="mb-4">
                                    <label for="password"
                                        class="block text-base/normal font-semibold text-gray-200 mb-2">Password</label>
                                    <input
                                        class="block w-full rounded py-1.5 px-3 bg-transparent border-white/10 border-gray-200 text-white/80 focus:border-white/25 focus:ring-transparent"
                                        name="password" type="password" required="" id="password"
                                        placeholder="Enter your password">
                                </div>
                                <!-- end password input -->
                                <div class="mb-6">
                                    <a href="{{ url('forget') }}"
                                        class="text-gray-200 float-right border-b border-dashed"><small>Forgot your
                                            password?</small></a>
                                    <input type="checkbox"
                                        class="h-4 w-4 rounded border-white/20 bg-white/20 text-primary-600 shadow-sm focus:border-primary-300 focus:ring focus:ring-primary/60 focus:ring-offset-0"
                                        id="checkbox-signin">
                                    <label class="ms-2 text-gray-200 align-middle" for="checkbox-signin">Remember me</label>
                                </div>
                                <!-- end checkbox input -->
                                <div class="mb-6 text-center">
                                    <button
                                        class="w-full inline-flex items-center justify-center px-6 py-2 backdrop-blur-2xl bg-white/20 text-white rounded-lg transition-all duration-500 group hover:bg-primary/20 hover:text-primary mt-5"
                                        type="submit">
                                        <i class="uil uil-navigator me-2 text-lg"></i> <span class="fw-bold">Log In</span>
                                    </button>
                                </div>
                            </form><!-- end form-->

                            <div class="pb-6">
                                <div class="text-center">
                                    <p class="text-lg text-gray-200 font-semibold mb-4">Sign in with</p>
                                    <div class="flex flex-wrap items-center justify-center gap-2">
                                        <a href="{{ route('redirect') }}"
                                            class="h-10 p-2 inline-flex items-center justify-center backdrop-blur-2xl bg-white/20 rounded-lg transition-all duration-500 group hover:bg-primary/60 text-white">
                                            <img src="https://tias.ti.ft.uika-bogor.ac.id/img/google.png" width="25"
                                                alt=""> &nbsp;&nbsp; Google
                                        </a>
                                    </div><!-- end social -->
                                </div>
                            </div>
                        </div>
                        <div class="w-full text-center">
                            <p class="text-gray-300 leading-6 text-base font-medium">Don't have an account? <a
                                    href="{{ url('regist') }}" class="text-primary font-semibold ms-1">Sign Up</a></p>
                        </div>
                    </div> <!-- end col -->
                    <div class="hidden lg:block">
                        <div class="relative overflow-hidden">
                            <img src="https://uika-bogor.ac.id/uploads/files/surat-keterangan-semester-gasal-2023-2024.jpg"
                                alt="" class="max-w-full max-h-full transform -scale-x-200">
                            <div class="absolute inset-0 bg-black/70">
                                <div class="flex items-end justify-center h-full">
                                    <div class="swiper auth-swiper rounded-xl">
                                        <div class="swiper-wrapper">
                                            <div class="swiper-slide rounded-xl">
                                                <div class="flex items-end justify-center h-full">
                                                    <div class="p-6 mb-10 text-center">
                                                        <h5 class="text-xl font-bold text-white mb-3">Pengumuman Pembayaran.
                                                        </h5>
                                                        <p class="text-base font-medium text-gray-300">Berdasarkan
                                                            Pengumuman Rektor Universitas Ibn Khaldun Bogor Nomor:
                                                            1911/K.11/UIKA/2023tentang Pembayaran Biaya Perkuliahan Semester
                                                            GASAL Tahun Akademik 2023/2024.</p>
                                                    </div>
                                                </div>
                                            </div><!-- end swiper-slider -->

                                            <div class="swiper-slide rounded-xl">
                                                <div class="flex items-end justify-center h-full">
                                                    <div class="p-6 mb-10 text-center">
                                                        <h5 class="text-xl font-bold text-white mb-3">Kalender Akademik.
                                                        </h5>
                                                        <p class="text-base font-medium text-gray-300">Berdasarkan Surat
                                                            Keputusan Rektor Universitas Ibn Khaldun Bogor Nomor:
                                                            270/KEP/UIKA/2023 tentang Kalender Akademik Universitas Ibn
                                                            Khaldun Bogor Tahun Akademik 2023/2024.</p>
                                                    </div>
                                                </div>
                                            </div><!-- end swiper-slider -->

                                        </div><!-- end wrapper -->

                                        <div class="swiper-pagination"></div>
                                        <!-- end swiper-pagination -->

                                    </div><!-- end swiper -->
                                </div>
                            </div>
                        </div>
                    </div><!-- end gri-cols -->
                </div> <!-- end grid -->
            </div> <!-- end bg -->
        </div><!-- end container -->
    </section><!-- end section -->
@endsection

@section('js')
    <script>
        @if (session()->has('success') == "You've been logged out.")
            // AKSI KETIKA LOGOUT BY URL
            deleteCookie('tokens');
            deleteCookie('tias');
            deleteCookie('user_tias');
        @endif

        if (getCookie('tokens')) {
            window.location.href = "{{ url('dashboard') }}";
        } else {
            deleteCookie('tokens');
            deleteCookie('tias');
            deleteCookie('user_tias');
        }

        @if (session()->has('success') == "You've been logged out.")
            // AKSI KETIKA LOGOUT BY URL
            deleteCookie('tokens');
            deleteCookie('tias');
            deleteCookie('user_tias');
        @endif

        $(".form").submit(function(e) {
            e.preventDefault();
            var formData = new FormData($('.form')[0]);
            $.ajax({
                url: "{{ url('api/auth/login') }}",
                method: "POST",
                data: formData,
                dataType: 'JSON',
                contentType: false,
                processData: false,
                success: function(data) {
                    // console.log(data);
                    if (data['status'] == 200) {
                        setCookie('tokens', data['data']['token_portal']);
                        setCookie('tias', JSON.stringify(data['data']['user']));

                        var usrTias = {
                            email: $("[name=email]").val(),
                            password: $("[name=password]").val()
                        };
                        setCookie('user_tias', JSON.stringify(usrTias));

                        $.ajax({
                            type: "POST",
                            url: `{{ url('/session') }}`,
                            data: {
                                "_token": $("[name=_token]").val(),
                                "name": data['data']['name'],
                                "password": data['data']['password']
                            },
                            dataType: 'JSON',
                            success: function(data) {
                                // if (data.redirect) {
                                //     window.location.href = data.redirect;
                                // } 
                            },
                            error: function(xhr, status, error) {
                                // Handle error response
                                console.log('xhr', xhr);
                                console.log('status', status);
                            },
                            complete: function(jqXHR, textStatus) {
                                var statusCode = jqXHR.status;
                                console.log('Status Code:', statusCode);

                                if (statusCode === 200) {
                                    console.log('berhasil');
                                    window.location.href = "{{ url('dashboard') }}";
                                }
                            }
                        });
                        // window.location.href = "{{ url('dashboard') }}";
                    } else {
                        swal(
                            `Gagal`,
                            `Mohon cek kembali email atau password anda!`,
                            'error'
                        ).then(function() {});
                    }
                }
            });
        });

        // }
        @if ($name != null && $password != null)
            var users = @json($user);
            setCookie('tokens', '{{ $token_portal }}');
            setCookie('tias', JSON.stringify(users));
            setCookie('user_tias', JSON.stringify({
                email: '{{ $name }}',
                password: '{{ $password }}'
            }));
            $.ajax({
                type: "POST",
                url: `{{ url('/session') }}`,
                data: {
                    "_token": $("[name=_token]").val(),
                    "name": '{{ $name }}',
                    "password": '{{ $password }}'
                },
                dataType: 'JSON',
                success: function(data) {
                    // if (data.redirect) {
                    //     window.location.href = data.redirect;
                    // } 
                },
                error: function(xhr, status, error) {
                    // Handle error response
                    console.log('xhr', xhr);
                    console.log('status', status);
                },
                complete: function(jqXHR, textStatus) {
                    var statusCode = jqXHR.status;
                    console.log('Status Code:', statusCode);

                    if (statusCode === 200) {
                        console.log('berhasil');
                        window.location.href = "{{ url('dashboard') }}";
                    }
                }
            });
        @endif
    </script>
@endsection
