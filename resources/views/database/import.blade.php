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
                        <label for="image_path" class="base-input-label">@lang('Fichier')</label>
                    </div>
                    <div class="col-sm-9">
                        <div class="form-group form-inline col-sm-8">
                            <div class="input-group" style="margin-left: 0.7em;">
                                <input type="file" name="file" id="file" required>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-12">
                        <hr>
                    </div>
                    <div class="col-sm-3">
                        <label for="image_path" class="base-input-label">@lang('Table')</label>
                    </div>
                    <div class="col-sm-9">
                        <div class="form-group form-inline col-sm-8">
                            <label for="table" class="control-label thin-weight">{{ __('Select table') }}</label>
                            <select class="form-control" id="table" name="table" required>
                                <option value="">{{ __('Select table') }}</option>
                                @foreach($tables as $table)
                                    <option value="{{ $table->Tables_in_daybyday }}">{{ $table->Tables_in_daybyday }}</option>
                                @endforeach
                            </select>
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
    </div>

@stop