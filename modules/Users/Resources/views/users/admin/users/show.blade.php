@extends('adminlte::layouts.modal')

@section('title')
    {{ $user->full_name }}
@endsection

@section('content')
    <div class="box-body no-padding">
        <table class="table table-bordered">
            <tbody>
            <tr class="cell-center">
                <th>
                    <b>ID</b>
                </th>
                <td>
                    {{ $user->id }}
                </td>
            </tr>
            <tr class="cell-center">
                <th>
                    <b>Last name</b>
                </th>
                <td>
                    {{ $user->last_name }}
                </td>
            </tr>
            <tr class="cell-center">
                <th>
                    <b>First name</b>
                </th>
                <td>
                    {{ $user->first_name }}
                </td>
            </tr>
            <tr class="cell-center">
                <th>
                    <b>Email</b>
                </th>
                <td>
                    {{ $user->email }}
                </td>
            </tr>
            {{--<tr>--}}
                {{--<td>--}}
                    {{--<b>API key</b>--}}
                {{--</td>--}}
                {{--<td>--}}
                    {{--{{ !is_null($user->apikey) ? $user->apikey->key : trans('users::admin.show.message.no_apikey') }}--}}
                    {{--<div class="pull-right">--}}
                        {{--<button class="btn btn-flat btn-xs"><i class="fa fa-eye"></i> show</button>--}}
                        {{--<button class="btn btn-flat btn-xs"><i class="fa fa-refresh"></i> regenerate</button>--}}
                    {{--</div>--}}
                {{--</td>--}}
            {{--</tr>--}}
            </tbody>
        </table>

        @if ($user->addresses->count())
            <br>
            <table class="table table-bordered">
                <tbody>
                <tr class="cell-center">
                    <th colspan="2">
                        <b>Addresses</b>
                    </th>
                </tr>
                @foreach ($user->addresses as $addresse)
                    <tr class="cell-center">
                        <td class="cell-center" width="15%">
                            @if ($addresse->is_primary)
                                <i class="fa fa-home"></i><br/>Primary<br/>address
                            @elseif ($addresse->is_billing)
                                <i class="fa fa-credit-card"></i><br/>Billing<br/>address
                            @elseif ($addresse->is_shipping)
                                <i class="fa fa-truck"></i><br/>Shipping<br/>address
                            @endif
                        </td>
                        <td>
                            {!! trans($addresse->organization) !!}<br/>
                            {!! trans($addresse->street) !!}
                            {!! trans($addresse->street_extra) !!}<br/>
                            {!! trans($addresse->city) !!}
                            {{--{!! trans($addresse->state_a2) !!}--}}
                            {!! trans($addresse->state_name) !!}
                            {!! trans($addresse->zip) !!}<br/>
                            {{--{!! trans($addresse->country_a2) !!}--}}
                            {!! trans($addresse->country_name) !!}
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endif

        @if ($user->roles->count())
            <br>
            <table class="table table-bordered">
                <tbody>
                <tr class="cell-center">
                    <th>
                        <b>Roles</b>
                    </th>
                </tr>
                @foreach ($user->roles as $role)
                    <tr class="cell-center">
                        <td>
                            {!! trans($role->display_name) !!}
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endif

    </div>
@endsection

@section('footer')
    <a href="{{ url('admin/users/impersonate/' . $user->id) }}" class="btn btn-default pull-left">
        <i class="fa fa-user-secret"></i> {{ trans('users::admin.show.btn.impersonate') }}
    </a>
    <button type="button" class="btn btn-default pull-right" data-dismiss="modal">
        {{ trans('global.close') }}
    </button>
@endsection
