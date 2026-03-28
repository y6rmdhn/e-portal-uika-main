@extends('layouts.user_type.guest')

@section('content')
    <section class="min-vh-100 mb-8">
        <div class="page-header align-items-start min-vh-50 pt-5 pb-11 mx-3 border-radius-lg"
            style="background-image: url('../assets/img/curved-images/curved14.jpg');">
            <span class="mask bg-primary opacity-6"></span>
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-lg-5 text-center mx-auto">
                        <h1 class="text-white mb-2 mt-5">Selamat Datang!</h1>
                        <p class="text-lead text-white">di E-PORTAL Sistem Informasi UIKA.</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="container">
            <div class="row mt-lg-n10 mt-md-n11 mt-n10">
                <div class="col-xl-4 col-lg-5 col-md-7 mx-auto">
                    <div class="card z-index-0">
                        <div class="card-header text-center pt-4">
                            <h5>Sign In</h5>
                        </div>

                        <div class="card-body"> 
                            <form class="form text-left" method="post" enctype="multipart/form-data"> 
                                @csrf
                                <div class="mb-3">
                                    <input required type="text" class="form-control" placeholder="username" name="email"
                                        id="name" aria-label="name" aria-describedby="name-addon"
                                        value="{{ old('name') }}">
                                    @error('name')
                                        <p class="text-danger text-xs mt-2">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div class="mb-3">
                                    <input required type="password" class="form-control" placeholder="Password" name="password"
                                        id="password" aria-label="Password" aria-describedby="password-addon">
                                    @error('password')
                                        <p class="text-danger text-xs mt-2">{{ $message }}</p>
                                    @enderror
                                </div>
                                <small class="text-muted">Lupa Password?
                                    <a href="/login/forgot-password"
                                        class="text-info text-gradient font-weight-bold">here</a>
                                </small>
                                <div class="text-center">
                                    <button type="submit" class="btn bg-gradient-dark w-100 my-4 mb-2">Sign In</button>
                                </div>

                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@section('js')
  <script src="{{ asset('assets/js/custom.js') }}"></script>

  <script>   
    // deleteCookie('tokens');
    // if(getCookie('tokens')){
    //     window.location.href = "{{url('dashboard')}}";  
    // }
    console.log('Token :', getCookie('tokens'));

    $(".form").submit(function(e) { 
        e.preventDefault(); 
        var formData = new FormData($('.form')[0]); 
        $.ajax({
            url: "{{url('api/auth/login')}}",
            method: "POST",
            data: formData,
            dataType: 'JSON',
            contentType: false,
            processData: false,  
            success: function (data) { 
                // console.log(data);
                if(data['status'] == 200){ 
                    setCookie('tokens', data['data']['token_portal']);
                    setCookie('tias', JSON.stringify(data['data']['user']));

                    var usrTias = {
                        email: $("[name=email]").val(),
                        password: $("[name=password]").val()
                    };
                    setCookie('user_tias', JSON.stringify(usrTias));

                    $.ajax({ 
                        type : "POST", 
                        url : `{{url('/session')}}`, 
                        data : {  
                            "_token": $("[name=_token]").val(),
                            "name" : data['data']['name'],  
                            "password" : data['data']['password'] 
                        },   
                        dataType: 'JSON',   
                        success : function(data){
                            // if (data.redirect) {
                            //     window.location.href = data.redirect;
                            // } 
                        },
                        error: function(xhr, status, error) {
                            // Handle error response
                            console.log('xhr',xhr);
                            console.log('status', status);
                        },
                        complete: function(jqXHR, textStatus) {
                            var statusCode = jqXHR.status;
                            console.log('Status Code:', statusCode);
                            
                            if (statusCode === 200) {
                                console.log('berhasil');
                                window.location.href = "{{url('dashboard')}}";
                            }
                        }
                    });
                    // window.location.href = "{{url('dashboard')}}";
                }else{
                    swal(
                    `Gagal`, 
                    `Mohon cek kembali email atau password anda!`,
                    'error'
                    ).then(function() { 
                    });
                } 
            }
        }); 
    });
  </script>
@endsection