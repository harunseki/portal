<?php
$query = "
    SELECT sp.id, sp.session_key, sp.aciklama, sp.source_type, sm.field_key
    FROM session_parameters sp
    LEFT JOIN session_mapping sm 
        ON sm.session_parametre_id = sp.id
    WHERE sp.aktif = 1
";

$result = $dba->query($query);
?>

<section class="content-header">
    <h2>Session Düzenle</h2>
</section>
<section class="content">
    <div class='col-md-12'>
        <div class='box box-warning' style="margin-top: 10px">
            <div class='box-header'>
                <div class="row">
                    <div class="col-xs-6"><h3 class='box-title'><i class="fa fa-list"></i> Session Yönetimi</h3></div>
                </div>
            </div
            <div class='box-body'>
                <div class="card shadow-sm">
                    <div class="card-body">
                        <table class="table table-bordered table-striped" id="modulTable"  style="margin-top:10px;">
                            <thead class="thead-dark">
                                <tr>
                                    <th></th>
                                    <th>Session Key</th>
                                    <th>Kaynak</th>
                                    <th>Açıklama</th>
                                    <th>Alan Seç</th>
                                    <th>Kaydet</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php $i=0; while($row = $result->fetch_assoc()): $i++; ?>
                                <tr>
                                    <td><?= $i ?></td>
                                    <td class="sessionKey">
                                        <?= htmlspecialchars($row['session_key']) ?>
                                    </td>
                                    <td>
                                        <select
                                                class="sourceSelect form-control"
                                                data-session-key="<?= htmlspecialchars($row['session_key']) ?>"
                                        >
                                            <option value="ldap" <?= $row['source_type']=='ldap'?'selected':'' ?>>LDAP</option>
                                            <option value="ybs" <?= $row['source_type']=='ybs'?'selected':'' ?>>YBS</option>
                                            <option value="yetkili" <?= $row['source_type']=='yetkili'?'selected':'' ?>>Yetkili</option>
                                        </select>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($row['aciklama']) ?>
                                    </td>
                                    <td>
                                        <select class="fieldSelect"
                                            data-session-key="<?= htmlspecialchars($row['session_key']) ?>"
                                            data-source="<?= htmlspecialchars($row['source_type']) ?>"
                                            data-selected="<?= htmlspecialchars($row['field_key'] ?? '') ?>">
                                            <option value="">Yükleniyor...</option>
                                        </select>
                                    </td>
                                    <td>
                                        <button type="button"
                                            class="saveMapping btn btn-sm btn-primary"
                                            data-session-key="<?= htmlspecialchars($row['session_key']) ?>"
                                            data-source="<?= htmlspecialchars($row['source_type']) ?>">
                                            Kaydet
                                        </button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>

                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        function loadFields(selectBox, sourceType, selectedField = null){

            selectBox.empty();
            selectBox.append('<option value="">Yükleniyor...</option>');

            $.ajax({
                url: 'moduller/get_fields.php',
                type: 'POST',
                data: { source_type: sourceType },
                dataType: 'json',
                success: function(response){

                    selectBox.empty();
                    selectBox.append('<option value="">Seçiniz</option>');

                    $.each(response, function(index, field){

                        let isSelected = (field.field_key === selectedField) ? 'selected' : '';

                        selectBox.append(
                            `<option value="${field.field_key}" ${isSelected}>
                        ${field.field_label}
                    </option>`
                        );
                    });
                }
            });
        }

        $(document).on('change', '.sourceSelect', function(){

            let row = $(this).closest('tr');
            let sourceType = $(this).val();
            let fieldSelect = row.find('.fieldSelect');

            loadFields(fieldSelect, sourceType);
        });

        $(document).ready(function() {

            /* FIELD LİSTELERİNİ DOLDUR */
            $('.fieldSelect').each(function(){

                let sourceType = $(this).data('source');
                let selectBox = $(this);

                loadFields(selectBox, sourceType, selectedField);
            });

            /* MAPPING KAYDET */
            $(document).on('click', '.saveMapping', function(){

                let row = $(this).closest('tr');

                let sessionKey = row.find('.sessionKey').text().trim();
                let sourceType = row.find('.sourceType').text().trim();
                let fieldKey   = row.find('.fieldSelect').val();

                if(!fieldKey){
                    alert("Alan seçiniz.");
                    return;
                }

                $.ajax({
                    url: 'moduller/save_mapping.php',
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        session_key: sessionKey,
                        source_type: sourceType,
                        field_key: fieldKey
                    },
                    success: function(response){

                        if(response.success){
                            toastr.success(response.message);
                        } else {
                            toastr.success(response.message);
                        }
                    },
                    error: function(xhr){

                        let msg = "Beklenmeyen hata oluştu.";

                        if(xhr.responseJSON && xhr.responseJSON.message){
                            msg = xhr.responseJSON.message;
                        }

                        toastr.error(msg);
                    }
                });

            });
        });
    </script>
</section>
