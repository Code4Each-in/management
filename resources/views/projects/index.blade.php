<?php
use App\Models\Projects;?>

@extends('layout')
@section('title', 'Projects')
@section('subtitle', 'Projects')
@section('content')
<style>
    /* =========================================================
   PROJECT PAGE - RESPONSIVE ONLY
   No HTML / Blade changes required
   ========================================================= */


/* =========================================================
   TABLET / SMALL LAPTOP
   768px - 991px
   ========================================================= */

@media screen and (min-width: 768px) and (max-width: 991px) {

    /* Add Project button */
    .project {
        max-width: 180px;
        font-size: 14px;
    }

    /* Project section */
    .sprint-section {
        width: 100%;
    }

    /* Table */
    #filter-box {
        width: 100%;
        overflow-x: auto;
    }

    #filter-box .box-body {
        width: 100%;
        overflow-x: auto;
    }

    #filter-box .table-responsive {
        width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }

    #projects {
        min-width: 850px;
        width: 100%;
    }

    #projects th,
    #projects td {
        font-size: 13px;
        padding: 8px 10px;
        vertical-align: middle;
    }

    /* Status boxes */
    .status-group {
        gap: 3px;
        flex-wrap: nowrap;
    }

    .status-box {
        min-width: 25px;
        height: 25px;
        line-height: 25px;
        font-size: 11px;
        text-align: center;
    }

    /* Add Project modal */
    #addProjects .modal-dialog {
        max-width: 90%;
        width: auto;
        margin: 1.75rem auto;
    }

    #addProjects .modal-content {
        width: 100% !important;
    }

    /* Form */
    #addProjects .modal-body {
        padding: 20px;
    }

    #addProjects .form-control,
    #addProjects .form-select {
        width: 100%;
    }

    /* Quill */
    #toolbar-container {
        max-width: 100%;
        overflow-x: auto;
        white-space: nowrap;
    }

    #editor {
        width: 100%;
        height: 250px !important;
    }
}


/* =========================================================
   LARGE MOBILE
   576px - 767px
   ========================================================= */

@media screen and (min-width: 576px) and (max-width: 767px) {

    /* Add Project button */
    .project {
        width: auto;
        min-width: 130px;
        font-size: 13px;
        padding: 8px 14px;
    }

    /* Project section */
    .sprint-section {
        width: 100%;
    }

    /* Header */
    .sprint-header {
        width: 100%;
    }

    .section-left {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 6px;
    }

    .section-icon {
        flex: 0 0 auto;
    }

    .section-title {
        font-size: 14px;
    }

    /* Table scrolling */
    #filter-box {
        width: 100%;
        overflow: hidden;
    }

    #filter-box .box-body {
        width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }

    #filter-box .table-responsive {
        width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }

    #projects {
        min-width: 850px;
        width: 100%;
    }

    #projects th,
    #projects td {
        font-size: 12px;
        padding: 7px 8px;
        vertical-align: middle;
    }

    /* Project name */
    #projects td:first-child {
        max-width: 180px;
    }

    /* Assign images */
    #projects .actions-cell img {
        width: 20px !important;
        height: 20px !important;
    }

    /* Status */
    .status-group {
        display: flex !important;
        justify-content: center !important;
        align-items: center !important;
        gap: 3px;
        flex-wrap: nowrap;
    }

    .status-box {
        width: 24px;
        height: 24px;
        min-width: 24px;
        line-height: 24px;
        font-size: 11px;
        text-align: center;
    }

    /* Action icons */
    #projects .actions-cell i {
        font-size: 13px;
        margin: 0 2px;
    }

    /* =====================================================
       ADD PROJECT MODAL
       ===================================================== */

    #addProjects .modal-dialog {
        width: calc(100% - 30px);
        max-width: none;
        margin: 15px auto;
    }

    #addProjects .modal-content {
        width: 100% !important;
    }

    #addProjects .modal-header {
        padding: 12px 15px;
    }

    #addProjects .modal-title {
        font-size: 17px;
    }

    #addProjects .modal-body {
        padding: 15px;
    }

    /* Stack labels and inputs */
    #addProjects .modal-body .row.mb-3 {
        margin-bottom: 15px !important;
    }

    #addProjects .modal-body .col-sm-3,
    #addProjects .modal-body .col-sm-9 {
        width: 100%;
        max-width: 100%;
        flex: 0 0 100%;
    }

    #addProjects .modal-body .col-sm-3 {
        margin-bottom: 5px;
    }

    #addProjects .modal-body .col-form-label {
        font-size: 13px;
        padding-bottom: 3px;
    }

    #addProjects .form-control,
    #addProjects .form-select {
        width: 100%;
        font-size: 13px;
    }

    /* Quill */
    #toolbar-container {
        width: 100%;
        overflow-x: auto;
        white-space: nowrap;
        -webkit-overflow-scrolling: touch;
    }

    #editor {
        width: 100%;
        height: 220px !important;
    }

    /* Modal footer */
    #addProjects .modal-footer {
        display: flex;
        justify-content: flex-end;
        gap: 8px;
        padding: 12px 15px;
    }

    /* Assignment modal */
    #ShowAssign .modal-dialog {
        width: calc(100% - 30px);
        max-width: none;
        margin: 15px auto;
    }

    #ShowAssign .modal-content {
        width: 100%;
    }
}


/* =========================================================
   MOBILE
   401px - 575px
   ========================================================= */

@media screen and (min-width: 401px) and (max-width: 575px) {

    /* =====================================================
       ADD PROJECT BUTTON
       ===================================================== */

    .project {
        width: auto !important;
        min-width: 120px;
        max-width: 160px;
        font-size: 12px !important;
        padding: 8px 12px !important;
        margin-top: 10px !important;
        margin-bottom: 10px !important;
    }


    /* =====================================================
       SECTION HEADER
       ===================================================== */

    .sprint-section {
        width: 100%;
    }

    .sprint-header {
        width: 100%;
        padding: 8px 10px;
    }

    .section-left {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 5px;
    }

    .section-icon {
        width: 26px;
        height: 26px;
        line-height: 26px;
        min-width: 26px;
        text-align: center;
        font-size: 13px;
    }

    .section-title {
        font-size: 13px;
    }


    /* =====================================================
       PROJECT TABLE
       ===================================================== */

    #filter-box {
        width: 100%;
        overflow: hidden;
    }

    #filter-box .box-body {
        width: 100%;
        padding: 8px;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }

    #filter-box .table-responsive {
        width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }

    #projects {
        min-width: 820px !important;
        width: 100% !important;
        margin-bottom: 0;
    }

    #projects th,
    #projects td {
        font-size: 11px !important;
        padding: 7px 8px !important;
        vertical-align: middle;
        white-space: normal;
    }

    #projects th {
        font-size: 10.5px !important;
        white-space: nowrap;
    }

    /* Project name */
    #projects td:first-child {
        max-width: 160px;
        word-break: break-word;
    }

    /* Client name */
    #projects td:nth-child(3) {
        max-width: 150px;
        word-break: break-word;
    }

    /* Profile pictures */
    #projects .actions-cell img {
        width: 20px !important;
        height: 20px !important;
    }


    /* =====================================================
       STATUS BOXES
       ===================================================== */

    .status-group {
        display: flex !important;
        justify-content: center !important;
        align-items: center !important;
        gap: 3px !important;
        flex-wrap: nowrap !important;
    }

    .status-box {
        width: 23px !important;
        min-width: 23px !important;
        height: 23px !important;
        line-height: 23px !important;
        font-size: 10px !important;
        text-align: center;
    }


    /* =====================================================
       ACTION ICONS
       ===================================================== */

    #projects .actions-cell {
        white-space: nowrap;
    }

    #projects .actions-cell i {
        font-size: 13px !important;
        margin: 0 2px;
    }


    /* =====================================================
       ADD PROJECT MODAL
       ===================================================== */

    #addProjects .modal-dialog {
        width: calc(100% - 20px) !important;
        max-width: none !important;
        margin: 10px auto !important;
    }

    #addProjects .modal-content {
        width: 100% !important;
        max-width: 100% !important;
    }

    #addProjects .modal-header {
        padding: 10px 12px;
    }

    #addProjects .modal-title {
        font-size: 16px;
    }

    #addProjects .modal-body {
        padding: 12px;
    }

    /* Make Bootstrap form rows vertical */
    #addProjects .modal-body .row.mb-3 {
        display: block;
        margin-left: 0;
        margin-right: 0;
        margin-bottom: 12px !important;
    }

    #addProjects .modal-body .col-sm-3,
    #addProjects .modal-body .col-sm-9 {
        width: 100% !important;
        max-width: 100% !important;
        flex: none !important;
        padding-left: 0;
        padding-right: 0;
    }

    #addProjects .modal-body .col-sm-3 {
        margin-bottom: 5px;
    }

    #addProjects .modal-body .col-form-label {
        display: block;
        width: 100%;
        font-size: 12px;
        padding-top: 0;
        padding-bottom: 3px;
    }

    #addProjects .form-control,
    #addProjects .form-select {
        width: 100% !important;
        max-width: 100%;
        font-size: 12px;
    }

    #addProjects textarea {
        min-height: 80px;
    }


    /* =====================================================
       QUILL EDITOR
       ===================================================== */

    #toolbar-container {
        width: 100%;
        max-width: 100%;
        overflow-x: auto !important;
        white-space: nowrap;
        -webkit-overflow-scrolling: touch;
    }

    #toolbar-container .ql-formats {
        display: inline-block;
        margin-right: 5px;
    }

    #editor {
        width: 100% !important;
        height: 200px !important;
        max-width: 100%;
    }


    /* =====================================================
       MODAL FOOTER
       ===================================================== */

    #addProjects .modal-footer {
        padding: 10px 12px;
        display: flex;
        justify-content: flex-end;
        gap: 6px;
    }

    #addProjects .modal-footer .btn {
        font-size: 12px;
        padding: 7px 12px;
    }


    /* =====================================================
       ASSIGN MODAL
       ===================================================== */

    #ShowAssign .modal-dialog {
        width: calc(100% - 20px) !important;
        max-width: none !important;
        margin: 10px auto !important;
    }

    #ShowAssign .modal-content {
        width: 100% !important;
    }

    #ShowAssign .modal-title {
        font-size: 16px;
    }

    #ShowAssign .modal-body {
        padding: 12px;
    }
}


/* =========================================================
   SMALL MOBILE
   400px AND BELOW
   ========================================================= */

@media screen and (max-width: 400px) {

    /* =====================================================
       ADD PROJECT BUTTON
       ===================================================== */

    .project {
        width: auto !important;
        min-width: 110px;
        max-width: 140px;
        font-size: 11px !important;
        padding: 7px 10px !important;
        margin-top: 8px !important;
        margin-bottom: 8px !important;
    }


    /* =====================================================
       SECTION HEADER
       ===================================================== */

    .sprint-section {
        width: 100%;
    }

    .sprint-header {
        width: 100%;
        padding: 7px 8px;
    }

    .section-left {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 4px;
    }

    .section-icon {
        width: 24px;
        height: 24px;
        min-width: 24px;
        line-height: 24px;
        font-size: 12px;
    }

    .section-title {
        font-size: 12px;
    }


    /* =====================================================
       TABLE
       ===================================================== */

    #filter-box {
        width: 100%;
        overflow: hidden;
    }

    #filter-box .box-body {
        width: 100%;
        padding: 6px;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }

    #filter-box .table-responsive {
        width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }

    #projects {
        min-width: 780px !important;
        width: 100% !important;
    }

    #projects th,
    #projects td {
        font-size: 10px !important;
        padding: 6px 7px !important;
        vertical-align: middle;
    }

    #projects th {
        font-size: 9.5px !important;
        white-space: nowrap;
    }

    #projects td:first-child {
        max-width: 140px;
        word-break: break-word;
    }

    #projects td:nth-child(3) {
        max-width: 130px;
        word-break: break-word;
    }


    /* =====================================================
       PROFILE PICTURES
       ===================================================== */

    #projects .actions-cell img {
        width: 18px !important;
        height: 18px !important;
    }


    /* =====================================================
       STATUS BOXES
       ===================================================== */

    .status-group {
        display: flex !important;
        justify-content: center !important;
        align-items: center !important;
        gap: 2px !important;
        flex-wrap: nowrap !important;
    }

    .status-box {
        width: 21px !important;
        min-width: 21px !important;
        height: 21px !important;
        line-height: 21px !important;
        font-size: 9px !important;
    }


    /* =====================================================
       ACTION ICONS
       ===================================================== */

    #projects .actions-cell {
        white-space: nowrap;
    }

    #projects .actions-cell i {
        font-size: 12px !important;
        margin: 0 1px;
    }


    /* =====================================================
       ADD PROJECT MODAL
       ===================================================== */

    #addProjects .modal-dialog {
        width: calc(100% - 12px) !important;
        max-width: none !important;
        margin: 6px auto !important;
    }

    #addProjects .modal-content {
        width: 100% !important;
        max-width: 100% !important;
        border-radius: 6px;
    }

    #addProjects .modal-header {
        padding: 9px 10px;
    }

    #addProjects .modal-title {
        font-size: 15px;
    }

    #addProjects .modal-body {
        padding: 10px;
    }


    /* =====================================================
       FORM
       ===================================================== */

    #addProjects .modal-body .row.mb-3 {
        display: block;
        margin-left: 0;
        margin-right: 0;
        margin-bottom: 10px !important;
    }

    #addProjects .modal-body .col-sm-3,
    #addProjects .modal-body .col-sm-9 {
        width: 100% !important;
        max-width: 100% !important;
        flex: none !important;
        padding-left: 0;
        padding-right: 0;
    }

    #addProjects .modal-body .col-sm-3 {
        margin-bottom: 4px;
    }

    #addProjects .modal-body .col-form-label {
        font-size: 11px;
        padding: 0;
    }

    #addProjects .form-control,
    #addProjects .form-select {
        width: 100% !important;
        max-width: 100%;
        font-size: 11px;
        min-height: 34px;
    }


    /* =====================================================
       QUILL
       ===================================================== */

    #toolbar-container {
        width: 100%;
        overflow-x: auto !important;
        white-space: nowrap;
        -webkit-overflow-scrolling: touch;
    }

    #toolbar-container .ql-formats {
        margin-right: 3px;
    }

    #editor {
        width: 100% !important;
        height: 180px !important;
    }


    /* =====================================================
       FOOTER
       ===================================================== */

    #addProjects .modal-footer {
        padding: 8px 10px;
        display: flex;
        justify-content: flex-end;
        gap: 5px;
    }

    #addProjects .modal-footer .btn {
        font-size: 11px;
        padding: 6px 10px;
    }


    /* =====================================================
       ASSIGN MODAL
       ===================================================== */

    #ShowAssign .modal-dialog {
        width: calc(100% - 12px) !important;
        max-width: none !important;
        margin: 6px auto !important;
    }

    #ShowAssign .modal-content {
        width: 100% !important;
    }

    #ShowAssign .modal-title {
        font-size: 15px;
    }

    #ShowAssign .modal-body {
        padding: 10px;
    }
}


/* =========================================================
   VERY SMALL PHONES
   360px AND BELOW
   ========================================================= */

@media screen and (max-width: 360px) {

    .project {
        min-width: 105px;
        font-size: 10px !important;
        padding: 6px 9px !important;
    }

    .section-title {
        font-size: 11px;
    }

    #projects {
        min-width: 750px !important;
    }

    #projects th,
    #projects td {
        font-size: 9.5px !important;
        padding: 5px 6px !important;
    }

    .status-box {
        width: 20px !important;
        min-width: 20px !important;
        height: 20px !important;
        line-height: 20px !important;
        font-size: 8px !important;
    }

    #addProjects .modal-dialog {
        width: calc(100% - 8px) !important;
        margin: 4px auto !important;
    }

    #addProjects .modal-body {
        padding: 8px;
    }

    #addProjects .form-control,
    #addProjects .form-select {
        font-size: 10px;
    }

    #editor {
        height: 160px !important;
    }
}
</style>
<div class="row">
    <button class="btn btn-primary mt-3 project mb-3" onClick="openprojectModal()" href="javascript:void(0)" style="background-color: #4154f1;">Add
        Project</button>
</div>
<div class="row">
        <div class="sprint-section">

                <!-- filter -->
                <div class="sprint-header production">
                    <div class="section-left">
                      <div class="section-icon bg-production" style="background-color: #297bab;">P</div>
                      <div class="section-title" style="color: #297bab;">Total Projects</div>
                      <div class="section-title">• {{ $projectCount ?? 0 }} Projects</div>
                    </div>
                  </div>
                <div class="box-header with-border" id="filter-box">
                    <div class="box-body">
                        <div class="table-responsive">
                    <table class="styled-sprint-table sprint-table" id="projects" style="text-align: center;">
                            <thead>
                                <tr>
                                    <th>Project Name</th>
                                    <th>Assign</th>
                                    <th>Client Name</th>
                                    <th>Start Date</th>
                                    @if(auth()->user()->role_id != 6)
                                    <th>End Date</th>
                                    @endif
                                    <th>Status</th>
                                    <th>Active|Inactive|Completed</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($projects as $data)
                                <tr class="pointer" onclick="if (!event.target.closest('.actions-cell')) window.open('{{ url('/project/'.$data->id) }}', '_blank');">
                                    <td>{{($data->project_name )}}</td>
                                    <td class="actions-cell"> @if (count($data->projectassign)<= 5) @foreach ($data->projectassign as $assign)
                                            @if (!empty($assign->profile_picture))
                                            <img src="{{asset('assets/img/').'/'.$assign->profile_picture}}" width="20" height="20" class="rounded-circle " alt="">
                                            @else <img src="assets/img/blankImage" alt="Profile" width="20" height="20" class="rounded-circle">
                                            @endif
                                            @endforeach
                                            @endif

                                            @if(count($data->projectassign)!=0)
                                            <a class="text-primary small pt-1 pointer text-right" onClick="ShowAssignModal('{{$data->id}}')" id="view"><i class="bi-person-lines-fill"></i>
                                            </a>
                                            @else
                                            <span>NA</span>
                                            @endif
                                    </td>
                                    <!-- <td>{{ $data->client_name}}</td> -->
                                    <td>
                                        {{
                                            $data->clients->isNotEmpty()
                                                ? $data->clients->pluck('name')->join(', ')
                                                : ($data->client->name ?? '---')
                                        }}
                                    </td>

                                    <td>{{ $data->start_date}}</td>
                                    @if(auth()->user()->role_id != 6)
                                    <td>{{ $data->end_date ?? '---'}}</td>
                                    @endif
                                    <td>
                                    @if($data->status == 'not_started')
                                    <span class="badge rounded-pill bg-primary">Not Started</span>
                                    @elseif($data->status == 'active')
                                    <span class="badge rounded-pill bg-info ">Active</span>
                                    @elseif($data->status == 'deactivated')
                                    <span class="badge rounded-pill bg-danger text-mute">Deactivated</span>
                                    @else
                                    <span class="badge rounded-pill  bg-success">Completed</span>
                                    @endif
                                    <!-- <p class="small mt-1" style="font-size: 11px;font-weight:600; margin-left:6px;">  By: {{ $projectstatusData->first_name ?? '' }} </p> -->
                                    </td>
                                   <td style="text-align: center;">
                                        <div class="d-flex justify-content-center status-group">
                                            <div class="status-box text-white" title="Active" style="background-color: #2a9d8f;">
                                                {{ $data->active_sprints }}
                                            </div>
                                            <div class="status-box text-white" title="Inactive" style="background-color: #e76f51;">
                                                {{ $data->inactive_sprints }}
                                            </div>
                                            <div class="status-box text-white" title="Completed" style="background-color: #264653;">
                                                {{ $data->completed_sprints }}
                                            </div>
                                        </div>
                                    </td>
                                    <td class="actions-cell">
                                         <a href="{{ url('/project/'.$data->id)}}">
                                            <i class="fa fa-eye fa-fw pointer"></i>
                                        </a>
                                        <a href="{{ url('/edit/project/'.$data->id)}}">
                                        <i style="color:#4154f1;" href="javascript:void(0)" class="fa fa-edit fa-fw pointer"> </i>
                                        </a>
                                        @if (auth()->user()->role['name'] == 'Super Admin')
                                        <i style="color:#4154f1;" onClick="deleteProjects('{{ $data->id }}')" href="javascript:void(0)" class="fa fa-trash fa-fw pointer"></i>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                @endforelse
                                @if($projects->isEmpty())
                                <tr>
                                    <td colspan="{{ auth()->user()->role['name'] == 'Super Admin' || auth()->user()->role['name'] == 'HR Manager' ? 9 : 8 }}" class="text-center">
                                        No records to show
                                    </td>
                                </tr>
                                @endif
                        </table>
                        </div>

                    </div>
                </div>
                <div>
                </div>
        </div>
</div>
        <!----Add Projects--->
        <div class="modal fade" id="addProjects" tabindex="-1" aria-labelledby="role" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content" style="width: 630px;">
                    <div class="modal-header">
                        <h5 class="modal-title" id="role">Add Project</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form id="addProjectsForm" enctype="multipart/form-data" >
                        @csrf
                        <div class="modal-body">
                            <div class="alert alert-danger" style="display:none"></div>
                            <div class="row mb-3">
                                <label for="title" class="col-sm-3 col-form-label required">Project Name</label>
                                <div class="col-sm-9">
                                    <input type="text" class="form-control" name="project_name" id="project_name">
                                </div>
                            </div>

                            @if(auth()->user()->role_id != 6)
                            <div class="row mb-3">
                                <label for="client_id" class="col-sm-3 col-form-label required">Client Name</label>
                                <div class="col-sm-9">
                                    <select name="client_id[]" class="form-select form-control" id="client_id" multiple>
                                        <!-- <option value="" disabled selected>Select Clients</option> -->
                                        @foreach ($clients as $client)
                                            <option value="{{ $client->id }}">{{ $client->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            @endif

                            @if(auth()->user()->role_id != 6)
                            <div class="row mb-3">
                                <label for="" class="col-sm-3 col-form-label required">Assign To</label>
                                <div class="col-sm-9">
                                <select class="form-select form-control" id="user" name="assign_to[]" data-placeholder="Select User" multiple>
                                <option value="" disabled>Select User</option>
                                        @foreach ($users ?? '' as $data)
                                            <option value="{{$data->id}}">
                                                {{$data->first_name}} - {{$data->designation}}
                                            </option>
                                        @endforeach
                                </select>
                                </div>
                            </div>
                            @endif

                            <div class="row mb-3">
                                <label for="title" class="col-sm-3 col-form-label ">Live Url</label>
                                <div class="col-sm-9">
                                    <input type="text" class="form-control" name="live_url" id="live_url">
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label for="title" class="col-sm-3 col-form-label ">Dev Url</label>
                                <div class="col-sm-9">
                                    <input type="text" class="form-control" name="dev_url" id="dev_url">
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label for="title" class="col-sm-3 col-form-label ">Git Repository</label>
                                <div class="col-sm-9">
                                    <input type="text" class="form-control" name="git_repo" id="git_repo">
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label for="title" class="col-sm-3 col-form-label">Tech Stacks</label>
                                <div class="col-sm-9">
                                    <input type="text" class="form-control" name="tech_stacks" id="tech_stacks" data-role="taginput">
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label for="tinymce_textarea" class="col-sm-3 col-form-label">Description</label>
                                <div class="col-sm-9">
                                    <!-- Quill Toolbar -->
                                    <div id="toolbar-container">
                                        <span class="ql-formats">
                                            <select class="ql-font"></select>
                                            <select class="ql-size"></select>
                                        </span>
                                        <span class="ql-formats">
                                            <button class="ql-bold"></button>
                                            <button class="ql-italic"></button>
                                            <button class="ql-underline"></button>
                                            <button class="ql-strike"></button>
                                        </span>
                                        <span class="ql-formats">
                                            <select class="ql-color"></select>
                                            <select class="ql-background"></select>
                                        </span>
                                        <span class="ql-formats">
                                            <button class="ql-script" value="sub"></button>
                                            <button class="ql-script" value="super"></button>
                                        </span>
                                        <span class="ql-formats">
                                            <button class="ql-header" value="1"></button>
                                            <button class="ql-header" value="2"></button>
                                            <button class="ql-blockquote"></button>
                                            <button class="ql-code-block"></button>
                                        </span>
                                        <span class="ql-formats">
                                            <button class="ql-list" value="ordered"></button>
                                            <button class="ql-list" value="bullet"></button>
                                            <button class="ql-indent" value="-1"></button>
                                            <button class="ql-indent" value="+1"></button>
                                        </span>
                                        <span class="ql-formats">
                                            <button class="ql-direction" value="rtl"></button>
                                            <select class="ql-align"></select>
                                        </span>
                                        <span class="ql-formats">
                                            <button class="ql-link"></button>
                                            <button class="ql-image"></button>
                                            <button class="ql-video"></button>
                                            <button class="ql-formula"></button>
                                        </span>
                                        <span class="ql-formats">
                                            <button class="ql-clean"></button>
                                        </span>
                                    </div>

                                    <div id="editor" style="height: 300px;">{!! old('description') !!}</div>
                                    <input type="hidden" name="description" id="description_input">

                                    @if ($errors->has('description'))
                                        <span style="font-size: 12px;" class="text-danger">{{ $errors->first('description') }}</span>
                                    @endif
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label for="start_date" class="col-sm-3 col-form-label required">Start Date</label>
                                <div class="col-sm-9">
                                    <input type="date" class="form-control" id="start_date" name="start_date">
                                </div>
                            </div>

                            <div class="row mb-3">
                                <label for="end_date" class="col-sm-3 col-form-label">End Date</label>
                                <div class="col-sm-9">
                                    <input type="date" class="form-control" id="end_date" name="end_date">
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label for="tinymce_textarea" class="col-sm-3 col-form-label">Credentials</label>
                                <div class="col-sm-9">
                                    <textarea name="credentials" class="form-control"></textarea>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label for="status" class="col-sm-3 col-form-label required">Status</label>
                                <div class="col-sm-9">
                                    <select name="status" class="form-select" id="status" name="status">
                                        <option value="" disabled selected>Select Status</option>
                                        <option value="not_started">Not Started</option>
                                        <option value="active">Active</option>
                                        <option value="deactivated">Deactivated</option>
                                        <option value="completed">Completed</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label for="document" class="col-sm-3 col-form-label ">Document</label>
                                <div class="col-sm-9">
                                    <input type="file" class="form-control" name="add_document[]" id="add_document" multiple />
                                </div>
                            </div>

                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                <button type="submit" class="btn btn-primary" href="javascript:void(0)">Save</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <!---end Add modal-->

        <div class="modal fade" id="ShowAssign" tabindex="-1" aria-labelledby="ShowAssign" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Project Assign To</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row projectAsssign">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class=" btn
                            btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
        <!---end Add modal-->

        @endsection
        @section('js_scripts')
        <script>
            $(document).ready(function() {
                setTimeout(function() {
                    $('.message').fadeOut("slow");
                }, 2000);

                $("#addProjectsForm").submit(function(event) {
                    event.preventDefault();
                    $('#description_input').val(quill.root.innerHTML);
                    var formData = new FormData(this);
                    // var totalfiles = document.getElementById('add_document').files.length;

                    // for (var index = 0; index < totalfiles; index++) {
                    //     formData.append("add_document[]" + index, document.getElementById('add_document')
                    //         .files[
                    //             index]);
                    // }
                    // console.log(formData);

                    $.ajaxSetup({
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    }
                });

                    $.ajax({
                        type: 'POST',
                        url: "{{ url('/add/projects')}}",
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: (data) => {
                            if (data.errors) {
                                $('.alert-danger').html('');
                                $.each(data.errors, function(key, value) {
                                    $('.alert-danger').show();
                                    $('.alert-danger').append('<li>' + value + '</li>');
                                })

                            } else {
                                $("#addProjects").modal('hide');
                                location.reload();
                            }
                        },
                        error: function(data) {}
                    });
                });

                $('#client_id').select2({
                    dropdownParent: $('#addProjects'),
                });

                $( '#user' ).select2( {
                    dropdownParent: $('#addProjects')
                } );
            });

            $('#addProjects').on('hidden.bs.modal', function () {
                $('.alert-danger').html('').hide();
                $('#addProjectsForm')[0].reset();
            });

            function ShowAssignModal(id) {
                $('.projectAsssign').html('');
                $('#ShowAssign').modal('show');
                $.ajax({
                    type: 'POST',
                    url: "{{ url('/project/assign')}}",
                    data: {
                        id: id
                    },
                    cache: false,
                    success: (data) => {
                        if (data.projectAssigns.length > 0) {
                            var html = '';
                            $.each(data.projectAssigns, function(key, assign) {
                                var picture = 'blankImage';
                                if (assign.profile_picture != "") {
                                    picture = assign.profile_picture;
                                }
                                html +=
                                    '<div class="row leaveUserContainer mt-2 "> <div class="col-md-2"><img src="{{asset("assets/img")}}/' +
                                    picture +
                                    '"" width="50" height="50" alt="" class="rounded-circle"></div><div class="col-md-10 "><p><b>' +
                                    assign.first_name + '</b></p></div></div>';
                            })
                            $('.projectAsssign').html(html);
                        } else {
                            $('.projectAsssign').html('<span>No record found <span>');
                        }
                    },
                    error: function(data) {}
                });

            }

                $.ajaxSetup({
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        }
                    });
            function openprojectModal() {
                document.getElementById("addProjectsForm").reset();
                $('#addProjects').modal('show');
            }

            function editTickets(id) {
                $('#editProjects').modal('show');
                $('#ticket_id').val(id);
                $.ajax({
                    type: "POST",
                    url: "{{ url('/edit/project') }}",
                    data: {
                        id: id
                    },
                    success: function(res) {
                        if (res.projects != null) {
                            $('#edit_title').val(res.projects.title);
                            $('#edit_description').val(res.projects.description);
                            $('#edit_status').val(res.projects.status);
                            $('#edit_comment').val(res.projects.comment);

                            $('#edit_priority').val(res.projects.priority);
                            // var test = "http://127.0.0.1:8000/public/assets/img/" + res.projects.profile_picture;
                            // $("#profile").html(
                            //     '<img src="{{asset("assets/img")}}/' + res.projects.profile_picture +
                            //     '" width = "100" class = "img-fluid img-thumbnail" > '
                            // );

                        }
                        if (res.ticketAssign != null) {
                            $.each(res.ticketAssign, function(key, value) {
                                $('#edit_assign option[value="' + value.user_id + '"]')
                                    .attr(
                                        'selected', 'selected');
                            })
                        }
                    }
                });
            }

            function deleteProjects(id) {
    if (confirm("Are you sure?")) {
        $.ajax({
            type: "DELETE",
            url: "{{ url('/delete/projects') }}",
            data: {
                id: id,
                _token: "{{ csrf_token() }}"
            },
            dataType: 'json',
            success: function(res) {
                if (res.status === 200) {
                    location.reload();
                } else {
                    alert('Error: ' + res.message);
                }
            },
            error: function(xhr) {
                alert('Something went wrong.');
                console.log(xhr.responseText);
            }
        });
    }
}


            $('.readMoreLink').click(function(event) {
                event.preventDefault();

                var description = $(this).siblings('.description');
                var fullDescription = $(this).siblings('.fullDescription');

                description.text(fullDescription.text());
                $(this).hide();
                $(this).siblings('.readLessLink').show();
            });

            $('.readLessLink').click(function(event) {
                event.preventDefault();

                var description = $(this).siblings('.description');
                var fullDescription = $(this).siblings('.fullDescription');

                var truncatedDescription = fullDescription.text().substring(0, 100) + '...';
                description.text(truncatedDescription);
                $(this).hide();
                $(this).siblings('.readMoreLink').show();
            });

            //TAGS KEY JS
            $('#tech_stacks').tagsinput({
            confirmKeys: [13, 188]
            });

            $('#tech_stacks').on('keypress', function(e){
            if (e.keyCode == 13){
                e.preventDefault();
            };
            });

        </script>
    <!-- <script src="{{ asset('assets/js/bootstrap-tags.js') }}"></script> -->
@endsection
