<?php
$q = $dba->query("SELECT * FROM services WHERE durum != 5 ORDER BY name");
?>

<section class="content-header">
    <h2>API Servis Yönetimi</h2>
</section>

<section class="content">
    <div class='row' style="margin-top:20px;">
        <div class='col-md-12'>
            <div class='box box-success'>
                <div class='box-header'>
                    <div class="row">
                        <div class="col-xs-6">
                            <h3 class='box-title'><i class="fa fa-server"></i> Servisler</h3>
                        </div>
                        <div class="col-xs-6 text-right" >
                            <button class="btn btn-success btn-sm" data-toggle="modal" data-target="#serviceModal" onclick="yeniService()" style="margin: 10px">
                                <i class="fa fa-plus"></i> Yeni Servis
                            </button>
                        </div>
                    </div>
                </div>

                <div class='box-body'>
                    <table class="table table-bordered table-striped">
                        <thead>
                        <tr>
                            <th>ID</th>
                            <th>Ad</th>
                            <th>URL</th>
                            <th>Protocol</th>
                            <th>Auth</th>
                            <th>Durum</th>
                            <th>Son Kontrol</th>
                            <th>İşlem</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php $i=0; while($row=$q->fetch_assoc()): $i++; ?>
                            <tr>
                                <td><?=$i?></td>
                                <td><?=$row['name']?></td>
                                <td><?=$row['base_url'] . $row['health_endpoint']?></td>
                                <td><?=$row['protocol']?></td>
                                <td><?=$row['auth_type']?></td>
                                <td>
                                    <?php
                                    if($row['last_status']===1)
                                        echo '<span class="label label-success">UP</span>';
                                    else if($row['last_status']===0)
                                        echo '<span class="label label-danger">DOWN</span>';
                                    else
                                        echo '<span class="label label-default">UNKNOWN</span>';
                                    ?>
                                </td>
                                <td><?=$row['last_checked']?></td>
                                <td>
                                    <button class="btn btn-primary btn-sm" onclick="editService(<?=$row['id']?>)"><i class="fa fa-edit"></i></button>
                                    <button class="btn btn-danger btn-sm" onclick="deleteService(<?=$row['id']?>)"><i class="fa fa-trash"></i></button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- MODAL -->
<div class="modal fade" id="serviceModal">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-info">
                <h4>Servis Ekle / Düzenle</h4>
                <button class="close" data-dismiss="modal">&times;</button>
            </div>

            <form id="serviceForm">
                <input type="hidden" name="id" id="id">
                <div class="modal-body">
                    <div class="row">
                        <div class="form-group col-md-6">
                            <label>Servis Adı</label>
                            <input name="name" id="name" class="form-control" required>
                        </div>
                        <div class="form-group col-md-6">
                            <label>Protocol</label>
                            <select name="protocol" id="protocol" class="form-control">
                                <option value="http">HTTP</option>
                                <option value="soap">SOAP</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Base URL</label>
                        <input name="base_url" id="base_url" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label>Health Endpoint</label>
                        <input name="health_endpoint" id="health_endpoint" class="form-control">
                    </div>

                    <div class="row">
                        <div class="form-group col-md-6">
                            <label>Method</label>
                            <select name="method" id="method" class="form-control">
                                <option>GET</option>
                                <option>POST</option>
                            </select>
                        </div>

                        <div class="form-group col-md-6">
                            <label>Auth Type</label>
                            <select name="auth_type" id="auth_type" class="form-control">
                                <option value="none">None</option>
                                <option value="bearer">Bearer</option>
                                <option value="query_token">Query Token</option>
                                <option value="header_token">Header Token</option>
                                <option value="basic">Basic</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Headers JSON</label>
                        <textarea name="headers" id="headers" class="form-control" rows="3"></textarea>
                    </div>

                    <div class="form-group">
                        <label>Request Body</label>
                        <textarea name="request_body" id="request_body" class="form-control" rows="3"></textarea>
                    </div>

                    <div class="form-group">
                        <label>Params</label>
                        <textarea name="query_params" id="query_params" class="form-control" rows="3"></textarea>
                    </div>

                    <div class="form-group">
                        <label>Auth Config JSON</label>
                        <textarea name="auth_config" id="auth_config" class="form-control" rows="3"></textarea>
                    </div>

                    <div class="form-group">
                        <label>Token Access</label>
                        <input name="access_token" id="access_token" class="form-control">
                    </div>

                    <div class="form-group">
                        <label>Token Endpoint</label>
                        <input name="token_endpoint" id="token_endpoint" class="form-control">
                    </div>

                    <div class="form-group">
                        <label>Token Config JSON</label>
                        <textarea name="token_config" id="token_config" class="form-control" rows="3"></textarea>
                    </div>

                    <div class="form-group">
                        <label>Success Condition JSON</label>
                        <textarea name="success_condition" id="success_condition" class="form-control" rows="3"></textarea>
                    </div>

                    <div class="row">
                        <div class="form-group col-md-4">
                            <label>Timeout</label>
                            <input type="number" name="timeout" id="timeout" value="10" class="form-control">
                        </div>

                        <div class="form-group col-md-4">
                            <label>Check Interval</label>
                            <input type="number" name="check_interval" id="check_interval" value="300" class="form-control">
                        </div>

                        <div class="form-group col-md-4">
                            <label>Aktif</label>
                            <select name="is_active" id="is_active" class="form-control">
                                <option value="1">Aktif</option>
                                <option value="0">Pasif</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-success">Kaydet</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    toastr.options = {
        "hideDuration": "1000"
    }
    function yeniService() {
        $("#serviceForm")[0].reset();
        $("#id").val("");
    }

    function editService(id) {
        $.post("services/services_getir.php", {id:id}, function(res){
            let s = JSON.parse(res);
            $("#id").val(s.id);
            $("#name").val(s.name);
            $("#protocol").val(s.protocol);
            $("#base_url").val(s.base_url);
            $("#health_endpoint").val(s.health_endpoint);
            $("#method").val(s.method);
            $("#auth_type").val(s.auth_type);
            $("#headers").val(s.headers);
            $("#request_body").val(s.request_body);
            $("#query_params").val(s.query_params);
            $("#auth_config").val(s.auth_config);
            $("#access_token").val(s.access_token);
            $("#token_endpoint").val(s.token_endpoint);
            $("#token_config").val(s.token_config);
            $("#success_condition").val(s.success_condition);
            $("#timeout").val(s.timeout);
            $("#check_interval").val(s.check_interval);
            $("#is_active").val(s.is_active);
            $("#serviceModal").modal("show");
        });
    }

    $("#serviceForm").submit(function(e){
        e.preventDefault();
        $.post("services/services_kaydet.php", $(this).serialize(), function(res){
            let r = JSON.parse(res);
            if(r.ok) {
                toastr.success("Servis başarıyla kaydedildi!");
                // 1 saniye sonra sayfayı yenile
                setTimeout(() => location.reload(), 1000);
            } else {
                toastr.error(r.mesaj || "Bir hata oluştu!");
            }
        });
    });

    function deleteService(id) {
        if(confirm("Silinsin mi?")) {
            $.post("services/services_sil.php",{id:id}, function(){
                toastr.success("Servis başarıyla silindi!");
                // 1 saniye sonra sayfayı yenile
                setTimeout(() => location.reload(), 1000);
            });
        }
    }
</script>