
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <title>
        E-PORTAL UIKA
    </title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="Tailwind CSS Saas & Software Landing Page Template, agency, application, business, clean, creative, it solutions, modern, multipurpose, saas, software, tailwind css." name="description" />
    <meta content="coderthemes" name="author" />

    <link rel="apple-touch-icon" sizes="76x76" href="{{ asset('assets/img/apple-icon.png') }}">
    <link rel="icon" type="image/png" href="{{ asset('assets/img/favicon.png') }}"> 

      <!-- style css -->
    <link href="https://coderthemes.com/opixo/assets/css/style.min.css" rel="stylesheet" type="text/css">

    <!-- unicons Icons css -->
    <link href="{{ asset('assets/new-theme/libs/@iconscout/unicons/css/line.css') }}" rel="stylesheet" type="text/css">

    <!-- font-awesome Icons css -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" integrity="sha512-Kc323vGBEqzTmouAECnVceyQqyqdsSiqLQISBL29aUW4U/M7pSPA/gEUZQqv1cwx4OnYxTxve5UMg5GT6L4JJg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/js/all.min.js" integrity="sha512-6sSYJqDreZRZGkJ3b+YfdhB3MzmuP9R7X1QZ6g5aIXhRvR1Y/N/P47jmnkENm7YL3oqsmI6AK+V6AD99uWDnIw==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>

    <!--Swiper slider css-->
    <link href="{{ asset('assets/new-theme/libs/swiper/swiper-bundle.min.css') }}" rel="stylesheet" type="text/css">

    <!-- sweetalert2 -->
	<link href="{{ asset('assets/vendor/sweetalert2/dist/sweetalert2.min.css') }}" rel="stylesheet">

    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/flowbite@2.5.1/dist/flowbite.min.js"></script>

    @yield('css')
</head>

<body class="relative h-full">

    
    @auth
        <nav class="bg-white border-gray-200 dark:bg-gray-900">
            <div class="flex flex-wrap items-center justify-between max-w-screen-xl mx-auto p-4">
                <a href="{{ url('dashboard') }}" class="flex items-center space-x-3 rtl:space-x-reverse">
                    <img src="{{ asset('assets/img/favicon.png') }}" class="h-8" alt="Flowbite Logo" />
                    <span class="self-center text-2xl font-semibold whitespace-nowrap dark:text-white">E-PORTAL</span>
                </a>
                <div class="flex items-center md:order-2 space-x-1 md:space-x-2 rtl:space-x-reverse"> 
                    @can('users-management')
                        <a href="{{ url('link-items') }}" class="text-gray-800 dark:text-white hover:bg-gray-50 focus:ring-4 focus:ring-gray-300 font-medium rounded-lg text-sm px-4 py-2 md:px-5 md:py-2.5 dark:hover:bg-gray-700 focus:outline-none dark:focus:ring-gray-800">
                            <i class="fas fa-lg fa-list-ul" style="font-size: 1rem;" aria-hidden="true"></i>
                            &nbsp; Link Items
                        </a>
                    @endcan
                    <a href="{{ url('/logout') }}" class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-4 py-2 md:px-5 md:py-2.5 dark:bg-blue-600 dark:hover:bg-blue-700 focus:outline-none dark:focus:ring-blue-800">
                        <i class="fa fa-sign-out" aria-hidden="true"></i> &nbsp;Logout
                    </a> 
                </div> 
            </div>
        </nav> 
    @endauth 

    
    @yield('content') 



    @auth
        <footer class="bg-white rounded-lg shadow dark:bg-gray-900 m-4">
            <div class="w-full max-w-screen-xl mx-auto p-4 md:py-8">
                <div class="sm:flex sm:items-center sm:justify-between">
                    <div class="mb-6 md:mb-0">
                        <a href="{{ url('dashboard') }}" class="flex items-center">
                            <img src="{{ asset('assets/img/favicon.png') }}" class="h-8 me-3" alt="FlowBite Logo" />
                            <span class="self-center text-2xl font-semibold whitespace-nowrap dark:text-white">E-PORTAL</span>
                        </a>
                        <p class="w-80 mt-3">Portal UIKA adalah sumber utama untuk menjelajahi semua sistem informasi dari Universitas Ibn Khaldun Bogor. Temukan pintu masuk yang menyediakan akses yang mudah dan terpadu ke berbagai layanan universitas dalam satu platform.</p>
                    </div> 
                    <div class="grid grid-cols-2 gap-8 sm:gap-6 sm:grid-cols-4">
                        <a href="https://www.instagram.com/uika_bogor/" target="_blank">
                            <img src="../assets/front-page/assets/img/icons/instagram.png" alt="icon"
                                style="width: 50px; height: 50px; margin-right: 10px;" />
                        </a>
                        <a href="https://www.facebook.com/pagesuika" target="_blank">
                            <img src="../assets/front-page/assets/img/icons/facebook.png" alt="icon"
                                style="width: 50px; height: 50px; margin-right: 10px;" />
                        </a>
                        <a href="https://www.youtube.com/@UIKA-TV" target="_blank">
                            <img src="../assets/front-page/assets/img/icons/youtube.png" alt="icon"
                                style="width: 50px; height: 50px; margin-right: 10px;" />
                        </a>
                        <a href="https://x.com/uikabogor" target="_blank">
                            <img src="../assets/front-page/assets/img/icons/twitter.png" alt="icon"
                                style="width: 50px; height: 50px; margin-right: 10px;" />
                        </a>
                    </div>   
                </div>
                <hr class="my-6 border-gray-200 sm:mx-auto dark:border-gray-700 lg:my-8" />
                <span class="block text-sm text-gray-500 sm:text-center dark:text-gray-400">© 2024 <a href="{{ url('dashboard') }}" class="hover:underline">E-PORTAL</a>. All Rights Reserved.</span>
            </div>
        </footer>
    @endauth 


    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js" integrity="sha512-v2CJ7UaYy4JwqLDIrZUI/4hqeoQieOmAZNXBeQyjo21dadnwR+8ZaIJVT8EE2iyI61OV8e6M8PP2/4hpQINQ/g==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <!-- plugin -->
    <script src="{{ asset('assets/new-theme/libs/swiper/swiper-bundle.min.js') }}"></script> 
    <!-- custom swiper js -->
    <script src="https://coderthemes.com/opixo/assets/js/swiper.js"></script> 
    <!-- sweetalert2 -->
	<script src="{{ asset('assets/vendor/sweetalert2/dist/sweetalert2.min.js') }}"></script>
    <!-- custom js -->
    <script src="{{ asset('assets/js/custom.js') }}"></script>
 
    @auth
    <script>  
        function logouts(){
			deleteCookie('tokens');
            deleteCookie('tias');
            deleteCookie('user_tias');
			window.location.href = "{{url('logout')}}";
		}
        var getAkunTias = JSON.parse(getCookie('tias')); 
        var getUsrTias = JSON.parse(getCookie('user_tias'));  
    </script>
    @endauth 
    
    @yield('js')
</body>

</html>