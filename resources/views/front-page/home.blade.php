@extends('front-page.layouts.app')

@section('content')
    <div class="bg-dark">
        <img class="img-fluid position-absolute end-0" src="../assets/front-page/assets/img/hero/hero-bg.png" alt="" />
    </div>

    <section>
        <div class="container overflow-hidden rounded-2">
            <div class="bg-holder" style="background-image:url(assets/img/promo/promo-bg.png);">
            </div>
            <!--/.bg-holder-->

            <div class="px-5 py-7 position-relative glass-effect mb-8 mt-8">
                <h1 class="text-center w-lg-75 mx-auto fs-lg-6 fs-md-4 fs-3 text-dark">Portal Sistem Informasi Universitas
                    Ibn Khaldun Bogor</h1>
                <hr />
                <div class="container mt-5">
                    <div class="row">
                        @if ($items->isEmpty())
                            <p>No items found.</p>
                        @else
                            @foreach ($items as $item)
                                <div class="col-lg-2 col-md-4 mb-4">
                                    <a href="{{ $item->link }}" target="_blank">
                                        <div class="card h-100 text-center card-front">
                                            <img class="card-img-top mx-auto mt-3" src="{{ $item->icon }}" alt="Icon"
                                                style="width: 80px; height: 80px;">
                                            <div class="mt-3">
                                                <h5 class="card-title text-dark">{{ $item->title }}</h5>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                            @endforeach
                        @endif
                    </div>
                </div>
            </div>
        </div>
        <!-- end of .container-->
    </section>
@endsection
