@extends('app')

@section('content')
    <x-pdf-extractor :products="$products" />
@endsection