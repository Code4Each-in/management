<!DOCTYPE html>
<html lang="en">
@include('includes.css')

<body>
    <main id="main" class="main">
        <!-- Navigation -->
        @include('includes.header')
        <!-- Navigation -->
        @php
            $showTitle = View::hasSection('show_title') ? trim($__env->yieldContent('show_title')) : 'true';
        @endphp
        <div class="pagetitle" @if($showTitle == "false") style="display: none;" @endif>
        
            <div class="row">
                <div class="col">
                    <h1>@yield('title')</h1>
                </div>
            </div>
            
        </div><!-- End Page Title -->
        <section class="section">
            <div class="row">
       
                @yield('content')
              
            </div>
        </section>
    </main>
    @include('includes.jss')
    <script type="text/javascript">
    $(document).ready(function() {});
    </script>
    @yield('js_scripts')
</body>

</html>