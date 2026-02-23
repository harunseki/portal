</div>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<!-- jQuery UI 1.10.3 -->
<script src="assets/js/jquery-ui-1.10.3.min.js" type="text/javascript"></script>
<!-- Sparkline -->
<script src="assets/js/plugins/sparkline/jquery.sparkline.min.js" type="text/javascript"></script>
<!-- jvectormap -->
<script src="assets/js/plugins/jvectormap/jquery-jvectormap-1.2.2.min.js" type="text/javascript"></script>
<script src="assets/js/plugins/jvectormap/jquery-jvectormap-world-mill-en.js" type="text/javascript"></script>
<!-- fullCalendar -->
<script src="assets/js/plugins/fullcalendar/index.global.min.js" type="text/javascript"></script>
<!--<script src="assets/js/plugins/fullcalendar/fullcalendar.min.js" type="text/javascript"></script>-->
<!-- jQuery Knob Chart -->
<script src="assets/js/plugins/jqueryKnob/jquery.knob.js" type="text/javascript"></script>
<!-- daterangepicker -->
<script src="assets/js/plugins/daterangepicker/daterangepicker.js" type="text/javascript"></script>
<!-- Bootstrap WYSIHTML5 -->
<script src="assets/js/plugins/bootstrap-wysihtml5/bootstrap3-wysihtml5.all.min.js" type="text/javascript"></script>
<!-- iCheck -->
<script src="assets/js/plugins/iCheck/icheck.min.js" type="text/javascript"></script>
<!-- CK Editor -->
<script src="assets/js/plugins/ckeditor/ckeditor.js" type="text/javascript"></script>
<!-- Bootstrap -->
<script src="assets/js/bootstrap.min.js" type="text/javascript"></script>

<script>
    $('#sil_modal').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget); // Button that triggered the modal
        var id = button.data('id'); // Extract info from data-* attributes
        var modal = $(this);

        document.getElementById("delete_photo").addEventListener("click", handler);
        function handler(e) {
            e.target.removeEventListener(e.type, arguments.callee);
            Sil_photo(id);
        }
    });
</script>
<script src="assets/js/plugins/input-mask/jquery.inputmask.js" type="text/javascript"></script>
<!-- DATA TABES SCRIPT -->
<script src="assets/js/plugins/datatables/jquery.dataTables.js" type="text/javascript"></script>
<script src="assets/js/plugins/datatables/dataTables.bootstrap.js" type="text/javascript"></script>
<!-- AdminLTE App -->
<script src="assets/js/AdminLTE/app.js" type="text/javascript"></script>
<!-- page script -->
<script type="text/javascript">
    $(function() {
        // Plugin API değil, yeni DataTables API'si:
        $('#example1').DataTable({
            paging: true,
            lengthChange: false,
            searching: false,
            ordering: true,
            info: true,
            autoWidth: false,
            responsive: true  // okların responsive kapsayıcıda bozulmasını da önler
        });

        $('#example2').DataTable({
            paging: true,
            lengthChange: false,
            searching: false,
            ordering: true,
            info: true,
            autoWidth: false,
            responsive: true
        });

        $('#pdksTable').DataTable({
            paging: true,
            lengthChange: false,
            searching: false,
            ordering: true,
            info: true,
            autoWidth: false,
            responsive: true
        });

    });
</script>
<script type="text/javascript">
    $(function () {
// Replace the <textarea id="editor1"> with a CKEditor
// instance, using default configuration.
        /* CKEDITOR.replace('editor1');
         CKEDITOR.replace('editor2');*/
        CKEDITOR.replaceClass = 'ckeditor';

//bootstrap WYSIHTML5 - text editor
        /*$(".textarea").wysihtml5();*/
    });

    (function ($) {
        jQuery.fn.cke_resize = function () {
            return this.each(function () {
                var $this = $(this);
                var rows = $this.attr('rows');
                var height = rows * 1;
                $this.next("div.cke").find(".cke_contents").css("height", height);
            });
        };
    })(jQuery);

    CKEDITOR.on('instanceReady', function () {
        $(".ckeditor").cke_resize();
    })
</script>
<script type="text/javascript">
    toastr.options = {
        "closeButton": false,
        "debug": false,
        "newestOnTop": true,
        "progressBar": true,
        "positionClass": "toast-bottom-right",
        "preventDuplicates": true,
        "onclick": null,
        "showDuration": "300",
        "hideDuration": "300",
        "extendedTimeOut": "500",
        "showEasing": "swing",
        "hideEasing": "linear",
        "showMethod": "fadeIn",
        "hideMethod": "fadeOut"
    };
    $(function() {
        //Datemask dd/mm/yyyy
        $("#datemask").inputmask("dd/mm/yyyy", {"placeholder": "dd/mm/yyyy"});

        //Datemask2 mm/dd/yyyy
        $("#datemask2").inputmask("mm/dd/yyyy", {"placeholder": "mm/dd/yyyy"});
        //Money Euro
        $("[data-mask]").inputmask();

        $('#cep_telefonu').inputmask('(999) 999-9999');

        //Date range picker
        $('#reservation').daterangepicker();
        //Date range picker with time picker
        $('#reservationtime').daterangepicker({timePicker: true, timePickerIncrement: 30, format: 'MM/DD/YYYY h:mm A'});
        //Date range as a button
        $('#daterange-btn').daterangepicker(
            {
                ranges: {
                    'Today': [moment(), moment()],
                    'Yesterday': [moment().subtract('days', 1), moment().subtract('days', 1)],
                    'Last 7 Days': [moment().subtract('days', 6), moment()],
                    'Last 30 Days': [moment().subtract('days', 29), moment()],
                    'This Month': [moment().startOf('month'), moment().endOf('month')],
                    'Last Month': [moment().subtract('month', 1).startOf('month'), moment().subtract('month', 1).endOf('month')]
                },
                startDate: moment().subtract('days', 29),
                endDate: moment()
            },
            function(start, end) {
                $('#reportrange span').html(start.format('MMMM D, YYYY') + ' - ' + end.format('MMMM D, YYYY'));
            }
        );

        //iCheck for checkbox and radio inputs
        $('input[type="checkbox"].minimal, input[type="radio"].minimal').iCheck({
            checkboxClass: 'icheckbox_minimal',
            radioClass: 'iradio_minimal'
        });
        //Red color scheme for iCheck
        $('input[type="checkbox"].minimal-red, input[type="radio"].minimal-red').iCheck({
            checkboxClass: 'icheckbox_minimal-red',
            radioClass: 'iradio_minimal-red'
        });
        //Flat red color scheme for iCheck
        $('input[type="checkbox"].flat-red, input[type="radio"].flat-red').iCheck({
            checkboxClass: 'icheckbox_flat-red',
            radioClass: 'iradio_flat-red'
        });

        //Colorpicker
        //$(".my-colorpicker1").colorpicker();
        //color picker with addon
        //$(".my-colorpicker2").colorpicker();

        //Timepicker
        /*$(".timepicker").timepicker({
            showInputs: false
        });*/
    });
</script>
<script src="assets/js/chosen.jquery.js" type="text/javascript"></script>

<script src="assets/js/prism.js" type="text/javascript" charset="utf-8"></script>

<script type="text/javascript">
    var config = {
        '.chosen-select'           : {},
        '.chosen-select-deselect'  : {allow_single_deselect:true},
        '.chosen-select-no-single' : {disable_search_threshold:10},
        '.chosen-select-no-results': {no_results_text:'Oops, nothing found!'},
        '.chosen-select-width'     : {width:"95%"}
    }
    for (var selector in config) {
        $(selector).chosen(config[selector]);
    }
</script>

<style>
    .chosen-container.chosen-container-single {
        width: 100% !important; /* or any value that fits your needs */
    }
    .chosen-container.chosen-container-multi {
        width: 100% !important; /* or any value that fits your needs */
    }
</style>
<script>
    $(document).ready(function() {
        // Sidebar'daki treeview linklerine tıklandığında
        $('.sidebar-menu .treeview > a').on('click', function(e) {

            // Tıklanan linkin 'li.treeview' ebeveynini bul
            var $clickedParentLi = $(this).parent('.treeview');

            // Tıklanan menü *dışındaki* 'active' (açık) olan *diğer* menüleri bul
            // .not($clickedParentLi) ifadesi, tıklanan öğeyi hariç tutar
            var $otherOpenMenus = $('.sidebar-menu .treeview.active').not($clickedParentLi);

            // Diğer açık menüleri kapat
            if ($otherOpenMenus.length > 0) {
                $otherOpenMenus.removeClass('active');
                // AdminLTE'nin animasyonunu taklit etmek için slideUp() kullanıyoruz
                $otherOpenMenus.children('.treeview-menu').slideUp();
            }

            // Not: Kullandığınız temanın kendi script'i (örn: app.js)
            // tıklanan öğeyi açma/kapama işlemini zaten yapacaktır.
            // Bu kod sadece *diğerlerini* kapatmayı garanti eder.
        });
    });
</script>
</body>
</html>