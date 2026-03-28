@extends('new-theme.app') 
@auth
    @yield('auth')
@endauth
@section('content') 
<section class="flex items-center p-6 w-full bg-right-top h-screen bg-no-repeat bg-[url(../assets/front-page/assets/img/hero/hero-bg.png)]">
    <!-- <div class="absolute inset-0 w-full h-full bg-black/60"></div> -->
    <div class="container">
        <div class="w-full p-4 text-center bg-white border border-gray-200 rounded-lg shadow sm:p-8 dark:bg-gray-800 dark:border-gray-700">
            <h5 class="mb-10 text-3xl  font-bold text-gray-900 dark:text-white">Portal Sistem Informasi <br>Universitas Ibn Khaldun Bogor
            </h5>
            <!-- <p class="mb-5 text-base text-gray-500 sm:text-lg dark:text-gray-400">Stay up to date and move work forward with Flowbite on iOS & Android. Download the app today.</p> -->
            <hr class="h-px my-8 bg-gray-200 border-0 dark:bg-gray-700">

            <div style="height: 420px;" class="grid lg:grid-cols-6 sm:grid-cols-3 overflow-y-auto" id='bdyItems'>

                
            </div>
        </div>
    </div>
</section>



@endsection
@section('js')
    <script>  
        setSkeltonImageText(12, 'bdyItems'); 

        $.ajax({
			type: "GET",
			url: `{{ url('dashboard/getItems') }}`,
			// data: {
			//     "searchData": val, 
			// },
			dataType: "JSON",
			success: function(res) { 
                // console.log(res);
                var isi = '';
                var endpoint = 'oauth/callback';
                var params = `?token=${getAkunTias['token']}&npm=${getAkunTias['npm']}&nidn=${getAkunTias['nidn']}&username=${getAkunTias['username']}&email=${getAkunTias['email']}&password=${getUsrTias['password']}&role=${getAkunTias['role']}&nip=${getAkunTias['nip']}&nama_lengkap=${getAkunTias['nama_lengkap']}&image=${getAkunTias['image']}&no_hp=${getAkunTias['no_hp']}&imageUrl=${getAkunTias['imageUrl']}&kode_mhs=${getAkunTias['kode_mhs']}&isverified=${getAkunTias['isverified']}&created_at=${getAkunTias['created_at']}`;
                if(res['data'].length > 0){
                    for (let i = 0; i < res['data'].length; i++) {  
                        
                        isi += ` 
                            <a href="${res['data'][i]['link']}${endpoint}${params}" target="_blank" role="status" class="mx-2 mb-4 max-w-sm p-4 border border-gray-200 rounded shadow md:p-6 dark:border-gray-700 hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-4 focus:ring-gray-100 dark:focus:ring-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:text-white dark:hover:bg-gray-700">
                                <div class="flex items-center justify-center h-20 mb-4 ">
                                    <img class="card-img-top mx-auto mt-3" src="${res['data'][i]['icon']}" alt="Icon" style="width: 80px; height: 80px;">
                                </div>
                                <h5 class="font-bold">${res['data'][i]['title']}</h5> 
                            </a>
                        `; 
                    } 
                }else{
                    isi = '<p>No items found.</p>';
                }
                $("#bdyItems").html(isi); 
            }
        });
    </script> 
@endsection
