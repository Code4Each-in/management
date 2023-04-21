@extends('layout')
@section('title', '')
@section('subtitle', '')
@section('content')
<style>
    .declarationFormInputs{
        display: none;
    }
    .saveDeclarationBtn{
        display: none;
    }
</style>
<div class="col-lg-12" style="display: flex; justify-content: center;">
    <div class="card" style="width:600px;">
        <div class="card-header" style="color: #012970;">
            <h1>Self Declaration</h1>
        </div>
        <div class="card-body">
            <div class="box-header with-border" id="filter-box">
                @if(session()->has('message'))
                <div class="alert alert-success message mt-2">
                    {{ session()->get('message') }}
                </div>

                @endif
                <br>
                <form id="declarationForm">
                @csrf
                <div class=" modal-body">
                    <div class="alert alert-danger" style="display:none"></div>
                    <div class="adharcardmsg mb-3 text-center"><b>Please enter adharcard number first</b></div>
                    <div class="row mb-4">
                        <label for="adharcard_no" class="col-sm-3 col-form-label required">Adharcard no</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" name="adharcard_no" id="adharcard_no">
                        </div>
                    </div>
                    <div class="declarationFormInputs">
                    <div class="row mb-3 mt-4">
                        <label for="full_name" class="col-sm-3 col-form-label required">Full Name</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" name="full_name" id="full_name">
                        </div>
                    </div>

                    <div class="row mb-3">
                        <label for="father_name" class="col-sm-3 col-form-label required">Father Name </label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" name="father_name" id="father_name">
                        </div>
                    </div>
                    <div class="row mb-3">
                        <label for="address" class="col-sm-3 col-form-label required">Address</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" name="address" id="address">
                        </div>
                    </div>
                    <div class="row mb-3">
                        <label for="block" class="col-sm-3 col-form-label required">Block</label>
                        <div class="col-sm-6">
                            <input type="text" class="form-control" name="block" id="block">
                        </div>
                    </div>
                    <div class="row mb-3">
                        <label for="district" class="col-sm-3 col-form-label required">District</label>
                        <div class="col-sm-6">
                            <input type="text" class="form-control" name="district" id="district">
                        </div>
                    </div>
                    <div class="row mb-3">
                        <label for="state" class="col-sm-3 col-form-label required">State</label>
                        <div class="col-sm-6">
                            <input type="text" class="form-control" name="state" id="state">
                        </div>
                    </div>
                    <div class="row mb-4">
                        <label for="zip" class="col-sm-3 col-form-label required">Zip</label>
                        <div class="col-sm-4">
                            <input type="text" class="form-control" name="zip" id="zip">
                        </div>
                    </div>
                </div>
                </div>
                <div class="card-footer text-center">
                    <button type="button" class="btn btn-primary adharCheckBtn" onClick="checkAdharcard()" href="javascript:void(0)">Save</button>
                    <button type="button" class="btn btn-primary saveDeclarationBtn" onClick="addSelfDeclaration()" href="javascript:void(0)">Save</button>
                </div>
            </form>
            </div>
        </div>
    </div>
</div>
@endsection
@section('js_scripts')
<script>
$(document).ready(function() {
    // setTimeout(function() {
    //     $('.message').fadeOut("slow");
    // }, 2000);
    $('#role_table').DataTable({
        "order": []
        //"columnDefs": [ { "orderable": false, "targets": 7 }]
    });
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });
});

function checkAdharcard() {
    var adharCard = $('#adharcard_no').val();
    $('.alert-danger').html('');
    $.ajax({
        type: 'POST',
        url: "{{ url('/check/declaration')}}",
        data: {adharcard_no:adharCard},
        success: (data) => {
            if (data.errors) {
                $('.alert-danger').html('');
                $.each(data.errors, function(key, value) {
                    $('.alert-danger').show();
                    $('.alert-danger').append('<li>' + value + '</li>');
                })
            }else if (data.checkAdharcard =='exist') {
                    $('.alert-danger').show();
                    $('.alert-danger').append('Adharcard already Exist!');
            } else {
                $('.alert-danger').html('');
                $('.alert-danger').hide();
                $('.adharCheckBtn').hide();
                $('.adharcardmsg').hide();
                $('.declarationFormInputs').show();
                $('.saveDeclarationBtn').show();
            }
        },
        error: function(data) {
            console.log(data);
        }
    });
}
function addSelfDeclaration() {
    $.ajax({
        type: 'POST',
        url: "{{ url('/add/declaration')}}",
        data: $('#declarationForm').serialize(),
        success: (data) => {
            if (data.errors) {
                $('.alert-danger').html('');
                $.each(data.errors, function(key, value) {
                    $('.alert-danger').show();
                    $('.alert-danger').append('<li>' + value + '</li>');
                })
            } else {
                $('.alert-danger').html('');
                location.reload();
            }
        },
        error: function(data) {
            console.log(data);
        }
    });
}
</script>
@endsection