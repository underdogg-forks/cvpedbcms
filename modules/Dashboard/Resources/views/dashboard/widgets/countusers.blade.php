<div class="col-lg-3 col-xs-6">
    <!-- small box -->
    <div class="small-box bg-yellow">
        <div class="inner">
            <h3>42</h3>

            <p>User Registrations</p>
        </div>
        <div class="icon">
            <i class="ion ion-person-add"></i>
        </div>

{{--        @if (Auth::check() && Auth::hasRole('admin-toto'))--}}
        <a href="{{ url('admin/users') }}" class="small-box-footer">Check users <i class="fa fa-arrow-circle-right"></i></a>
        {{--@endif--}}

    </div>
</div>