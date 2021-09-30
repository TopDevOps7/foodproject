@extends('dashboard.layouts.app')

@section('title')
Project
@endsection

@section('create_btn'){{route('dashboard_otherprojects.index')}}@endsection
@section('create_btn_btn') Back @endsection

@section('content')

<div class="card mb-4 wow fadeIn">
    <div class="card-body">

        <h4 class="mb-2 mb-sm-0 pt-1">
            Project Information
        </h4>
        <hr>
        <form class="ajaxForm orders" enctype="multipart/form-data" data-name="orders"
            action="{{route('dashboard_otherprojects.post_data')}}" method="post">
            {{csrf_field()}}
            <div class="modal-body">
                <input id="id" name="id" value="" type="hidden">
                <div class="form-group">
                    <label for="projectname">Project Name</label>
                    <input type="text" class="cls form-control" name="projectname" id="projectname"
                        placeholder="Project Name">
                </div>
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="numberofticket">Number of Tickets</label>
                            <input type="Number" class="cls form-control" name="numberofticket" id="numberofticket"
                                placeholder="Number of Tickets">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="priceofticker">Price of ticker</label>
                            <input type="Number" class="cls form-control" name="priceofticker" id="priceofticker"
                                placeholder="Price of ticker">
                        </div>
                    </div>
                    <div class="col-md-4" id="progress_">
                        <input type="range" name="progressval" id="progressval" min="1" max="100" step="1" value="0" />
                        <span id="pregressval">0%</span>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="numberofticket">Available</label>
                            <input type="number" class="cls form-control" name="available" id="available"
                                placeholder="Available">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="priceofticker">Sold</label>
                            <input type="number" class="cls form-control" name="sold" id="sold" placeholder="Sold">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group form-elements m-0">
                                        <div class="form-label"> Stauts</div>
                                    </div>
                                </div>
                                <div class="col-md-4 x-check-active">
                                    <div class="material-switch pull-left" style="transform: translateY(16px);">
                                        <input id="someSwitchOptionSuccess3" name="status" type="checkbox"
                                            onChange="statuschange()" />
                                        <label for="someSwitchOptionSuccess3" class="label-success"></label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-12" style="margin-top:10px">
                    <div class="card shadow">
                        <div class="card-header">
                            <h3 class="mb-0 card-title">Logo Upload</h3>
                        </div>
                        <div class="card-body">
                            <input type="file" class="dropify" id="picture" name="file" data-height="300" />
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <input type="hidden" name="button_action" id="button_action" value="insert">
                <a href="{{route('dashboard_otherprojects.index')}}" class="btn btn-default">
                    Close
                </a>
                <button type="submit" class="btn btn-primary btn-load">
                    Save Changes
                </button>
            </div>


        </form>
    </div>

</div>


@endsection

<style>
input[type=range] {
    -webkit-appearance: none;
    -moz-apperance: none;
    border-radius: 6px;
    height: 6px;

    background-image: -webkit-gradient(linear,
            left top,
            right top,
            color-stop(15%, #FF5500),
            color-stop(15%, #e9e2e1));

    background-image: -moz-linear-gradient(left center,
            #e9e2e1 0%, #e9e2e1 15%,
            #FF5500 15%, #FF5500 100%);
}

#pregressval {
    font-size: 17px;
    font-weight: 700;
    font-style: unset;
    font-family: 'themify';
    position: relative;
    top: -16px;
    left: -79px;
    width: 50px;
}

#progress_ {
    display: flex;
    justify-content: center;
    align-items: center;
}

input[type="range"]::-moz-range-track {
    border: none;
    background: none;
    outline: none;
}

input[type=range]:focus {
    outline: none;
    border: none;
}

input[type=range]::-webkit-slider-thumb {
    -webkit-appearance: none !important;
    background-color: #FF5500;
    height: 13px;
    width: 13px;
    border-radius: 50%;
}

input[type=range]::-moz-range-thumb {
    -moz-appearance: none !important;
    background-color: #FF5500;
    border: none;
    height: 13px;
    width: 13px;
    border-radius: 50%;
}
</style>

@section('js')

<script type="text/javascript">
$(document).ready(function() {
    $("input[type=range]").mousemove(function(e) {
        var val = ($(this).val() - $(this).attr('min')) / ($(this).attr('max') - $(this).attr('min'));
        var percent = val * 100;
        $("#pregressval").text(parseInt(percent) + "%");
        $(this).css('background-image',
            '-webkit-gradient(linear, left top, right top, ' +
            'color-stop(' + percent + '%, #FF5500), ' +
            'color-stop(' + percent + '%, #e9e2e1)' +
            ')');

        $(this).css('background-image',
            '-moz-linear-gradient(left center, #FF5500 0%, #FF5500 ' + percent + '%, #e9e2e1 ' +
            percent + '%, #e9e2e1 100%)');
    });


    "use strict";
    //Code here.

    var url = $(location).attr('href'),
        parts = url.split("/"),
        last_part = parts[parts.length - 1];

    var name_form = $('.ajaxForm').data('name');

    if (isNaN(last_part) == false) {
        if (last_part != null) {
            $('.title_info').html("Edit");
            Render_Data(last_part);
        }
    } else {
        $('.title_info').html("Close");
    }


});

function statuschange() {
    var checkedValue = $('#someSwitchOptionSuccess3:checked').val();
    let checkid = "";
    if (checkedValue == undefined) {
        checkedValue = "off";
    } else {
        checkedValue = "on";
    }
    console.log(checkedValue);
}
var Render_Data = function(id) {
    $.ajax({
        url: "{{route('dashboard_otherprojects.get_data_by_id')}}",
        method: "get",
        data: {
            "id": id,
        },
        dataType: "json",
        success: function(result) {
            if (result.success != null) {
                console.log(result.success);
                 $('#id').val(result.success.id);
                $('#projectname').val(result.success.name);
                $('#numberofticket').val(result.success.numberofticket);
                $('#priceofticker').val(result.success.priceofticker);
                $('#progressval').attr("value",`${result.success.progressval}`);
                $('#available').val(result.success.available);
                $('#sold').val(result.success.sold);
                if(result.success.status == "off")  $('#someSwitchOptionSuccess3').attr("checked",false);
                if(result.success.status == "on")  $('#someSwitchOptionSuccess3').attr("checked",true);
                var image = geturlphoto() + result.success.picture;

                        if (image != "") {
                            var editdrEvent_Img1 = $('#picture').dropify({
                                defaultFile: 'data:image/jpeg;base64,' + image
                            });
                            editdrEvent_Img1 = editdrEvent_Img1.data('dropify');
                            editdrEvent_Img1.resetPreview();
                            editdrEvent_Img1.clearElement();
                            editdrEvent_Img1.settings.defaultFile = 'data:image/jpeg;base64,' + image;
                            editdrEvent_Img1.destroy();
                            editdrEvent_Img1.init();

                            $('.dropify-render').attr("src", image);
                            $('.dropify-render img').attr("src", image);
                        }
            } else {
                toastr.error('لا يوحد بيانات', 'العمليات');
                window.location.href = "{{route('dashboard_otherprojects.index')}}";
            }
        }
    });
};
</script>


@endsection