@if ($errors->any())
    <div class="row">
        <div class="col-md-12 col-sm-12">
            <div class="alert alert-danger mb-3">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
@endif

@if(session()->has('message'))
    <div class="alert alert-success mt-5">{{ session()->get('message') }}</div>
@endif

@if(session()->has('error'))
    <div class="alert alert-danger mt-5">{{ session()->get('error') }}</div>
@endif
