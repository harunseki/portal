$(document).ready(function () {

    $('.select2').select2({
        width: '100%',
        placeholder: "Seçiniz",
        allowClear: true
    });

    // Başkan değişince müdürlükleri otomatik yükle
    $("#baskan").on("change", function () {
        let baskanId = $(this).val();

        $.ajax({
            url: "personeller/ajax_mudurluk_by_baskan.php",
            type: "GET",
            data: { baskan: baskanId },
            dataType: "json",
            success: function (data) {
                let $mudurluk = $("#mudurluk");
                $mudurluk.empty();

                data.forEach(function(item) {
                    $mudurluk.append(new Option(item.mudurluk, item.mudurluk, true, true));
                });

                $mudurluk.trigger('change');
            }
        });
    });

    // Excel export
    $("#exportExcel").on("click", function () {
        let table = document.getElementById('pdksTable');
        let rows = [...table.querySelectorAll('tr')];

        let csv = rows.map(row =>
            [...row.querySelectorAll('th,td')]
                .map(cell => `"${cell.innerText}"`)
                .join(",")
        ).join("\n");

        let blob = new Blob([csv], { type: 'text/csv' });
        let link = URL.createObjectURL(blob);

        let a = document.createElement('a');
        a.href = link;
        a.download = 'pdks_rapor.csv';
        a.click();
        URL.revokeObjectURL(link);
    });

});