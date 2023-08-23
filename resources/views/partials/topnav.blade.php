<style>
    /* Custom styles for the status icons */
    .status-icon {
        width: 30px;
        height: 30px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        position: relative;
        font-size: 17px;
        border: 2px solid transparent;
        /* Add transparent border for spacing */
    }

    .online {
        background-color: #28a745;
        /* Green circle for online */
        border-color: #28a745;
        /* Border color for online */
    }

    .offline {
        background-color: #7d7c6c;
        /* Gray circle for offline */
        border-color: #6c757d;
        /* Border color for offline */
    }

    .unavailable {
        background-color: #dc3545;
        /* Red circle for unavailable */
        border-color: #dc3545;
        /* Border color for unavailable */
    }

    /* Styles for the icons inside the circles */
    .status-icon::before {
        content: "✔";
        /* Default symbol */
        color: white;
        /* Default color for the symbol */
    }

    .offline::before {
        content: "⚠";
        /* X symbol for offline */
    }

    .unavailable::before {
        content: "✖";
        /* Warning symbol for unavailable */
    }

    .status-div {
        margin-top: -6px;
        right: 3px;
        color: #5E6974 !important;
        font-weight: 600;
        margin-right: 10px;
    }
</style>
<!-- top navigation -->
<div class="top_nav">
    <div class="nav_menu">
        <div class="nav toggle">
            <a id="menu_toggle"><i class="fa fa-bars"></i></a>
        </div>
        <nav class="nav navbar-nav">
            <ul class=" navbar-right">

                <li class="nav-item dropdown open" style="padding-left: 15px;">
                    <a href="javascript:;" class="user-profile dropdown-toggle" aria-haspopup="true" id="navbarDropdown"
                        data-toggle="dropdown" aria-expanded="false">
                        {{ Auth::check() ? Auth::user()->name : '' }}
                    </a>
                    <div class="dropdown-menu dropdown-usermenu pull-right" aria-labelledby="navbarDropdown">
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-jet-dropdown-link class="dropdown-item" href="{{ route('logout') }}" onclick="event.preventDefault();
                                    this.closest('form').submit();">
                                <i class="fa fa-sign-out pull-right"></i> {{ __('Log Out') }}
                            </x-jet-dropdown-link>
                        </form>
                    </div>
                </li>
                <li class="nav-item">
                    <div @if(Auth::user() && Auth::user()->status != "1") style="display: none;" @endif class="status-div"
                        id="online-status-div">
                        <span class="status-icon online ml-1"></span> Available
                    </div>
                    <div @if(Auth::user() && Auth::user()->status != "2") style="display: none;" @endif class="status-div"
                        id="offline-status-div">
                        <span class="status-icon offline ml-1"></span> Offline
                    </div>
                    <div @if(Auth::user() && Auth::user()->status != "3") style="display: none;" @endif class="status-div"
                        id="unavailable-status-div">
                        <span class="status-icon unavailable ml-1"></span> Unavailable
                    </div>
                </li>
            </ul>
        </nav>
    </div>
</div>
<!-- /top navigation -->
