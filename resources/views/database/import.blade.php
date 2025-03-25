@extends('layouts.master')
@section('heading')
{{ __('Import data from CSV')}}
@stop

@section('content')

    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <form action="{{ route('database.import') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="col-sm-12">
                        <hr>
                    </div>
                    <div class="col-sm-3">
                        <label for="file1" class="base-input-label">@lang('Fichier 1')</label>
                    </div>
                    <div class="col-sm-9">
                        <div class="form-group form-inline col-sm-8">
                            <div class="input-group" style="margin-left: 0.7em;">
                                <input type="file" name="file1" id="file1" >
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-12">
                        <hr>
                    </div>
                    <div class="col-sm-3">
                        <label for="file2" class="base-input-label">@lang('Fichier 2')</label>
                    </div>
                    <div class="col-sm-9">
                        <div class="form-group form-inline col-sm-8">
                            <div class="input-group" style="margin-left: 0.7em;">
                                <input type="file" name="file2" id="file2" >
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-12">
                        <hr>
                    </div>
                    <div class="col-sm-3">
                        <label for="file3" class="base-input-label">@lang('Fichier 3')</label>
                    </div>
                    <div class="col-sm-9">
                        <div class="form-group form-inline col-sm-8">
                            <div class="input-group" style="margin-left: 0.7em;">
                                <input type="file" name="file3" id="file3" >
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-12">
                        <hr>
                    </div>
                    <div class="col-sm-9">
                        <div class="form-group form-inline col-sm-8">
                            <input type="submit" class="btn btn-md btn-brand movedown" id="createTask" value="{{__('Importer le CSV')}}">
                        </div>
                    </div>
                </form>
            </div>
        </div>
        @if(session()->has('errorImport'))
        <div class="alert alert-danger">
            @foreach(session('errorImport') as $error)
            <p>{{ $error }}</p>
            @endforeach
        </div>

        @endif

        @if(session()->has('warningImport'))
        <div class="alert alert-warning">
            @foreach(session('warningImport') as $error)
            <p>{{ $error }}</p>
            @endforeach
        </div>

        @endif
    </div>

@stop