<!-- Left side column. contains the logo and sidebar -->
<aside class="main-sidebar">

    <!-- sidebar: style can be found in sidebar.less -->
    <section class="sidebar">

        <!-- Sidebar user panel (optional) -->
        <div class="user-panel">
            <div class="pull-left image">
                </br>
                </br>
            </div>
            <div class="pull-left info">
{{--                <p>{{Auth::user()->name}}</p>--}}
                <!-- Status -->
                <a href="#"><i class="fa fa-circle text-success"></i> Online</a>
            </div>

        </div>


        <!-- Sidebar Menu -->
        <ul class="sidebar-menu">
            <li class="header"></li>
            <!-- Optionally, you can add icons to the links -->
            @can('search_ticket')
                <li class="{{ ( Request::is('ticket/search') ? 'active' : '' ) }}"><a  href="/ticket/search"><i class="fa fa-search"></i><span class="hidden-tablet"> Ticket Search</span></a ></li>
            @endcan
            @can('view_chronics')
                <li class="{{ ( Request::is('ticket/chronicTickets') ? 'active' : '' ) }}"><a href="/ticket/chronicTickets"><i class="fa fa-warning"></i><span class="hidden-tablet"> Chronic Tickets</span></a></li>
            @endcan
            @can('view_tech_online')
                <li class="{{ ( Request::is('techsOnline') ? 'active' : '' ) }}"><a href="/techsOnline"><i class="fa fa-users"></i><span class="hidden-tablet"> Techs Online</span></a></li>
            @endcan
            <li class="treeview
                    {{ ( Request::is('address/search') ? 'active' : '' ) }} ||
                    {{ ( Request::is('countrycode/show') ? 'active' : '' ) }} ||
                    {{ ( Request::is('ticket/clearFollowUp') ? 'active' : '' ) }} ||
                    {{ ( Request::is('origin/show') ? 'active' : '' ) }} ||
                    {{ ( Request::is('priority/show') ? 'active' : '' ) }} ||
                    {{ ( Request::is('ticket/resetTicket') ? 'active' : '' ) }} ||
                    {{ ( Request::is('resolution/show') ? 'active' : '' ) }} ||
                    {{ ( Request::is('sponsor/show') ? 'active' : '' ) }} ||
                    {{ ( Request::is('status/show') ? 'active' : '' ) }} ||
                    {{ ( Request::is('symptom/show') ? 'active' : '' ) }} ||
                    {{ ( Request::is('tech/show') ? 'active' : '' ) }} ||
                    {{ ( Request::is('ziptimezone/search') ? 'active' : '' ) }}"
            >
                <a href="#">
                    <i class="fa fa-cog"></i> <span>Admin Tools</span>
                    <span class="pull-right-container">
                            <i class="fa fa-angle-left pull-right"></i>
                        </span>
                </a>
                <ul class="treeview-menu selected">
                    @can('view_address')
                        <li class="{{ ( Request::is('address/search') ? 'active' : '' ) }}"><a href="/address/search"><i class="fa"></i> Address</a></li>
                    @endcan
                    @can('view_country_code')
                        <li class="{{ ( Request::is('countrycode/show') ? 'active' : '' ) }}"><a href="/countrycode/show"><i class="fa"></i> Country Code</a></li>
                    @endcan
                    @can('clear_follow_up')
                        <li class="{{ ( Request::is('ticket/clearFollowUp') ? 'active' : '' ) }}"><a href="/ticket/clearFollowUp"><i class="fa"></i> Clear Follow Up</a></li>
                    @endcan
                    @can('view_origin')
                        <li class="{{ ( Request::is('origin/show') ? 'active' : '' ) }}"><a href="/origin/show"><i class="fa"></i> Origin Of Request</a></li>
                    @endcan
                    @can('view_priority')
                        <li class="{{ ( Request::is('priority/show') ? 'active' : '' ) }}"><a href="/priority/show"><i class="fa"></i> Priority</a></li>
                    @endcan
                    @can('reset_ticket')
                        <li class="{{ ( Request::is('ticket/resetTicket') ? 'active' : '' ) }}"><a href="/ticket/resetTicket"><i class="fa"></i> Reset Ticket</a></li>
                    @endcan
                    @can('view_resolution')
                        <li class="{{ ( Request::is('resolution/show') ? 'active' : '' ) }}"><a href="/resolution/show"><i class="fa"></i> Resolution</a></li>
                    @endcan
                    @can('view_sponsor')
                        <li class="{{ ( Request::is('sponsor/show') ? 'active' : '' ) }}"><a href="/sponsor/show"><i class="fa"></i> Sponsor</a></li>
                    @endcan
                    @can('view_status')
                        <li class="{{ ( Request::is('status/show') ? 'active' : '' ) }}"><a href="/status/show"><i class="fa"></i> Status</a></li>
                    @endcan
                    @can('view_symptom')
                        <li class="{{ ( Request::is('symptom/show') ? 'active' : '' ) }}"><a href="/symptom/show"><i class="fa"></i> Symptom</a></li>
                    @endcan
                    @can('view_tech')
                        <li class="{{ ( Request::is('tech/show') ? 'active' : '' ) }}"><a href="/tech/show"><i class="fa"></i> Techs</a></li>
                    @endcan
                    @can('view_ziptimezone')
                        <li class="{{ ( Request::is('ziptimezone/search') ? 'active' : '' ) }}"><a href="/ziptimezone/search"><i class="fa"></i> ZipTimeZone</a></li>
                    @endcan
                </ul>
            </li>
            <li class="{{ ( Request::is('report/') ? 'active' : '' ) }}"><a href="/report/"><i class="fa fa-rocket"></i><span class="hidden-tablet"> Reporting</span></a></li>


        </ul>
        <!-- /.sidebar-menu -->
    </section>
    <!-- /.sidebar -->
</aside>
