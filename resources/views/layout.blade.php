<!DOCTYPE html>
<html lang="en">
    @include('includes.css')
    <title> @yield('title') </title>
    <body>
        <div id="wrapper">
           <!-- Navigation -->
           @include('includes.header')
			<!-- Navigation -->
            <div id="page-wrapper">
                <div class="container-fluid">
                    <div class="row">
                        <br><br>
		                <div class="col-lg-12">
		                    @yield('content')
		                </div>
		            </div>  
                </div>
            </div>
        </div>
        @include('includes.jss')
        <script type="text/javascript">
           
        </script>
        @yield('js_scripts')
    </body>
</html>
