@extends('new-theme.app') 
    
@section('content')
    <section class="flex items-center p-6 w-full bg-right-bottom h-screen bg-no-repeat bg-cover bg-[url(https://gpm.ppsuika.ac.id/wp-content/uploads/2023/06/uika-bogor.jpg)]">
        <div class="absolute inset-0 w-full h-full bg-black/60"></div>
        <!-- end backdrop -->
        
        <div class="container sm:p-0">
            <div class="backdrop-blur-2xl bg-black/40 rounded-lg overflow-hidden max-w-5xl mx-auto">
                <div class="grid lg:grid-cols-2">
                    <div class="flex flex-col h-full p-10">
                        <div class="pb-5">
                            <a href="{{ url('/') }}" class="flex justify-center">
                                <img src="{{ asset('assets/img/favicon.png') }}" alt="dark logo" class="h-20">
                            </a>
                        </div><!-- end logo -->
                        <div class="pb-6 my-auto text-center">
                            <h4 class="text-2xl font-bold text-white mb-3">E-PORTAL</h4>
                            <p class="text-gray-300 mb-5 max-w-sm mx-auto"><b>Register</b><br>Enter your email address and we'll send you an email with instructions to reset your password.</p>

                            <iframe onload="window.parent.scrollTo(0,0)" src="https://tias.ti.ft.uika-bogor.ac.id/register" width="100%" style="height: 55vh;" frameborder="0" allowfullscreen="true"></iframe>
                             
                        </div>
                        <div class="w-full text-center">
                            <p class="text-gray-300 leading-6 text-base font-medium">Already have an account? <a href="{{ url('/') }}" class="text-primary font-semibold ms-1">Log In</a></p>
                        </div>
                    </div> <!-- end col -->
                    <div class="hidden lg:block">
                        <div class="relative overflow-hidden">
                            <img src="https://uika-bogor.ac.id/uploads/files/surat-keterangan-semester-gasal-2023-2024.jpg" alt="" class="max-w-full max-h-full transform -scale-x-200">
                            <div class="absolute inset-0 bg-black/70">
                                <div class="flex items-end justify-center h-full">
                                    <div class="swiper auth-swiper rounded-xl">
                                        <div class="swiper-wrapper">
                                            <div class="swiper-slide rounded-xl">
                                                <div class="flex items-end justify-center h-full">
                                                    <div class="p-6 mb-10 text-center">
                                                        <h5 class="text-xl font-bold text-white mb-3">Pengumuman Pembayaran.</h5>
                                                        <p class="text-base font-medium text-gray-300">Berdasarkan Pengumuman Rektor Universitas Ibn Khaldun Bogor Nomor: 1911/K.11/UIKA/2023tentang Pembayaran Biaya Perkuliahan Semester GASAL Tahun Akademik 2023/2024.</p>
                                                    </div>
                                                </div>
                                            </div><!-- end swiper-slider -->

                                            <div class="swiper-slide rounded-xl">
                                                <div class="flex items-end justify-center h-full">
                                                    <div class="p-6 mb-10 text-center">
                                                        <h5 class="text-xl font-bold text-white mb-3">Kalender Akademik.</h5>
                                                        <p class="text-base font-medium text-gray-300">Berdasarkan Surat Keputusan Rektor Universitas Ibn Khaldun Bogor Nomor: 270/KEP/UIKA/2023 tentang Kalender Akademik Universitas Ibn Khaldun Bogor Tahun Akademik 2023/2024.</p>
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
    $(".form").submit(function(e) { 
        e.preventDefault(); 
        var formData = new FormData($('.form')[0]); 
        $.ajax({
            url: "{{ env('TIAS_API_URL') }}/auth/forgotPassword",
            method: "POST",
            data: {
                email: $('[name=email]').val()
            },
            dataType: 'JSON',
            // contentType: false,
            // processData: false,  
            success: function (data) {  
                swal(
                `Info`, 
                `Password Reset Email Sent, Check your email for reset password!`,
                'success'
                ).then(function() { 
                    window.location.href = "{{url('/')}}";
                }); 
            },
            error: function(xhr, status, error) {
                // Handle error response 
                // console.log('status', status);
            },
            complete: function(jqXHR, textStatus) {
                var ress = jqXHR.responseJSON;
                var statusCode = jqXHR.status;  
                
                if (statusCode === 200) {
                    
                }else{
                    swal(
                    `${ress['message']}`, 
                    ``,
                    'error'
                    ).then(function() { 
                        $('[name=email]').val('')
                    });
                }
            }
        }); 
    });
  </script>
@endsection