<!DOCTYPE html>
<html lang="en-US" dir="ltr">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">


    <!-- ===============================================-->
    <!--    Document Title-->
    <!-- ===============================================-->
    <title>E-PORTAL UIKA</title>


    <!-- ===============================================-->
    <!--    Favicons-->
    <!-- ===============================================-->
    <link rel="icon" type="image/png" sizes="32x32" href="../assets/front-page/assets/img/favicon.png">
    <meta name="theme-color" content="#ffffff">


    <!-- ===============================================-->
    <!--    Stylesheets-->
    <!-- ===============================================-->
    <link rel="preconnect" href="https://fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@200;300;400;500;600;700&amp;display=swap"
        rel="stylesheet">
    <link href="../assets/front-page/vendors/prism/prism.css" rel="stylesheet">
    <link href="../assets/front-page/vendors/swiper/swiper-bundle.min.css" rel="stylesheet">
    <link href="../assets/front-page/assets/css/theme.css" rel="stylesheet" />
    <link href="../assets/front-page/assets/css/user.css" rel="stylesheet" />

    <style>
        .glass-effect {
            background: rgba(255, 255, 255, 0.3);
            /* Menambah opacity */
            border: 1px solid rgba(255, 255, 255, 0.5);
            /* Border lebih tebal */
            backdrop-filter: blur(15px);
            /* Meningkatkan blur */
            -webkit-backdrop-filter: blur(15px);
            /* Untuk dukungan browser */
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
            /* Menambah shadow */
            border-radius: 15px;
            /* Membulatkan sudut */
            padding: 20px;
            /* Menambah padding untuk lebih terlihat */
        }

        .card-front {
            transition: transform 0.2s;
        }

        .card-front:hover {
            transform: translateY(-10px);
            /* Atur jarak naik (vertical) sesuai keinginan */
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            /* Efek bayangan (shadow) saat hover */
        }
    </style>

</head>


<body>

    <!-- ===============================================-->
    <!--    Main Content-->
    <!-- ===============================================-->
    <main class="main" id="top">
        <nav class="navbar navbar-expand-lg fixed-top navbar-dark" data-navbar-on-scroll="data-navbar-on-scroll">
            <div class="container">
                <div class="navbar-brand" style="display: flex; align-items: center;">
                    <img src="../assets/front-page/assets/img/uika.png" alt="logo"
                        style="width: 50px; height: 50px; margin-right: 10px;" />
                    <h5 style="margin: 0;" class="fs-lg-4 fs-md-2 fs-1 text-white">PORTAL UIKA</h5>
                </div>

                <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                    data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent"
                    aria-expanded="false" aria-label="Toggle navigation"><i
                        class="fa-solid fa-bars text-white fs-3"></i></button>
                <div class="collapse navbar-collapse" id="navbarSupportedContent">
                    <ul class="navbar-nav ms-auto mt-2 mt-lg-0">
                        {{-- <li class="nav-item"><a class="nav-link {{ Request::is('/') ? 'active' : '' }}"
                                aria-current="page" href="{{ url('/') }}">Home</a></li> --}}
                        {{-- <li class="nav-item"><a class="nav-link {{ Request::is('/about') ? 'active' : '' }}"
                                aria-current="page" href="{{ url('/about') }}">About</a></li>
                        <li class="nav-item"><a class="nav-link {{ Request::is('/blogs') ? 'active' : '' }}"
                                aria-current="page" href="{{ url('/blogs') }}">Blogs</a></li> --}}
                        <li class="nav-item mt-2 mt-lg-0"><a
                                class="nav-link btn btn-light text-black w-md-25 w-50 w-lg-100" aria-current="page"
                                href="{{ url('/login') }}">Log In</a></li>
                    </ul>
                </div>
            </div>
        </nav>


        @yield('content')
    </main>
    <!-- ===============================================-->
    <!--    End of Main Content-->
    <!-- ===============================================-->




    <!-- ============================================-->
    <!-- <section> begin ============================-->

    <!-- <section> close ============================-->
    <!-- ============================================-->




    <!-- ============================================-->
    <!-- <section> begin ============================-->
    <section class="pt-0">

        <div class="container">
            <div class="row justify-content-between">
                <div class="col-lg-6 col-sm-12">
                    <div class="navbar-brand" style="display: flex; align-items: center;">
                        <img src="../assets/front-page/assets/img/uika.png" alt="logo"
                            style="width: 50px; height: 50px; margin-right: 10px;" />
                        <h5 style="margin: 0;" class="fs-lg-4 fs-md-2 fs-1 text-dark">E-PORTAL UIKA</h5>
                    </div>
                    <p class="w-lg-75 text-gray">Portal UIKA adalah sumber utama untuk menjelajahi semua sistem
                        informasi dari Universitas Ibn Khaldun Bogor. Temukan pintu masuk yang menyediakan akses yang
                        mudah dan terpadu ke berbagai layanan universitas dalam satu platform.</p>
                </div>
                <div class="cold-md-6 col-sm-4">
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
        </div>
        <!-- end of .container-->

    </section>
    <!-- <section> close ============================-->
    <!-- ============================================-->




    <!-- ===============================================-->
    <!--    JavaScripts-->
    <!-- ===============================================-->
    <script src="../assets/front-page/vendors/popper/popper.min.js"></script>
    <script src="../assets/front-page/vendors/bootstrap/bootstrap.min.js"></script>
    <script src="../assets/front-page/vendors/anchorjs/anchor.min.js"></script>
    <script src="../assets/front-page/vendors/is/is.min.js"></script>
    <script src="../assets/front-page/vendors/fontawesome/all.min.js"></script>
    <script src="../assets/front-page/vendors/lodash/lodash.min.js"></script>
    <script src="https://polyfill.io/v3/polyfill.min.js?features=window.scroll"></script>
    <script src="../assets/front-page/vendors/prism/prism.js"></script>
    <script src="../assets/front-page/vendors/swiper/swiper-bundle.min.js"></script>
    <script src="../assets/front-page/assets/js/theme.js"></script>

</body>

</html>
