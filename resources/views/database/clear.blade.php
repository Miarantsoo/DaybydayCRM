@extends('layouts.master')

@section('content')

    <div class="row">
        <div class="col-md-12">
            <h1>{{ __('Nettoyer votre base de données') }}</h1>
            <br>
            <a href="{{ route('database.clean') }}">
                <button type="submit" class="btn btn-danger">{{ __('Clear database') }}</button>
            </a>
        </div>
    </div>

@stop