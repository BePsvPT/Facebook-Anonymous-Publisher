@extends('layouts.master')

@section('main')
  <div class="row">
    <div class="col-xs-12 col-md-offset-2 col-md-8">
      <header>
        <h1 class="text-center">{{ $pageName }}</h1>
      </header>

      <section>
        @include('components.form-errors')

        {!! Form::open(['route' => 'kobe', 'method' => 'POST', 'files' => true, 'role' => 'form', 'data-toggle' => 'validator']) !!}

        <div class="form-group">
          {!! Form::textarea('content', null, ['class' => 'form-control', 'placeholder' => '今天要靠北什麼？', 'maxlength' => 500, 'data-error' => '至少需要靠北點東西', 'required']) !!}
          <div class="help-block with-errors"></div>
        </div>

        <div class="form-group">
          {!! Form::label('image', '圖片（可選）') !!}
          {!! Form::file('image', ['accept' => 'image/*']) !!}
          <p class="help-block">大小需小於 3 MB</p>
        </div>

        <div class="form-group">
          {!! Recaptcha::render() !!}
        </div>

        <div class="form-group {{ empty($application['license']) ? 'hidden' : '' }}">
          <div class="checkbox">
            <label>
              {!! Form::checkbox('accept-license', true, empty($application['license']) ? true : false, ['data-error' => '您必須同意本站隱私條款', 'required']) !!}
              <span>我同意並已詳細閱讀使用條款及本站隱私權政策</span>
            </label>
          </div>
        </div>

        <div class="form-group">
          <button type="submit" class="btn btn-success btn-block">送出</button>
        </div>

        {!! Form::close() !!}
      </section>

      @unless(empty($application['ad']))
        <section>{!! $application['ad'] !!}</section>
      @endunless

      @unless(empty($application['ga']))
        <section>{!! $application['ga'] !!}</section>
      @endunless
    </div>
  </div>
@endsection
