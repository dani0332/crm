<style>
    /* Custom styles for the status icons */
    .status-div {
        position: relative;
        display: inline-block;

        &>span {
            line-height: 1;
            padding-top: 0.5rem;
            padding-bottom: 0.5rem;
            padding-left: 0.75rem;
            padding-right: 0.75rem;
            background-color: #f3f4f6;
            border-radius: 0.25rem;
            display: inline-flex;
            align-items: center;
            max-width: 100%;
            gap: 0.5rem;

            &>svg {
                width: 1.25rem;
                height: 1.25rem;
                flex-shrink: 0;
            }
        }

        &>div {
            position: absolute;
            top: 0;
            right: 0;
            margin: -5%;
            border-radius: 9999px;
            border-width: 2px;
            border-color: #f5f5f5;

            &>div {
                padding: 0.375rem;
                overflow: hidden;
            }
        }
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

                <li class="nav-item dropdown open" style="padding-left: 15px; margin-top: 8px;">
                    <a href="javascript:;" class="user-profile dropdown-toggle" aria-haspopup="true" id="navbarDropdown" data-toggle="dropdown" aria-expanded="false">
                        {{ Auth::check() ? Auth::user()->name : '' }}
                    </a>
                    <div class="dropdown-menu dropdown-usermenu pull-right" aria-labelledby="navbarDropdown">
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-dropdown-link class="dropdown-item" href="{{ route('logout') }}" onclick="event.preventDefault();
                                    this.closest('form').submit();">
                                <i class="fa fa-sign-out pull-right"></i> {{ __('Log Out') }}
                            </x-dropdown-link>
                        </form>
                    </div>
                </li>
                {{-- <li>
                    <div @if(Auth::user() && Auth::user()->status != "1") style="display: none;" @endif class="status-div"
                        id="online-status-div">
                        <span>
                            <svg class="text-success" xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24">
                                <g fill="none">
                                    <path fill-rule="evenodd" clip-rule="evenodd" d="M5.636 5.636a1 1 0 0 0-1.414-1.414c-4.296 4.296-4.296 11.26 0 15.556a1 1 0 0 0 1.414-1.414a9 9 0 0 1 0-12.728zm14.142-1.414a1 1 0 1 0-1.414 1.414a9 9 0 0 1 0 12.728a1 1 0 1 0 1.414 1.414c4.296-4.296 4.296-11.26 0-15.556zM8.464 8.464A1 1 0 0 0 7.05 7.05a7 7 0 0 0 0 9.9a1 1 0 1 0 1.414-1.414a5 5 0 0 1 0-7.072zM16.95 7.05a1 1 0 1 0-1.414 1.414a5 5 0 0 1 0 7.072a1 1 0 0 0 1.414 1.414a7 7 0 0 0 0-9.9zM9 12a3 3 0 1 1 6 0a3 3 0 0 1-6 0z" fill="currentColor" />
                                </g>
                            </svg>

                            <span>
                                Available
                            </span>
                        </span>
                        <div class="bg-success">
                            <div></div>
                        </div>
                    </div>
                    <div @if(Auth::user() && Auth::user()->status != "2") style="display: none;" @endif class="status-div"
                        id="offline-status-div">
                        <span>
                            <svg class="text-secondary" xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24">
                                <g fill="none">
                                    <path fill-rule="evenodd" clip-rule="evenodd" d="M3.707 2.293a1 1 0 0 0-1.414 1.414L15.535 16.95l2.829 2.828l1.929 1.93a1 1 0 0 0 1.414-1.415l-1.253-1.254c3.607-4.321 3.382-10.76-.676-14.817a1 1 0 1 0-1.414 1.414a9.001 9.001 0 0 1 .668 11.982l-1.425-1.425a7.002 7.002 0 0 0-.657-9.143a1 1 0 1 0-1.414 1.414a5.002 5.002 0 0 1 .636 6.294l-1.465-1.465a3 3 0 0 0-4-4l-7-7zM3.75 8.4a1 1 0 0 0-1.834-.8C.161 11.624.928 16.485 4.222 19.778a1 1 0 0 0 1.414-1.414A9.004 9.004 0 0 1 3.749 8.4zm3.32 2.766a1 1 0 0 0-1.972-.332A6.992 6.992 0 0 0 7.05 16.95a1 1 0 1 0 1.414-1.414a4.993 4.993 0 0 1-1.394-4.37z" fill="currentColor"></path>
                                </g>
                            </svg>
                            <span>
                                Offline
                            </span>
                        </span>
                        <div class="bg-secondary">
                            <div></div>
                        </div>
                    </div>
                    <div @if(Auth::user() && Auth::user()->status != "3") style="display: none;" @endif class="status-div"
                        id="unavailable-status-div">
                        <span>
                            <svg class="text-danger" xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24">
                                <g fill="none">
                                    <path fill-rule="evenodd" clip-rule="evenodd" d="M3.707 2.293a1 1 0 0 0-1.414 1.414L15.535 16.95l2.829 2.828l1.929 1.93a1 1 0 0 0 1.414-1.415l-1.253-1.254c3.607-4.321 3.382-10.76-.676-14.817a1 1 0 1 0-1.414 1.414a9.001 9.001 0 0 1 .668 11.982l-1.425-1.425a7.002 7.002 0 0 0-.657-9.143a1 1 0 1 0-1.414 1.414a5.002 5.002 0 0 1 .636 6.294l-1.465-1.465a3 3 0 0 0-4-4l-7-7zM3.75 8.4a1 1 0 0 0-1.834-.8C.161 11.624.928 16.485 4.222 19.778a1 1 0 0 0 1.414-1.414A9.004 9.004 0 0 1 3.749 8.4zm3.32 2.766a1 1 0 0 0-1.972-.332A6.992 6.992 0 0 0 7.05 16.95a1 1 0 1 0 1.414-1.414a4.993 4.993 0 0 1-1.394-4.37z" fill="currentColor"></path>
                                </g>
                            </svg>
                            <span>
                                Unavailable
                            </span>
                        </span>
                        <div class="bg-danger">
                            <div></div>
                        </div>
                    </div>
                </li> --}}

            </ul>
        </nav>
    </div>
</div>
<!-- /top navigation -->
