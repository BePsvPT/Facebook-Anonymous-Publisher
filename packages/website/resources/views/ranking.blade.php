@extends('layouts.master')

@section('main')
  <section class="text-center">
    <h1>{{ [1 => '日', 7 => '週', 30 => '月'][$days] }}排行榜</h1>

    <h3>Top {{ $posts->currentPage() * 5 - 4 }} ~ {{ $posts->currentPage() * 5 }}</h3>
  </section>

  <section>
    <div class="row">
      @foreach($posts as $post)
        <div class="col-xs-12 col-md-offset-3 col-md-6" style="padding: 25px 15px;">
          <div class="fb-post" data-href="https://www.facebook.com/{{ $pageId }}/posts/{{ $post->getAttribute('fbid') }}"></div>
        </div>
      @endforeach
    </div>
  </section>

  {{ Html::pagination($posts) }}
@endsection

@push('scripts')
  <script src="https://connect.facebook.net/zh_TW/sdk.js#xfbml=1&amp;version=v2.2" defer></script>
@endpush
